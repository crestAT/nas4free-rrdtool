<?php
/*
	rrd_graph_cpu.php

    Copyright (c) 2015 - 2016 Andreas Schmidhuber, RRDTool portion by Snunn1
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2015 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");

bindtextdomain("nas4free", "/usr/local/share/locale-rrd");
$pgtitle = array(gettext("Status"), gettext("Graph"), gettext("CPU usage"));
$rrd_cpu_usage = true;

$refresh = !empty($config['rrdgraphs']['refresh_time']) ? $config['rrdgraphs']['refresh_time'] : 300;
exec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-graph.sh cpu");

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-rrd"); ?>

<meta http-equiv="refresh" content="<?=$refresh?>">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td class="tabnavtbl">
        <ul id="tabnav">
<?php require("rrd_graph_tabs.inc");?>
        </ul>
    </td>
</tr>
<td class="tabcont">
<?=sprintf(gettext("Graph updates every %d seconds"), $refresh);?>
<div align="center" style="min-width:840px;">
    <br>
    <img src="/rrd-cpu_daily.png?rand=<?=time()?>" alt="RRDGraphs Daily CPU Graph" width="graph_width" height="graph_height">
    <br><br>
    <img src="/rrd-cpu_weekly.png?rand=<?=time()?>" alt="RRDGraphs Weekly CPU Graph" width="graph_width" height="graph_height">
    <br><br>
    <img src="/rrd-cpu_monthly.png?rand=<?=time()?>" alt="RRDGraphs Monthly CPU Graph" width="graph_width" height="graph_height">
    <br><br>
    <img src="/rrd-cpu_yearly.png?rand=<?=time()?>" alt="RRDGraphs Yearly CPU Graph" width="graph_width" height="graph_height">
</div>
</td></tr></table>
<?php include("fend.inc");?>
