/*$Id: chkdata.0.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $*/
#include "common.h"


int usage(char*);

int main(int argc, char** argv)
{
  FILE* f;
  Flow flow;
  unsigned long sequence = 0;
  int argval;
  char *prg;
  time_t print_time = 0;
  bool quiet = false;
  
  prg = argv[0];
  while(-1 != (argval = getopt(argc,argv,"qh"))){
    switch(argval){
      case 'q': quiet = true; break;
      case 'h': usage(prg); exit(-1); break;
      default : usage(prg); exit(-1); break;
      }
    }
  argc -= optind + 1;
  argv += optind - 1;

  if(NULL == (f = fopen(argv[1], "r"))){
    vlog(LOG_ERR, "Error while reading file '%s'\n", argv[1]);
    usage(prg);
    exit(-1);
    }
  // Reading the first record
  if(1 != fread(&flow, sizeof(Flow), 1, f)){
    exit(0);
    }
  sequence = flow.sequence;
  rewind(f);
  while(!feof(f)){
    if(1 != fread(&flow, sizeof(Flow), 1, f)){
      break;
      }
    if(!quiet){
      if(time(NULL) - print_time > 1){
        vlog(LOG_ERR, "Checking timestamp %s", ctime(&(flow.timestamp)));
        print_time =  time(NULL);
        }
      }
    if(flow.sequence != sequence){
      if(flow.sequence == 0){
        sequence = 0;
        }
      else{
        // Data are wrong
        if(!quiet){
          vlog(LOG_ERR, "'%s' has incorrect format\n", argv[1]);
          vlog(LOG_ERR, "Expected counter: %lu\n", sequence);
          vlog(LOG_ERR, "Actual counter: %lu\n", flow.sequence);
          vlog(LOG_ERR, "Timestamp: %s", ctime(&(flow.timestamp)));
          exit(1);
        }
      }
    }
    sequence++;
    }
  if(!quiet) { vlog(LOG_ERR, "'%s' is OK\n", argv[1]);}
  return 0;
}

int usage(char* prg)
{
  return vlog(LOG_ERR, "Usage:\n\
  %s [-q] <file>\n\
  <file>  File to be checked.\n\
  -q  Quiet mode. All output is suppresed.\n\
Return values:\n\
  0\tIf <file> has correct format\n\
  1\tIf <file> has incorrect format\n\
  -1\tIf an error has occured\n", prg);
  return 0;
}
