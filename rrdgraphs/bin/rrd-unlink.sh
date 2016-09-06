#!/bin/bash

WORKING_DIR=`dirname $0`
. $WORKING_DIR/CONFIG.sh
logger "rrdgraphs: remove links for rrdtool (Platform OS ${OS_RELEASE}) ..."

# function compares arg1 and arg2, returns: "0" on arg1 less, "1" on arg1 equal, "2" on arg1 greater than arg2
COMPARE ()
{ local RESULT=`echo | awk -v n1=$1 -v n2=$2 '{if (n1<n2) print("0"); else if (n1==n2) print("1"); else print("2");}'`; return $RESULT; }

if [ -L /usr/local/bin/rrdtool ]; then
    unlink /usr/local/bin/rrdtool
    unlink /usr/local/lib/librrd.so.6
    unlink /usr/local/lib/libpangocairo-1.0.so.0
    unlink /usr/local/lib/libpangoft2-1.0.so.0
    unlink /usr/local/lib/libharfbuzz.so.0
    unlink /usr/local/lib/libpango-1.0.so.0
    unlink /usr/local/lib/libcairo.so.2
    unlink /usr/local/lib/libgraphite2.so.3
    unlink /usr/local/lib/libpixman-1.so.9
    unlink /usr/local/lib/libfontconfig.so.1
    unlink /usr/local/lib/libxcb-shm.so.0
    unlink /usr/local/lib/libxcb-render.so.0
    unlink /usr/local/lib/libXrender.so.1
    unlink /usr/local/lib/libX11.so.6
    unlink /usr/local/lib/libxcb.so.2
    unlink /usr/local/lib/libXau.so.6
    unlink /usr/local/lib/libXdmcp.so.6
    unlink /usr/local/lib/libpthread-stubs.so.0
    unlink /usr/local/lib/pango
    unlink /usr/local/etc/pango
    unlink /usr/local/etc/fonts

    if [ "$OS_RELEASE" != "9.1" ]; then
        unlink /usr/local/lib/libfreetype.so.9
        unlink /usr/local/lib/libxml2.so.5
    fi

    #if [ "$OS_RELEASE" != "9.3" ]; then
        if [ -L /usr/local/lib/X11 ]; then unlink /usr/local/lib/X11; fi
    #fi

    COMPARE $OS_RELEASE 9.3     # test for OS_RELEASE greater or equal 9.3 (e.g. 10.1, 10.2, 11.x)
    if [ $? -ge 1 ]; then
        unlink /usr/local/lib/libpcre.so.3
        unlink /usr/local/lib/libintl.so.9
        unlink /usr/local/lib/libexpat.so.6
        unlink /usr/local/lib/libpng15.so.15
        unlink /usr/local/lib/libiconv.so.3
        unlink /usr/local/lib/X11/fonts/dejavu
    fi
fi

# unlink the pngs
cd /usr/local/www
PNGS=`ls -1 rrd-*.png`
for NAME in $PNGS; do 
    unlink /usr/local/www/$NAME
done
