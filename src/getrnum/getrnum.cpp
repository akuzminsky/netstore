#include "common.h"

int main(int argc, char** argv)
{
 FILE* f = fopen(argv[1], "r");
 ArchFlow buf;
 unsigned long long int rnum = 0;
 unsigned long LASTKEY;
 fread(&buf, sizeof(ArchFlow), 1, f);
 LASTKEY = buf.client_id;
 while(!feof(f)){
 	if(1 == fread(&buf, sizeof(ArchFlow), 1, f)){
		if(buf.client_id != LASTKEY){
			rnum++;
			fprintf(stderr, "%lu\n", buf.client_id);
			}
		}
	LASTKEY = buf.client_id;
 	}
 printf("Number of series: %llu\n", rnum);
}
