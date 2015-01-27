<?php
/* 
    rrd-install.php
     
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

$v = "v0.0.1";                          // extension version
$appname = "RRDGraphs";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64")) { echo "unsupported architecture!\n"; exit(1);  }
if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "unsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";
if (isset($config['rrdgraphs']['rootfolder'])) { $install_dir = $config['rrdgraphs']['rootfolder']; }

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

//exit;   //********************************************************************************************************************************

// install application on server
if ( !isset($config['rrdgraphs']) || !is_array($config['rrdgraphs'])) {
    $config['rrdgraphs'] = array();
	$config['rrdgraphs']['appname'] = $appname;
    $config['rrdgraphs']['version'] = exec("cat {$install_dir}version.txt");
    $config['rrdgraphs']['product_version'] = "-----";
	$config['rrdgraphs']['osrelease'] = $release[0];
    if ($arch == "i386" || $arch == "x86") { $config['rrdgraphs']['architecture'] = "x86"; }
    else { $config['rrdgraphs']['architecture'] = "x64"; }
	$config['rrdgraphs']['rootfolder'] = $install_dir;
	$config['rrdgraphs']['backupfolder'] = $config['rrdgraphs']['rootfolder']."backup/";
	$config['rrdgraphs']['updatefolder'] = $config['rrdgraphs']['rootfolder']."update/";
    $i = 0;
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['postinit']['cmd']);) {
            if (preg_match('/rrdgraphs/', $config['rc']['postinit']['cmd'][$i])) break;
            ++$i;
        }
    }
    $config['rc']['postinit']['cmd'][$i] = $config['rrdgraphs']['rootfolder']."rrd_start.php";
    $i =0;
    if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
        for ($i; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/rrdgraphs/', $config['rc']['shutdown']['cmd'][$i])) break; ++$i; }
    }
    $config['rc']['shutdown']['cmd'][$i] = $config['rrdgraphs']['rootfolder']."rrd_stop.php";
    if (!is_dir("{$config['rrdgraphs']['rootfolder']}rrd")) { mkdir("{$config['rrdgraphs']['rootfolder']}rrd", 0775); }
    if (!is_dir("{$config['rrdgraphs']['backupfolder']}")) { mkdir("{$config['rrdgraphs']['backupfolder']}", 0775); }
    if (!is_dir("{$config['rrdgraphs']['updatefolder']}")) { mkdir("{$config['rrdgraphs']['updatefolder']}", 0775); }
    write_config();
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
    echo "\n".$appname." Version ".$config['rrdgraphs']['version']." installed";
    echo "\n\nInstallation completed, use WebGUI | Extensions | ".$appname." to configure \nthe application (don't forget to refresh the WebGUI before use)!\n";
}
else { 
    $config['rrdgraphs']['product_version'] = "-----";
    write_config();
    require_once("{$config['rrdgraphs']['rootfolder']}rrd-start.php");
}
?>
