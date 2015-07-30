<?php

namespace diversen;
use diversen\moduleinstaller;
use diversen\conf as conf;
/**
 * file contains the template installer
 * @package installer
 */

/**
 * class for installing a templates or upgrading it.
 * base actions are:
 *
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
     * holding array of info for the install
     * this is loaded from install.inc file and will read
     * the $_INSTALL var
     * @var array $installInfo
     */
    public $installInfo = null;

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
     *
     * @param   array $options
     */
    public function setInstallInfo($options){
        if (isset($options['module_name'])) {
            $template_name = $options['module_name'];
        } else {
            $template_name = $options['template'];
        }
        
        $template_dir = conf::pathHtdocs()  . "/templates/$template_name";
        $ini_file = $template_dir . "/$template_name.ini";
        $ini_file_dist = $template_dir . "/$template_name.ini-dist";

        if (isset($options['profile'])){
            $ini_file_dist = conf::pathBase() . "/profiles/$options[profile]/$template_name.ini-dist";
        }

        if (!file_exists($ini_file)){
            if (file_exists($ini_file_dist)){
                copy ($ini_file_dist, $ini_file);
                conf::$vars['coscms_main']['template'] = conf::getIniFileArray($ini_file);
            } 
        } else {
            conf::$vars['coscms_main']['template'] = conf::getIniFileArray($ini_file);
        }

        if (file_exists($template_dir)){
            $install_file = "$template_dir/install.inc";
            if (!file_exists($install_file)){
                cos_cli_print("Notice: No install file '$install_file' found in: '$template_dir'\n");
            }
              
            $this->installInfo['NAME'] = $template_name;
            
             // if no version we check if this is a git repo
            if (!isset($this->installInfo['VERSION'])) {

                $command = "cd " . conf::pathHtdocs() . "/templates/"; 
                $command.= $this->installInfo['NAME'] . " ";
                $command.= "&& git config --get remote.origin.url";

                $git_url = shell_exec($command);
                // git config --get remote.origin.url
                $tags = git_get_remote_tags($git_url);
                
                //git_get_latest_remote_tag($repo);

                if (empty($tags)) {
                    $latest = 'master';
                } else {
                    $latest = array_pop($tags);
                }

                $this->installInfo['VERSION'] = $latest;

            }
            
            
            if (file_exists($install_file)) {
                include $install_file;

                $this->installInfo = $_INSTALL;
                $this->installInfo['NAME'] = $template_name;
                
                if (empty($this->installInfo['MAIN_MENU_ITEM'])){
                    $this->installInfo['menu_item'] = 0;
                } else {
                    $this->installInfo['menu_item'] = 1;
                }

                if (empty($this->installInfo['RUN_LEVEL'])){
                    $this->installInfo['RUN_LEVEL'] = 0;
                }
            } 

            
            
        } else {
            cos_cli_print ("Notice: No module dir: $template_dir\n");
        }
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
                    $this->error = "Error: Could not copy $ini_file to $ini_file_dist" . NEW_LINE;
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
        
        $this->confirm = "Template '" . $this->installInfo['NAME'] . "' installed" . NEW_LINE;
        $this->confirm.= "Make sure your module has an ini-dist file: $ini_file_dist";
                    
    }
}
