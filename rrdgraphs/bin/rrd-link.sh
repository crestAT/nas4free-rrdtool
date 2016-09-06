#!/bin/bash

WORKING_DIR=`dirname $0`
. $WORKING_DIR/CONFIG.sh

# function compares arg1 and arg2, returns: "0" on arg1 less, "1" on arg1 equal, "2" on arg1 greater than arg2
COMPARE ()
{ local RESULT=`echo | awk -v n1=$1 -v n2=$2 '{if (n1<n2) print("0"); else if (n1==n2) print("1"); else print("2");}'`; return $RESULT; }

if [ ! -e /usr/local/bin/rrdtool ]; then
    logger "rrdgraphs: create links for rrdtool (Platform OS ${OS_RELEASE}) ..."
    ln -s $WORKING_DIR/bin/rrdtool /usr/local/bin/rrdtool
    ln -s $WORKING_DIR/bin/lib/librrd.so.6 /usr/local/lib/librrd.so.6
    ln -s $WORKING_DIR/bin/lib/libpangocairo-1.0.so.0 /usr/local/lib/libpangocairo-1.0.so.0
    ln -s $WORKING_DIR/bin/lib/libpangoft2-1.0.so.0 /usr/local/lib/libpangoft2-1.0.so.0
    ln -s $WORKING_DIR/bin/lib/libharfbuzz.so.0 /usr/local/lib/libharfbuzz.so.0
    ln -s $WORKING_DIR/bin/lib/libpango-1.0.so.0 /usr/local/lib/libpango-1.0.so.0
    ln -s $WORKING_DIR/bin/lib/libcairo.so.2 /usr/local/lib/libcairo.so.2
    ln -s $WORKING_DIR/bin/lib/libgraphite2.so.3 /usr/local/lib/libgraphite2.so.3
    ln -s $WORKING_DIR/bin/lib/libpixman-1.so.9 /usr/local/lib/libpixman-1.so.9
    ln -s $WORKING_DIR/bin/lib/libfontconfig.so.1 /usr/local/lib/libfontconfig.so.1
    ln -s $WORKING_DIR/bin/lib/libxcb-shm.so.0 /usr/local/lib/libxcb-shm.so.0
    ln -s $WORKING_DIR/bin/lib/libxcb-render.so.0 /usr/local/lib/libxcb-render.so.0
    ln -s $WORKING_DIR/bin/lib/libXrender.so.1 /usr/local/lib/libXrender.so.1
    ln -s $WORKING_DIR/bin/lib/libX11.so.6 /usr/local/lib/libX11.so.6
    ln -s $WORKING_DIR/bin/lib/libxcb.so.2 /usr/local/lib/libxcb.so.2
    ln -s $WORKING_DIR/bin/lib/libXau.so.6 /usr/local/lib/libXau.so.6
    ln -s $WORKING_DIR/bin/lib/libXdmcp.so.6 /usr/local/lib/libXdmcp.so.6
    ln -s $WORKING_DIR/bin/lib/libpthread-stubs.so.0 /usr/local/lib/libpthread-stubs.so.0
    ln -s $WORKING_DIR/bin/pango/ /usr/local/lib/pango
    ln -s $WORKING_DIR/bin/pango/ /usr/local/etc/pango
    ln -s $WORKING_DIR/bin/fonts/ /usr/local/etc/fonts

    if [ "$OS_RELEASE" != "9.1" ]; then
        ln -s $WORKING_DIR/bin/lib/libfreetype.so.9 /usr/local/lib/libfreetype.so.9
        ln -s $WORKING_DIR/bin/lib/libxml2.so.5 /usr/local/lib/libxml2.so.5
    fi

    #if [ "$OS_RELEASE" != "9.3" ]; then
        if [ ! -e /usr/local/lib/X11 ]; then ln -s $WORKING_DIR/bin/lib/X11/ /usr/local/lib/X11; fi
    #fi

    COMPARE $OS_RELEASE 9.3     # test for OS_RELEASE greater or equal 9.3 (e.g. 10.1, 10.2, 11.x)
    if [ $? -ge 1 ]; then
        if [ ! -d /usr/local/lib/X11/fonts ]; then mkdir /usr/local/lib/X11/fonts; fi   # for r1391
        ln -s $WORKING_DIR/bin/lib/libpcre.so.3 /usr/local/lib/libpcre.so.3
        ln -s $WORKING_DIR/bin/lib/libintl.so.9 /usr/local/lib/libintl.so.9
        ln -s $WORKING_DIR/bin/lib/libexpat.so.6 /usr/local/lib/libexpat.so.6
        ln -s $WORKING_DIR/bin/lib/libpng15.so.15 /usr/local/lib/libpng15.so.15
        ln -s $WORKING_DIR/bin/lib/libiconv.so.3 /usr/local/lib/libiconv.so.3
        ln -s $WORKING_DIR/bin/lib/X11/fonts/dejavu/ /usr/local/lib/X11/fonts/dejavu
    fi
fi
