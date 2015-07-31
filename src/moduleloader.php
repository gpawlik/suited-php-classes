<?php

namespace diversen;
use diversen\moduleloader\reference;
use diversen\lang;
use diversen\conf as conf;
use diversen\uri;
/**
 * File contains class for loading modules
 *
 * @package    moduleloader
 */

/**
 * Class for loading modules
 *
 * @package    moduleloader
 */
class moduleloader {

    /**
     * all enabled modules
     * @var array $modules 
     */
    public static $modules = array();

    
    /**
     * var holding all loaded modules
     * @var array $modules 
     */
    public static $loadedModules = array();
    /**
     * holding different run leve
     * @var array $lvelsls
     */
    public $levels = array();

    /**
     * holding info about files to load when loaidng module.
     * @var array $info 
     */
    public $info = array();
    
    /**
     *                  
     *                  static variable which can be set in case we don't
     *                  want to load called module. Used for enablingloading
     *                  of error module when an error code has been set.
     *                  self::$status[403] or self::$status[404]
     * @var     array   $status 

     */
    public static $status = array();
    
    /**
     * used to hold a message when user encounters e.g. 403 or 404
     * @var type 
     */
    public static $message = '';

    /**
     * holding module ini settings.
     * @var array $iniSettings 
     */
    //public static $iniSettings = array();
    
    /**
     * public running module
     */
    public static $running = null;
    
    /**
     * constructer recieves module list and places them in $this->levels where
     * we can see at which run level modules should be run.
     * 
     * ModuleLoader will call self::getAllModules which in turn will
     * connect first time to database. 
     */
    public function __construct(){
        self::$modules = self::getAllModules(); 
        $this->setLevels();
    }
    
    /**
     * method for setting a status code 403 or 404
     * @param int $code
     */
    public static function setStatus ($code) {
        self::$status[$code] = 1;
    }

    /**
     * method for getting all modules from db. This is the first time we 
     * connect to database. 
     * 
     * @return array $ary array with all rows from modules table
     */
    public static function getAllModules (){

        if (!empty(self::$modules)) {
            return self::$modules;
        }
        
        static $modules = null;
        if ($modules) { 
            return $modules;
        }
        
        // we connect here because this should be 
        // the first time we use the database
        // in the system
        
        $db = new db();
        $db->connect();

        return $db->selectAll('modules');
    }
    
    /**
     * get all installed module name only
     * @return array $ary array of module names 
     */
    public static function getInstalledModuleNames () {
        $mods = self::getAllModules();
        $ins = array ();
        foreach ($mods as $val) {
            $ins[] = $val['module_name'];
        }
        return $ins;
    } 

    /**
     * moduleExists alias of isInstalledModule
     * @param string $module_name
     * @return boolean $res true if module exists else false.  
     */
    public static function moduleExists ($module_name) {
        return self::isInstalledModule($module_name);
    }
    
    
    
    /**
     *
     * get child modules to a parent module
     * @param string $parent name of parent module
     * @return array $ary containing child modules.
     */
    public static function getChildModules ($parent){
        static $children = array();
        if (isset($children[$parent])) return $children[$parent];

        foreach (self::$modules as $val){
            if ($val['parent'] == $parent){
                $children[$parent][] = $val['module_name'];
            }
        }

        if (empty($children[$parent])){
            return array();
        }

        return $children[$parent];
    }

    /**
     * method for getting a modules parent name.
     * @deprecated 2.4
     * @param string    $module the module to examine for a parent
     * @return mixed    $res if a parent module is found we return the parent module name
     *                       else we return null
     */
    public static function getParentModule ($module){        
        static $parent = null;
        if (isset($parent)) { 
            return $parent;
        }
        foreach (self::$modules as $val){
            if ($val['module_name'] != $module) { 
                continue;
            } else {
                if (isset($val['parent'])){
                    return $val['parent'];
                }
            }
        }
        return null;
    }

    /**
     * 
     * method for placeing all modules in $this->levels 
     * according the modules run_levels
     */
    public function setLevels(){
        foreach (self::$modules as $key => $val){
            $module_levels = explode(',', $val['run_level']);
            foreach ($module_levels as $k => $v){
                $this->levels[$v][] = $val['module_name'];
            }
        }
    }

