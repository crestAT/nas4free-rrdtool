#!/bin/bash
#date
WORKING_DIR=`dirname $0`
. $WORKING_DIR/CONFIG.sh

# function converts SI units (K, M, G, T bits/bytes) to bits/bytes: factor 1000 instead of 1024 because RRDTool converts not binary
CALC_SI ()
{
    CRESULT=`echo -e $1 | awk '/M/ {gsub("[M]", ""); calc=$1*1000*1000; print calc}'`
    if [ "${CRESULT}" == "" ]; then                                                     # not MByte
        CRESULT=`echo -e $1 | awk '/K/ {gsub("[K]", ""); calc=$1*1000; print calc}'`
        if [ "${CRESULT}" == "" ]; then                                                 # not kByte
            CRESULT=`echo -e $1 | awk '/G/ {gsub("[G]", ""); calc=$1*1000*1000*1000; print calc}'`
            if [ "${CRESULT}" == "" ]; then                                             # not GByte
                CRESULT=`echo -e $1 | awk '/T/ {gsub("[T]", ""); calc=$1*1000*1000*1000*1000; print calc}'`
                    if [ "${CRESULT}" == "" ]; then CRESULT=$1; fi                      # only Byte (no postfix)
            fi
        fi
    fi
}

# function creates rrdtool update command for mounted disks -> parameters: mount_point(=$1) used_space(=$2) free_space(=$3)
CREATE_MOUNTS_CMD ()
{
while [ "${1}" != "" ]; do
    i=0
    counter=MOUNT${i}
    while [ "${!counter}" != "" ]; do
        if [ "${!counter}" == "$1" ]; then
            CALC_SI $2; C_USED=${CRESULT}
            CALC_SI $3; C_FREE=${CRESULT}
            /usr/local/bin/rrdtool update $WORKING_DIR/rrd/mnt_${1}.rrd N:${C_USED}:${C_FREE}
            break
        fi
        i=$((i+1))
        counter=MOUNT${i}
    done
    shift 3
done
}

# function creates rrdtool update command for pools -> parameters: pool_name(=$1) used_space(=$2) free_space(=$3)
CREATE_POOLS_CMD ()
{
while [ "${1}" != "" ]; do
    CALC_SI $2; C_USED=${CRESULT}
    CALC_SI $3; C_FREE=${CRESULT}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/mnt_${1}.rrd N:${C_USED}:${C_FREE}
    shift 3
done
}

# function extracts values from 'top' for ARC usage -> parameters: var_name(=$1) var_value(=$2)
CREATE_AVARS ()
{
# ARC: 712K Total, 146K MFU, 347K MRU, 16K Anon, 12K Header, 190K Other
Total=0; MFU=0; MRU=0; Anon=0; Header=0; Other=0;
while [ "${1}" != "" ]; do
   case ${1} in
        Total)  CALC_SI ${2}; Total=${CRESULT};;
        MFU)    CALC_SI ${2}; MFU=${CRESULT};;
        MRU)    CALC_SI ${2}; MRU=${CRESULT};;
        Anon)   CALC_SI ${2}; Anon=${CRESULT};;
        Header) CALC_SI ${2}; Header=${CRESULT};;
        Other)  CALC_SI ${2}; Other=${CRESULT};;
    esac
    shift
done
AVARS=$Total:$MFU:$MRU:$Anon:$Header:$Other
}

# function extracts values from 'top' for memory -> parameters: var_name(=$1) var_value(=$2)
CREATE_MVARS ()
{
active=0; inact=0; wired=0; cache=0; buf=0; free=0; swaptotal=0; swapused=0;
while [ "${1}" != "" ]; do
   case ${1} in
        Active) CALC_SI ${2}; active=${CRESULT};;
        Inact)  CALC_SI ${2}; inact=${CRESULT};;
        Wired)  CALC_SI ${2}; wired=${CRESULT};;
        Cache)  CALC_SI ${2}; cache=${CRESULT};;
        Buf)    CALC_SI ${2}; buf=${CRESULT};;
        Free)	CALC_SI ${2}; free=${CRESULT};;
        Total)  CALC_SI ${2}; swaptotal=${CRESULT};;
        Used)   CALC_SI ${2}; swapused=${CRESULT};;
    esac
    shift
