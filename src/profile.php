<?php

namespace diversen;
use diversen\moduleinstaller;
use diversen\conf;

/**
 * *
 * File which contains class for creating profiles which is complete systems
 * with modules and templates
 * @package    profile
 */

/**
 * class for installing a profile or creating one from current install
 *
 * @package    profile
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
     * if true we use home if false we don't.
     * @var boolean  $profileUseHome
     */
    public $profileUseHome;

    /**
     * var holding db 
     * @var object $db
     */
    public $db = null;
    
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
     * @ignore
     * constructor 
     */
    function __construct(){
        
    }

    /**
     * method for setting master
     */
    function setMaster (){
        self::$master = 1;
    }
    
    /**
     * method for setting master
     */
    function setNoHideSecrets (){
        self::$hideSecrets = null;
    }

    /**
     * method for getting all installed modules with repo info set
     * which we will base our profile on.
     * @return array $ary assoc array of all modules
     */
    public static function getModules(){
        $db = new db();
        $db->connect();
        $modules = $db->selectAll('modules');

        foreach ($modules as $key => $val){
            $options['module'] = $val['module_name'];
            $mi = new moduleinstaller($options);

            // check for a public clone url
            if (isset($mi->installInfo['PUBLIC_CLONE_URL'])) {
                $modules[$key]['public_clone_url'] = $mi->installInfo['PUBLIC_CLONE_URL'];
            } else {
                
                // try to find public clone url
                $status = "module $val[module_name] has no public clone url set. We try to guess it.";
                cos_cli_print_status('NOTICE', 'y', $status);
                $module_path = conf::pathModules() . "/$val[module_name]";
                if (!file_exists($module_path)) {
                    cos_cli_print_status('NOTICE', 'y', "module $val[module_name] has no module source");
                    continue;
                } 
                
                $command = "cd $module_path && git config --get remote.origin.url";              
                $ret = cos_exec($command, null, 0);
                if (!$ret) { 
                    $git_url = shell_exec($command);
                    $modules[$key]['public_clone_url'] = $git_url;
                }

            }
            
            if (isset($mi->installInfo['PRIVATE_CLONE_URL'])) {
                $modules[$key]['private_clone_url'] = $mi->installInfo['PRIVATE_CLONE_URL'];
            } else {
                $status = "No private clone url is set for module $val[module_name]";
                cos_cli_print_status('NOTICE', 'y', $status);
            }
            
            if (self::$master){
                $modules[$key]['module_version'] = 'master';
            }
        }
        return $modules;
    }
    
    /**
     * get a single module from modules table with repo info set
     * @param string $module
     * @return array|false $ary array with info if module exists else false
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
     * get a single template from templates dir with repo info set
     * @param string $module
     * @return array|false $ary array with info if template exists else false
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
     * method for creating a profile from profile name
     * @param string $profile
     */
    public function createProfile($profile){

        // create all files
        $this->createProfileFiles($profile);
        
        // create install script
        $this->createProfileScript($profile);
        // copy config.ini

        $this->createConfigIni($profile);
    }

    /**
     * method for recreating a profile
     * just means that we recreate all except config.ini
     * @param string $profile
     */
    public function recreateProfile($profile){
        // create all files
        $this->createProfileFiles($profile);
        // create install script
        $this->createProfileScript($profile);
    }

    
    
    /**
     * method for getting all templates located in conf::pathHtdocs()/template
     * used for settings current templates in profiles/profile/profile.inc file
     */
    public function getTemplates (){
        $dir = conf::pathHtdocs() . "/templates";
        $templates = file::getFileList($dir, array('dir_only' => true));

        foreach ($templates as $key => $val){
            $install = $dir . "/$val/install.inc";
            if (file_exists($install)){
                include $install;
                $templates[$key] = array ();
                $templates[$key]['public_clone_url'] = $_INSTALL['PUBLIC_CLONE_URL'];
                $templates[$key]['private_clone_url'] = $_INSTALL['PRIVATE_CLONE_URL'];
                if (!self::$master){
                    $templates[$key]['module_version'] = "$_INSTALL[VERSION]";
                } else {
                    $templates[$key]['module_version'] = "master";
                }
                $templates[$key]['module_name'] = $val;
            } else {
                
                $templates[$key] = array ();
                
                // check if this a git repo
                $path = conf::pathHtdocs() . "/templates/$val";
                $command = "cd $path && git config --get remote.origin.url";
                $ret = null;
                exec($command, $output, $ret);
                if ($ret != 0) continue;
               
                $git_url = shell_exec($command);
                $tags = git_get_local_tags($val, 'template');
                
                $latest = array_pop($tags);
                
                if (!self::$master){
                    $templates[$key]['module_version'] = $latest;
                } else {
                    $templates[$key]['module_version'] = "master";
                }
                
                $templates[$key]['module_name'] = $val;
                $templates[$key]['public_clone_url'] = trim($git_url);

            }

        }

        return $templates;
    }
    
    

    /**
     * method for creating a profile script. The profile script
     * is a php array of all modules, versions, git repos, templates,
     * set template etc. 
     * @param string $profile
     */
    public function createProfileScript($profile){
        $modules = $this->getModules();
        
        
        $module_str = var_export($modules, true);
        
        $templates = $this->getTemplates();
        $template_str = var_export($templates, true);
        
        $profile_str = '<?php ' . "\n\n";
        $profile_str.= '$_PROFILE_MODULES = ' . $module_str . ";";
        $profile_str.= "\n\n";
        $profile_str.= '$_PROFILE_TEMPLATES = ' . $template_str . ";";
        $profile_str.= "\n\n";
        $profile_str.= '$_PROFILE_TEMPLATE = ' . "'" . $this->getProfileTemplate() . "'" . ';';
        $profile_str.= "\n\n";
        $profile_str.= '$_PROFILE_USE_HOME = ' . $this->getProfileUseHome() . ';';
        $profile_str.= "\n\n";
        $file = conf::pathBase() . "/profiles/$profile/profile.inc";
        if (!file_put_contents($file, $profile_str)){
            print "Could not write to $file";
        }
    }

    /**
     * method getting a profiles template
     *
     * @return  string  name of profiles template extracted from database settings.
     */
    public function getProfileTemplate (){
        $db = new db();
        $db->connect();
        $row = $db->selectOne('settings', 'id', 1);
        return $row['template'];
    }

    /**
     * method for determine weather we use a home url or not in main menu
     * this just means do we have a link in main menu which says "home"
     *
     * return   int     1 on yes and 0 on no
     */
    public function getProfileUseHome (){
        $db = new db();
        $db->connect();
        $row = $db->selectOne('menus', 'url', '/');
        if (!empty($row)){
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * method for setting a profiles template
     * @param string $template
     * @return  boolean $res true on success and false on failure
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
     * method for settinge weather we use a home url or not in main menu 
     */
    public function setProfileUseHome(){
        $db = new db();
        $db->connect();
        $sql = "INSERT INTO `menus` VALUES (1, 'home', '/', '', '', 0, 0);";
        if ($this->profileUseHome){
            return $db->rawQuery($sql);
        }
    }

    /**
     * method for creating a profiles configuration files
     * this is all modules .ini files
     * @param   string  name of profile to be created
     */
    private function createProfileFiles($profile){
        
        $modules = $this->getModules();
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        
        if (!file_exists($profile_dir) || !is_dir($profile_dir)) {
            $mkdir = @mkdir($profile_dir);
            if ($mkdir){
                $this->confirm[] = "Created dir $profile_dir"; 
            } else {
                $this->error[] = "Could not create dir $profile_dir";
            }
        }
        
        // use config.ini-dist with modules with personal configuration
        //$secrets = array ('remote');
        foreach ($modules as $key => $val){

            $source = conf::pathModules() . "/$val[module_name]/$val[module_name].ini";

            // if no ini we just skip           
            if (!file_exists($source)) continue;
            
            $ary = conf::getIniFileArray($source, true);
            $ary = $this->iniArrayPrepare($ary);               
            $config_str = conf::arrayToIniFile($ary);

            $dest = $profile_dir . "/$val[module_name].ini-dist";
            file_put_contents($dest, $config_str);

            // if php ini file exists copy that to.
            $source = conf::pathModules() . "/$val[module_name]/config.php";
            $dest = $profile_dir . "/$val[module_name].php-dist";

            if (file_exists($source)){
                copy($source, $dest);
            }

        }
        
        $templates = $this->getTemplates();
        foreach ($templates as $key => $val){
            //$template_ini_file = conf::pathBase() . "/templates/$val[module_name]/$val[module_name].ini";
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
     * method for creating a main config.ini file
     * @param   string   name of the profile
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
     * method for setting info about profile
     *
     * @param string    profile name
     */
    public function setProfileInfo($profile){
        $profile_dir = conf::pathBase() . "/profiles/$profile";
        if (!file_exists($profile_dir)) {
            //echo;
            cos_cli_abort( "No such path to profiles: $profile_dir");
        } 
        
        include $profile_dir . "/profile.inc";
        $this->profileModules = $_PROFILE_MODULES;
        $this->profileTemplates = $_PROFILE_TEMPLATES;
        $this->profileTemplate = $_PROFILE_TEMPLATE;
        $this->profileUseHome = $_PROFILE_USE_HOME;
    }
    
    public function isModuleInProfile ($module) {
        foreach ($this->profileModules as $val){
            if ($val['module_name'] == $module){
                return true;
            }
        }
        return false;      
    }
    
    public function isTemplateInProfile ($template) {
        foreach ($this->profileTemplates as $val){
            if ($val['module_name'] == $template){
                return true;
            }
        }
        return false;      
    }

    /**
     *  method for loading a profile
     * @param string     profile
     */
    public function loadProfile($profile){
        $this->setProfileInfo($profile);
        $this->loadProfileFiles($profile);
        $this->loadConfigIni($profile);
    }

    /**
     * method for reloading a profile. Reloads all ini file except config.ini
     * @param   string   profile
     */
    public function reloadProfile($profile){
        $this->setProfileInfo($profile);
        // install all ini files except config.ini
        $this->loadProfileFiles($profile);
    }
    
    /**
     * method for loading a profiles configuration files, which means all
     * modules configuration files.
     *
     * @param   string    name of profile to be installed
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

            // if php ini file exists copy that to.
            $dest = conf::pathModules() . "/$val[module_name]/$val[module_name].php.ini";
            $source = $profile_dir . "/$val[module_name].php.ini-dist";

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
     * method for loading main configuration file for a profile
     *
     * @param string name of profile to be installed
     */
    public function loadConfigIni($profile){
        // copy config,ini
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
