<?php
/* 
    rrd_start.php

    Copyright (c) 2015 Andreas Schmidhuber, RRDTool portion by Snunn1
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
    either expressed or implied, of the FreeBSD Project.
 */
require_once("config.inc");
require_once("functions.inc");
require_once("install.inc");
require_once("util.inc");
require_once("{$config['rrdgraphs']['rootfolder']}ext/rrdgraphs_fcopy.inc");

$saved = $config['rrdgraphs']['product_version'];
$current = get_product_version().'-'.get_product_revision();
if ($saved != $current) {
    exec ("logger rrdgraphs: Saved Release: $saved New Release: $current - new backup of standard GUI files!");
    copy_origin2backup($files, $backup_path, $extend_path);
 	$config['rrdgraphs']['product_version'] = $current;
	write_config();
}
else exec ("logger rrdgraphs: saved and current GUI files are identical - OK");

if (is_file("{$config['rrdgraphs']['rootfolder']}version.txt")) {
    $file_version = exec("cat {$config['rrdgraphs']['rootfolder']}version.txt");
    if ($config['rrdgraphs']['version'] != $file_version) {
        $config['rrdgraphs']['version'] = $file_version;
        write_config();
    }
}

if (!is_dir('/usr/local/www/ext/rrdgraphs')) { exec ("mkdir -p /usr/local/www/ext/rrdgraphs"); }        // check for extension directory, links and cp ...
mwexec("cp {$config['rrdgraphs']['rootfolder']}ext/* /usr/local/www/ext/rrdgraphs/", true);
if (!is_link("/usr/local/www/rrdgraphs.php")) { exec ("ln -s /usr/local/www/ext/rrdgraphs/rrdgraphs.php /usr/local/www/rrdgraphs.php"); }
if (!is_link("/usr/local/www/rrdgraphs_update_extension.php")) { exec ("ln -s /usr/local/www/ext/rrdgraphs/rrdgraphs_update_extension.php /usr/local/www/rrdgraphs_update_extension.php"); }

if (isset($config['rrdgraphs']['enable'])) { 
    exec("logger rrdgraphs: enabled, starting ...");
    mwexec("cp {$config['rrdgraphs']['rootfolder']}files/* /usr/local/www/", true);
// exchange originals files with changed ...
    copy_extended2origin($files, $backup_path, $extend_path);                                            
// cp locales to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd", 0775, true); }
    exec ("cp -R {$config['rrdgraphs']['rootfolder']}locale-rrd/* {$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd/");
    if (!is_link("/usr/local/share/locale-rrd")) { exec("ln -s {$config['rrdgraphs']['storage_path']}rrdgraphs/locale-rrd /usr/local/share/"); }
// cp binaries to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/bin")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/bin", 0775, true); } 
    exec ("cp -R {$config['rrdgraphs']['rootfolder']}bin/{$config['rrdgraphs']['architecture']}/* {$config['rrdgraphs']['storage_path']}rrdgraphs/bin/");
// cp scripts to work path
    exec ("cp {$config['rrdgraphs']['rootfolder']}bin/*.sh {$config['rrdgraphs']['storage_path']}rrdgraphs/");
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
        $rrd_name = "{$config['interfaces']['lan']['if']}.rrd";
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

// cp graphs to work path
    if (!is_dir("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd")) { mkdir("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd", 0775, true); } 
    exec ("cp -R {$config['rrdgraphs']['rootfolder']}rrd/*.rrd {$config['rrdgraphs']['storage_path']}rrdgraphs/rrd/");  
// create new graphs
    mwexec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-graph.sh", true);
// create graph links
    mwexec("{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-link_png.sh", true);
}
else { copy_backup2origin ($files, $backup_path, $extend_path); }           // case extension not enabled at start restore original files
?>
