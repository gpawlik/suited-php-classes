<?php

namespace diversen;
use diversen\conf;
/**
 * File contains methods for logging
 * @package    log
 */

/**
 * class log contains methods for doing 
 * logging
 * @package log
 */
class log {
    
    /**
     * logs an error. Will always be written to log file
     * if using a web server it will be logged to the default
     * error file. If CLI it will be placed in logs/coscms.log
     * @param string $message
     * @param boolean $write_file
     */
    public static function error ($message) {
              
        if (!is_string($message)) {
            $message = var_export($message, true);
        }

        
        if (conf::getMainIni('debug')) {
            if (conf::isCli()) {
                echo $message . PHP_EOL;
            } else {
                echo "<pre>" . $message . "</pre>";        
            }
        }
        
        error_log($message, 4);
    }
    
    
    /**
     * debug a message. Writes to stdout and to log file 
     * if debug = 1 is set in config
     * @param string $message 
     */
    public static function debug ($message) {       
        if (conf::getMainIni('debug')) {
            self::error($message);
            return;
        } 
    }

    /**
     * set log file. 
     * Can be used for CLI
     * @param string $file
     */    
    public static function setErrorLog($file = null) {
        if (!$file) {
            ini_set('error_log', conf::pathBase() . '/logs/coscms.log');
        }
    }

    /**
     * Set a log level based on env and debug
     */
    public static function setLogLevel() {
        
        $env = conf::getEnv();
        if ($env == 'development') {
            error_reporting(E_ALL);
        }

        // check if we are in debug mode and display errors
        if (conf::getMainIni('debug')) {
            ini_set('display_errors', 1);
        }
    }
}
