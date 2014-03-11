<?php

class PhpSync{

    static $g;

    const LOCAL = 0;
    const FTP   = 1;
    const SFTP  = 2;

    public $add;
    public $rs;
    public $files;
    public $conf;

    public $ex_patterns;
    public $inc_pattern;

    public $ftp;
    public $ftp_details;

    public $protocol;
    public $lastupload;

    public $sysfiles;

    public $data_path;
    public $source_path;

    public $reupload;

    public static $SYNC_DATA_PATH;

    static function g($id=''){
        if(!isset(self::$g[$id])){
            self::$g[$id] = new self;
        }
        return self::$g[$id];
    }

    static function init(){

        $fl = new self();
        $fl->execInit();

    }

    static function add(){

        $fl = new self();
        $fl->execAdd();

    }

    static function resync(){

        $fl = new self();
        $fl->execAdd();
        $fl->cleanRemote();
        $fl->execSync();
        $fl->execReset();

    }

    static function sync($opt=array()){

        $fl = new self();
        $fl->execSync($opt);

    }

    static function reset(){
        $fl = new self();
        $fl->execReset();
    }

    function __construct(){

        $this->data_path   = self::$SYNC_DATA_PATH."sync/";

        $base = $this->getConf('source');
        echo $this->source_path = SOURCE_PATH . $base;

    }

    function sysfile($id){
        if( in_array($id,array('cnf','log','mod','upd','new')) ){
            return $this->data_path.$id;
        }
        Tools::msg("Invalid extension $id");
        exit;
    }

    function rs($id,$a='open'){

        Tools::log('Method: '. __METHOD__ ."($id,$a)" );

        if( $id =='close_all' ){
            foreach( array('log','upd','new','mod') as $_id ){
                $this->rs($_id,'close');
            }
            return;
        }

        $file = $this->sysfile($id);
        if($a=='open'){

            if( !isset($this->rs[$id]) ){
                $this->rs[$id] = fopen( $file ,'a+');
            }
            fseek($this->rs[$id],0,SEEK_CUR);
            return $this->rs[$id];

        }elseif($a=='close' or  $a == 'unlink'  ){

             if( isset($this->rs[$id]) ){
                 fclose($this->rs[$id]);
                 unset($this->rs[$id]);
             }
        }

        if(  $a == 'unlink' ){
            if( file_exists($file) ){
                unlink($file);
            }
        }

    }

    function addFile($file){

        Tools::log("add: $file");
        if($this->filter($file)){

            $stats['s']  = 0;
            $stats['mt'] = 0;
            $stats['a']     = 'A';
            $stats['t']     = 'F';
            if( is_dir($this->source_path.$file) ){
                $stats['t']     = 'D';
            }
            $this->add[$file] = $stats;

        }

    }

    function getFiles($id='log'){

        Tools::log('Method: '. __METHOD__ ."($id)" );
        $rs = $this->rs($id);

        Tools::msg("Getting $id files ");
        if( isset($this->files[$id]) and $this->isCache() ){
            Tools::msg(' - done(cached data).','');
            return $this->files[$id];
        }

        $lines = array();
        if( $rs == null ){
            Tools::msg("Resource $id is null.");
            return $lines;
        }

        while(!feof($rs))
        {
            $buffer =  trim(fgets($rs));
            if($buffer!=''){

                list($file,$size,$mtime) = explode("|",$buffer);
                $file = trim($file);
                if( $file != '' ){
                    $lines[$file]['a'] =  '';
                    if( is_dir( $this->source_path . $file)  ){
                        $t = 'D';
                        $s   = 0;
                        $mt  = 0;
                    }else{
                        $t = 'F';
                        $s  = trim($size);
                        $mt = trim($mtime);
                    }

                    $lines[$file]['t']  = $t;
                    $lines[$file]['s']  = $s;
                    $lines[$file]['mt'] = $mt;
                }
            }
        }
        Tools::msg(' - done','');
        $this->files[$id] = $lines;
        return $lines;

    }

