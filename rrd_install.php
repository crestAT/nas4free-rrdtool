#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$install_dir = dirname(__FILE__);                           // get directory where the installer script resides
if (!is_dir("{$install_dir}/rrdgraphs")) { mkdir("{$install_dir}/rrdgraphs", 0775, true); }

$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}/rrdgraphs/rrd-install.php 'https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdgraphs/rrd-install.php'", true);
if ($return_val == 0) { 
    chmod("{$install_dir}/rrdgraphs/rrd-install.php", 0775);
    require_once("{$install_dir}/rrdgraphs/rrd-install.php"); 
}
else { echo "\nInstallation file 'rrd-install.php' not found, installation aborted!"; }
?>
