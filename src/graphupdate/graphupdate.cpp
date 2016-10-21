/* $Id: graphupdate.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"

#define RRD_DIR "/var/db/rrd"

using namespace conf;
using namespace mysqlpp;

typedef struct statset{
  Traffic whole;
  Traffic smtp;
  Traffic pop3;
  Traffic web;
  Traffic dns;
  Traffic msn;
  } STAT_SET;
typedef map<time_t, STAT_SET> RRD_DATA;

int usage(char*);
int mkdir_p(char*);
int exist(const char*);

extern bool netstore_debug;
char* prg;

int main(int argc, char** argv)
{
  string host("localhost");
  string db("netstore");
  string user(getlogin());
  string password("");

  string flowsfile("flows");
  FILE *fflows;
 
  int argval;
 
  prg = argv[0];
  // Parse arguments....
  // First checking if config file is given
  while(-1 != (argval = getopt(argc,argv,"h:d:u:p:?f:gic:"))){
    switch(argval){
      case 'c': {CONFIG config(optarg);
		host = config.get_value("mysql_host");
		db = config.get_value("mysql_database");
		user = config.get_value("mysql_user");
		password = config.get_value("mysql_password");
		break;
		}
      default : break;
      }
    }
  // reset getopt()
  optind = 1; optreset = 1;
  // Parsing the rest options
  // 
  while(-1 != (argval = getopt(argc,argv,"h:d:u:p:?f:gic:"))){
    switch(argval){
      case 'c': break;
      case 'h': host = optarg; break;
      case 'd': db = optarg; break;
      case 'u': user = optarg; break;
      case 'p': password = optarg; break;
      case 'f': flowsfile = optarg; break;
      case 'g': netstore_debug = true; break;
      case 'i': password = getpass("Password:"); break;
      case '?': usage(prg); 
        exit(-1);
        break;
      default : usage(prg); 
        exit(-1); 
        break;
      }
    }
  
  Connection con(true);
  Query query = con.query();
  Result res;
  Result::iterator i;
  Row row;
  
  //time_t lastload = 0;
  time_t starttimestamp = 0;
  time_t stoptimestamp = 0;
  // Connecting to mysql server
  try{
    // Open files
    if(NULL == (fflows = fopen(flowsfile.c_str(), "r"))){
      vlog(LOG_ERR, "Can't open file '%s'\n", flowsfile.c_str());
      vlog(LOG_ERR, "%s: fopen(): %s\n", basename(prg), strerror(errno));
      exit(-1);
      }
    mkdir_p(RRD_DIR);
    // Determine minimum and maximum timestamp
    ArchFlow flow;
    if(1 == fread(&flow, sizeof(ArchFlow), 1, fflows)){
      starttimestamp = flow.timestamp;
      stoptimestamp = starttimestamp;
      }
    FILE* rrd_stream;
    if(NULL == (rrd_stream = popen("rrdtool -", "w"))){
      vlog(LOG_ERR, "Can't open rrdtool\n");
      vlog(LOG_ERR, "%s: popen(): %s\n", basename(prg), strerror(errno));
      exit(-1);
      }
    vlog(LOG_DEBUG, "Take a look to tha data file and fetch Timespan\n");
    while(!feof(fflows)){
      if(1 == fread(&flow, sizeof(ArchFlow), 1, fflows)){
        if(starttimestamp > flow.timestamp){
          starttimestamp = flow.timestamp;
          }
        if(stoptimestamp < flow.timestamp){
          stoptimestamp = flow.timestamp;
          }
        }
      }
    vlog(LOG_DEBUG, "Timespan: %lu .. %lu\n", starttimestamp, stoptimestamp);
    con.connect(db.c_str(), host.c_str(), user.c_str(), password.c_str());
    // Start transaction
    vlog(LOG_INFO, "Starting transaction\n");
    query.reset();
    query << "BEGIN";
    query.execute();
    // Get client identifiers from DB
    query.reset();
    query << "SELECT id FROM client";
    vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
    res = query.store();
    vlog(LOG_DEBUG, "Prepare buffer for RRD data\n");
    STAT_SET traffic;
    traffic.whole.incoming = 0;
    traffic.whole.outcoming = 0;
    traffic.smtp.incoming = 0;
    traffic.smtp.outcoming = 0;
    traffic.pop3.incoming = 0;
    traffic.pop3.outcoming = 0;
    traffic.web.incoming = 0;
    traffic.web.outcoming = 0;
    traffic.dns.incoming = 0;
    traffic.dns.outcoming = 0;
    traffic.msn.incoming = 0;
    traffic.msn.outcoming = 0;
    RRD_DATA rrd_buf;
    for(time_t t = starttimestamp; t <= stoptimestamp; t += 300){
      rrd_buf.insert(pair<time_t, STAT_SET>(t,traffic));
      }
    vlog(LOG_DEBUG, "Created %lu samples\n", rrd_buf.size());
    vlog(LOG_DEBUG, "Updating RRD database\n");
    for(i = res.begin(); i != res.end(); i++){
      row = *i;
      vlog(LOG_DEBUG, "Flush buffer\n");
      RRD_DATA::iterator i_rrd_buf;
      for(i_rrd_buf = rrd_buf.begin(); i_rrd_buf != rrd_buf.end(); i_rrd_buf++){
        i_rrd_buf->second.whole.incoming = 0;
        i_rrd_buf->second.whole.outcoming = 0;
        i_rrd_buf->second.smtp.incoming = 0;
        i_rrd_buf->second.smtp.outcoming = 0;
        i_rrd_buf->second.pop3.incoming = 0;
        i_rrd_buf->second.pop3.outcoming = 0;
        i_rrd_buf->second.web.incoming = 0;
        i_rrd_buf->second.web.outcoming = 0;
        i_rrd_buf->second.dns.incoming = 0;
        i_rrd_buf->second.dns.outcoming = 0;
        i_rrd_buf->second.msn.incoming = 0;
        i_rrd_buf->second.msn.outcoming = 0;
        }
      unsigned long client_id = 0;
      client_id = (unsigned long)row["id"];
      if(netstore_debug){
        vlog(LOG_DEBUG, "Should be empty buffer for client %lu(whole traffic only)\n", client_id);
        for(i_rrd_buf = rrd_buf.begin(); i_rrd_buf != rrd_buf.end(); i_rrd_buf++){
          vlog(LOG_DEBUG, "%s: %llu %llu\n", ctime(&(i_rrd_buf->first)), i_rrd_buf->second.whole.incoming, i_rrd_buf->second.whole.outcoming);
          }
        }
      vlog(LOG_DEBUG, "Client id %lu\n", client_id);
      if(0 == cl_seek(fflows, client_id)){
        vlog(LOG_ERR, "Warning: No data for client with id %lu\n", client_id);
        continue;
        }
      vlog(LOG_DEBUG, "Fill buffer with traffic\n");
      while(!feof(fflows)){
        if(1 == fread(&flow, sizeof(ArchFlow), 1, fflows)){
          if(flow.client_id != client_id){
            break;
            }
          if(rrd_buf.end() == (i_rrd_buf = rrd_buf.lower_bound(flow.timestamp))){
            //vlog(LOG_ERR, "Timestamp %lu in the data is out of calculating range!\n", flow.timestamp);
	    // Actually traffic is from the last 5 minutes of the timespan
	    i_rrd_buf--;
            continue;
            }
          i_rrd_buf->second.whole.incoming += (flow.dir == in) ? flow.d_octets : 0;
          i_rrd_buf->second.whole.outcoming += (flow.dir == out) ? flow.d_octets : 0;
          // SMTP
          if(flow.protocol == 6 && (flow.src_port == 25 || flow.dst_port == 25 )){
            i_rrd_buf->second.smtp.incoming += (flow.dir == in) ? flow.d_octets : 0;
            i_rrd_buf->second.smtp.outcoming += (flow.dir == out) ? flow.d_octets : 0;
            }
          // POP3
          if(flow.protocol == 6 && (flow.src_port == 110 || flow.dst_port == 110)){
            i_rrd_buf->second.pop3.incoming += (flow.dir == in) ? flow.d_octets : 0;
            i_rrd_buf->second.pop3.outcoming += (flow.dir == out) ? flow.d_octets : 0;
            }
          // WEB
          if(flow.protocol == 6 && (flow.src_port == 80
                          || flow.dst_port == 80
                          || flow.src_port == 3128
                          || flow.dst_port == 3128
                          || flow.src_port == 8000
                          || flow.dst_port == 8000
                          || flow.src_port == 8001
                          || flow.dst_port == 8001
                          || flow.src_port == 8080
                          || flow.dst_port == 8080
                          || flow.src_port == 8081
                          || flow.dst_port == 8081
                          || flow.src_port == 443
                          || flow.dst_port == 443)){
            i_rrd_buf->second.web.incoming += (flow.dir == in) ? flow.d_octets : 0;
            i_rrd_buf->second.web.outcoming += (flow.dir == out) ? flow.d_octets : 0;
            }
          // DNS
          if((flow.protocol == 6 || flow.protocol == 11 ) && 
                          (flow.src_port == 53 || flow.dst_port == 53)){
            i_rrd_buf->second.dns.incoming += (flow.dir == in) ? flow.d_octets : 0;
            i_rrd_buf->second.dns.outcoming += (flow.dir == out) ? flow.d_octets : 0;
            }
          // MSN
          if((flow.protocol == 6 || flow.protocol == 11 ) && 
                          ((flow.src_port >= 135  && flow.src_port <= 139)
                           || flow.dst_port == 445)){
            i_rrd_buf->second.msn.incoming += (flow.dir == in) ? flow.d_octets : 0;
            i_rrd_buf->second.msn.outcoming += (flow.dir == out) ? flow.d_octets : 0;
            }
          }
        }
      vlog(LOG_DEBUG, "buffer filled\n");
      if(netstore_debug){
        vlog(LOG_DEBUG, "buffer for client %lu(whole traffic only)\n", client_id);
        for(i_rrd_buf = rrd_buf.begin(); i_rrd_buf != rrd_buf.end(); i_rrd_buf++){
          vlog(LOG_ERR, "%s: %llu %llu\n", ctime(&(i_rrd_buf->first)), i_rrd_buf->second.whole.incoming, i_rrd_buf->second.whole.outcoming);
          }
        }
      string rrd_cmd;
      string rrd_path;
      ostringstream ost;
      ost << RRD_DIR << "/traffic" << client_id << ".rrd";
      rrd_path = ost.str();
      //sprintf(rrd_path, "%s/traffic%lu.rrd", RRD_DIR, client_id);
      if(!exist(rrd_path.c_str())){
        vlog(LOG_DEBUG, "%s doesn't exist. Creating...\n", rrd_path.c_str());
        // create rrd database
        ost.clear(); ost.str("");
        ost << "create " << rrd_path << " --start " << starttimestamp - 300 << " DS:ifInOctets:ABSOLUTE:600:0:U DS:ifOutOctets:ABSOLUTE:600:0:U DS:SmtpIn:ABSOLUTE:600:0:U DS:SmtpOut:ABSOLUTE:600:0:U DS:Pop3In:ABSOLUTE:600:0:U DS:Pop3Out:ABSOLUTE:600:0:U DS:WebIn:ABSOLUTE:600:0:U DS:WebOut:ABSOLUTE:600:0:U DS:DnsIn:ABSOLUTE:600:0:U DS:DnsOut:ABSOLUTE:600:0:U DS:MsnIn:ABSOLUTE:600:0:U DS:MsnOut:ABSOLUTE:600:0:U RRA:AVERAGE:0.5:1:600 RRA:AVERAGE:0.5:6:700 RRA:AVERAGE:0.5:24:775 RRA:AVERAGE:0.5:288:797" << endl;
        rrd_cmd = ost.str();
        vlog(LOG_DEBUG, "Sending command %s\n", rrd_cmd.c_str());
        fprintf(rrd_stream, "%s", rrd_cmd.c_str());
        vlog(LOG_DEBUG, "done\n");
        }
      vlog(LOG_DEBUG, "Store buffer in RRD database\n");
      for(i_rrd_buf = rrd_buf.begin(); i_rrd_buf != rrd_buf.end(); i_rrd_buf++){
        ost.clear(); ost.str("");
        ost << "update " << RRD_DIR << "/traffic" << client_id << ".rrd "
                << i_rrd_buf->first << ":"
                << i_rrd_buf->second.whole.incoming << ":" 
                << i_rrd_buf->second.whole.outcoming << ":"
                << i_rrd_buf->second.smtp.incoming << ":" 
                << i_rrd_buf->second.smtp.outcoming << ":"
                << i_rrd_buf->second.pop3.incoming << ":" 
                << i_rrd_buf->second.pop3.outcoming << ":"
                << i_rrd_buf->second.web.incoming << ":" 
                << i_rrd_buf->second.web.outcoming << ":"
                << i_rrd_buf->second.dns.incoming << ":" 
                << i_rrd_buf->second.dns.outcoming << ":"
                << i_rrd_buf->second.msn.incoming << ":" 
                << i_rrd_buf->second.msn.outcoming << endl;
        rrd_cmd = ost.str();
        vlog(LOG_DEBUG, "Sending command %s\n", rrd_cmd.c_str());
        fprintf(rrd_stream, "%s", rrd_cmd.c_str());
        }
      vlog(LOG_DEBUG, "done\n");
      }
    pclose(rrd_stream);
    vlog(LOG_DEBUG, "Commiting transaction\n");
    query.reset();
    query << "COMMIT";
    query.execute();
     return 0;
    }
  catch (mysqlpp::Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    if(con.connected()){
      vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
      query.reset();
      query << "ROLLBACK";
      query.execute();
      }
    exit(-1);
    }
}

int usage(char *s)
{
 return vlog(LOG_ERR, "Usage: %s -h host -u user -p password -d database -i \n\
  -h host       Host of mysql server\n\
  -u user       User name to login to mysql\n\
  -p password   Hmm... password\n\
  -d database   Database name\n\
  -i            Ask password in command line rather using `-p' key\n\
  -f file       File with flows\n\n", s);
}


int mkdir_p(char* dir)
{
  struct stat sb;
  int status = 0;

  /* Check if dir exist */
  status = stat(dir, &sb);
  if(status != 0){
    /* Check, may be dir doesn't exist? */
    if(errno == ENOENT){
      vlog(LOG_ERR, "warning: %s doesn't exist, Trying create it... ", dir);
      /* really dosen't exist! Should be created */
      if(0 != mkdir(dir, 0755)){
        vlog(LOG_ERR, "failure\n");
        vlog(LOG_ERR, "Could not create directory %s\n", dir);
        perror("mkdir_p");
        exit(-1);
        }
      vlog(LOG_ERR, "success\n");
      }
    else{
      vlog(LOG_ERR, "Could stat %s\n", dir);
      perror("mkdir_p");
      exit(-1);
      }
    }
  return 1;
}

int exist(const char* file)
{
  struct stat sb;
  int status = 0;
  status = stat(file, &sb);
  if(status != 0){
    if(errno == ENOENT){
      return 0;
      }
    }
  return 1;
}

