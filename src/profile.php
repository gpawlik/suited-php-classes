<?php

namespace diversen;
use diversen\moduleinstaller;
use diversen\conf;
use diversen\cli\common;
use diversen\git;

/**
 * Class for install, creation, and updating profiles
 * @package profile
 */
class profile  {

    /**
     * holding errors
     * @var array $errors
     */
    public $error = array();

    /**
     * holding confirm message
     * @var string $confirm
     */
    public $confirm = array();

    /**
     * holding modules for profile
     * @var array  $profileModules
     */
    public $profileModules = array();
    
    /**
     * holding default profile template as string
     * @var string $profileTemplates
     */
    public $profileTemplate;

    /**
     * holding all profiles templates as array
     * @var string  
     */
    public $profileTemplates;

    /**
     * hide default secrets like url, user, password etc when building profile
     * @var int $hideSecrets
     */
    public static $hideSecrets = 1;

    /**
     * are we using git master
     * @var boolean $master
     */
    public static $master = null;

    /**
     * Method for setting master flag
     */
    public static function setMaster (){
        self::$master = 1;
    }
    
    /**
     * Flag which indicates if using master
     */
    public static function setNoHideSecrets (){
        self::$hideSecrets = null;
    }

    /**
     * Get all moduls from database
     * @return array $ary Array of all modules
     */
    public static function getModules(){      
        $db = new db();
        $db->connect();
        $modules = $db->selectAll('modules');
        return self::getModulesInfo($modules);
    }
    
    /**
     * Set module info for all modules
     * @param array $modules modules direct from database 'modules' table
     * @return array $modules modules with info set
     */
    public static function getModulesInfo($modules) {
        foreach ($modules as $key => $val) {
            $ret = self::getModuleInfo($val); 
            if (!$ret) {
                common::echoStatus('WARNING', $color, "We could not find any legel git repo for module $val");
                unset($modules[$key]);
            }
            $modules[$key] = $ret;   
        }
        return $modules;
    }
    
    /**
     * Sets clone URLs for a single module
     * @param array $val module
     * @return array $val module
     */
    public static function getModuleInfo($val) {
        $options['module'] = $val['module_name'];
        $mi = new moduleinstaller($options);

        // Find a public clone URL
        if (isset($mi->installInfo['PUBLIC_CLONE_URL'])) {
            $val['public_clone_url'] = $mi->installInfo['PUBLIC_CLONE_URL'];
        } else {
            $val['public_clone_url'] = self::getCloneUrl($val['module_name']);
            $val['public_clone_url'] = git::getHttpsFromSsh($val['public_clone_url']);           
        }

        // Set private clone URL
        if (isset($mi->installInfo['PRIVATE_CLONE_URL'])) {
            $val['private_clone_url'] = $mi->installInfo['PRIVATE_CLONE_URL'];
        } else {
            $val['private_clone_url'] = self::getCloneUrl($val['module_name']);
            $val['private_clone_url'] = git::getSshFromHttps($val['private_clone_url']);
        }

        if (self::$master) {
            $val['module_version'] = 'master';
        }
        
        return $val;
    }

    /**
     * Try to set a public clone URL in a module array
     * @param array $val module info
     * @return array $val module info with a public clone URL. If a public clone URL exists
     */
    public static function getCloneUrl($module_name) {

        $module_path = conf::pathModules() . "/$module_name";
        if (!file_exists($module_path)) {
            common::echoStatus('NOTICE', 'y', "module $module_name has no module source");
            return $val;
        }

        $command = "cd $module_path && git config --get remote.origin.url";
        $ret = common::execCommand($command, array('silence' => 1), 0);
        if ($ret == 0) {
            $git_url = shell_exec($command);
            return trim($git_url);
        }
        return false;
    }

    /**
     * Get a single row from 'modules' table
     * @param string $module the module row to get
     * @return array|false $ary module
     */
    public function getModule ($module) {
        $mods = $this->getModules();
        foreach ($mods as $mod) {
            if ($mod['module_name'] == $module) {
                return $mod;
            }
        }
        return false;
    }
    
    /**
     * Get a single template
     * @param string $template
     * @return array|false $ary template
     */
    public function getTemplate ($template) {
        $temps = $this->getTemplates();
        foreach ($temps as $temp) {
            if ($temp['module_name'] == $template) {
                return $temp;
            }
        }
        return false;
    }

    /**
     * Create profile with specified profile name
     * @param string $profile the profile name
     */
    public function createProfile($profile){
        $this->createProfileFiles($profile);
        $this->createProfileScript($profile);
        $this->createConfigIni($profile);
    }

    /**
     * Re-ceate profile with specified profile name
     * Re-create don't touch config.ini
     * @param string $profile the profile name
     */
    public function recreateProfile($profile){
        $this->createProfileFiles($profile);
        $this->createProfileScript($profile);
    }

   
    /**
     * method for getting all templates located in conf::pathHtdocs()/template
     * used for settings current templates in profiles/profile/profile.inc file
     */
    public function getTemplates (){
        $dir = conf::pathHtdocs() . "/templates";
        $templates = file::getFileList($dir, array('dir_only' => true));

        $ary = array ();
        foreach ($templates as $val){
            $info = $this->getSingleTemplate($val);
            if (empty($info)) {
                continue;
            }
            $ary[] = $info;
        }
        return $ary;
    }
    
