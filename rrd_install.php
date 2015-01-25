#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$dirname = dirname(__FILE__);
if (!is_dir("{$dirname}/rrdgraphs")) { mkdir("{$dirname}/rrdgraphs", 0775); }
$return_val = mwexec("fetch {$verify_hostname} -vo {$dirname}/rrdgraphs/rrd-install.php 'https://raw.github.com/crestAT/nas4free-rrdtool/master/rrdgraphs/rrd-install.php'", true);
#$return_val=0;
if ($return_val == 0) { 
    chmod("{$dirname}/rrdgraphs/rrd-install.php", 0775);
    require_once("{$dirname}/rrdgraphs/rrd-install.php"); 
}
else { echo "\nInstallation file 'rrd-install.php' not found, installation aborted!"; }
?>
