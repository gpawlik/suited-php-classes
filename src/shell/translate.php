<?php

use diversen\conf;
use diversen\file;
use diversen\layout;
use diversen\moduleloader;
use diversen\translate;
use diversen\translate\extract;
use Symfony\Component\Filesystem\Filesystem;

/**
 * generates a line of the translation array
 * if a string uses '' we write the array with ""
 * if a string uses "" we write the array with ''
 * @param string $key
 * @param string $value
 * @return string $str, e.g. $_COS_LANG_MODULE['This is a test'] = 'This is a test'
 */
function translate_with_quote ($key, $value = '') {
    
    $str = '';
    if (empty($value)) { 
        $value = $key; 
    }
    
    
    // search for apostrophe (') somewhere 
    // in order to know how to write out the array
    $apo_key = strpos($key, "'");
    if ($apo_key) {
        $str.= "\$" . '_COS_LANG_MODULE["'.$key.'"] = ';
    } else {
        $str.= "\$_COS_LANG_MODULE['$key'] = ";
    }
    
    // and also for the value
    $apo_val = strpos($value, "'");
    if ($apo_val) {
        $str.= '"'.$value.'";' . "\n";
    } else {
        $str.= "'$value';" . "\n";
    }
        
    return $str;
}

/**
 * function for creating a file with all strings
 * to be translated in a module by traversing all files
 * in the module.
 *
 * @param array $options
 */
function translate($options){

    $strings_all = array();
    $strings_all[] = '';

    if (isset($options['vendor'])) {
        $module_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src";
        $lang_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src/lang/$options[language]";
    }
    elseif (isset($options['template'])) {
        $module_dir = conf::pathHtdocs() . '/templates/' . $options['module'];
        $lang_dir = conf::pathHtdocs() . "/templates/$options[module]/lang/$options[language]";
    } else {
        $module_dir = conf::pathModules() . "/$options[module]";
        $lang_dir = conf::pathModules() . "/$options[module]/lang/$options[language]";
    }
        
    if (!file_exists($module_dir)){
        cos_cli_print_status('Notice', 'y', "No such module dir. Skipping: $module_dir");
        return;
    }
    
    if (!file_exists($module_dir . "/lang")) {
        mkdir($module_dir . "/lang");
    }
    
    if (!file_exists($module_dir . "/lang/$options[language]")) {
        mkdir($module_dir . "/lang/$options[language]");
    }

    // get all files  from modules dir
    $file_list = file::getFileListRecursive($module_dir);
    
    // compose a php file
    $str = $sys_str = "<?php\n\n";
    
    foreach ($file_list as $val){
  
        if (!translate_is_text($val)) {
            continue;
        }

        $file_str = file_get_contents($val);    
        $strings = extract::fromStr($file_str);


        // no strings we continue
        if (empty($strings)) { 
            continue;
        }

        if (strstr($val, 'menu.inc') || strstr($val, 'install.inc') ||  strstr($val, 'system_lang.inc') ){
            // system translation
            // we add the file info to translation as comment
            $file = str_replace(conf::pathBase(), '', $val);
            $sys_str.="// Translation of file $file\n\n";

            // and we add all strings in that file
            foreach ($strings as $trans){
                $sys_str.= translate_with_quote($trans);
            }
        } else {

            // we add the file info to translation as comment
            $file = str_replace(conf::pathBase(), '', $val);
            $str.="// Translation of file $file\n\n";

            // and we add all strings in that file
            foreach ($strings as $trans){
                // check if string already has been translated
                if (array_search($trans, $strings_all)) {
                    continue;
                }
                $str.= translate_with_quote($trans);
            }

        }
        $values = array_values($strings);
        $strings_all = array_merge($strings_all, $values);

    }

    if (!file_exists($lang_dir)){
        $res = mkdir($lang_dir);
        if ($res){
            cos_cli_print("Dir: $lang_dir created\n");
        } else {
            cos_cli_abort("Dir could not be created: $lang_dir\n");
        }
    }

    // final: write the translation file
    $write_file = $lang_dir . "/language.inc";

    // Issue warning if language file already exists
    if (file_exists($write_file)){
        if (!cos_confirm_readline("language files already exists.\nThese file will be over written")) {
            cos_cli_abort();
        }
    }

    file_put_contents($write_file, rtrim($str) . "\n");

    // final: write the translation file
    $write_sys_file = $lang_dir . "/system.inc";
    file_put_contents($write_sys_file, rtrim($sys_str) . "\n");
}



