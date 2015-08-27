<?php

use diversen\conf;
use diversen\profile;
use diversen\cli\common;
use diversen\git;

/**
 * funtion for installing a module from a git repo name
 * @param array     $options array ('repo' => 'git::repo')
 * @param string    $type (module, profile or template)
 */
function cos_git_install ($options, $type = 'module'){
    $module_name = git::getModulenameFromRepo($options['repo']);
    if (!$module_name){
        common::abort('Install command need a valid repo name');
    }

    $options['module'] = $module_name;

    cos_git_clone ($options, $type);
    if ($type == 'module'){
        $str = install_module($options, true);
        common::echoMessage($str);
        return;
    }
    
    if ($type == 'template') {
        $str = install_template($options, true);
        common::echoMessage($str);
        return;
    }
}

/**
 * function for getting path to a repo.
 * @param string     $module_name
 * @param string     $type (module, profile, template)
 * @return string    $repo_path the locale path to the repo.
 */

function cos_get_repo_path ($module_name, $type = 'module'){
    // set repo_dir according to module type.
    if($type == 'template'){
        $repo_dir = conf::pathBase() . "/htdocs/templates/$module_name";
    } else if ($type == 'profile'){
        $repo_dir = conf::pathBase() . "/profiles/$module_name";
    } else {
        $repo_dir = conf::pathModules() . "/$module_name";
    }
    return $repo_dir;
}

/**
 * function for cloning a template repo
 * @param array $options
 */
function cos_git_clone_template($options){
    
    cos_git_clone($options, 'template');
    
    $template = git::getModulenameFromRepo($options['repo']);
    $options['template'] = $template;
    $str = install_template($options, true);
    common::echoMessage($str);
    return;
}


/**
 * function used for cloning a repo
 * @param array $options
 * @param string $type
 */

function cos_git_clone($options, $type){
    
    // get latest repo tag
    $latest = git::getTagsRemoteLatest($options['repo']);
    
    // if version is set we will use this version.
    // or we will use latest tag.
    if (isset($options['version'])){
        $checkout = $options['version'];
    } else {
        $checkout = $latest;
    }

    // we abort if there is no tags.
    if (!$latest){
        $checkout = 'master';
    }
    
    // check if profile use master or if master is set
    if (isset(conf::$vars['git_use_master']) || isset($options['master'])){
        $checkout = 'master';
    }

    // set dir according to module type. Template, profile or module.
    if ($type == 'template'){
        $clone_path = conf::pathHtdocs() . "/templates";
    } else if ($type == 'profile'){
        $clone_path = conf::pathBase() . "/profiles";
    } else {
        $clone_path = conf::pathModules();
    }

    // create path if it does not exists
    if (!file_exists($clone_path)){
        mkdir($clone_path);
    }

    // get module name
    $module_name = git::getModulenameFromRepo($options['repo']);
    $module_path = "$clone_path/$module_name";

    // if dir exists we check if it is a git repo
    // or just a directory
    $ret = null;
    if (file_exists($module_path)){
        $repo_dir = $clone_path . "/$module_name";

        // check if path is a git repo
        $git_folder = $repo_dir . "/.git";
        if (file_exists($git_folder)){
            // repo exists. We pull changes and set version
            $git_command = "cd $repo_dir && git checkout master && git pull && git checkout $checkout";
        } else {
            // no git repo - empty dir we presume.
            $git_command = "cd $clone_path && git clone $options[repo] && cd $module_name && git checkout $checkout";
        }
        $ret = common::execCommand($git_command);
    } else {
        $git_command = "cd $clone_path && git clone $options[repo] && cd $module_name && git checkout $checkout";
        $ret = common::execCommand($git_command);
    }

    // evaluate actions
    if ($ret){
        common::abort("$git_command failed");
    }    
}

/**
 * cli call function is --master is set then master will be used instead of
 * normal tag
 *
 * @param array $options
 */
function cos_git_use_master ($options){
    conf::$vars['git_use_master'] = 1;
}



/**
 * updates a single module
 * @param array $options
 */
function cos_git_upgrade_template_single($options) {
    
    $path = conf::pathHtdocs() . "/templates/$options[repo]";
    $p = new profile();
    if (cos_git_is_repo($path)) {
        $mod = $p->getTemplate($options['repo']);
        cos_git_upgrade_template ($mod);
    } else {
        common::echoStatus('ERROR', 'r', "--temp-up needs a template name, e.g. 'clean'. The module must exists in the module dir");
    }
}

/**
 * updates a single module
 * Notice: You can not set version. It will just checkout the latest tag. 
 * @param array $options
 */
function cos_git_upgrade_module_single($options) {
    
    $path = conf::pathModules() . "/$options[repo]";
    $p = new profile();
    if (cos_git_is_repo($path)) {
        $mod = $p->getModule($options['repo']);        
        cos_git_upgrade_module ($mod);
    } else {
        common::echoStatus('ERROR', 'r', "--mod-up needs a module name, e.g. 'settings'. The module must exists in the module dir");
    }
}

