/*$Id: acc2str.cpp,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $*/
#include "common.h"

int usage(char*);

int main(int argc, char** argv)
{
  FILE* f = NULL;
  char* prg;
  int argval;
  AccFlow acc;
  unsigned long long sum = 0;
  unsigned long long incoming = 0;
  unsigned long long outcoming = 0;
  unsigned long client_id = 0;
  bool summarize = false;
  time_t max = 0 , min = 0;
  
  prg = argv[0];
  while(-1 != (argval = getopt(argc,argv,"f:c:s"))){
    switch(argval){
      case 'f': 
        if(NULL == (f = fopen(optarg, "r"))){
          vlog(LOG_ERR, "Can't open file '%s'\n", optarg);
          }
       break;
      case 'c': client_id = atoi(optarg); break;
      case 's': summarize = true; break;
      default: usage(prg); exit(-1);
      }
    }
  if(f == NULL) {
    usage(prg);
    exit(-1);
    }
  while(!feof(f)){
    if(1 != fread(&acc, sizeof(AccFlow), 1, f)){
      break;
      }
    // If that's the first iteration, set max and min values of timestamp
    if(ftell(f) == sizeof(AccFlow)){
      max = acc.timestamp;
      min = acc.timestamp;
      }
    if(client_id == 0 || (client_id != 0 && acc.id == client_id)){
      sum += acc.d_octets;
      incoming += (acc.dir == in ? acc.d_octets: 0);
      outcoming += (acc.dir == out ? acc.d_octets: 0);
      if(acc.timestamp > max) max = acc.timestamp;
      if(acc.timestamp < min) min = acc.timestamp;
      printf("%lu;%d;%u;%lu\n", acc.id, acc.dir, acc.timestamp, acc.d_octets);
      }
    }
  if(summarize){
    printf("\nsum = %llu in = %llu, out = %llu\n", sum, incoming, outcoming);
    printf("Timespan: (%u, %u)\n", min, max);
    }
  return 0;
}

int usage(char* prog)
{
  return vlog(LOG_ERR,
    "Usage: %s -f file [-c client_id] [-s]\n"
    "\t-f file\tRead accounting data from `file'\n"
    "\t-c client_id\tOutput data only for client_id\n"
    "\t-s \tOutput summary info\n", prog);
}

