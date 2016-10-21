/* $Id: collector.cpp,v 1.2 2011/06/23 07:58:29 ingoth Exp $ */
#define HAVE_STDARG_H 1
#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>
#undef DEMO_USE_SNMP_VERSION_3

#include "common.h"
#include "config.h"

using namespace conf;
using namespace mysqlpp;

class credentials{
	public:
		credentials() {};
		~credentials() {};
		string host;
		string user;
		string password;
		string db;
};


void process_flow(int, CONFIG&);
void ntoh_hdr(CiscoFlowHeader*);
void ntoh_cisco_flow(CiscoFlow*);
int show_cisco_flow(CiscoFlow*);
int sync_router_table();


void dump(int);
void rebuildIfTable(int);
void finish(int);
void rotate(int);

bool ext_if(unsigned short);
unsigned short get_mysql_if_id(unsigned short, unsigned int);
int get_flowd_pid(CONFIG&);

int buildIfTable();
int getNumberOfInterfaces(const char*, const char*);
int usage(char*);

extern bool netstore_debug;
credentials mysql_cred;
list<RouterConfig> routers;
string data_file;
vector<Flow> flow_cache;
FILE* f_data;
string progname;
If_list interface_list;
unsigned int sequence = 0;
int int_num = 0;

int main (int argc, char** argv) 
{
  struct sockaddr_in serv_addr;
  char ch;
  extern char *optarg;
  extern int optind;
  
  string action("none");
  string configfile(DEFAULT_CONFIG_FILE);
  int pid;
  
  FILE* pid_file;
  
  progname = argv[0];
  while((ch = getopt(argc, argv, "k:f:gn:")) != -1){
   switch(ch) {
     case 'k': action = optarg; break;
     case 'f': configfile = optarg; break;
     case 'n': int_num = atoi(optarg); break;
     case 'g': netstore_debug = true; break;
     default:
       usage(argv[0]);
       exit(-1);
     }
   }
  CONFIG config(configfile);
  // Define global variables
  mysql_cred.host = config.get_value("mysql_host");
  mysql_cred.user = config.get_value("mysql_user");
  mysql_cred.password = config.get_value("mysql_password");
  mysql_cred.db = config.get_value("mysql_database");
  if(netstore_debug){
    config.show();
    }
  vlog(LOG_DEBUG, "Copying routers from config to the global variable\n");
  for(list<RouterConfig>::iterator ir = config.routers.begin();
		  ir != config.routers.end();
		  ir++){
    routers.push_back(*ir);
    }
  vlog(LOG_DEBUG, "Routers in global variable:\n");
  if(netstore_debug){
    for(list<RouterConfig>::iterator ir = routers.begin();
		    ir != routers.end();
		    ir++){
      vlog(LOG_DEBUG, "router_id: %lu\n", ir->router_id);
      vlog(LOG_DEBUG, "router_name: '%s'\n", ir->router_name.c_str());
      vlog(LOG_DEBUG, "community: '%s'\n", ir->community.c_str());
      vlog(LOG_DEBUG, "address: %lu\n", ir->address);
      vlog(LOG_DEBUG, "flow_sequence: %lu\n", ir->flow_sequence);
      vlog(LOG_DEBUG, "sys_uptime: %lu\n\n", ir->sys_uptime);
      }
    }
  data_file = config.get_value("data");
  // end of...
  if(action != "none"){
    pid = get_flowd_pid(config);
    if(action == "shutdown"){
      if(kill(pid, SIGTERM) < 0){
        vlog(LOG_ERR, "kill(): %s\n", strerror(errno));
        exit(-1);
        }
      exit(0);
      }
    if(action == "dump"){
      if(kill(pid, SIGUSR1) < 0){
        vlog(LOG_ERR, "kill(): %s\n", strerror(errno));
        exit(-11);
        }
      exit(0);
      }
    if(action == "rebuild"){
      if(kill(pid, SIGUSR2) < 0){
        vlog(LOG_ERR, "kill(): %s\n", strerror(errno));
        exit(-1);
        }
      exit(0);
      }
    if(action == "rotate"){
      if(kill(pid, SIGQUIT) < 0){
        vlog(LOG_ERR, "kill(): %s\n", strerror(errno));
        exit(-1);
        }
      exit(0);
      }
    usage(argv[0]);
    }
  argc -= optind;
  if(argc != 0){
    usage(argv[0]);
   exit(1);
    }

  openlog(basename(argv[0]), LOG_PID, LOG_DAEMON);
  if(!netstore_debug){
    if(-1 == daemon(0, 0)) {
      vlog(LOG_ERR, "daemon() %s\n", strerror (errno));
      exit(1);
      }
    }
  vlog(LOG_WARNING, "Starting collector\n");
  
  pid_file = fopen(config.get_value("pid_file"), "w");
  if(pid_file == NULL){
    vlog(LOG_ERR, "Can't open file %s\n", config.get_value("pid_file"));
    vlog(LOG_ERR, "fopen() %s\n", strerror (errno));
    exit(-1);
    }
  vlog(LOG_DEBUG, "process id: %d\n", getpid());
  fprintf(pid_file, "%d\n", getpid());
  fclose (pid_file);

  if(-1 == sync_router_table()){
    vlog(LOG_ERR, "Can't synchronize routers table\n");
    exit(-1);
    }
  if(-1 == buildIfTable()){
    vlog(LOG_ERR, "Error occures while building interface table\n");
    exit(-1);
    }

  // Open socket
  int s;
  if((s = socket(AF_INET, SOCK_DGRAM, 0)) < 0){
    vlog(LOG_ERR, "socket(): %s", strerror (errno));
    exit(-1);
    }
  bzero(&serv_addr, sizeof(serv_addr));
  serv_addr.sin_family = AF_INET;
  serv_addr.sin_port = htons(atoi(config.get_value("port")));
  if((string)config.get_value("listen") == (string)"0.0.0.0"){
    bzero(&serv_addr.sin_addr, sizeof(struct in_addr));
    }
  if(0 == inet_aton(config.get_value("listen"), &serv_addr.sin_addr)){
    struct hostent *hp;
    hp = gethostbyname(config.get_value("listen"));
    if(hp == NULL){
      vlog(LOG_ERR, "gethostbyname(): %s\n", hstrerror(h_errno));
      bzero(&serv_addr.sin_addr, sizeof(struct in_addr));
      }
    else{
      bcopy(hp->h_addr, &serv_addr.sin_addr, hp->h_length);
      }
    }

  if(bind(s, (struct sockaddr *) &serv_addr, sizeof (serv_addr)) < 0){
    vlog(LOG_ERR, "bind (): %s\n", strerror (errno));
    exit(-1);
    }

  vlog(LOG_DEBUG, "Listen %s:%s\n", config.get_value("listen"), config.get_value("port"));
  
  vlog(LOG_DEBUG, "openning data file  %s\n", config.get_value("data"));
  f_data = fopen(config.get_value("data"), "a");
  if(f_data == NULL){
    vlog(LOG_ERR, "Can't open file '%s'\n", config.get_value("data"));
    vlog(LOG_ERR, "fopen() '%s'\n", strerror (errno));
    exit(-1);
    }
  
  signal(SIGALRM, dump);
  signal(SIGUSR1, dump);
  signal(SIGUSR2, rebuildIfTable);
  signal(SIGQUIT, rotate);
  signal(SIGHUP, rebuildIfTable);
  signal(SIGTERM, finish);
  signal(SIGPIPE, SIG_IGN);

  fd_set fdset;
  FD_ZERO(&fdset);
  FD_SET(s, &fdset);
  vlog(LOG_WARNING, "Starting normal operation\n");
  while(1){ 
   if(select(FD_SETSIZE, &fdset, NULL, NULL, NULL) < 0){
     if(errno == EINTR){
       continue;
       }
     }
   if(FD_ISSET(s, &fdset)){
     vlog(LOG_DEBUG, "got udp packet, proccessing it..\n");
     process_flow(s, config);
     vlog(LOG_DEBUG, "processing done\n");
     }
   FD_SET(s, &fdset);
   }
}

