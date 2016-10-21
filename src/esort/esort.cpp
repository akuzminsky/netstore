/*$Id: esort.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $*/
#include <stdio.h>
#include <math.h>
#include <string.h>
#include <unistd.h>
#include <libgen.h>
#include <stdlib.h>

#include "common.h"

typedef struct node {
	unsigned long KEY;
	ArchFlow RECORD;
	struct node* LOSER;
	unsigned RN;
	struct node* PE;
	struct node* PI;
} NODE;

typedef struct listnode{
	NODE node;
	struct listnode* next;
} LISTNODE;

int init_series(char*, char*, unsigned);
int merge_files(char*, char*, char*);
int merge_series(FILE*, FILE*, FILE*);
int distribute_series(char*, char*, char*);
int copy_series(char*, char*, char*);

int usage(char*);
NODE* getnode(LISTNODE*, unsigned);

char prgn[256];

int main(int argc, char** argv)
{
	unsigned P = 1024;
	extern char *optarg;
	char ch;
	unsigned rnum = 0;
	int skip_init = 0;
	
	char t1[L_tmpnam], t2[L_tmpnam], t3[L_tmpnam];
	t1[0] = '\0';
	t2[0] = '\0';
	t3[0] = '\0';
	while((ch = getopt(argc, argv, "f:o:t:p:s")) != -1){
		switch(ch){
			case 'f': strncpy(t1, optarg, sizeof(t1)); break;
			case 't': strncpy(t2, optarg, sizeof(t2)); break;
			case 'o': strncpy(t3, optarg, sizeof(t3)); break;
			case 's': skip_init = 1; break;
			case 'p': P = atoi(optarg); break;
			default:
				usage(basename(argv[0]));
				exit(-1);
			}
		}
	if(t1 == '\0' || t2 == '\0' || t3 == '\0'){
		usage(basename(argv[0]));
		exit(-1);
		}
	strncpy(prgn, argv[0], sizeof(prgn));
	if(!skip_init){
		init_series(t1, t3, P);
		distribute_series(t1, t2, t3);
		}
	while(1 != (rnum = merge_files(t1, t2, t3))){
		printf("Copy %u series onto T3\n", rnum);
		copy_series(t1, t2, t3);
		}
	return 0;
}

