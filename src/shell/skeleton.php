<?php

/**
 * File containing functions for creating a skeleton when writining a module
 *
 * @package     shell
 */

/**
 * function for creating a skeleton for a module. We are just
 * using shell command touch for creating the files.
 *
 * @param   array   e.g: <code>array('module' => 'test')</code> This will create
 *                  the following folder /modules/test containing base files
 *                  used when writing a module
 * @return  int     result from shell operation 0 is success
 */
function create_module_skeleton($args){
    $module_name = $args['module'];
    $ary = explode('/', $module_name);
    $module_path = conf::pathModules() . '/' . $module_name;
    
    cos_exec('mkdir ' . $module_path);

    if (count($ary) == 1) {
        // create dirs for sql. Only need in a base module
        $mysql_dir = $module_path . "/mysql";
        cos_exec('mkdir ' . $mysql_dir);
        $mysql_up = $mysql_dir . "/up";
        cos_exec('mkdir ' . $mysql_up);
        $mysql_down = $mysql_dir . "/down";
        cos_exec('mkdir ' . $mysql_down);

        // create dirs for language. Only need in base module
        $lang_dir = $module_path . "/lang";
        cos_exec('mkdir ' . $lang_dir);
        $lang_dir_en = $module_path . "/lang/en_GB";
        cos_exec('mkdir ' . $lang_dir_en);

        $files = $module_path . "/menu.inc ";
        $files.= $module_path . "/views.php ";
        $files.= $module_path . "/README.md ";
        $files.= $module_path . "/install.inc ";
        $files.= $module_path . "/$module_name.ini ";
        $files.= $module_path . "/$module_name.ini-dist ";
        $files.= $module_path . "/module.php ";
        $files.= $lang_dir_en . "/system.inc ";
        $files.= $lang_dir_en . "/language.inc ";
    } 
    cos_cli_print('Creating files: ');
    $res = cos_exec('touch ' . $files);
    
    if (!$res) {
        // add a version
        $str = "<?php

\$_INSTALL['VERSION'] = 1.71; 
";
        $res = file_put_contents($module_path . "/install.inc", $str);
        $res;
        
    }
}

self::setCommand('skeleton', array(
    'description' => 'Create skeleton for a module',
));

self::setOption('create_module_skeleton', array(
    'long_name'   => '--create-module',
    'description' => 'specify module v2 to create',
    'action'      => 'StoreTrue'
));


self::setArgument('module', array('description'=> 'Specify the module to create skeleton for'));
