/* $Id: config.h,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
#ifndef __CONFIG_H_
#define __CONFIG_H_

#include "common.h"

#ifndef __FLEX_LEXER_H
#undef yyFlexLexer
#include <FlexLexer.h>
#endif

namespace conf{
class CONFIG{
  public:
    CONFIG();
    CONFIG(const char*);
    CONFIG(const string&);
    ~CONFIG(){};
    const char* get_value(const string&);
    const string set_value(const string&, const string&);
    const char* get_value(const char*);
    void add_router(const RouterConfig&);
    list<RouterConfig> routers;
    int show();
  private:
    string confname;
    ifstream* confstream;
    map<string,string> values;
  };
}
#endif
