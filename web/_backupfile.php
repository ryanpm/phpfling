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
	$status['total_dl'] = 0;
	$status['total_files'] = count($files_to_download);

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

				if( !isset($stats['dl']) or (isset($stats['dl']) and $stats['dl'] == 'f') ){
					if( count($files_to_download) < 10 ){
						$files_to_download[$filename] = $stats;
					}
				}

			}

			foreach ($files_to_download as $filename => $stats) {


				$file_destination = $directory.$filename;
			    Tools::makeRecursiveDir($file_destination);

			    if( $fl->ftp->download($filename, $file_destination) ){
					$stats['dl'] = 'o';
			    }else{
					$stats['dl'] = 'f';
			    }

		    	if( !isset($stats['dl_tr']) ){
		    		$stats['dl_tr'] = 0;
		    	}
				$stats['dl_tr']++;

			    if( $stats['dl'] == 'o' or $stats['dl_tr'] > 5  ){
					$status['total_dl']++;
			    }

		    	$status['modified'][$filename] = $stats;
			    $files_to_download[$filename] = $stats;

			}

			$result = true;


	    }


	}else{


	}

}

var_dump($status['total_files']);
if(  $status['total_files'] > 0 ){
	$status['progress'] = ceil(( $status['total_dl'] / $status['total_files'] ) * 100);
}else{
	$status['progress'] = 100;
}

file_put_contents($statusfile, json_encode($status,true));
$fl->rs('close_all');


ob_clean();

echo json_encode(['success'=>$result,'id'=>$datetime,'progress'=>$status['progress']]);
