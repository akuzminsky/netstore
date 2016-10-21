/*$Id: repairdata.0.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $*/
#include <stdio.h>
#include <time.h>
#include <unistd.h>
#include "common.h"


//int cl_seek(unsigned long);
FILE* f, *fout;
int usage(char*);

int main(int argc, char** argv)
{
  Flow flow;
  unsigned long sequence = 0;
  time_t begin_ts = 0;
  time_t end_ts = 0 ;
  
  int argval;

  end_ts = time(NULL);
  
  while(-1 != (argval = getopt(argc,argv,"b:e:"))){
    switch(argval){
      case 'b': begin_ts = strtoul(optarg, NULL, 10); break;
      case 'e': end_ts = strtoul(optarg, NULL, 10); break;
      case 'h': usage(argv[0]); exit(-1); break;
      default : usage(argv[0]); exit(-1); break;
      }
    }
   argc -= optind + 1;
  argv += optind - 1;
  if(NULL == (f = fopen(argv[1], "r"))){
    fprintf(stderr, "Error while reading file %s\n", argv[1]);
    usage(argv[0]);
    exit(-1);
    }
  if(NULL == (fout = fopen(argv[2], "w"))){
    fprintf(stderr, "Error while openning file %s for writing\n", argv[2]);
    usage(argv[0]);
    exit(-1);
    }

  // Reading the first record
  if(1 != fread(&flow, sizeof(Flow), 1, f)){
    exit(0);
    }
  sequence = flow.sequence;
  rewind(f);
  time_t print_time = 0;  
  while(!feof(f)){
    if(1 != fread(&flow, sizeof(Flow), 1, f)){
      break;
      }
    if( time(NULL) - print_time > 1){
      printf("%s", ctime(&(flow.timestamp)));
      print_time =  time(NULL);
      }
    if(flow.sequence != sequence){
      if(flow.sequence == 0){
        sequence = 0;
        }
      else{
        // Data are wrong
        char ch = 0;
        long i = ftell(f) - sizeof(Flow);
        while(ch != 'y'){
          fseek(f, i, SEEK_SET);
          if(1 != fread(&flow, sizeof(Flow), 1, f)){
            break;
            }
          printf("%s", ctime(&(flow.timestamp)));
          printf("%lu;%u;%lu;%lu;%u;%u;%lu;%lu;%u;%u;%u;%u;%u\n",
                  flow.sequence,
                  flow.timestamp,
                  flow.src_addr,
                  flow.dst_addr,
                  flow.in_if_id,
                  flow.out_if_id,
                  flow.d_pkts,
                  flow.d_octets,
                  flow.protocol,
                  flow.src_port,
                  flow.dst_port,
                  flow.src_as,
                  flow.dst_as);
          printf("Ok? ");
          ch = getchar();
          i++;
          }
        sequence = flow.sequence;
        }
      }
    sequence++;
    if(flow.timestamp >= begin_ts && flow.timestamp < end_ts){
      if(1 != fwrite(&flow, sizeof(Flow), 1, fout)){
        fprintf(stderr, "Error while writing to the file %s\n", argv[2]);
        exit(-1);  
        }
       }
     }
  return 0;
}

int usage(char* prg)
{
  vlog(LOG_ERR, "Usage:\n%s <original file> <repaired file>\n", prg);
  return 0;
}