    /**
     * check if a module is installed / exists
     * @param string $module the module we examine
     * @return boolean $res boolean
     */
    public static function isInstalledModule($module){  
        
        if (empty(self::$modules)) {
            $mod = new self();
            self::$modules = $mod->getAllModules();
        }
        foreach (self::$modules as $val){
            if ($val['module_name'] == $module){
                $installed[$module] = true;
                return true;
            }
        }
        return false; 
    }

    /**
     * method for running a module at a exact runlevel.
     * This is used in coslib/head.php (bootstrap file)
     *
     * @param int $level the runlevel to run [1- 7]
     */
    public function runLevel($level){
        if (!isset($this->levels[$level])) return;
        foreach($this->levels[$level] as $val){

            $this->includeModule($val);
            $class = new $val;
            $class->runLevel($level);
        }
    }

    /**
     * method for setting info for home module info
     * home module is set in config/config.ini with frontpage_module
     * home module deals with requests to /
     */
    public function setHomeModuleInfo(){
        
        $frontpage_module = conf::getMainIni('frontpage_module');
        $this->info['is_frontpage'] = true;
        $this->info['module_name'] = $frontpage_module;
        $this->info['module_base_name'] = $frontpage_module;
        $this->info['language_file'] = conf::pathModules() . "/$frontpage_module" . '/lang/' . conf::getMainIni('language') . '/language.inc';
        $this->info['ini_file'] =  conf::pathModules() . "/$frontpage_module"  . "/$frontpage_module" . '.ini';
        
        $controller_dir = conf::pathModules() . "/$frontpage_module/";
        $first = uri::fragment(0);
        
        if (!empty($first)){
            $controller_file = $controller_dir . $first . ".php";
        } else {
            $controller_file = $controller_dir . "index.php";
        }
        $this->info['controller_file'] = $controller_file;
        $this->info['controller'] = 'index';
        $this->info['module_class'] = $this->info['module_name'] . "";
        
    }
    
    /**
     * method for setting info for error module. E.g. we recieve
     * a 404 or 403 from a module, and we want to let the error module
     * take care
     * 
     * Notice: At the moment you can not have your own error module. 
     * But it wil lbe easy to implement at some point. 
     * 
     */
    public function setErrorModuleInfo(){
        
        $error_module = conf::getMainIni('error_module');
        if (!$error_module) {
            $error_module = 'error';
        }
        $this->info['module_name'] = $error_module;
        $this->info['module_base_name'] = $error_module;
        
        $this->info['language_file'] = conf::pathModules() . "/$error_module" . '/lang/' . conf::getMainIni('language'). '/language.inc';
        $this->info['ini_file'] =  conf::pathModules()  . "/$error_module"  . "/$error_module" . '.ini';
        
        if (isset(self::$status[404])){
            $controller_file = conf::pathModules() . "/$error_module". '/404.php';
        }
        if (isset(self::$status[403])){           
            $controller_file = conf::pathModules() . "/$error_module". '/403.php';
        }

        $this->info['controller_file'] = $controller_file;
        $this->info['controller'] = "403.php";
        $this->info['module_class'] = $this->info['module_name'];
        
    }
    

