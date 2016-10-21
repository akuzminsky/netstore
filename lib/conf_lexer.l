/* $Id: conf_lexer.l,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
%s COMMENT NORMAL
/* instructs flex to generate a case-insensitive scanner */
%option caseless
/* instructs flex to generate an 8-bit scanner */
%option 8bit
/* Don't generate the default rule => no output to console */
%option nodefault
/* directs flex to generate a scanner that maintains
   the number of the current line read from its input
   in the global variable yylineno */
%option yylineno
/* if unset (i.e., --noyywrap), makes the scanner
   not call yywrap() upon an end-of-file, but simply
   assume that there are no more files to scan */
%option noyywrap
/* specifies that you want flex to generate a C++ scanner class */
%option c++
%option yyclass="conf::Lexer"
%option prefix="conf"


/*%option debug*/

comstart "#"
comstop "\n"
%{

#include "conf_lexer.h"
#include "conf_parser.hpp"

char linebuf[1024];
%}

ip_address [[:digit:]]{1,3}("."[[:digit:]]{1,3}){3}
label  [[:alpha:]]+[[:alnum:]\-]*
dns_name {label}+(\.{label})*

%%
    BEGIN NORMAL;
<NORMAL>{comstart}  { BEGIN COMMENT; }
<COMMENT>[ [:graph:]\t] ;
<COMMENT>{comstop}  { BEGIN 0; BEGIN NORMAL; }
<NORMAL>pid_file  {
            return conf::Parser::token::l_pid_file;
            }
<NORMAL>listen  {
                return conf::Parser::token::l_listen;
                }
<NORMAL>port  {
                return conf::Parser::token::l_port;
                }
<NORMAL>mysql_host  {
                return conf::Parser::token::l_mysql_host;
                }
<NORMAL>mysql_user  {
                return conf::Parser::token::l_mysql_user;
                }
<NORMAL>mysql_password    {
                return conf::Parser::token::l_mysql_password;
                }
<NORMAL>mysql_database  {
              return conf::Parser::token::l_mysql_database;
              }
<NORMAL>cache_size  {
              return conf::Parser::token::l_cache_size;
              }
<NORMAL>router  {
              return conf::Parser::token::l_router;
              }
<NORMAL>data    {
              return conf::Parser::token::l_data;
              }
<NORMAL>address  {
            return conf::Parser::token::l_address;
            }
<NORMAL>community  {
                  return conf::Parser::token::l_community;
                  }
<NORMAL>{ip_address}  {
                      return conf::Parser::token::l_ip_address;
                      }
<NORMAL>{dns_name}  {
                    return conf::Parser::token::l_dns_name;
                    }
<NORMAL>[-./_~!@$%^&*()+=[:alnum:]]+  {
                      yylval->sval = YYText();
                      return conf::Parser::token::l_strvalue;
                      }
<NORMAL>[ \t\n]  ;
<NORMAL>.  {
          return YYText()[0];
          }
%%
namespace conf {

// Constructor
Lexer::Lexer(std::istream* src) : yyFlexLexer(src, 0){}

// Destructor
Lexer::~Lexer(){};

//Error report routine
void Lexer::LexerError(const char* msg) {
  throw std::runtime_error( msg );
}

//function call operator overloading
Parser::token_type Lexer::operator()(Parser::semantic_type* lval, Parser::location_type* lloc){
  yylval = lval;
  return Parser::token_type(yylex());
 }
}

