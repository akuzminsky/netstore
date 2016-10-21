/* $Id: make_dbf.cpp,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"
#include "dbf.h"

using namespace charge;
using namespace conf;
using namespace mysqlpp;

int usage(char*);

extern bool netstore_debug;
const unsigned int f_size = 200;
char* prg;

int main(int argc, char** argv)
{
  string host("localhost");
  string db("netstore");
  string user(getlogin());
  string password("");
  int argval;
  struct DB_HEADER db_header;
  struct DB_FIELD db_field;
  prg = argv[0];
  // Parse arguments....
  // First checking if config file is given
  while(-1 != (argval = getopt(argc,argv,"f:h:d:u:p:gi?"))){
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
  while(-1 != (argval = getopt(argc,argv,"f:h:d:u:p:gi?"))){
    switch(argval){
      case 'f': break;
      case 'h': host = optarg; break;
      case 'd': db = optarg; break;
      case 'u': user = optarg; break;
      case 'p': password = optarg; break;
      case 'g': netstore_debug = true; break;
      case 'i': password = getpass("Password:"); break;
      case '?': usage(basename(prg)); exit(-1); break;
      default : usage(basename(prg)); exit(-1); 
      }
    }
  mysqlpp::Connection con(true);
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i;
  mysqlpp::Row row;
  // Connecting to mysql server
  try{
    struct tm ttm;
    time_t now;
    con.connect(db.c_str(), host.c_str(), user.c_str(), password.c_str());
    vlog(LOG_INFO, "Starting transaction\n");
    query << "BEGIN";
    query.execute();
    query.reset();
    query << "SELECT * FROM register";
    res = query.store();
    i = res.begin();
    row = *i;
    
    now = time(&now);
    memcpy(&ttm, localtime(&now), sizeof(ttm));
    memset(&db_header, 0, sizeof(db_header));
    db_header.version = 0x03;
    db_header.last_update[0] = ttm.tm_year;
    db_header.last_update[1] = ttm.tm_mon + 1;
    db_header.last_update[2] = ttm.tm_mday;
    db_header.records = res.num_rows();
    db_header.header_length = sizeof(struct DB_HEADER) + row.size() * sizeof(struct DB_FIELD) + 1;
    db_header.record_length = f_size * row.size() + 1;
 
    // output header, part I;
    fwrite(&db_header, sizeof(struct DB_HEADER), 1, stdout);
    // output array of fields;
    for(unsigned int k = 0; k < row.size(); k++){
      memset(&db_field, 0, sizeof(struct DB_FIELD));
      memcpy(db_field.field_name, res.names(k).c_str(), sizeof(db_field.field_name));
      for(unsigned j = 0; j < sizeof(db_field.field_name); j++){
        if(isalpha(db_field.field_name[j])){
          db_field.field_name[j] = toupper(db_field.field_name[j]);
	  }
        }
      db_field.field_type = 'C';
      db_field.field_adress = 0;
      db_field.field_length = f_size;
      db_field.field_decimals = 0;
      fwrite(&db_field, sizeof(struct DB_FIELD), 1, stdout);
      }
    // out put header tail;
    unsigned char tail = 0x0D;
    fwrite(&tail, sizeof(unsigned char), 1, stdout);
    // output rows;
    unsigned char buffer[f_size];
    unsigned min_size = 0;
    for(i = res.begin(); i != res.end(); i++){
      row = *i;
      tail = 0x20;
      fwrite(&tail, sizeof(unsigned char), 1, stdout);
      for(unsigned int k = 0; k < row.size(); k++){
        memset(&buffer, 0x20, sizeof(buffer));
        min_size = (sizeof(buffer) > strlen(row[k].c_str())) ? strlen(row[k].c_str()) : sizeof(buffer);
        memcpy(buffer, row[k].c_str(), min_size);
        fwrite(&buffer, sizeof(buffer), 1, stdout);
        }
      }
    tail = 0x1A;
    fwrite(&tail, sizeof(unsigned char), 1, stdout);
    vlog(LOG_INFO, "Commiting transaction\n");
    query.reset();
    query << "COMMIT";
    query.execute();
    return 0;
    }
  catch (mysqlpp::Exception er){
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
  "Usage: %s [-f config] [-h host] [-u user] [-p password] [-d database] [-ig]\n"
  "\t-f config\tRead options from `config' file\n"
  "\t-h host\t\tIP address or domain name of mysql server(Default: `localhost')\n"
  "\t-u user\t\tUsername for connection to mysql server(Default: `netstore')\n"
  "\t-p password\tPassword for connection to mysql server(Default: no password)\n"
  "\t-d database\tDatabase for connection to mysql server(Default: `netstore')\n"
  "\t-i\t\tAsk password interactively instead of specifying -p option\n"
  "\t-g\t\tTurn on debug output\n", prog);
}
