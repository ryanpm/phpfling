<?php 

ob_start();

$data_path = 'C:/PROJECTS/Manalastas/_sync/test/.phpvs/';
$source_path = 'C:/PROJECTS/Manalastas/Test/';

$config = json_decode(str_replace("\n", '',file_get_contents($data_path."sync/cnf")),true);

define("SYSTEM_PATH", dirname(__FILE__)."/../" );
define("SOURCE_PATH", Tools::appendSlash( $source_path ) );

function __autoload($class){
    include_once(SYSTEM_PATH."lib/$class.php");
}

PhpSync::$SYNC_DATA_PATH = $data_path;
PhpSync::$SYNC_SOURCE_PATH = '';

$fl = new PhpSync();
$modified = $fl->showModifed();

var_dump($fl->getFiles());
var_dump(Tools::getFiles( $fl->source_path ,'dir_file'));

$add_files = array_diff_key( Tools::getFiles( $fl->source_path ,'dir_file') ,$fl->getFiles());


print_r($add_files);


// ob_clean();

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
			
		/*]]>*/
		</script>
			
</head>
<body>

</body>
</html>
<h2>Modified</h2>

<input type="button" id="upload-all" value="Upload All" />
<br/><br/>
<?php foreach ($modified  as $file => $stats): ?>
	<input type="checkbox" class="uploadable"  data-file="<?php echo $file ?>" /><?php echo $file ?><br/>
<?php endforeach ?>

<br/><br/>
<h2>New Files</h2>
<br/><br/>
<?php foreach ($add_files  as $file ): ?>
	<input type="checkbox" class="uploadable"  data-file="<?php echo $file ?>" /><?php echo $file ?><br/>
<?php endforeach ?>
