#ifndef __CONF_LEXER_H
#define __CONF_LEXER_H

#ifndef __FLEX_LEXER_H
#undef yyFlexLexer
#include <FlexLexer.h>
#endif

#include "conf_parser.hpp"

namespace conf {

  class Lexer: public yyFlexLexer {
    //tokenizer;
    int yylex();
  public:
    //constructor
    Lexer(std::istream* src = 0);
    //destructor
    virtual ~Lexer();
    //be the functor
    Parser::token_type operator()(Parser::semantic_type* lval, Parser::location_type* lloc = 0);
    //error report routine
    virtual void LexerError(const char* msg);
  private:
    Parser::semantic_type* yylval;
  };

}

#endif
