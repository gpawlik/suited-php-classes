<?php

use diversen\mycurl;

/**
 * File containing documentation functions for shell mode
 *
 * @package     shell_dev
 */

/**
 * function for checking if your are denying people 
 * from e.g. admin areas of your module. 
 */
function dev_test_access($options = null){
    
    $files = file::getFileListRecursive(conf::pathModules(), "*.php");
   
    $base_url = "http://" . conf::getMainIni('server_name');
    foreach ($files as $val) {
        $url = str_replace(conf::pathModules(), '', $val);
        $url = substr($url, 0, -4);
       
        $url = $base_url . $url;
        $curl = new mycurl($url);
        $curl->createCurl();
       
        echo $curl->getHttpStatus();
        cos_cli_print(" Status code recieved on: $url");       
   }
}

function dev_env($options = null){
    echo conf::getEnv() . "\n";
}

function dev_log($options = null){
    log::error('Hello world');
}



self::setCommand('dev', array(
    'description' => 'Dev commands for testing and checking.',
));

self::setOption('dev_test_access', array(
    'long_name'   => '--http-return-codes',
    'description' => 'Will check all web access points and give return code, e.g. 200 or 403 or 404',
    'action'      => 'StoreTrue'
));

self::setOption('dev_env', array(
    'long_name'   => '--env',
    'description' => 'Displays which env you are in (development, stage or production)',
    'action'      => 'StoreTrue'
));

self::setOption('dev_log', array(
    'long_name'   => '--log',
    'description' => 'Test log file. Will write hello world into logs/coscms.log',
    'action'      => 'StoreTrue'
));