    /**
     * Get install info for a single template
     * @param string $template
     * @return array $val template info
     */
    public function getSingleTemplate($template) {
        
        $template_dir = conf::pathHtdocs() . "/templates";
        $install_file = $template_dir . "/$template/install.inc";
        
        $val = array();
        if (file_exists($install_file)) {
            
            include $install_file;
            $val['public_clone_url'] = $_INSTALL['PUBLIC_CLONE_URL'];
            $val['private_clone_url'] = $_INSTALL['PRIVATE_CLONE_URL'];
            if (!self::$master) {
                if (isset($_INSTALL['VERSION'])) {
                    $val['module_version'] = "$_INSTALL[VERSION]";
                } else {
                    $tags = git::getTagsModule($template, 'template');
                    $val['module_version'] = array_pop($tags);
                }
            } else {
                $val['module_version'] = "master";
            }
            $val['module_name'] = $template;
        } else { 

            // check if this a git repo
            $path = conf::pathHtdocs() . "/templates/$template";
            $command = "cd $path && git config --get remote.origin.url";
            exec($command, $output, $ret);
            if ($ret != 0) {
                return false;
            }
            
            $git_url = shell_exec($command);
            $tags = git::getTagsModule($template, 'template');
            $latest = array_pop($tags);

            if (!self::$master) {
                $val['module_version'] = $latest;
            } else {
                $val['module_version'] = "master";
            }

            $val['module_name'] = $template;
            $val['public_clone_url'] = trim($git_url);
        }  
        return $val;
    }

    /**
     * Create a profile. A PHP file with configuration about an profile install
     * @param string $profile the profile name
     */
    public function createProfileScript($profile){
        
        // Get moduls as a string
        $modules = $this->getModules();
        $module_str = var_export($modules, true); 
        
        // Get tempaltes as a string
        $templates = $this->getTemplates();
        $template_str = var_export($templates, true);
        
        // Compose profile script
        $profile_str = '<?php ' . "\n\n";
        $profile_str.= '$_PROFILE_MODULES = ' . $module_str . ";";
        $profile_str.= "\n\n";
        $profile_str.= '$_PROFILE_TEMPLATES = ' . $template_str . ";";
        $profile_str.= "\n\n";
        $profile_str.= '$_PROFILE_TEMPLATE = ' . "'" . $this->getProfileTemplate() . "'" . ';';
        $profile_str.= "\n\n";
        $file = conf::pathBase() . "/profiles/$profile/profile.inc";
        if (!file_put_contents($file, $profile_str)){
            common::abort("Could not write to file: $file");
        }
    }

    /**
     * Method getting a profile's template
     * @return string $str name of current running template
     */
    public function getProfileTemplate (){
        $db = new db();
        $db->connect();
        $row = $db->selectOne('settings', 'id', 1);
        return $row['template'];
    }

    /**
     * Method for setting a profile's template
     * @param string $template
     * @return boolean $res
     */
    public function setProfileTemplate ($template = null){
        $db = new db();
        $db->connect();
        if (isset($template)){
            $this->profileTemplate = $template;
        }

        $ini_file = conf::pathHtdocs() . "/templates/$this->profileTemplate/$this->profileTemplate.ini";
        $ini_file_dist = $ini_file . "-dist";

        if (conf::isCli()) {
            if (file_exists($ini_file_dist)){
                copy($ini_file_dist, $ini_file);
            }
        }
        $values = array('template' => $this->profileTemplate);
        return $db->update('settings', $values, 1);        
    }


    /**
     * method for creating a profiles configuration files
     * this is all modules .ini files
     * @param   string  name of profile to be created
     */
    private function createProfileFiles($profile){
        
        // Create profile dir
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        if (!file_exists($profile_dir)) {
            $mkdir = @mkdir($profile_dir);
            if (!$mkdir){
                common::abort("Could not make dir: '$profile_dir'");
            }
        }
        
        $modules = $this->getModules();
        foreach ($modules as $key => $val){

            $source = conf::pathModules() . "/$val[module_name]/$val[module_name].ini";

            // if no ini we just skip           
            if (!file_exists($source)) { 
                continue;
            }
            
            $ary = conf::getIniFileArray($source, true);
            $ary = $this->iniArrayPrepare($ary);               
            $config_str = conf::arrayToIniFile($ary);

            // Module ini file
            $dest = $profile_dir . "/$val[module_name].ini-dist";
            file_put_contents($dest, $config_str);

            // PHP config file
            $source = conf::pathModules() . "/$val[module_name]/config.php";
            $dest = $profile_dir . "/$val[module_name].php-dist";

            if (file_exists($source)){
                copy($source, $dest);
            }
        }
        
        $templates = $this->getTemplates();
        foreach ($templates as $key => $val){

            $source = conf::pathHtdocs() . "/templates/$val[module_name]/$val[module_name].ini";
            $dest = $profile_dir . "/$val[module_name].ini-dist";
            
            // templates does not need to have an ini file
            if (file_exists($source)) {
                if (copy($source, $dest)){
                    $this->confirm[] = "Copied $source to $dest";
                } else {
                    $this->error[] = "Could not copy $source to $dest";
                }
            }
        }       
    }