int init_series(char* s1, char* s2, unsigned P)
{	
	FILE* T1;
	FILE* T2;
	
	unsigned j;
	NODE* Qbuf;
	unsigned u;
	
	LISTNODE* X;
	LISTNODE* px;
	LISTNODE* pe;
	LISTNODE* pi;
	LISTNODE* fx;
	unsigned RMAX = 0;
	unsigned RC = 0;
	unsigned long LASTKEY = 0;
	int sw = 0;
	NODE* Q;
	unsigned RQ = 0;
	NODE* T;

	if(NULL == (T1 = fopen(s1, "r"))){
		fprintf(stderr, "Can't open file %s\n", s1);
		fprintf(stderr, "%s: init_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T2 = fopen(s2, "w"))){
		fprintf(stderr, "Can't open file %s\n", s2);
		fprintf(stderr, "%s: init_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}

	// Allocate memory for NODES
	X = (LISTNODE*)malloc(sizeof(LISTNODE));
	if(X == NULL){
		fprintf(stderr, "Unable to allocate %u bytes of memory\n", sizeof(LISTNODE));
		fprintf(stderr, "%s: init_series(): malloc(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	X->next = NULL;
	px = X;
	for(j = 0; j < P; j++){
		px->next = (LISTNODE*)malloc(sizeof(LISTNODE));
		if(px->next == NULL){
			fprintf(stderr, "%s: Warning: Unable to allocate addtional %u bytes of memory\n",basename(prgn), sizeof(LISTNODE));
			fprintf(stderr, "%s: init_series(): malloc(): %s\n", basename(prgn), strerror(errno));
			P = j;
			fprintf(stderr, "%s: Using binary tree with %u nodes... \n", basename(prgn), P);
			}
		bzero(px->next, sizeof(LISTNODE));
		px = px->next;
		px->next = NULL;
		}
	for(pe = X, j = 0; j < P/2; pe = pe->next, j++);
	for(px = X, j = 0, pi = X; px != NULL; px = px->next, j++){
		px->node.LOSER = &(px->node);
		px->node.RN = 0;
		px->node.PE = &(pe->node);
		px->node.PI = &(pi->node);
		if(j % 2 == 1){
			pi = pi->next;
			pe = pe->next;
			}
		}
	Q = &(X->node);

R2:	
	while(RQ <= RMAX){
		if(RC == RQ){
			if(RQ != 0){
				fwrite(&(Q->RECORD), sizeof(ArchFlow), 1, T2);
				LASTKEY = Q->KEY;
				}	
			if(1 == fread(&(Q->RECORD), sizeof(ArchFlow), 1, T1)){
				Q->KEY = Q->RECORD.client_id;
				if(Q->KEY < LASTKEY || sw == 0){
					sw = RQ != 0 ? 1: 0;
					RQ = RQ + 1;
					if(RQ > RMAX){
						RMAX = RQ;
						}
					}
				}
			else{
				RQ = RMAX + 1;
				}
			T = Q->PE;

R6:
			if(T->RN < RQ || (T->RN == RQ && (T->LOSER)->KEY < Q->KEY)){
				Qbuf = T->LOSER;
				T->LOSER = Q;
				Q = Qbuf;
				u = T->RN;
				T->RN = RQ;
				RQ = u;
				}
			if(T == getnode(X, 1)){
				goto R2;
				}
			else{
				T = T->PI;
				goto R6;
				}
			}
		RC = RQ;
		}
	// Free allocated memory
	px = X;
	while(px != NULL){
		fx = px;
		px = px->next;
		free(fx);
		}
	fclose(T1);
	fclose(T2);
	return 0;
}

int usage(char* prg)
{
	fprintf(stderr, "Usage: %s -f infile -o outfile -t buffer [-p num] [-s]\n", prg);
	fprintf(stderr, "      -s skip creating seires on `infile'(e.g. `infile' and `buffer' already sorted)\n");
	return 0;
}

// Ditriburte tape #3 to tapes #1 and #2
int distribute_series(char* s1, char* s2, char* s3)
{
	ArchFlow archbuf1;
	unsigned long LASTKEY = 0;
	int sw = 0;
	FILE* T1;
	FILE* T2;
	FILE* T3;
	FILE* fbuf;
	
	if(NULL == (T1 = fopen(s1, "w"))){
		fprintf(stderr, "Can't open file %s\n", s1);
		fprintf(stderr, "%s: distribute_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T2 = fopen(s2, "w"))){
		fprintf(stderr, "Can't open file %s\n", s2);
		fprintf(stderr, "%s: distribute_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T3 = fopen(s3, "r"))){
		fprintf(stderr, "Can't open file %s\n", s3);
		fprintf(stderr, "%s: distribute_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	fbuf = T2;
	while(!feof(T3)){
		if(1 != fread(&archbuf1, sizeof(ArchFlow), 1, T3)){
			break;
			}
		if(archbuf1.client_id < LASTKEY || sw == 0){
			sw = 1;
			fbuf = fbuf == T1 ? T2 : T1;
			}
		LASTKEY = archbuf1.client_id;
		fwrite(&archbuf1, sizeof(ArchFlow), 1, fbuf);
		}
	fclose(T1);
	fclose(T2);
	fclose(T3);
	return 0;
}

// Merges tape #1 and #2 to #3
int merge_files(char* s1, char* s2, char* s3)
{
	unsigned Rnum = 0; // Number of series

	FILE* T1;
	FILE* T2;
	FILE* T3;
	
	if(NULL == (T1 = fopen(s1, "r"))){
		fprintf(stderr, "Can't open file %s\n", s1);
		fprintf(stderr, "%s: merge_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T2 = fopen(s2, "r"))){
		fprintf(stderr, "Can't open file %s\n", s2);
		fprintf(stderr, "%s: merge_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T3 = fopen(s3, "w"))){
		fprintf(stderr, "Can't open file %s\n", s3);
		fprintf(stderr, "%s: merge_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	Rnum = 0;
	while(!feof(T1) || !feof(T2)){
		merge_series(T1, T2, T3);
		Rnum++;
		}
	fclose(T1);
	fclose(T2);
	fclose(T3);
	return Rnum;
}

// Copies tapes #1 and #2 to #3
int copy_series(char* s1, char* s2, char* s3)
{
	ArchFlow archbuf;
	unsigned long LASTKEY = 0, KEY = 0;
	int sw = 0;
	FILE* T1;
	FILE* T2;
	FILE* T3;
	FILE* fbuf;
	
	
	if(NULL == (T1 = fopen(s1, "w"))){
		fprintf(stderr, "Can't open file %s\n", s1);
		fprintf(stderr, "%s: copy_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T2 = fopen(s2, "w"))){
		fprintf(stderr, "Can't open file %s\n", s2);
		fprintf(stderr, "%s: copy_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	if(NULL == (T3 = fopen(s3, "r"))){
		fprintf(stderr, "Can't open file %s\n", s2);
		fprintf(stderr, "%s: copy_series(): fopen(): %s\n", basename(prgn), strerror(errno));
		exit(-1);
		}
	fbuf = T1;
	while(!feof(T3)){
		if(1 == fread(&archbuf, sizeof(ArchFlow), 1, T3)){
			KEY = archbuf.client_id;
			if(KEY < LASTKEY && sw != 0){
				sw = 1;
				fbuf = fbuf == T2 ? T1 : T2;
				}
			sw = 1;
			LASTKEY = KEY;
			fwrite(&archbuf, sizeof(ArchFlow), 1, fbuf);
			}
		}
	fclose(T1);
	fclose(T2);
	fclose(T3);
	return 0;
}

inline NODE* getnode(LISTNODE* X, unsigned j)
{
 unsigned i;
 NODE* res = &(X->node);
 for(i = 0; i < j; i++){
 	res = &(X->next->node);
	X = X->next;
 	}
 return res;
}

int merge_series(FILE* T1, FILE* T2, FILE* T3)
{
	ArchFlow* cartridge[3];
	ArchFlow buf1, buf2;
	cartridge[0] = NULL;
	cartridge[1] = NULL;
	cartridge[2] = NULL;
	int RN1 = 0, RN2 = 0;
	unsigned long LASTKEY1 = 0, LASTKEY2 = 0;
	while(1){
		if(cartridge[1] == NULL && RN1 == 0){
			if(1 == fread(&buf1, sizeof(ArchFlow), 1, T1)){
				if(buf1.client_id < LASTKEY1){
					RN1++;
					}
				cartridge[1] = &buf1;
				LASTKEY1 = buf1.client_id;
				}
			}
		if(cartridge[2] == NULL && RN2 == 0){
			if(1 == fread(&buf2, sizeof(ArchFlow), 1, T2)){
				if(buf2.client_id < LASTKEY2){
					RN2++;
					}
				cartridge[2] = &buf2;
				LASTKEY2 = buf2.client_id;
				}
			}
		if(RN1 != 0) cartridge[1] = NULL;
		if(RN2 != 0) cartridge[2] = NULL;
		if(cartridge[1] == NULL && cartridge[2] == NULL){
			break;
			}
		else{
			if(cartridge[1] != NULL && cartridge[2] == NULL){
				fwrite(&buf1, sizeof(ArchFlow), 1, T3);
				cartridge[1] = NULL;
				}
			if(cartridge[2] != NULL && cartridge[1] == NULL){
				fwrite(&buf2, sizeof(ArchFlow), 1, T3);
				cartridge[2] = NULL;
				}
			if(cartridge[2] != NULL && cartridge[1] != NULL){
				if(cartridge[1]->client_id < cartridge[2]->client_id){
					fwrite(&buf1, sizeof(ArchFlow), 1, T3);
					cartridge[1] = NULL;
					}
				else{
					fwrite(&buf2, sizeof(ArchFlow), 1, T3);
					cartridge[2] = NULL;
					}
				}
			}
		}
	if(!feof(T1)){
		fseek(T1, -sizeof(ArchFlow), SEEK_CUR);
		}
	if(!feof(T2)){
		fseek(T2, -sizeof(ArchFlow), SEEK_CUR);
		}
	fflush(T1);
	fflush(T2);
	fflush(T3);
	return 0;
}
