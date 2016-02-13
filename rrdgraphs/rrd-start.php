<?php
/* 
    rrd-start.php

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
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
require_once("{$config['rrdgraphs']['rootfolder']}ext/rrdgraphs_fcopy.inc");

//@v02: one-time backup of stock graphs for new color definitions
if (!is_file("{$config['rrdgraphs']['backupfolder']}graph.php")) {
    copy("/usr/local/www/graph.php", "{$config['rrdgraphs']['backupfolder']}graph.php");
    copy("/usr/local/www/graph_cpu.php", "{$config['rrdgraphs']['backupfolder']}graph_cpu.php");
}

$saved = $config['rrdgraphs']['product_version'];
$current = get_product_version().'-'.get_product_revision();
if ($saved != $current) {
    exec ("logger rrdgraphs: Saved Release: $saved New Release: $current - new backup of standard GUI files!");
    rrd_copy_origin2backup($files, $backup_path, $extend_path);
 	$config['rrdgraphs']['product_version'] = $current;
}
else exec ("logger rrdgraphs: saved and current GUI files are identical - OK");

if (is_file("{$config['rrdgraphs']['rootfolder']}version.txt")) {
    $file_version = exec("cat {$config['rrdgraphs']['rootfolder']}version.txt");
    if ($config['rrdgraphs']['version'] != $file_version) {
        $config['rrdgraphs']['version'] = $file_version;
    }
}
// write_config();

if (!is_dir('/usr/local/www/ext/rrdgraphs')) { exec ("mkdir -p /usr/local/www/ext/rrdgraphs"); }        // check for extension directory, links and cp ...
mwexec("cp {$config['rrdgraphs']['rootfolder']}ext/* /usr/local/www/ext/rrdgraphs/", true);
// create links for WebGUI pages
if (!is_link("/usr/local/www/rrdgraphs.php")) { exec ("ln -s /usr/local/www/ext/rrdgraphs/rrdgraphs.php /usr/local/www/rrdgraphs.php"); }
if (!is_link("/usr/local/www/rrdgraphs_update_extension.php")) { exec ("ln -s /usr/local/www/ext/rrdgraphs/rrdgraphs_update_extension.php /usr/local/www/rrdgraphs_update_extension.php"); }
if (!is_link("/usr/local/share/locale-rrd")) { exec("ln -s {$config['rrdgraphs']['rootfolder']}locale-rrd /usr/local/share/"); }
//    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd", 0775, true); }
//    exec ("cp -R {$config['rrdgraphs']['rootfolder']}locale-rrd/* {$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd/");

if (isset($config['rrdgraphs']['enable'])) { 
    exec("logger rrdgraphs: enabled, starting ...");
    mwexec("cp {$config['rrdgraphs']['rootfolder']}files/* /usr/local/www/", true);
// exchange originals files with changed ...
    rrd_copy_extended2origin($files, $backup_path, $extend_path);                                            
// if isset background_black use originial colors for stock graphs  
    if (isset($config['rrdgraphs']['background_black'])) {
        copy("{$config['rrdgraphs']['backupfolder']}graph.php", "/usr/local/www/graph.php");
        copy("{$config['rrdgraphs']['backupfolder']}graph_cpu.php", "/usr/local/www/graph_cpu.php");
    }
// cp binaries to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/bin")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/bin", 0775, true); } 
    exec ("cp -R {$config['rrdgraphs']['rootfolder']}bin/{$config['rrdgraphs']['architecture']}/* {$config['rrdgraphs']['storage_path']}rrdgraphs/bin/");
// cp scripts to work path
    exec ("cp {$config['rrdgraphs']['rootfolder']}bin/*.sh {$config['rrdgraphs']['storage_path']}rrdgraphs/");
// cp templates to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/templates")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/templates", 0775, true); }
    exec ("cp {$config['rrdgraphs']['rootfolder']}bin/templates/* {$config['rrdgraphs']['storage_path']}rrdgraphs/templates/");
// create links to work path
    mwexec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-link.sh", true);

// create new .rrds if necessary
    $rrd_name = "cpu_freq.rrd";
    if (isset($config['rrdgraphs']['cpu_frequency']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
			'DS:core0:GAUGE:600:0:U' \
			'DS:core1:GAUGE:600:0:U' \
			'RRA:AVERAGE:0.5:1:576' \
			'RRA:AVERAGE:0.5:6:672' \
			'RRA:AVERAGE:0.5:24:732' \
			'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "cpu_temp.rrd";
    if (isset($config['rrdgraphs']['cpu_temperature']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
			'DS:core0:GAUGE:600:0:60' \
			'DS:core1:GAUGE:600:0:60' \
			'RRA:AVERAGE:0.5:1:576' \
			'RRA:AVERAGE:0.5:6:672' \
			'RRA:AVERAGE:0.5:24:732' \
			'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "cpu_usage.rrd";
    if (isset($config['rrdgraphs']['load_averages']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
			'DS:CPU:GAUGE:600:0:100' \
			'DS:CPU5:GAUGE:600:0:100' \
			'DS:CPU15:GAUGE:600:0:100' \
			'RRA:AVERAGE:0.5:1:576' \
			'RRA:AVERAGE:0.5:6:672' \
			'RRA:AVERAGE:0.5:24:732' \
			'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    if (isset($config['rrdgraphs']['lan_load'])) {
        $rrd_name = "{$config['rrdgraphs']['lan_if']}.rrd";
        if (!is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}")) { 
            mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
    			'-s 300' \
    			'DS:in:DERIVE:600:0:12500000' \
    			'DS:out:DERIVE:600:0:12500000' \
    			'RRA:AVERAGE:0.5:1:576' \
    			'RRA:AVERAGE:0.5:6:672' \
    			'RRA:AVERAGE:0.5:24:732' \
    			'RRA:AVERAGE:0.5:144:1460'
            ", true);
            exec("logger rrdgraphs: new rrd created: {$rrd_name}");
        }
        for ($j = 1; isset($config['interfaces']['opt' . $j]); $j++) {
        	$if = $config['interfaces']['opt' . $j]['if'];
            $rrd_name = "{$if}.rrd";
            if (!is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}")) {
                mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
        			'-s 300' \
        			'DS:in:DERIVE:600:0:12500000' \
        			'DS:out:DERIVE:600:0:12500000' \
        			'RRA:AVERAGE:0.5:1:576' \
        			'RRA:AVERAGE:0.5:6:672' \
        			'RRA:AVERAGE:0.5:24:732' \
        			'RRA:AVERAGE:0.5:144:1460'
                ", true);
                exec("logger rrdgraphs: new rrd created: {$rrd_name}");
            }
        }
    }
    $rrd_name = "cpu.rrd";
    if (isset($config['rrdgraphs']['cpu_usage']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:user:GAUGE:600:U:U' \
            'DS:nice:GAUGE:600:U:U' \
            'DS:system:GAUGE:600:U:U' \
            'DS:interrupt:GAUGE:600:U:U' \
            'DS:idle:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "memory.rrd";
    if (isset($config['rrdgraphs']['memory_usage']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:active:GAUGE:600:U:U' \
            'DS:inact:GAUGE:600:U:U' \
            'DS:wired:GAUGE:600:U:U' \
            'DS:cache:GAUGE:600:U:U' \
            'DS:buf:GAUGE:600:U:U' \
            'DS:free:GAUGE:600:U:U' \
            'DS:total:GAUGE:600:U:U' \
            'DS:used:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "arc.rrd";
    if (isset($config['rrdgraphs']['arc_usage']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:Total:GAUGE:600:U:U' \
            'DS:MFU:GAUGE:600:U:U' \
            'DS:MRU:GAUGE:600:U:U' \
            'DS:Anon:GAUGE:600:U:U' \
            'DS:Header:GAUGE:600:U:U' \
            'DS:Other:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "processes.rrd";
    if (isset($config['rrdgraphs']['no_processes']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:total:GAUGE:600:U:U' \
            'DS:running:GAUGE:600:U:U' \
            'DS:sleeping:GAUGE:600:U:U' \
            'DS:waiting:GAUGE:600:U:U' \
            'DS:starting:GAUGE:600:U:U' \
            'DS:stopped:GAUGE:600:U:U' \
            'DS:zombie:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "ups.rrd";
    if (isset($config['rrdgraphs']['ups']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:charge:GAUGE:600:U:U' \
            'DS:load:GAUGE:600:U:U' \
            'DS:bvoltage:GAUGE:600:U:U' \
            'DS:ivoltage:GAUGE:600:U:U' \
            'DS:runtime:GAUGE:600:U:U' \
            'DS:OL:GAUGE:600:U:U' \
            'DS:OF:GAUGE:600:U:U' \
            'DS:OB:GAUGE:600:U:U' \
            'DS:CG:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "latency.rrd";
    if (isset($config['rrdgraphs']['latency']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:min:GAUGE:600:U:U' \
            'DS:avg:GAUGE:600:U:U' \
            'DS:max:GAUGE:600:U:U' \
            'DS:stddev:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }
    $rrd_name = "uptime.rrd";
    if (isset($config['rrdgraphs']['uptime']) && !is_file("{$config['rrdgraphs']['rootfolder']}rrd/{$rrd_name}"))
    { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/{$rrd_name} \
			'-s 300' \
            'DS:uptime:GAUGE:600:U:U' \
            'RRA:AVERAGE:0.5:1:576' \
            'RRA:AVERAGE:0.5:6:672' \
            'RRA:AVERAGE:0.5:24:732' \
            'RRA:AVERAGE:0.5:144:1460'
    ", true);
    exec("logger rrdgraphs: new rrd created: {$rrd_name}");
    }

    // create config file - for booleans we need the variable $txt
    $rrdconfig = fopen("{$config['rrdgraphs']['rootfolder']}bin/CONFIG.sh", "w");
        fwrite($rrdconfig, "OS_RELEASE={$config['rrdgraphs']['osrelease']}"."\n");
        fwrite($rrdconfig, "GRAPH_H={$config['rrdgraphs']['graph_h']}"."\n");
        fwrite($rrdconfig, "REFRESH_TIME={$config['rrdgraphs']['refresh_time']}"."\n");
        $txt = isset($config['rrdgraphs']['background_black']) ? "1" : "0";
        fwrite($rrdconfig, "BACKGROUND_BLACK=".$txt."\n");
        $txt = isset($config['rrdgraphs']['bytes_per_second']) ? "1" : "0";
        fwrite($rrdconfig, "BYTE_SWITCH=".$txt."\n");
        $txt = isset($config['rrdgraphs']['logarithmic']) ? "1" : "0";
        fwrite($rrdconfig, "LOGARITHMIC=".$txt."\n");
        $txt = isset($config['rrdgraphs']['axis']) ? "1" : "0";
        fwrite($rrdconfig, "AXIS=".$txt."\n");
        fwrite($rrdconfig, "INTERFACE0={$config['rrdgraphs']['lan_if']}"."\n");
        fwrite($rrdconfig, "UPS_AT={$config['rrdgraphs']['ups_at']}"."\n");
        fwrite($rrdconfig, "LATENCY_HOST={$config['rrdgraphs']['latency_host']}"."\n");
        fwrite($rrdconfig, "LATENCY_INTERFACE={$config['rrdgraphs']['latency_interface']}"."\n");
        fwrite($rrdconfig, "LATENCY_INTERFACE_IP=".get_ipaddr($config['rrdgraphs']['latency_interface'])."\n");
        fwrite($rrdconfig, "LATENCY_COUNT={$config['rrdgraphs']['latency_count']}"."\n");
        fwrite($rrdconfig, "LATENCY_PARAMETERS='{$config['rrdgraphs']['latency_parameters']}'"."\n");
        $txt = isset($config['rrdgraphs']['lan_load']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_LAN=".$txt."\n");
        $txt = isset($config['rrdgraphs']['load_averages']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_AVG=".$txt."\n");
        $txt = isset($config['rrdgraphs']['cpu_temperature']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_TMP=".$txt."\n");
        $txt = isset($config['rrdgraphs']['cpu_frequency']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_FRQ=".$txt."\n");
        $txt = isset($config['rrdgraphs']['disk_usage']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_DUS=".$txt."\n");
        $txt = isset($config['rrdgraphs']['no_processes']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_PRO=".$txt."\n");
        $txt = isset($config['rrdgraphs']['cpu_usage']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_CPU=".$txt."\n");
        $txt = isset($config['rrdgraphs']['memory_usage']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_MEM=".$txt."\n");
        $txt = isset($config['rrdgraphs']['arc_usage']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_ARC=".$txt."\n");
        $txt = isset($config['rrdgraphs']['latency']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_LAT=".$txt."\n");
        $txt = isset($config['rrdgraphs']['ups']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_UPS=".$txt."\n");
        $txt = isset($config['rrdgraphs']['uptime']) ? "1" : "0";
        fwrite($rrdconfig, "RUN_UPT=".$txt."\n");

        if (isset($config['rrdgraphs']['disk_usage'])) {
            unset($config["rrdgraphs"]["mounts"]);
            $config["rrdgraphs"]["mounts"] = array();
            unset($config["rrdgraphs"]["pools"]);
            $config["rrdgraphs"]["pools"] = array();
            
            if (is_array($config['mounts']) && is_array($config['mounts']['mount'])) {
                for ($i = 0; $i < count($config['mounts']['mount']); ++$i) {
                    $config["rrdgraphs"]["mounts"]["mount{$i}"] = $config['mounts']['mount'][$i]['sharename']; 
                    fwrite($rrdconfig, "MOUNT{$i}={$config['mounts']['mount'][$i]['sharename']}"."\n");
                }
            }

            if (is_array($config['zfs']['pools']) && is_array($config['zfs']['pools']['pool'])) {
                for ($i = 0; $i < count($config['zfs']['pools']['pool']); ++$i) {
                    $config["rrdgraphs"]["pools"]["pool{$i}"] = $config['zfs']['pools']['pool'][$i]['name'];
                    fwrite($rrdconfig, "POOL{$i}={$config['zfs']['pools']['pool'][$i]['name']}"."\n");
                }
            }
        
            $temp_array = array_merge($config["rrdgraphs"]["mounts"], $config["rrdgraphs"]["pools"]);
            foreach ($temp_array as $retval) {
                if (!is_file("{$config['rrdgraphs']['rootfolder']}rrd/mnt_{$retval}.rrd"))
                { mwexec("/usr/local/bin/rrdtool create {$config["rrdgraphs"]["rootfolder"]}rrd/mnt_{$retval}.rrd \
                    '-s 300' \
                    'DS:Used:GAUGE:600:U:U' \
                    'DS:Free:GAUGE:600:U:U' \
                    'RRA:AVERAGE:0.5:1:576' \
                    'RRA:AVERAGE:0.5:6:672' \
                    'RRA:AVERAGE:0.5:24:732' \
                    'RRA:AVERAGE:0.5:144:1460'
                ", true);
                exec("logger rrdgraphs: new rrd created: mnt_{$retval}.rrd");
                }
            }
        }
    fclose($rrdconfig);

// cp CONFIG.sh to work path
    exec ("cp {$config['rrdgraphs']['rootfolder']}bin/CONFIG.sh {$config['rrdgraphs']['storage_path']}rrdgraphs/");
// cp rrds to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd")) { 
        mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd", 0775, true); 
        exec ("cp -R {$config['rrdgraphs']['rootfolder']}rrd/*.rrd {$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/");
    }
    else {
		foreach (glob("{$config['rrdgraphs']['rootfolder']}rrd/*.rrd") as $file_name) {
			if ((!is_file("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/".basename($file_name))) || 
                 (filemtime($file_name) > filemtime("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/".basename($file_name))))
                { 
                    copy($file_name, "{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/".basename($file_name));
                    exec("logger rrdgraphs: {$file_name} copied to {$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/".basename($file_name));
            } 
		}
	} 
// create new graphs
    mwexec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-graph.sh", true);
// create graph links
    mwexec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-link_png.sh", true);
}
else { rrd_copy_backup2origin ($files, $backup_path, $extend_path); }           // case extension not enabled at start restore original files
write_config();
?>