inline void process_flow(int s, CONFIG& conf)
{
  char buf[sizeof(CiscoFlowHeader) + MAX_FLOWS * sizeof(CiscoFlow)];
  CiscoFlowHeader *hdr = (CiscoFlowHeader*)buf;
  ssize_t pkt_len;
  socklen_t from_len = sizeof(struct sockaddr);
  struct sockaddr_in from_addr;
  list<RouterConfig>::iterator i_r_config;
  int i;
  Flow flow;
  CiscoFlow* ciscoFlow;
  
  pkt_len = recvfrom(s, buf, sizeof(buf), 0, (struct sockaddr*)&from_addr, &from_len);
  if(pkt_len == -1){
   vlog(LOG_ERR, "recvfrom() %s\n", strerror (errno));
   return;
   }
  
  vlog(LOG_DEBUG, "packet from %s(0x%08X)\n", inet_ntoa(from_addr.sin_addr), from_addr.sin_addr.s_addr);
  for(i_r_config = conf.routers.begin(); i_r_config != conf.routers.end(); i_r_config++){
   if(from_addr.sin_addr.s_addr == i_r_config->address){
     ntoh_hdr(hdr);
     vlog(LOG_DEBUG, "header:\n");
     vlog(LOG_DEBUG, "version: %d\n", hdr->version);
     vlog(LOG_DEBUG, "count: %d\n", hdr->count);
     vlog(LOG_DEBUG, "sys_uptime: %lu\n", hdr->sys_uptime);
     vlog(LOG_DEBUG, "unix_secs: %lu\n", hdr->unix_secs);
     vlog(LOG_DEBUG, "unix_nsecs: %lu\n", hdr->unix_nsecs);
     vlog(LOG_DEBUG, "flow_sequence: %lu\n", hdr->flow_sequence);
     if(hdr->version != NF_VERSION){
       vlog(LOG_ERR, "Invalid flow version: %d, ignoring packet\n", hdr->version);
       break;
       }
     if(i_r_config->flow_sequence != hdr->flow_sequence){
       if (i_r_config->flow_sequence == 0){
         vlog(LOG_WARNING, "init flow_sequence\n");
         i_r_config->flow_sequence = hdr->flow_sequence;
         }
       else{
         vlog(LOG_ERR, "warning! lost %lu flows from router %s !\n", hdr->flow_sequence - i_r_config->flow_sequence, i_r_config->router_name.c_str());
         i_r_config->flow_sequence = hdr->flow_sequence;
         }
       }
     if(i_r_config->sys_uptime > hdr->sys_uptime){
       vlog(LOG_WARNING, "warning! router %s was rebooted.\n", i_r_config->router_name.c_str());
       buildIfTable();
       }
     i_r_config->sys_uptime = hdr->sys_uptime;
   
     for(i = 0; i < hdr->count; i++){
       ciscoFlow = (CiscoFlow*)(buf + sizeof(CiscoFlowHeader) + i*sizeof(CiscoFlow));
       ntoh_cisco_flow (ciscoFlow);
       if(netstore_debug){
         show_cisco_flow (ciscoFlow);
         }
       flow.src_addr = ciscoFlow->src_addr;
       flow.dst_addr = ciscoFlow->dst_addr;
       flow.d_pkts = ciscoFlow->d_pkts;
       flow.d_octets = ciscoFlow->d_octets;
       flow.src_port = ciscoFlow->src_port;
       flow.dst_port = ciscoFlow->dst_port;
       flow.src_as = ciscoFlow->src_as;
       flow.dst_as = ciscoFlow->dst_as;
       flow.protocol = ciscoFlow->protocol;
       flow.timestamp = hdr->unix_secs;
       flow.in_if_id = get_mysql_if_id(ciscoFlow->in_iface, i_r_config->router_id);
       flow.out_if_id = get_mysql_if_id(ciscoFlow->out_iface, i_r_config->router_id);
       flow.router_id = i_r_config->router_id;
       
       if(ext_if(flow.in_if_id) || ext_if(flow.out_if_id)){
         vlog(LOG_DEBUG, "store flow in memory\n");
         flow.sequence = sequence;
         sequence++;
         flow_cache.insert(flow_cache.end(), flow);
         if(flow_cache.size() >= 0.95*strtoul(conf.get_value("cache_size"), NULL, 10)/sizeof(Flow)){
           vlog(LOG_WARNING, "Flow cache utilization >= 95%%. Autodump.\n");
           dump(12345);
           }
         }
       }// End for
     i_r_config->flow_sequence += hdr->count;
     }
    }
  return;
}


