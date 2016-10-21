# $Id: Makefile,v 1.1.1.1 2007/11/29 15:29:46 ingoth Exp $

include ../netstore.mk

INC_PATH += -I$(dir $(realpath Makefile))../include
CONF_OBJECTS = conf_parser.o conf_lexer.o
CHARGE_OBJECTS = charge_parser.o charge_lexer.o
OBJECTS = $(CONF_OBJECTS) $(CHARGE_OBJECTS)
TLIBS = libnetstore.a

.SUFFIXES: 
.SUFFIXES: .cpp .o .l .y .a .hpp

%.cpp: %.l
	$(LEX) -o$@ $<

%.cpp: %.y
	$(YACC) $<

%.o: %.cpp
	$(CC) $(CFLAGS) $(INC_PATH) -c -o $@ $<

all: libnetstore.a

libnetstore.a: libnetstore.a($(OBJECTS))


libnetstore.a($(OBJECTS)): $(OBJECTS)
	$(AR) $@ $?
	ranlib $@

# clean targets
GEN_SRCS = location.hh position.hh stack.hh charge_parser.hpp charge_parser.cpp conf_parser.hpp conf_parser.cpp
clean:
	rm -f $(OBJECTS) $(GEN_SRCS) $(TLIBS)
	rm -f *.core
	rm -f conf_lexer.cpp
