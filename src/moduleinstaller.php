<?php

namespace diversen;

use diversen\cli\common;
use diversen\conf;
use diversen\db;
use diversen\git;
use PDOException;

/**
 * Class for installing modules.
 * Copy configuration, and install SQL
 * @package  moduleinstaller
 */
class moduleinstaller  {

    /**
     * holding array of info for the install
     * this is loaded from install.inc file and will read
     * the $_INSTALL var
     * @var array $installInfo 
     */
    public $installInfo = array();

    /**
     * holding error
     * @var string $error
     */
    public $error = null;
    
    /**
     * var holding notices
     * @var string 
     */
    public $notice = null;

    /**
     * holding confirm message
     * @var string $confirm
     */
    public $confirm;

    /**
     * Connect to database
     * Set some install options
     *
     * @param   array $options
     *                array ('module', 'profile'
     */
    public function __construct($options = null){
        
        $db = new db();
        $db->connect();
        
        if (isset($options)){
            return $this->setInstallInfo($options);
        }
    }

    /**
     * 
     * @param   array $options
     */
    public function setInstallInfo($options){
        
        // Base info
        $module_name = $options['module'];
        $module_dir = conf::pathModules() . "/$module_name";
        
        // ini file info
        $ini_file = $module_dir . "/$module_name.ini";
        $ini_file_dist = $module_dir . "/$module_name.ini-dist";

        // If profile is set. Then use profile's module ini dist settings
        if (isset($options['profile'])){
            $ini_file_dist = conf::pathBase() . "/profiles/$options[profile]/$module_name.ini-dist";
        }

        // If module_dir already exists, then try to load ini settings of the module
        if (file_exists($module_dir)){
            
            // load base module settings
            $this->generateInifile($ini_file, $ini_file_dist);
            
            // merge in locale.ini settings if found
            $this->loadLocaleIniSettings($module_dir);
            
            // load install.inc if found
            $install_file = "$module_dir/install.inc";
            if (!file_exists($install_file)){
                $status = "Notice: No install file '$install_file' found in: '$module_dir'";
                common::echoStatus('NOTICE', 'y', $status);
            }
            $this->loadInstallFile($module_name, $install_file);
            
            
        } else {
            $status = "No module dir: $module_dir";
            common::echoStatus('NOTICE', 'y', $status);
            return false;
        }
    }
    
    /**
     * overload ini settings with locale.ini, if file is found in module_dir
     * @param string $module_dir
     */
    public function loadLocaleIniSettings($module_dir) {

        // If locale.ini is found, theń merge configuration
        $ini_locale = $module_dir . "/locale.ini";
        if (file_exists($ini_locale)) {
            $locale = conf::getIniFileArray($ini_locale, true);
            conf::$vars['coscms_main']['module'] = array_merge(
                    conf::$vars['coscms_main']['module'], $locale
            );
        }
    }

    /**
     * Generate an ini file if one is not found, and load module ini settings
     * @param string $ini_file
     * @param string $ini_file_dist
     */
    public function generateInifile($ini_file, $ini_file_dist) {
        if (!file_exists($ini_file)) {
            if (file_exists($ini_file_dist)) {
                copy($ini_file_dist, $ini_file);
                conf::$vars['coscms_main']['module'] = conf::getIniFileArray($ini_file);
            }
        } else {
            conf::$vars['coscms_main']['module'] = conf::getIniFileArray($ini_file);
        }
    }

    /**
     * load install.inc file, and set installInfo
     * @param string $module_name
     * @param string $install_file
     */
    public function loadInstallFile($module_name, $install_file) {
        // set a default module_name - which is the module dir
        $this->installInfo['NAME'] = $module_name;

        // load install.inc if found
        if (file_exists($install_file)) {
            include $install_file;

            $this->installInfo = $_INSTALL;
            $this->installInfo['NAME'] = $module_name;

            // Has menu item
            if (empty($this->installInfo['MAIN_MENU_ITEM'])) {
                $this->installInfo['menu_item'] = 0;
            } else {
                $this->installInfo['menu_item'] = 1;
            }

            // run levels
            if (empty($this->installInfo['RUN_LEVEL'])) {
                $this->installInfo['RUN_LEVEL'] = 0;
            }
        }

        // If no version is found, then check if this is a git repo
        // If it is, then use latest tag for install

        if (!isset($this->installInfo['VERSION']) && conf::isCli()) {
            $this->setInstallInfoFromGit();           
        }
    }
    
