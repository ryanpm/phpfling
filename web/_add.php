<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$files = $fl->getFiles();
if( !isset( $files[$_POST['file']] ) ){
	$result = $fl->appendFiles([ $_POST['file'] => $fl->getEmptyStats($_POST['file']) ]);
}
$fl->rs('close_all');

ob_clean();
echo json_encode(['success'=>$result]);