inline void ntoh_hdr(CiscoFlowHeader *hdr)
{
   hdr->version = ntohs(hdr->version);
   hdr->count = ntohs(hdr->count);
   hdr->sys_uptime = ntohl(hdr->sys_uptime);
   hdr->unix_secs = ntohl(hdr->unix_secs);
   hdr->unix_nsecs = ntohl(hdr->unix_nsecs); 
   hdr->flow_sequence = ntohl(hdr->flow_sequence); 
   return;
}

inline void ntoh_cisco_flow(CiscoFlow *cf)
{
   cf->in_iface = ntohs(cf->in_iface);
   cf->out_iface = ntohs(cf->out_iface);
   cf->d_pkts = ntohl(cf->d_pkts);
   cf->d_octets = ntohl(cf->d_octets);
   cf->first = ntohl(cf->first);
   cf->last = ntohl(cf->last);
   cf->src_addr = ntohl(cf->src_addr);
   cf->dst_addr = ntohl(cf->dst_addr);
   cf->src_port = ntohs(cf->src_port);
   cf->dst_port = ntohs(cf->dst_port);
   cf->src_as = ntohs(cf->src_as);
   cf->dst_as = ntohs(cf->dst_as);
   return;
}

int usage (char *prog_name)
{
   return vlog(LOG_ERR, 
      "Usage: %s [-k operation] [-f config] -g\n"
     "   operations:\n"
     "     shutdown   - daemon stutdown\n"
     "     dump       - dump gathered data in data file\n"
     "     rebuild    - rebuild interface table\n"
     "     rotate     - rotate data file\n\n"
     "     -g         run collector in debug mode\n"
     "     -n num     specify number of interfaces\n"
     "     -f config  use `config' as configuration file. Default /etc/collector.conf\n",
     prog_name);
}


