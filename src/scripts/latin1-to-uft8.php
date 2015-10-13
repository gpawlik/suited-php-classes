<?php

/**
 * 
According to this question on stackoverflow: [Detecting utf8 broken characters in MySQL](http://stackoverflow.com/questions/1476356/detecting-utf8-broken-characters-in-mysql). I found that [this answer](http://stackoverflow.com/questions/1476356/detecting-utf8-broken-characters-in-mysql/9159875#9159875) had the correct approach. This approach will transform broken chars like the following into the correct utf8 chars: 

    Ã¡ = á
    Ã© = é
    Ã­- = í
    Ã³ = ó
    Ã± = ñ
    Ã¡ = Á

At least it worked perfect for my setup, so I added a small script to the my class collection [simple-php-classes,](https://github.com/diversen/simple-php-classes) which transforms a whole MySQL database in the above way. You [can find the script on github](https://github.com/diversen/simple-php-classes/blob/master/src/scripts/latin1-to-uft8.php). Usage: 

Require the script:

    composer require diversen/simple-php-classes

Create config file 

    mkdir config
    touch config/config.ini

Add something like the following to the `config/config.ini` file:

    url = "mysql:dbname=demo;host=localhost;charset=utf8"
    db_init = "SET NAMES utf8"
    username = "root"
    password = "password"

Run the script:

    php vendor/diversen/simple-php-classes/src/scripts/latin1-to-uft8.php

 */



include_once "vendor/autoload.php";

use diversen\conf;
use diversen\db;

conf::setMainIni('base_path', realpath('.'));

if (!conf::loadMainCli()){
    $str = <<<EOF
Usage: 

create config/config.ini e.g.:

mkdir config
touch config/config.ini

Put your credentials into config/config.ini
Something like this: 

url = "mysql:dbname=demo;host=localhost;charset=utf8"
db_init = "SET NAMES utf8"
username = "root"
password = "password"

EOF;
    echo $str;
    exit(1);
}
        
$db = new db();
$db->connect();

function get_tables_db () {
    $db = new db();
    $rows = $db->selectQuery('show tables');
    $tables = array();
    foreach ($rows as $table) {
        $tables[] = array_pop($table);
    }
    return $tables;
}

function get_table_create ($table) {
    $db = new db();
    $sql = "DESCRIBE $table";
    return $db->selectQuery($sql);
}

function column_has_text ($ary) {
    $ary['Type'] = trim($ary['Type']);
    if (preg_match("#^varchar#", $ary['Type'])) {
        return true;
    }
    
    if (preg_match("#^text#", $ary['Type']) ) {
        return true;
    }
    
    if (preg_match("#^mediumtext#", $ary['Type']) ) {
        return true;
    }
    
    if (preg_match("#^longtext#", $ary['Type']) ) {
        return true;
    }
    if (preg_match("#^tinytext#", $ary['Type']) ) {
        return true;
        
    }
    return false;
}

$tables = get_tables_db();
foreach ($tables as $table ) {
    $create = get_table_create($table);

    foreach ($create as $column) {
        if (column_has_text($column)) {
        
            echo "Fixing $table:  column $column[Field]\n";
            $query = "ALTER TABLE `$table` MODIFY `$column[Field]` $column[Type] character set latin1;";
            $query.= "ALTER TABLE `$table` MODIFY `$column[Field]` BLOB;";
            $query.= "ALTER TABLE `$table` MODIFY `$column[Field]` $column[Type] character set utf8;";
            $db->rawQuery($query);
            
        }
    }    
}
