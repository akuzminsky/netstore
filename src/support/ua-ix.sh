#!/bin/sh

/usr/bin/rsh -l www barracuda.nbi.com.ua "show ip route | include 195.35.65" | awk '{ print $2}' | grep "\."


