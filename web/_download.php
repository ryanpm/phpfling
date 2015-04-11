<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$filename = md5($_POST['file']);

$file_destination = $config['data_path'] .'cache/'.$filename;
$result = $fl->fileDownload($_POST['file'], $file_destination );
$fl->rs('close_all');

ob_clean();

echo json_encode(['success'=>$result]);
