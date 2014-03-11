<?php

class Sftp{
   
    private $connection;
    private $sftp;
    var $root_dir;
    var $server;
    var $user_name;
    var $user_pass;
    
    public function __construct($host,$un,$pw,$dst)
    {
        if ($host!="" && $un!="" && $pw!="" ) {
         
            $this->server    = $host;
            $this->user_name = $un;
            $this->user_pass = $pw;
            $this->root_dir  = $dst;
            
        }else{
            
            Tools::msg("Invalid Sftp credentials.");
            return false; 
            
        }
    }

    public function connect($port=22){
         
        Tools::msg("Connecting to ".$this->server." ...");
        $this->connection = ssh2_connect($this->server, (int)$port);
        if($this->connection){
            return $this->login($this->user_name,$this->user_pass);
        }
        Tools::msg("Could not connect on port $port.");
        return false;

    }

    public function login($username, $password)
    {
        
        if (! @ssh2_auth_password($this->connection, $username, $password)){
            Tools::msg("Could not authenticate");
            return false;
        }

        $this->sftp = @ssh2_sftp($this->connection);
        if (!$this->sftp){
            Tools::log("Could not initialize SFTP subsystem.");
            return false;
        }
        Tools::msg("Connected");
        return true;
        
    }

    public function upload($remote_file, $local_file )
    {
         
        if($this->_upload( $remote_file ,$local_file) ){  
            return true;
        }else{ 
            if( $this->makeRecursiveDir($remote_file) ){ 
                if( $this->_upload($remote_file, $local_file) ){
                    return true;
                } 
            }
        }
        return false;

    }
    
    function _upload($remote_file, $local_file){

        $remote_file = '/'.ltrim($this->root_dir.$remote_file,'/'); 
        if(@ssh2_scp_send($this->connection, $local_file, $remote_file, 0644)){
            return true;
        }
        return false;
        
    }
         
    function makeRecursiveDir($file){ 
        
        $folders   = Tools::getPaths($file);

        if(count($folders)!=0){   
            $path = './';
            foreach($folders as $folder){

                $path .= $folder."/";
                $_path = '/'.ltrim($this->root_dir.$path,'/');
                
                if( @ssh2_sftp_mkdir($this->sftp,$_path) ){
                    Tools::msg("Created remote folder $path ");
                }

            } 
            return true;
            
        }else{ 
            return false; 
        }
    
    }

    public function delete($remote_file){
        
        $remote_file = '/'.ltrim($this->root_dir.$remote_file,'/'); 
        if(@ssh2_sftp_unlink($this->sftp,$remote_file)){
            return true;
        }
        return false;

    }
    
    
    
}

?>