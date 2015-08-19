<?php

namespace diversen\db;

use diversen\conf;
use PDO;
use PDOException;

class connect {
    
    /** database handle */
    public static $dbh = null;
    
    /** Flaf indicating if there is a connection */
    public static $con = null;
    
    /** var thar holds all sqlstatements */
    public static $debug = array();
    
    /**
     * create a connect
     */
    public function __construct($options = null) {
        if (!self::$dbh) {
            self::$dbh->connect($options);
        }
    }
    
    /**
     * connect to a database
     * @param type $options
     * 
     * array('url', 'username', 'password', 'dont_die', 'db_init')
     * 
     * @return string
     */
    public static function connect($options = null){

        self::$debug[] = "Trying to connect with " . conf::$vars['coscms_main']['url'];
        if (isset($options['url'])) {
            $url = $options['url'];
            $username = $options['username'];
            $password = $options['password'];
        } else {
            $url = conf::getMainIni('url');
            $username = conf::getMainIni('username');
            $password = conf::getMainIni('password');
        }

        if (conf::getMainIni('db_dont_persist') == 1) {
            $con_options = array();
        } else {
            $con_options = array('PDO::ATTR_PERSISTENT' => true);
        }
        
        try {    
            self::$dbh = new PDO(
                $url,
                $username,
                $password, 
                $options
            );
            
            // Exception mode
            self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // set SSL
            self::setSsl();
	    
            // init
            if (conf::getModuleIni('db_init')) {
                self::$dbh->exec(conf::getModuleIni('db_init'));
            }

        // Catch Exception
        } catch (PDOException $e) {
            if (!$options){
                self::fatalError ('Connection failed: ' . $e->getMessage());
            } else {
                if (isset($options['dont_die'])){
                    self::$debug[] = $e->getMessage();
                    self::$debug[] = 'No connection';
                    return "NO_DB_CONN";
                }
            }
        }
        self::$con = true;
        self::$debug[]  = 'Connected!';
    }
    
    /**
     * set SSL for mysql 
     */
    public static function setSsl() {
        $attr = conf::getMainIni('mysql_attr');
        if (isset($attr['mysql_attr'])) {
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_KEY, $attr['ssl_key']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CERT, $attr['ssl_cert']);
            self::$dbh->setAttribute(PDO::MYSQL_ATTR_SSL_CA, $attr['ssl_ca']);
        }
    }
    
    /**
     * Method for showing errors
     * @param   string  $msg the message to show with the backtrace
     */
    protected static function fatalError($msg) {
        self::$debug[] = "Fatal error encountered";
        echo "<pre>Error!: $msg\n";
        $bt = debug_backtrace();
        foreach($bt as $line) {
            $args = var_export($line['args'], true);
            echo "{$line['function']}($args) at {$line['file']}:{$line['line']}\n";
        }
        echo "</pre>";
        die();
    }
}