void dump(int xxx) 
{
   signal(SIGALRM, SIG_IGN);
   signal(SIGUSR1, SIG_IGN);
   signal(SIGUSR2, SIG_IGN);
   signal(SIGQUIT, SIG_IGN);
   signal(SIGHUP, SIG_IGN);
   signal(SIGTERM, SIG_IGN);   

   vlog(LOG_WARNING, "dumping data\n");
   
   vector<Flow>::iterator i_flow_cache;
   Flow fl;  
   for(i_flow_cache = flow_cache.begin(); i_flow_cache != flow_cache.end(); i_flow_cache++){
     fl = *i_flow_cache;
     if(1 != fwrite(&fl, sizeof(Flow), 1, f_data)){
       vlog(LOG_ERR, "fwrite(): %s\n", strerror(errno));
       }
     }
   flow_cache.clear();
   signal(SIGALRM, dump);
   signal(SIGUSR1, dump);
   signal(SIGUSR2, rebuildIfTable);
   signal(SIGQUIT, rotate);
   signal(SIGHUP, rebuildIfTable);
   signal(SIGTERM, finish);   
}

void rebuildIfTable(int xxx) 
{
   signal(SIGALRM, SIG_IGN);
   signal(SIGUSR1, SIG_IGN);
   signal(SIGUSR2, SIG_IGN);
   signal(SIGQUIT, SIG_IGN);
   signal(SIGHUP, SIG_IGN);
   signal(SIGTERM, SIG_IGN);   
   
   vlog(LOG_WARNING, "Rebuilding interface table\n");
   if(-1 == buildIfTable()){
     exit(1);
     }
   
   signal(SIGALRM, dump);
   signal(SIGUSR1, dump);
   signal(SIGUSR2, rebuildIfTable);
   signal(SIGQUIT, rotate);
   signal(SIGHUP, rebuildIfTable);
   signal(SIGTERM, finish);   
}

void finish(int xxx)
{
   vlog(LOG_ERR, "Shutting down collector\n");
   dump(12345);
   fclose(f_data);
   vlog(LOG_ERR, "Exit normally\n");
   exit(0);
}

