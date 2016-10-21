/* $Id: refilter.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"
#include "dbf.h"

using namespace charge;
using namespace conf;
using namespace mysqlpp;

int usage(char*);
time_t getrange(FILE*, unsigned long, time_t*);

extern bool netstore_debug;
bool updatecounters = true;
bool gettsfromfilter = false;
bool execbackground = false;
char* prg;

int main(int argc, char** argv)
{
  string host("localhost");
  string db("netstore");
  string user(getlogin());
  string password("");
  string datafile("none");
  int argval;
  time_t tfrom = 0;
  time_t tto = 0;
  unsigned long filter_id = 0;
  unsigned long client_id = 0;
  FILE* f;
  pid_t pid;
  ArchFlow archflow;
  Flow flow;
  Traffic traffic;
  Traffic_Cl traffic_cl;
  Traffic_Cl::iterator i_traffic_cl;
  Traffic_Ts traffic_ts;
  Traffic_Ts::iterator i_traffic_ts;

  prg = argv[0];
  // Parse arguments....
  // First checking if config file is given
  while(-1 != (argval = getopt(argc,argv,"h:d:u:p:?giaf:t:r:bce:j:"))){
    switch(argval){
      case 'j': { CONFIG config(optarg);
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
  while(-1 != (argval = getopt(argc,argv,"h:d:u:p:?giaf:t:r:bce:j:"))){
    switch(argval){
      case 'j': break;
      case 'h': host = optarg; break;
      case 'd': db = optarg; break;
      case 'u': user = optarg; break;
      case 'e': datafile = optarg; break;
      case 'f': tfrom = strtoul(optarg, NULL, 10); break;
      case 't': tto = strtoul(optarg, NULL, 10); break;
      case 'r': filter_id = strtoul(optarg, NULL, 10); break;
      case 'a': updatecounters = true; break;
      case 'b': gettsfromfilter = true; break;
      case 'c': execbackground = true; break;
      case 'p': password = optarg; break;
      case 'g': netstore_debug = true; break;
      case 'i': password = getpass("Password:"); break;
      case '?': usage(basename(prg)); 
        exit(-1);
        break;
      default : usage(basename(prg)); 
        exit(-1); 
      }
    }
  if(filter_id == 0){
    usage(basename(prg));
    exit(-1);
    }
  if(NULL == (f = fopen(datafile.c_str(), "r"))){
    vlog(LOG_ERR, "Can't open file %s\n", datafile.c_str());
    vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
    exit(-1);
    }
  if(execbackground == true){
    pid = fork();
    if(pid != 0){
      exit(0);
      }
    else{
      freopen("/dev/zero", "r", stdin);
      freopen("/dev/null", "w", stdout);
      freopen("/dev/null", "w", stderr);
      }
    }
  Connection con(true);
  Query query = con.query();
  Result res;
  Result::iterator i;
  Row row;
  // Connecting to mysql server
  try{
    time_t ts = 0;
    time_t i_time = 0;
    
    con.connect(db.c_str(), host.c_str(), user.c_str(), password.c_str());
    // Start transaction
    vlog(LOG_INFO, "Starting transaction\n");
    query << "BEGIN";
    query.execute();
    
    if(gettsfromfilter){
      query.reset();
      query << "SELECT client_id, UNIX_TIMESTAMP(starttimestamp) AS starttimestamp, UNIX_TIMESTAMP(stoptimestamp) stoptimestamp FROM filter WHERE id = '" << filter_id << "'";
      vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      res = query.store();
      i = res.begin();
      if(res.begin() != res.end()){
        row = *i;
        client_id = row["client_id"];
        }
      else{
        vlog(LOG_ERR, "Unable to find filter with identifier %lu\n", filter_id);
        exit(-1);
        }
      tfrom = (time_t)row["starttimestamp"];
      tto = (time_t)row["stoptimestamp"];
      }
    if(tfrom == 0 || tto == 0){
      time_t maxts = 0;
      time_t mints = 0;
      maxts = 0;
      mints = 0;
      maxts = getrange(f, client_id, &mints);
      if(tfrom == 0){
        tfrom = mints;
        }
      if(tto == 0){
        tto = maxts;
        }
      }
    // Prepare Traffic_Cl map
    traffic.incoming = 0;
    traffic.outcoming = 0;
    traffic_cl.insert(pair<unsigned long, Traffic>(filter_id, traffic));
    // Prepare Traffic_Ts map
    vlog(LOG_DEBUG, "Creating traffic_ts map\n");
    if(tto >= tfrom){
      ts = (getnextday(tfrom) - 1 > tto ) ? tto : getnextday(tfrom) - 1;
      traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
      i_time = getnextday(getnextday(tfrom));
      do{
        ts = (i_time > tto) ? tto: i_time - 1;
        if(ts <= tto){
          traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
          }
        i_time = getnextday(i_time);
        }while(i_time <= getnextday(tto));
      }
    vlog(LOG_DEBUG, "Calculating traffic\n");
    cl_seek(f, client_id);
    FILTER* filter = new FILTER(con, filter_id);
    while(!feof(f)){
      if(1 == fread(&archflow, sizeof(ArchFlow), 1, f)){
        if(archflow.timestamp < tfrom || archflow.timestamp > tto){
          continue;
          }
        if(archflow.client_id != client_id){
          break;
          }
        flow.sequence = 0;
        flow.router_id = 0;
        flow.timestamp = archflow.timestamp;
        flow.src_addr = archflow.src_addr;
        flow.dst_addr = archflow.dst_addr;
        flow.in_if_id = archflow.in_if_id;
        flow.out_if_id = archflow.out_if_id;
        flow.d_pkts = 0;
        flow.d_octets = 0;
        flow.protocol = archflow.protocol;
        flow.src_port = archflow.src_port;
        flow.dst_port = archflow.dst_port;
        flow.src_as = archflow.src_as;
        flow.dst_as = archflow.dst_as;

        if(filter->satisfy(flow)){
          if(traffic_ts.end() == (i_traffic_ts = traffic_ts.lower_bound(archflow.timestamp))){
            vlog(LOG_ERR, "Timestamp in the data is out of calculating range!\n");
            vlog(LOG_ERR, "archflow.timestamp = %lu\n", archflow.timestamp);
            exit(-1);
            }
          i_traffic_cl = i_traffic_ts->second.begin();
          if(archflow.dir == in){
            i_traffic_cl->second.incoming += archflow.d_octets;
            }
          else{
            i_traffic_cl->second.outcoming += archflow.d_octets;
            }
          }
        }
      }
    if(!traffic_ts.empty()){
      struct tm ttm;
      char c_ts[32];
      
      query.reset();
      query << "DELETE FROM filter_counter_snapshot"
              << " WHERE UNIX_TIMESTAMP(timestamp) >= " << tfrom
              << " AND UNIX_TIMESTAMP(timestamp) <= " << tto
              << " AND filter_id = " << filter_id;
      vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      query.execute();
      
      query = con.query();
      query << "INSERT INTO filter_counter_snapshot (filter_id, timestamp, incoming, outcoming)"
              << " VALUES ";
      i_traffic_ts = traffic_ts.begin();
      traffic_cl = i_traffic_ts->second;
      memcpy(&ttm, localtime(&i_traffic_ts->first), sizeof(struct tm));
      strftime(c_ts, sizeof(c_ts), "%Y-%m-%d %H:%M:%S", &ttm);
      i_traffic_cl = traffic_cl.begin();
      query << "(" << i_traffic_cl->first << ", "
              << "'" << c_ts << "', "
              << i_traffic_cl->second.incoming << ", "
              << i_traffic_cl->second.outcoming << ")";
      i_traffic_cl++;
      for(; i_traffic_cl != traffic_cl.end(); i_traffic_cl++){
        query << ", (" << i_traffic_cl->first << ", "
                << "'" << c_ts << "', "
                << i_traffic_cl->second.incoming << ", "
                << i_traffic_cl->second.outcoming << ")";
        }
      i_traffic_ts++;
      for(; i_traffic_ts != traffic_ts.end(); i_traffic_ts++){
        traffic_cl = i_traffic_ts->second;
        memcpy(&ttm, localtime(&i_traffic_ts->first), sizeof(struct tm));
        strftime(c_ts, sizeof(c_ts), "%Y-%m-%d %H:%M:%S", &ttm);
        i_traffic_cl = traffic_cl.begin();
        for(i_traffic_cl = traffic_cl.begin(); i_traffic_cl != traffic_cl.end(); i_traffic_cl++){
          query << ", (" << i_traffic_cl->first << ", "
                  << "'" << c_ts << "', "
                  << i_traffic_cl->second.incoming << ", "
                  << i_traffic_cl->second.outcoming << ")";
          }
        }
      query.execute();
      }
    vlog(LOG_INFO, "Commiting transaction\n");
    query.reset();
    query << "COMMIT";
    query.execute();
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
    if(con.connected()){
      query.reset();
      query << "ROLLBACK";
      query.execute();
      }
    exit(-1);
    }
  return 0;
}

int usage(char* prog)
{
  return vlog(LOG_ERR,
   "Usage: %s [-j config] [-h host] [-u user] [-p password] [-d database] [-igb] [-f starttime] [-t stoptime] [-a] -r filter_id -e datafile\n"
   "\t-j config\tRead options from `config' file\n"
   "\t-h host\t\tIP address or domain name of mysql server(Default: `localhost')\n"
   "\t-u user\t\tUsername for connection to mysql server(Default: `netstore')\n"
   "\t-p password\tPassword for connection to mysql server(Default: no password)\n"
   "\t-d database\tDatabase for connection to mysql server(Default: `netstore')\n"
   "\t-i\t\tAsk password interactively instead of specifying -p option\n"
   "\t-g\t\tTurn on debug output\n"
   "\t-f starttime\tTimestamp(unix time), when filter became valid\n"
   "\t-t stoptime\tTimestamp(unix time), when filter became invalid\n"
   "\t-r num\tFilter id\n"
   "\t-e datafile\tFile with data\n"
   "\t-b \t\tUse timestamps from table `filter'\n"
   "\t-c \t\tExecute program in background\n"
   "\t-a \t\tUpdate counters in database\n", prog);
}

time_t getrange(FILE* f, unsigned long client_id, time_t* mints)
{
  time_t maxts = 0;
  ArchFlow archbuf;
  if(0 == cl_seek(f, client_id)){
    vlog(LOG_ERR, "No data for client with id %lu\n", client_id);
    exit(-1);
    }
  if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
    vlog(LOG_ERR, "%s: fread(): %s\n", prg, strerror(errno));
    exit(-1);
    }
  maxts = archbuf.timestamp;
  *mints = archbuf.timestamp;
  while(archbuf.client_id == client_id){
    if(archbuf.timestamp > maxts){
      maxts = archbuf.timestamp;
      }
    if(archbuf.timestamp < *mints){
      *mints = archbuf.timestamp;
      }
    if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
      break;
      }
    }
  return maxts;
}
