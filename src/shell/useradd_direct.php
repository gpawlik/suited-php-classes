<?php

/**
 * @package shell
 *
 */
include_once "vendor/diversen/simple-php-classes/src/shell/useradd.php";
/**
 * Adds a super user directly. Password have to be the md5 of the real password. 
 * @param array $options
 * @return int
 */
function useradd_direct_add ($options = null){

    $values['email'] = $options['email'];
    $values['password'] = $options['password']; // MD5
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
 * Adds an admin user directly. Note: Password have to be the md5 of the real password. 
 * @param array $options
 * @return int
 */
function useradd_direct_add_admin ($options = null){

    $values['email'] = $options['email'];
    $values['password'] = $options['password']; // MD5
    $values['username'] = $values['email'];
    $values['verified'] = 1;
    $values['admin'] = 1;
    $values['super'] = 0;
    $values['type'] = 'email';
    
    $res = useradd_db_insert($values);
    if ($res) { 
        return 0;
    } else {
        return 1;
    }
}



self::setCommand('useradd-direct', array(
    'description' => 'Will create user or super user',
));

self::setOption('useradd_direct_add', array(
    'long_name'   => '--add',
    'description' => 'Add user direct.',
    'action'      => 'StoreTrue'
));

self::setOption('useradd_direct_add_admin', array(
    'long_name'   => '--add-admin',
    'description' => 'Add admin user direct.',
    'action'      => 'StoreTrue'
));

self::setArgument('email',
    array('description'=> 'email',
          'optional' => false));

self::setArgument('password',
    array('description'=> 'password',
          'optional' => false));