void rotate(int xxx)
{
   signal(SIGALRM, SIG_IGN);
   signal(SIGUSR1, SIG_IGN);
   signal(SIGUSR2, SIG_IGN);
   signal(SIGQUIT, SIG_IGN);
   signal(SIGHUP, SIG_IGN);
   signal(SIGTERM, SIG_IGN);   

   char new_data_file_name[64];

   vlog(LOG_WARNING, "Rotating files\n");
   strcpy(new_data_file_name, data_file.c_str());
   strcat(new_data_file_name, ".0");
   dump(12345);
   fclose(f_data);

   if(rename(data_file.c_str(), new_data_file_name) < 0){
     vlog(LOG_ERR, "rename (%s,%s): %s\n", data_file.c_str(), new_data_file_name, strerror(errno));
     }
   
   if(NULL == (f_data = fopen(data_file.c_str(), "w"))){
     vlog(LOG_ERR, "Can't open file '%s'\n", data_file.c_str());
     vlog(LOG_ERR, "fopen(): %s\n", strerror(errno));
     }
   sequence = 0;
   signal(SIGALRM, dump);
   signal(SIGUSR1, dump);
   signal(SIGUSR2, rebuildIfTable);
   signal(SIGQUIT, rotate);
   signal(SIGHUP, rebuildIfTable);
   signal(SIGTERM, finish);   
}

inline bool ext_if(unsigned short if_id)
{
   If_list::iterator i;
   if(if_id == 0){
     // if traffic is dropped if == 0.
     return false;
     }
   for(i = interface_list.begin(); i != interface_list.end(); i++){
     if(i->mysql_if_id == if_id){
       return i->accounted;
       }
     }
   vlog(LOG_ERR, "Can't find interface MySQL identifier %d\n", if_id);
   return false;
}

inline unsigned short get_mysql_if_id(unsigned short snmp_id, unsigned int router_id)
{
   If_list::iterator i;
   if(snmp_id == 0){
    // if traffic is dropped if == 0.
    return 0;
    }
   for(i = interface_list.begin(); i != interface_list.end(); i++){
     if(i->snmp_if_id == snmp_id && i->router_id == router_id){
       return i->mysql_if_id;
       }
     }
   vlog(LOG_ERR, "Can't find interface identifier for router %d, SNMP id %d\n", router_id, snmp_id);
   return 0;
}

int get_flowd_pid(CONFIG& conf)
{
  FILE* fp;
  int pid = 0;
  
  if(conf.get_value("pid_file") == ""){
    vlog(LOG_ERR, "`pid_file' option not specified in config file\n");
    exit(-1);
    }
  if(NULL == (fp = fopen(conf.get_value("pid_file"), "r"))){
   vlog(LOG_ERR, "Can't open file %s", conf.get_value("pid_file"));
    vlog(LOG_ERR, "fopen(): %s", strerror(errno));
    exit(1);
    }
  fscanf(fp, "%d", &pid);
  fclose(fp);
  vlog(LOG_INFO, "get_flowd_pid(): returning pid %d\n", pid);
  return pid;
}

int sync_router_table()
{
  try{
   vlog(LOG_WARNING, "Synchronizing mysql table `routers'\n");
   Connection con(mysql_cred.db.c_str(),
		  mysql_cred.host.c_str(),
		  mysql_cred.user.c_str(),
		  mysql_cred.password.c_str());
   Query query = con.query();
   Result res;
   ResNSel nsres;
   list<RouterConfig>::iterator i_r_config;
   for(i_r_config = routers.begin(); i_r_config != routers.end(); i_r_config++){
     query.reset();
     query << "SELECT router_id FROM routers WHERE hostname = '" << i_r_config->router_name << "'";
     res = query.store();
     if(res.size() == 0){
       query.reset();
       vlog(LOG_WARNING, "Entering the new router (%lu) '%s' into database\n", i_r_config->router_id, i_r_config->router_name.c_str());
       query << "INSERT INTO routers(hostname) VALUES('" << i_r_config->router_name << "')";
       nsres = query.execute();
       i_r_config->router_id = nsres.insert_id;
       }
     else{
       Result::iterator i;
       i = res.begin();
       Row row = *i;
       i_r_config->router_id = (unsigned int)row["router_id"];
       }
     }
   con.close();
   vlog(LOG_WARNING, "Done successfully.\n");
   return 0;
    }
  catch(Exception er){
    vlog(LOG_ERR, "Error: %s\n", er.what());
    exit(-1);
    }
}

