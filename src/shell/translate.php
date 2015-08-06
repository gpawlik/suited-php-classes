<?php

use diversen\conf;
use diversen\file;
use diversen\layout;
use diversen\moduleloader;
use diversen\translate;
use diversen\translate\extract;
use Symfony\Component\Filesystem\Filesystem;



/**
 * function for creating a file with all strings
 * to be translated in a module by traversing all files
 * in the module.
 *
 * @param array $options
 */
function translate($options) {
    return extract::generate($options);
}

/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_all_update($options) {

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

function translate_vendor($options) {
    $options['vendor'] = true;
    translate($options);
}

function translate_vendor_update($options) {
    $options['vendor'] = true;
    translate_update($options);
}

function translate_temp($options) {
    $options['template'] = true;
    translate($options);
}

function translate_temp_update($options) {
    $options['template'] = true;
    translate_update($options);
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
function translate_update($options) {
    return extract::update($options);
}

/** @deprecated since 4.01
 * 
 * @param array $options
 * @return void
 */
function translate_collect($options) {
    return extract::collect($options);
}

self::setCommand('translate', array(
    'description' => 'Extract strings to translation files.',
));

self::setOption('translate', array(
    'long_name' => '--translate',
    'short_name' => '-t',
    'description' => 'Create a translation file from all strings found in a module.',
    'action' => 'StoreTrue'
));

self::setOption('translate_update', array(
    'long_name' => '--update',
    'short_name' => '-u',
    'description' => 'Update a translation file with all new strings found in a module.',
    'action' => 'StoreTrue'
));

self::setOption('translate_temp', array(
    'long_name' => '--temp',
    'description' => 'Create a translation file from all strings found in a template.',
    'action' => 'StoreTrue'
));

self::setOption('translate_temp_update', array(
    'long_name' => '--temp-up',
    'description' => 'Update a translation file with all new strings found in a template.',
    'action' => 'StoreTrue'
));

self::setOption('translate_all_update', array(
    'long_name' => '--all-up',
    'description' => 'Update all translation files for all modules and templates.',
    'action' => 'StoreTrue'
));

self::setOption('translate_vendor', array(
    'long_name' => '--vendor',
    'description' => 'Update vendor dir simple-php-classes.',
    'action' => 'StoreTrue'
));

self::setOption('translate_vendor_update', array(
    'long_name' => '--vendor-up',
    'description' => 'Update vendor dir simple-php-classes.',
    'action' => 'StoreTrue'
));

self::setArgument('module', array('description' => 'Specicify the module or template for which you will extract a translation. In --all-up and collect you should specify all',
    'optional' => false));

self::setArgument('language', array('description' => 'Specicify the language, e.g. da_DK or en_GB. This is the language we extract translation for. This will normally be en_GB as this is the systems base language.',
    'optional' => false));

