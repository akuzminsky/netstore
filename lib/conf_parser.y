/* $Id: conf_parser.y,v 1.2 2009/05/03 08:37:01 ingoth Exp $ */
%skeleton "lalr1.cc"
%defines
%output="conf_parser.cpp"
%parse-param {conf::Lexer& yylex}
%parse-param {conf::CONFIG& config}
%define "parser_class_name" "Parser"
%name-prefix="conf"

%{
#include "common.h"
#include "config.h"

namespace conf {
class Lexer;
}
%}

%union {
  const char* sval;
}

%token l_pid_file 
%token l_listen
%token l_port
%token l_mysql_host 
%token l_mysql_user 
%token l_mysql_password
%token l_mysql_database 
%token l_cache_size
%token l_router 
%token l_data
%token l_address
%token l_community
%token l_account_interface
%token <sval> l_ip_address
%token <sval> l_dns_name 
%token <sval> l_strvalue 

%start par_list

%type <sval> value
%type <sval> host_name
%{
string buf_routername;
RouterConfig buf_routerconfig;
#include "conf_lexer.h"
%}
%%

par_list  : parameter
          | par_list parameter
          ;

parameter  : options
          | router_config
          ;

options  : l_pid_file value
          {
          config.set_value("pid_file", $2);
          }
        | l_listen value
          {
          config.set_value("listen", $2);
          }
        | l_port value
          {
          config.set_value("port", $2);
          }
        | l_mysql_host value
          {
          config.set_value("mysql_host", $2);
          }
        | l_mysql_user value
          {
          config.set_value("mysql_user", $2);
          }
        | l_mysql_password value
          {
          config.set_value("mysql_password", $2);
          }
        | l_mysql_database value
          {
          config.set_value("mysql_database", $2);
          }
        | l_cache_size value
          {
          unsigned long c_size = 0;
          char suffix[8];
          char buffer[32];
          sscanf($2, "%lu%s", &c_size, suffix);
          if(c_size == 0){
            syslog(LOG_ERR, "Illegal cache_size %s", $2);
            exit(1);
            }
          if(0 == strncmp(suffix, "GB", sizeof(suffix))){
            c_size *= 1073741824;
            }
          else{
            if(0 == strncmp(suffix, "MB", sizeof(suffix))){
              c_size *= 1048576;
              }
            else{
              if(0 == strncmp(suffix, "kB", sizeof(suffix))){
                c_size *= 1024;
                }
              else{
                vlog(LOG_ERR, "Unrecognised suffix '%s'. Acceptable are GB, MB, kB(case sensitive)", suffix);
                vlog(LOG_ERR, "Set cache size to 1 MB");
                c_size = 1048576;
                }
              }
            }
          sprintf(buffer, "%lu", c_size);
          config.set_value("cache_size", buffer);
          }
        | l_data value
          {
          config.set_value("data", $2);
          }
        ;
value  :  host_name
      | l_strvalue
      ;

router_config  : l_router value { buf_routerconfig.router_name = $2; } '{' 
                router_directive_list '}'
                {
                config.add_router(buf_routerconfig);
                }
                 ;

host_name  :  l_ip_address
          | l_dns_name
          ;
router_directive_list  :  router_directive
                      | router_directive_list router_directive
                      ;

router_directive  : l_address value 
                    {
                      struct in_addr ires;
                      if(-1 == inet_pton(AF_INET, $2, &ires)){
                        // Assume address is DNS name
                        // try to resolve it
                        syslog(LOG_WARNING, "assume %s is domain name", $2);
                        struct hostent* hp;
                        hp = gethostbyname($2);
                        if(hp != NULL){
                          memcpy(&(buf_routerconfig.address), hp->h_addr, 
                              sizeof(buf_routerconfig.address));
                          }
                        else{
                          vlog(LOG_ERR, "assuming '%s' is domain name, can't resolve it", $2);
                          exit(1);
                          }
                        }
                      else{
                        buf_routerconfig.address = ires.s_addr;
                        }
                    }
                  | l_community value
                     {
                      buf_routerconfig.community = $2;
                     }
                  ;

%%

