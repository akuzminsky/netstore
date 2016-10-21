/* $Id: classes.h,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#ifndef __CLASSES_H
#define __CLASSES_H

#include "common.h"

#ifndef __FLEX_LEXER_H
#undef yyFlexLexer
#include <FlexLexer.h>
#endif

namespace charge {
class FILTER_DEFINITION{
    public:
      FILTER_DEFINITION(mysqlpp::Connection&, unsigned long);
      ~FILTER_DEFINITION(){};
      bool match_flow(Flow&);
      int show();
    private:
      unsigned long id;
      int timestr_match(time_t);
      string timerange;
      time_t starttimestamp;
      time_t stoptimestamp;
      unsigned char proto;
      unsigned int in_if;
      unsigned int out_if;
      unsigned long src_addr;
      unsigned long src_mask;
      unsigned long dst_addr;
      unsigned long dst_mask;
      unsigned int src_port;
      unsigned int dst_port;
      unsigned int src_as;
      unsigned int dst_as;
    };

class FILTER{
    public:
      FILTER(){ id = 0; };
      FILTER(mysqlpp::Connection&, unsigned long);
      ~FILTER(){};
      FILTER(const FILTER&);
      bool satisfy(Flow&);
      int show();
      unsigned long long get_traffic(mysqlpp::Connection&, unsigned long, direction);
      unsigned long get_id() { return id; };
    private:
      unsigned long id;
      int inverse;
      list<FILTER_DEFINITION> lf_def;
    };
  
  class CLIENT{
    public:
      CLIENT(){ id = 0; };
      CLIENT(mysqlpp::Connection&, unsigned long);
      ~CLIENT(){};
      unsigned long long get_traffic(mysqlpp::Connection&, unsigned long, direction);
      double get_tac_rate();
      unsigned long get_id() { return id; };
      string get_description() {return description; };
      map<unsigned long, FILTER> map_filters;
    private:
      unsigned long id;
      string description;
      bool tac_status;
      double tac_rate;
    };

class SERVICE{
    public:
      SERVICE(){
        id = 0;
        type_id = 0;
        tariff = "";
        start_time = 0;
        expire_time = 0;
        };
      SERVICE(mysqlpp::Connection&, unsigned long);
      ~SERVICE(){};
      int update_charges();
      int purge_charges();
      double get_price();
      double set_price(double p) { return price = p; };
      unsigned long long traffic(unsigned long);
      unsigned long long traffic_in(unsigned long);
      unsigned long long traffic_out(unsigned long);
      unsigned long long filter(unsigned long);
      unsigned long long filter_in(unsigned long);
      unsigned long long filter_out(unsigned long);
      double rel_time(unsigned long);
      unsigned long get_start_time() { return start_time; };
      unsigned long get_expire_time() { return expire_time; };
    private:
      string tariff;
      bool monthly;
      bool main_currency;
      unsigned long start_time;
      unsigned long expire_time;
      CLIENT client;
      double price;
      unsigned long id;
      unsigned long type_id;
      unsigned long actual_day;
      map <unsigned long, double> rate_reference;
      mysqlpp::Connection connection;
      bool cash;
    };


// Class Money
//

class MONEY{
  // Constructors
  public:
    MONEY();
    MONEY(const double);
    MONEY(const long long, const unsigned int);
  //
  // Assignment constructor
  // 
    MONEY& operator=(const MONEY&);
  private:  
    MONEY(const long long val);
    MONEY(const long val);
    MONEY(const int val);
  private:
    // Major part
    long long high() const;
    // Minor part
    unsigned int low() const;
    
    // 
    // Operations on the MONEY
    // 
  public:  
    // Multiply
    MONEY multiply(const double);
    
    friend MONEY operator*(const MONEY&, const double);
    friend MONEY operator*(const double, const MONEY&);
    
    
    // Division
    MONEY divide(const double);
    
    friend MONEY operator/(const MONEY&, const double);
    
    
    // Addition
    MONEY add(const MONEY&);
    
    friend MONEY operator+(const MONEY&, const MONEY&);
    
    
    // Subsraction
    MONEY subtract(const MONEY&);
    
    friend MONEY operator-(const MONEY&, const MONEY&);
    
    
    // Logical operators on the MONEY
    friend bool operator==(const MONEY&, const MONEY&);
    friend bool operator!=(const MONEY&, const MONEY&);
    friend bool operator>(const MONEY&, const MONEY&);
    friend bool operator>=(const MONEY&, const MONEY&);
    friend bool operator<(const MONEY&, const MONEY&);
    friend bool operator<=(const MONEY&, const MONEY&);
    
    
    // Output the MONEY in a stream
    friend ostream& operator<<(ostream&, const MONEY&);
    
    
    // Sharing in equal parts
    list<MONEY> share(const unsigned long) const;
    
    
    // Division respectively to ratios
    list<MONEY> allocate(list<double>&);
    
    
    private:
    long long value;
    double round2(const double) const;
    double round0(const double) const;
    long long llabs(const long long) const;
};

}
#endif
