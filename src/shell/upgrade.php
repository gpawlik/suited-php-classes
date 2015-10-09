<?php

use diversen\profile;
use diversen\moduleloader;
use diversen\git;
use diversen\cli\common;
use diversen\conf;

/**
 * reloads lang, menus, module conig
 * @param type $options
 */
function cos_upgrade_post ($options = array ()) {

    // reload config
    cos_config_reload();
}

function cos_upgrade ($options) {
    moduleloader::includeModule('system');
    $p = new profile();
          
    if (git::isMaster()) {
        common::abort('Can not make upgrade from master branch');
    }
    
    $repo = conf::getModuleIni('system_repo');
    $remote = git::getTagsRemoteLatest($repo);
    if ($p->upgradePossible()) {
        
        common::echoMessage("Latest version/tag: $remote", 'y');
        $continue = common::readlineConfirm('Continue the upgrade');
        if ($continue) {
            cos_upgrade_to($remote);
        }

    } else {
        $locale = git::getTagsInstallLatest();
        common::echoMessage("Latest version/tag: $locale", 'y');
        
        $continue = common::readlineConfirm('Continue. Maybe your upgrade was interrupted. ');
        if ($continue) {
            cos_upgrade_to($remote);
        }
    }
}

function cos_upgrade_to($version) {
    common::echoMessage("Will now pull source, and checkout latest tag", 'y');
    
    $command = "git checkout master && git pull && git checkout $version";
    $ret = common::execCommand($command);
    if ($ret) {
        common::abort('Aborting upgrade');
    }
    
    common::echoMessage("Will upgrade vendor with composer according to version", 'y');
    
    $command = "composer update";
    $ret = common::systemCommand($command);
    if ($ret) {
        common::abort('Composer update failed.');
    }
    
    common::echoMessage("Will upgrade all modules and templates the versions in the profile", 'y');
    
    // Upgrade all modules and templates
    $profile = conf::getModuleIni('system_profile');
    if (!$profile) {
        $profile = 'default';
    }
    upgrade_from_profile(array (
        'clone_only' => 1, 
        'profile' => $profile)
    );
    
    // reload any changes
    common::echoMessage("Reloading all configuration files", 'y');
    $p = new profile();
    $p->reloadProfile($profile);
    
    common::echoMessage("Load modules changes into database", 'y');
    cos_config_reload();
    
}

self::setCommand('upgrade', array(
    'description' => 'Upgrade existing system',
));

self::setOption('cos_upgrade', array(
    'long_name'   => '--upgrade',
    'description' => 'Installs new menu items, module config, and language files',
    'action'      => 'StoreTrue'
));

self::setArgument('profile',
    array('description'=> 'specify the profile to install',
          'optional' => true));