    /**
     * Method for setting the requested module info
     */
    public function setModuleInfo ($route = null){

        // check if user has been locked in early run level
        
        if (isset(self::$status[403]) || isset(self::$status[404])){                     
            $this->setErrorModuleInfo(); 
            return;
        } 
        
        $uri = uri::getInstance($route);
        $info = uri::getInfo();
       
        // if no module_base is set in the URI::info we can will use
        // the home module
        if (empty($info['module_base'])){
            $this->setHomeModuleInfo();
            return;
        }

        // if we only have one fragment 
        // means we are need to load the frontpage module
        $frontpage_module = conf::getMainIni('frontpage_module');
        $this->info['module_name'] = $info['module_name'];
        if ($uri->numFragments() == 1){         
            $this->info['module_base_name'] = $frontpage_module;
            $this->info['module_class'] = $this->info['module_name'];
            $this->info['base'] = conf::pathModules() . "/$frontpage_module";
        } else {
            $this->info['module_base_name'] = $info['module_base_name'];
            $this->info['module_class'] = str_replace('/', '_', $this->info['module_name']);
            $this->info['base'] = conf::pathModules();
        }

        $this->info['language_file'] = conf::pathModules() . $info['module_base'] . '/lang/' . conf::getMainIni('language'). '/language.inc';
        $this->info['ini_file'] =  conf::pathModules() . $info['module_base'] . $info['module_base'] . '.ini';
        $this->info['ini_file_php'] =  conf::pathModules() . $info['module_base'] . $info['module_base'] . '.php.ini';
        $controller_file = conf::pathModules() . $info['controller_path_str'] . '/' . $info['controller'] . '.php';
        
        $this->info['controller_file'] = $controller_file;
        $this->info['controller'] = $info['controller'];
        
        // set module class name from path e.g. content/admin will become contentAdmin 
        // But only if module path exists. In order to prevent clash with web file system. 
        $module_full_path = conf::pathModules() . "/" . $this->info['module_name'];
        if (file_exists($module_full_path)) {
            $this->info['module_class'] = self::modulePathToClassName($this->info['module_name']);
        }
        
        if (!self::isInstalledModule($this->info['module_base_name'])){          
            self::$status[404] = 1;
            $this->setErrorModuleInfo(); 
        }
    }

    /**
     * method for initing a module
     * loads module
     * set module specific template
     * set controller specific page
     */
    public function initModule(){
        
        $module = $this->info['module_name'];  
        self::$running = $module;
        self::includeModule($module);    
    }
    
    /**
     * var holding a reference name
     * @var mixed $reference 
     */
    public static $reference = null;
    
    /**
     * var holding a referenceId
     * @var int $referenceId 
     */
    public static $referenceId = 0;
    
    /**
     * var id holding inline parent id
     * @var int $id
     */
    public static $id;
    
    /**
     * var holding referenceLink. E.g. for pointing back to parent modules
     * page
     * @var string $referenceLink
     */
    public static $referenceLink = null;
    
    /**
     * var holding redirect reference when called object has performed
     * e.g. a correct submission
     * @var string $referenceRedirect
     */
    public static $referenceRedirect = null;
    
    /**
     * var holding reference options sent to called object
     * @var array $referenceOptions
     */
    public static $referenceOptions = null;
    
    /**
     * method for including a reference module
     * @param int $frag_reference_id
     * @param int $frag_id
     * @param string $frag_reference_name
     */
    public static function includeRefrenceModule (
            $frag_reference_id = 2, 
            
            // reserved. Will be set by the module in reference
            // e.g. will be set in files when used in content.
            
            $frag_id = 3,
            $frag_reference_name = 4) {    
        
        return reference::includeRefrenceModule($frag_reference_id, $frag_id, $frag_reference_name);
    }
    
    /** 
     * return all set reference info as an array 
     * @return array $ary array 
     *                      (parent_id, inline_parent_id, reference, link, redirect)
     */
    public static function getReferenceInfo () {
        return reference::getReferenceInfo();
    }

    /**
     * return modules classname from a modules path.
     * e.g. account_profile will return accountProfile
     * e.g. content/article will return contentArticle
     * 
     * @param  string   $path (e.g. account/profile)
     * @return string   $classname (e.g. accountProfile)
     */
    public static function modulePathToClassName ($path){
        return str_replace('/', '_', $path);
    }
    
    /**
     * reference to table name e.g. content/article to content_article
     * @param string $reference
     * @return string $table_name
     */
    public static function moduleReferenceToTable ($reference) {
        return str_replace('/', '_', $reference);
    }
    

    
    /**
     * method for running a parsing module
     * @return string the parsed modules html
     */
    public function getParsedModule(){
     
        $action_str = $this->getParsedModuleAction();  
        if (!file_exists($this->info['controller_file']) && !$this->info['module_action_exists'] ){ 
            self::$status[404] = 1;
            $this->setErrorModuleInfo(); 
        }  else {
            // include controller file or call module action 
            if ($action_str !== false) {
                echo $action_str;        
            } else {
                include_once $this->info['controller_file'];
            }
        }
        
        if (isset(self::$status[403])){
            $this->setErrorModuleInfo();
            $this->initModule();
            include_once $this->info['controller_file'];
        }

        if (isset(self::$status[404])){
            $this->setErrorModuleInfo();
            $this->initModule();
            include_once $this->info['controller_file'];
        }

        $str = ob_get_contents();
        ob_clean();
        return $str;
    }
    