    function appendFiles($new,$id='log'){

        Tools::log('Method: '. __METHOD__ );
        Tools::log('Append files: ');
        Tools::log($new);

        $rs = $this->rs($id);

        $error = 0;
        if( !is_array($new) ){
            Tools::msg("Required array.");
            return;
        }elseif( count($new) == 0 ){
            Tools::msg("Nothing to add..");
            return;
        }

        $append = ''; ;
        foreach($new as $file => $stats ){
            $this->files[$id][$file] = $stats;
            $size  = $stats['s'];
            $atime = $stats['mt'];
            $action = $stats['a'];
            if( $action != '' ){
                Tools::msg("[$action] " . $file );
            }
            $append  .= $file." | $size | $atime\n";
        }

        if( trim($append) != '' ){
            fwrite($rs,$append,strlen($append));
        }else{
            Tools::msg("Nothing to add.");
        }

    }

    function logFiles(){

        if( $this->add == null ){Tools::msg("Add: Null.");return;}
        if( !is_array($this->add) ){Tools::msg("Add: Must be array.");return;}
        if( count($this->add) == 0 ){Tools::msg("Add: Zero.");return;}
        $this->appendFiles(array_diff_key($this->add,$this->getFiles()));

    }

    function getConf($var){

        if( !isset($this->conf[$var]) ){

            $cnf = $this->sysfile('cnf');
            if( !file_exists($cnf) ){
                Tools::msg("Fling configuration does exist.");
                exit;
            }

            $conf_rs  = $this->rs('cnf');
            while(!feof($conf_rs)){

                $buffer = trim(fgets($conf_rs));
                if($buffer!=''){
                    $pair = explode("=",trim($buffer));
                    if(count($pair)==2){
                        $this->conf[trim($pair[0])] = trim($pair[1]);
                    }
                }
            }

            if( !isset($this->conf[$var]) ){
                $this->conf[$var] = '';
            }

        }

        if( isset($this->conf[$var]) ){
            return $this->conf[$var];
        }

        return '';

    }

    function updateLogFiles($new_file_stats){

        Tools::log('Method: '. __METHOD__ );
        $this->appendFiles($new_file_stats,'new');
        $this->rs('new', 'close' );
        $this->rs('log', 'unlink');
        rename( $this->sysfile('new'), $this->sysfile('log') );

    }

    function updateModFiles($modified_files){

        Tools::log('Method: '. __METHOD__ );


        $mod_f = $upd_f = false;
        $last_mod_files = $this->getFiles('mod');

        $this->files['mod'] = $modified_files;

        Tools::log('Before Merged Mod files found:' );
        Tools::log($this->files['mod']);

        if( count($last_mod_files) > 0 ){
            Tools::log($last_mod_files);
            $this->files['mod'] = array_merge($last_mod_files,$this->files['mod']);
            $mod_f = true;
        }

        Tools::log('After Merged Mod files found:' );
        Tools::log($this->files['mod']);

        $uploaded_files = $this->getFiles('upd');

        if( count($uploaded_files) != 0 ){

            Tools::log('Last Uploaded files found:' );
            Tools::log($uploaded_files);
            $new_modified_files_temp = array();
            foreach( $this->files['mod'] as $file => $stats ){

                if( isset($uploaded_files[$file]) ){

                    Tools::log( $this->files['mod'][$file]['mt'] .'=='. $uploaded_files[$file]['mt']  );
                    if( $this->files['mod'][$file]['mt'] == $uploaded_files[$file]['mt'] ){
                        continue;
                    }

                }
                $new_modified_files_temp[$file] = $stats;
            }
            $this->files['mod'] = $new_modified_files_temp;
            unset($new_modified_files_temp);

            Tools::log('List of Final modified files: ');
            Tools::log($this->files['mod']);
            $upd_f = true;

        }

        if( count($this->files['mod']) != 0 ){

            $this->rs('mod','unlink');
            $this->appendFiles( $this->files['mod'], 'mod');
            $this->updateUploadedFiles('reset');
            $this->rs('mod','close');

        }else{

            if($mod_f){
                $this->rs('mod','unlink');
            }else{
                $this->rs('mod','close');
            }

            if($upd_f){
                $this->updateUploadedFiles('reset');
            }else{
                $this->rs('mod','close');
            }

        }
        return true;

    }

    function updateUploadedFiles($uploaded_file){

        //Tools::log('Method: '. __METHOD__ . "($uploaded_file)" );

        Tools::log("Added to uploaded: ");
        Tools::log($uploaded_file);

        if($uploaded_file=='reset'){
            $this->rs('upd','unlink');
            return;
        }

        $this->reupload = false;
        $this->appendFiles($uploaded_file,'upd');

    }

