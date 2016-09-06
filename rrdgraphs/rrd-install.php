<?php
/* 
    rrd-install.php
     
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

$v = "v0.3.3-b4";                                  // extension version
$appname = "RRDGraphs";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
// no check necessary since the extension is for all archictectures/platforms/releases
//if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2")) { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
//if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}rrd")) { mkdir("{$install_dir}rrd", 0775, true); }
if (!is_dir("{$install_dir}backup")) { mkdir("{$install_dir}backup", 0775, true); }
if (!is_dir("{$install_dir}update")) { mkdir("{$install_dir}update", 0775, true); }

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";
// create stripped version name 
$vs = str_replace(".", "", $v);
$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}master.zip 'https://github.com/crestAT/nas4free-rrdtool/releases/download/{$v}/rrdgraphs-{$vs}.zip'", true);
if ($return_val == 0) {
    $return_val = mwexec("tar -xf {$install_dir}master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        exec("rm {$install_dir}master.zip");
        exec("chmod -R 775 {$install_dir}");
        if (is_file("{$install_dir}version.txt")) { $file_version = exec("cat {$install_dir}version.txt"); }
        else { $file_version = "n/a"; }
        $savemsg = sprintf(gettext("Update to version %s completed!"), $file_version);
    }
    else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip corrupt /"); }
}
else { $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip"); }

// create all necessary params  
$rrdgraphs = array();
if (isset($config['rrdgraphs']) && is_array($config['rrdgraphs'])) {
    $rrdgraphs = $config['rrdgraphs'];                                                // take existings params
    if (isset($config['rrdgraphs']['enable'])) include_once("{$config['rrdgraphs']['rootfolder']}rrd-stop.php");
} 
$rrdgraphs['appname'] = $appname;
$rrdgraphs['version'] = exec("cat {$install_dir}version.txt");                        // extension version
$rrdgraphs['product_version'] = "-----";                                              // NAS4Free version e.g. 10.2.0.2-2268
$rrdgraphs['osrelease'] = $release[0];                                                // OS release e.g. 9.3 or 10.2
if ($arch == "i386" || $arch == "x86") $rrdgraphs['architecture'] = "x86";            // x86
elseif ($arch == "amd64" || $arch == "x64") $rrdgraphs['architecture'] = "x64";       // x64
    else $rrdgraphs['architecture'] = $arch;                                          // rpi
$rrdgraphs['rootfolder'] = $install_dir;
$rrdgraphs['backupfolder'] = $rrdgraphs['rootfolder']."backup/";
$rrdgraphs['updatefolder'] = $rrdgraphs['rootfolder']."update/";
$i = 0;
if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
    for ($i; $i < count($config['rc']['postinit']['cmd']);) {
        if (preg_match('/rrdgraphs/', $config['rc']['postinit']['cmd'][$i])) break;
        ++$i;
    }
}
$config['rc']['postinit']['cmd'][$i] = $rrdgraphs['rootfolder']."rrd_start.php";
$i =0;
if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
    for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
        if (preg_match('/rrdgraphs/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
}
$config['rc']['shutdown']['cmd'][$i] = $rrdgraphs['rootfolder']."rrd_stop.php";
if (is_link("/usr/local/share/locale-rrd")) unlink("/usr/local/share/locale-rrd");

// install application on server
if ( !isset($config['rrdgraphs']) || !is_array($config['rrdgraphs'])) {
    $config['rrdgraphs'] = array();
    $config['rrdgraphs'] = $rrdgraphs;
    write_config();
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
    echo "\n".$appname." Version ".$config['rrdgraphs']['version']." installed";
    echo "\n\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure \nthe application (don't forget to refresh the WebGUI before use)!\n";
}
else {
    $config['rrdgraphs'] = $rrdgraphs;
    write_config();
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
}
?>
