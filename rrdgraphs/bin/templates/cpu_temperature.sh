/usr/local/bin/rrdtool graph $WORKING_DIR/rrd/rrd-cpu_temp_${GRAPH_NAME}.png    \
"-s" "$START_TIME"						                \
"-t" "$TITLE_STRING"                                    \
$BACKGROUND                                             \
"-v Degrees C"                                          \
"-E"                                                    \
"-a" "PNG"                                              \
"-h ${GRAPH_H}"                                         \
"-w" "600"                                              \
"-X 0"                                                  \
"-l 10"                       					        \
"DEF:cpu=$WORKING_DIR/rrd/cpu_temp.rrd:core0:AVERAGE"   \
"LINE1:cpu#00CF00:"                                     \
"VDEF:maxC=cpu,MAXIMUM"                                 \
"VDEF:minC=cpu,MINIMUM"                                 \
"VDEF:avgC=cpu,AVERAGE"                                 \
"VDEF:lastC=cpu,LAST"                                   \
"HRULE:60#FF0000"                                       \
"GPRINT:minC:Min\\: %2.1lf"                       		\
"GPRINT:maxC:Max\\: %2.1lf"                       		\
"GPRINT:avgC:Avg\\: %2.1lf"                       		\
"GPRINT:lastC:Last\\: %2.1lf \t\t" 				    \
"COMMENT: Last update\: $LAST_UPDATE"
