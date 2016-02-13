<?php
/* 
    rrdgraphs.php

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

// Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext("The changes have been applied successfully.");
$dummy = gettext("The configuration has been changed.<br />You must apply the changes in order for them to take effect.");
$dummy = gettext("The following input errors were detected");

bindtextdomain("nas4free", "/usr/local/share/locale-rrd");
$pgtitle = array(gettext("Extensions"), $config['rrdgraphs']['appname']." ".$config['rrdgraphs']['version']);

if ( !isset( $config['rrdgraphs']['rootfolder']) && !is_dir( $config['rrdgraphs']['rootfolder'] )) {
	$input_errors[] = gettext("Extension installed with fault!");
} 
if (!isset($config['rrdgraphs']) || !is_array($config['rrdgraphs'])) $config['rrdgraphs'] = array();

$upsname = !empty($config['ups']['upsname']) ? $config['ups']['upsname'] : "identifier";
$upsip = !empty($config['ups']['ip']) ? $config['ups']['ip'] : "host-ip-address";
 
function cronjob_process_updatenotification($mode, $data) {
	global $config;
	$retval = 0;
	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			if (is_array($config['cron']['job'])) {
				$index = array_search_ex($data, $config['cron']['job'], "uuid");
				if (false !== $index) {
					unset($config['cron']['job'][$index]);
					write_config();
				}
			}
			break;
	}
	return $retval;
}

/* Check if the directory exists, the mountpoint has at least o=rx permissions and
 * set the permission to 775 for the last directory in the path
 */
function change_perms($dir) {
    global $input_errors;

    $path = rtrim($dir,'/');                                            // remove trailing slash
    if (strlen($path) > 1) {
        if (!is_dir($path)) {                                           // check if directory exists
            $input_errors[] = sprintf(gettext("Directory %s doesn't exist!"), $path);
        }
        else {
            $path_check = explode("/", $path);                          // split path to get directory names
            $path_elements = count($path_check);                        // get path depth
            $fp = substr(sprintf('%o', fileperms("/$path_check[1]/$path_check[2]")), -1);   // get mountpoint permissions for others
            if ($fp >= 5) {                                             // transmission needs at least read & search permission at the mountpoint
                $directory = "/$path_check[1]/$path_check[2]";          // set to the mountpoint
                for ($i = 3; $i < $path_elements - 1; $i++) {           // traverse the path and set permissions to rx
                    $directory = $directory."/$path_check[$i]";         // add next level
                    exec("chmod o=+r+x \"$directory\"");                // set permissions to o=+r+x
                }
                $path_elements = $path_elements - 1;
                $directory = $directory."/$path_check[$path_elements]"; // add last level
                exec("chmod 775 {$directory}");                         // set permissions to 775
                exec("chown {$_POST['who']} {$directory}*");
            }
            else
            {
                $input_errors[] = sprintf(gettext("RRDGraphs needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."), $path, "/{$path_check[1]}/{$path_check[2]}");
            }
        }
    }
}