int getNumberOfInterfaces(const char *peer, const char *community)
{
   struct snmp_session session;
   struct snmp_session* ss;
   struct snmp_pdu* pdu;
   struct snmp_pdu* response;
   struct variable_list *vars;
   oid anOID[MAX_OID_LEN];
   size_t anOID_len = MAX_OID_LEN;
   
   int status; 
   
   if(int_num != 0){
     return int_num;
     }
   memset(&session, 0, sizeof(struct snmp_session));
   session.remote_port = SNMP_DEFAULT_REMPORT;
   session.timeout = SNMP_DEFAULT_TIMEOUT;
   session.retries = SNMP_DEFAULT_RETRIES;
   session.authenticator = NULL;
   session.version = SNMP_VERSION_2c; /*  or SNMP_VERSION_1 */
   session.community = (unsigned char *)community;
   session.community_len = strlen (community);
   session.peername = (char*)peer; 

   init_mib ();

   if((ss = snmp_open(&session)) == NULL){
     vlog(LOG_ERR, "Can't open SNMP session to %s\n", peer);
     snmp_perror("snmpget request");
     return -1;
     }

   pdu = snmp_pdu_create (SNMP_MSG_GET);
   if(read_objid(".1.3.6.1.2.1.2.1.0", anOID, &anOID_len) == 0){
     vlog(LOG_ERR, "Invalid object identifier: interfaces.ifNumber.0\n");
     snmp_close(ss);
     return -1;
     }
     
   snmp_add_null_var(pdu, anOID, anOID_len);
   status = snmp_synch_response(ss, pdu, &response);

   if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR){
     int ret_val = -1;
     
     vars = response->variables;
     if(vars->type == ASN_INTEGER){
       ret_val = *vars->val.integer;
       }
     else{
       vlog(LOG_ERR, "Value isn't an integer\n");
       }
     snmp_free_pdu (response);
     snmp_close (ss);
     return ret_val;
     }
   else{
     if(status == STAT_SUCCESS){
       vlog(LOG_ERR, "Error in packet. Reason: %s\n", snmp_errstring(response->errstat));
       snmp_free_pdu (response);
       snmp_close (ss);
       return -1;
       }
     else{
       char *err;

       snmp_error(ss, NULL, NULL, &err);
       vlog(LOG_ERR, "%s: %s\n", peer, err);
       free(err);
       snmp_free_pdu (response);
       snmp_close (ss);
       return -1;
       }
     }
}