    /**
     * Method for creating a main config.ini file
     * @param string $profile name of the profile
     */
    private function createConfigIni($profile){
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        $source = conf::pathBase() . "/config/config.ini";  
        $ary = conf::getIniFileArray($source, true);
        $ary = $this->iniArrayPrepare($ary);  
        $config_str = conf::arrayToIniFile($ary);     
        file_put_contents($profile_dir . "/config.ini-dist", $config_str);
    }
    
    /**
     * remove secrets from an ini file aray
     * @param array $ary
     * @return array $ary
     */
    public function iniArrayPrepare ($ary) {
       
        if (!self::$hideSecrets) {
            return $ary;
        }
        
        $secrets =array (
            'username', 
            'password',
            'smtp_params_username',
            'smtp_params_password',
            'ssh_host',
            'ssh_port',
            'account_facebook_api_secret',
            'account_google_secret',
            'account_github_secret',
            'imap_user',
            'imap_password',
            'google_translate_key',
            'comment_akismet_key',
            'session_handler'
        );
        
        foreach ($ary as $key => &$val) {
            if (is_array($val)) {
                foreach ($val as $k2  => $v2) {
                    if (in_array($v2, $secrets)) {
                        $val[$k2] = '';
                    }
                }
            }
            if (in_array($key, $secrets)) {
                $ary[$key] = '';
            }
        }
        return $ary;    
    }

    /**
     * Method for setting info about profile
     * @param string $profile name of the profile
     */
    public function setProfileInfo($profile){
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        if (!file_exists($profile_dir)) {
            common::abort( "No such path to profiles: $profile_dir");
        } 
        
        include $profile_dir . "/profile.inc";
        $this->profileModules = $_PROFILE_MODULES;
        $this->profileTemplates = $_PROFILE_TEMPLATES;
        $this->profileTemplate = $_PROFILE_TEMPLATE;
    }
    
    /**
     * Checks if a module exists in a memory loaded profile 
     * @param string $module
     * @return boolean $res
     */
    public function isModuleInProfile ($module) {
        foreach ($this->profileModules as $val){
            if ($val['module_name'] == $module){
                return true;
            }
        }
        return false;      
    }

    /**
     * Checks if a template exists in a memory loaded profile
     * @param string $template
     * @return boolean $res
     */
    public function isTemplateInProfile ($template) {
        foreach ($this->profileTemplates as $val){
            if ($val['module_name'] == $template){
                return true;
            }
        }
        return false;      
    }

    /**
     * Method for loading a profile
     * @param string $profile the name of the profile
     */
    public function loadProfile($profile){
        $this->setProfileInfo($profile);
        $this->loadProfileFiles($profile);
        $this->loadConfigIni($profile);
    }

    /**
     * Method for reloading a profile. 
     * Same as loadProfile except that this does not copy main config.ini
     * @param   string   profile
     */
    public function reloadProfile($profile){
        $this->setProfileInfo($profile);
        $this->loadProfileFiles($profile);
    }
    
    /**
     * Method for loading all module's configuration, contained in a profile 
     * @param string $profile name of the profile
     */
    public function loadProfileFiles($profile){
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        
        foreach ($this->profileModules as $key => $val){
            $source = $profile_dir . "/$val[module_name].ini-dist";
            $dest = conf::pathModules() . "/$val[module_name]/$val[module_name].ini";
    
            if (copy($source, $dest)){
                $this->confirm[] = "Copy $source to $dest";
            } else {
                $this->error[] = "Could not copy $source to $dest";
            }

            // If a PHP config.php file exists, then copy that too.
            $source = $profile_dir . "/$val[module_name].php-dist";
            $dest = conf::pathModules() . "/$val[module_name]/config.php";
            

            if (file_exists($source)){
                copy($source, $dest);
            }
        }
        
        foreach ($this->profileTemplates as $key => $val){
            $source = $profile_dir . "/$val[module_name].ini-dist";
            $dest = conf::pathHtdocs() . "/templates/$val[module_name]/$val[module_name].ini";
    
            if (file_exists($source)) {
                if (copy($source, $dest)){
                    $this->confirm[] = "Copy $source to $dest";
                } else {
                    $this->error[] = "Could not copy $source to $dest";
                }
            }
        } 
    }

    /**
     * Method for loading main configuration file for a profile
     * @param string $profile the name of profile
     */
    public function loadConfigIni($profile){
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        $dest = conf::pathBase() . "/config/config.ini";
        $source = $profile_dir . "/config.ini-dist";
        if (copy($source, $dest)){
            $this->confirm[] = "Copy $source to $dest";
        } else {
            $this->error[] = "Could not Copy $source to $dest";
        }
    }
}
