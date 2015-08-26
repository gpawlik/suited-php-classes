<?php

namespace diversen\cli;

use diversen\autoloader\modules;
use diversen\cli;
use diversen\cli\common;
use diversen\conf;
use diversen\db;
use diversen\db\admin;
use diversen\file;
use diversen\intl;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;

class main extends cli {
    public static function init() {

        $m = new modules();
        $m->autoloadRegister();

        // Define all system constants
        conf::defineCommon();

        // Set include path - based on config.ini
        conf::setIncludePath();

        // Load config file 
        conf::load();

        // Set log level - based on config.ini
        log::setLogLevel();

        // Set locales
        intl::setLocale();

        // Set default timezone
        intl::setTimezone();
        
        // Load language
        $l = new lang();
        
        $base = conf::pathBase();
        $l->setDirsInsideDir("$base/modules/");
        $l->setDirsInsideDir("$base/htdocs/templates/");
        $l->setSingleDir("$base/vendor/diversen/simple-php-classes");       
        $l->loadLanguage(conf::getMainIni('lang'));
        
        // Init parent with base commands
        parent::init();
        
        self::$parser->description = <<<EOF
                    _ _       _     
  ___ ___  ___  ___| (_)  ___| |__  
 / __/ _ \/ __|/ __| | | / __| '_ \ 
| (_| (_) \__ \ (__| | |_\__ \ | | |
 \___\___/|___/\___|_|_(_)___/_| |_|

    Modulized Command line program

EOF;
        self::$parser->version = '0.0.1';

        // Adding a main option for setting domain
        self::$parser->addOption(
            'domain', array(
            'short_name' => '-d',
            'long_name' => '--domain',
            'description' => 'Domain to use if using multi hosts. If not set we will use default domain',
            'action' => 'StoreString',
            'default' => 'default',
                )
        );
        
        self::beforeParse();
    }
    
    /**
     * Run the parser
     *                              
     * @return int $ret 0 on success any other int is failure
     */
    public static function run() {
        
        $result = self::parse();
        self::afterParse($result);
        
        // Execute the result
        $ret = self::execute($result);
        
        // Exit with result from execution
        exit($ret);
    }

    /**
     * Do this after the commandline options has been parsed. 
     * Examine the --domain flag and the --verbose flag
     * @param type $result
     */
    public static function afterParse($result) {
        
        $verbose = $result->options['verbose'];
        conf::setMainIni('verbose', $verbose);
        
        // Check if other domain than default is being used
        $domain = $result->options['domain'];
        conf::setMainIni('domain', $domain);

        if ($domain != 'default' || empty($domain)) {
            $domain_ini = conf::pathBase() . "/config/multi/$domain/config.ini";
            if (!file_exists($domain_ini)) {
                common::abort("No such domain - no configuration found: '$domain_ini'");
            } else {
                // If domain is used - Load domain specific configuration
                conf::loadMainCli();
            }
        }
    }

    /**
     * Before parsing of the commandline options
     * This loads all commandline options from file system
     * and modules found in the database
     */
    public static function beforeParse () {
        self::loadBaseModules();
        $url = conf::getMainIni('url');
        if ($url) {
            self::loadDbModules();
        }
    }

    /**
     * loads all modules in database
     */
    public static function loadDbModules (){        
          
        if (!self::tablesExists()) {
            common::echoMessage('No tables exists. We can not load all modules');
            return;
        }

        $mod_loader = new moduleloader();
        $modules = moduleloader::getAllModules();
           
        foreach ($modules as $val){         
            if (isset($val['is_shell']) && $val['is_shell'] == 1){
                moduleloader::includeModule($val['module_name']);               
                $path =  conf::pathModules() . "/$val[module_name]/$val[module_name].inc";           
                if (file_exists($path)) {
                    include_once $path;
                }             
            }
        }
    }
    
        
    /**
     * Loads all base modules
     * Base modules are placed in vendor/diversen/simple-php-classes
     */
    public static function loadBaseModules () {
        $command_path = __DIR__ . "/../shell";
        $base_list = file::getFileList($command_path, array ('search' => '.php'));
        foreach ($base_list as $val){
            include_once $command_path . "/$val";
        }
    }
    
    /**
     * Checks if any table exist in database
     * @return boolean
     */
    public static function tablesExists () {

        $db = new db();
        $ret = $db->connect(array('dont_die' => 1));
        if ($ret == 'NO_DB_CONN'){
            return false;
        }
        
        $info = admin::getDbInfo();
        if (!$info) {
            common::echoMessage('No databse url in config.ini');
        }
        if ($info['scheme'] == 'mysql' || $info['scheme'] == 'mysqli') {
            $rows = $db->selectQuery("SHOW TABLES");
            if (empty($rows)){
                return false; 
            }
            return true;
        }

        if ($info['scheme'] == 'sqlite')  {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='modules'";
            $rows = $db->selectQuery($sql);
            
            if (empty($rows)){
                return false; 
            }
            return true;    
        }
    } 
}
