/* $Id: report.cpp,v 1.3 2010/07/20 08:31:40 ingoth Exp $ */
#include "common.h"
#include "classes.h"
#include "config.h"
#include "report.h"

using namespace charge;
using namespace conf;
using namespace mysqlpp;

typedef struct traffic{
  unsigned long long incoming;
  unsigned long long outcoming;
  } TRAFFIC;
typedef map<time_t, TRAFFIC> TABLE;

bool currrep = false;
FILE *f;
char* prg;

extern bool netstore_debug;

int main(int argc, char** argv)
{
    int argval;
    string host = "localhost";
    string login = getlogin();
    string passwd = "";
    string db = "netstore";
    string datafile = "";
    int report_id = 0;
    int c_id = 0;
    prg = argv[0];
    while(-1 != (argval = getopt(argc,argv,"h:d:u:p:r:gic:f:j:"))){
      switch(argval){
        case 'j': { CONFIG config(optarg);
                    host = config.get_value("mysql_host");
                    db = config.get_value("mysql_database");
                    login = config.get_value("mysql_user");
                    passwd = config.get_value("mysql_password");
                    break;
		  }
        default : break;
        }
      }
    optind = 1; optreset = 1;
    while(-1 != (argval = getopt(argc,argv,"h:d:u:p:r:gic:f:j:"))){
      switch(argval){
        case 'j': break;
        case 'h': host = optarg; break;
        case 'u': login = optarg; break;
        case 'p': passwd = optarg; break;
        case 'd': db = optarg; break;
        case 'g': netstore_debug = true; break;
        case 'f': datafile = optarg; break;
        case 'c': currrep = true; c_id = atoi(optarg); break;
        case 'r': report_id = atoi(optarg); break;
        case 'i': passwd = getpass("Password:"); break;
        }
      }
    
    if(datafile == ""){
      vlog(LOG_ERR, "Datafile not specified! Use -f to do that\n");
      exit(-1);
      }
    if(NULL == (f = fopen(datafile.c_str(), "r"))){
      vlog(LOG_ERR, "Can't open file %s\n", datafile.c_str());
      vlog(LOG_ERR, "%s: fopen(): %s\n", basename(argv[0]), strerror(errno));
      exit(-1);
      }
    Connection con(true);
  try{
    con.set_option(con.opt_set_charset_name, "koi8u");
    con.connect(db.c_str(), host.c_str(), login.c_str(), passwd.c_str());

    report* rep = new report(con, report_id);
    if(currrep){
      rep->set_client_id(c_id);
      }
    report_type type = rep->get_type();
    cout << "Название клиента: " << rep->client_description << endl << endl;
    if(type == current){
      vlog(LOG_DEBUG, "Report type - current\n");
      vlog(LOG_DEBUG, "Client id %d(%d)\n", c_id, rep->get_client_id());
      current_report* crpt = new current_report(con, 0, rep->get_client_id());
      crpt->set_client_id(c_id);
      crpt->make_full_report();
      crpt->make_filter_report();
      }
    vlog(LOG_DEBUG, "Client id %d\n", rep->get_client_id());
    if(type == daily){
      vlog(LOG_DEBUG, "Report type - daily\n");
      daily_report* drpt = new daily_report(con, report_id, rep->get_client_id());
      drpt->make_full_report();
      drpt->make_filter_report();
      }
    if(type == hourly){
      vlog(LOG_DEBUG, "Report type - hourly\n");
      vlog(LOG_DEBUG, "create object hourly_report...");
      hourly_report* hrpt = new hourly_report(con, report_id, rep->get_client_id());
      vlog(LOG_DEBUG, "done.\n");
      vlog(LOG_DEBUG, "generate report...");
      hrpt->make_full_report();
      vlog(LOG_DEBUG, "done.\n");
      }
    if(type == flows){
      vlog(LOG_DEBUG, "Report type - flows\n");
      flows_report* frpt = new flows_report(con, report_id, rep->get_client_id());
      frpt->make_full_report();
      frpt->make_filter_report();
      }
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}


// Implementations 

client::client(Connection& cn, int cl_id) : con(cn), id(cl_id){}



report::report(Connection& cn, int report_id = 0) : con(cn), id(report_id)
{
  try{
    vlog(LOG_DEBUG, "report object has been created\n");
    if(report_id != 0){
      Query query = con.query();
      query << "SELECT order_report.report_type as report_type , "
              << " order_report.client_id,"
              << " UNIX_TIMESTAMP(order_report.starttimestamp) as starttimestamp,"
              << " UNIX_TIMESTAMP(order_report.stoptimestamp) as stoptimestamp,"
              << " order_report.resolve_ip,"
              << " client.description as description"
              << " FROM order_report"
              << " left join client on order_report.client_id = client.id"
              << " WHERE order_report.id = " << report_id;
      vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
      Result res = query.store();
      Row row;
      Result::iterator i;
      i = res.begin();
      if(i == res.end()){
        cerr << "No report #" << id << endl;
        }
      row = *i;
      client_id = row["client_id"];
      from = row["starttimestamp"];
      to = row["stoptimestamp"];
      if((string)row["report_type"] == "daily") type = daily;
      if((string)row["report_type"] == "hourly") type = hourly;
      if((string)row["report_type"] == "flows") type = flows;
      resolve_ip = false;
      if((string)row["resolve_ip"] == "yes") resolve_ip = true;
      client_description = (string)row["description"];
      }
    else{
      client_id = 0;
      from = 0;
      to = 0;
      resolve_ip = false;
      type = current;
      }
  }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int report::get_client_id()
{
  return client_id;
}

int report::set_client_id(int id)
{
  client_id = id;
  if(client_id != 0){
    try{
      Query query = con.query();
      query << "SELECT description "
              << " FROM client"
              << " WHERE id = " << client_id;
      vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
      Result res = query.store();
      Row row;
      Result::iterator i;
      i = res.begin();
      if(i == res.end()){
        cerr << "No client with id #" << id << endl;
        }
      row = *i;
      client_description = (string)row["description"];
      }
    catch (Exception er){
      vlog(LOG_ERR, "Error: %s\n", er.what());
      exit(-1);
      }
    }
  return client_id;
}

void report::show_from()
{
  cout << from << endl;
}

void report::show_to()
{
  cout << to << endl;
}

report_type report::get_type()
{
  return type;
}

daily_report::daily_report(Connection& cn, int report_id, int cl_id) : report(cn, report_id), client(cn, cl_id)
{
}

int daily_report::make_full_report()
{
  try{
    unsigned long long sum_in = 0;
    unsigned long long sum_out = 0;
    TABLE Tresult;
    TABLE::iterator i;
    ArchFlow archbuf;
    time_t tcur = getnextday(from) - 1;
    unsigned long client_id = 0;
    
    while(tcur < to){
      Tresult[tcur].incoming = 0;
      Tresult[tcur].outcoming = 0;
      tcur = getnextday(tcur + 1) - 1;
      }
    client_id = get_client_id();
    if(0 == cl_seek(f, client_id)){
      fprintf(stderr, "No data for client with id %lu\n", client_id);
      exit(-1);
      }
    if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
      fprintf(stderr, "%s: make_full_report(): fread(): %s\n", prg, strerror(errno));
      exit(-1);
      }
    while(archbuf.client_id == client_id){
      if((time_t)archbuf.timestamp >= from && (time_t)archbuf.timestamp < to){
        tcur = getnextday(archbuf.timestamp) - 1;
        Tresult[tcur].incoming += ((archbuf.dir == in) ? archbuf.d_octets : 0);
        Tresult[tcur].outcoming += ((archbuf.dir == out) ? archbuf.d_octets : 0);
        }
      if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
        break;
        }
      }
    cout << "Использование трафика по дням" << endl;
    cout << endl;
    cout << setw(11) << "Дата" << setw(12) << "Входящий" << setw(12) << "Исходящий" << endl;
    
    char day[256];
    struct tm ttm;
    for(i = Tresult.begin(); i != Tresult.end(); i++){
      cout.width(11);
      memcpy(&ttm, localtime(&((*i).first)), sizeof(struct tm));
      strftime(day, sizeof(day), "%Y-%m-%d", &ttm);
      cout << day;
      cout.width(12);
      cout << i->second.incoming;
      cout.width(12);
      cout << i->second.outcoming << endl;
      sum_in += i->second.incoming;
      sum_out += i->second.outcoming;
      }
    cout << endl << setw(11) << "Всего:";
    cout.width(12);
    cout << sum_in;
    cout.width(12);
    cout << sum_out << endl;
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int daily_report::make_filter_report()
{
    return 0;
}

hourly_report::hourly_report(Connection& cn, int report_id, int cl_id) : report(cn, report_id), client(cn, cl_id)
{
}

int hourly_report::make_full_report()
{
  try{
    unsigned long long sum_in = 0;
    unsigned long long sum_out = 0;
    TABLE Tresult;
    TABLE::iterator i;
    ArchFlow archbuf;
    time_t tcur = getnexthour(from) - 1;
    unsigned long client_id = 0;
  
    vlog(LOG_DEBUG, "Timespan: %lu..%lu\n", tcur, to);
    while(tcur < to){
      Tresult[tcur].incoming = 0;
      Tresult[tcur].outcoming = 0;
      tcur = getnexthour(tcur + 1) - 1;
      }
    vlog(LOG_DEBUG, "get client_id\n");
    client_id = get_client_id();
    vlog(LOG_DEBUG, "Seeking for id %lu...", client_id);
    if(0 == cl_seek(f, client_id)){
      fprintf(stderr, "No data for client with id %lu\n", client_id);
      exit(-1);
      }
    vlog(LOG_DEBUG, "done\n");
    if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
      fprintf(stderr, "%s: make_full_report(): fread(): %s\n", prg, strerror(errno));
      exit(-1);
      }
    while(archbuf.client_id == client_id){
      if((time_t)archbuf.timestamp >= from && (time_t)archbuf.timestamp < to){
        tcur = getnexthour(archbuf.timestamp) - 1;
        Tresult[tcur].incoming += ((archbuf.dir == in) ? archbuf.d_octets : 0);
        Tresult[tcur].outcoming += ((archbuf.dir == out) ? archbuf.d_octets : 0);
        }
      if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
        break;
        }
      }
    cout << setw(15) << "Дата" << setw(12) << "Входящий" << setw(12) << "Исходящий" << endl;
    char hour[256];
    struct tm ttm;
    for(i = Tresult.begin(); i != Tresult.end(); i++){
      memcpy(&ttm, localtime(&((*i).first)), sizeof(struct tm));
      strftime(hour, sizeof(hour), "%Y-%m-%d %H", &ttm);
      cout.width(15);
      cout << hour;
      cout.width(12);
      cout << i->second.incoming;
      cout.width(12);
      cout << i->second.outcoming << endl;
      sum_in += i->second.incoming;
      sum_out += i->second.outcoming;
      }
    cout << endl << setw(11) << "Всего:";
    cout.width(12);
    cout << sum_in;
    cout.width(12);
    cout << sum_out << endl;
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int hourly_report::make_filter_report()
{
    return 0;
}


flows_report::flows_report(Connection& cn, int report_id, int cl_id) : report(cn, report_id), client(cn, cl_id)
{
}

int flows_report::make_full_report()
{
  try{
      unsigned long long sum = 0;
      ArchFlow archbuf;
      off_t fpos;
      unsigned long key = get_client_id();
      if(0 == cl_seek(f, key)){
        fprintf(stderr, "No data for client with id %lu\n", key);
        exit(-1);
        }
      fpos = ftello(f);
      if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
        fprintf(stderr, "%s: make_full_report(): fread(): %s\n", prg, strerror(errno));
        exit(-1);
        }
      string protocol;
      string src_addr;
      string dst_addr;
      string src_port;
      string dst_port;
    
      struct in_addr s,d;
      struct hostent* phost;
      struct hostent src_hostent;
      struct hostent dst_hostent;
      struct in_addr in;
    
      unsigned maxsrcsize = 0;
      unsigned maxdstsize = 0;
    
      map<int, string> dnscache;

      int isrc, idst;
    
      // Find max length of domain names.
      if(resolve_ip){
        vlog(LOG_DEBUG, "Start resolve IP addresses\n");
        sethostent(1);
        while(archbuf.client_id == key){
          if((time_t)archbuf.timestamp >= from && (time_t)archbuf.timestamp < to){
            isrc = htonl(archbuf.src_addr);
            idst = htonl(archbuf.dst_addr);
            s.s_addr = isrc;
            d.s_addr = idst;
            src_addr = inet_ntoa(s);
            dst_addr = inet_ntoa(d);
          
            if(dnscache.find(isrc) == dnscache.end()){
              if(inet_aton(src_addr.c_str(), &in)){
                if((phost = gethostbyaddr((char*)&in.s_addr,sizeof(in.s_addr),AF_INET))){
                  memcpy(&src_hostent, phost, sizeof(src_hostent));
                  src_addr = src_hostent.h_name;
                  phost = NULL;
                  }
                dnscache[isrc] = src_addr;
                if(src_addr.length() > maxsrcsize) maxsrcsize = src_addr.length();
                vlog(LOG_DEBUG, "MISS %s\n", src_addr.c_str());
                }
              }
            else{
              src_addr = dnscache[isrc];
              vlog(LOG_DEBUG, "HIT %s\n", src_addr.c_str());
              }
          
            if(dnscache.find(idst) == dnscache.end()){
              if(inet_aton(dst_addr.c_str(), &in)){
                if((phost = gethostbyaddr((char*)&in.s_addr,sizeof(in.s_addr),AF_INET))){
                  memcpy(&dst_hostent, phost, sizeof(dst_hostent));
                  dst_addr = dst_hostent.h_name;
                  phost = NULL;
                  }
                dnscache[idst] = dst_addr;
                if(dst_addr.length() > maxdstsize) maxdstsize = dst_addr.length();
                vlog(LOG_DEBUG, "MISS %s\n", dst_addr.c_str());
                }
              }
            else{
              dst_addr = dnscache[idst];
              vlog(LOG_DEBUG, "HIT %s\n", dst_addr.c_str());
              }
            }
          if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
            break;
            }
          }
        endhostent();
        vlog(LOG_DEBUG, "Resolving finished\n");
        }
      if(-1 == fseeko(f, fpos, SEEK_SET)){
        fprintf(stderr, "%s: cl_seek(): fseeko(): %s\n", prg, strerror(errno));
        exit(-1);
        }
      //
      if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
        fprintf(stderr, "%s: make_full_report(): fread(): %s\n", prg, strerror(errno));
        exit(-1);
        }
      char hour[256];
      struct tm ttm;
      char port_buf[256];
      if(! resolve_ip) maxsrcsize = 16;
      if(! resolve_ip) maxdstsize = 16;
      cout << setw(20) << "Date"
              << setw(10) << "Protocol"
              << setw(maxsrcsize + 2) << "Src addr"
              << setw(maxdstsize + 2) << "Dst addr"
              << setw(16) << "Src port"
              << setw(16) << "Dst port"
              << setw(10) << "Bytes"
              << endl;
      while(archbuf.client_id == key){
        if((time_t)archbuf.timestamp >= from && (time_t)archbuf.timestamp < to){
          // Do not resolve protocol!
          snprintf(port_buf, sizeof(port_buf), "%u", archbuf.protocol);
          protocol = port_buf;
          /*
          struct protoent * pprotoent = getprotobynumber(archbuf.protocol);
          if(pprotoent != NULL){
            protocol = pprotoent->p_name;
            }
          else{
            protocol = archbuf.protocol;
            }
          */
          // Do not resolve port!
          snprintf(port_buf, sizeof(port_buf), "%u", archbuf.src_port);
          src_port = port_buf;
          /*
          struct servent * pservent = getservbyport(htons(archbuf.src_port), pprotoent->p_name);
          if(pservent != NULL){
            src_port = pservent->s_name;
            }
          else{
            snprintf(port_buf, sizeof(port_buf), "%u", archbuf.src_port);
            src_port = port_buf;
            }
          */
          snprintf(port_buf, sizeof(port_buf), "%u", archbuf.dst_port);
          dst_port = port_buf;
          /*
          pservent = getservbyport(htons(archbuf.dst_port), pprotoent->p_name);
          if(pservent != NULL){
            dst_port = pservent->s_name;
            }
          else{
            snprintf(port_buf, sizeof(port_buf), "%u", archbuf.dst_port);
            dst_port = port_buf;
            }
          */
          s.s_addr = htonl(archbuf.src_addr);
          d.s_addr = htonl(archbuf.dst_addr);
          src_addr = inet_ntoa(s);
          dst_addr = inet_ntoa(d);
          if(resolve_ip){
            src_addr = dnscache[htonl(archbuf.src_addr)];
            if(src_addr == ""){
              src_addr = inet_ntoa(s);
              }
            dst_addr = dnscache[htonl(archbuf.dst_addr)];
            if(dst_addr == ""){
              dst_addr = inet_ntoa(d);
              }
            }
          
          memcpy(&ttm, localtime(&(archbuf.timestamp)), sizeof(struct tm));
          strftime(hour, sizeof(hour), "%Y-%m-%d %H:%M:%S", &ttm);
          cout << setw(20) << hour
                  << setw(10) << protocol.c_str()
                  << setw(maxsrcsize + 2) << src_addr.c_str()
                  << setw(maxdstsize + 2) << dst_addr.c_str()
                  << setw(16) << src_port.c_str()
                  << setw(16) << dst_port.c_str()
                  << setw(10) << archbuf.d_octets
                  << endl;
          sum += archbuf.d_octets;
          }
        if(1 != fread(&archbuf, sizeof(ArchFlow), 1, f)){
          break;
          }
        }
    cout << endl << setw(11) << "Суммарный траффик:";
    cout.width(12);
    cout << sum << endl;
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int flows_report::make_filter_report()
{
 return 0;
}

current_report::current_report(Connection& cn, int report_id, int cl_id) : report(cn, report_id), client(cn, cl_id)
{
}

int current_report::make_full_report()
{
  try{
    Query query = report::con.query();
    query << "SELECT MAX(timestamp) AS day FROM feeding";
    vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
    Result result = query.store();
    Result::iterator i;
    Row row;
    i = result.begin();
    row = *i;
    cout << "Использование трафика по состоянию на " << (const char*)row["day"] << endl;
    cout << endl;
    
    query = report::con.query();
    query << "SELECT SUM(incoming) AS incoming, SUM(outcoming) AS outcoming"
            << " FROM traffic_snapshot"
            << " WHERE client_id = " << get_client_id()
            << " AND MONTH(NOW()) = MONTH(timestamp)"
            << " AND YEAR(NOW()) = YEAR(timestamp)";
    vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
    result = query.store();
    i = result.begin();
    row = *i;
    cout << setw(12) << "Входящий" << setw(12) << "Исходящий" << endl;
    cout << setw(12) << "Mb" << setw(12) << "Mb" << endl;
    if(result.begin() == result.end()){
      cout << "Нет данных" << endl;
      }
    
    for(i = result.begin(); i != result.end(); i++){
      row = *i;
      cout.width(12);
      cout << ((unsigned long long)row["incoming"])/1048576.0;
      cout.width(12);
      cout << ((unsigned long long)row["outcoming"])/1048576.0 << endl;
      }
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int current_report::make_filter_report()
{
  try{
    // Print interface filter
    Query query = report::con.query();
    query << "select id, description"
            << " from filter"
            << " where client_id = " << get_client_id();
    vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
    Result result = query.store();
    Result::iterator i;
    Row row;
    for(i = result.begin(); i != result.end(); i++){
      row = *i;
      query = report::con.query();
      query << "SELECT SUM(incoming) AS incoming, SUM(outcoming) AS outcoming"
              << " FROM filter_counter_snapshot"
              << " WHERE filter_id = " << row["id"]
              << " AND MONTH(NOW()) = MONTH(timestamp)"
              << " AND YEAR(NOW()) = YEAR(timestamp)";
      vlog(LOG_DEBUG, "%s\n", query.preview().c_str());
      Result res = query.store();
      Result::iterator j;
      Row r;
      cout << endl << "Трафик по фильтру " << row["description"] << endl << endl;
      cout << setw(12) << "Входящий" << setw(12) << "Исходящий" << endl;
      cout << setw(12) << "Mb" << setw(12) << "Mb" << endl;
      if(res.begin() == res.end()){
        cout << "Нет данных" << endl;
        }
      for(j = res.begin(); j != res.end(); j++){
        r = *j;
        cout.width(12);
        cout << ((unsigned long long)r["incoming"])/1048576.0;
        cout.width(12);
        cout << ((unsigned long long)r["outcoming"])/1048576.0 << endl;
        }
      }
    return 0;
    }
  catch (Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

