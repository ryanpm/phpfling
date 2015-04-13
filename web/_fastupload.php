<?php 

ob_start();

include_once("./init.php");

$processId = (int)$_GET['id'];

$fl = new PhpSync();

if( $processId == 0 ){

	$datetime = time();
	
}else{

	$datetime = $processId;

}

$directory = $config['data_path'] .'uploads/';

$status = [];
$statusfile = $directory .$datetime;

if( $processId == 0 ){

	$modified = $fl->showModifed();
	$status['modified'] = $modified;
	$status['progress'] = 0;
	$status['total_processed'] = 0;
	$status['total_files'] = count($modified);

}else{

	$status = json_decode(file_get_contents($statusfile), true);

}

$type = $fl->getProtocol();
$result = false;
$filesuploaded = [];

$allfiles = $fl->getFiles();

if( $status['progress'] < 100 ){

	if( $type == PhpSync::FTP or $type == PhpSync::SFTP ){

	    if( $fl->ftpLog() ){

			$files_to_upload = [];

	    	foreach ($status['modified'] as $filename => $stats) {

				if( !isset($stats['_s']) or (isset($stats['_s']) and $stats['_s'] == 'f') ){
					if( count($files_to_upload) < 10 ){
						$files_to_upload[$filename] = $stats;
					}
				}

			}

			foreach ($files_to_upload as $filename => $stats) {

			    if ( $fl->ftp->upload($filename, $fl->source_path . $filename) ){
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
			    	$filesuploaded[] = md5($filename);
					$allfiles[$filename] = $stats;
			    	
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

$fl->updateLogFiles($allfiles);

$fl->rs('close_all');


ob_clean();

echo json_encode(['success'=>$result,'id'=>$datetime,'progress'=>$status['progress'],'files'=>$filesuploaded]);
