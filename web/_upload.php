<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();

$result = $fl->uploadFile($_POST['file']);

$stats = $fl->getCurrentStats($_POST['file']);

$allfiles = $fl->getFiles();
$allfiles[$_POST['file']] = $stats;
$fl->updateLogFiles($allfiles);

$fl->rs('close_all');

// $uploaded_file[ $_POST['file'] ] =  $stats;
// $fl->updateModFiles(  $uploaded_file );
// $fl->getFiles('upd');
// print_r($fl->files);
// $fl->appendFiles($uploaded_file,'upd');

ob_clean();
echo json_encode(['success'=>$result]);