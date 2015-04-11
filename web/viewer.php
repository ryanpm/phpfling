<?php 

$file = urldecode($_GET['file']);
$pathinfo = pathinfo($file);
if( in_array($pathinfo['extension'], ['jpg','gif','png','jpeg']) ){
	readfile($file);
	exit;
}

?>
<pre><?php readfile($file); ?></pre>