if (isset($_POST['save']) && $_POST['save']) {
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-stop.php");

    unset($input_errors);
    $pconfig = $_POST;
    if (isset($_POST['ups']) && empty($_POST['ups_at'])) { $input_errors[] = gettext("UPS identifier and IP address")." ".sprintf(gettext("must be in the format: %s."), "identifier@host-ip-address"); }
    if (isset($_POST['latency']) && empty($_POST['latency_host'])) { $input_errors[] = gettext("Network latency").": ".gettext("Destination host name or IP address.")." ".gettext("Host")." ".gettext("must be defined!"); }
	if (empty($input_errors)) {
		if (isset($_POST['enable'])) {
            $config['rrdgraphs']['enable'] = isset($_POST['enable']) ? true : false;
            if (empty($_POST['storage_path'])) { $config['rrdgraphs']['storage_path'] = "/var/run/"; }
            else $_POST['storage_path'] = rtrim($_POST['storage_path'],'/')."/";            // ensure to have a trailing slash
            if (!is_dir("{$_POST['storage_path']}rrdgraphs")) { 
                mkdir("{$_POST['storage_path']}rrdgraphs", 0775, true);                     // new destination or first install
                change_perms("{$_POST['storage_path']}rrdgraphs");                          // check/set permissions
            }
            $config['rrdgraphs']['storage_path'] = $_POST['storage_path'];
            $_POST['graph_h'] = trim($_POST['graph_h']);  
            $config['rrdgraphs']['graph_h'] = !empty($_POST['graph_h']) ? $_POST['graph_h'] : 200;
            $config['rrdgraphs']['refresh_time'] = !empty($_POST['refresh_time']) ? $_POST['refresh_time'] : 300;
            $config['rrdgraphs']['background_black'] = isset($_POST['background_black']) ? true : false;
            $config['rrdgraphs']['bytes_per_second'] = isset($_POST['bytes_per_second']) ? true : false;
            $config['rrdgraphs']['logarithmic'] = isset($_POST['logarithmic']) ? true : false;
            $config['rrdgraphs']['axis'] = isset($_POST['axis']) ? true : false;
            if ($config['rrdgraphs']['axis'] == true) { $config['rrdgraphs']['logarithmic'] = false; }
            $config['rrdgraphs']['load_averages'] = isset($_POST['load_averages']) ? true : false;
            $config['rrdgraphs']['cpu_frequency'] = isset($_POST['cpu_frequency']) ? true : false;
            $config['rrdgraphs']['cpu_temperature'] = isset($_POST['cpu_temperature']) ? true : false;
            $config['rrdgraphs']['disk_usage'] = isset($_POST['disk_usage']) ? true : false;
            $config['rrdgraphs']['lan_load'] = isset($_POST['lan_load']) ? true : false;
            $config['rrdgraphs']['lan_if'] = get_ifname($config['interfaces']['lan']['if']);    // for 'auto' if name
            $config['rrdgraphs']['no_processes'] = isset($_POST['no_processes']) ? true : false;
            $config['rrdgraphs']['cpu_usage'] = isset($_POST['cpu_usage']) ? true : false;
            $config['rrdgraphs']['memory_usage'] = isset($_POST['memory_usage']) ? true : false;
            $config['rrdgraphs']['arc_usage'] = isset($_POST['arc_usage']) ? true : false;
            $config['rrdgraphs']['latency'] = isset($_POST['latency']) ? true : false;
            $config['rrdgraphs']['latency_host'] = !empty($_POST['latency_host']) ? $_POST['latency_host'] : "127.0.0.1";
            $config['rrdgraphs']['latency_interface'] = $_POST['latency_interface'];
            $config['rrdgraphs']['latency_count'] = $_POST['latency_count'];
            $config['rrdgraphs']['latency_parameters'] = !empty($_POST['latency_parameters']) ? $_POST['latency_parameters'] : "";
            $config['rrdgraphs']['ups'] = isset($_POST['ups']) ? true : false;
            $config['rrdgraphs']['ups_at'] = !empty($_POST['ups_at']) ? $_POST['ups_at'] : "identifier@host-ip-address";
            $config['rrdgraphs']['uptime'] = isset($_POST['uptime']) ? true : false;

            $cronjob = array();
            $a_cronjob = &$config['cron']['job'];
            $uuid = isset($config['rrdgraphs']['schedule_uuid']) ? $config['rrdgraphs']['schedule_uuid'] : false;
            if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
            	$a_cronjob[$cnid]['enable'] = true;
            	$a_cronjob[$cnid]['command'] = "{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-update.sh";
            } else {
            	$cronjob['enable'] = true;
            	$cronjob['uuid'] = uuid();
            	$cronjob['desc'] = "RRDGraphs updates every 5 minutes";
            	for ($i = 0; $i <= 55; $i = $i+5) { $cronjob['minute'][] = $i; }
            	$cronjob['hour'] = true;
            	$cronjob['day'] = true;
            	$cronjob['month'] = true;
            	$cronjob['weekday'] = true;
            	$cronjob['all_mins'] = 0;
            	$cronjob['all_hours'] = 1;
            	$cronjob['all_days'] = 1;
            	$cronjob['all_months'] = 1;
            	$cronjob['all_weekdays'] = 1;
            	$cronjob['who'] = 'root';
            	$cronjob['command'] = "{$config['rrdgraphs']['storage_path']}rrdgraphs/rrd-update.sh";
                $config['rrdgraphs']['schedule_uuid'] = $cronjob['uuid'];
            }
            if (isset($uuid) && (FALSE !== $cnid)) {
//            		$a_cronjob[$cnid] = $cronjob;
            		$mode = UPDATENOTIFY_MODE_MODIFIED;
            	} else {
            		$a_cronjob[] = $cronjob;
            		$mode = UPDATENOTIFY_MODE_NEW;
            	}
            updatenotify_set("cronjob", $mode, $cronjob['uuid']);

            $savemsg = get_std_save_message(write_config());
//            header("Location: rrdgraphs.php");

    		$retval = 0;
    		if (!file_exists($d_sysrebootreqd_path)) {
    			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
    			config_lock();
    			$retval |= rc_update_service("cron");
    			config_unlock();
    		}
    		$savemsg = get_std_save_message($retval);
    		if ($retval == 0) {
    			updatenotify_delete("cronjob");
    		}

            require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
        }   // end of enable extension
		else { 
            $config['rrdgraphs']['enable'] = isset($_POST['enable']) ? true : false;

            $a_cronjob = &$config['cron']['job'];
            $uuid = isset($config['rrdgraphs']['schedule_uuid']) ? $config['rrdgraphs']['schedule_uuid'] : false;
            if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
            	$a_cronjob[$cnid]['enable'] = false;
            } 
            if (isset($uuid) && (FALSE !== $cnid)) {
        		$mode = UPDATENOTIFY_MODE_MODIFIED;

                updatenotify_set("cronjob", $mode, $cronjob['uuid']);
    
        		$retval = 0;
        		if (!file_exists($d_sysrebootreqd_path)) {
        			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
        			config_lock();
        			$retval |= rc_update_service("cron");
        			config_unlock();
        		}
        		$savemsg = get_std_save_message($retval);
        		if ($retval == 0) {
        			updatenotify_delete("cronjob");
        		}
            } 
            write_config();
            $savemsg .= " ".$config['rrdgraphs']['appname'].gettext(" is now disabled!");
        }   // end of disable extension
    }   // end of empty input_errors
}   // end of SAVE

