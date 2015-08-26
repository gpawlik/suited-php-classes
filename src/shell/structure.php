<?php

use diversen\conf;
use diversen\db\admin;
use diversen\time;
use diversen\cli\common;


/**
 * dumps entire structure
 */
function cos_structure_dump () {
    $ary = admin::getDbInfo();
    if (!$ary) {
        return db_no_url();    
    }

    $user = conf::getMainIni('username');
    $password = conf::getMainIni('password');
    $command = "mysqldump -d -h $ary[host] -u $user -p$password $ary[dbname]";
    common::execCommand($command);
}

/**
 * dump single table structure
 * @param array $options
 */
function cos_structure_dump_table ($options) {
    $ary = admin::getDbInfo();
    if (!$ary) {
        return db_no_url();    
    }

    $user = conf::getMainIni('username');
    $password = conf::getMainIni('password');
    
    $dump_dir = "backup/sql/$options[table]";
    if (!file_exists($dump_dir)) {
        mkdir($dump_dir);
    }
    
    $dump_name = "backup/sql/$options[table]/" . time() . ".sql";
    
    $command = "mysqldump -d -h $ary[host] -u $user -p$password $ary[dbname] $options[table] > $dump_name";
    common::execCommand($command);
}

/**
 * function for dumping a database specfied in config.ini to a file
 *
 * @param   array   Optional. If you leave empty, then the function will try
 *                  and find most recent sql dump and load it into database.
 *                  Set <code>$options = array('File' => '/backup/sql/latest.sql')</code>
 *                  for setting a name for the dump.
 * @return  int     the executed commands shell status 0 on success.
 */
function cos_db_dump_table ($options = null){

    if (!isset($options['table'])) {
        common::abort('Specify a table to backup');
    }
    
    $dump_dir = "backup/sql/$options[table]";
    if (!file_exists($dump_dir)) {
        mkdir($dump_dir);
    }
    
    $dump_name = "backup/sql/$options[table]/" . time() . ".sql";  
    $db = admin::getDbInfo();
    if (!$db) {
        return db_no_url();    
    }

    $command = 
        "mysqldump --opt -u" . conf::$vars['coscms_main']['username'] .
        " -p" . conf::$vars['coscms_main']['password'];
    $command.= " $db[dbname] $options[table] > $dump_name";
    common::execCommand($command);
}

/**
 * function for loading a database file into db specified in config.ini
 *
 * @param   array   options. You can specifiy a file to load in options.
 *                  e.g. <code>$options = array('File' => 'backup/sql/latest.sql')</code>
 * @return  int     the executed commands shell status 0 on success.
 */
function cos_db_load_table($options){
    
    
    if (!isset($options['table'])) {
        common::abort('Specify a table to load with a backup');
    }
    
    $dump_dir = "backup/sql/$options[table]";
    if (!file_exists($dump_dir)) {
        common::abort('Yet no backups');
    }
    
    $search = conf::pathBase() . "/backup/sql/$options[table]";
    $latest = get_latest_db_dump($search);
    if ($latest == 0) {
        common::abort('Yet no database dumps');
    }
        
    $latest = "backup/sql/$options[table]/" . $latest . ".sql";
    $db = admin::getDbInfo();
    if (!$db) {
        return db_no_url();    
    }

    $command = 
        "mysql --default-character-set=utf8  -u" . conf::$vars['coscms_main']['username'] .
        " -p" . conf::$vars['coscms_main']['password'] . " $db[dbname] < $latest";
    return $ret = common::execCommand($command);
}

if (conf::isCli()){

    self::setCommand('structure', array(
        'description' => 'Dump structure of a db table',
    ));
    
    self::setOption('cos_structure_dump', array(
        'long_name'   => '--db',
        'description' => 'Outputs table structure for complete database',
        'action'      => 'StoreTrue'
    ));

    self::setOption('cos_structure_dump_table', array(
        'long_name'   => '--table',
        'description' => 'Outputs table structure for a single table',
        'action'      => 'StoreTrue'
    ));
    
    self::setOption('cos_db_dump_table', array(
        'long_name'   => '--backup-table',
        'description' => 'Backup single DB table',
        'action'      => 'StoreTrue'
    ));
    
    self::setOption('cos_db_load_table', array(
        'long_name'   => '--load-table',
        'description' => 'Create single table from latest backup',
        'action'      => 'StoreTrue'
    ));

    self::setArgument('table',
        array('description'=> 'Specify table to dump structure of',
              'optional' => true));
}
