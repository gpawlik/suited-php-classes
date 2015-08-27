<?php

use diversen\moduleloader;
use diversen\cli\common;

function cron_run ($options) {
    $m = new moduleloader();
    $modules = $m->getAllModules();
    foreach($modules as $module) {
        $name = $module['module_name'];
        $class = "\\modules\\$name\\cron";
        if (method_exists($class, 'run')) {
            // Load config if any
            moduleloader::includeModule($name);
            $class::run();
        }
    }
}

function cron_install($options) {
    
    $mes = "Add the following line to your crontab";
    common::echoMessage($mes);
    
    $command = diversen\conf::pathBase() . "/coscli.sh cron --run";
    $command = "* * * * * $command 1>> /dev/null 2>&1";
    common::echoMessage($command);
    return 0;
}


self::setCommand('cron', array(
    'description' => 'Cron command.',
));

self::setOption('cron_run', array(
    'long_name'   => '--run',
    'description' => 'Runs the cron jobs',
    'action'      => 'StoreTrue'
));

self::setOption('cron_install', array(
    'long_name'   => '--install',
    'description' => 'Set this flag and enable SSL mode',
    'action'      => 'StoreTrue'
));
