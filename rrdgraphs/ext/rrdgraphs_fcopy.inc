<?php
/*
    rrdgraphs_fcopy.inc

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
// files which will be used/replaced by rrdgraphs (file name, original location)
$files = array(
//-------------------------------> /usr/local/www/
  // for Status | Graph page extensions
    array("graph.php","/usr/local/www/"),
    array("graph_cpu.php","/usr/local/www/"),
    array("status_graph.php","/usr/local/www/"),
    array("status_graph_cpu.php","/usr/local/www/"),
);

$backup_path = $config['rrdgraphs']['rootfolder']."backup/";
$extend_path = $config['rrdgraphs']['rootfolder']."files/";

function rrdg_copy_origin2backup ($files, $backup_path, $extend_path) {
    foreach ($files as $file) {
        if (is_file($file[1].$file[0])) {
            if (! copy($file[1].$file[0], $backup_path.$file[0])) {
                $msg="failed to copy $file[0] from $file[1] to $backup_path"; 
                exec ('logger rrdgraphs: '.$msg); $error_msg[]=$msg;
            }
        }
    }
    return $error_msg;
}                                                                              

function rrdg_copy_backup2origin ($files, $backup_path, $extend_path) {
    foreach ($files as $file) {
        if (is_file($backup_path.$file[0])) {
            if (! copy($backup_path.$file[0], $file[1].$file[0])) {
                $msg="failed to copy $file[0] from $backup_path to $file[1]";
                exec ('logger rrdgraphs: '.$msg); $error_msg[]=$msg;
            }
        }
    }
    return $error_msg;
}

function rrdg_copy_extended2origin ($files, $backup_path, $extend_path) {
    foreach ($files as $file) {
        if (! copy($extend_path.$file[0], $file[1].$file[0])) {
            $msg="failed to copy $file[0] from $extend_path to $file[1]"; 
            exec ('logger rrdgraphs: '.$msg); $error_msg[]=$msg;
        }
    }
    return $error_msg;
}
?>