    /**
     * latest way to load is a module, is by checking 
     * if a class a has an action method. Eg. 
     * 
     * We have loaded a blog module and we we are on blog/index
     * The we check the class blog for a method with the name
     * indexAction
     * 
     * This takes precedence over the old method were the actions
     * were placed in the module as files, e.g. blog/index.php
     * 
     */
    
    public function getParsedModuleAction () {
        
        $controller = $this->info['controller'];
        $action = $controller. 'Action';
        $module_class = $this->info['module_class'];
        $var = $action_exists = @is_callable(array ($module_class, $action));

        // We need is a controller
        if (!$action_exists){
            $this->info['module_action_exists'] = false;
            return false;
        } else {
            $this->info['module_action_exists'] = true;
        }
        
        $init_action = 'initAction';            
        $module_object = new $module_class();
        if (method_exists($module_class, $init_action) ) {
            $module_object->$init_action();
        }          
        
        $module_object->$action();
        $str = ob_get_contents();
        ob_clean();    
        return $str;
    }
    
    /**
     * method for setting a modules ini settings.
     * @param string $module
     * @param string $type module or template
     * @return void
     */
    public static function setModuleIniSettings($module, $type = 'module'){

        static $set = array();     
        if (isset($set[$module])) {
            return;
        }
        
        if (!isset(conf::$vars['coscms_main']['module'])){
            conf::$vars['coscms_main']['module'] = array();
        }

        $set[$module] = $module;
        if ($type == 'module') {
            $ini_file = conf::pathModules() . "/$module/$module.ini";
            $ini_locale = conf::pathModules() . "/$module/locale.ini";
        } else {
            $ini_file = conf::pathHtdocs() . "/templates/$module/$module.ini";
            $ini_locale = conf::pathHtdocs() . "/templates/$module/locale.ini";
        }
        
        if (!file_exists($ini_file)) {
            return;
        }

        $module_ini = conf::getIniFileArray($ini_file, true);
        if (is_array($module_ini)){
            conf::$vars['coscms_main']['module'] = array_merge(
                conf::$vars['coscms_main']['module'],
                $module_ini
            );
        }
        
        if (isset($module_ini['development']) && conf::getEnv() =='development' ) {
                conf::$vars['coscms_main']['module'] =
                        array_merge(
                        conf::$vars['coscms_main']['module'],
                        $module_ini['development']
                    );
        }

        // check if stage settings exists.
        if ((isset($module_ini['stage']) && conf::getEnv() =='stage' ) ){
                conf::$vars['coscms_main']['module'] =
                        array_merge(
                        conf::$vars['coscms_main']['module'],
                        $module_ini['stage']
                    );
        }

        
        // load language specific configuration, e.g. en_GB or en or sv
        $language = conf::getMainIni('language');
        if (isset($module_ini[$language])) {
                conf::$vars['coscms_main']['module'] =
                        array_merge(
                        conf::$vars['coscms_main']['module'],
                        $module_ini[$language]
                    );
        }
        
        // check for a locale ini file which only
        // can be added by end user. 
        if (file_exists($ini_locale)) {
            $locale = conf::getIniFileArray($ini_locale, true);
            conf::$vars['coscms_main']['module'] =
                array_merge(
                conf::$vars['coscms_main']['module'],
                $locale
            );
        }
    }
    
    /**
     * method for getting modules pre content. pre content is content shown
     * before the real content of a page. E.g. admin options if any. 
     * 
     * @param array $modules the modules which we want to get pre content from
     * @param array $options spseciel options to be send to the sub module
     * @return string   the parsed modules pre content as a string
     */
    public static function subModuleGetPreContent ($modules, $options) {
        $str = '';
        $ary = array();
        if (!is_array($modules)) return array ();
        foreach ($modules as $val){
            $str = '';
            
            if (!self::isInstalledModule($val)) {
                continue;
            }
            if (method_exists($val, 'subModulePreContent')){
                $str = $val::subModulePreContent($options);
                if (!empty($str)) {
                    $ary[] = $str;
                }
            }
        }       
        return $ary;
    }
    