/**
 * 
 * @param array $val module array with public repo path set
 * @param string $version
 */
function cos_git_upgrade_module ($val, $tag = null) {
    if (isset(conf::$vars['git_use_master'])){
        $tag = 'master';
    } else {
        $tag = git::getTagsRemoteLatest($val['public_clone_url'], true);
    }

    if ( ($tag == 'master') OR  ($tag != $val['module_version'])) { 
        cos_git_upgrade ($val, $tag, 'module');
    } else {
        common::echoStatus('NOTICE', 'y', "Nothing to upgrade: Version is: $tag");
    }
}

/**
 * upgrade a template
 * @param type $val
 */
function cos_git_upgrade_template($val) {
    if (isset(conf::$vars['git_use_master'])){
        $tag = 'master';
    } else {
        $tag = git::getTagsRemoteLatest($val['public_clone_url'], true);
    }

    if ( ($tag == 'master') OR  ($tag != $val['module_version'])) {
        cos_git_upgrade ($val, $tag, 'template');
    } else {
        common::echoStatus('NOTICE', 'y', "Nothing to upgrade: Version is: $tag");
    }
}

/**
 * get latest tag for modules and templates and
 * upgrade according to latest tag
 * @param   array   options from cli env
 */
function cos_git_upgrade_all ($options){

    $profile = new profile();
    
    $modules = $profile->getModules();
    
    foreach ($modules as $key => $val){
        cos_git_upgrade_module($val);
    }
        
    $templates = $profile->getTemplates();
    foreach ($templates as $key => $val){
        cos_git_upgrade_template($val);
    }
}

/**
 * function for adding and commiting all modules and templates
 * @param   array   options from cli env
 */
function cos_git_commit_all ($options){

    $profile = new profile();
    $modules = $profile->getModules();
    foreach ($modules as $key => $val){
        cos_git_commit($val, 'module');

    }

    
    $templates = $profile->getTemplates();
    foreach ($templates as $key => $val){
        cos_git_commit($val, 'template');
    }
}

/**
 * function for adding and commiting all modules and templates
 * @param   array   options from cli env
 */
function cos_git_commit_module_single ($options){

    $path = conf::pathModules() . "/$options[repo]";
    if (!cos_git_is_repo($path)) {
        common::abort("Module: $options[repo] is not a git repo. Specify installed module name (e.g. 'settings') when commiting");
    }
    
    $p = new profile();
    $mod = $p->getModule($options['repo']);
    cos_git_commit($mod, 'module');
}

/**
 * shell callback function for commiting a single template
 * @param array $options array ('repo')
 */
function cos_git_commit_template_single ($options){
    $path = conf::pathHtdocs() . "/templates/$options[repo]";
    if (!cos_git_is_repo($path)) {
        common::abort("Template: $options[repo] is not a git repo. Specify installed template (e.g. 'clean') name when commiting");
    }
    
    $p = new profile();
    $mod = $p->getTemplate($options['repo']);
    cos_git_commit($mod, 'template');
}

/**
 * function for tagging all modules and templates
 * @param   array   options from cli env
 */
function cos_git_tag_all ($options){
    $profile = new profile();
    $version = common::readSingleline('Enter tag version to use ');
    $modules = $profile->getModules();
    foreach ($modules as $key => $val){

        $tags = git::getTagsModule($val['module_name'], 'module');
        if (in_array($version, $tags)) {
            common::echoStatus('NOTICE', 'y', "Tag already exists local for module '$val[module_name]'.");
        }
        
        $val['new_version'] = $version;
        cos_git_tag($val, 'module');
    }

    $templates = $profile->getTemplates();
    foreach ($templates as $key => $val){
        
        $tags = git::getTagsModule($val['module_name'], 'template');
        if (in_array($version, $tags)) {
            common::echoStatus('NOTICE', 'y', "Tag already exists local for template '$val[module_name]'");
        }
        
        $val['new_version'] = $version;
        cos_git_tag($val, 'template');
    }
}

/**
 * function for tagging a module or all modules
 * @param array $val
 * @param string $typ (template or module)
 * @return type 
 */
function cos_git_tag ($val, $type = 'module'){
    $repo_path = cos_get_repo_path($val['module_name'], $type);

    if (!cos_git_is_repo ($repo_path)){
        common::echoMessage("$repo_path is not a git repo");
        return;
    }

    if (empty($val['private_clone_url'])) {
        common::echoMessage("No private clone url is set in install.inc of $val[module_name]");
        return;
    }

    if (!common::readlineConfirm("You are about to tag module: $val[module_name]. Continue?")){
        return;
    }
    
    $git_command = "cd $repo_path && git add . && git commit -m \"$val[new_version]\" && git push $val[private_clone_url]";
    proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));
    
    $git_command = "cd $repo_path && git tag -a \"$val[new_version]\" -m \"$val[new_version]\"";
    proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

    $git_command = "cd $repo_path && git push --tags $val[private_clone_url]";
    passthru($git_command);

    echo "\n---\n";
}

