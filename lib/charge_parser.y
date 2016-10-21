/*$Id: charge_parser.y,v 1.6 2008/02/23 09:01:53 ingoth Exp $*/
%skeleton "lalr1.cc"
%defines
%output="charge_parser.cpp"
%parse-param {charge::Lexer& yylex}
%parse-param {charge::SERVICE& service}
%define "parser_class_name" "Parser"
%name-prefix="charge"

%{
#include "common.h"
#include "classes.h"

namespace charge{
class Lexer;
}

%}

%union {
  double dval;
  int ival;
  const char* sval;
}

%token IF
%token AND OR NOT
%token EQ NEQ GT NGT LT NLT
%token <sval> IDENTIFIER
%token <dval> NUMBER
%token RETURN
%token <sval> FUNCTION

%type <dval> expression
%type <dval> extexpression
%type <ival> condition
%type <dval> ifoperator

%start statementlist

%left EQ NEQ GT NGT LT NLT
%left OR
%left AND
%left NOT

%left '+' '-'
%left '*' '/'
%nonassoc UMINUS

%{
map<string,double> vartable;
string var_buffer, buf;
#include "charge_lexer.h"
%}

%%
statementlist : statement
              | statementlist statement
              ;
statement : assigneoperator
          | returnoperator
          ;
assigneoperator : IDENTIFIER { var_buffer = $1; } '=' extexpression ';' 
                  { 
                  vartable[var_buffer] = $4; 
                  vlog(LOG_DEBUG, "%s = %f\n", var_buffer.c_str(), $4);
                  }
                ;
extexpression : expression 
              | ifoperator
              ;
expression : '(' expression ')' { $$ = $2; }
           | expression '+' expression { $$ = $1 + $3; }
           | expression '-' expression { $$ = $1 - $3; }
           | expression '*' expression { $$ = $1 * $3; }
           | expression '/' expression { $$ = $1 / $3; }
           | '-' expression { $$ = - $2; } %prec UMINUS
           | IDENTIFIER { $$ = vartable[$1]; }
           | FUNCTION { buf = $1; } '(' expression ')' { 
              vlog(LOG_DEBUG, "Call to %s(%u)\n", buf.c_str(), (unsigned long)$4);
              if(buf == "traffic_in"){
                $$ = service.traffic_in((unsigned long)$4);
                }
              if(buf == "traffic_out"){
                $$ = service.traffic_out((unsigned long)$4);
                }
              if(buf == "traffic"){
                $$ = service.traffic((unsigned long)$4);
                }
              if(buf == "filter_in"){
                $$ = service.filter_in((unsigned long)$4);
                }
              if(buf == "filter_out"){
                $$ = service.filter_out((unsigned long)$4);
                }
              if(buf == "filter"){
                $$ = service.filter((unsigned long)$4);
                }
              if(buf == "rel_time"){
                $$ = service.rel_time((unsigned long)$4);
                }
              }
           | NUMBER 
           ;
ifoperator : IF '(' condition ',' expression ',' expression ')' { if( $3 ) $$ = $5; else $$ = $7;}
           ;
returnoperator : RETURN extexpression ';' {
	                                service.set_price($2); 
                                        vlog(LOG_DEBUG, "Current price %.4f\n", $2);
                                        }
               ;
condition : '(' condition ')' { $$ = $2; }
           | condition OR condition { $$ = $1 || $3; }
           | condition GT condition { $$ = $1 > $3; }
           | condition NGT condition { $$ = $1 <= $3; }
           | condition LT condition { $$ = $1 < $3; }
           | condition NLT condition { $$ = $1 >= $3; }
           | condition EQ condition { $$ = $1 == $3; }
           | condition NEQ condition { $$ = $1 != $3; }
           | condition AND condition { $$ = $1 && $3; }
           | NOT condition { $$ = ! $2; }
           | expression OR expression { $$ = $1 || $3; }
           | expression GT expression { $$ = $1 > $3; }
           | expression NGT expression { $$ = $1 <= $3; }
           | expression LT expression { $$ = $1 < $3; }
           | expression NLT expression { $$ = $1 >= $3; }
           | expression EQ expression { $$ = $1 == $3; }
           | expression NEQ expression { $$ = $1 != $3; }
           | expression AND expression { $$ = $1 && $3; }
           ;
%%

bool netstore_debug = false;
static const char *days[] =
  { "su", "mo", "tu", "we", "th", "fr", "sa", "wk", "any", "al" };

#define DAYMIN    (24*60)
#define WEEKMIN    (24*60*7)
#define val(x)    (( (x) < 48 || (x) > 57) ? 0 : ((x) - 48))


double round0(double x)
{
  bool flag = false;
  long long c = 0;
  double res = 0.0;
  
  if(x < 0){
    x = -x;
    flag = true;
    }
  c = (long long)floor(x);
  res = c - 1 + rint(x - c + 1);
  if(flag){
    res = -res;
    }
  return res;
}
  
double round2(double x)
{
  return round0(x*100) / 100;
}

int show_flow(Flow *flow)
{
  return fprintf(stderr, "%lu;%u;%lu;%lu;%lu;%u;%u;%lu;%lu;%u;%u;%u;%u;%u\n",
      flow->sequence,
      flow->router_id,
      flow->timestamp,
      flow->src_addr,
      flow->dst_addr,
      flow->in_if_id,
      flow->out_if_id,
      flow->d_pkts,
      flow->d_octets,
      flow->protocol,
      flow->src_port,
      flow->dst_port,
      flow->src_as,
      flow->dst_as);
}

time_t getnextday(time_t t)
{
  struct tm* p;
  time_t result;
  t += 3600 * 24;
  p = localtime(&t);
  p->tm_sec = 0;
  p->tm_min = 0;
  p->tm_hour = 0;
  result = mktime(p);
  return result;
}