    /**
     * method for getting sub modules admin options as an array of links
     * 
     * @param array $modules the modules which we want to get pre content from
     * @param array $options spseciel options to be send to the sub module
     * @return array $ary array containing submodule links
     */
    public static function subModuleGetAdminOptions ($modules, $options) {
        $str = '';
        $ary = array();
        
        if (!is_array($modules)) { 
            return array ();
        }
        
        foreach ($modules as $val){
            if (!self::isInstalledModule($val)) {
                continue;
            }
            if (method_exists($val, 'subModuleAdminOption')){
                $str = $val::subModuleAdminOption($options);
                if (!empty($str)) { 
                    $ary[] = $str;
                }
            }
        }
        return $ary;
    }
    
    /**
     * method for getting sub modules admin options as an array of arrays containging
     * array ('url', 'text' ,'link')
     * 
     * @param array $modules the modules which we want to get pre content from
     * @param array $options spseciel options to be send to the sub module
     * @return array $ary array containing submodule links
     */
    public static function subModuleGetAdminOptionsExtended ($modules, $options) {
        $str = '';
        $ary = array();
        
        if (!is_array($modules)) { 
            return array ();
        }
        
        foreach ($modules as $val){
            if (!self::isInstalledModule($val)) {
                continue;
            }
            
            if (method_exists($val, 'subModuleAdminOption')){
                $a = $val::subModuleAdminOptionAry($options);
                if (!empty($a)) { 
                    $ary[] = $a;
                }
            }
        }
        return $ary;
    }
    
    /**
     * method for building a reference url
     * @param string $base
     * @param array $params
     * @return string $url 
     */
    public static function buildReferenceURL ($base, $params) {
        if (isset($params['id'])) {
            $extra = $params['id'];
        } else {
            $extra = 0;
        }
        
        $url = $base . "/$params[parent_id]/$extra/$params[reference]";
        return $url;
    }
    
    /**
     * method for parsing the admin options. As there can be more modules
     * we iritate over an array of sub modules and return the admin menu
     * as a string. 
     * 
     * @param array $ary the array of strings
     * @return string $str string
     */
    public static function parseAdminOptions ($ary = array()){
        $num = count($ary);
        $str = "<div id =\"content_menu\">\n";
        $str.= "<ul>\n";
        foreach ($ary as $val){
            $num--;
            if ($num) {
                $str.= "<li>" . $val . MENU_SUB_SEPARATOR .  "</li>\n";
            } else {
                $str.= "<li>" . $val . "</li>\n";
            }
        }
        $str.= "</ul>\n";
        $str.= "</div>\n";
        return $str;
    }

    /**
     * method for setting inline content
     * @param array $modules
     * @param array $options
     * @return string 
     */
    public static function subModuleGetInlineContent ($modules, $options){
        $ary = array ();
        if (!is_array($modules)) { 
            return $ary;
        }
        foreach ($modules as $val){
            if (!self::isInstalledModule($val)) {
                continue;
            }
            if (method_exists($val, 'subModuleInlineContent')){
                $str = $val::subModuleInlineContent($options);
                if (!empty($str)) { 
                    $ary[] = $str;
                }
            }
        }
        return $ary;
    }

    /**
     * method for getting post content of some modules
     * @param type $modules
     * @param type $options
     * @return string the post content as a string. 
     */
    public static function subModuleGetPostContent ($modules, $options){

        $ary = array ();
        if (!is_array($modules)) return $ary;
        foreach ($modules as $val){
            if (@method_exists($val, 'subModulePostContent') && self::isInstalledModule($val)){
                $str = $val::subModulePostContent($options);
                if (!empty($str)) { 
                    $ary[] = $str;
                }
            }
        }
        return $ary;
        
    }

    /**
     *method for including modules
     * @param array $modules
     * @return false|void   false if no modules where given.  
     */
    public static function includeModules ($modules) {
        if (!is_array($modules)) { 
            return false;
        }
        foreach ($modules as $val) {
            self::includeModule ($val);
        }
    }
    
