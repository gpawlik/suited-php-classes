<?php


use diversen\translate\extractor;
use diversen\conf;
use diversen\cli\common;

/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_all_update($options) {
    $e = new extractor();
    $e->setDirsInsideDir('modules/');
    $e->setDirsInsideDir('htdocs/templates/');
    $e->setSingleDir("vendor/diversen/simple-php-classes");
    $e->updateLang();
}

/**
 * will update all translation files in specified language
 * @param array $options
 */
function translate_path($options) {
    if (!isset($options['path'])) {
        common::abort('Add a path');
    }
    
    $path = conf::pathBase() . "/$options[path]";
    if (!file_exists($path) OR !is_dir($path)) {
        common::abort('Specify a dir as path');
    }
    
    $e = new extractor();
    if (!empty($options['language'])) {
        $e->defaultLanguage = $options['language'];
    }
    
    $e->setSingleDir($options['path']);
    $e->updateLang();
    common::echoStatus('OK', 'g', 'Extraction done');
}

self::setCommand('translate', array(
    'description' => 'Extract strings to be translated into translation files.',
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

self::setArgument('language', array('description' => "Specicify the language, e.g. 'de' or 'da'. Default is 'en'",
    'optional' => true));

