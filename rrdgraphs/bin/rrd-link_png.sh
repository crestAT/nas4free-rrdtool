#!/bin/sh

WORKING_DIR=`dirname $0`
. $WORKING_DIR/CONFIG.sh

# links to the pngs
cd $WORKING_DIR/rrd
PNGS=`ls -1 rrd-*.png`
for NAME in $PNGS; do 
    ln -s $WORKING_DIR/rrd/$NAME /usr/local/www/$NAME
done
