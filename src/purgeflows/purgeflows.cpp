/* $Id: purgeflows.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"

using namespace charge;
using namespace conf;

int usage(char*);

extern bool netstore_debug;

int main (int argc, char** argv) 
{
  char ch;
  extern char *optarg;
  extern int optind;
  string srcfile("/dev/stdin");
  string dstfile("/dev/stdout");
  string configfile(DEFAULT_CONFIG_FILE);
  
  map<unsigned long, int> save_flows;
  unsigned long client_id = 0;
  int save = 1;
  
  FILE *fsrcfile;
  FILE *fdstfile;
  
  ArchFlow archflow;
  
  while((ch = getopt(argc, argv, "f:s:d:g")) != -1){
   switch(ch){
     case 'f': configfile = optarg; break;
     case 's': srcfile = optarg; break;
     case 'd': dstfile = optarg; break;
     case 'g': netstore_debug = true; break;
     default: usage(basename(argv[0]));
     }
   }
  argc -= optind;
  if(argc != 0){
   usage(basename(argv[0]));
    }
  
  CONFIG config(configfile);
  if(NULL == (fsrcfile = fopen(srcfile.c_str(), "r"))){
    vlog(LOG_ERR, "Can't open file %s\n", srcfile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  if(NULL == (fdstfile = fopen(dstfile.c_str(), "w"))){
    vlog(LOG_ERR, "Can't open file %s\n", dstfile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  mysqlpp::Connection con(true);
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  try{
    con.connect(config.get_value("mysql_database"), config.get_value("mysql_host"), 
           config.get_value("mysql_user"), config.get_value("mysql_password"));
    vlog(LOG_DEBUG,"Starting transaction\n");
    query << "BEGIN";
    query.execute();
    query.reset();
    query << "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED";
    query.execute();
    // Create index of client by ip addresses.
    vlog(LOG_DEBUG,"Creating map (client_id, save_flows) ... ");
    query.reset();
    query << "SELECT id, IF(save_flows = 'yes', 1, 0) AS save_flows FROM client LOCK IN SHARE MODE";
    res = query.store();
    for(i_res = res.begin(); i_res != res.end(); i_res++){
      row = *i_res;
      client_id = (unsigned long)row["id"];
      save = (int)row["save_flows"];
      save_flows.insert(make_pair(client_id, save));
      }
    if(netstore_debug){
      vlog(LOG_DEBUG, "Success\n");
      vlog(LOG_DEBUG,"MAP(client_id, save_flows):\n");
      for(map<unsigned long, int>::iterator i = save_flows.begin(); i != save_flows.end(); i++){
        vlog(LOG_DEBUG,"%lu\t%d\n", (*i).first, (*i).second);
        }
      }
    vlog(LOG_DEBUG, "Purge flows ...\n");
    while(!feof(fsrcfile)){
      if(1 != fread(&archflow, sizeof(archflow), 1, fsrcfile)){
        break;
      }
      if(archflow.client_id == 0 || save_flows[archflow.client_id] == 1){
        if(1 != fwrite(&archflow, sizeof(archflow), 1, fdstfile)){
          vlog(LOG_ERR, "Can't write to file %s\n", dstfile.c_str());
          vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      }
    vlog(LOG_DEBUG, "Done\n");
    fclose(fsrcfile);
    fclose(fdstfile);
    vlog(LOG_DEBUG, "Commiting transaction\n");
    query.reset();
    query << "COMMIT";
    query.execute();
    }
  catch(mysqlpp::Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());   
    exit(-1);
    }
  return 0;
}

int usage(char* s)
{
  return vlog(LOG_ERR,
          "Usage: %s [-g] [-f configfile] [-s srcfile] [-d dstfile]\n"
          "     -f file read config from `file'\n"
          "     -s file get flows from `file'(Default stdin)\n"
          "     -d file store flows in `file'(Default stdout)\n"
          "     -g force loader to be more verbose\n", s);
}