int buildIfTable()
{
   try{
     vlog(LOG_WARNING, "Building interface table\n");
     Connection con(mysql_cred.db.c_str(),
		     mysql_cred.host.c_str(),
		     mysql_cred.user.c_str(),
		     mysql_cred.password.c_str());
     
     Query query = con.query();
     Result res;
     
     query << "SELECT router_id, hostname FROM routers";
     res = query.store();
     vlog(LOG_ERR, "There is(are) %d router(s) in mysql database\n", res.size());
     
     Result::iterator i_res;
     Row row;
     
     interface_list.clear();
     
     for(i_res = res.begin(); i_res != res.end(); i_res++){
       row = *i_res;
       
       list<RouterConfig>::iterator i_r_config;
       i_r_config = routers.begin();
       // Find iterator to router's config
       vlog(LOG_ERR, "Looking for config for the router id %d\n", (unsigned int)row["router_id"]);
       while(i_r_config != routers.end()){
	 vlog(LOG_DEBUG, "router_id: %lu\n", i_r_config->router_id);
         if(i_r_config->router_id == (unsigned int)row["router_id"]) break;
         i_r_config++;
         }
       vlog(LOG_ERR, "config has been found for router(%d) '%s'\n", i_r_config->router_id, i_r_config->router_name.c_str());
       
       struct snmp_session session;
       struct snmp_session* ss;
       struct snmp_pdu *pdu, *response;
       struct variable_list *vars;
       oid anOID[MAX_OID_LEN];
       size_t anOID_len = MAX_OID_LEN;
       int status, id, if_found;
       
       init_mib();
       vlog(LOG_DEBUG,"Getting number of interfaces\n");
       int if_num = getNumberOfInterfaces(i_r_config->router_name.c_str(), i_r_config->community.c_str());
       vlog(LOG_ERR, "router '%s' has %d interfaces\n", i_r_config->router_name.c_str(), if_num);
       if(if_num <= 0){
         vlog(LOG_ERR, "Can't get number of interfaces for router: '%s'\n", i_r_config->router_name.c_str());
         return -1;
         }
       init_snmp("snmpapp");
       snmp_sess_init(&session);
       
       //memset(&session, 0, sizeof(struct snmp_session));
       session.remote_port = SNMP_DEFAULT_REMPORT;
       session.timeout = SNMP_DEFAULT_TIMEOUT;
       session.retries = SNMP_DEFAULT_RETRIES;
       session.authenticator = NULL;
       session.version = SNMP_VERSION_2c; /* SNMP_VERSION_1 */
       session.community = (unsigned char*)i_r_config->community.c_str();
       session.community_len = strlen(i_r_config->community.c_str());
       //strncpy(session.peername, i_r_config->router_name.c_str(), strlen(i_r_config->router_name.c_str()));
       session.peername = (char*)i_r_config->router_name.c_str();
       SOCK_STARTUP;
       vlog(LOG_ERR, "open session to %s using community %s\n", session.peername, session.community);
       if((ss = snmp_open(&session)) == NULL){
         vlog(LOG_ERR, "Can't open SNMP session to %s\n", i_r_config->router_name.c_str());
         snmp_perror("snmpopen");
         return -1;
         }
       // Get list of interface descriptions
       vlog(LOG_ERR, "getting interfaces descriptions\n");
       id = 1;
       if_found = 1;
       while(if_found <= if_num){
         char buf[128];
         char if_desc[128];
         pdu = snmp_pdu_create(SNMP_MSG_GET);
         //sprintf(buf, "interfaces.ifTable.ifEntry.ifDescr.%d", id);
         sprintf(buf, ".1.3.6.1.2.1.2.2.1.2.%d", id);
         vlog(LOG_ERR, "getting %s\n", buf);
         if(read_objid(buf, anOID, &anOID_len) == 0){
           vlog(LOG_ERR, "Invalid object identifier: %s\n", buf);
           snmp_close (ss);
           return -1;
           }
         snmp_add_null_var(pdu, anOID, anOID_len);
         status = snmp_synch_response(ss, pdu, &response);
         if(status == STAT_SUCCESS && response->errstat == SNMP_ERR_NOERROR){
           vars = response->variables;
           if(vars->val.string != NULL){
             strncpy(if_desc, (const char*)vars->val.string, vars->val_len);
             if_desc[vars->val_len] = '\0';
             if_found++;
             vlog(LOG_DEBUG, "interface(%d) %s\n", id, if_desc);
             }
           else{
             vlog(LOG_ERR, "Got empty reply on %s\n", buf);
             }
           }
         else{
           if(status == STAT_SUCCESS){
             vlog(LOG_ERR, "Error in packet. Reason: %s\n", snmp_errstring (response->errstat));
             snmp_free_pdu (response);
             snmp_close (ss);
             return -1;
             }
           else{
             char *err;
             snmp_error(ss, NULL, NULL, &err);
             vlog(LOG_ERR, "%s\n", err);
             free(err);
             snmp_free_pdu(response);
             snmp_close(ss);
             return -1;
             }
           }
         
         // Find identifier of inetface on router i_r_config->router_id
         // with description if_descr.
         // If there is no one - insert.
         query.reset();
         query << "select if_id, description, type from interfaces"
                 << " where router_id = " << i_r_config->router_id 
                 << " and description = '" << if_desc << "'";
         Result if_res = query.store();
         Result::iterator i_if_res = if_res.begin();
         if(if_res.size() == 0){
           // Insert new interface
           query.reset();
           query << "insert into interfaces(router_id, description, type) values("
                   << i_r_config->router_id << ", "
                   << "'" << if_desc << "',"
                   << "'Internal')";
           ResNSel nsres = query.execute();
           If_entry if_buf;
           if_buf.mysql_if_id = nsres.insert_id;
           if_buf.snmp_if_id = id;
           if_buf.router_id = i_r_config->router_id;
           if_buf.accounted = false;
           interface_list.push_back(if_buf);
           vlog(LOG_WARNING, "New interface has been added to `interfaces' table\n");
           vlog(LOG_WARNING, "Type is set to 'Internal'.\n");
           vlog(LOG_WARNING, "Change it to 'External', if you need and rebuild internal interface table\n");
           vlog(LOG_WARNING, "sending HUP signal to collector (killall -HUP %s)\n", basename(progname.c_str()));
           }
         else{
           Row  row = *i_if_res;
           If_entry if_buf;
           if_buf.mysql_if_id = (unsigned int)row["if_id"];
           if_buf.snmp_if_id = id;
           if_buf.router_id = i_r_config->router_id;
           if(string(row["type"]) == "Internal"){
             if_buf.accounted = false;
             }
           else{
             if_buf.accounted = true;
             }
           interface_list.push_back(if_buf);
           }  
         snmp_free_pdu(response);
         if(id == 6666666) {
           vlog(LOG_ERR, "Unexpected error. Infinity loop\n");
           snmp_close(ss);
           return -1;
           }
         id++;
         }
       snmp_close (ss);
       SOCK_CLEANUP;
       }
     If_list::iterator i_if;
     vlog(LOG_ERR, "Interface list:\n");
     vlog(LOG_ERR, "Mysql id  SNMP id  router id  accounted?\n");
     for(i_if = interface_list.begin(); i_if != interface_list.end(); i_if++){
       vlog(LOG_ERR, "%8d  %7d  %9d  %9d\n", i_if->mysql_if_id, i_if->snmp_if_id, i_if->router_id, i_if->accounted == true? 1: 0);
       }
     vlog(LOG_WARNING, "Done successfully.\n");
     return 0;
     }
   catch(Exception er){
     vlog(LOG_ERR, "Error: %s\n", er.what());
     exit(1);
     }
}

