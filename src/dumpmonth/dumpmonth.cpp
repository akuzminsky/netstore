/* $Id: dumpmonth.cpp,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $ */

#include "common.h"

void usage(char*);

int main (int argc, char** argv) 
{
	char ch;
	extern char *optarg;
	char src_file[128];
	char dst_file[128];
	FILE* fs;
	FILE* fd;
	time_t ts_begin = 0;
	time_t ts_end = 0;
	ArchFlow aflow;

	src_file[0] = '\0';
	dst_file[0] = '\0';
	while((ch = getopt(argc, argv, "f:o:b:e:")) != -1){
		switch(ch){
			case 'f': strncpy(src_file, optarg, sizeof(src_file)); break;
			case 'o': strncpy(dst_file, optarg, sizeof(dst_file));break;
			case 'b': ts_begin = strtoul(optarg, NULL, 10); break;
			case 'e': ts_end = strtoul(optarg, NULL, 10); break;
			default:
				usage(basename(argv[0]));
				exit(-1);
			}
		}
	if(ts_begin == 0 || ts_end == 0 || src_file[0] == '\0' || dst_file[0] == '\0'){
		usage(basename(argv[0]));
		exit(-1);
		}
	if(NULL == (fs = fopen(src_file, "r"))){
		fprintf(stderr, "Can't open file %s\n", src_file);
		fprintf(stderr, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
		exit(-1);
		}
	if(NULL == (fd = fopen(dst_file, "w"))){
		fprintf(stderr, "Can't open file %s\n", dst_file);
		fprintf(stderr, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
		exit(-1);
		}
	while(!feof(fs)){
		if(1 != fread(&aflow, sizeof(ArchFlow), 1, fs)){
			break;
			}
		if(aflow.timestamp >= ts_begin && aflow.timestamp < ts_end){
			if(1 != fwrite(&aflow, sizeof(ArchFlow), 1, fd)){
				fprintf(stderr, "Can't write to the file %s\n", dst_file);
				fprintf(stderr, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
				exit(-1);
				}
			}
		}
	fclose(fs);
	fclose(fd);
	exit(0);
}

void usage(char* prg)
{
	fprintf(stderr,
		"Usage: %s -f file -o file -b timestamp -e timestamp\n"
		"          \n"
		"          -f file  source file where data are given\n"
		"          -o file  destionation file, where interesting data are written\n"
		"          -b timestamp  date in seconds from Epoch beginning \n"
		"                        from which data need to be written to the destionation file.\n"
		"                        Included in period\n"
		"          -e timestamp  date in seconds from Epoch ending \n"
		"                        before which data need to be written to the destionation file\n"
		"                        Not included in period\n",
		prg);
}
