<?php

use diversen\conf;
use diversen\file;
use diversen\cli\common;
use diversen\git;


/**
 * function for doing a prompt install from shell mode
 * is a wrapper around other shell functions.
 */
function prompt_install(){
    common::echoMessage('Pick a version to install:','y');
    
    $tags = git::getTagsInstallLatest() . PHP_EOL;
    $tags.= "master";

    common::echoMessage ($tags);
    $tag = common::readSingleline("Enter tag (version) to use:");

    common::execCommand("git checkout $tag");

    // Which profile to install
    $profiles = file::getFileList('profiles', array('dir_only' => true));
    if (count($profiles) == 1) {
        $profile = array_pop($profiles);
    } else {
        common::echoMessage("List of profiles: ");
        foreach ($profiles as $val){
            common::echoMessage("\t".$val);
        }

        // select profile and load it
        $profile = common::readSingleline('Enter profile, and hit return: ');
    }
    
    common::echoMessage("Loading the profile '$profile'",'y');
   
    load_profile(array('profile' => $profile, 'config_only' => true));
    common::echoMessage("Main configuration (placed in config/config.ini) for '$profile' is loaded", 'y');

    // Keep base path. Ortherwise we will lose it when loading profile    
    $base_path = conf::pathBase();
    
    // Load the default config.ini settings as a skeleton
    conf::$vars['coscms_main'] = conf::getIniFileArray($base_path . '/config/config.ini', true);
    
    // Reset base path
    conf::setMainIni('base_path', $base_path);
    conf::defineCommon();
    
    common::echoMessage("Enter MySQL credentials",'y');
    
    // Get configuration info
    $host = common::readSingleline('Enter your MySQL host: ');
    $database = common::readSingleline('Enter database name: ');
    $username = common::readSingleline('Enter database user: ');
    $password = common::readSingleline('Enter database users password: ');
    $server_name = common::readSingleline('Enter server host name: ');

    common::echoMessage("Writing database connection info to main configuration",'y');
    
    // Assemble configuration info
    conf::$vars['coscms_main']['url'] = "mysql:dbname=$database;host=$host;charset=utf8";
    conf::$vars['coscms_main']['username'] = $username;
    conf::$vars['coscms_main']['password'] = $password;
    conf::$vars['coscms_main']['server_name'] = $server_name;
    
    // Write it to ini file
    $content = conf::arrayToIniFile(conf::$vars['coscms_main'], false);
    $path = conf::pathBase() . "/config/config.ini";
    file_put_contents($path, $content);

    common::echoMessage("Your can also always change the config/config.ini file manually",'y');

    $options = array();
    $options['profile'] = $profile;
    if ($tag == 'master'){
        $options['master'] = true;
    }

    common::echoMessage("Will now clone and install all modules",'y');
    cos_install($options);
    
    common::echoMessage("Create a super user",'y');
    useradd_add();
    
    $login = "http://$server_name/account/login/index";
    
    

    
    common::echoMessage("If there was no errors you will be able to login at $login", 'y');
    common::echoMessage("Remember to change file permissions. This will require super user",'y');

}

function get_password(){
    $site_password = common::readSingleline('Enter system user password, and hit return: ');
    $site_password2 = common::readSingleline('Retype system user password, and hit return: ');
    if ($site_password == $site_password2){
        return $site_password;
    } else {
        get_password();
    }
}



self::setCommand('prompt-install', array(
    'description' => 'Prompt install. Asks questions and install',
));


self::setOption('prompt_install', array(
    'long_name'   => '--install',
    'description' => 'Will prompt user for install info',
    'action'      => 'StoreTrue'
));

