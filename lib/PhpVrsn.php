<?php

class PhpVrsn{

    static $g;

    public $add;
    public $rs;
    public $files;
    public $conf;

    public $ex_patterns;
    public $inc_pattern;

    public $cache;
    public $sysfiles;

    public $head;

    public $data_path;
    public $source_path;

    public $commit_msg;

    static function g(){
        if(self::$g==null){
            self::$g = new self;
        }
        return self::$g;
    }

    static function init(){
        self::g()->execInit();
    }

    static function add(){
        self::g()->execAdd();
    }

    static function commit($msg){
        self::g()->commit_msg = $msg;
        self::g()->execCommit();
    }

    static function revert($revid){
        self::g()->execRevert($revid);
    }

    static function status(){
        self::g()->execStatus();
    }

    static function logs(){
        self::g()->execLogs();
    }

    static function push(){
        self::g()->execPush();
    }

    function __construct(){
        $this->data_path   = SOURCE_DATA_PATH."version/";
        $this->source_path = SOURCE_PATH;
    }

    function execAdd(){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Add files.");

        $add_files = Tools::getFiles($this->source_path,'dir_file');
        foreach($add_files as $file){
            $this->addFile($file);
        }
        unset($add_files);
        $this->logFiles();
        $this->rs('log','close');

    }

    function sysfile($id){

        Tools::log('Method: '. __METHOD__ ."($id)" );
        if( in_array($id,array('cnf','log','new','head')) ){
            return $this->data_path."$id";
        }
        Tools::msg("Invalid extension $id");
        exit;
    }

    function execInit(){

        Tools::log('Method: '. __METHOD__ );
        Tools::msg("Running init.");

        Tools::makeDir(SOURCE_DATA_PATH);
        Tools::makeDir($this->data_path);
        Tools::touchFile($this->sysfile('cnf'));
        Tools::msg("Successfully initialize.");

    }

    function getRevFiles($revid){

        $rev_path = $this->data_path."repository/v{$revid}/v";
        $files_rs = fopen($rev_path,'r');
        $lines = array();
        while(!feof($files_rs)){

            $buffer =  trim(fgets($files_rs));
            Tools::log("buffer=$buffer");
            if($buffer!=''){
                list($a,$file) = explode("|",$buffer);
                $lines[$file]['a'] = $a;
            }

        }
        return $lines;

    }

    function getRevMsg($revid){

        $rev_path = $this->data_path."repository/v{$revid}/msg";
        if( !file_exists($rev_path) )return '';
        return file_get_contents($rev_path);

    }

    function getRevLog($revid){

        $rev_path = $this->data_path."repository/v{$revid}/l";
        if( !file_exists($rev_path) )return '';
        $l_rs = fopen($rev_path,'r');
        $ret = '';
        while(!feof($l_rs)){

            $buffer =  trim(fgets($l_rs));
            if($buffer!=''){

                list($var,$val) = explode("=",$buffer);
                if(trim($var)=='Timestamp'){
                    $ret .= "$var: ". date("F d Y H:i:sa",$val) ."\n";
                }else{
                    $ret .= "$var: $val\n";
                }

            }

        }
        return $ret;
    }

    function execStatus(){

        Tools::log('Method: '. __METHOD__  );
        $files = $this->getModifiedFiles();
        $modified_files = $files[0];

        if( count($modified_files) != 0 ){

            foreach( $modified_files as $file => $stats ){
                Tools::msg($stats['a'] .' | '.$file);
            }

        }else{
            Tools::msg("No file has been modified.");
        }
        Tools::msg("Complete.");

    }

    function execLogs(){

        Tools::log('Method: '. __METHOD__  );

        $head = $this->getHead();
        if( $head > 0 ){
            for($i=$head;$i>=1;$i--){

                Tools::msg("\nRevno: $i");
                Tools::msg($this->getRevLog($i));
                Tools::msg("Message: \n ".$this->getRevMsg($i) );
                Tools::msg('---------------------------------');

            }
        }
        Tools::msg("\nCurrent Head: $head");
        Tools::msg("\nComplete.");

    }

    function execPush($loc=''){

        if($loc!=''){
            $loc = '';
        }
        if( $loc == '' )return;

        //check if valid loc

        // parse loc

        // check if version loc


    }

