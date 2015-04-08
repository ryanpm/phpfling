<?php 

function getModifiedFiles($g){
	$lines = explode("\n", $g);
	$modified_files = [];
	foreach ($lines as $line) {

		$line = trim($line);
		if( strpos($line, 'modified:') === 0 ){
			list($type, $file) = explode("  ", $line);
			$modified_files[] = trim($file);
		}

	}
	return $modified_files;
}

// var_dump(realpath('../../../Manalastas/Test'));
ob_start();
$s = system('git status ../');

// $s = system('git show ../');
// $s = system('git show "../../../Manalastas/Test"');
// echo nl2br($s);
$g = ob_get_clean();

// echo nl2br($g);

$modified = getModifiedFiles($g);

echo "Modified files:<br/>";
foreach ($modified as $file) {
	echo $file."<br/>";
}
