<?php

use diversen\conf;
use diversen\profile;
use diversen\cli\common;


/**
 * build source package with more simple form of install. 
 * @param array $options
 */
function cos_build_simple($options = null) {

    $dir = getcwd();
    $name = basename($dir);

    if (file_exists("./build/$name")) {
        common::execCommand("sudo rm -rf ./build/$name*");
    }
    common::execCommand("mkdir ./build/$name");

    $htdocs = "cp -rf htdocs/* ./build/$name";
    common::execCommand($htdocs);

    $domain = conf::getMainIni('domain');
    if (!$domain) {
        $domain = 'default';
    }

    $files_rm = "sudo rm -rf ./build/$name/files/$domain/*";
    common::execCommand($files_rm);

    $config = "mkdir ./build/$name/config";
    common::execCommand($config);

    $tmp_dir = "mkdir ./build/$name/tmp";
    common::execCommand($tmp_dir);
    
    $profiles = "cp -rf profiles ./build/$name";
    common::execCommand($profiles);

    $sql_scripts = "cp -rf scripts ./build/$name";
    common::execCommand($sql_scripts);

    $cli = "cp -rf coscli.sh ./build/$name";
    common::execCommand($cli);
    
    $composer = "cp -rf composer.json ./build/$name";
    common::execCommand($composer);

    // reset database password
    $ary = conf::getIniFileArray("./config/config.ini");
    $profile = new profile();

    $ary = $profile->iniArrayPrepare($ary);

    // clean ini settings for secrets
    $ini_settings = conf::arrayToIniFile($ary);

    // add ini dist file
    file_put_contents("./build/$name/config/config.ini-dist", $ini_settings);

    $index = "cp -rf htdocs/index.php ./build/$name/index.php";
    common::execCommand($index);

    $phar_cli = "cp -rf phar-cli.php ./build/$name/";
    common::execCommand($phar_cli);
    
    $phar_web = "cp -rf phar-web.php ./build/$name/";
    common::execCommand($phar_web);

    $module_dir = conf::pathModules();
    $modules = "cp -rf $module_dir ./build/$name";
    common::execCommand($modules);

    $vendor = "cp -rf vendor ./build/$name";
    common::execCommand($vendor);

    $rm_git = "rm `find ./build/$name -name '.git'` -rf";
    common::execCommand($rm_git);

    $rm_ignore = "rm `find ./build/$name -name '.gitignore'` -rf";
    common::execCommand($rm_ignore);

    $rm_doc = "rm -rf ./build/vendor/doc";
    common::execCommand($rm_doc);

    $output = array();

    exec('git tag -l', $output);
    $version = array_pop($output);

    $command = "cd  ./build && tar -Pczf $name-$version.tar.gz $name ";
    common::execCommand($command);
}

self::setCommand('build', array(
    'description' => 'Build distros',
));

self::setOption('cos_build_simple', array(
    'long_name' => '--build',
    'description' => 'Will build a distribution from current source where vendor is in base path',
    'action' => 'StoreTrue'
));