    function execInit(){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Running init.");

        Tools::makeDir($this->data_path);
        Tools::touchFile($this->sysfile('cnf'));

        $destination = trim($this->getConf('destination'));
        if($destination==''){
            Tools::msg("Enter Destination: ");

            $destination = Tools::prompt();
            $conf = "destination=". Tools::appendSlash($destination) ."\n";
            file_put_contents($this->sysfile('cnf'),$conf);

        }
        Tools::msg("Successfully initialized.");

    }

    function execSync($opt=array()){

        Tools::log('Method: '. __METHOD__ );

        Tools::msg('Syncronizing...');
        Tools::msg('Destination: '. $this->getDestination() );

        $log_files = $this->getFiles();

        Tools::log('Log files: ');
        Tools::log($log_files);

        Tools::msg('Comparing log files...');
        $modified_files = $new_file_stats = array();
        foreach($log_files as $file => $last_stats){

            if( $last_stats['t'] == 'F' ){

                if( ($new_stats = Tools::fileStats($this->source_path.$file)) === false ){
                    continue;
                }



                Tools::log("previous for $file: ");
                Tools::log($last_stats);

                Tools::log("latest for $file: ");
                Tools::log($new_stats);

                $new_stats['a'] = '';
                Tools::log( $new_stats['mt'] ."!=". $last_stats['mt'] );

                if( $new_stats['mt'] != $last_stats['mt'] or in_array("force",$opt) ){

                    $modified_files[$file] = $new_stats;
                }
                $new_file_stats[$file] = $new_stats;

            }else{

                $new_file_stats[$file] = $last_stats;

            }

        }
        Tools::msg(' - ok','');

        Tools::log('List of modified files: ');
        Tools::log($modified_files);

        $this->updateModFiles($modified_files);

        if( count($new_file_stats) != 0 ){
            $this->updateLogFiles($new_file_stats);
        }

        if( count($this->files['mod']) != 0 ){
            $this->uploadFiles();
            Tools::msg("Complete.");
        }else{
            Tools::msg("No file has been modified.");
        }
        $this->rs('close_all');



    }

    function cleanRemote($opt=array()){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Cleaning remote files.");

        $destination = $this->getConf('destination');

        $rs = $this->rs('log','open');
        $delete_files = array();
        while(!feof($rs))
        {
            $buffer = trim(fgets($rs));
            if($buffer!=''){
                list($file,$size,$atime) = explode("|",$buffer);
                $file = trim($file);
                if( $file != '' ){
                   if(  !file_exists($this->source_path . $file)  ){
                        $delete_files[]=$file;
                   }
                }
            }
        }

        Tools::log("Missing files:");
        Tools::log($delete_files);
        $this->deleteFiles($delete_files);

        $this->rs('log','close');
        Tools::msg("Complete");

    }

    function execReset(){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Running reset..");

        $log_files = $this->getFiles();
        foreach($log_files as $file => $last_stats){
            if( ($new_stats = Tools::fileStats($this->source_path.$file)) === false ){
                continue;
            }
            $new_stats['a'] = '';
            $log_files[$file]= $new_stats;
        }

        if( count($log_files)!=0 ){
            $this->updateLogFiles($log_files);
            Tools::msg("Complete.");
        }else{
            Tools::msg("Nothing to reset.");
        }

        $this->rs('log','close');
    }

    function execAdd(){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Add files..");

        $add_files = Tools::getFiles( $this->source_path ,'dir_file');
        foreach($add_files as $file){
            $this->addFile($file);
        }
        unset($add_files);
        $this->logFiles();
        $this->rs('log','close');

    }

    /**
    *$opt = included or excluded
    *return array
    */
    function getFilter($filter=''){

        Tools::log('Method: '. __METHOD__ );
        if( !in_array($filter,array('excluded','included')) ){
            Tools::msg("Invalid option");
            return;
        }

        if( $filter == 'excluded' ){
            $pettern = $this->ex_patterns;
        }else{
            $pettern = $this->inc_pattern;
        }

        if( $pettern == null ){

            $x = str_replace("\\","\\\\",$this->getConf($filter));
            $x = str_replace("/","\/",$x);
            $x = str_replace(".","\.",$x);
            $x = str_replace("*","(.*)",$x);
            $patterns =  explode(";",$x);

            if( count($patterns) > 0 ){
                foreach($patterns as $_pattern){
                    $_pattern = trim($_pattern);
                    if($_pattern!=''){
                        $pettern[] = $_pattern;
                    }
                }
            }
        }

        if( $filter == 'excluded' ){
            $this->ex_patterns  = $pettern;
        }else{
            $this->inc_pattern  = $pettern;
        }
        return $pettern;

    }

