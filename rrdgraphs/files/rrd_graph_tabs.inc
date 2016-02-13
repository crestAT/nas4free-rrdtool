<!-- 
	rrd_graph_tabs.inc

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
-->
<li class="<?=isset($status_graph) ? "tabact" : "tabinact"?>"><a href="status_graph.php" title="<?=gettext("Reload page");?>"><span><?=gettext("System load");?></span></a></li>
<li class="<?=isset($status_cpu) ? "tabact" : "tabinact"?>"><a href="status_graph_cpu.php" title="<?=gettext("Reload page");?>"><span><?=gettext("CPU load");?></span></a></li>
<?php if (isset($config['rrdgraphs']['cpu_frequency'])):?>
    <li class="<?=isset($rrd_cpu_freq) ? "tabact" : "tabinact"?>"><a href="rrd_graph_cpu_freq.php" title="<?=gettext("Reload page");?>"><span><?=gettext("CPU frequency");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['cpu_temperature'])):?>
    <li class="<?=isset($rrd_cpu_temp) ? "tabact" : "tabinact"?>"><a href="rrd_graph_cputemp.php" title="<?=gettext("Reload page");?>"><span><?=gettext("CPU temperature");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['cpu_usage'])):?>
    <li class="<?=isset($rrd_cpu_usage) ? "tabact" : "tabinact"?>"><a href="rrd_graph_cpu.php" title="<?=gettext("Reload page");?>"><span><?=gettext("CPU usage");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['disk_usage'])):?>
    <li class="<?=isset($rrd_disk_usage) ? "tabact" : "tabinact"?>"><a href="rrd_graph_disk_usage.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Disk usage");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['load_averages'])):?>
    <li class="<?=isset($rrd_cpu_averages) ? "tabact" : "tabinact"?>"><a href="rrd_graph_cpu_averages.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Load averages");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['memory_usage'])):?>
    <li class="<?=isset($rrd_memory) ? "tabact" : "tabinact"?>"><a href="rrd_graph_memory.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Memory usage");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['latency'])):?>
    <li class="<?=isset($rrd_latency) ? "tabact" : "tabinact"?>"><a href="rrd_graph_latency.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Network latency");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['lan_load'])):?>
    <li class="<?=isset($rrd_lan) ? "tabact" : "tabinact"?>"><a href="rrd_graph_lan.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Network traffic");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['no_processes'])):?>
    <li class="<?=isset($rrd_processes) ? "tabact" : "tabinact"?>"><a href="rrd_graph_processes.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Processes");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['ups'])):?>
    <li class="<?=isset($rrd_ups) ? "tabact" : "tabinact"?>"><a href="rrd_graph_ups.php" title="<?=gettext("Reload page");?>"><span><?=gettext("UPS");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['uptime'])):?>
    <li class="<?=isset($rrd_uptime) ? "tabact" : "tabinact"?>"><a href="rrd_graph_uptime.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Uptime");?></span></a></li>
<?php endif;?>
<?php if (isset($config['rrdgraphs']['arc_usage'])):?>
    <li class="<?=isset($rrd_arc) ? "tabact" : "tabinact"?>"><a href="rrd_graph_arc.php" title="<?=gettext("Reload page");?>"><span><?=gettext("ZFS ARC");?></span></a></li>
<?php endif;?>
