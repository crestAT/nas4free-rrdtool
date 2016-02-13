#!/bin/sh

WORKING_DIR=`dirname $0`
. $WORKING_DIR/CONFIG.sh
LAST_UPDATE=`date +"%d.%m.%Y %H\:%M"`
if [ $BACKGROUND_BLACK -eq 1 ]; then BACKGROUND='-c CANVAS#000000';
else BACKGROUND=''; fi

# function parameters (mandatory): template_file_name graph_title_string
CREATE_GRAPHS ()
{
    GRAPH=${1}
    GRAPH_NAME="daily";     START_TIME="-1day";     TITLE_STRING="${2} - by day (5 minute averages)"
    . $WORKING_DIR/templates/${1}.sh
    GRAPH_NAME="weekly";    START_TIME="-1week";    TITLE_STRING="${2} - by week (30 minute averages)"
    . $WORKING_DIR/templates/${1}.sh
    GRAPH_NAME="monthly";   START_TIME="-1month";   TITLE_STRING="${2} - by month (2 hour averages)"
    . $WORKING_DIR/templates/${1}.sh
    GRAPH_NAME="yearly";    START_TIME="-1year";    TITLE_STRING="${2} - by year (12 hour averages)"
    . $WORKING_DIR/templates/${1}.sh
}

if [ "$1" == "traffic" ] || ( [ "$1" == "" ] && [ "$RUN_LAN" == "1" ] ); then 
    if [ $LOGARITHMIC -eq 1 ]; then SCALING='-o --units=si'; LOWER_LIMIT='-l 1000';
    else SCALING=''; LOWER_LIMIT='--alt-autoscale-max'; fi
    if [ $AXIS -eq 1 ]; then YAXIS='-1'; OUT_MAX="MIN"; else YAXIS='1'; OUT_MAX="MAX"; fi
    if [ $BYTE_SWITCH -eq 1 ]; then BIT_STR="Bytes"; BIT_VAL=1; else BIT_STR="Bits"; BIT_VAL=8; fi
    x=0
    while [ -e "$WORKING_DIR/rrd/${INTERFACE0}.rrd" ]
    do
        CREATE_GRAPHS "network_traffic" "Traffic on interface ${INTERFACE0}"
        x=$((x+1))
        INTERFACE0=`/usr/local/bin/xml sel -t -v "//interfaces/opt${x}/if" /conf/config.xml`
    done
fi
if [ "$1" == "load" ]        || ( [ "$1" == "" ] && [ "$RUN_AVG" == "1" ] ); then CREATE_GRAPHS "load_averages"   "CPU load averages"; fi
if [ "$1" == "temperature" ] || ( [ "$1" == "" ] && [ "$RUN_TMP" == "1" ] ); then CREATE_GRAPHS "cpu_temperature" "CPU temperature"; fi
if [ "$1" == "frequency" ]   || ( [ "$1" == "" ] && [ "$RUN_FRQ" == "1" ] ); then CREATE_GRAPHS "cpu_frequency"   "CPU frequency"; fi
if [ "$1" == "processes" ]   || ( [ "$1" == "" ] && [ "$RUN_PRO" == "1" ] ); then CREATE_GRAPHS "processes"       "Number of processes"; fi
if [ "$1" == "cpu" ]         || ( [ "$1" == "" ] && [ "$RUN_CPU" == "1" ] ); then CREATE_GRAPHS "cpu"             "CPU usage"; fi
if [ "$1" == "disk_usage" ]  || ( [ "$1" == "" ] && [ "$RUN_DUS" == "1" ] ); then 
    if [ "$2" == "" ]; then
        DA=`df -k | awk '!/jail/ && /\/mnt\// {gsub("/mnt/",""); print $6}' | awk '!/\// {print}'`
        for DISK_NAME in $DA; do CREATE_GRAPHS "disk_usage" "Disk space usage for ${DISK_NAME}"; done
    else
        DISK_NAME=$2;
        CREATE_GRAPHS "disk_usage" "Disk space usage for ${DISK_NAME}";
    fi
fi
if [ "$1" == "memory" ]      || ( [ "$1" == "" ] && [ "$RUN_MEM" == "1" ] ); then 
    CREATE_GRAPHS "memory"          "Memory usage"; 
    CREATE_GRAPHS "memory-detailed" "Memory usage - detailed"; 
fi
if [ "$1" == "arc" ]         || ( [ "$1" == "" ] && [ "$RUN_ARC" == "1" ] ); then CREATE_GRAPHS "arc"     "ARC usage"; fi
if [ "$1" == "ups" ]         || ( [ "$1" == "" ] && [ "$RUN_UPS" == "1" ] ); then CREATE_GRAPHS "ups"     "UPS ${UPS_AT}"; fi
if [ "$1" == "latency" ]     || ( [ "$1" == "" ] && [ "$RUN_LAT" == "1" ] ); then CREATE_GRAPHS "latency" "Destination host $LATENCY_HOST"; fi
if [ "$1" == "uptime" ]      || ( [ "$1" == "" ] && [ "$RUN_UPT" == "1" ] ); then 
    TOP=`top -bSItu`
    UT=`echo -e "$TOP" | awk '/averages:/ {gsub("[,+:]", " "); print $10" day(s), "$11" hour(s), "$12" minute(s)"; exit}'`
    CREATE_GRAPHS "uptime"  "System uptime"
fi

exit 0
