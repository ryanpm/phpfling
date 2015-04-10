<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$result = $fl->uploadFile($_POST['file']);
var_dump($config['source_path'].$_POST['file']);

$fl->updateModFiles( [ $_POST['file'] =>  $fl->getFreshStats($_POST['file']) ] );
var_dump(array( $_POST['file'] => $fl->getCurrentStats($_POST['file']) ));
// $fl->updateUploadedFiles(array( $_POST['file'] => $fl->getCurrentStats($_POST['file']) ));

// ob_clean();

echo json_encode(['success'=>$result]);