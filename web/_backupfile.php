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

$directory = $config['data_path'] .'backup/'.$datetime.'/';

$status = [];

$statusfile = $directory .'_status';

if( $processId == 0 ){

	$modified = $fl->showModifed();
	$logfiles = $fl->getFiles();

	// only files that are uploaded
	$files_to_download = [];
	foreach ($modified as $filename => $stats) {
		$log_stats = $logfiles[$filename];
		if( $log_stats['mt'] != 0 ){
			$files_to_download[$filename] = $stats;
		}
	}

	$status['modified'] = $files_to_download;
	$status['progress'] = 0;
	$status['total_processed'] = 0;
	$status['total_files'] = count($files_to_download);
    Tools::makeRecursiveDir($statusfile);

}else{

	$status = json_decode(file_get_contents($statusfile), true);

}

$type = $fl->getProtocol();
$result = false;
if( $status['progress'] < 100 ){

	if( $type == PhpSync::FTP or $type == PhpSync::SFTP ){

	    if( $fl->ftpLog() ){

			$files_to_download = [];

	    	foreach ($status['modified'] as $filename => $stats) {

				if( !isset($stats['_s']) or (isset($stats['_s']) and $stats['_s'] == 'f') ){
					if( count($files_to_download) < 10 ){
						$files_to_download[$filename] = $stats;
					}
				}

			}

			foreach ($files_to_download as $filename => $stats) {


				$file_destination = $directory.$filename;
			    Tools::makeRecursiveDir($file_destination);

			    if( $fl->ftp->download($filename, $file_destination) ){
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

			    }

		    	$status['modified'][$filename] = $stats;
			    $files_to_download[$filename] = $stats;

			}

			$result = true;


	    }


	}else{


	}

}

if(  $status['total_files'] > 0 ){
	$status['progress'] = ceil(( $status['total_processed'] / $status['total_files'] ) * 100);
}else{
	$status['progress'] = 100;
}

file_put_contents($statusfile, json_encode($status,true));
$fl->rs('close_all');

ob_clean();

echo json_encode(['success'=>$result,'id'=>$datetime,'progress'=>$status['progress']]);