done
}

# function extracts values from 'top' for processes -> parameters: var_name(=$3) var_value(=$4)
CREATE_PVARS ()
{
total=${2}
running=0; sleeping=0; waiting=0; starting=0; stopped=0; zombie=0
while [ "${3}" != "" ]; do
   case ${3} in
        running)	running=${4};;
        sleeping)	sleeping=${4};;
        waiting)	waiting=${4};;
        starting)   starting=${4};;
        stopped)	stopped=${4};;
        zombie)		zombie=${4};;
    esac
    shift
done
}

# function extracts values from 'top' for CPU usage -> parameters: var_name(=$1) var_value(=$2)
CREATE_CVARS ()
{
user=0; nice=0; system=0; interrupt=0; idle=0;
while [ "${1}" != "" ]; do
   case ${1} in
        user)       user=${2};;
        nice)       nice=${2};;
        system)     system=${2};;
        interrupt)  interrupt=${2};;
        idle)       idle=${2};;
        *)		    TYPE=$2;;
    esac
    shift
done
}

CREATE_UPSVARS ()
{
# Values:
#   battery.charge      %
#   ups.load            %
#   battery.voltage     V
#   input.voltage       V 
#   battery.runtime     m
#   ups.status          OL [CHRG]
#                       OFF
#                       OB
# $1: var name $2: value $3: CHRG 
charge=0; load=0; bvoltage=0; ivoltage=0; runtime=0; OL=0; OF=0; OB=0; CG=0;
if [ "${CMD}" == "" ]; then OF=100; return; fi
while [ "${1}" != "" ]; do
   case ${1} in
        battery.charge:)    charge=${2};;
        ups.load:)          load=${2};;
        battery.voltage:)   bvoltage=${2};;
        input.voltage:)     ivoltage=${2};;
        battery.runtime:)   runtime=`echo -e $2 | awk '{calc=$1/60; print calc}'`;;
        ups.status:)    case ${2} in
                        OL)     OL=100;;
                        OFF)    OF=100;;
                        OB)     OL=100; OB=100;;
                        CHRG)   OL=100; CG=100;;
                        esac; 
                        case ${3} in
                        CHRG)   OL=100; CG=100;;
                        esac;;
        esac
    shift
done
}

# call 'top' once for: Load Averages, Processes, CPU Usage, Memory & Swap Usage, ZFS ARC, Uptime
if [ $RUN_AVG -eq 1 ] || [ $RUN_PRO -eq 1 ] || [ $RUN_CPU -eq 1 ] || [ $RUN_MEM -eq 1 ] || [ $RUN_ARC -eq 1 ]  || [ $RUN_UPT -eq 1 ] ; then TOP=`top -bSItud2`; fi

####################################################
# Update graphs for:
####################################################

# network interfaces
if [ $RUN_LAN -eq 1 ]; then 
    x=0
    while [ -e "$WORKING_DIR/rrd/${INTERFACE0}.rrd" ]
    do
        /usr/local/bin/rrdtool update $WORKING_DIR/rrd/${INTERFACE0}.rrd N:`netstat -I ${INTERFACE0} -nWb -f link | grep -v Name | awk '{print $8":"$11}'`
		x=$((x+1))
        INTERFACE0=`/usr/local/bin/xml sel -t -v "//interfaces/opt${x}/if" /conf/config.xml`
    done
fi

# system load averages
if [ $RUN_AVG -eq 1 ]; then 
    LA=`echo -e "$TOP" | awk '/averages:/ {gsub(",", ""); print $6":"$7":"$8; exit}'`
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/cpu_usage.rrd N:$LA
fi