    function filter($file){

        Tools::log('Method: '. __METHOD__ );

        $pattern = $this->getFilter('excluded');

        Tools::log('checking if excluded '. $file .' :');
        if( $pattern != null ){
            if($this->testFilter($pattern,$file)  ){
                Tools::log('excluded');
                return false;
            }
        }

        $pattern = $this->getFilter('included');
        Tools::log('checking if included - '.$file .' :');
        if( $pattern != null ){

            if($this->testFilter($pattern,$file)  ){
                Tools::log('ok','');
                return true;
            }else{

                Tools::log('not','');
                return false;
            }
        }else{
            Tools::log('ok','');
            return true;
        }

    }

    function testFilter($patterns,$file){
        if( $patterns != null ) {
            foreach( $patterns as $pattern ){
                if( preg_match('/^'.$pattern.'$/',$file) ){
                    return true;
                }
            }
        }
        return false;
    }

    function getDestination(){

        $dest = $this->getConf('destination');
        Tools::log('Destination: '.$dest);
        if($dest==''){
            Tools::msg('No destination');
            return false;
        }
        return $dest;

    }

    function getProtocol(){

         if( $this->protocol != null ){
            return $this->protocol;
         }
         $dest = $this->getDestination();
         if($dest!==false){

            $this->protocol = self::LOCAL;
            if( preg_match('/^ftp:\/\/(.*)$/',$dest) ){
                $this->protocol = self::FTP;
            }elseif( preg_match('/^sftp:\/\/(.*)$/',$dest) ){
                $this->protocol = self::SFTP;
            }

            return $this->protocol;

         }
         return false;
    }

    function uploadFiles($isRecursive=false){

        Tools::log('Method: '. __METHOD__ );

        $type = $this->getProtocol();
        if( $type !== false ){

            $this->reupload = false;
            if( $type == self::FTP or $type == self::SFTP ){
                $this->ftpUpload();
            }else{
                $this->localUpload();
            }

            if(!$isRecursive){
                if( $this->reupload ){
                    Tools::msg('Session expired, Retrying to reconnect to server');
                    $this->lastupload = null;
                    if( count($this->files['mod']) > 0 ){
                        $this->uploadFiles(true);
                    }
                }
            }
        }

    }

    function ftpDetails(){

        if( $this->ftp_details != null ){
            return $this->ftp_details;
        }
        if( ($dest = $this->getDestination()) ===  false ){
            return false;
        }

        $replace_from = array('\@');
        $replace_to = array('[at]');
        $replace_new = array('@');

        $dest = str_replace($replace_from,$replace_to,$dest);

        $credentials = str_replace("ftp://","", str_replace("sftp://","",$dest));
        list($access,$hostdetails) = explode("@",$credentials);


        $access = str_replace($replace_to,$replace_new,$access);
        $hostdetails = str_replace($replace_to,$replace_new,$hostdetails);


        $hostdetails = Tools::getPaths($hostdetails,'d');
        $FTP_HOST = $hostdetails[0];

        $DEST_PATH = '/';
        if( count($hostdetails) != 0 ){
            array_shift($hostdetails);
            foreach($hostdetails as $folder){
                $DEST_PATH .= $folder."/";
            }
        }

        $access = explode(":",$access);
        $FTP_UN = $access[0];
        if( count($access) == 2 ){
            $FTP_PW = $access[1];
        }else{
            Tools::msg("Password:");
            $FTP_PW = trim(Tools::promptSilent());
        }

        $this->ftp_details['host']  = $FTP_HOST;
        $this->ftp_details['un']    = $FTP_UN;
        $this->ftp_details['pw']    = $FTP_PW;
        $this->ftp_details['path']  = $DEST_PATH;

        Tools::log("FTP details:");
        Tools::log($this->ftp_details);
        return true;

    }

