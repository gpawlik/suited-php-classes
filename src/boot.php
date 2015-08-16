<?php

namespace diversen;

use diversen\autoloader\modules;
use diversen\conf;
use diversen\log;
use diversen\moduleloader;
use diversen\uri\dispatch;
//use mainTemplate;
use diversen\intl;
use diversen\db;
use diversen\html\common;
use diversen\view;

class boot {

    public function run() {

       
        $m = new modules();
        $m->autoloadRegister();

        common::defineConstants();
        
        // define all constant - based on base path and config.ini
        conf::defineCommon();

        // set include path - based on config.ini
        conf::setIncludePath();
        
        // load config file 
        conf::load();

        // set log level - based on config.ini
        log::setLogLevel();

        // utf-8
        ini_set('default_charset', 'UTF-8');

        // load config/config.ini
        // check if there exists a shared ini file
        // shared ini is used if we want to enable settings between hosts
        // which share same code base. 
        // e.g. when updating all sites, it is a good idea to set the following flag
        // site_update = 1
        // this flag will send correct 503 headers, when we are updating our site. 
        // if site is being updaing we send temporarily headers
        // and display an error message
        if (conf::getMainIni('site_update')) {
            http::temporarilyUnavailable();
        }


        // set a unified server_name if not set in config file. 
        $server_name = conf::getMainIni('server_name');
        if (!$server_name) {
            conf::setMainIni('server_name', $_SERVER['SERVER_NAME']);
        }

        // redirect to uniform server name is set in config.ini
        // e.g. www.testsite.com => testsite.com
        $server_redirect = conf::getMainIni('server_redirect');
        if (isset($server_redirect)) {
            http::redirectHeaders($server_redirect);
        }

        // redirect to https is set in config.ini
        // force anything into ssl mode
        $server_force_ssl = conf::getMainIni('server_force_ssl');
        if (isset($server_force_ssl)) {
            http::sslHeaders();
        }

        // catch all output
        ob_start();

        // init module loader. 
        // after this point we can check if module exists and fire events connected to
        // installed modules
        $db = new db();
        $ml = new moduleloader();
        
        // initiate uri
        uri::getInstance();

        // runlevel 1: merge db config
        $ml->runLevel(1);

        // select all db settings and merge them with ini file settings
        $db_settings = $db->selectOne('settings', 'id', 1);

        // merge db settings with config/config.ini settings
        // db settings override ini file settings
        conf::$vars['coscms_main'] = array_merge(conf::$vars['coscms_main'], $db_settings);

        // run level 2: set locales 
        $ml->runLevel(2);

        // set locales
        intl::setLocale();

        // set default timezone
        intl::setTimezone();

        // runlevel 3 - init session
        $ml->runLevel(3);

        // start session
        session::initSession();
        session::checkAccount();

        // set account timezone if enabled - can only be done after session
        // as user needs to be logged in
        intl::setAccountTimezone();

        // run level 4 - load language
        $ml->runLevel(4);

        // load a 'language_all' file or load all module system language
        // depending on configuration
        $l = new lang();
        $base = conf::pathBase();
        $htdocs = conf::pathHtdocs();
        $l->setDirsInsideDir("$base/modules/");
        $l->setDirsInsideDir("$htdocs/templates/");
        $l->setSingleDir("$base/vendor/diversen/simple-php-classes");
        $l->loadLanguage(conf::getMainIni('language'));

        // runlevel 5
        $ml->runLevel(5);

        // load url routes if any
        dispatch::setDbRoutes();

        $ml->runLevel(6);
        
        // check db routes or load by defaults
        $db_route = dispatch::getMatchRoutes();
        if (!$db_route) {
            $ml->setModuleInfo();
            $ml->initModule();
        }
        

        // include template class found in conf::pathHtdocs() . '/templates'
        // only from here we should use template class. 
        // template translation will override module translations
        $layout = new layout();
        
        // we first load menus here so we can se what happened when we
        // init our module. In case of a 404 not found error we don't want
        // to load module menus
        $layout->loadMenus();

        // init blocks
        $layout->initBlocks();
        
        
        // if any matching route was found we check for a method or function
        if ($db_route) {
            $str = dispatch::call($db_route['method']);
        } else {
            // or we use default module parsing
            $str = $ml->getParsedModule();
        }

        // set view vars
        $vars['content'] = $str;
        
        // get template name
        $file = conf::pathHtdocs() . "/templates/" . layout::getTemplateName() . "/template.php";
        
        // get view
        echo $str = view::getFileView($file, $vars);
        //die;

        $ml->runLevel(7);
        conf::$vars['final_output'] = ob_get_contents();
        ob_end_clean();

        // Last divine intervention
        // e.g. Dom or Tidy
        $ml->runLevel(8);
        echo conf::$vars['final_output'];
    }
}
