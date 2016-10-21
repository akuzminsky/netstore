/* $Id: charge.cpp,v 1.1.1.1 2007/11/29 15:29:45 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"

using namespace charge;
using namespace conf;
using namespace mysqlpp;

void usage(char*);
extern bool netstore_debug;

int main(int argc, char** argv)
{
  Connection con(true);
  try{
    int argval;
    string host = "localhost";
    string login = getlogin();
    string passwd = "";
    string db = "netstore";
    int service_type_id = 1;
    unsigned long service_id = 0;
    
    Result res_services, res;
    Result::iterator i_services;
    Row row_services, row;
    time_t actual_time = 0;
    bool fullupdate = false;
    
    // First checking if config file is given
    while(-1 != (argval = getopt(argc,argv,"h:d:u:p:it:?s:ga:fc:"))){
      switch(argval){
        case 'c': {CONFIG config(optarg);
		host = config.get_value("mysql_host");
		db = config.get_value("mysql_database");
		login = config.get_value("mysql_user");
		passwd = config.get_value("mysql_password");
		break;
		}
        default : break;
        }
      }
    // reset getopt()
    optind = 1; optreset = 1;
    // Parsing the rest options
    while(-1 != (argval = getopt(argc,argv,"h:d:u:p:it:?s:ga:fc:"))){
      switch(argval){
        case 'c': break;
        case 'h': host = optarg; break;
        case 'u': login = optarg; break;
        case 'p': passwd = optarg; break;
        case 'd': db = optarg; break;
        case 's': service_id = strtoul(optarg, NULL, 10); break;
        case 'a': actual_time = strtoul(optarg, NULL, 10); break;
        case 'i': passwd = getpass("Password:"); break;
        case 'g': netstore_debug = true; break;
        case 'f': fullupdate = true; break;
        case 't': service_type_id = atoi(optarg); break;
        case '?': usage(basename(argv[0])); exit(-1);break;
        default : usage(basename(argv[0])); exit(-1);
        }
      }
    vlog(LOG_DEBUG, "Trying to connect to %s as %s with password %s. Database %s\n", 
          host.c_str(), login.c_str(), "****", db.c_str());
    con.connect(db.c_str(), host.c_str(), login.c_str(), passwd.c_str());
    Query query = con.query();
    query << "SET AUTOCOMMIT = 0";
    query.execute();
    query.reset();
    query << "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED";
    query.execute();
    query.reset();
    query << "START TRANSACTION";
    query.execute();
    
    query.reset();
    query << "SELECT service.id AS id"
      << " FROM service"
      << " LEFT JOIN service_type ON service_type.id = service.service_type_id"
      << " WHERE 1 AND service_type.id = " << service_type_id;
    if(service_id != 0){
      query << " AND service.id = " << service_id;
      }
    query << " LOCK IN SHARE MODE";
    vlog(LOG_DEBUG, "Executing query: %s\n", query.preview().c_str());
    res_services = query.store();
    charge::SERVICE* service;
    for(i_services = res_services.begin(); i_services != res_services.end(); i_services++){
      row_services = *i_services;
      service_id = row_services["id"];
      service = new charge::SERVICE(con, service_id);
      vlog(LOG_DEBUG, "Service(%lu) starts on %lu-th day, expires on %lu-th day\n", service_id, service->get_start_time(), service->get_expire_time());
      if(fullupdate){
        service->purge_charges();
        }
      service->update_charges();
      }
    if(con.connected()){
      vlog(LOG_DEBUG, "Commiting changes\n");
      query.reset();
      query << "COMMIT";
      query.execute();
      }
    }
  catch (mysqlpp::Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    return -1;
    }
  exit(0);
}

void usage(char* prog)
{
 fprintf(stderr, "Usage: %s [-c config] [-h host] [-u user] [-p password] [-d database] [-i] [-f] [-g] [-t type] [-s service]\n", prog);
 fprintf(stderr, "\t-c config\t\tRead config from `config'\n");
 fprintf(stderr, "\t-h host\t\tIP address or domain name of mysql server(Default: `localhost')\n");
 fprintf(stderr, "\t-u user\t\tUsername for connection to mysql server(Default: `netstore')\n");
 fprintf(stderr, "\t-p password\tPassword for connection to mysql server(Default: no password)\n");
 fprintf(stderr, "\t-d database\tDatabase for connection to mysql server(Default: `netstore')\n");
 fprintf(stderr, "\t-i\t\tAsk password interactively instead of specifying -p option\n");
 fprintf(stderr, "\t-g\t\tTurn on debug output\n");
 fprintf(stderr, "\t-f\t\tForce to recalculate whole period\n");
 fprintf(stderr, "\t-t service_type\tType of service. 1 for Leased lines(Default: 1)\n");
 fprintf(stderr, "\t-s service_id\tCalculate only service with id `service_id'\n");
 fprintf(stderr, "\t-a actual_time\tCalculate until `actual_time`\n\n");
}

