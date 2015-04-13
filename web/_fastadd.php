<?php 

ob_start();

include_once("./init.php");

$processId = (int)$_GET['id'];
 
$fl = new PhpSync();


if( $processId == 0 ){

	$datetime = 'add-'.time();
	
}else{

	$datetime = $processId;

}

$directory = $config['data_path'] .'cache/';

$status = [];
$statusfile = $directory .$datetime;

if( $processId == 0 ){

	$add_files = Tools::getFiles( $fl->source_path ,'dir_file');
	foreach($add_files as $file){
		$fl->addFile($file);
	}
	$logfiles = $fl->getFiles();
	$add_files = array_diff_key($fl->add,$logfiles);

	$status['addfiles'] = $add_files;
	$status['progress'] = 0;
	$status['total_processed'] = 0;
	$status['total_files'] = count($add_files);

}else{

	$status = json_decode(file_get_contents($statusfile), true);

}

$type = $fl->getProtocol();
$result = false;
$filesadded = [];

$allfiles = $fl->getFiles();

if( $status['progress'] < 100 ){

	if( $type == PhpSync::FTP or $type == PhpSync::SFTP ){

	    if( $fl->ftpLog() ){

			$files_to_add = [];

	    	foreach ($status['addfiles'] as $filename => $stats) {

				if( !isset($stats['_s']) or (isset($stats['_s']) and $stats['_s'] == 'f') ){
					if( count($files_to_add) < 500 ){
						$files_to_add[$filename] = $stats;
					}
				}

			}

			foreach ($files_to_add as $filename => $stats) {

			    if ( $fl->appendFiles([ $filename => $fl->getEmptyStats($filename) ]) ){
					$stats['_s'] = 'o';
			    }else{
					$stats['_s'] = 'f'; // failed
			    }

		    	if( !isset($stats['_a']) ){
		    		$stats['_a'] = 0;
		    	}
				$stats['_a']++;

			    if( $stats['_s'] == 'o' or $stats['_a'] > 5  ){
					$status['total_processed']++;
					if( $stats['_s'] == 'f' ){
						$stats['_s'] = 'fa'; // failed attempts
					}
			    	$filesadded[] = md5($filename);
			    	
			    }

		    	$status['modified'][$filename] = $stats;

			}

			$result = true;

	    }


	}else{


	}

}

var_dump($status['total_files']);
if(  $status['total_files'] > 0 ){
	$status['progress'] = ceil(( $status['total_processed'] / $status['total_files'] ) * 100);
}else{
	$status['progress'] = 100;
}

file_put_contents($statusfile, json_encode($status,true));

$fl->rs('close_all');


ob_clean();

echo json_encode(['success'=>$result,'id'=>$datetime,'progress'=>$status['progress'],'files'=>$filesadded]);
