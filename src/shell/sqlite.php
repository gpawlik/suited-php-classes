<?php

use diversen\conf;
use diversen\db\admin;
use Symfony\Component\Filesystem\Filesystem;
use diversen\cli\common;


function db_to_sqlite ($options = array ()) {
    
    $check = "which sequel";
    if (common::execCommand($check)) {
        common::echoMessage('You need sequel. Install it like this, e.g.:');
        common::echoMessage('sudo aptitude install ruby-sequel libsqlite3-ruby libmysql-ruby');
        common::abort();
    } else {
        common::echoStatus('OK' , 'g','Sequel is installed' );
    }
    
    $ok = false;
    $info = admin::getDbInfo();
    if ($info['scheme'] == 'mysql') {
        $ok = true; 
    } 
    if ($info['scheme'] == 'mysqli') {
        $ok = true;
    }
    
    if (!$ok) {
        common::echoStatus('ERROR', 'r', 'Driver needs to be mysql or mysqli');
    }
    
    $fs = new Filesystem();
    $fs->remove('sqlite/database.sql');
    
    $username = conf::getMainIni('username');
    $password = conf::getMainIni('password');
    $command = "sequel ";
    $command.= "$info[scheme]://$username:$password@$info[host]/$info[dbname] ";
    $command.= "-C ";
    $command.= "sqlite://sqlite/database.sql";

    $ret = common::systemCommand($command);
    
    $base = conf::pathBase();
    if (!$ret) {
        $fs->chmod('sqlite/database.sql', 0777, 0000, true);
        common::echoMessage('Sqlite database created. Edit config.ini and add:'); 
        common::echoMessage("sqlite:/$base/sqlite/database.sql");
    }    
}

if (conf::isCli()){

    self::setCommand('sqlite', array(
        'description' => 'Sqlite database commands.',
    ));
    
    self::setOption('db_to_sqlite', array(
        'long_name'   => '--mysql-to-sqlite',
        'description' => 'Create a sqlite3 database from current MySQL database. Will be placed in sqlite/database.sql',
        'action'      => 'StoreTrue'
    ));
}