    /**
     * returns running modules reference info which is an array e.g.
     * array ('reference' => 'content/article', 'parent_id' => 24);
     * @return array $ary e.g. array ('reference' => 'content/article', 'parent_id' => 24);
     */
    public static function getRunningModuleReferenceInfo () {
        // get module running
        $module = self::$running;
        self::includeModule($module);
        $class_name = self::modulePathToClassName($module);
        
        // get reference info (reference, parent_id) from running module
        $obj = new $class_name();
        return $obj->getReferenceInfo();
    }

     
    /**
     * include a module. This will include the module file
     * and load language and configuration
     * @param string $module
     * @retur boolean true on success and false on failure
     */
    public static function includeModule ($module) {
        if (isset(self::$loadedModules['loaded'][$module])){
            return true;
        }
        
        // find base module. 
        // only in base modules we set language and ini settings
        
        $ary = explode('/', $module);
        $base_module = $ary[0]; 
        
        // lang and ini only exists in base module
        lang::loadModuleLanguage($base_module);
        self::setModuleIniSettings($base_module);
        
        // new include style

        $module_file = conf::pathModules() . "/$module/module.php";
        if (file_exists($module_file)) {    
            self::$loadedModules['loaded'][$module] = true;
            include_once $module_file;
        }
        
        return true;

        
    }
    
    /**
     * gets ase module from a sub module or a base module, e.g. shop/cart
     * returns shop
     * @param string $module
     * @return string $base
     */
    public static function getBaseModuleFromModuleName ($module) {
        $ary = explode('/', $module);
        return $ary[0];
    }
    
    /**
     * include template common.inc
     * @param string $template
     */
    public static function includeTemplateCommon ($template) {
        static $included = array ();
        if (!isset($included[$template])) {
            include_once conf::pathHtdocs() . "/templates/$template/common.php";
        }
        $included[$template] = true;
    }
    
    /**
     * method for including a controller
     * @param string $controller
     */
    public static function includeController ($controller) {
        $module_path = conf::pathModules() . '/' . $controller;
        $controller_file = $module_path . '.php';
        include_once $controller_file;
    }
    
    /**
     * method for including filters
     * @param array|string $filters
     */
    public static function includeFilters ($filter) {
        static $loaded = array();

        if (!is_array($filter)){
            self::initFilter($filter);
            $loaded[$filter] = true;
        }

        if (is_array ($filter)){
            foreach($filter as  $val){
                if (isset($loaded[$val])) continue;
                self::initFilter($val);
                $loaded[$val] = true;
            }
        }
    }
    
    /**
     * getting filter help from filters
     * @param array $filters
     * @return string $filters_help
     */
    public static function getFiltersHelp ($filters) {
        if (empty($filters)) { 
            return '';
        }
        
        $str = '<span class="small-font">';
        $i = 1;

        if (is_string($filters)) {
            $ary = array ();
            $ary[] = $filters;
            $filters = $ary;

        }
        
        foreach($filters as $val) {
            $str.= $i . ") " .  lang::translate("filter_" . $val . "_help") . "<br />";
            $i++;
        }
        
        $str.='</span>';
        return $str;
    }
    
    /**
     * get filtered content
     * @param array $filters
     * @param string $content
     * @return string $content
     */
    public static function getFilteredContent ($filter, $content) {
        if (!$filter) { 
            return $content;
        }
        
        if (!is_array($filter)){

            $class_name = "diversen\\filter\\" . $filter;
            $filter_class = new $class_name;

            if (is_array($content)){
                foreach ($content as $key => $val){
                    $content[$key] = $filter_class->filter($val);
                }
            } else {
                $content = $filter_class->filter($content);
            }

            return $content;
        }

        if (!empty ($filter)){

            foreach($filter as $key => $val){
                
                $class_name = 'diversen\filter' . "\\$val"; 
                $filter_class = new $class_name;
                if (is_array($content)){
                    foreach ($content as $key => $val){
                        $content[$key] = $filter_class->filter($val);
                    }
                } else {
                    $content = $filter_class->filter($content);
                }
            }
            return $content;
        }
    return '';
    }
}
