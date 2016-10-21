/*$Id: arch2str.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $*/
#include "common.h"

int usage(char*);

int main(int argc, char** argv)
{
  FILE *f = NULL;
  char *prg;
  int argval;
  ArchFlow arch;
  unsigned long client_id = 0;
  unsigned long long int incoming = 0;
  unsigned long long int outcoming = 0;
  bool summarize = false;
  time_t begin = 0, end = 0;

  prg = argv[0];
  while(-1 != (argval = getopt(argc,argv,"f:c:sb:e:"))){
    switch(argval){
      case 'f': 
        if(NULL == (f = fopen(optarg, "r"))){
          vlog(LOG_ERR, "Can't open file '%s'\n", optarg);
          }
       break;
      case 'c': client_id = atoi(optarg); break;
      case 'b': begin = atoi(optarg); break;
      case 'e': end = atoi(optarg); break;
      case 's': summarize = true; break;
      default: usage(prg); exit(-1);
      }
    }
  if(f == NULL) {
    usage(prg);
    exit(-1);
    }
  vlog(LOG_INFO, "ArchFlow size: %u\n", sizeof(ArchFlow));
  if(client_id !=0){
    cl_seek(f, client_id);
    }
  while(!feof(f)){
    if(1 != fread(&arch, sizeof(ArchFlow), 1, f)){
      break;
      }
    if((begin == 0 || (begin != 0 && arch.timestamp >= begin)) 
		   && (end == 0 || (end != 0 && arch.timestamp < end))
		   && (client_id == 0 || (client_id != 0 && arch.client_id == client_id))){
      
    incoming += (arch.dir == in ? arch.d_octets: 0);
    outcoming += (arch.dir == out ? arch.d_octets: 0);
    printf("%lu;%lu;%d;%u;%lu;%lu;%u;%u;%lu;%lu;%u;%u;%u;%u;%u\n",
                  arch.sequence,
                  arch.client_id,
                  arch.dir,
                  arch.timestamp,
                  arch.src_addr,
                  arch.dst_addr,
                  arch.in_if_id,
                  arch.out_if_id,
                  arch.d_pkts,
                  arch.d_octets,
                  arch.protocol,
                  arch.src_port,
                  arch.dst_port,
                  arch.src_as,
                  arch.dst_as);
      }
    }
  if(summarize){
    printf("Total traffic: in = %llu, out = %llu\n", incoming, outcoming);
    }
 return 0;
}

int usage(char* prog)
{
  return vlog(LOG_ERR,
    "Usage: %s -f file [-c client_id] [-s]\n"
    "\t-f file\tRead archive data from `file'\n"
    "\t-c client_id\tOutput data only for client_id\n"
    "\t-s \tOutput summary info\n", prog);
}

