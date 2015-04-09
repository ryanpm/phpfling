<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$filename = md5($_POST['file']);
$file_destination = $config['data_path'] .'../.cache/'.$filename;
$fl->fileDownload($_POST['file'], $file_destination );

ob_clean();
