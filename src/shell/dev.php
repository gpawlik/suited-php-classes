<?php

use diversen\conf;
use diversen\file;
use diversen\log;
use diversen\mycurl;
use diversen\cli\common;


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
    
    $files = file::getFileListRecursive(conf::pathModules(), "*module.php");
    foreach ($files as $val) {

        $class_path = "modules" . str_replace(conf::pathModules(), '', $val);
        $class_path = str_replace('.php', '', $class_path);
        
        $class = str_replace('/', "\\", $class_path);
        $ary = get_class_methods($class);
       
        if (!is_array($ary)) {
            continue;
        }
        $call_paths = dev_get_actions($ary, $class_path);
        
        foreach ($call_paths as $path) {

            $url = conf::getSchemeWithServerName() . "$path";
            $curl = new mycurl($url);
            $curl->createCurl();

            echo $curl->getHttpStatus();
            common::echoMessage(" Status code recieved on: $url");

        }
   }
}

function dev_get_actions ($methods, $class_path) {
    $ary = explode("/", $class_path);
    
    array_pop($ary);
    array_shift($ary);
    
    $path = "/" . implode('/', $ary);

    foreach($methods as $key => $method) {
        if (!strstr($method, 'Action')) {
            unset($methods[$key]);
            continue;
        }
        $methods[$key] = $path . "/"  . str_replace('Action', '', $method);
    }
    
    return $methods;
    
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