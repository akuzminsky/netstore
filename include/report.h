/* $Id: report.h,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#ifndef _report_h
#define _report_h

#include "common.h"

using namespace mysqlpp;

class client{
  public:
    client(Connection& cn, int cl_id);
    ~client(){};
  private:
    Connection& con;
    int id;
};

typedef enum rpt_type{daily, hourly, flows, current} report_type;

class report{
  public:
    report(Connection& cn, int report_id);
    ~report(){};

    int get_client_id();
    int set_client_id(int);
    report_type get_type();
    void show_from();
    void show_to();
    string client_description;
  protected:
    Connection& con;
    time_t from;
    time_t to;
    bool resolve_ip;
  private:
    int id;
    int client_id;
    report_type type;
};

class daily_report : public report, public client{
  public:
    daily_report(Connection& cn, int report_id, int cl_id);
    ~daily_report(){};
    int make_full_report();
    int make_filter_report();
};


class hourly_report : public report, public client{
  public:
    hourly_report(Connection& cn, int report_id, int cl_id);
    ~hourly_report(){};
    int make_full_report();
    int make_filter_report();
};

class flows_report : public report, public client{
  public:
    flows_report(Connection& cn, int report_id, int cl_id);
    ~flows_report(){};
    int make_full_report();
    int make_filter_report();
};

class current_report : public report, public client{
  public:
    current_report(Connection& cn, int report_id, int cl_id);
    ~current_report(){};
    int make_full_report();
    int make_filter_report();
};

#endif
