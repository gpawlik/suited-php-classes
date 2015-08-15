<?php

namespace diversen\autoloader;
use diversen\conf;
class modules {

    
    /*
    public function moduleAutoLoader($classname) {

        $classname = ltrim($classname, '\\');
        $filename = '';
        $namespace = '';
        if ($lastnspos = strripos($classname, '\\')) {
            $namespace = substr($classname, 0, $lastnspos);
            $classname = substr($classname, $lastnspos + 1);
            $filename = str_replace('\\', '/', $namespace) . '/';
        }
        $filename = str_replace('_', '/', $classname) . '.php';
        include $filename;
    }*/

    public function autoloadRegister () {
        //spl_autoload_register(array($this, 'moduleAutoLoader'));
        spl_autoload_register(array($this, 'modulesAutoLoader'));
    }
    
    public function modulesAutoLoader ($classname) {        
        $class = str_replace('\\', '/', $classname) . "";
        $class = conf::pathBase() . "/" . $class.= ".php";
        //die;
        if (file_exists($class)) {
            require $class;
        } 
    }
}