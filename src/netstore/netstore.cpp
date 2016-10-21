/* $Id: netstore.cpp,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"

using namespace charge;
using namespace conf;
using namespace mysqlpp;


int usage(char*);

extern bool netstore_debug;
int semid;
char* prg;

int main(int argc, char** argv)
{
  string host("localhost");
  string db("netstore");
  string user(getlogin());
  string password("");

  string flowsfile("flows");
  string flows_filterfile("flows_filter");
  string configfile(DEFAULT_CONFIG_FILE);
  FILE *fflows;
  FILE *fflows_filter;
 
  int argval;
 
  key_t key;
  union semun semarg;
  struct semid_ds ds;
  bool removesemaphore = false;

  prg = argv[0];
  // Parse arguments....
  // First checking if config file is given
  while(-1 != (argval = getopt(argc,argv,"f:h:d:u:p:?sgia:b:"))){
    switch(argval){
      case 'f': {CONFIG config(optarg);
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
  while(-1 != (argval = getopt(argc,argv,"f:h:d:u:p:?sgia:b:"))){
    switch(argval){
      case 'f': break;
      case 'h': host = optarg; break;
      case 'd': db = optarg; break;
      case 'u': user = optarg; break;
      case 'p': password = optarg; break;
      case 'a': flowsfile = optarg; break;
      case 'b': flows_filterfile = optarg; break;
      case 'g': netstore_debug = true; break;
      case 'i': password = getpass("Password:"); break;
      case 's': removesemaphore = true; break;
      case '?': usage(argv[0]); 
        exit(-1);
        break;
      default : usage(argv[0]); 
        exit(-1); 
        break;
      }
    }
  // Try to create locking semaphore.
  // If it created allready, exit and show who has create one.
  key = ftok("/var/run/netstore.sem",1);
  if(key == -1){
    vlog(LOG_ERR, "/var/run/netstore.sem is not accessable\n");
    vlog(LOG_ERR, "%s\n", strerror(errno));
    exit(-1);
    }
  semid = semget(key, 1, SEM_R | SEM_A | IPC_CREAT | IPC_EXCL);
  if(semid == -1){
    vlog(LOG_ERR, "Couldn't create semaphore: %s\n", strerror(errno));
    semid = semget(key, 1, SEM_R | SEM_A);
    if(semid == -1){
      vlog(LOG_ERR, "Couldn't get the semaphore's identifier: %s\n", strerror(errno));
      exit(-1);
      }
    if(removesemaphore){
      if(-1 == semctl(semid, 1, IPC_RMID)){
        vlog(LOG_ERR, "Couldn't remove the semaphore: %s\n", strerror(errno));
        exit(-1);
        }
      vlog(LOG_ERR, "Semaphore is removed\n");
      exit(0);
      }
    semarg.buf = &ds;
    if(-1 == semctl(semid, 1, IPC_STAT, semarg)){
      vlog(LOG_ERR, "Couldn't fetch the semaphore set's struct semid_ds: %s\n", strerror(errno));
      exit(-1);
      }
    vlog(LOG_ERR, "Semaphore exists already and has been created by uid %d\n", ds.sem_perm.cuid);
    vlog(LOG_ERR, "Remove it running %s -s\n", prg);
    exit(-1);
    }
  
  Connection con(true);
  Query query = con.query();
  Result res;
  Result::iterator i;
  Row row;
  AccFlow accflow;
  Traffic traffic;
  Traffic_Cl traffic_cl;
  Traffic_Cl::iterator i_traffic_cl;
  Traffic_Ts traffic_ts;
  Traffic_Ts::iterator i_traffic_ts;
  
  
  //time_t lastload = 0;
  time_t starttimestamp = 0;
  time_t stoptimestamp = 0;
  char tbuf[32];
  char c_ts[32];
  struct tm ttm;
  // Connecting to mysql server
  try{
    // Open files
    if(NULL == (fflows = fopen(flowsfile.c_str(), "r"))){
      vlog(LOG_ERR, "Can't open file '%s'\n", flowsfile.c_str());
      vlog(LOG_ERR, "%s: fopen(): %s\n", basename(prg), strerror(errno));
      exit(-1);
      }
    if(NULL == (fflows_filter = fopen(flows_filterfile.c_str(), "r"))){
      vlog(LOG_ERR, "Can't open file '%s'\n", flows_filterfile.c_str());
      vlog(LOG_ERR, "%s: fopen(): %s\n", basename(prg), strerror(errno));
      exit(-1);
      }
    con.connect(db.c_str(), host.c_str(), user.c_str(), password.c_str());
    // Start transaction
    vlog(LOG_INFO, "Starting transaction\n");
    query.reset();
    query << "BEGIN";
    query.execute();
    
    // Determine min and max timestamps
    // Determine time ranges of flows data.
    if(1 == fread(&accflow, sizeof(accflow), 1, fflows)){
      starttimestamp = accflow.timestamp;
      stoptimestamp = accflow.timestamp;
      }
    else{
      vlog(LOG_ERR, "Can't read from file %s\n", flowsfile.c_str());
      vlog(LOG_ERR, "%s: fread(): %s\n", basename(prg), strerror(errno));
      if(-1 == semctl(semid, 1, IPC_RMID)){
        vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
        }
      if(con.connected()){
        vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
        query.reset();
	query << "ROLLBACK";
	query.execute();
        }
      exit(-1);
      }
    rewind(fflows);
    while(!feof(fflows)){
      if(1 == fread(&accflow, sizeof(accflow), 1, fflows)){
        if(accflow.timestamp > stoptimestamp){
          stoptimestamp = accflow.timestamp;
          }
        if(accflow.timestamp < starttimestamp){
          starttimestamp = accflow.timestamp;
          }
        }
      }
    ctime_r(&starttimestamp, tbuf);  
    vlog(LOG_DEBUG, "Min timestamp in flows %s", tbuf);
    ctime_r(&stoptimestamp, tbuf);
    vlog(LOG_DEBUG, "Max timestamp in flows %s", tbuf);
    
    ctime_r(&starttimestamp, tbuf);
    vlog(LOG_INFO, "Period for calculation: from %s", tbuf);
    ctime_r(&stoptimestamp, tbuf);
    vlog(LOG_INFO, "Period for calculation: to %s", tbuf);
    

    // Get client identifiers from DB and create traffic_cl map
    query.reset();
    query << "SELECT id FROM client";
    vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
    res = query.store();
    vlog(LOG_DEBUG, "Creating traffic_cl map\n");
    traffic.incoming = 0;
    traffic.outcoming = 0;
    for(i = res.begin(); i != res.end(); i++){
      row = *i;
      traffic_cl.insert(pair<unsigned long, Traffic>(row["id"], traffic));
      vlog(LOG_DEBUG, "Client id: %lu\n", (unsigned long)row["id"]);
      }
    // Check if data is valid
    // and client_id in data file exists in traffic_cl
    rewind(fflows);
    vlog(LOG_DEBUG, "Checking `%s' integrity\n", flowsfile.c_str());
    while(!feof(fflows)){
      if(1 == fread(&accflow, sizeof(AccFlow), 1, fflows)){
        if(traffic_cl.find(accflow.id) == traffic_cl.end() && accflow.id != 0){
          vlog(LOG_ERR, "Data file `%s' has client identifier %lu which doesn't exist in the database `%s'\n", flowsfile.c_str(), accflow.id, db.c_str());
          vlog(LOG_ERR, "May be data file `%s' is not valid\n", flowsfile.c_str());
          if(-1 == semctl(semid, 1, IPC_RMID)){
            vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
            }
          if(con.connected()){
            vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
            query.reset();
            query << "ROLLBACK";
            query.execute();
            }
          exit(-1);
          }
        }
      }  
    // Create traffic_ts map
    time_t i_time = starttimestamp;
    time_t ts = 0;
    vlog(LOG_DEBUG, "Creating traffic_ts map\n");
    if(stoptimestamp >= starttimestamp){
      ts = (getnextday(starttimestamp) - 1 > stoptimestamp ) ? stoptimestamp : getnextday(starttimestamp) - 1;
      traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
      i_time = getnextday(getnextday(starttimestamp));
      do{
        ts = (i_time > stoptimestamp) ? stoptimestamp: i_time - 1;
        if(ts <= stoptimestamp){
          traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
          }
        i_time = getnextday(i_time);
        }while(i_time <= getnextday(stoptimestamp));
      }
    for(i_traffic_ts = traffic_ts.begin(); i_traffic_ts != traffic_ts.end(); i_traffic_ts++){
      vlog(LOG_DEBUG, "Copy with %lu timestamp\n", i_traffic_ts->first);
      }
    rewind(fflows);
    vlog(LOG_DEBUG, "Calculating traffic\n");
    while(!feof(fflows)){
      if(1 == fread(&accflow, sizeof(AccFlow), 1, fflows)){
        if(accflow.timestamp >= starttimestamp){
          if(traffic_ts.end() == (i_traffic_ts = traffic_ts.lower_bound(accflow.timestamp))){
            vlog(LOG_ERR, "Timestamp in the data is out of calculating range!\n");
            vlog(LOG_ERR, "accflow.timestamp = %lu\n", accflow.timestamp);
            if(-1 == semctl(semid, 1, IPC_RMID)){
              vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
              }
            if(con.connected()){
              vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
              query.reset();
              query << "ROLLBACK";
              query.execute();
              }
            exit(-1);
            }
          if(accflow.id == 0){
            continue;  
            }
          if((i_traffic_cl = i_traffic_ts->second.find(accflow.id)) == i_traffic_ts->second.end()){
            vlog(LOG_ERR, "Unknown client with id = %lu\n", accflow.id);
            if(-1 == semctl(semid, 1, IPC_RMID)){
              vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
              }
            if(con.connected()){
              vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
              query.reset();
              query << "ROLLBACK";
              query.execute();
              }
            exit(-1);
            }
          if(accflow.dir == in){
            i_traffic_cl->second.incoming += accflow.d_octets;
            }
          if(accflow.dir == out){
            i_traffic_cl->second.outcoming += accflow.d_octets;
            }
          }
        }  
      }
    // Storing result in DB
    if(!traffic_ts.empty()){
      query.reset();
      query << "INSERT INTO traffic_snapshot(client_id, timestamp, incoming, outcoming)"
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
      for(i_traffic_cl++; i_traffic_cl != traffic_cl.end(); i_traffic_cl++){
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
        for(i_traffic_cl = traffic_cl.begin(); i_traffic_cl != traffic_cl.end(); i_traffic_cl++){
          query << ", (" << i_traffic_cl->first << ", " 
                  << "'" << c_ts << "', "
                  << i_traffic_cl->second.incoming << ", "
                  << i_traffic_cl->second.outcoming << ")";
          }
        }
      //vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      query.execute();
      }
    // Calculate traffic by filters
    traffic_cl.clear();
    traffic_ts.clear();

    // Get filter identifiers from DB and create traffic_cl map
    query.reset();
    query << "SELECT id FROM filter";
    vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
    res = query.store();
    vlog(LOG_DEBUG, "Creating traffic_cl map for filters\n");
    traffic.incoming = 0;
    traffic.outcoming = 0;
    for(i = res.begin(); i != res.end(); i++){
      row = *i;
      traffic_cl.insert(pair<unsigned long, Traffic>(row["id"], traffic));
      }

    // Create traffic_ts map for filters
    i_time = starttimestamp;
    ts = 0;
    vlog(LOG_DEBUG, "Creating traffic_ts map for filters\n");
    if(stoptimestamp >= starttimestamp){
      ts = (getnextday(starttimestamp) - 1 > stoptimestamp ) ? stoptimestamp : getnextday(starttimestamp) - 1;
      traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
      i_time = getnextday(getnextday(starttimestamp));
      do{
        ts = (i_time > stoptimestamp) ? stoptimestamp: i_time - 1;
        if(ts <= stoptimestamp){
          traffic_ts.insert(pair<time_t, Traffic_Cl>(ts, traffic_cl));
          }
        i_time = getnextday(i_time);
        }while(i_time <= getnextday(stoptimestamp));
      }
    for(i_traffic_ts = traffic_ts.begin(); i_traffic_ts != traffic_ts.end(); i_traffic_ts++){
      vlog(LOG_DEBUG, "Copy with %lu timestamp\n", i_traffic_ts->first);
      }
    
    rewind(fflows_filter);
    vlog(LOG_DEBUG, "Calculating traffic for filters\n");
    while(!feof(fflows_filter)){
      if(1 == fread(&accflow, sizeof(AccFlow), 1, fflows_filter)){
        if(accflow.timestamp >= starttimestamp){
          if(traffic_ts.end() == (i_traffic_ts = traffic_ts.lower_bound(accflow.timestamp))){
            vlog(LOG_ERR, "Timestamp in the data with filter's traffic is out of calculating range!\n");
            vlog(LOG_ERR, "accflow.timestamp = %lu\n", accflow.timestamp);
            if(-1 == semctl(semid, 1, IPC_RMID)){
              vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
              }
            if(con.connected()){
              vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
              query.reset();
              query << "ROLLBACK";
              query.execute();
              }
            exit(-1);
            }
          if((i_traffic_cl = i_traffic_ts->second.find(accflow.id)) == i_traffic_ts->second.end()){
            vlog(LOG_ERR, "Unknown filter with id = %lu\n", accflow.id);
            if(-1 == semctl(semid, 1, IPC_RMID)){
              vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
              }
            if(con.connected()){
              vlog(LOG_ERR, "Rolling back transaction. Check errors and start again\n");
              query.reset();
              query << "ROLLBACK";
              query.execute();
              }
            exit(-1);
            }
          if(accflow.dir == in){
            i_traffic_cl->second.incoming += accflow.d_octets;
            }
          if(accflow.dir == out){
            i_traffic_cl->second.outcoming += accflow.d_octets;
            }
          }
        }  
      }

    // Storing result in DB
    if(!traffic_ts.empty()){
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
      //vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      }
    query.reset();
    query << "INSERT IGNORE INTO feeding(timestamp) VALUES(FROM_UNIXTIME(" << stoptimestamp << "))" ;
    vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
    query.execute();
              
    // Commit changes
    vlog(LOG_INFO, "Commiting transaction\n");
    query.reset();
    query << "COMMIT";
    query.execute();
    if(-1 == semctl(semid, 1, IPC_RMID)){
      vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
      exit(-1);
      }
     return 0;
    }
  catch (mysqlpp::Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    if(-1 == semctl(semid, 1, IPC_RMID)){
      vlog(LOG_ERR, "Can't remove semaphore: %s", strerror(errno));
      }
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
 return vlog(LOG_ERR, "Usage: %s -f config -h host -u user -p password -d database -i \
  -f config     Config file\n\
  -h host       Host of mysql server\n\
  -u user       User name to login to mysql\n\
  -p password   Hmm... password\n\
  -d database   Database name\n\
  -i            Ask password in command line rather using `-p' key\n\
  -a file       File with flows\n\
  -b file       File with flows corresponding to filters\n\n\
  Additional option -s may be specified. It is used to remove locking semaphore.\n\
  Use it if you absolutely sure what you do.\n\
  It is worth to use -s when previous you want remove existing semaphore\n\
  which wasn't removed due abnormal termination of '%s'.\n", s, s);
}

