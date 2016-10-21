/* $Id: loader.cpp,v 1.2 2008/12/05 12:02:47 ingoth Exp $ */

#include "common.h"
#include "classes.h"
#include "config.h"

#define MBYTE 1048576

using namespace charge;
using namespace conf;

typedef map<unsigned long, CLIENT> IndexCLIENT;

int usage(char*);

extern bool netstore_debug;

int main (int argc, char** argv) 
{
  char ch;
  extern char *optarg;
  extern int optind;
  string datafile("none");
  string archfile("/dev/stdout");
  string flowsfile("flows");
  string flows_filterfile("flows_filter");
  string configfile(DEFAULT_CONFIG_FILE);
 
  unsigned long sequence = 0;
  int frecognized = 0;
 
  IndexCLIENT indexbyip;
  IndexCLIENT indexbyinterface;
  IndexCLIENT::iterator i_index;
  map<unsigned long, FILTER>::iterator i_filter;
  unsigned long client_id = 0;
  unsigned long network = 0;
  unsigned long netmask = 0;
  unsigned long ipnum = 0;
  unsigned long interface_id = 0;
  bool skip = false;
  
  FILE *f;
  FILE *farch;
  FILE *fflows;
  FILE *fflows_filter;
 
  Flow flow;
  ArchFlow archflow;
  AccFlow accflow;
 
  time_t current_time = 0;
 
  while((ch = getopt(argc, argv, "f:gsd:a:i:j:")) != -1){
    switch(ch){
      case 'f': configfile = optarg; break;
      case 'd': datafile = optarg; break;
      case 'a': archfile = optarg; break;
      case 'i': flowsfile = optarg; break;
      case 'j': flows_filterfile = optarg; break;
      case 'g': netstore_debug = true; break;
      case 's': skip = true; break;
      default: usage(basename(argv[0]));
      }
    }
  argc -= optind;
  if(argc != 0){
    usage(basename(argv[0]));
    }
 
  CONFIG config(configfile);
  if(datafile == "none"){
      datafile = config.get_value("data");
      }
  if(NULL == (f = fopen(datafile.c_str(), "r"))){
    vlog(LOG_ERR, "Can't open file '%s'\n", datafile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  if(NULL == (farch = fopen(archfile.c_str(), "a"))){
    vlog(LOG_ERR, "Can't open file '%s'\n", archfile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  if(NULL == (fflows = fopen(flowsfile.c_str(), "w"))){
    vlog(LOG_ERR, "Can't open file '%s'\n", flowsfile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  if(NULL == (fflows_filter = fopen(flows_filterfile.c_str(), "w"))){
    vlog(LOG_ERR, "Can't open file '%s'\n", flows_filterfile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
 
  mysqlpp::Connection con(true);
  try{
    con.connect(config.get_value("mysql_database"), config.get_value("mysql_host"), 
          config.get_value("mysql_user"), config.get_value("mysql_password"));
    mysqlpp::Query query = con.query();
    query << "BEGIN";
    query.execute();
    query.reset();
    query << "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED";
    query.execute();
    // Create index of client by ip addresses.
    mysqlpp::Result res;
    mysqlpp::Result::iterator i_res;
    mysqlpp::Row row;

    query.reset();
    query << "SELECT client_id, network, netmask FROM client_network LEFT JOIN client ON client_network.client_id = client.id WHERE client.blocked = 'n' LOCK IN SHARE MODE";
    res = query.store();
    for(i_res = res.begin(); i_res != res.end(); i_res++){
      row = *i_res;
      client_id = (unsigned long)row["client_id"];
      network = (unsigned long)row["network"];
      netmask = (unsigned long)row["netmask"];
      // Number of ip addresses
      ipnum = ~netmask + 1;
      for(unsigned long i = 0; i < ipnum; i++){
        indexbyip.insert(make_pair(network + i, CLIENT(con, client_id)));
        }
      }
    query.reset();
    query << "SELECT client_id, interface_id "
      << "FROM client_interface "
      << "LEFT JOIN client ON client_interface.client_id = client.id "
      << "WHERE client.blocked = 'n' LOCK IN SHARE MODE";
    res = query.store();
    for(i_res = res.begin(); i_res != res.end(); i_res++){
      row = *i_res;
      client_id = (unsigned long)row["client_id"];
      interface_id = (unsigned long)row["interface_id"];
      indexbyinterface.insert(make_pair(interface_id, CLIENT(con, client_id)));
      }
    current_time = time(NULL);
    if(1 != fread(&flow, sizeof(flow), 1, f)){
      vlog(LOG_WARNING, "Hmm.. It seems data file '%s' has no data\n", datafile.c_str());
      exit(0);
      }
    sequence = flow.sequence;
    rewind(f);
    while(!feof(f)){
      if(1 != fread(&flow, sizeof(flow), 1, f)){
        break;
        }
      if(flow.timestamp > current_time){
        vlog(LOG_ERR, "Flow's timestamp '%lu' greater than current time '%lu'. Traffic from the future?\n", flow.timestamp, current_time);
	exit(0);
        }
      if(flow.sequence != sequence){
        if(flow.sequence == 0){
          sequence = 0;
	  }
	else{
	  if(skip){
            sequence = flow.sequence;
	    }
	  else{
            // Data are wrong!!!
	    vlog(LOG_ERR, "Corrupted file '%s'\n", datafile.c_str());
	    vlog(LOG_ERR, "Expected sequence counter: %lu\n", sequence);
	    vlog(LOG_ERR, "Actual sequence counter: %lu\n", flow.sequence);
	    vlog(LOG_ERR, "Timestamp: %s", ctime(&(flow.timestamp)));
	    exit(-1);
	    }
	  }
        }
      sequence++;
      memset(&archflow, 0, sizeof(archflow));
      memset(&accflow, 0, sizeof(accflow));
      
      archflow.sequence = flow.sequence;
      archflow.client_id = 0;
      archflow.dir = any;
      archflow.timestamp = flow.timestamp;
      archflow.src_addr = flow.src_addr;
      archflow.dst_addr = flow.dst_addr;
      archflow.in_if_id = flow.in_if_id;
      archflow.out_if_id = flow.out_if_id;
      archflow.d_pkts = flow.d_pkts;
      archflow.d_octets = flow.d_octets;
      archflow.src_port = flow.src_port;
      archflow.dst_port = flow.dst_port;
      archflow.protocol = flow.protocol;
      archflow.src_as = flow.src_as;
      archflow.dst_as = flow.dst_as;
      
      accflow.id = 0;
      archflow.dir = any;
      accflow.timestamp = flow.timestamp;
      accflow.d_octets = flow.d_octets;
      
      if(netstore_debug){
        vlog(LOG_DEBUG, "Flow:\n");
	show_flow(&flow);
        }
      
      i_index = indexbyip.end(); 
      frecognized = 0;
      
      if((i_index = indexbyip.find(flow.src_addr)) != indexbyip.end()){
        frecognized++;
	for(i_filter = i_index->second.map_filters.begin();
			i_filter != i_index->second.map_filters.end();
			i_filter++){
          if(netstore_debug){
            i_filter->second.show();
	    }
	  if(i_filter->second.satisfy(flow)){
            accflow.id = i_filter->second.get_id();
	    accflow.dir = out;
	    if(netstore_debug){
              vlog(LOG_DEBUG, "Matched!\n");
	      }
	    if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows_filter)){
              vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
	      vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	      exit(-1);
	      }
	    }
	  }
        archflow.client_id = i_index->second.get_id();
	archflow.dir = out;
	
	if(1 != fwrite(&archflow, sizeof(archflow), 1, farch)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", archfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
	
	accflow.id = i_index->second.get_id();
	accflow.dir = out;
	
	if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      
      i_index = indexbyip.end();
      
      if((i_index = indexbyip.find(flow.dst_addr)) != indexbyip.end()){
        frecognized++;
	for(i_filter = i_index->second.map_filters.begin();
			i_filter != i_index->second.map_filters.end();
			i_filter++){
          if(netstore_debug){
            i_filter->second.show();
	    }
	  if(i_filter->second.satisfy(flow)){
            if(netstore_debug){
              vlog(LOG_DEBUG, "Matched!\n");
	      }
	    accflow.id = i_filter->second.get_id();
	    accflow.dir = in;
	    if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows_filter)){
              vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
              vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	      exit(-1);
	      }
	    }
          }
	archflow.client_id = i_index->second.get_id();
	archflow.dir = in;
	
	if(1 != fwrite(&archflow, sizeof(archflow), 1, farch)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", archfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
	
	accflow.id = i_index->second.get_id();
	accflow.dir = in;
	
	if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows)){
          vlog(LOG_ERR, "Can't write to file %s\n", flowsfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      
      i_index = indexbyinterface.end();
      if((i_index = indexbyinterface.find(flow.in_if_id)) != indexbyinterface.end()){
        frecognized++;
	for(i_filter = i_index->second.map_filters.begin();
			i_filter != i_index->second.map_filters.end();
			i_filter++){
          if(netstore_debug){
            i_filter->second.show();
	    }
	  if(i_filter->second.satisfy(flow)){
            if(netstore_debug){
              vlog(LOG_DEBUG, "Matched!\n");
	      }
	    
	    accflow.id = i_filter->second.get_id();
	    accflow.dir = in;
	    if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows_filter)){
              vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
	      vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	      exit(-1);
	      }
	    }
	  }
	
	archflow.client_id = i_index->second.get_id();
	archflow.dir = out;
	
	if(1 != fwrite(&archflow, sizeof(archflow), 1, farch)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", archfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
	
	accflow.id = i_index->second.get_id();
	accflow.dir = out;
	if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
	  vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      i_index = indexbyinterface.end();
      if((i_index = indexbyinterface.find(flow.out_if_id)) != indexbyinterface.end()){
        frecognized++;
	for(i_filter = i_index->second.map_filters.begin();
			i_filter != i_index->second.map_filters.end();
			i_filter++){
          if(netstore_debug){
            i_filter->second.show();
	    }
	  if(i_filter->second.satisfy(flow)){
            if(netstore_debug){
              vlog(LOG_DEBUG, "Matched!\n");
	      }
	    accflow.id = i_filter->second.get_id();
	    accflow.dir = in;
	    if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows_filter)){
              vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
	      vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	      exit(-1);
	      }
	    }
	  }
	archflow.client_id = i_index->second.get_id();
	archflow.dir = in;
	if(1 != fwrite(&archflow, sizeof(archflow), 1, farch)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", archfile.c_str());
          vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
	accflow.id = i_index->second.get_id();
	accflow.dir = in;
	if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
          vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      if(frecognized == 0){
        archflow.client_id = 0;
	archflow.dir = any;
	if(1 != fwrite(&archflow, sizeof(archflow), 1, farch)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", archfile.c_str());
          vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
	accflow.id = 0;
	accflow.dir = any;
	if(1 != fwrite(&accflow, sizeof(accflow), 1, fflows)){
          vlog(LOG_ERR, "Can't write to file '%s'\n", flowsfile.c_str());
          vlog(LOG_ERR, "%s: fwrite(): %s\n", basename(argv[0]), strerror(errno));
	  exit(-1);
	  }
        }
      }
    fclose(f);
    fclose(farch);
    fclose(fflows);
    fclose(fflows_filter);
    
    query.reset();
    query << "COMMIT";
    query.execute();
    }
  catch(mysqlpp::Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    return -1;
    }
  exit(0);
}

int usage(char* s)
{
 return vlog(LOG_ERR,
         "Usage: %s [-g] [-f file] [-d file] [-s size]\n"
         "     -f file read config from `file'\n"
         "     -d file load data from `file'\n"
         "     -a file store archive data in `file'(Default stdout)\n"
         "     -i file store flows data in `file'(Default flows)\n"
         "     -j file store filter flows data in `file'(Default flows_filter)\n"
         "     -s size temorary file buffer size in megabytes(Default 100)\n"
         "     -g force loader to be more verbose\n"
         "     -s skip looking for valid data if sequence counter doesn't match\n", s);
}

