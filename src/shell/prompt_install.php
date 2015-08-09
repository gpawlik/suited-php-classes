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
    common::echoMessage('The following tags can be used:');

    $tags = '';
    $tags.= git::getTags ();
    $tags.= "master";

    common::echoMessage ($tags);
    $tag = common::readSingleline("Enter tag (version) to use:");

    common::execCommand("git checkout $tag");

    $profiles = file::getFileList('profiles', array('dir_only' => true));
    common::echoMessage("List of profiles: ");
    foreach ($profiles as $key => $val){
        common::echoMessage("\t".$val);
    }

    // select profile and load it
    $profile = common::readSingleline('Enter profile, and hit return: ');
    load_profile(array('profile' => $profile, 'config_only' => true));
    common::echoMessage("Main config file (config/config.ini) for $profile is loaded");

    
    
    // Keep base path. Ortherwise we will lose it when loading profile    
    $base_path = conf::pathBase();
    
    // load profile.
    conf::$vars['coscms_main'] = conf::getIniFileArray($base_path . '/config/config.ini', true);
    
    // reset base path
    // all commons are set based on base_path
    conf::setMainIni('base_path', $base_path);
    conf::defineCommon();
    
    // get configuration info
    $host = common::readSingleline('Enter mysql host, and hit return: ');
    $database = common::readSingleline('Enter database name, and hit return: ');
    $username = common::readSingleline('Enter database user, and hit return: ');
    $password = common::readSingleline('Enter database users password, and hit return: ');
    $server_name = common::readSingleline('Enter server host name (e.g. www.coscms.org), and hit return: ');

    // assemble configuration info
    conf::$vars['coscms_main']['url'] = "mysql:dbname=$database;host=$host;charset=utf8";
    conf::$vars['coscms_main']['username'] = $username;
    conf::$vars['coscms_main']['password'] = $password;
    conf::$vars['coscms_main']['server_name'] = $server_name;
    
    // write it to ini file
    $content = conf::arrayToIniFile(conf::$vars['coscms_main'], false);
    $path = conf::pathBase() . "/config/config.ini";
    file_put_contents($path, $content);

    // install profile.
    $confirm_mes = "Configuration rewritten (config/config.ini). More options can be set here, so check it out at some point.";
    $confirm_mes.= "Will now install system ... ";
    
    common::echoMessage($confirm_mes);

    $options = array();
    $options['profile'] = $profile;
    if ($tag == 'master'){
        $options['master'] = true;
    }

    cos_install($options);
    useradd_add();
    $login = "http://$server_name/account/login/index";
    common::echoMessage("You are now able to log in: At $login");
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

