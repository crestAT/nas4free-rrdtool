/usr/local/bin/rrdtool graph $WORKING_DIR/rrd/rrd-mnt_${DISK_NAME}_${GRAPH_NAME}.png    \
"-s" "$START_TIME" \
"-t" "$TITLE_STRING" \
$BACKGROUND \
"-v Bytes" \
"-E" \
"-a" "PNG" \
"-h ${GRAPH_H}" \
"-w" "600" \
"-l 0" \
"--slope-mode" \
"DEF:Used=$WORKING_DIR/rrd/mnt_${DISK_NAME}.rrd:Used:AVERAGE" \
"DEF:Free=$WORKING_DIR/rrd/mnt_${DISK_NAME}.rrd:Free:AVERAGE" \
"AREA:Used#FFCC559F:Used" \
"GPRINT:Used:MIN:Min\\:%7.2lf %s" \
"GPRINT:Used:MAX:Max\\:%7.2lf %s" \
"GPRINT:Used:AVERAGE:Avg\\:%7.2lf %s" \
"GPRINT:Used:LAST:Last\\:%7.2lf %s" \
"COMMENT:\n" \
"STACK:Free#00CF007F:Free" \
"GPRINT:Free:MIN:Min\\:%7.2lf %s" \
"GPRINT:Free:MAX:Max\\:%7.2lf %s" \
"GPRINT:Free:AVERAGE:Avg\\:%7.2lf %s" \
"GPRINT:Free:LAST:Last\\:%7.2lf %s" \
"COMMENT:\n" \
"TEXTALIGN:right" "COMMENT:Last update\: $LAST_UPDATE"
