<?php 
	
ob_start();

include_once("./init.php");

$fl = new PhpSync();
$modified = $fl->showModifed();
$logfiles = $fl->getFiles();

$add_files = Tools::getFiles( $fl->source_path ,'dir_file');
foreach($add_files as $file){
	$fl->addFile($file);
}

$add_files = array_diff_key($fl->add,$logfiles);
ob_clean();

?>

<!DOCTYPE html>
<html>
<head>
	<title>Sync File</title>
	<link rel="stylesheet" type="text/css" href="css/sync.css">
</head>
<body>
<h2>Modified</h2>

<input type="button" id="upload-selected" value="Upload Selected" />
<input type="button" id="backup-all" value="Remote File Backup(Locally Modified Files Only)" onclick="return backupRemoteFileLocallyModified()" />

<div id="backup-progressbar" style="margin:10px;margin-top:20px;display:none">
	<div  style="width:200px;height:10px;border:1px solid gray;background:lightgray">
		<div id="backup-progress" style="width:0%;height:10px;background:green" ></div>
	</div>
</div>


<div id="modified-logs" style="margin-top:10px">
	Upload Total Files:<span id="upload-total">0</span>	
</div>
<div id="upload-progressbar" style="margin:10px;margin-top:20px;display:none">
	<div  style="width:200px;height:10px;border:1px solid gray;background:lightgray">
		<div id="upload-progress" style="width:0%;height:10px;background:green" ></div>
	</div>
</div>

Total Modified Files: <span id="total-uploadable"><?php echo count($modified) ?></span>
<br/><br/>
<div style="height:300px;display:block;overflow-y:scroll">

<table id="modified-list" width="100%" class="table-list" cellspacing="0" cellspacing="0">
<thead>
	<tr>
		<th width="10"><input type="checkbox" class="select_all" data-table="modified-list" /></th>
		<th width="" style="text-align:left">File</th>
		<th width="50"></th>
		<th width="150"></th>
	</tr>
</thead>
		<tbody>
<?php foreach ($modified  as $file => $stats): 

	if( $stats['t']=='D' ){
		continue;
	}

$syntax_error = false;
$is_new       = false;
$log_stats = $fl->files['log'][$file];
if( $log_stats['mt'] == 0 ){
	$log_stats['s'] = 'A';
	$is_new       = true;
}else{
	$log_stats['s'] = 'M';
}

$pathinfo = pathinfo($file);

if( strtolower($pathinfo['extension']) == 'php' ){
	ob_start();
	system('php '.$fl->source_path.$file.' -ls');
	$lint = ob_get_clean();

	if( strstr($lint,'error') !== false ){
		$syntax_error = true;
	}
}


$actions = [];
if (!$is_new){
	$actions[] = '<a href="#" onclick="return compareFile(this)">Compare</a> ';
}
if (!$syntax_error){
	$actions[] = '<a href="#" onclick="return uploadFile(this)">Upload</a>';
}
	?>
	<tr>
		<td>

			<?php if ($syntax_error): ?>
				<input type="checkbox"  name="files[]"  value="<?php echo $file ?>" disabled="disabled" />
			<?php else: ?>
				<input type="checkbox"  class="uploadable" name="files[]"  value="<?php echo $file ?>" />
			<?php endif ?>
			
		</td>
		<td>
		<?php if ($syntax_error): ?>
			<span style="color:red"  title="<?php echo htmlentities($lint) ?>">[Error]</span>
		<?php endif ?>
			<?php echo $file ?>
		</td>
		<td style="text-align:center"><?php echo $log_stats['s'] ?></td>
		<td style="text-align:center">
			
			<?php echo implode(' | ', $actions) ?>

		</td>
	</tr>
	<?php endforeach ?>
		</tbody>
</table>

	
</div>
<br/><br/>
<h2>New Files</h2>
<input type="button" id="add-selected" value="Add Selected" />

<br/><br/>
<div style="height:300px;display:block;overflow-y:scroll">
<table id="add-list" width="100%" class="table-list" cellspacing="0" cellspacing="0">
<thead>
	<tr>
		<th width="10"><input type="checkbox" class="select_all" data-table="add-list" data-table="add-list" /></th>
		<th width="" style="text-align:left">File</th>
		<th width="150"></th>
	</tr>
</thead>

<tbody>
<?php foreach ($add_files  as $file => $stats ): 

	if( $stats['t']=='D' ){
		continue;
	}

?>
<tr>
	<td><input type="checkbox" class="addable" value="<?php echo $file ?>" /></td>
	<td><?php echo $file ?></td>
	<td style="text-align:center">

		<a href="#" onclick="return addFile(this)">Add</a> 

	</td>
</tr>
<?php endforeach ?>
</tbody>

</table>

</div>

<h2>Back Up History</h2>


<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.3.min.js"> </script>
<script type="text/javascript" src="js/sync.js"> </script>
	 
</body>
</html>