    function execRevert($revid){

        Tools::log('Method: '. __METHOD__ ."($revid)" );

        $current_head = $this->getHead();

        if((int)$current_head == (int)$revid){
            Tools::msg('Your are already in revision no. '.$revid);
            return;
        }

        Tools::msg('Revert files to revision no.'.$revid.' ...');

        $rev_path = $this->data_path."repository/v{$revid}/files/";
        $rev_files = $this->getRevFiles($revid);
        $log_files = $this->getFiles();

        Tools::log('Rev path: '.$rev_path);
        Tools::log('Reg files: ');
        Tools::log($rev_files);

        Tools::log('Log files: ');
        Tools::log($log_files);

        $modified_files = array();
        foreach($rev_files as $file => $a ){
            Tools::log($file);
            if( isset($log_files[$file]) ){
                Tools::log(' - current version='. $log_files[$file]['v'],'');
                if( $log_files[$file]['v'] == $revid ){
                    Tools::log(' - not changed','');
                    continue;
                }
            }

            Tools::msg("[". $a['a'] . "] ". $file);
            if( $a['a']=='M' or $a['a']=='A'){
                $this->_copy( $rev_path.$file, $this->source_path.$file);
            }elseif( $a['a']=='D'  ){
                @unlink($this->source_path.$file);
            }

        }

        Tools::msg("Complete.");

    }

    function execCommit(){

        Tools::log('Method: '. __METHOD__ );

        Tools::msg('Running commit...');

        $files = $this->getModifiedFiles();
        $modified_files = $files[0];
        $new_file_stats = $files[1];

        if( count($modified_files) != 0 ){

            $this->updateVersionFiles($modified_files);
            $this->updateLogFiles($new_file_stats);

            Tools::msg('Latest revision: '.$this->getHead());
            Tools::msg("Complete.");

        }else{
            Tools::msg("No file has been modified.");
        }

    }

    function getModifiedFiles(){

        Tools::log('Method: '. __METHOD__ );

        $log_files = $this->getFiles();

        Tools::log('Log files: ');
        Tools::log($log_files);

        $head = $this->getHead();

        Tools::msg('Comparing log files...');
        $modified_files = $new_file_stats = array();

        foreach($log_files as $file => $last_stats){

            if( $last_stats['t'] == 'F' ){

                if( ($new_stats = Tools::fileStats($this->source_path.$file)) === false ){

                    $new_stats = $last_stats;
                    $new_stats['a'] = 'D';
                    $modified_files[$file] = $new_stats;

                }else{

                    Tools::log("previous for $file: ");
                    Tools::log($last_stats);

                    Tools::log("latest for $file: ");
                    Tools::log($new_stats);

                    $new_stats['a'] = '';
                    $new_stats['v'] = $last_stats['v'];
                    Tools::log( $new_stats['mt'] ."!=". $last_stats['mt'] );
                    if( $new_stats['mt'] != $last_stats['mt'] ){

                        $new_stats['a'] = ( $last_stats['mt'] == 0 )?'A':'M';
                        $modified_files[$file] = $new_stats;
                        $new_stats['v'] = $head+1;

                    }

                }

                $new_file_stats[$file] = $new_stats;

            }else{

                $new_file_stats[$file] = $last_stats;

            }

        }

        Tools::msg(' - ok','');
        Tools::log('List of modified files: ');
        Tools::log($modified_files);

        return array($modified_files,$new_file_stats);

    }

    function updateVersionFiles($modified_files){


        Tools::log('Method: '. __METHOD__ ."($modified_files)" );
        $newhead = $this->getHead()+1;
        $newfolder = $this->data_path . "repository/v".$newhead.'/';

        Tools::makeDir($newfolder);

        $path_files = $newfolder."files/";
        Tools::makeDir($path_files);

        if($this->commit_msg!=''){
            @file_put_contents($newfolder."msg",$this->commit_msg);
        }

        $mod_files = '';
        foreach($modified_files as $files => $stats){
            $mod_files .=  $stats['a'] ."|". $files ."\n";
        }
        if($mod_files!=''){
            @file_put_contents($newfolder."v",$mod_files);
        }

        $logs = 'Committer='. $this->getConf('author') ;
        $logs .= "\n".'Timestamp='. time();
        @file_put_contents($newfolder."l",$logs);

        $this->copyfiles( $modified_files, $path_files );
        $this->setHead($newhead);

        //check if checkout location is in config
        $checkout = $this->getConf('checkout');
        if( $checkout != '' ){

            $sync = new PhpSync;
            $sync->conf['destination'] = $checkout;

            $this->data_path           = $this->data_path.'sync/';
            $this->source_path         = $this->data_path;
            $sync->execInit();
            $sync->execAdd();
            $sync->execSync();

        }

    }

