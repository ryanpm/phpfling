<?php

include_once("./init.php");

$remote  = $config['data_path'].'cache/'.md5($_GET['file']);
$local = $config['source_path'].$_GET['file'];


$pathinfo = pathinfo($local);


?>
<!DOCTYPE html>
<html>
<head>
  <title>Compare</title>

  <link rel="stylesheet" type="text/css" href="css/compare.css"></link>
  <style type="text/css">

  table tr td:nth-child(2){
    width: 46%;
  }
  </style>

</head>
<body>

  <b>Remote:</b> <a href="viewer.php?file=<?php echo urlencode($remote) ?>" target="_blank"><?php echo $remote ?></a><br/>
  <b>Local:</b> <a href="viewer.php?file=<?php echo urlencode($local) ?>" target="_blank"><?php echo $local ?></a>
<br/><br/><br/><br/>
  <?php 

  if( in_array($pathinfo['extension'], ['php','js','css','html','tpl']) ){
    include '_compare_text.php';
  }elseif( in_array($pathinfo['extension'], ['jpg','gif','png','jpeg','png']) ){
    include '_compare_img.php';
  }

  ?>


</body>
</html>