/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_all_update ($options) {

    if ($options['module'] != 'all') {
        cos_cli_abort("Specify 'all' as module when updating all modules");
    }
    
    $mods = moduleloader::getAllModules();
    foreach ($mods as $mod) {
        cos_cli_print_status('Notice', 'g', "Translating $mod[module_name]");
        $options['module'] = $mod['module_name'];
        translate_update($options);
    }
    
    $temps = layout::getAllTemplates();
    foreach ($temps as $mod) {
        $options['template'] = true;
        cos_cli_print_status('Notice', 'g', "Translating template $mod");
        $options['module'] = $mod;
        translate_update($options);
    }
}

function translate_vendor ($options) {
    $options['vendor'] = true;
    translate($options);
}

function translate_vendor_update($options) {
    $options['vendor'] = true;
    translate_update($options);
}

function translate_temp($options){ 
    $options['template'] = true;
    translate($options);
}

function translate_temp_update($options){ 
    $options['template'] = true;
    translate_update($options);
}

/**
 * check if prim mime type is text
 * @param string $filename
 * @return boolean $res true if text else false
 */
function translate_is_text ($file) {
    $prim_mime = file::getPrimMime($file);
    if ($prim_mime == 'text') {
        return true;
    }
    return false;
}

/**
 * function for creating a file with all strings
 * to be translated in a module by traversing all files
 * in the module.
 *
 * Could easily be refined to check if strings are translated
 * So far it is as it is .)
 *
 * @param array $options
 */
function translate_update($options){
    
    if (isset($options['vendor'])) {
        $module_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src";
        $lang_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src/lang/$options[language]";
    }
    elseif (isset($options['template'])) {
        $module_dir = conf::pathHtdocs() . '/templates/' . $options['module'];
        $lang_dir = conf::pathHtdocs() . "/templates/$options[module]/lang/$options[language]";
    } else {
        $module_dir = conf::pathModules() . "/$options[module]";
        $lang_dir = conf::pathModules() . "/$options[module]/lang/$options[language]";
    }
    
    if (!file_exists($module_dir)){
        cos_cli_print_status('Notice', 'y', "No such module|template dir. Skipping: $module_dir");
        return;
    }
    
    $translate_dir = $module_dir . "/lang/$options[language]";
    $translate_file = $translate_dir . "/language.inc";
    $system_file = $translate_dir . "/system.inc";
    
    // just do start translation if file does not exists
    if (!file_exists($translate_file)){
        translate($options);
        return;
    }

    include $translate_file;
    if (!isset($_COS_LANG_MODULE)) {
        $org_lang = $_COS_LANG_MODULE = array ();
    } else {
        $org_lang = $_COS_LANG_MODULE;
    }
        
    if (file_exists($system_file)){
        include $system_file;
        $org_lang = array_merge($_COS_LANG_MODULE, $org_lang);
    }


    // compose a php file
    $translation_str = $translation_sys_str = "<?php\n\n";
    
    // get all files  from modules dir
    $file_list = file::getFileListRecursive($module_dir);

    // arrays to use if we know if some strings are extracted
    $done_system = $done_lang = array ();
    foreach ($file_list as $val){
        
        if (!translate_is_text($val)) {
            continue;
        }
        
        $file_str = file_get_contents($val);


        $strings = extract::fromStr($file_str);
        // no strings we continue
        if (empty($strings)) continue;

        // and we add all strings
        // all menus are added to system translation
        
        if (strstr($val, 'menu.inc') || strstr($val, 'install.inc') ||  strstr($val, 'system_lang.inc') ){
            
            $file = str_replace(conf::pathBase(), '', $val);
            $translation_sys_str.="\n// Translation of file $file\n\n";
            
            foreach ($strings as $trans){
                if (isset($done_system[$trans])) continue;
                $done_system[$trans] = 1;
                
                // if string is not set in _COS_LANG_MODULE
                if (!isset($org_lang[$trans])){
                    $translation_sys_str.=translate_with_quote($trans);
                } else {
                    $new_str = $org_lang[$trans];
                    $translation_sys_str.= translate_with_quote($trans, $new_str);
                }
            }
        } else {
            
            $file = str_replace(conf::pathBase(), '', $val);
            $translation_str.="\n// Translation of file $file\n\n";
            
            foreach ($strings as $trans){
                
                if (isset($done_lang[$trans])) continue;
                $done_lang[$trans] = 1;
                
                if (!isset($org_lang[$trans])){
                    $translation_str.= translate_with_quote($trans);
                } else {
                    $new_str = $org_lang[$trans];
                    $translation_str.=translate_with_quote($trans, $new_str);
                }
            }
        }
    }

    $lang_dir = $module_dir . "/lang/$options[language]";
    if (!file_exists($lang_dir)){
        $res = mkdir($lang_dir);
        if ($res){
            cos_cli_print("Dir: $lang_dir created\n");
        } else {
            cos_cli_abort("Dir could not be created: $lang_dir\n");
        }
    }

    // final: write the translation file
    $write_file = $lang_dir . "/language.inc";
    file_put_contents($write_file, rtrim($translation_str) . "\n");

    // final: write the translation file
    $write_sys_file = $lang_dir . "/system.inc";
    file_put_contents($write_sys_file, rtrim($translation_sys_str) . "\n");

}