    function updateLogFiles($new_file_stats){

        Tools::log('Method: '. __METHOD__ );
        $this->appendFiles($new_file_stats,'new');
        $this->rs('new', 'close' );
        $this->rs('log', 'unlink');
        rename( $this->sysfile('new'), $this->sysfile('log') );

    }


    function rs($id,$a='open'){

        Tools::log('Method: '. __METHOD__ ."($id,$a)" );

        if( $id =='close_all' ){
            foreach( array('log') as $_id ){
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

        Tools::log('Method: '. __METHOD__ ."($file)" );

        if($this->filter($file)){

            $stats['s']     = 0;
            $stats['mt']    = 0;
            $stats['a']     = 'A';
            $stats['t']     = 'F';
            $stats['v']     = '0';
            if( is_dir($this->source_path.$file) ){
                $stats['t']     = 'D';
            }
            $this->add[$file] = $stats;
        }

    }

    function getFiles($id='log'){

        Tools::log('Method: '. __METHOD__ ."($id)" );

        Tools::msg("Getting $id files ");

        $rs = $this->rs($id);

        $lines = array();
        if( $rs == null ){
            Tools::msg("Resource $id is null.");
            return $lines;
        }

        rewind ($rs);
        while(!feof($rs))
        {
            $buffer =  trim(fgets($rs));
            Tools::log("buffer=$buffer");
            if($buffer!=''){

                list($file,$version,$size,$mtime) = explode("|",$buffer);
                $file = trim($file);

                if( $file != '' ){

                    $lines[$file]['a'] =  '';
                    if( is_dir( $this->source_path . $file)  ){
                        $t = 'D';
                        $s   = 0;
                        $mt  = 0;
                        $v   = 0;
                    }else{
                        $t  = 'F';
                        $s  = trim($size);
                        $mt = trim($mtime);
                        $v  = trim($version);
                    }

                    $lines[$file]['v']  = $v;
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

        Tools::log('Method: '. __METHOD__ ."($new,$id)" );
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
            $size    = $stats['s'];
            $atime   = $stats['mt'];
            $action  = $stats['a'];
            $version = (int)$stats['v'];
            if( $action != '' ){
                Tools::msg("[$action] " . $file );
            }
            if( $action != 'D' ){
                $append  .= $file." | $version | $size | $atime\n";
            }
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

       if( !isset($this->conf[$var])  ){

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
        Tools::log('checking if excluded '.$pattern .' :');
        if( $pattern != null ){
            if($this->testFilter($pattern,$file)  ){
                Tools::log('excluded');
                return false;
            }
        }

        $pattern = $this->getFilter('included');
        Tools::log('checking if included - '.$pattern .' :');
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

    function getHead(){
        if( !file_exists( $this->sysfile('head')) ){    return 0;
        }
        $this->head = (int)file_get_contents( $this->sysfile('head') );

        return $this->head;

    }

    function setHead($val){
        @file_put_contents( $this->sysfile('head') ,(int)$val);
    }

    function incHead(){
        $this->setHead( $this->getHead() + 1 );
    }

    function copyfiles($files,$dest){

        Tools::log('Method: '. __METHOD__ );

        Tools::log("Copying files:");
        Tools::log($files);

        foreach($files as $file => $stats){
            if($file!=''){
                Tools::log("Copying $file ");
                $_dest=  $dest.$file;
                if( $this->_copy( $this->source_path . $file , $_dest) ){
                    Tools::log(" - ok",'');
                }else{
                    Tools::log(" - failed",'');
                }
            }
        }

    }

    function _copy($src,$dest){

        Tools::log('Method: '. __METHOD__ ."($src,$dest)");
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

}

?>