// reset graphs
if (isset($_POST['reset_graphs']) && $_POST['reset_graphs']) {
    exec("logger rrdgraphs: reseting graphs ...");
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-stop.php");
    $savemsg = gettext("All data from the following statistics have been deleted:");
    if (isset($_POST['uptime']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/uptime.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/uptime.rrd");
        exec("logger rrdgraphs: reseting uptime graphs");
        $savemsg .= "<br />- ".gettext("Uptime"); }
    if (isset($_POST['load_averages']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/cpu_usage.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/cpu_usage.rrd");
        exec("logger rrdgraphs: reseting load averages graphs");
        $savemsg .= "<br />- ".gettext("Load averages"); }
    if (isset($_POST['no_processes']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/processes.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/processes.rrd");
        exec("logger rrdgraphs: reseting processes graphs");
        $savemsg .= "<br />- ".gettext("Processes"); }
    if (isset($_POST['cpu_usage']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/cpu.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/cpu.rrd");
        exec("logger rrdgraphs: reseting cpu usage graphs");
        $savemsg .= "<br />- ".gettext("CPU usage"); }
    if (isset($_POST['cpu_frequency']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/cpu_freq.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/cpu_freq.rrd"); 
        exec("logger rrdgraphs: reseting cpu frequency graphs");
        $savemsg .= "<br />- ".gettext("CPU frequency"); }
    if (isset($_POST['cpu_temperature']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/cpu_temp.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/cpu_temp.rrd"); 
        exec("logger rrdgraphs: reseting cpu temperature graphs");
        $savemsg .= "<br />- ".gettext("CPU temperature"); }
    if (isset($_POST['disk_usage'])) { mwexec("rm {$config['rrdgraphs']['rootfolder']}rrd/mnt_*.rrd", true);
        exec("logger rrdgraphs: reseting disk_usage graphs");
        $savemsg .= "<br />- ".gettext("Disk usage"); }
    if (isset($_POST['memory_usage']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/memory.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/memory.rrd");
        exec("logger rrdgraphs: reseting memory usage graphs");
        $savemsg .= "<br />- ".gettext("Memory"); }
    if (isset($_POST['arc_usage']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/arc.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/arc.rrd");
        exec("logger rrdgraphs: reseting ZFS arc usage graphs");
        $savemsg .= "<br />- ".gettext("ZFS ARC"); }
    if (isset($_POST['lan_load']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/em0.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/em0.rrd"); 
        exec("logger rrdgraphs: reseting network traffic graphs");
        $savemsg .= "<br />- ".gettext("Network traffic"); }
    if (isset($_POST['latency']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/latency.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/latency.rrd");
        exec("logger rrdgraphs: reseting latency graphs");
        $savemsg .= "<br />- ".gettext("Latency"); }
    if (isset($_POST['ups']) && is_file("{$config['rrdgraphs']['rootfolder']}rrd/ups.rrd")) { unlink("{$config['rrdgraphs']['rootfolder']}rrd/ups.rrd");
        exec("logger rrdgraphs: reseting UPS graphs");
        $savemsg .= "<br />- ".gettext("UPS"); }
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
}

$pconfig['enable'] = isset($config['rrdgraphs']['enable']) ? true : false;
$pconfig['storage_path'] = !empty($config['rrdgraphs']['storage_path']) ? $config['rrdgraphs']['storage_path'] : "/var/run/";
$pconfig['graph_h'] = !empty($config['rrdgraphs']['graph_h']) ? $config['rrdgraphs']['graph_h'] : 200;
$pconfig['refresh_time'] = !empty($config['rrdgraphs']['refresh_time']) ? $config['rrdgraphs']['refresh_time'] : 300;
$pconfig['background_black'] = isset($config['rrdgraphs']['background_black']) ? true : false;
$pconfig['bytes_per_second'] = isset($config['rrdgraphs']['bytes_per_second']) ? true : false;
$pconfig['logarithmic'] = isset($config['rrdgraphs']['logarithmic']) ? true : false;
$pconfig['axis'] = isset($config['rrdgraphs']['axis']) ? true : false;
// available graphs
$pconfig['uptime'] = isset($config['rrdgraphs']['uptime']) ? true : false;
$pconfig['load_averages'] = isset($config['rrdgraphs']['load_averages']) ? true : false;
$pconfig['no_processes'] = isset($config['rrdgraphs']['no_processes']) ? true : false;
$pconfig['cpu_usage'] = isset($config['rrdgraphs']['cpu_usage']) ? true : false;
$pconfig['cpu_frequency'] = isset($config['rrdgraphs']['cpu_frequency']) ? true : false;
$pconfig['cpu_temperature'] = isset($config['rrdgraphs']['cpu_temperature']) ? true : false;
$pconfig['disk_usage'] = isset($config['rrdgraphs']['disk_usage']) ? true : false;
$pconfig['memory_usage'] = isset($config['rrdgraphs']['memory_usage']) ? true : false;
$pconfig['arc_usage'] = isset($config['rrdgraphs']['arc_usage']) ? true : false;
$pconfig['lan_load'] = isset($config['rrdgraphs']['lan_load']) ? true : false;
$pconfig['latency'] = isset($config['rrdgraphs']['latency']) ? true : false;
$pconfig['latency_host'] = !empty($config['rrdgraphs']['latency_host']) ? $config['rrdgraphs']['latency_host'] : "127.0.0.1";
$pconfig['latency_interface'] = !empty($config['rrdgraphs']['latency_interface']) ? $config['rrdgraphs']['latency_interface'] : "identifier@host-ip-address";
$pconfig['latency_count'] = !empty($config['rrdgraphs']['latency_count']) ? $config['rrdgraphs']['latency_count'] : "3";
$pconfig['latency_parameters'] = !empty($config['rrdgraphs']['latency_parameters']) ? $config['rrdgraphs']['latency_parameters'] : "";
$pconfig['ups'] = isset($config['rrdgraphs']['ups']) ? true : false;
$pconfig['ups_at'] = !empty($config['rrdgraphs']['ups_at']) ? $config['rrdgraphs']['ups_at'] : "identifier@host-ip-address";

$a_interface = get_interface_list();
// Add VLAN interfaces (from user Vasily1)
if (isset($config['vinterfaces']['vlan']) && is_array($config['vinterfaces']['vlan']) && count($config['vinterfaces']['vlan'])) {
   foreach ($config['vinterfaces']['vlan'] as $vlanv) {
      $a_interface[$vlanv['if']] = $vlanv;
      $a_interface[$vlanv['if']]['isvirtual'] = true;
   }
}
// Add LAGG interfaces (from user Vasily1)
if (isset($config['vinterfaces']['lagg']) && is_array($config['vinterfaces']['lagg']) && count($config['vinterfaces']['lagg'])) {
   foreach ($config['vinterfaces']['lagg'] as $laggv) {
      $a_interface[$laggv['if']] = $laggv;
      $a_interface[$laggv['if']]['isvirtual'] = true;
   }
}

// Use first interface as default if it is not set.
if (empty($pconfig['latency_interface']) && is_array($a_interface)) $pconfig['latency_interface'] = key($a_interface);

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-rrd"); ?>
<!-- The Spinner Elements -->
<?php include("ext/rrdgraphs/spinner.inc");?>
<script src="ext/rrdgraphs/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->

<script type="text/javascript">
<!--
function lan_change() {
	switch(document.iform.lan_load.checked) {
		case true:
			showElementById('bytes_per_second_tr','show');
			showElementById('logarithmic_tr','show');
			showElementById('axis_tr','show');
			break;

		case false:
			showElementById('bytes_per_second_tr','hide');
			showElementById('logarithmic_tr','hide');
			showElementById('axis_tr','hide');
			break;
	}
}

function latency_change() {
	switch(document.iform.latency.checked) {
		case true:
			showElementById('latency_host_tr','show');
			showElementById('latency_interface_tr','show');
			showElementById('latency_count_tr','show');
			showElementById('latency_parameters_tr','show');
			showElementById('latency_interface_cell','show');
			showElementById('latency_interface_table','show');
			break;

		case false:
			showElementById('latency_host_tr','hide');
			showElementById('latency_interface_tr','hide');
			showElementById('latency_count_tr','hide');
			showElementById('latency_parameters_tr','hide');
			showElementById('latency_interface_cell','hide');
			showElementById('latency_interface_table','hide');
			break;
	}
}

function ups_change() {
	switch(document.iform.ups.checked) {
		case true:
			showElementById('ups_at_tr','show');
			break;

		case false:
			showElementById('ups_at_tr','hide');
			break;
	}
}

function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
	document.iform.storage_path.disabled = endis;
	document.iform.storage_pathbrowsebtn.disabled = endis;
	document.iform.graph_h.disabled = endis;
	document.iform.refresh_time.disabled = endis;
	document.iform.background_black.disabled = endis;
	document.iform.bytes_per_second.disabled = endis;
	document.iform.logarithmic.disabled = endis;
	document.iform.axis.disabled = endis;
	document.iform.reset_graphs.disabled = endis;
	document.iform.uptime.disabled = endis;
	document.iform.load_averages.disabled = endis;
	document.iform.no_processes.disabled = endis;
	document.iform.cpu_usage.disabled = endis;
	document.iform.cpu_frequency.disabled = endis;
	document.iform.cpu_temperature.disabled = endis;
	document.iform.memory_usage.disabled = endis;
	document.iform.arc_usage.disabled = endis;
	document.iform.disk_usage.disabled = endis;
	document.iform.lan_load.disabled = endis;
	document.iform.latency.disabled = endis;
	document.iform.latency_host.disabled = endis;
	document.iform.latency_interface.disabled = endis;
	document.iform.latency_count.disabled = endis;
	document.iform.latency_parameters.disabled = endis;
	document.iform.ups.disabled = endis;
	document.iform.ups_at.disabled = endis;
}
//-->
</script>
<form action="rrdgraphs.php" method="post" name="iform" id="iform" onsubmit="spinner()">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabact"><a href="rrdgraphs.php"><span><?=gettext("Configuration");?></span></a></li>
			<li class="tabinact"><a href="rrdgraphs_update_extension.php"><span><?=gettext("Extension Maintenance");?></span></a></li>
		</ul>
	</td></tr>
    <tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
        	<?php html_titleline_checkbox("enable", $config['rrdgraphs']['appname'], $pconfig['enable'], gettext("Enable"), "enable_change(false)");?>
			<?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s."), $config['rrdgraphs']['rootfolder']));?>
			<?php html_filechooser("storage_path", gettext("Working directory"), $pconfig['storage_path'], gettext("The working directory which will be used during the runtime of RRDGraphs. This should be set to a <b>SSD</b> (preferably) or <b>RAM disk</b> to prevent a disk spinning all the time.<br /><b><font color='red'>CAUTION:</font> The use of a RAM disk could lead to the loss of statistic data in case of a system crash or not graceful shutdown of a system!</b><br />Default is /var/run which is on a RAM disk on embedded installations."), $g['media_path'], false, 60);?>
            <?php html_inputbox("graph_h", gettext("Graphs height"), $pconfig['graph_h'], sprintf(gettext("Height of the graphs. Default is 200 pixel."), 200), false, 5);?>
            <?php html_inputbox("refresh_time", gettext("Refresh time"), $pconfig['refresh_time'], gettext("Refresh time for graph pages.")." ".sprintf(gettext("Default is %s %s."), 300, gettext("seconds")), false, 5);?>
            <?php html_checkbox("background_black", gettext("Graphs background"), $pconfig['background_black'], gettext("Black background for graphs."), "", false);?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Available graphs"));?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("cpu_frequency", gettext("CPU frequency")." - ".gettext("Statistics for CPU frequency."), $pconfig['cpu_frequency'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("cpu_temperature", gettext("CPU temperature")." - ".gettext("Statistics for CPU temperature."), $pconfig['cpu_temperature'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("cpu_usage", gettext("CPU usage")." - ".gettext("Displays a percentage of time spent in each of the processor states (user, nice, system, interrupt, idle)."), $pconfig['cpu_usage'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("disk_usage", gettext("Disk usage")." - ".gettext("Statistics for disk (mount point) usage."), $pconfig['disk_usage'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("load_averages", gettext("Load averages")." - ".gettext("Statistics for average system load for 1, 5 and 15 minute periods."), $pconfig['load_averages'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("memory_usage", gettext("Memory")." - ".gettext("Displays information about memory allocation (active, inact, wired, cache, buf, free, swap used, swap free)."), $pconfig['memory_usage'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("latency", gettext("Network latency")." - ".gettext("Statistics for network latency."), $pconfig['latency'], gettext("Enable"), "latency_change()");?>
            <?php html_inputbox("latency_host", gettext("Host"), $pconfig['latency_host'], gettext("Destination host name or IP address."), false, 20);?>
			<tr>
				<td id="latency_interface_cell" valign="top" class="vncell"><?=gettext("Interface selection");?></td>
				<td id="latency_interface_table" class="vtable">
				<select name="latency_interface" class="formfld" id="xif">
					<?php foreach($a_interface as $if => $ifinfo):?>
						<?php $ifinfo = get_interface_info($if); if (("up" == $ifinfo['status']) || ("associated" == $ifinfo['status'])):?>
						<option value="<?=$if;?>"<?php if ($if == $pconfig['latency_interface']) echo "selected=\"selected\"";?>><?=$if?></option>
						<?php endif;?>
					<?php endforeach;?>
				</select>
				<br /><?=gettext("Select the interface (only selectable if your server has more than one) to use for the source IP address in outgoing packets.");?>
				</td>
			</tr>
            <?php $latency_a_count = array(); for ($i = 1; $i <= 20; $i++) { $latency_a_count[$i] = $i; }?>
            <?php html_combobox("latency_count", gettext("Count"), $pconfig['latency_count'], $latency_a_count, gettext("Stop after sending (and receiving) N packets."), false);?>
            <?php html_inputbox("latency_parameters", gettext("Auxiliary parameters"), $pconfig['latency_parameters'], gettext("These parameters will be added to the ping command.")." ".sprintf(gettext("Please check the <a href='%s' target='_blank'>documentation</a>."), "http://www.freebsd.org/cgi/man.cgi?query=ping&amp;apropos=0&amp;sektion=0&amp;format=html"), false, 60);?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("lan_load", gettext("Network traffic")." - ".gettext("Statistics for incoming/outgoing bits/second for network interfaces."), $pconfig['lan_load'], gettext("Enable"), "lan_change()");?>
            <?php html_checkbox("bytes_per_second", gettext("Bytes/sec"), $pconfig['bytes_per_second'], gettext("If enabled the network throughput is displayed in Bytes/sec rather than Bits/sec."), "", false);?>
            <?php html_checkbox("logarithmic", gettext("Logarithmic scaling"), $pconfig['logarithmic'], sprintf(gettext("Logarithmic y-axis scaling for %s graphs (can not be used together with positive/negative y-axis range)."), gettext("Network traffic")), "", false);?>
            <?php html_checkbox("axis", gettext("Y-axis range"), $pconfig['axis'], sprintf(gettext("Show positive/negative values for %s graphs (can not be used together with logarithmic scaling)."), gettext("Network traffic")), "", false);?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("no_processes", gettext("Processes")." - ".gettext("Displays the number of processes in each state (total, running, sleeping, waiting, starting, stopped, zombie)."), $pconfig['no_processes'], gettext("Enable"), "");?>
			<?php html_separator();?>
        	<?php html_titleline_checkbox("ups", gettext("UPS")." - ".gettext("Statistics for UPS battery capacity, voltage, load, remaining runtime, input voltage and the UPS status (online, on battery, charging and offline)."), $pconfig['ups'], gettext("Enable"), "ups_change()");?>
            <?php html_inputbox("ups_at", gettext("UPS identifier and IP address"), $pconfig['ups_at'], gettext("UPS identifier and host IP address of the machine where the UPS is connected to (this can be also a remote host).")." ".gettext("UPS identifier and IP address")." ".sprintf(gettext("must be in the format: %s."), "identifier@host-ip-address"), false, 60);?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("uptime", gettext("Uptime")." - ".gettext("Displays the length of time the system has been up."), $pconfig['uptime'], gettext("Enable"), "");?>
			<?php html_separator();?>
            <?php html_titleline_checkbox("arc_usage", gettext("ZFS ARC")." - ".gettext("Statistics for ZFS ARC (total, MRU, MFU, anon, header, other)."), $pconfig['arc_usage'], gettext("Enable"), "");?>
        </table>
        <div id="remarks">
            <?php html_remark("note", gettext("Note"), sprintf(gettext("%s deletes all the data for selected statistics. If only a certain statistic needs to be reset, clear all other check boxes before performing '%s'."), gettext("Reset graphs"), gettext("Reset graphs"))); ?>
        </div>
        <div id="submit">
			<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext("Save and Restart");?>"/>
            <input id="reset_graphs" name="reset_graphs" type="submit" class="formbtn" value="<?=gettext("Reset graphs");?>" onclick="return confirm('<?=gettext("Do you really want to delete all data from the selected statistics?");?>')" />
    </div>
	</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
lan_change();
latency_change();
ups_change();
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
