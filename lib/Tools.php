<?php

class Tools{

    static $show_error = false;

    static $stdSeparator = '/';
    static function appendSlash($dir){
        if($dir=='')return '';
        return trim( self::stdDirSeparator($dir) ,"/")."/";
    }
    static function stdDirSeparator($dir){
        return str_replace("\\", self::$stdSeparator ,$dir);
    }
    static function stdVersionFormat($dir){
        return str_replace(SOURCE_PATH,"",self::stdDirSeparator($dir));
    }

    static function getFiles($dir,$opt='files_only'){

        $dir =  Tools::appendSlash($dir);
        $main_folder_rs = opendir($dir);
        $add_list = array();

        while( $file = readdir($main_folder_rs)  ){

            if(  $file == '.' or $file == '..' or $file == '.phpvs' or $file[0] == '.' )continue;

            $file_path = $dir.$file;
            if( is_dir($file_path) ){

                if( $opt == 'dir_file' ){
                    $add_list[] = self::appendSlash($file);
                }
                foreach(self::getFiles($file_path) as $f){
                    $add_list[] = self::stdVersionFormat($f);
                }

            }else{
                $add_list[] = self::stdVersionFormat($dir.$file);
            }

        }
        return $add_list;
    }

    static function fileStats($file){

         $_file =  $file;
         if( !file_exists($_file)  ){ if(self::$show_error){ echo "\n$file File not found "; } return false;}

         if( is_dir($_file) ){

             $ret['s']  = 0;
             $ret['mt'] = 0;

         }else{

             $stats = stat($_file);
             $ret['s']  = $stats['size'];
             $ret['mt'] = $stats['mtime'];

         }
         return $ret;

    }

    static function makeRecursiveDir($file){

        $dir = dirname($file);
        $folders = explode( Tools::$stdSeparator ,$dir);

        $path = '';
        foreach($folders as $folder){
            if($folder!=''){
                $path .= $folder."/";
                if( !is_dir($path) ){
                    mkdir($path,0777) or die("Cant make dir $path.");
                }
            }
        }
    }

    static function prompt($msg=''){
        if($msg!=''){
            echo "\n".$msg;
        }
        $handle = fopen ("php://stdin","r");
        return trim(fgets($handle));
    }

    static  function promptSilent($prompt = "Enter Password:") {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
            $vbscript, 'wscript.echo(InputBox("'
            . addslashes($prompt)
            . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
            . addslashes($prompt)
            . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
            return $password;
        }
    }

    static function touchFile($file){
        if( !file_exists($file)  ){
            touch($file);
        }
    }

    static function makeDir($dir){
        if( !is_dir($dir) ){
            mkdir($dir,0777);
        }
    }

    static function log($msg){

        return;
        if( is_string($msg) ){
            echo "\n$msg";
        }elseif(is_array($msg)){
            echo "\n";
            print_r($msg);
        }else{
            echo "\n";
            var_dump($msg);
        }
        self::prompt();

    }

    static function msg($msg,$pref="\n"){

        echo $pref."$msg";

    }

    static function getPaths($file,$t='f'){

        $path = $file;
        if($t=='f'){
            $path = dirname($file);
        }

        $folders   =  explode( self::$stdSeparator, $path );
        $ret = array();

        foreach($folders as $folder){

           if($folder=='.' or $folder == '')continue;
           $ret[] =  $folder;

        }
        return $ret;

    }

}

?>