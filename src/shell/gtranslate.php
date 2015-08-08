<?php


use diversen\translate\google;
use diversen\conf;

function google_translate_all_update ($options) {
    
    if ($options['path'] != 'all') {
        cos_cli_abort("Specify 'all' as path");
    }
    
    
    $t = new google();
    if (!empty($options['target'])) {
        $t->target = $options['target'];
    }
    
    if (!empty($options['source'])) {
        $t->source = $options['source'];
    }
    
    $key = conf::getMainIni('google_translate_key');
    $t->key = $key;
    $t->setDirsInsideDir('modules/*');
    $t->setDirsInsideDir('/htdocs/templates/*');  
    $t->setSingleDir("vendor/diversen/simple-php-classes");
    $t->updateLang();

}

function google_translate_path ($options) {
    if (!isset($options['path'])) {
        cos_cli_abort('You need to specify path to translate');
    }
    if (!isset($options['target'])) {
        cos_cli_abort('You need to specify target language to translate into');
    }
    $e = new google();
    $key = conf::getMainIni('google_translate_key');
    $e->key = $key;
    $e->setSingleDir($options['path']);
    $e->updateLang();
}

self::setCommand('google-translate', array(
    'description' => 'Translate using Googles Translate API',
));

self::setOption('google_translate_all_update', array(
    'long_name'   => '--all-up',
    'description' => "Translate all modules / templates into a language. Specify 'all' as path ",
    'action'      => 'StoreTrue'
));

self::setOption('google_translate_path', array(
    'long_name'   => '--path',
    'description' => 'Translate a path into another language',
    'action'      => 'StoreTrue'
));

self::setArgument('path',
    array('description'=> 'Specicify the module for which you will make a translation',
          'optional' => true));

self::setArgument('target',
    array('description'=> 'Specicify the target language which we will translate into',
          'optional' => true));

self::setArgument('source',
    array('description'=> "Specicify the source language which we will translate from. If not specified 'en' will be used",
          'optional' => true));
