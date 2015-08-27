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
        cos_upgrade_to($remote);
    } else {
        $locale = git::getTagsInstallLatest();
        common::echoMessage("Latest version/tag exists locale: $locale", 'y');
        
        $continue = common::readlineConfirm('E.g. Maybe your upgrade was interrupted. ');
        if ($continue) {
            cos_upgrade_to($remote);
        }
    }
}

function cos_upgrade_to($version) {
    $command = "git checkout master && git pull && git checkout $version";
    $ret = common::execCommand($command);
    if ($ret) {
        $continue = common::readlineConfirm('Command failed. Do you want to continue: ');
        if (!$continue) {
            common::abort('Aborting upgrade');
        }
    }
    
    $command = "composer update";
    $ret = common::systemCommand($command);
    if ($ret) {
        $continue = common::readlineConfirm('composer update failed. Do you want to continue: ');
        if (!$continue) {
            common::abort('Aborting upgrade');
        }
    }
    
    // Upgrade all modules and templates
    $profile = conf::getModuleIni('system_profile');
    upgrade_from_profile(array (
        'clone_only' => 1, 
        'profile' => $profile)
    );
    
    // reload any changes
    common::echoMessage('Reloading profile');
    $p = new profile();
    $p->reloadProfile($profile);
    
    common::echoMessage('Reloading config');
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
