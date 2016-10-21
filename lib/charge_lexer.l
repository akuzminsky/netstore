/* $Id: charge_lexer.l,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $ */
%s CCOMMENT CPPCOMMENT NORMAL
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
%option yyclass="charge::Lexer"
%option prefix="charge"

ccomstart "/*"
ccomstop "*/"
cppcomstart "//"
cppcomstop "\n"

%{
#include "charge_lexer.h"
#include "charge_parser.hpp"
%}

%%
  BEGIN NORMAL;
<NORMAL>{ccomstart} {
  BEGIN CCOMMENT; }
<NORMAL>{cppcomstart} {
  BEGIN CPPCOMMENT; }
<CCOMMENT>[^*/] ;
<CCOMMENT>{ccomstop} {
  BEGIN 0; 
  BEGIN NORMAL;
  }
<CPPCOMMENT>[^\n] ;
<CPPCOMMENT>\n {
  BEGIN 0;
  BEGIN NORMAL;
  }
<NORMAL>if {
  return charge::Parser::token::IF;
  }
<NORMAL>return {
  return charge::Parser::token::RETURN;
  }
<NORMAL>and|&& {
  return charge::Parser::token::AND;
  }
<NORMAL>or|"||" {
  return charge::Parser::token::OR;
  }
<NORMAL>not|! {
  return charge::Parser::token::NOT;
  }
<NORMAL>== {
  return charge::Parser::token::EQ;
  }
<NORMAL>!=|<> {
  return charge::Parser::token::NEQ;
  }
<NORMAL>">" {
  return charge::Parser::token::GT;
  }
<NORMAL>"<" {
  return charge::Parser::token::LT;
  }
<NORMAL>">="|"=>" {
  return charge::Parser::token::NLT;
  }
<NORMAL>"<="|"=<" {
  return charge::Parser::token::NGT;
  }
<NORMAL>traffic_in|traffic_out|traffic|filter|filter_in|filter_out|rel_time {
  yylval->sval = YYText();
  return charge::Parser::token::FUNCTION;
  }
<NORMAL>[0-9]+(\.[0-9]+)? {
  yylval->dval = atof(YYText());
  return charge::Parser::token::NUMBER;
  }
<NORMAL>[[:alnum:]]+ {
  yylval->sval = YYText();
  return charge::Parser::token::IDENTIFIER;
  }
<NORMAL>[ \t\r\n] ;
<NORMAL>. {
  return YYText()[0];
  }
%%

namespace charge {

// Constructor
Lexer::Lexer( std::istream* src ) : yyFlexLexer( src, 0 ){}

// Destructor
Lexer::~Lexer( ){};

//Error report routine
void Lexer::LexerError( const char* msg ) {
  throw std::runtime_error( msg );
}

//function call operator overloading 
Parser::token_type Lexer::operator()(Parser::semantic_type* lval, Parser::location_type* lloc){
      yylval = lval;
      return Parser::token_type(yylex());
 }
}
