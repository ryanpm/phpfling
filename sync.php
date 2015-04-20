<?php


if( isset($argv[1]) ){

    $dir = $argv[1];
    $options = array();
    foreach($argv as $option){
        $res = explode('=',$option);

        if( count($res) == 2 ){
             list($varname,$val) = $res;
            $options[$varname] = $val;

        }

    }

}else{
    echo "No Command.";
    exit;
}

//define("SYSTEM_PATH", Tools::stdDirSeparator(dirname(__FILE__)) );
define("SYSTEM_PATH", dirname(__FILE__)."/" );
define("SOURCE_PATH", Tools::appendSlash($dir) );

$data_path[] = SOURCE_PATH.".phpvs/";
if( isset($options['--data-path']) ){
    $data_path = array();
    $paths = explode(';',Tools::appendSlash($options['--data-path']));
    foreach ($paths as $path) {
        $data_path[] = Tools::appendSlash($path);
    }
}

//var_dump(SOURCE_PATH);
if( !is_dir(SOURCE_PATH) ){
    echo SOURCE_PATH;
    echo "Directory does not exist.";
    exit;
}

function __autoload($class){
    include_once(SYSTEM_PATH."lib/$class.php");
}

function __options_details($func){

    if( $func == 'sync' ){

        Tools::msg("options for sync: -[i,a,s,r,f,rs]");
        Tools::msg("-i   :  initialize sync configuration files");
        Tools::msg("-a   :  add new files to log files");
        Tools::msg("-s   :  execute syncronization");
        Tools::msg("-r   :  reset sync files");
        Tools::msg("-l   :  loop sync every 2 minute");
        Tools::msg("-m   :  View modified files");
        Tools::msg("-f   :  force sync\n\n");

    }else{

        Tools::msg($commands);

    }
}

function __options($func){
    if( $func == 'vrsn' ){
        return array('-i','-a','-c','-r','-s','-l');
    }elseif( $func == 'sync' ){
        return array('-i','-a','-s','-r','-f','-rs','-m');
    }
}

system("cls");

$valid_commands = array('-i','-a','-s','-r','-l','-m');

$commands_desc = "
Commands:\n-i  :   Initialize
-a  :   Add new files to Log file
-s  :   Perform syncronization
-r  :   Mark all files as uploaded
-l  :   Loop sync every 60 seconds
-m  :   View modified files\n";

foreach ($data_path as $id => $path) {
    $_p  = pathinfo($path);
    $_p  = pathinfo($_p['dirname']);
    $commands_desc .= "$id :  ". $_p['basename'] ."\n";
}


Tools::msg($commands_desc);

while(true){

    $input_command = trim(Tools::prompt('Command: '));

    if($input_command != 'q'){


        if($input_command == '' ){
            Tools::msg($commands_desc);
            continue;
        }

        $parts = explode(' ', $input_command);
        $command = trim($parts[0]);

        $filter = isset($parts[1])?explode(',', $parts[1]):null;

        foreach ($data_path as $id => $path) {
            
            if( trim($path) == '' ) continue;
            if( !is_null($filter) ){
                if( !in_array($id, $filter) ){
                    continue;
                }
            }
            if( in_array($command,$valid_commands)  ){

               // define("SOURCE_DATA_PATH",$path);
                Tools::msg("Using config: ". $path);
                PhpSync::$SYNC_DATA_PATH = $path;

                if( $command == '-i'  ){
                    Tools::makeDir($path);
                    PhpSync::init();
                }elseif( $command == '-a' ){
                    PhpSync::add();
                }elseif( $command == '-s' ){
                    PhpSync::sync();
                }elseif( $command == '-r'   ){
                    PhpSync::reset();
                }elseif( $command == '-m'   ){
                    PhpSync::modified();
                }elseif( $command == '-l'   ){

                    while(true){
                        PhpSync::add();
                        sleep(2);
                        PhpSync::sync();
                        Tools::msg("\n\nSync will run in 60 seconds...");
                        sleep(60);
                    }

                }

            }else{

                Tools::msg($commands_desc);
                continue;

            }
        }


    }else{
        exit;
    }

}

?>