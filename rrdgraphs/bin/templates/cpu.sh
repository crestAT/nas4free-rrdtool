/usr/local/bin/rrdtool graph $WORKING_DIR/rrd/rrd-${GRAPH}_${GRAPH_NAME}.png    \
"-v CPU usage [%]" \
"-s" "$START_TIME" \
"-t" "$TITLE_STRING" \
$BACKGROUND \
"-a" "PNG" \
"-h ${GRAPH_H}" \
"-w" "600" \
"--alt-autoscale-max" \
"DEF:user=$WORKING_DIR/rrd/cpu.rrd:user:AVERAGE" \
"DEF:nice=$WORKING_DIR/rrd/cpu.rrd:nice:AVERAGE" \
"DEF:system=$WORKING_DIR/rrd/cpu.rrd:system:AVERAGE" \
"DEF:interrupt=$WORKING_DIR/rrd/cpu.rrd:interrupt:AVERAGE" \
"DEF:idle=$WORKING_DIR/rrd/cpu.rrd:idle:AVERAGE" \
"AREA:interrupt#DF00007F:Interrupt" \
"GPRINT:interrupt:MIN:Min\\:%6.1lf" \
"GPRINT:interrupt:MAX:Max\\:%6.1lf" \
"GPRINT:interrupt:AVERAGE:Avg\\:%6.1lf" \
"GPRINT:interrupt:LAST:Last\\:%6.1lf" \
"COMMENT:\n" \
"STACK:nice#FFC96C7F:Nice     " \
"GPRINT:nice:MIN:Min\\:%6.1lf" \
"GPRINT:nice:MAX:Max\\:%6.1lf" \
"GPRINT:nice:AVERAGE:Avg\\:%6.1lf" \
"GPRINT:nice:LAST:Last\\:%6.1lf" \
"COMMENT:\n" \
"STACK:system#EC00EC7F:System   " \
"GPRINT:system:MIN:Min\\:%6.1lf" \
"GPRINT:system:MAX:Max\\:%6.1lf" \
"GPRINT:system:AVERAGE:Avg\\:%6.1lf" \
"GPRINT:system:LAST:Last\\:%6.1lf" \
"COMMENT:\n" \
"STACK:user#10BB0D7F:User     " \
"GPRINT:user:MIN:Min\\:%6.1lf" \
"GPRINT:user:MAX:Max\\:%6.1lf" \
"GPRINT:user:AVERAGE:Avg\\:%6.1lf" \
"GPRINT:user:LAST:Last\\:%6.1lf" \
"COMMENT:\n" \
"STACK:idle#E2E2E27F:Idle     " \
"GPRINT:idle:MIN:Min\\:%6.1lf" \
"GPRINT:idle:MAX:Max\\:%6.1lf" \
"GPRINT:idle:AVERAGE:Avg\\:%6.1lf" \
"GPRINT:idle:LAST:Last\\:%6.1lf" \
"COMMENT:\n" \
"LINE:interrupt#DF0000" \
"STACK:nice#FFC96C" \
"STACK:system#EC00EC" \
"STACK:user#10BB0D" \
"STACK:idle#E2E2E2" \
"TEXTALIGN:right" "COMMENT:Last update\: $LAST_UPDATE"