/**
 * function for upgrading a module, template or profile according to latest tag
 * or master
 *
 * @param array     with module options
 * @param string    tag with wersion or 'master'
 * @param string    module, templatee or profile.
 */
function cos_git_commit ($val, $type = 'module'){
    $repo_path = cos_get_repo_path($val['module_name'], $type);

    if (!cos_git_is_repo ($repo_path)){
        common::echoMessage("$repo_path is not a git repo");
        return;
    }

    if (empty($val['private_clone_url'])) {
        common::echoMessage("No private clone url is set in install.inc of $val[module_name]");
        return;
    }
    
    if (!common::readlineConfirm("You are about to commit module: $val[module_name]. Continue?")){
        return;
    }


    common::echoStatus('COMMIT', 'g', "Module: '$val[module_name]'");
    
    $git_add = "cd $repo_path && git add . ";
    common::execCommand($git_add);

    $git_command = "cd $repo_path && git commit ";
    proc_close(proc_open($git_command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes));

    $git_push = "cd $repo_path && git push $val[private_clone_url]";
    //passthru($git_command);
    common::execCommand($git_push);
    echo PHP_EOL;
}

/**
 * function for upgrading a module, template or profile according to latest tag
 * or master
 *
 * @param array     with module options
 * @param string    tag with wersion or 'master'
 * @param string    module, templatee or profile. 
 */
function cos_git_upgrade ($val, $tag = 'master', $type = 'module'){

    if (!isset($val['module_name'])) {
        $val['module_name'] = git::getModulenameFromRepo($val['repo']);
    }
    
    $repo_path = cos_get_repo_path($val['module_name'], $type);
    
    if (!cos_git_is_repo ($repo_path)){
        common::echoMessage("$repo_path is not a git repo - will not upgrade");
        return;
    }

    $git_command = "cd $repo_path && ";
    $git_command.= "git checkout master && ";
    $git_command.= "git pull && git fetch --tags && ";
    $git_command.= "git checkout $tag";
    
    common::execCommand($git_command);
    
    if ($type == 'module'){
        // It is called with a different name in the upgrade_module
        // function ...
        $val['module'] = $val['module_name'];

        // upgrade to latest set in $_INSTALL['VERSION']
        $val['version'] = null;
        upgrade_module($val);
    }
    
    // templates have no registry - they are tag based only in version
}

function cos_git_is_repo($path){
    $repo = $path . "/.git";
    if (!file_exists($repo)){
        return false;
    }
    return true;
}

function cos_git_no_questions (){
    common::readlineConfirm(null, 1);
}

/**
 * function for showing git tags (just for testing)
 * @param array $options
 */
function cos_git_echo_remote_tags ($options){
    if (empty($options['repo']))  {
        common::abort('You need to specify a repo');
    }
    
    $tags = git::getTagsRemote($options['repo']);
    if (empty($tags)) {
        common::abort('No tags');
    }
    $latest = git::getTagsRemoteLatest($options['repo']);
    common::echoMessage("Latest is: $latest");
}

self::setCommand('git', array(
    'description' => 'git module management',
));

self::setOption('cos_git_no_questions', array(
    'long_name'   => '--silence',
    'short_name'   => '-s',
    'description' => 'Will ask [y] to all questions raised',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_use_master', array(
    'long_name'   => '--master',
    'short_name'   => '-m',
    'description' => 'Will use master. Valid for all-up',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_install', array(
    'long_name'   => '--mod-in',
    'description' => 'Will clone specified remote url with latest version',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_clone_template', array(
    'long_name'   => '--temp-in',
    'description' => 'Will install remote clone url with latest version',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_upgrade_all', array(
    'long_name'   => '--all-up',
    'description' => 'Will check latest remote versions of modules, templates and profiles, and compare with locale version. If remote is higher it will be checked out and system will be upgraded',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_upgrade_module_single', array(
    'long_name'   => '--mod-up',
    'description' => 'Will check latest remote version and compare with locale version. If remote is higher it will be checked out and installed',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_upgrade_template_single', array(
    'long_name'   => '--temp-up',
    'description' => 'Will check out latest tag of a template. If remote is higher it will be checked out',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_commit_all', array(
    'long_name'   => '--all-commit',
    'description' => 'Will try and commit all modules and templates in one try.',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_commit_module_single', array(
    'long_name'   => '--mod-commit',
    'description' => 'Will try and commint single module',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_commit_template_single', array(
    'long_name'   => '--temp-commit',
    'description' => 'Will try and commint single module',
    'action'      => 'StoreTrue'
));

self::setOption('cos_git_tag_all', array(
    'long_name'   => '--all-tag',
    'description' => 'Will tag and push tags for all modules and templates in one try.',
    'action'      => 'StoreTrue'
));


self::setArgument(
    'repo',
    array('description'=> 'Specify the git repo | module to be used',
        'optional' => true,
));

self::setArgument(
    'version',
    array('description'=> 'Specify the version to checkout e.g. master or 1.11',
        'optional' => true,
));