    /**
     * If no version info was found in install.inc, try to fetch a version
     * from a git repo, and set the version in installInfo
     */
    public function setInstallInfoFromGit($type = 'module') {
        if ($type == 'module') {
            $base = conf::pathModules();
        } else {
            $type = conf::pathHtdocs();
        }
               
        $tags = git::getTagsModule($this->installInfo['NAME'], 'module');
        if (empty($tags)) {
            $latest = 'master';
        } else {
            $latest = array_pop($tags);
        }
        $this->installInfo['VERSION'] = $latest;
    }

    /**
     * Check if module is installed
     * @param string $module 
     * @return  boolean $res true or false
     */
    public function isInstalled($module = null){

        if (!isset($module)){
            $module = $this->installInfo['NAME'];
        }

        $db = new db();
        $row = $db->selectOne('modules', 'module_name', $module);
        if (!empty($row)){    
            return true;
        }
        return false;
    }

    /**
     * get module info from db
     * @return array|false  $res
     */
    public function getModuleDbInfo(){

        $db = new db();
        $row = $db->selectOne('modules', 'module_name', $this->installInfo['NAME'] );
        if (!empty($row)){
            return $row;
        }
        return false;
    }

    /**
     * Get an array of all modules
     * @return array  $modules assoc array of all modules
     */
    public static function getModules(){
        $db = new db();
        $db->connect();
        $modules = $db->selectAll('modules');
        return $modules;
    }
    
    /**
     * Get array of modules from file system.
     * @return array $base_dirs
     */
    public static function getModulesFromDir () {
        return file::getDirsGlob(conf::pathModules(), array('basename' => 1));
    }

    /**
     * Get all templates from file system
     * @return array $templates
     */
    public function getTemplates () {
        $dir = conf::pathHtdocs() . "/templates";
        $templates = file::getFileList($dir, array('dir_only' => true));
        return $templates;
    }
    
    /**
     * Reloads install config for all modules
     */
    public function reloadConfig () {
        $db = new db();
        $modules = $this->getModules();
        foreach ($modules as $val){
            $this->setInstallInfo($options = array ('module' => $val['module_name']));
            if (isset($this->installInfo['IS_SHELL']) && $this->installInfo['IS_SHELL'] == '1') {
                $db->update(
                        'modules', 
                        array('is_shell' => 1), 
                        array ('module_name' => $val['module_name'])
                        );
            } else {
                $db->update(
                        'modules', 
                        array('is_shell' => 1), 
                        array ('module_name' => NULL)
                        );
            }
            
            if (isset($this->installInfo['RUN_LEVEL'])) {
                $db->update(
                        'modules', 
                        array('run_level' => $this->installInfo['RUN_LEVEL']), 
                        array('module_name' => $val['module_name']));
            }
            
            $this->deleteMenuItem();
            $this->insertMenuItem();
            $this->insertRoutes();
        }
    }
    
    /**
     * Method for deleting DB routes for a module
     */
    public function deleteRoutes () {
        $db = new db();
        $db->delete('system_route', 'module_name', $this->installInfo['NAME']);
    }
    
    /**
     * Method for inserting routes for a module
     */
    public function insertRoutes () {
        $db = new db();
        if (isset($this->installInfo['ROUTES'])) {
            $db->delete('system_route', 'module_name', $this->installInfo['NAME']);
            $routes = $this->installInfo['ROUTES'];
            $this->insertRoutesDb($routes);
        }
    }
    
    /**
     * Insert routes into route table
     * @param array $routes
     */
    public function insertRoutesDb($routes) {
        $db = new db();
        foreach ($routes as $val) {
            foreach ($val as $route => $value) {
                $insert = array(
                    'module_name' => $this->installInfo['NAME'],
                    'route' => $route,
                    'value' => serialize($value));

                $db->insert('system_route', $insert);
            }
        }
    }

