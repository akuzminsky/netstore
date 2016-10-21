#!/bin/sh

#/usr/bin/rsh -l www barracuda.nbi.com.ua "show ip route | include 195.5.49" | grep -v "[a-zA-Z]" | awk '{ print $2}'
/usr/bin/rsh -l www barracuda.nbi.com.ua "show ip bgp neighbors 195.5.49.1 received-routes" | awk '{ print $2}' | grep "\."