int show_cisco_flow(CiscoFlow* cf)
{
  struct in_addr s;
  s.s_addr = cf->src_addr;
  vlog(LOG_DEBUG, "Source IP address (src_addr): %s\n", inet_ntoa(s));
  s.s_addr = cf->dst_addr;
  vlog(LOG_DEBUG, "Destination IP address (dst_addr): %s\n", inet_ntoa(s));
  s.s_addr = cf->nexthop;
  vlog(LOG_DEBUG, "IP address of next hop router (nexthop): %s\n", inet_ntoa(s));
  vlog(LOG_DEBUG, "SNMP index of input interface (in_iface): %u\n", cf->in_iface);
  vlog(LOG_DEBUG, "SNMP index of output interface (out_iface): %u\n", cf->out_iface);
  vlog(LOG_DEBUG, "Packets in the flow (d_pkts): %lu\n", cf->d_pkts);
  vlog(LOG_DEBUG, "Total number of Layer 3 bytes in the packets of the flow (d_octets): %lu\n", cf->d_octets);
  vlog(LOG_DEBUG, "SysUptime at start of flow (first): %lu secs?\n", cf->first);
  vlog(LOG_DEBUG, "SysUptime at the time the last packet of the flow was received (last): %lu secs?\n", cf->last);
  vlog(LOG_DEBUG, "TCP/UDP source port number or equivalent (src_port): %u\n", cf->src_port);
  vlog(LOG_DEBUG, "TCP/UDP destination port number or equivalent (dst_port): %lu\n", cf->dst_port);
  vlog(LOG_DEBUG, "Unused (zero) bytes (pad): %u\n", cf->pad);
  vlog(LOG_DEBUG, "Cumulative OR of TCP flags (tcp_flags): %02X\n", cf->tcp_flags);
  vlog(LOG_DEBUG, "IP protocol type (protocol): %u\n", cf->protocol);
  vlog(LOG_DEBUG, "IP type of service (tos): %u\n", cf->tos);
  vlog(LOG_DEBUG, "Autonomous system number of the source, either origin or peer (src_as): %u\n", cf->src_as);
  vlog(LOG_DEBUG, "Autonomous system number of the destination, either origin or peer (dst_as): %u\n", cf->dst_as);
  s.s_addr = cf->src_mask;
  vlog(LOG_DEBUG, "Source address prefix mask bits (src_mask): %s\n", inet_ntoa(s));
  s.s_addr = cf->dst_mask;
  vlog(LOG_DEBUG, "Destination address prefix mask bits (dst_mask): %s\n", inet_ntoa(s));
  return 0;
}