    /**
     * get single SQL file name from 'module_name', 'version', and action
     * @param  string   $module
     * @param  float    $version
     * @param  string   $action (up or down)
     * @return string   sql filename
     */
    public function getSqlFileName($module, $version, $action){
        $sql_file = conf::pathModules() . "/$module/mysql/$action/$version.sql";
        return $sql_file;
    }

    /**
     * Get a SQL string from module, version, action
     * @param   string   $module
     * @param   float    $version
     * @param   string   $action (up or down)
     * @return  string   $sql
     */
    public function getSqlFileString($module, $version, $action){
        $sql_file = $this->getSqlFileName($module, $version, $action);
        if (file_exists($sql_file)){
            $sql = file_get_contents($sql_file);
        }
        return $sql;
    }

    /**
     * Get a SQL file list from module, and action
     * @param   string   $module
     * @param   string   $action (up or down)
     * @return  array    $ary array with file list
     */
    public function getSqlFileList($module, $action){
        $sql_dir = conf::pathModules() . "/$module/mysql/$action";
        $file_list = file::getFileList($sql_dir);
        if (is_array($file_list)){
            return $file_list;
        } else {
            return array();
        }   
    }

    /**
     * Get sql file list ordered by floats
     * @param   string   $module
     * @param   string   $action
     * @param   float    specific version
     * @param   float    current version
     * @return  array    array sorted according to version
     */
    public function getSqlFileListOrdered($module, $action, $specific = null, $current = null){
        $ary = $this->getSqlFileList($module, $action);
        asort($ary);
        if (isset($specific)){
            $ary = array_reverse($ary);
            if ($specific == 1 ) {
                foreach ($ary as $key => $val){
                    $val = substr($val, 0, -4);
                    if ($val > $current) {
                        unset($ary[$key]);
                    }
                }
                return $ary;
            } else {
                foreach ($ary as $key => $val){
                    $val = substr($val, 0, -4);
                    if ($val < $specific ){
                        unset($ary[$key]);
                    }
                    if ($val > $current) {
                        unset($ary[$key]);
                    }
                }
                return $ary;
            }
        }

        return $ary;
    }

    /**
     * method for inserting values into module registry
     * adds a row to module register in database
     * @return  boolean true on success false on failure
     */
    public function insertRegistry (){
          
        $db = new db();
        if (!isset($this->installInfo['menu_item'])) {
            $this->installInfo['menu_item'] = 0;
        }
          
        if (!isset($this->installInfo['RUN_LEVEL'])) {
            $this->installInfo['RUN_LEVEL'] = 0;
        }

        $values = array (
            'module_version' => $this->installInfo['VERSION'],
            'module_name' => $this->installInfo['NAME'],
            'menu_item' => $this->installInfo['menu_item'],
            'run_level' => $this->installInfo['RUN_LEVEL']);
        
        if (isset($this->installInfo['LOAD_ON'])){
            $values['load_on'] = $this->installInfo['LOAD_ON'];
        }

        if (isset($this->installInfo['IS_SHELL'])){
            $values['is_shell'] = $this->installInfo['IS_SHELL'];
        }

        if (isset($this->installInfo['PARENT'])){
            $values['parent'] = $this->installInfo['PARENT'];
        }

        return $db->insert('modules', $values);
    }

    /**
     * Method for creating the modules main menu items
     * @return  boolean $res true on success false on failure
     */
    public function insertMenuItem(){
        $res = null;

        $db = new db();
        moduleloader::setModuleIniSettings($this->installInfo['NAME']);
        
        if (!empty($this->installInfo['MAIN_MENU_ITEM'])){
            $values = $this->installInfo['MAIN_MENU_ITEM'];
            $values['title'] = $values['title'];          
            $res = $db->insert('menus', $values);
        }
 
        if (!empty($this->installInfo['MAIN_MENU_ITEMS'])) {
            foreach ($this->installInfo['MAIN_MENU_ITEMS'] as $val) {
                $val['title'] = $val['title'];
                $res = $db->insert('menus', $val);
            }
        }      
        return $res;
    }

    /**
     * Delete a menu item from database
     * @param  string $module 
     * @return boolean $res 
     */
    public function deleteMenuItem($module = null){

        $db = new db();
        if (!isset($module)){
            $module = $this->installInfo['NAME'];
        }
        $result = $db->delete('menus', 'module_name', $module);
        return $result;
    }