time_t getnexthour(time_t t)
{
  struct tm* p;
  time_t result;
  vlog(LOG_DEBUG, "getnexthour(): arg %s", ctime(&t));
  t += 3600;
  p = localtime(&t);
  p->tm_sec = 0;
  p->tm_min = 0;
  result = mktime(p);
  vlog(LOG_DEBUG, "getnexthour(): res %s", ctime(&result));
  return result;
}

time_t getnexttimestamp(time_t starttimestamp)
{
  struct tm nexttm;
  time_t res;
  char buf[32];
  memcpy(&nexttm, localtime(&starttimestamp), sizeof(nexttm));
  nexttm.tm_sec = 0;
  nexttm.tm_min = 0;
  nexttm.tm_hour = 0;
  nexttm.tm_mday = 1;
  nexttm.tm_year = (nexttm.tm_mon == 11) ? nexttm.tm_year + 1: nexttm.tm_year;
  nexttm.tm_mon = (nexttm.tm_mon == 11) ? 0: nexttm.tm_mon + 1;
  nexttm.tm_isdst = -1;
  res = mktime(&nexttm);
  ctime_r(&starttimestamp, buf);
  vlog(LOG_DEBUG, "getnexttimestamp(): argument %s\n", buf);
  ctime_r(&res, buf);
  vlog(LOG_DEBUG, "getnexttimestamp(): result %s\n", buf);
  return res;
}

time_t getprevtimestamp(time_t starttimestamp)
{
  struct tm nexttm;
  time_t res;
  memcpy(&nexttm, localtime(&starttimestamp), sizeof(nexttm));
  nexttm.tm_sec = 0;
  nexttm.tm_min = 0;
  nexttm.tm_hour = 0;
  nexttm.tm_mday = 1;
  nexttm.tm_isdst = -1;
  res = mktime(&nexttm);
  vlog(LOG_DEBUG, "getprevtimestamp(): arg %u, res %u\n", starttimestamp, res);
  return res;
}


int cl_seek(FILE* f, unsigned long key)
{
  ArchFlow fi;
  off_t l, u, i;

  if(-1 == fseeko(f, 0, SEEK_END)){
    vlog(LOG_ERR, "%s: cl_seek(): fseeko(): %s\n", __FILE__, strerror(errno));
    exit(-1);
    }
  l = 0;
  u = ftello(f)/sizeof(ArchFlow);
  rewind(f);
  vlog(LOG_DEBUG, "There are %lu ArchFlow records.\n", u);
  vlog(LOG_DEBUG, "ArchFlow size %d bytes.\n", sizeof(ArchFlow));
  while((u >= l) && (feof(f) == 0)){
    i = (l + u) / 2;
    vlog(LOG_DEBUG, "Setting pointer at %lu position\n", i);
    if(-1 == fseeko(f, i * sizeof(ArchFlow), SEEK_SET)){
      vlog(LOG_ERR, "%s: cl_seek(): fseeko(): %s\n", __FILE__, strerror(errno));
      exit(-1);
      }
    if(feof(f) == 0){
      if(1 != fread(&fi, sizeof(ArchFlow), 1, f)){
        if(feof(f) == 0){
          vlog(LOG_ERR, "%s: cl_seek(): fread(): %s\n", __FILE__, strerror(errno));
          exit(-1);
          }
        }
      }
    if(fi.client_id == key){
      break;
      }
    if(fi.client_id > key){
      u = i - 1;
      }
    else{
      l = i + 1;
      }
    }
  if((u >= l) && (feof(f) == 0)){
    u = i;
    i = l;
    if(-1 == fseeko(f, i * sizeof(ArchFlow), SEEK_SET)){
      vlog(LOG_ERR, "%s: cl_seek(): fseeko(): %s\n", __FILE__, strerror(errno));
      exit(-1);
      }
    do{
      if(-1 == fseeko(f, i * sizeof(ArchFlow), SEEK_SET)){
        vlog(LOG_ERR, "%s: cl_seek(): fseeko(): %s\n", __FILE__, strerror(errno));
        exit(-1);
        }
      if(feof(f) == 0){
        if(1 != fread(&fi, sizeof(ArchFlow), 1, f)){
          if(feof(f) == 0){
            vlog(LOG_ERR, "%s: cl_seek(): fread(): %s\n", __FILE__, strerror(errno));
            exit(-1);
            }
          }
        }
      i++;
      }while(fi.client_id != key);
    if(-1 == fseeko(f, (i - 1) * sizeof(ArchFlow), SEEK_SET)){
      vlog(LOG_ERR, "%s: cl_seek(): fseeko(): %s\n", __FILE__, strerror(errno));
      exit(-1);
      }
    return key;
    }
  return 0;
}

time_t from_days(unsigned long day)
{
  time_t t_buf = 0;
  struct tm ttm;
  
  t_buf = 86400 * ( day - 719528);
  memcpy(&ttm, gmtime(&t_buf), sizeof(struct tm));
  ttm.tm_isdst = -1;
  return mktime(&ttm);
}

unsigned long to_days(time_t clock)
{
  unsigned long days = 0;
  struct tm ttm;

  memcpy(&ttm, localtime(&clock), sizeof(struct tm));
  ttm.tm_isdst = -1;
  days = timegm(&ttm) / 86400 + 719528;
  return days;
}

bool samemonth(unsigned long day1, unsigned long day2)
{
  struct tm ttm1;
  struct tm ttm2;
  time_t clock1 = 0;
  time_t clock2 = 0;
  
  clock1 = from_days(day1);
  clock2 = from_days(day2);

  memcpy(&ttm1, localtime(&clock1), sizeof(struct tm));
  memcpy(&ttm2, localtime(&clock2), sizeof(struct tm));

  return (bool)((ttm1.tm_mon == ttm2.tm_mon) && (ttm1.tm_year == ttm2.tm_year)) ;
}

