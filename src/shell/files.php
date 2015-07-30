<?php

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

    cos_needs_root();
    $owner = posix_getlogin();

    $files_path = conf::pathBase() . '/htdocs/files ';
    $files_path.= conf::pathBase() . '/logs ';
    $files_path.= conf::pathBase() . '/private ';
    $files_path.= conf::pathBase() . '/config/multi';
    $command = "chown -R $owner:$group $files_path";
    cos_exec($command);
    $command = "chmod -R 770 $files_path";
    cos_exec($command);
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
    cos_needs_root();
    $owner = posix_getlogin();
    $files_path = conf::pathBase() . '/htdocs/files ';
    $files_path.= conf::pathBase() . '/logs ';
    $files_path.= conf::pathBase() . '/private ';
    $files_path.= conf::pathBase() . '/config/multi';
    $command = "chown -R $owner:$owner $files_path";
    cos_exec($command);
    $command = "chmod -R 770 $files_path";
    cos_exec($command);
}

/**
 * function for removing all files in htdocs/files/*, htdocs/logo/*
 * when doing an install
 *
 * @return int  value from exec command
 */
function cos_rm_files(){
    cos_needs_root();
    $files_path = conf::pathBase() . '/htdocs/files/* ';
    $command = "rm -Rf $files_path";
    cos_exec($command);
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
        cos_exec($command);
    }

    $files_path = conf::pathBase() . '/htdocs/files';
    if (!file_exists($files_path)){
        $command = "mkdir $files_path";
        cos_exec($command);
    }
    
    $domain = conf::getDomain();
    $files_path = conf::pathBase() . "/htdocs/files/$domain";
    
    if (!file_exists($files_path)){
        $command = "mkdir $files_path";
        cos_exec($command);
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