namespace conf {

void Parser::error(const location_type& loc, const std::string& msg){
  throw std::runtime_error(msg);
  };


CONFIG::CONFIG(){
  try {
    confname = DEFAULT_CONFIG_FILE;
    confstream = new ifstream(confname.c_str());
    values.clear();
    values["pid_file"] = "";
    values["listen"] = "0.0.0.0";
    values["port"] = "";
    values["mysql_host"] = "localhost";
    values["mysql_user"] = "netstore";
    values["mysql_password"] = "";
    values["mysql_database"] = "netstore";
    values["cache_size"] = "16777216";
    conf::Lexer lexer(confstream);
    conf::Parser parser(lexer, *this);
    parser.parse();
    }
  catch(exception& e){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: %s\n", e.what());
    exit(-1);
    }
  catch(...){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: unknown\n");
    exit(-1);
    }
}

CONFIG::CONFIG(const char* conf){
  try{
    confname = conf;
    confstream = new ifstream(confname.c_str());
    values.clear();
    values["pid_file"] = "";
    values["listen"] = "0.0.0.0";
    values["port"] = "";
    values["mysql_host"] = "localhost";
    values["mysql_user"] = "netstore";
    values["mysql_password"] = "";
    values["mysql_database"] = "netstore";
    values["cache_size"] = "16777216";
    conf::Lexer lexer(confstream);
    conf::Parser parser(lexer, *this);
    parser.parse();
    // Get router_id from MySQL
    try{
      mysqlpp::Connection con(values["mysql_database"].c_str(), values["mysql_host"].c_str(), values["mysql_user"].c_str(), values["mysql_password"].c_str());
      mysqlpp::Query query = con.query();
      for(list<RouterConfig>::iterator ir = routers.begin(); ir != routers.end(); ir++){
        query.reset();
        query << "SELECT router_id FROM routers WHERE hostname = '" << ir->router_name << "'";
        mysqlpp::Result res = query.store();
        if(res.size() == 0){
          vlog(LOG_WARNING, "Router %s doesn't have the respective record in mysql database\n", ir->router_name.c_str());
          ir->router_id = 0;
          }
        else{
          mysqlpp::Result::iterator i;
          i = res.begin();
          mysqlpp::Row row = *i;
          ir->router_id = (unsigned int)row["router_id"];
          }
        }
      con.close();
      }
    catch(mysqlpp::Exception er){
      vlog(LOG_ERR, "Error: %s", er.what());
      exit(-1);
      }
    }
  catch(exception& e){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: %s\n", e.what());
    exit(-1);
    }
  catch(...){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: unknown\n");
    exit(-1);
    }
}

CONFIG::CONFIG(const string& conf){
  try{
    confname = conf;
    vlog(LOG_DEBUG, "config file: '%s'\n", conf.c_str());
    confstream = new ifstream(confname.c_str());
    values.clear();
    values["pid_file"] = "";
    values["listen"] = "0.0.0.0";
    values["port"] = "";
    values["mysql_host"] = "localhost";
    values["mysql_user"] = "netstore";
    values["mysql_password"] = "";
    values["mysql_database"] = "netstore";
    values["cache_size"] = "16777216";
    conf::Lexer lexer(confstream);
    conf::Parser parser(lexer, *this);
    parser.parse();
    // Get router_id from MySQL
    try{
      mysqlpp::Connection con(values["mysql_database"].c_str(), values["mysql_host"].c_str(), values["mysql_user"].c_str(), values["mysql_password"].c_str());
      mysqlpp::Query query = con.query();
      for(list<RouterConfig>::iterator ir = routers.begin(); ir != routers.end(); ir++){
        query.reset();
        query << "SELECT router_id FROM routers WHERE hostname = '" << ir->router_name << "'";
        mysqlpp::Result res = query.store();
        if(res.size() == 0){
          vlog(LOG_WARNING, "Router %s doesn't have the respective record in mysql database\n", ir->router_name.c_str());
          ir->router_id = 0;
          }
        else{
          mysqlpp::Result::iterator i;
          i = res.begin();
          mysqlpp::Row row = *i;
          ir->router_id = (unsigned int)row["router_id"];
          }
        }
      con.close();
      }
    catch(mysqlpp::Exception er){
      vlog(LOG_ERR, "Error: %s", er.what());
      exit(-1);
      }
    }
  catch(exception& e){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: %s\n", e.what());
    exit(-1);
    }
  catch(...){
    vlog(LOG_ERR, "Exception is thrown in CONFIG constuctor. Reason: unknown\n");
    exit(-1);
    }
}

const char* CONFIG::get_value(const string& attr){
  return values[attr.c_str()].c_str();
}

const char* CONFIG::get_value(const char* attr){
  return values[attr].c_str();
}

const string CONFIG::set_value(const string& attr, const string& value){
  return values[attr] = value;
}

void CONFIG::add_router(const RouterConfig& rc){
  return routers.push_back(rc);
}
int CONFIG::show(){
  cerr << "Conf name: " << confname << endl;
  cerr << "Values:" << endl;
  for(map<string,string>::iterator i_values = values.begin();
      i_values != values.end();
      i_values++){
    cerr << i_values->first << " = " << i_values->second << endl;
    }
  cerr << "Routers:" << endl;
  for(list<RouterConfig>::iterator i_routers = routers.begin();
      i_routers != routers.end();
      i_routers++){
    cerr << "router_id: " << i_routers->router_id << endl;
    cerr << "router_name: " << i_routers->router_name << endl;
    cerr << "community: " << i_routers->community << endl;
    cerr << "address: " << i_routers->address << endl;
    cerr << "flow_sequence: " << i_routers->flow_sequence << endl;
    cerr << "sys_uptime: " << i_routers->sys_uptime << endl << endl;
    }
  return 0;
}
}