# CPU temperatures
if [ $RUN_TMP -eq 1 ]; then 
    T1=`sysctl -q -n dev.cpu.0.temperature | awk '{gsub("C",""); print}'`;      # core 1 temperature
    T2=`sysctl -q -n dev.cpu.1.temperature | awk '{gsub("C",""); print}'`;      # core 2 temperature
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/cpu_temp.rrd N:$T1:$T2
fi

# CPU frequency
if [ $RUN_FRQ -eq 1 ]; then 
    F=`sysctl -n dev.cpu.0.freq | tr -d "\n"`;
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/cpu_freq.rrd N:$F:0
fi

# Processes
if [ $RUN_PRO -eq 1 ]; then 
    NP=`echo -e "$TOP" | awk '/processes:/ {gsub("[:,]", ""); print $2" "$1"  "$4" "$3"  "$6" "$5"  "$8" "$7"  "$10" "$9"  "$12" "$11"  "$14" "$13; exit}'`
    CREATE_PVARS ${NP}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/processes.rrd N:$total:$running:$sleeping:$waiting:$starting:$stopped:$zombie
fi

# CPU usage
if [ $RUN_CPU -eq 1 ]; then 
    CP=`echo -e "$TOP" | awk '/CPU:/ {gsub("[%,]", ""); print $3" "$2" "$5" "$4" "$7" "$6" "$9" "$8" "$11" "$10; exit}'`
    CREATE_CVARS ${CP}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/cpu.rrd N:$user:$nice:$system:$interrupt:$idle
fi

# Disk usage
if [ $RUN_DUS -eq 1 ]; then 
    mount=`df -h | awk '!/jail/ && /\/mnt\// {gsub("/mnt/",""); print $6, $3, $4}' | awk '!/\// {print}'`
    pool=`zfs list -H -o name,used,available | awk '!/\// {print}'`
    CREATE_MOUNTS_CMD ${mount}
    CREATE_POOLS_CMD ${pool}
fi

# Memory
if [ $RUN_MEM -eq 1 ]; then 
    SW=`echo -e "$TOP" | awk '/Swap:/ {gsub("[:,]", ""); gsub("Free", "Swapfree"); print $3" "$2" "$5" "$4" "$7" "$6" "$9" "$8" "$11" "$10; exit}'`
    MM="`echo -e "$TOP" | awk '/Mem:/ {gsub("[:,]", ""); print $3" "$2" "$5" "$4" "$7" "$6" "$9" "$8" "$11" "$10" "$13" "$12; exit}'` ${SW}"
    CREATE_MVARS ${MM}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/memory.rrd N:$active:$inact:$wired:$cache:$buf:$free:$swaptotal:$swapused
fi

# ZFS ARC
if [ $RUN_ARC -eq 1 ]; then 
    ARC=`echo -e "$TOP" | awk '/ARC:/ {gsub("[:,]", ""); print $3" "$2" "$5" "$4" "$7" "$6" "$9" "$8" "$11" "$10" "$13" "$12; exit}'`
    CREATE_AVARS ${ARC}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/arc.rrd N:$AVARS
fi

# UPS
if [ $RUN_UPS -eq 1 ]; then 
    CMD=`/usr/local/bin/upsc ${UPS_AT}`
    CREATE_UPSVARS ${CMD}
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/ups.rrd N:$charge:$load:$bvoltage:$ivoltage:$runtime:$OL:$OF:$OB:$CG
fi

# Latency
if [ $RUN_LAT -eq 1 ]; then 
    PG=`ping $LATENCY_PARAMETERS -S $LATENCY_INTERFACE_IP -c $LATENCY_COUNT $LATENCY_HOST | awk '/round-trip/ {gsub("/", ":"); print $4}'`
    if [ "$PG" == "" ]; then PG="0:0:0:0"; fi
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/latency.rrd N:$PG
fi

# Uptime
if [ $RUN_UPT -eq 1 ]; then 
    UT=`echo -e "$TOP" | awk '/averages:/ {gsub("[,+:]", " "); print $10*24*60+$11*60+$12; exit}'`
    /usr/local/bin/rrdtool update $WORKING_DIR/rrd/uptime.rrd N:$UT
fi
#date
