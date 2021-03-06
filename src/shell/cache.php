<?php

use diversen\cache\clear;
use diversen\conf;


/**
 * File containing documentation functions for shell mode
 *
 * @package     shell
 */



function cache_clear_db ($options = null) {
    if (clear::db()) {
        return 0;
    }
    return 1;
}

function cache_clear_assets ($options = null) {
    if (conf::isCli()) {
        common::needRoot();
    }
    clear::assets();
    return 0;
}

function cache_clear_all ($options = null) {
    if (conf::isCli()) {
        common::needRoot();
    }
    
    clear::all();
    return 0;
}


self::setCommand('cache', array(
    'description' => 'Commands for clearing caches. ',
));

self::setOption('cache_clear_db', array(
    'long_name'   => '--clear-db',
    'description' => 'Will clear db cache - only works on default domain',
    'action'      => 'StoreTrue'
));

self::setOption('cache_clear_assets', array(
    'long_name'   => '--clear-assets',
    'description' => 'Will clear cached assets',
    'action'      => 'StoreTrue'
));

self::setOption('cache_clear_all', array(
    'long_name'   => '--clear-all',
    'description' => 'Will clear all cached assets, and db cache',
    'action'      => 'StoreTrue'
));