    function ftpLog(){

        if( $this->lastupload != null ){

            // $tdiff = (int)(( time() - $this->lastupload )/60); // by mins
            // Tools::msg("Last upload: $tdiff mins");
            // $waitingtime = 5;
            // if( $this->protocol == self::SFTP ){
            //     $waitingtime = 1;
            // }
            // if( $tdiff < $waitingtime  ){
            //         $this->lastupload = time();
            //         return true;
            // }

        }

        if($this->ftpDetails()){
            list($h,$un,$pw,$dst) = array_values($this->ftp_details);

            if( $this->protocol == self::SFTP ){
                Tools::msg("Sftp Transfer");
                $this->ftp = new Sftp($h,$un,$pw,$dst);
            }else{
                Tools::msg("Ftp Transfer");
                $this->ftp = new Ftp($h,$un,$pw,$dst);
            }

            if(  $this->ftp->connect() ){
                $this->lastupload = time();
                return true;
            }

        }
        $this->lastupload = null;
        return false;

    }

    function ftpUpload(){

        Tools::log('Method: '. __METHOD__ );

        $files  = $this->files['mod'];
        if( ($dest = $this->getDestination()) ===  false ){
            return false;
        }

        if( !$this->ftpLog() ){
            return false;
        }

        Tools::log("Transfer files:");
        Tools::log($files);

        foreach($files as $file => $stats ){

            if($file==''){
                continue;
            }
            Tools::msg("Uploading ". $file);
            if ($this->ftp->upload($file, $this->source_path . $file) ){

                Tools::msg(" - ok.",'');
                $this->updateUploadedFiles(array($file=>$stats));

            }else{

                Tools::msg(" - failed !",'');

            }

        }

    }

    function localUpload(){

        Tools::log('Method: '. __METHOD__ );

        $files  = $this->files['mod'];

        Tools::msg("Local Transfer");
        Tools::log("Transfer files:");
        Tools::log($files);

        if( ($dest = $this->getDestination()) ===  false ){
            return false;
        }

        foreach($files as $file => $stats){
            if($file!=''){
                Tools::msg("Copying $file ");
                $_dest=  $dest.$file;
                if( $this->_localCopy( $this->source_path . $file , $_dest) ){
                    Tools::msg(" - ok",'');
                    $this->updateUploadedFiles(array($file=>$stats));
                }else{
                    Tools::msg(" - failed",'');
                }

            }
        }
    }

    function _localCopy($src,$dest){

        if( @copy($src, $dest) ){
            return true;
        }else{
            Tools::makeRecursiveDir($dest);
            if( @copy(  $src, $dest ) ){
                return true;
            }
        }
        return false;
    }


    function deleteFiles($files){

        if(!is_array($files)){
            Tools::msg("Invalid, must be array");
            return false;
        }
        if(count($files)==0){
            Tools::msg("No files to delete from remote");
            return false;
        }
        $type = $this->getProtocol();
        if( $type !== false ){
            if( $type == self::FTP or  $type == self::SFTP ){
                $this->ftpDelete($files);
            }else{
                $this->_localDelete($files);
            }
        }
    }

    function ftpDelete($files){

        if( !$this->ftpLog() ){
            return false;
        }
        foreach($files as $file){

            Tools::msg("Deleting remote file $file ");
            if($this->ftp->delete($file)){
                Tools::msg(" - ok ",'');
            }else{
                Tools::msg(" - failed ",'');
            }

        }
    }

    function _localDelete($files){

        $dst = $this->getDestination();

        foreach($files as $file){

            if( is_dir( $dst . $file) ){

                Tools::msg("Deleting remote folder $file");
                if(rmdir($dst.$file)){
                    Tools::msg(" - ok",'');
                }else{
                    Tools::msg(" - failed",'');
                }

            }else{

                Tools::msg("deleting remote file $file");
                if( file_exists($dst.$file) ){
                    if(unlink($dst.$file)){
                        Tools::msg(" - ok",'');
                    }else{
                        Tools::msg(" - failed",'');
                    }
                }else{
                    Tools::msg("$file does not exist in remote directory");
                }

            }

        }

    }

    function isCache(){
        return $this->getConf('cache')=='true'?true:false;
    }

    public function clear()
    {
        $this->conf = null;
        $this->files = null;
        $this->protocol = null;
        $this->lastupload = null;

    }

}
