<?php 

function __autoload($class){
    include_once(SYSTEM_PATH."lib/$class.php");
}

$config = require('./config.php');

define("SYSTEM_PATH", dirname(__FILE__)."/../" );
define("SOURCE_PATH", Tools::appendSlash( $config['source_path'] ) );

PhpSync::$SYNC_DATA_PATH = $config['data_path'];
PhpSync::$SYNC_SOURCE_PATH = '';

