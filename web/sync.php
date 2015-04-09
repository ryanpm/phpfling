<?php 

ob_start();

include_once("./init.php");

$fl = new PhpSync();
$modified = $fl->showModifed();

$_add_files = array_diff_key( Tools::getFiles( $fl->source_path ,'dir_file') ,$fl->getFiles());
$add_files = array();
foreach ($_add_files as $add_file) {
	if($fl->filter($add_file)){
		$add_files[] = $add_file;
	}
}

ob_clean();

?>

<!DOCTYPE html>
<html>
<head>
	<title></title>

	<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.3.min.js"> </script>
	
		<script type="text/javascript">
		/*<![CDATA[*/
			
			$(function(){

				$('input#upload-all').click(function(){

					$('input.uploadable').each(function(){

						console.log( $(this).attr('data-file') );

					})

				});

			});

			function compareFile (elem) {

				var _data = $(elem).parents('tr');

				$.ajax({
					url:'_compare.php',
					type:'post',
					data:'file='+$('input',_data).val(),
					dataType:'json',
					success:function(json){

					}
				})

				
			}

			
			function uploadFile (elem) {

				var _data = $(elem).parents('tr');

				$.ajax({
					url:'_upload.php',
					type:'post',
					data:'file='+$('input',_data).val(),
					dataType:'json',
					success:function(json){

					}
				})

				
			}



			
		/*]]>*/
		</script>
			
		<style type="text/css">
		a{
			text-decoration: none;
		}
		.table-list{
			border: 1px solid gray;
		}

		.table-list th{
			border: 1px solid white;
		}
		.table-list td{
			border: 1px solid gray;
		}
		.table-list td, .table-list th{
			padding: 5px;
		}

		.table-list thead th{
			background-color: gray;
			color: white;
		}

		</style>

</head>
<body>
<h2>Modified</h2>

<input type="button" id="upload-all" value="Upload Selected" />

<br/><br/>
<table width="600" class="table-list" cellspacing="0" cellspacing="0">
<thead>
	<tr>
		<th width="10"><input type="checkbox"  /></th>
		<th width="" style="text-align:left">File</th>
		<th width="120"></th>
	</tr>
</thead>
	<?php foreach ($modified  as $file => $stats): ?>
		<tbody>
	<tr>
		<td>

			<input type="checkbox" class="uploadable" name="files[]"  value="<?php echo $file ?>" />
			
		</td>
		<td>
			<?php echo $file ?>
		</td>
		<td style="text-align:center">
			
			<a href="#" onclick="return compareFile(this)">Compare</a>
			<a href="#" onclick="return uploadFile(this)">Upload</a>

		</td>
	</tr>
		</tbody>
	<?php endforeach ?>
</table>

<br/><br/>
<h2>New Files</h2>
<input type="button" id="upload-all" value="Add All" />
<input type="button" id="upload-all" value="Add Selected" />

<br/><br/>
<?php foreach ($add_files  as $file ): ?>
	<input type="checkbox" class="uploadable"  data-file="<?php echo $file ?>" /><?php echo $file ?><br/>
<?php endforeach ?>

</body>
</html>
