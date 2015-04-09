<?php

class Ftp{

    var $server;
    var $user_name;
    var $user_pass;
    var $root_dir;
    var $cur_dir='';
    var $conn_id;
    var $login_result;


    function __construct($ftp_server,$ftp_user_name,$ftp_user_pass,$root_dir){

        if ($ftp_server!="" && $ftp_user_name!="" && $ftp_user_pass!="" && $root_dir!="") {

            $this->server = $ftp_server;
            $this->user_name = $ftp_user_name;
            $this->user_pass = $ftp_user_pass;
            $this->root_dir = $root_dir;

        }else{
            Tools::msg("Invalid Ftp credentials.");
            return false;
        }

    }

    function connect(){

        Tools::msg("Connecting to ".$this->server." ... ");
        $this->conn_id      = @ftp_connect($this->server);
        if( is_bool($this->conn_id) ){
            Tools::msg(" - failed.",'');
            return false;
        }

        $this->login_result = @ftp_login($this->conn_id, $this->user_name, $this->user_pass);

        if ((!$this->conn_id) || (!$this->login_result)){
            Tools::msg(" - failed login.",'');
            return false;
        }else{

            ftp_pasv($this->conn_id, true);
            if( !$this->setdir() ){
                return false;
            }
            Tools::msg(" - connected.",'');
            return true;

        }

    }

    function setdir($dir=''){

        $_dir =  Tools::appendSlash($this->root_dir);
        if($dir!=''){
            Tools::msg("Change dir to $_dir");
        }
		if (ftp_chdir($this->conn_id, "/". $_dir )){
            return true;
        }else{
            return false;
        }
    }

    function makeRecursiveDir($file){

        $folders   = Tools::getPaths($file);
        if(count($folders)!=0){

            $path = './';
            foreach($folders as $folder){
                $path .= $folder."/";
                if( @ftp_mkdir($this->conn_id,$path) ){
                    Tools::msg("Created remote folder $path ");
                }
            }
            return true;

        }else{
            return false;
        }


    }

    function upload($remote_file, $local_file ){

        if($this->_upload($remote_file, $local_file)){
            return true;
        }else{
            if( $this->makeRecursiveDir($remote_file) ){
                if( $this->_upload($remote_file, $local_file)){
                    return true;
                }
            }
        }
        return false;

    }

    function _upload($remote_file, $local_file){

        if(@ftp_put($this->conn_id, $remote_file, $local_file, FTP_BINARY)){
            return true;
        }
        return false;

    }

    function delete($remote_file){
        if (@ftp_delete($this->conn_id,$remote_file)){
            return true;
        }
        return false;
    }


    public function download($remote_file, $local_file)
    {
        // open some file to write to
        $handle = fopen($local_file, 'w');
        if (@ftp_fget($this->conn_id, $handle, $remote_file, FTP_ASCII, 0)) {
         echo "successfully written to $local_file\n";
        } else {
         echo "There was a problem while downloading $remote_file to $local_file\n";
        }

    }

}

?>