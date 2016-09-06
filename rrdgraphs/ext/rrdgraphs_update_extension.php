<?php
/*
    rrdgraphs_update_extension.php
    
    Copyright (c) 2015 - 2016 Andreas Schmidhuber, RRDTool portion by Snunn1
    All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
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
$pgtitle = array(gettext("Extensions"), $config['rrdgraphs']['appname']." ".$config['rrdgraphs']['version'], gettext("Maintenance"));

if (is_file("{$config['rrdgraphs']['updatefolder']}oneload")) {
    require_once("{$config['rrdgraphs']['updatefolder']}oneload");
}

$return_val = mwexec("fetch -o {$config['rrdgraphs']['updatefolder']}version.txt https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdgraphs/version.txt", true);
if ($return_val == 0) { 
    $server_version = exec("cat {$config['rrdgraphs']['updatefolder']}version.txt"); 
    if ($server_version != $config['rrdgraphs']['version']) { $savemsg = sprintf(gettext("New extension version %s available, push '%s' button to install the new version!"), $server_version, gettext("Update Extension")); }
    mwexec("fetch -o {$config['rrdgraphs']['rootfolder']}release_notes.txt https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdgraphs/release_notes.txt", false);
}
else { $server_version = gettext("Unable to retrieve version from server!"); }

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

if (isset($_POST['ext_remove']) && $_POST['ext_remove']) {
// restore original pages
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-stop.php");
// remove start/stop commands
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
    		if (preg_match('/rrdgraphs/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]);} else{}
		++$i;
		}
	}
	if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/rrdgraphs/', $config['rc']['shutdown']['cmd'][$i])) { unset($config['rc']['shutdown']['cmd'][$i]); } else {}
		++$i;
		}
	}
// unlink created links and remove extension pages
	if (is_dir ("/usr/local/www/ext/rrdgraphs")) {
    	foreach ( glob( "{$config['rrdgraphs']['rootfolder']}ext/*.php" ) as $file ) {
        	$file = str_replace("{$config['rrdgraphs']['rootfolder']}ext/", "/usr/local/www/", $file);         // trailing backslash !!!
        	if (is_link($file)) unlink($file); 
        }
    	mwexec ("rm -rf /usr/local/www/ext/rrdgraphs");
	}
    if (is_link("/usr/local/share/locale-rrd")) unlink("/usr/local/share/locale-rrd");
	mwexec("rmdir -p /usr/local/www/ext");    // to prevent empty extensions menu entry in top GUI menu if there are no other extensions installed
// remove additional *.php files
	foreach ( glob( "{$config['rrdgraphs']['rootfolder']}files/*.php" ) as $file ) {
        $file = str_replace("{$config['rrdgraphs']['rootfolder']}files/", "/usr/local/www/", $file);    // trailing backslash !!!
        if (is_file($file)) unlink($file);
    }
// remove work directory
	if (is_dir ("{$config['rrdgraphs']['storage_path']}rrdgraphs")) {
    	mwexec ("rm -rf {$config['rrdgraphs']['storage_path']}rrdgraphs");
	}
// remove cronjobs
	if (is_array($config['cron']['job'])) {                                                            // check if cron jobs exists !!!
        $a_cronjob = &$config['cron']['job'];
        $uuid = isset($config['rrdgraphs']['schedule_uuid']) ? $config['rrdgraphs']['schedule_uuid'] : false;
        if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
        	$a_cronjob[$cnid]['enable'] = false;
        }
        if (isset($uuid) && (FALSE !== $cnid)) {
    		$mode = UPDATENOTIFY_MODE_DIRTY;
    
            updatenotify_set("cronjob", $mode, $uuid);
    
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
	}
// remove application section from config.xml
	if ( is_array($config['rrdgraphs'] ) ) { unset( $config['rrdgraphs'] ); write_config();}
	header("Location:index.php");
}

if (isset($_POST['ext_update']) && $_POST['ext_update']) {
// download installer & install
    $return_val = mwexec("fetch -vo {$config['rrdgraphs']['rootfolder']}rrd-install.php 'https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdgraphs/rrd-install.php'", true);
    if ($return_val == 0) {
//        require_once("{$config['rrdgraphs']['rootfolder']}rrd-stop.php");         // stop is execute in rrd-install now (v0.3.2)
        if (is_link("/usr/local/share/locale-rrd")) unlink("/usr/local/share/locale-rrd");
        require_once("{$config['rrdgraphs']['rootfolder']}rrd-install.php"); 
//        require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");        // start is execute in rrd-install now (v0.3.2)
        header("Refresh:8");;
    }
    else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "{$config['rrdgraphs']['rootfolder']}rrd-install.php"); }
}

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-rrd"); ?>
<!-- The Spinner Elements -->
<?php include("ext/rrdgraphs/spinner.inc");?>
<script src="ext/rrdgraphs/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->

<form action="rrdgraphs_update_extension.php" method="post" name="iform" id="iform" onsubmit="spinner()">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
			<li class="tabinact"><a href="rrdgraphs.php"><span><?=gettext("Configuration");?></span></a></li>
			<li class="tabact"><a href="rrdgraphs_update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
		</ul>
	</td></tr>
	<tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline(gettext("Extension Update"));?>
			<?php html_text("ext_version_current", gettext("Installed version"), $config['rrdgraphs']['version']);?>
			<?php html_text("ext_version_server", gettext("Latest version"), $server_version);?>
			<?php html_separator();?>
        </table>
        <div id="update_remarks">
            <?php html_remark("note_remove", gettext("Note"), gettext("Removing RRDGraphs integration from NAS4Free will leave the installation folder untouched - remove the files using Windows Explorer, FTP or some other tool of your choice. <br /><b>Please note: this page will no longer be available.</b> You'll have to re-run RRDGraphs extension installation to get it back on your NAS4Free."));?>
            <br />
            <input id="ext_update" name="ext_update" type="submit" class="formbtn" value="<?=gettext("Update Extension");?>" onclick="return confirm('<?=gettext("The selected operation will be completed. Please do not click any other buttons!");?>')" />
            <input id="ext_remove" name="ext_remove" type="submit" class="formbtn" value="<?=gettext("Remove Extension");?>" onclick="return confirm('<?=gettext("Do you really want to remove the extension from the system?");?>')" />
        </div>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_separator();?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Extension")." ".gettext("Release Notes"));?>
			<tr>
                <td class="listt">
                    <div>
                        <textarea style="width: 98%;" id="content" name="content" class="listcontent" cols="1" rows="25" readonly="readonly"><?php unset($lines); exec("/bin/cat {$config['rrdgraphs']['rootfolder']}release_notes.txt", $lines); foreach ($lines as $line) { echo $line."\n"; }?></textarea>
                    </div>
                </td>
			</tr>
        </table>
        <?php include("formend.inc");?>
    </td></tr>
</table>
</form>
<?php include("fend.inc");?>
