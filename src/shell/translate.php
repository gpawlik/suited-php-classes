<?php


use diversen\translate\extractor;



/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_all_update($options) {
    $e = new extractor();
    $e->setDirsInsideDir('modules/*');
    $e->setDirsInsideDir('htdocs/templates/*');
    $e->updateLang();
}

/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_path($options) {

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

self::setOption('translate_all_update', array(
    'long_name' => '--all-up',
    'description' => 'Update all translation files for all modules and templates.',
    'action' => 'StoreTrue'
));

self::setOption('translate_path', array(
    'long_name' => '--path',
    'description' => 'Update translation for a path, e.g. translate --path vendor/namespace/yourmodule en',
    'action' => 'StoreTrue'
));


self::setArgument('path', array('description' => 'Specicify the path for which you want to create translation. ',
    'optional' => true));

self::setArgument('language', array('description' => "Specicify the language, e.g. da_DK or en_GB. Default is 'en'",
    'optional' => true));

