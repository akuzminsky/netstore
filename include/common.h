/* $Id: common.h,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#ifndef __NETSTORE_COMMON_H
#define __NETSTORE_COMMON_H

#include <string.h>
#include <stdarg.h>
#include <stdio.h>
#include <syslog.h>
#include <errno.h>
#include <libgen.h>
#include <math.h>
#include <float.h>
#include <stdlib.h>
#include <unistd.h>
#include <time.h>
#include <fcntl.h>
#include <netdb.h>
#include <ctype.h>
#include <signal.h>


#include <sys/types.h>
#include <sys/stat.h>
#include <sys/param.h>
#include <sys/socket.h>
#include <sys/file.h>
#include <sys/sem.h>
#include <sys/ipc.h>
#include <sys/un.h>
#include <netinet/in.h>

#include <arpa/inet.h>

#include <netdb.h>

#include <iostream>
#include <iomanip>
#include <stdexcept>
#include <sstream>
#include <fstream>
#include <algorithm>

// STL headers
#include <string>
#include <list>
#include <stack>
#include <vector>
#include <map>

// MySQL++ headers
#include <connection.h>
#include <query.h>
#include <result.h>
#include <row.h>

using namespace std;

// Type definitions
typedef enum {in, out, any} direction;
typedef struct if_entry{
    unsigned short mysql_if_id; // Interface id in mysql table
    unsigned short snmp_if_id; // SNMP id of a interface
    unsigned short router_id; // Router id, which interface is relate
    bool accounted; // Whether or not account traffic via that interface
  } If_entry;

typedef list<If_entry> If_list;

#define DEFAULT_CONFIG_FILE "none"
#define NF_VERSION 5

typedef struct RouterConfig{
    unsigned int router_id;
    string router_name;
    string community;
    in_addr_t address;
    unsigned int flow_sequence;
    unsigned long sys_uptime;
  } RouterConfig;

#define MAX_FLOWS 30

typedef struct {
    unsigned short version;
    unsigned short count;
    unsigned long sys_uptime;
    unsigned long unix_secs;
    unsigned long unix_nsecs;
    unsigned long flow_sequence;
    unsigned long reserved;
  } CiscoFlowHeader; 

typedef struct {
    unsigned long src_addr;
    unsigned long dst_addr;
    unsigned long nexthop;
    unsigned short in_iface;
    unsigned short out_iface;
    unsigned long d_pkts;
    unsigned long d_octets;
    unsigned long first;
    unsigned long last;
    unsigned short src_port;
    unsigned short dst_port;
    unsigned char pad;
    unsigned char tcp_flags;
    unsigned char protocol;
    unsigned char tos;
    unsigned short src_as;
    unsigned short dst_as;
    unsigned char src_mask;
    unsigned char dst_mask;
  } CiscoFlow; 

typedef struct {
    unsigned long sequence;
    unsigned short router_id;
    time_t timestamp;
    unsigned long src_addr;
    unsigned long dst_addr;
    unsigned short in_if_id;
    unsigned short out_if_id;
    unsigned long d_pkts;
    unsigned long d_octets; // 4 gygabytes will be enough for everyone (c) Bill Gates
    unsigned char protocol;
    unsigned short src_port;
    unsigned short dst_port;
    unsigned short src_as;
    unsigned short dst_as;
  } Flow; 

typedef struct {
    unsigned long sequence;
    unsigned long client_id;
    direction dir;
    time_t timestamp;
    unsigned long src_addr;
    unsigned long dst_addr;
    unsigned short in_if_id;
    unsigned short out_if_id;
    unsigned long d_pkts;
    unsigned long d_octets; // 4 gygabytes will be enough for everyone (c) Bill Gates
    unsigned char protocol;
    unsigned short src_port;
    unsigned short dst_port;
    unsigned short src_as;
    unsigned short dst_as;
  } ArchFlow; 

typedef struct {
    unsigned long id;
    direction dir;
    time_t timestamp;
    unsigned long d_octets; // 4 gygabytes will be enough for everyone (c) Bill Gates
  } AccFlow; 

typedef struct {
    unsigned long long incoming;
    unsigned long long outcoming;
  } Traffic;

typedef map<unsigned long, Traffic> Traffic_Cl;
typedef map<time_t, Traffic_Cl> Traffic_Ts;


// Functions declarations

int vlog(int lvl, const char* fmt, ...);
time_t getnexttimestamp(time_t);
time_t getprevtimestamp(time_t);
int cl_seek(FILE*, unsigned long);
time_t getnextday(time_t);
time_t getnexthour(time_t);
int show_flow(Flow *);
time_t from_days(unsigned long);
unsigned long to_days(time_t);
unsigned mday(unsigned long);
unsigned numdays(unsigned long);
bool samemonth(unsigned long, unsigned long);
double round2(double);
double round0(double);
#endif