    /**
     * method for updating module version in module registry
     *
     * @param float $new_version the version to upgrade to
     * @param int $id the id of the module to be upgraded
     * @return boolean true or throws an error on failure
     */
    public function updateRegistry ($new_version, $id){
        $db = new db();
        $values = array (
            'module_version' => $new_version,
            'run_level' => $this->installInfo['RUN_LEVEL']);

        $result = $db->update('modules', $values, $id);
        return $result;
    }

    /**
     * Delete a module from registry
     * @return boolean true or throws an error on failure
     */
    public function deleteRegistry (){
        $db = new db();
        $result = $db->delete('modules', 'module_name', $this->installInfo['NAME']);
        return $result;
    }
    
    /**
     * Checks if an ini file exists, and creates an ini file if it not exists. 
     * @return boolean $res true on success and false on failure. 
     */
    public function createIniFile () {

        $module = $this->installInfo['NAME'];
        $module_path = conf::pathModules();
        
        $ini_file = "$module_path/$module/$module.ini";
        $ini_file_dist = "$module_path/$module/$module.ini-dist";
        if (!file_exists($ini_file) && file_exists($ini_file_dist)){
            if (!copy($ini_file_dist, $ini_file)){
                $this->error = "Error: Could not copy $ini_file to $ini_file_dist" . PHP_EOL;
                return false;
            }
        }
        
        $ini_file_php = "$module_path/$module/config.php";
        $ini_file_dist_php = "$module_path/$module/config.php-dist";
        if (!file_exists($ini_file_php) && file_exists($ini_file_dist_php)){
            if (!copy($ini_file_dist_php, $ini_file_php)){
                $this->error = "Error: Could not copy $ini_file_php to $ini_file_dist_php" . PHP_EOL;
                return false;
            }
        }
        return true;
    }

    /**
     * create SQL on module install
     * There is not much error checking, because it is not possible to enable 
     * commit and rollback on table creation (Not with MySQL)
     */
    public function executeInstallSQL () {

        $updates = $this->getSqlFileListOrdered($this->installInfo['NAME'], 'up');

        // perform sql upgrade. We upgrade only to the version nmber
        // set in module file install.inc. 
        if (!empty($updates)){            
            foreach ($updates as $val){
                $this->executeSqlUp($val);
            }
        }
        return true;
    }
    
    /**
     * run SQL statements for a single version, e.g. 1.02 
     * @param string $val path to file to read sql from
     */
    public function executeSqlUp($val) {
        $version = substr($val, 0, -4);
        if ($this->installInfo['VERSION'] >= $version) {
            $sql = $this->getSqlFileString(
                    $this->installInfo['NAME'], $version, 'up');

            $sql_ary = explode("\n\n", $sql);
            foreach ($sql_ary as $sql_val) {
                db::$dbh->exec($sql_val);
            }
        }
    }

    /**
     * method for installing a module.
     * checks if module already is installed, if not we install it.
     *
     * @return  boolean $res true on success or false on failure
     */
    public function install (){

        $ret = $this->isInstalled();
        if ($ret){
            $info = $this->getModuleDbInfo();
            $this->error = "Error: module '" . $this->installInfo['NAME'] . "' version '$info[module_version]'";
            $this->error.= " already exists in module registry!";
            return false;
        }

        // create ini file
        $res = $this->createIniFile ();
        if (!$res) {
            $this->confirm.= "module '" . $this->installInfo['NAME'] . "' does not have an ini file";        
        }

        // generate SQL
        $res = $this->executeInstallSQL () ;
        if (!$res) { 
            return false;
        }
        
        // insert into registry. Set menu item and insert language.
        $this->insertRegistry();
        $this->insertMenuItem();
        $this->insertRoutes();
        
        // Confirm message
        $this->confirm = "Module: '" . $this->installInfo['NAME'] . "' ";
        $this->confirm.= "Version: '"  . $this->installInfo['VERSION'] . "' ";
        $this->confirm.= "was installed";
        return true;  
    }

