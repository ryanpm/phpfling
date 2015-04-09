<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$result = $fl->uploadFile($_POST['file']);
ob_clean();

echo json_encode(['success'=>$result]);