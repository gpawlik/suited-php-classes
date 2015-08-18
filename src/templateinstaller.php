<?php

namespace diversen;

use diversen\conf;
use diversen\moduleinstaller;
use diversen\cli\common;
use diversen\git;


/**
 * class for installing a templates or upgrading it.
 * base actions are:
 *
 * update: checks module version from install.inc
 * perform any needed updates e.g. from version
 * 1.04 to 1.07
 *
 * If more upgrades exist. Upgrade all one after one.
 * 
 * @package    installer
 */
class templateinstaller extends moduleinstaller {

    /**
     * constructor which will take the template to install, upgrade or delete
     * as param
     *
     * @param array $options
     */
    function __construct($options = null){
        if (isset($options)){
            $this->setInstallInfo($options);
        }
    }

    /**
     * reads install info from modules/module_name/install.inc
     * @param   array $options
     */
    public function setInstallInfo($options){
        
        // In profile all templates are also modules
        if (isset($options['module_name'])) {
            $template_name = $options['module_name'];
        } else {
            $template_name = $options['template'];
        }
        
        // Set dir and ini files
        $template_dir = conf::pathHtdocs()  . "/templates/$template_name";
        $ini_file = $template_dir . "/$template_name.ini";
        $ini_file_dist = $template_dir . "/$template_name.ini-dist";

        // Profile
        if (isset($options['profile'])){
            $ini_file_dist = conf::pathBase() . "/profiles/$options[profile]/$template_name.ini-dist";
        }
        
        // Generate ini file
        $this->generateInifile($ini_file, $ini_file_dist);

        if (file_exists($template_dir)){
            $install_file = "$template_dir/install.inc";
            if (!file_exists($install_file)){
                common::echoMessage("Notice: No install file '$install_file' found in: '$template_dir'\n");
            }
              
            $this->installInfo['NAME'] = $template_name;
            
             // if no version we check if this is a git repo
            if (!isset($this->installInfo['VERSION'])) {
                $this->setInstallInfoFromGit('template');
            }
            
            if (file_exists($install_file)) {
                include $install_file;
                $this->installInfo = $_INSTALL;
                $this->installInfo['NAME'] = $template_name;               
                $this->installInfo['RUN_LEVEL'] = 0;
            } 
        } else {
            common::echoMessage ("Notice: No template dir: $template_dir\n");
        }
    }
    
    public function setInstallInfoFromGit() {

        $tags = git::getTagsModule($this->installInfo['NAME'], 'template');
        if (empty($tags)) {
            $latest = 'master';
        } else {
            $latest = array_pop($tags);
        }

        $this->installInfo['VERSION'] = $latest;
    }
    
    /**
     * installs a template
     * @return boolean $res 
     */
    public function install () {

        // create ini files for template
        $template = $this->installInfo['NAME'];
        $ini_file = conf::pathHtdocs() . "/templates/$template/$template.ini";
        $ini_file_php = conf::pathHtdocs() . "/templates/$template/$template.php.ini";
        $ini_file_dist = conf::pathHtdocs() . "/templates/$template/$template.ini-dist";
        $ini_file_dist_php = conf::pathHtdocs() . "/templates/$template/$template.php.ini-dist";

        if (!file_exists($ini_file)){
            if (file_exists($ini_file_dist)){
                if (!copy($ini_file_dist, $ini_file)){
                    $this->error = "Error: Could not copy $ini_file to $ini_file_dist" . PHP_EOL;
                    $this->error.= "Make sure your module has an ini-dist file: $ini_file_dist";
                    return false;
                }
            } 
        }
        
        // create php ini file if a php.ini-dist file exists
        if (!file_exists($ini_file_php)){
            if (file_exists($ini_file_dist_php)){
                copy($ini_file_dist_php, $ini_file_php);
            }
        }
        
        $this->confirm = "Template '" . $this->installInfo['NAME'] . "' installed" . PHP_EOL;
        $this->confirm.= "Make sure your module has an ini-dist file: $ini_file_dist";
                    
    }
}
