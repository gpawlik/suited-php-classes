<?php

use diversen\db;
use diversen\db\admin;
use diversen\cli\common;


/**
 * @package shell
 *
 */

/**
 * adds an user by prompt
 * @param array $options
 * @return int $res
 */
function useradd_add ($options = null){

    $values['email'] = common::readSingleline("Enter Email of super user (you will use this as login): ");
    $values['password'] = common::readSingleline ("Enter password: ");
    $values['password'] = md5($values['password']);
    $values['username'] = $values['email'];
    $values['verified'] = 1;
    $values['admin'] = 1;
    $values['super'] = 1;
    
    $values['type'] = 'email';
    $res = useradd_db_insert($values);
    if ($res) { 
        return 0;
    } else {
        return 1;
    }
}


/**
 * function for inserting user
 * @param   array   $values
 * @return  boolean $res
 */
function useradd_db_insert ($values){
    $database = admin::getDbInfo();
    if (!$database) {
        return db_no_url();
    }


    admin::changeDB($database['dbname']);
    $db = new db();
    $res = $db->insert('account', $values);
    return $res;
}

self::setCommand('useradd', array(
    'description' => 'Will help you create a super user for your install',
));

self::setOption('useradd_add', array(
    'long_name'   => '--add',
    'description' => 'Add user with prompt answers.',
    'action'      => 'StoreTrue'
));