    /**
     * method for uninstalling a module
     * @return boolean $res true on success or false on failure
     */
    public function uninstall(){
        
        if (!$this->isInstalled()){
            $this->error = "Module '" . $this->installInfo['NAME'];
            $this->error .= "' does not exists in module registry!";
            return false;
        }

        $row = $this->getModuleDbInfo();
        $current_version = $row['module_version'];
        $downgrades = $this->getSqlFileListOrdered(
                $this->installInfo['NAME'], 'down',
                1, $current_version);

        $this->deleteRegistry();
        $this->deleteMenuItem();
        $this->deleteRoutes();
        
        $this->executeUninstallSQL($downgrades);        
        
        $commit = 1;

        // set a confirm message
        if (isset($commit)){
            $this->confirm = "module '" . $this->installInfo['NAME'] . "' ";
            $this->confirm.= "version '" . $this->installInfo['VERSION'] . "' ";
            $this->confirm.= "uninstalled";
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * execute all downgrades in order to uninstall the module
     * @param array $downgrades
     */
    public function executeUninstallSQL($downgrades = array()) {
        $specific = 0;
        if (!empty($downgrades)) {
            foreach ($downgrades as $val) {
                $version = substr($val, 0, -4);
                if ($version <= $specific) {
                    continue;
                }
                $sql = $this->getSqlFileString(
                        $this->installInfo['NAME'], 
                        $version, 
                        'down');
                
                $this->executeSqlDown($sql);
            }
        }
    }
    
    /**
     * execute a single SQL string from file 
     * @param string $sql
     */
    public function executeSqlDown($sql) {
        if (isset($sql)) {
            $sql_ary = explode("\n\n", $sql);
            foreach ($sql_ary as $sql_val) {
                db::$dbh->query($sql_val);
            }
        }
    }

    /**
     * Upgrade to a specific version of a module
     * @param float $specific, e.g. '5.06'
     * @return boolean $res
     */
    public function upgrade ($specific = null){
        
        // Only upgrade if module is installed
        if (!moduleloader::isInstalledModule($this->installInfo['NAME'])) {
            common::echoMessage("Notice: Can not upgrade. You will need to install module first");
            return;
        }
        
        // Get specific version
        if (!isset($specific)){
            $specific = $this->installInfo['VERSION'] ;
        }
        
        // Get current module version from registry
        $row = $this->getModuleDbInfo();
        $current_version = $row['module_version'];

        // Same version. Return
        if ($current_version == $specific) {
            $this->confirm = "Module '" . $this->installInfo['NAME'] ."'. Version is '$specific'. No upgrade to perform";
            return true;
        }
        
        // Get a list of SQL updates to perform
        $updates = $this->getSqlFileListOrdered($this->installInfo['NAME'], 'up');

        // perform sql upgrade
        if (!empty($updates)){
            foreach ($updates as $key => $val){
                $possible_versions = '';
                $version = substr($val, 0, -4);
                if ($version == $specific){
                    $version_exists = true;
                } else {
                    $possible_versions.="$version ";
                }
            }
            
            if (!isset($version_exists)){
                $this->error = 'Module SQL ' . $this->installInfo['NAME'] . " ";
                $this->error.= 'does not have such a version. Possible version are: ';
                $this->error.= $possible_versions;
            }
            
            // perform SQL updates found in .sql files
            foreach ($updates as $key => $val){
                $version = substr($val, 0, -4);
                if ($current_version < $version ) {
                    $this->executeSqlUpgrade($version);
                }
            }   
        }
       
        // update registry
        $this->updateRegistry($specific, $row['id']);
        if ( $specific > $current_version ){
            $this->confirm = "Module '" . $this->installInfo['NAME'] . "'. ";
            $this->confirm.= "Version '" . $specific . "' installed. ";
            $this->confirm.= "Upgraded from $current_version";
            return true;
        } else {
            $this->confirm = "Module '" . $this->installInfo['NAME'] . "'. Nothing to upgrade. Module version is still $current_version";
            return true;
        }
    }
    
    /**
     * execute SQL upgrades
     * @param float $version e.g. 1.04
     */
    public function executeSqlUpgrade($version) {
        $sql = $this->getSqlFileString(
                $this->installInfo['NAME'], 
                $version, 
                'up');
        $sql_ary = explode("\n\n", $sql);
        foreach ($sql_ary as $sql_val) {
            if (empty($sql_val)) {
                continue;
            }

            db::$dbh->query($sql_val);
        }
    }
}
