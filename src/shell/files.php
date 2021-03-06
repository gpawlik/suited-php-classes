<?php

use diversen\cli\common;
use diversen\conf;


/**
 * File containing function for chown and chmod files to sane settings
 *
 * @package     shell
 */

/**
 * function for changing owner and group to correct and safe settings.
 *
 * we read which user the web server is running under by fetching
 * the whoami script from the server. the owner will be the user running the
 * script. Public upload dir /htdocs/files will then be set to 770 with correct
 * user and group
 *
 * @return int  value from exec command
 */
function cos_chmod_files(){
    
    $group = conf::getServerUser();
    if (!$group) {
        common::echoMessage('Servername is not set in config.ini', 'r');
        common::echoMessage('Set it, and try again', 'y');
        return 1;
    }

    // Try to get login username
    // As it is easier for the current user to examine
    // the files which belongs to the web user
    if (function_exists('posix_getlogin')){
        $owner = posix_getlogin();
    } else {
        $owner = exec('whoami');
    }
    
    if (!$owner) {
        $owner = $group;
    }

    common::needRoot();
    $files_path = conf::pathBase() . '/htdocs/files ';
    $files_path.= conf::pathBase() . '/logs ';
    $files_path.= conf::pathBase() . '/private ';
    $files_path.= conf::pathBase() . '/config/multi';
    $command = "chown -R $owner:$group $files_path";
    common::execCommand($command);
    $command = "chmod -R 770 $files_path";
    common::execCommand($command);
}

/**
 * function for changing all files to be onwed by user.
 *
 * Change file to owned by owner (the user logged in)
 * Public files /htdocs/files will then be set to 777
 *
 * @return int  value from exec command
 */
function cos_chmod_files_owner(){
    common::needRoot();
    if (function_exists('posix_getlogin')){
        $owner = posix_getlogin();
    } else {
        $owner = exec('whoami');
    }
    $files_path = conf::pathBase() . '/htdocs/files ';
    $files_path.= conf::pathBase() . '/logs ';
    $files_path.= conf::pathBase() . '/private ';
    $files_path.= conf::pathBase() . '/config/multi';
    $command = "chown -R $owner:$owner $files_path";
    common::execCommand($command);
    $command = "chmod -R 770 $files_path";
    common::execCommand($command);
}

/**
 * function for removing all files in htdocs/files/*, htdocs/logo/*
 * when doing an install
 *
 * @return int  value from exec command
 */
function cos_rm_files(){
    common::needRoot();
    $files_path = conf::pathBase() . '/htdocs/files/* ';
    $command = "rm -Rf $files_path";
    common::execCommand($command);
}

/**
 * function for removing all files in htdocs/files/*, htdocs/logo/*
 * when doing an install
 *
 * @return int  value from exec command
 */
function cos_create_files(){
    $files_path = conf::pathBase() . '/logs/coscms.log';
    if (!file_exists($files_path)){
        $command = "touch $files_path";
        common::execCommand($command);
    }

    $files_path = conf::pathBase() . '/htdocs/files';
    if (!file_exists($files_path)){
        $command = "mkdir $files_path";
        common::execCommand($command);
    }
    
    $domain = conf::getDomain();
    $files_path = conf::pathBase() . "/htdocs/files/$domain";
    
    if (!file_exists($files_path)){
        $command = "mkdir $files_path";
        common::execCommand($command);
    }    
}

self::setCommand('file', array(
    'description' => 'Basic files commands.',
));

self::setOption('cos_chmod_files', array(
    'long_name'   => '--chmod-files',
    'description' => 'Will try to chmod and chown of htdocs/files',
    'action'      => 'StoreTrue'
));

self::setOption('cos_chmod_files_owner', array(
    'long_name'   => '--chmod-files-owner',
    'description' => 'Will try to chmod and chown of htdocs/files to current user',
    'action'      => 'StoreTrue'
));

self::setOption('cos_rm_files', array(
    'long_name'   => '--rm-files',
    'description' => 'Will remove files in htdocs/files and in htdocs/logo',
    'action'      => 'StoreTrue'
));

self::setOption('cos_create_files', array(
    'long_name'   => '--create-files',
    'description' => 'Will create log file: log/coscms.log',
    'action'      => 'StoreTrue'
));