function translate_collect ($options) {

    $mods = moduleloader::getAllModules();
    $system = array ();
    $language = array ();
    
    
    // write out string
    $str = "<?php\n\n";
    foreach ($mods as $mod) {
        
        $mod_lang = conf::pathModules() . "/$mod[module_name]/lang/$options[language]";
        $system_file = $mod_lang . "/system.inc";
        $language_file = $mod_lang . "/language.inc";
        $_COS_LANG_MODULE = array ();
        if (file_exists($system_file)) {
            
            $file = str_replace(conf::pathBase(), '', $system_file);
            $str.="\n// Translation of file $file\n\n";
            
            include $system_file;
            
            foreach ($_COS_LANG_MODULE as $key => $val){
                $str.=translate_with_quote($key, $val);
            }
            $_COS_LANG_MODULE = array();

        }
        
        if (file_exists($language_file)) {
            
            $file = str_replace(conf::pathBase(), '', $language_file);
            $str.="\n// Translation of file $file\n\n";
            
            include $language_file;
            
            foreach ($_COS_LANG_MODULE as $key => $val){
                $str.=translate_with_quote($key, $val);
            }
            $_COS_LANG_MODULE = array();

        }
    }

    $temps = layout::getAllTemplates();
    foreach ($temps as $temp) {
        $mod_lang = conf::pathHtdocs() . "/templates/$temp/lang/$options[language]";
        $system_file = $mod_lang . "/system.inc";
        $language_file = $mod_lang . "/language.inc";
        
        $_COS_LANG_MODULE = array ();
        if (file_exists($system_file)) {
            
            $file = str_replace(conf::pathHtdocs(), '', $system_file);
            $str.="\n// Translation of file $file\n\n";
            
            include $system_file;
            foreach ($_COS_LANG_MODULE as $key => $val){
                $str.=translate_with_quote($key, $val);
            }
            $_COS_LANG_MODULE = array();
        }
        
        if (file_exists($language_file)) {
            
            $file = str_replace(conf::pathHtdocs(), '', $language_file);
            $str.="\n// Translation of file $file\n\n";
            
            include $language_file;
            foreach ($_COS_LANG_MODULE as $key => $val){
                $str.=translate_with_quote($key, $val);
            }
            $_COS_LANG_MODULE = array();
        }
    }

    $mod_lang = conf::pathHtdocs() . "/templates/$options[module]";
    if (!file_exists($mod_lang)) {
        cos_cli_abort("No such template: $options[module]");
    }
    
    // generate path if it does not exist
    $mod_lang.= "/lang/$options[language]";    
    $fs = new Filesystem();
    $fs->mkdir($mod_lang, 0755);
    $lang_all = $mod_lang . "/language-all.inc"; 
    file_put_contents($lang_all, $str);

}


self::setCommand('translate', array(
    'description' => 'Extract strings to translation files.',
));

self::setOption('translate', array(
    'long_name'   => '--translate',
    'short_name'   => '-t',
    'description' => 'Create a translation file from all strings found in a module.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_update', array(
    'long_name'   => '--update',
    'short_name'   => '-u',
    'description' => 'Update a translation file with all new strings found in a module.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_temp', array(
    'long_name'   => '--temp',
    'description' => 'Create a translation file from all strings found in a template.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_temp_update', array(
    'long_name'   => '--temp-up',
    'description' => 'Update a translation file with all new strings found in a template.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_all_update', array(
    'long_name'   => '--all-up',
    'description' => 'Update all translation files for all modules and templates.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_vendor', array(
    'long_name'   => '--vendor',
    'description' => 'Update vendor dir simple-php-classes.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_vendor_update', array(
    'long_name'   => '--vendor-up',
    'description' => 'Update vendor dir simple-php-classes.',
    'action'      => 'StoreTrue'
));

self::setOption('translate_collect', array(
    'long_name'   => '--collect',
    'description' => 'Collect all translations for a language into a single file into the enabled template. Specify template and language',
    'action'      => 'StoreTrue'
));

self::setArgument('module',
    array('description'=> 'Specicify the module or template for which you will extract a translation. In --all-up and collect you should specify all',
          'optional' => false));

self::setArgument('language',
    array('description'=> 'Specicify the language, e.g. da_DK or en_GB. This is the language we extract translation for. This will normally be en_GB as this is the systems base language.',
          'optional' => false));

