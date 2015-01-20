#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$return_val = mwexec("fetch {$verify_hostname} -vo rrdtool/rrd-install.php 'fetch https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdtool/rrd-install.php'", true);
if ($return_val == 0) { require_once("rrdtool/rrd-install.php"); }
else { echo "\nInstallation file 'rrd-install.php' not found, installation aborted!" }
?>