unsigned mday(unsigned long day)
{
 struct tm ttm;
 time_t clock = 0;
 
 clock = from_days(day);
 memcpy(&ttm, localtime(&clock), sizeof(struct tm));
 return ttm.tm_mday;
}

unsigned numdays(unsigned long day)
{
  time_t clock = 0;
  struct tm ttm;
  unsigned long last_day = 0;

  clock = from_days(day);
  memcpy(&ttm, localtime(&clock), sizeof(struct tm));
  ttm.tm_sec = 0;
  ttm.tm_min = 0;
  ttm.tm_hour = 0;
  ttm.tm_mday = 1;
  ttm.tm_mon = (ttm.tm_mon == 11) ? 0 : ttm.tm_mon + 1;
  ttm.tm_year = (ttm.tm_mon == 0) ? ttm.tm_year + 1 : ttm.tm_year;
  ttm.tm_isdst = -1;
  last_day = to_days(mktime(&ttm)) - 1;
  return mday(last_day);
}
  

namespace charge {
void Parser::error(const location_type& loc, const std::string& msg){
  throw std::runtime_error(msg);
  };


// DEFINITION OF class FILTER_DEFINITION ;-)
//

FILTER_DEFINITION::FILTER_DEFINITION(mysqlpp::Connection& con, unsigned long i)
{
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  
  id = i;
  query << "SELECT * FROM filter_definition WHERE id = " << i;
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  if((i_res = res.begin()) == res.end()){
    vlog(LOG_ERR, "There is no filter definition with id = %lu", i);
    }
  row = *i_res;
  timerange = (string)row["timerange"];
  proto = (unsigned char)row["proto"];
  in_if = (unsigned int)row["in_if"];
  out_if = (unsigned int)row["out_if"];
  src_addr = (unsigned long)row["src_addr"];
  src_mask = (unsigned long)row["src_mask"];
  dst_addr = (unsigned long)row["dst_addr"];
  dst_mask = (unsigned long)row["dst_mask"];
  src_port = (unsigned int)row["src_port"];
  dst_port = (unsigned int)row["dst_port"];
  src_as = (unsigned int)row["src_as"];
  dst_as = (unsigned int)row["dst_as"];
  
  query = con.query();
  query << "SELECT "
          << " UNIX_TIMESTAMP(starttimestamp) AS starttimestamp, "
          << " UNIX_TIMESTAMP(stoptimestamp) AS stoptimestamp "
          << " FROM filter WHERE id = " << (unsigned long)row["filter_id"];
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  if((i_res = res.begin()) == res.end()){
    vlog(LOG_ERR, "There is no filter with id = %lu", (unsigned long)row["filter_id"]);
    }
  row = *i_res;
  starttimestamp = (time_t)row["starttimestamp"];
  stoptimestamp = (time_t)row["stoptimestamp"];
}

bool FILTER_DEFINITION::match_flow(Flow& flow)
{
  // If flow is out of range of filter's validity
  // return false
  if(starttimestamp != 0){
    if(flow.timestamp < starttimestamp){
      return false;
      }
    }
  if(stoptimestamp != 0){
    if(flow.timestamp > stoptimestamp){
      return false;
      }
    }
  // If all attributes are zero, return false
  if(timerange.empty()
                  && proto == 0
                  && in_if == 0
                  && out_if == 0
                  && src_addr == 0
                  && src_mask == 0
                  && dst_addr == 0
                  && dst_mask == 0
                  && src_port == 0
                  && dst_port == 0
                  && src_as == 0
                  && dst_as == 0){
    return false;
    }
  // if attribute isn't equal zero
  // and doesn't match, return false
  if(!timerange.empty()){
    if(timestr_match(flow.timestamp) < 0){
      return false;
      }
    }
  if(proto != 0){
    if(proto != flow.protocol){
      return false;
      }
    }
  if(in_if != 0){
    if(in_if != flow.in_if_id){
      return false;
      }
    }
  if(out_if != 0){
    if(out_if != flow.out_if_id){
      return false;
      }
    }
  if(src_addr != 0 || src_mask != 0){
    if(src_addr != (flow.src_addr & src_mask)){
      return false;
      }
    }
  if(dst_addr != 0 || dst_mask != 0){
    if(dst_addr != (flow.dst_addr & dst_mask)){
      return false;
      }
    }
  if(src_port != 0){
    if(src_port != flow.src_port){
      return false;
      }
    }
  if(dst_port != 0){
    if(dst_port != flow.dst_port){
      return false;
      }
    }
  if(src_as != 0){
    if(src_as != flow.src_as){
      return false;
      }
    }
  if(dst_as != 0){
    if(dst_as != flow.dst_as){
      return false;
      }
    }
  // Otherwise, return true.
  return true;
}

int FILTER_DEFINITION::show()
{
  return fprintf(stderr, "%10s%4u%4u%4u%11lu%11lu%11lu%11lu%6u%6u%6u%6u\n",
    timerange.c_str(),
    proto,
    in_if,
    out_if,
    src_addr,
    src_mask,
    dst_addr,
    dst_mask,
    src_port,
    dst_port,
    src_as,
    dst_as);
}

/*
 *  String code.
 */
static int strcode(const char **str)
{
  int i;
  size_t l;

  vlog(LOG_DEBUG, "strcode %s called\n", *str);

  for (i = 0; i < 10; i++) {
    l = strlen(days[i]);
    if (l > strlen(*str))
      continue;
    if (strncmp(*str, days[i], l) == 0) {
      *str += l;
      break;
    }
  }
  vlog(LOG_DEBUG, "strcode result %d\n", i);

  return (i >= 10) ? -1 : i;

}

/*
 *  Fill bitmap with hours/mins.
 */
static int hour_fill(char *bitmap, const char *tm)
{
  char *p;
  int start, end;
  int i, bit, byte;

  vlog(LOG_DEBUG, "hour_fill called for %s\n", tm);

  /*
   *  Get timerange in start and end.
   */
  end = -1;
  if ((p = strchr(tm, '-')) != NULL) {
    p++;
    if (p - tm != 5 || strlen(p) < 4 || !isdigit((int) *p))
      return 0;
    end = 600 * val(p[0]) + 60 * val(p[1]) + atoi(p + 2);
  }
  if (*tm == 0) {
    start = 0;
    end = DAYMIN - 1;
  } else {
    if (strlen(tm) < 4 || !isdigit((int) *tm))
      return 0;
    start = 600 * val(tm[0]) + 60 * val(tm[1]) + atoi(tm + 2);
    if (end < 0) end = start;
  }
  /* Treat 2400 as 0000, and do some more silent error checks. */
  if (end < 0) end = 0;
  if (start < 0) start = 0;
  if (end >= DAYMIN) end = DAYMIN - 1;
  if (start >= DAYMIN) start = DAYMIN - 1;

  vlog(LOG_DEBUG, "hour_fill: range from %d to %d\n", start, end);

  /*
   *  Fill bitmap.
   */
  i = start;
  while (1) {
    byte = (i / 8);
    bit = i % 8;
    vlog(LOG_DEBUG, "setting byte %d, bit %d\n", byte, bit);
    bitmap[byte] |= (1 << bit);
    if (i == end) break;
    i++;
    i %= DAYMIN;
  }
  return 1;
}

/*
 *  Call the fill bitmap function for every day listed.
 */
static int day_fill(char *bitmap, const char *tm)
{
  const char *hr;
  int n;
  int start, end;

  for (hr = tm; *hr; hr++)
    if (isdigit((int) *hr))
      break;
  if (hr == tm) 
    tm = "Al";

  vlog(LOG_DEBUG, "dayfill: hr %s    tm %s\n", hr, tm);

  while ((start = strcode(&tm)) >= 0) {
    /*
     *  Find start and end weekdays and
     *  build a valid range 0 - 6.
     */
    if (*tm == '-') {
      tm++;
      if ((end = strcode(&tm)) < 0)
        break;
    } else
      end = start;
    if (start == 7) {
      start = 1;
      end = 5;
    }
    if (start > 7) {
      start = 0;
      end = 6;
    }
    n = start;
    vlog(LOG_DEBUG, "day_fill: range from %d to %d\n", start, end);
    while (1) {
      hour_fill(bitmap + 180 * n, hr);
      if (n == end) break;
      n++;
      n %= 7;
    }
  }

  return 1;
}

/*
 *  Fill the week bitmap with allowed times.
 */
static int week_fill(char *bitmap, const char *tm)
{
  char *s;
  char tmp[128];

  strncpy(tmp, tm, 128);
  tmp[127] = 0;
  for (s = tmp; *s; s++)
    if (isupper(*s)) *s = tolower(*s);
  
  s = strtok(tmp, ",|");
  while (s) {
    day_fill(bitmap, s);
    s = strtok(NULL, ",|");
  }

  return 0;
}

/*
 *  Match a timestring and return seconds left.
 *  -1 for no match, 0 for unlimited.
 */
int FILTER_DEFINITION::timestr_match(time_t t)
{
  struct tm *tm, s_tm;
  char bitmap[WEEKMIN / 8];
  int now, tot, i;
  int byte, bit;
// For time str debug
  int y;
  char *s;
  char null[8];
// end of "for time str debug"

  tm = localtime_r(&t, &s_tm);
  now = tm->tm_wday * DAYMIN + tm->tm_hour * 60 + tm->tm_min;
  tot = 0;
  memset(bitmap, 0, sizeof(bitmap));
  week_fill(bitmap, timerange.c_str());

  if(netstore_debug){
    memset(null, 0, 8);
    for (i = 0; i < 7; i++) {
      vlog(LOG_DEBUG, "%d: ", i);
      s = bitmap + 180 * i;
      for (y = 0; y < 23; y++) {
        s = bitmap + 180 * i + (75 * y) / 10;
        vlog(LOG_DEBUG, "%c", memcmp(s, null, 8) == 0 ? '.' : '#');
       }
    vlog(LOG_DEBUG, "\n");
    }
  }

  /*
   *  See how many minutes we have.
   */
  i = now;
  while (1) {
    byte = i / 8;
    bit = i % 8;
    vlog(LOG_DEBUG, "READ: checking byte %d bit %d\n", byte, bit);
    if (!(bitmap[byte] & (1 << bit)))
      break;
    tot += 60;
    i++;
    i %= WEEKMIN;
    if (i == now)
      break;
  }

  if (tot == 0) 
    return -1;

  return (i == now) ? 0 : tot;
}

// DEFINITION OF class FILTER ;-)
//
FILTER::FILTER(mysqlpp::Connection& con, unsigned long i)
{
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  
  id = i;
  query << "SELECT inverse FROM filter WHERE id = " << i;
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  i_res = res.begin();
  if(i_res != res.end()){
    row = *i_res;
    inverse = (int)row["inverse"];
    }
  else{
    inverse = 0;
    vlog(LOG_ERR, "It seems there isn't filter with id %lu\n", i);
    }
  vlog(LOG_DEBUG, "inverse: %d\n", inverse);
  query << "SELECT id FROM filter_definition WHERE filter_id = " << i;
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  lf_def.clear();
  for(i_res = res.begin(); i_res != res.end(); i_res++){
    row = *i_res;
    lf_def.push_back(FILTER_DEFINITION(con, (unsigned long)row["id"]));
    }
}

FILTER::FILTER(const FILTER& rhs)
{
  this->id = rhs.id;
  this->inverse = rhs.inverse;
  this->lf_def = rhs.lf_def;
}

bool FILTER::satisfy(Flow& flow)
{
  list<FILTER_DEFINITION>::iterator i;
  bool result = false;
  
  for(i = lf_def.begin(); i != lf_def.end(); i++){
    if(i->match_flow(flow) == true){
      result = true;
      }
    }
  vlog(LOG_DEBUG, "filter id# %lu:\n", id);
  vlog(LOG_DEBUG, "match to conditions: %d\n", result);
  vlog(LOG_DEBUG, "inverse: %d\n", inverse);
  result = (!result && inverse) || (result && !inverse);
  return result;
}

int FILTER::show()
{
  list<FILTER_DEFINITION>::iterator i;
  fprintf(stderr, "Beginning Of the Filter# %lu:\n", id);
  for(i = lf_def.begin(); i != lf_def.end(); i++){
    i->show();
    }
  fprintf(stderr, "End Of the Filter# %lu:\n", id);
  return 0;
}
unsigned long long FILTER::get_traffic(mysqlpp::Connection& con, 
                unsigned long actual_day = 0, 
                direction dir = any)
{
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  string dir_traffic;
  
  if(actual_day == 0){
    return 0;
    }
  if(dir == in){
    dir_traffic = "incoming";
    }
  if(dir == out){
    dir_traffic = "outcoming";
    }
  if(dir == any){
    dir_traffic = "incoming + outcoming";
    }
  query << "SELECT IFNULL(SUM(" << dir_traffic << "), 0) AS traffic "
          << " FROM filter_counter_snapshot "
          << " WHERE filter_id = " << id
          << " AND TO_DAYS(timestamp) <= " << actual_day
          << " AND YEAR(timestamp) = YEAR(FROM_DAYS(" << actual_day << "))"
          << " AND MONTH(timestamp) = MONTH(FROM_DAYS(" << actual_day << "))";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  if((i_res = res.begin()) != res.end()){
    row = *i_res;
    return row["traffic"];
    }
  else{
    return 0;
    }
}

// DEFINITION OF class CLIENT
//
CLIENT::CLIENT(mysqlpp::Connection& con, unsigned long i)
{
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  unsigned long filter_id = 0;
  
  id = i;
  query << "SELECT id FROM filter WHERE client_id = " << i;
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  map_filters.clear();
  for(i_res = res.begin(); i_res != res.end(); i_res++){
    row = *i_res;
    filter_id = (unsigned long)row["id"];
    map_filters[filter_id] = FILTER(con, filter_id);
    }
  query = con.query();
  query << "SELECT tac_status, description FROM client WHERE id = " << id;
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  i_res = res.begin();
  tac_status = true;
  if(i_res != res.end()){
    row = *i_res;
    description = (string)row["description"];
    if((string)row["tac_status"] == "y"){
      tac_status = true;
      query = con.query();
      query << "SELECT `value` FROM `config` WHERE `attribute` = 'tac'";
      vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      res = query.store();
      i_res = res.begin();
      if(i_res != res.end()){
        row = *i_res;
        tac_rate = (double)row["value"];
        }
      else{
        tac_rate = 0.2;
        }
      }
    else{
      tac_status = false;
      tac_rate = 0;
      }
    }
}

unsigned long long CLIENT::get_traffic(mysqlpp::Connection& con, 
                unsigned long actual_day = 0, 
                direction dir = any)
{
  mysqlpp::Query query = con.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  string dir_traffic;

  if(actual_day == 0){
    return 0;
    }
  if(dir == in){
    dir_traffic = "incoming";
    }
  if(dir == out){
    dir_traffic = "outcoming";
    }
  if(dir == any){
    dir_traffic = "incoming + outcoming";
    }
  query << "SELECT IFNULL(SUM(" << dir_traffic << "), 0) AS traffic "
          << " FROM traffic_snapshot "
          << " WHERE client_id = " << id
          << " AND TO_DAYS(timestamp) <= " << actual_day
          << " AND YEAR(timestamp) = YEAR(FROM_DAYS(" << actual_day << "))"
          << " AND MONTH(timestamp) = MONTH(FROM_DAYS(" << actual_day << "))";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  if((i_res = res.begin()) != res.end()){
    row = *i_res;
    return row["traffic"];
    }
  else{
    return 0;
    }
}

double CLIENT::get_tac_rate()
{
  return tac_rate;
}

// DEFINITION OF class SERVICE
//
SERVICE::SERVICE(mysqlpp::Connection& con, unsigned long i)
{
  mysqlpp::Query query = connection.query();
  mysqlpp::Result res;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row;
  
  connection = con;
  id = i;
  query << "SELECT \
          service.id AS id,\
          service.service_type_id AS type_id,\
          IFNULL(TO_DAYS(service.start_time),0) AS start_time,\
          IFNULL(TO_DAYS(service.expire_time),0) AS expire_time,\
          tariff.tariff AS tariff,\
          tariff.monthlypayment AS monthly,\
          tariff.main_currency AS main_currency,\
          service.cash AS cash, \
          contract.client_id AS client_id\
          FROM service\
          LEFT JOIN tariff ON tariff.id = service.tariff_id\
          LEFT JOIN contract ON contract.id = service.contract_id\
          WHERE service.id = " << i;
  query << " LOCK IN SHARE MODE";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  i_res = res.begin();
  row = *i_res;
  type_id = row["type_id"];
  tariff = (string)row["tariff"];
  start_time = row["start_time"];
  expire_time = row["expire_time"];
  if((string)row["monthly"] == "yes"){
    monthly = true;
    }
  else{
    monthly = false;
    }
  if((string)row["cash"] == "yes"){
    cash = true;
    }
  else{
    cash = false;
    }
  if((string)row["main_currency"] == "yes"){
    main_currency = true;
    }
  else{
    main_currency = false;
    }
  
  client = CLIENT(connection, row["client_id"]);
  query = connection.query();
  query << "SELECT TO_DAYS(`date`) AS `date`, `rate` FROM `rate` LOCK IN SHARE MODE";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  for(i_res = res.begin(); i_res != res.end(); i_res++){
    row = *i_res;
    rate_reference[(unsigned long)row["date"]] = (double)row["rate"];
    }          
}


int SERVICE::update_charges()
{
  mysqlpp::Query query = connection.query();
  mysqlpp::Result res, r;
  mysqlpp::Result::iterator i_res;
  mysqlpp::Row row, rw;
  unsigned long first_day = 0;
  unsigned long last_day = 0;
  unsigned long store_day = 0; // День, с которого записывать начисления в базу.
  unsigned long i_day = 0;
  double i_stored_price = 0.0;
  double i_prev_price = 0.0;
  double i_curr_price = 0.0;
  
  // Service MUST has its start time.
  if(start_time == 0){
    vlog(LOG_ERR, "Every service must have not zero start date. But service #%lu has\n", id);
    exit(1);
    }
  // Удаляем начисления, которые возможно присутствуют после даты закрытия сервиса.
  if(get_expire_time() != 0){
    query << "DELETE FROM charge WHERE service_id = " << id  << " AND TO_DAYS(timestamp) >= " << get_expire_time();
    vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
    query.execute();
    }
  // Определяем первый день подсчета денег
  // Определяем последнюю дату начислений по сервису
  query = connection.query();
  query << "SELECT TO_DAYS(MAX(timestamp)) + 1 as first_day"
        << " FROM charge"
        << " WHERE service_id = '" << id << "'"
        << " HAVING first_day IS NOT NULL";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  res = query.store();
  i_res = res.begin();
  if(res.begin() != res.end()){
    row = *i_res;
    first_day = (unsigned long)row["first_day"];
    }
  else{
    // Если начислений по сервису еще не было, то начислять надо с первого для действия сервиса
    vlog(LOG_INFO, "Service #%lu has no previous charges\n", id);
    first_day = start_time;
    }
  // Определяем последний день подсчета денег
  // Это либо текущая дата, либо первое число следуещего месяца, если тариф ежемесячный.
  // Если же известна дата окончания действия тарифа, то последний день подсчета - это дата окончания действия тарифа
  if(expire_time != 0){
    if(monthly){
      // Если тариф с предоплатой, то последний день - день окнчания услуги
      last_day = expire_time;
      }
    else{
      // Если услуга без предоплаты, то возможны два варианта:
      // 1. Текущая дата меньше, чем дата окончания услуги. Тогда последний день - текущая дата.
      // 2. Текущая дата больше, чем дата окончания услуги. Тогда последний день - дата окончания услуги
      last_day = (to_days(time(NULL)) < expire_time) ? to_days(time(NULL)) : expire_time;
      }
    }
  else{
    if(monthly){
      // Если за услугу берется предоплата.
      // то конец периода для начислений -
      // это первое число следующего месяца. 
      time_t now = time(NULL);
      struct tm ttm;
      memcpy(&ttm, localtime(&now), sizeof(struct tm));
      ttm.tm_sec = 0;
      ttm.tm_min = 0;
      ttm.tm_hour = 0;
      ttm.tm_mday = 1;
      ttm.tm_mon = (ttm.tm_mon == 11) ? 0 : ttm.tm_mon + 1;
      ttm.tm_year = (ttm.tm_mon == 0) ? ttm.tm_year + 1 : ttm.tm_year;
      ttm.tm_isdst = -1;
      last_day = to_days(mktime(&ttm)); // первое число сл. месяца
      }
    else{
      last_day = to_days(time(NULL)); // текущая дата
      }
    }

  // Calculate charges and store in mysql
  // Считаем деньги
  list<double> ratios;
  double ratio = 0;
    
  i_day = first_day;
  vlog(LOG_DEBUG, "update_charges(): start %lu, stop %lu\n", first_day, last_day);
  
  while(i_day < last_day){
    // Fill ratios for all days of a month
    unsigned long shift = i_day;
    // Определяем уже начисленную сумму в текущем месяце
    query = connection.query();
    query << "SELECT IFNULL(SUM(value_without_vat), 0) AS current_price FROM `charge` WHERE service_id = '" << id << "'"
        << " AND YEAR(timestamp) = YEAR(FROM_DAYS('" << i_day << "'))"
        << " AND MONTH(timestamp) = MONTH(FROM_DAYS('" << i_day << "'))";
    vlog(LOG_DEBUG, "Executing query: %s\n", query.preview().c_str());
    res = query.store();
    i_res = res.begin();
    row = *i_res;
    i_stored_price = row["current_price"];
    // Считаем коефициенты начислений
    do{
      actual_day = i_day;
      i_curr_price = get_price();
      ratio = i_curr_price - i_prev_price;
      vlog(LOG_DEBUG, "ratio(%lu) = %f\n", i_day, ratio);
      ratios.push_back(ratio);
      i_prev_price = i_curr_price;
      i_day++;
      if(i_day >= last_day){
        break;
        }
      } while(samemonth(i_day, i_day - 1)); // if i_day is the first day of a month, finish loop
    i_prev_price = 0;
    MONEY mon_price_without_vat(i_curr_price - i_stored_price);
    MONEY mon_price_with_vat;
    MONEY mon_vat;
    mon_price_with_vat = (!cash) ? mon_price_without_vat * (client.get_tac_rate() + 1) : mon_price_without_vat;
    mon_vat = mon_price_with_vat - mon_price_without_vat;
/*
      vlog(LOG_DEBUG, "Price of the service without VAT in double: %f\n", i_curr_price);
      cerr << "Price of the service without VAT : " << mon_price_without_vat << endl;
      cerr << "VAT : " << mon_vat << endl;
      cerr << "Price of the service with VAT : " << mon_price_with_vat << endl;
*/
    list<MONEY> daily_price_with_vat = mon_price_with_vat.allocate(ratios);
    list<MONEY> daily_price_without_vat = mon_price_without_vat.allocate(ratios);
    list<MONEY> daily_vat = mon_vat.allocate(ratios);
    list<MONEY>::iterator iprice_with_vat = daily_price_with_vat.begin();
    list<MONEY>::iterator iprice_without_vat = daily_price_without_vat.begin();
    list<MONEY>::iterator ivat = daily_vat.begin();
    while(iprice_with_vat != daily_price_with_vat.end()){ 
      query = connection.query();
      query << "INSERT INTO `charge`(`service_id`, `timestamp`, `value`, `value_without_vat`, `vat`)"
              << " VALUES(" << id << "," 
              << " FROM_DAYS(" << shift++ << "), '"
              << *iprice_with_vat << "',  '" << *iprice_without_vat<< "', '" << *ivat << "')";
      if(shift > store_day){
        vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
        query.execute();  
        }
      iprice_with_vat++;
      iprice_without_vat++;
      ivat++;
      }
    ratios.clear();
    }
  vlog(LOG_DEBUG, "update_charges(): Finished\n");
  return 0;
}

int SERVICE::purge_charges()
{
  // delete old charges
  mysqlpp::Query query = connection.query();
  query << "DELETE FROM charge WHERE service_id = '" << id << "'";
  vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
  query.execute();
  return 0;
}

double SERVICE::get_price()
{
  double rate = 0.0;
    
  price = 0.0;

  vlog(LOG_DEBUG, "Parsing tarif %s\n", tariff.c_str());
  istringstream tstream(tariff);
  charge::Lexer tariff_lexer(&tstream);
  charge::Parser tariff_parser(tariff_lexer, *this);
  if(0 != tariff_parser.parse()){
    vlog(LOG_ERR, "\nError while parsing tariff:\n\n%s\n\nfor service id %lu client(%lu): %s\n",
        tariff.c_str(),
        id,
        client.get_id(),
        client.get_description().c_str());
    exit(-1);
    }
  if(!main_currency){
    if(monthly){
      // Определяем курс валюты на первое число текущего месяца
      mysqlpp::Query query = connection.query();
      query << "SELECT " << actual_day << " - DAYOFMONTH(FROM_DAYS(" 
          << actual_day << ")) + 1 AS mbegin";
      vlog(LOG_DEBUG, "Query: %s\n", query.preview().c_str());
      mysqlpp::Result res = query.store();
      mysqlpp::Result::iterator i = res.begin();
      mysqlpp::Row row = *i;
      rate = ((unsigned long)row["mbegin"] < start_time) ? rate_reference[start_time] : rate_reference[(unsigned long)row["mbegin"]]; 
      vlog(LOG_DEBUG, "Currency rate is %f on %lu-th day\n", rate, (unsigned long)row["mbegin"]);
      }
    else{
      rate = rate_reference[actual_day];
      vlog(LOG_DEBUG, "Currency rate is %f on %lu-th day\n", rate, actual_day);
      }
    if(rate == 0){
      mysqlpp::Query query = connection.query();
      query << "SELECT FROM_DAYS('" << actual_day << "') AS actual_day";
      mysqlpp::Result res = query.store();
      mysqlpp::Result::iterator i = res.begin();
      mysqlpp::Row row = *i;
      vlog(LOG_ERR, "Rate for '%s' is not set\n", (const char*)row["actual_day"]);
      exit(1);
      }
    price = rate*price;
    }
  return price;
}

unsigned long long SERVICE::traffic(unsigned long id)
{
  unsigned long long result = 0;
  if(id == client.get_id()){
    result = client.get_traffic(connection, actual_day, any);
    }
  else{
    CLIENT* cl = new CLIENT(connection, id);
    result = cl->get_traffic(connection, actual_day, any);
    }
  return result;
}
unsigned long long SERVICE::traffic_in(unsigned long id)
{
  unsigned long long result = 0;
  if(id == client.get_id()){
    result = client.get_traffic(connection, actual_day, in);
    }
  else{
    CLIENT* cl = new CLIENT(connection, id);
    result = cl->get_traffic(connection, actual_day, in);
    }
  return result;
}
unsigned long long SERVICE::traffic_out(unsigned long id)
{
  unsigned long long result = 0;
  if(id == client.get_id()){
    result = client.get_traffic(connection, actual_day, out);
    }
  else{
    CLIENT* cl = new CLIENT(connection, id);
    result = cl->get_traffic(connection, actual_day, out);
    }
  return result;
}

unsigned long long SERVICE::filter(unsigned long id)
{
  unsigned long long result = 0;
  if(client.map_filters.find(id) != client.map_filters.end()){
    result = client.map_filters[id].get_traffic(connection, actual_day, any);
    }
  else{
    FILTER* fl = new FILTER(connection, id);
    result = fl->get_traffic(connection, actual_day, any);
    }
  return result;
}
unsigned long long SERVICE::filter_in(unsigned long id)
{
  unsigned long long result = 0;
  if(client.map_filters.find(id) != client.map_filters.end()){
    result = client.map_filters[id].get_traffic(connection, actual_day, in);
    }
  else{
    FILTER* fl = new FILTER(connection, id);
    result = fl->get_traffic(connection, actual_day, in);
    }
  return result;
}
unsigned long long SERVICE::filter_out(unsigned long id)
{
  unsigned long long result = 0;
  if(client.map_filters.find(id) != client.map_filters.end()){
    result = client.map_filters[id].get_traffic(connection, actual_day, out);
    }
  else{
    FILTER* fl = new FILTER(connection, id);
    result = fl->get_traffic(connection, actual_day, out);
    }
  return result;
}

double SERVICE::rel_time(unsigned long id = 0)
{
  double result = 0;
  if(samemonth(actual_day, start_time)){
    result = (double)(actual_day - start_time + 1)/(double)numdays(actual_day);
    }
  else{
    result = (double)mday(actual_day)/(double)numdays(actual_day);
    }
  vlog(LOG_DEBUG, "rel_time(0) = %f\n", result);
  return result;
}


//////////////////////////////////////////////
//
//
// MONEY implementation
//
//
//////////////////////////////////////////////

// Constructors

MONEY::MONEY()
{
  value = 0;
}
  
MONEY::MONEY(const double val)
{
  value = (long long)round0((round2(val) * 100));
}

MONEY::MONEY(const long long h, const unsigned int l)
{
  value = (h >= 0) ? 100 * h + l : 100 * h - l;
}

MONEY::MONEY(const long long val)
{
  value = val;
}

MONEY::MONEY(const long val)
{
  value = val;
}

MONEY::MONEY(const int val)
{
  value = val;
}

MONEY& MONEY::operator=(const MONEY& m)
{
  value = m.value;
  return *this;
}

//
// Returns major part
//
long long MONEY::high() const
{
  return (value > 0) ? (long long)round0(floor(value / 100.00)) : (long long)round0(ceil(value / 100.00));
}

// 
// Returns minor part
unsigned int MONEY::low() const
{
  return (value > 0) ? value % 100 : - value % 100;
}

//
// Output operator
// 
ostream& operator<<(ostream& os, const MONEY& m)
{
  os << m.high() << ".";  
  os.fill('0');
  os.width(2);
  os << m.low();
  
  return os;
}

//
// Mathematically rounds up to 2 digits after comma
// 
// i.e.
// round2(3.5) = 4
// round2(4.5) = 5
//
// in contrast to 
// rint(3.5) = 4
// rint(4.5) = 4
double MONEY::round2(const double x) const
{
  return round0(x*100) / 100;
}

//
// Mathematically rounds up to 0 digits after comma
// 
double MONEY::round0(const double x) const
{
  bool flag = false;
  long long c = 0;
  double res = 0.0;
  double buffer = 0.0;
  
  buffer = x;
  if(x < 0){
    buffer = -x;
    flag = true;
    }
  c = (long long)floor(buffer);
  res = c - 1 + rint(buffer - c + 1);
  if(flag){
    res = -res;
    }
  return res;
}

long long MONEY::llabs(const long long x) const
{
  return (x >= 0) ? x : -x;
}

MONEY MONEY::multiply(const double val)
{
  return MONEY(round2(value * val/100));
}

MONEY MONEY::divide(const double val)
{
  return MONEY(round2(value / val / 100.00));
}

MONEY MONEY::add(const MONEY& val)
{
  return MONEY(value + val.value);
}

MONEY MONEY::subtract(const MONEY& val)
{
  return MONEY(value - val.value);
}
//
// Overloaded arithmetics for MONEY
// 
MONEY operator*(const MONEY& m, const double a)
{
  return MONEY(m.round2((a * m.value) / 100));
}

MONEY operator*(const double a, const MONEY& m)
{
  return MONEY(m.round2((a * m.value) / 100));
}

MONEY operator/(const MONEY& m, const double a)
{
  return MONEY(m.round2(m.value / a / 100));
}

MONEY operator+(const MONEY& a, const MONEY& b)
{
  return MONEY(a.value + b.value);
}

MONEY operator-(const MONEY& a, const MONEY& b)
{
  return MONEY(a.value - b.value);
}

bool operator==(const MONEY& a, const MONEY& b)
{
  return a.value == b.value;
}

bool operator!=(const MONEY& a, const MONEY& b)
{
  return a.value != b.value;
}

bool operator>(const MONEY& a, const MONEY& b)
{
  return a.value > b.value;
}

bool operator>=(const MONEY& a, const MONEY& b)
{
  return a.value >= b.value;
}

bool operator<(const MONEY& a, const MONEY& b)
{
  return a.value < b.value;
}

bool operator<=(const MONEY& a, const MONEY& b)
{
  return a.value <= b.value;
}

// 
// Division on n equal parts
//
list<MONEY> MONEY::share(const unsigned long n) const
{
  long long remainder = 0;
  MONEY lowresult(value/n);  
  MONEY highresult(0);
  list<MONEY> result;
  
  if(lowresult >= 0){
    highresult = lowresult + 0.01;
    }
  else{
    highresult = lowresult - 0.01;
    }
  remainder = llabs(value % n);
  for(long long i = 0; i < remainder; i++){
    result.push_back(highresult);
    }
  for(unsigned long long i = remainder; i < n; i++){
    result.push_back(lowresult);
    }
  return result;
  };


    
//
// Division to respective coefficients
//

list<MONEY> MONEY::allocate(list<double>& ratios)
{
  double total = 0.0;
  long long remainder = 0;
  long long i;
  list<double>::iterator ratio;
  list<MONEY>::iterator r;
  list<MONEY> result;
  
  remainder = value;
  
  for(ratio = ratios.begin(); ratio != ratios.end(); ratio++){
    total += *ratio;
    }
  
  for(ratio = ratios.begin(); ratio != ratios.end(); ratio++){
    if(fabs(total) > DBL_EPSILON){
      result.push_back(MONEY((long long)round0((value * (*ratio) / total))));
      }
    else{
      result.push_back(MONEY(0));  
      }
    remainder -= result.back().value;
    }
  
  if(remainder > 0){
    for(r = result.begin(), i = 0; i < remainder; r++, i++){
      *r = *r + MONEY(0,1);
      }
    }
  else{
    for(r = result.begin(), i = 0; i < labs(remainder); r++, i++){
      *r = *r - MONEY(0,1);
      }
    }
  return result;
}
}

int vlog(int lvl, const char* fmt, ...)
{
  va_list ap;
  int r = 0;
  va_start(ap, fmt);
  if((netstore_debug == true && lvl == LOG_DEBUG ) || lvl == LOG_ERR){
    vfprintf(stderr, fmt, ap);
    }
  if(lvl != LOG_DEBUG){
    // send to syslog everything except DEBUG
    vsyslog(lvl, fmt, ap);
    }
  va_end(ap);
  return r;
}
