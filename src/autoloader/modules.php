<?php

namespace diversen\autoloader;
use diversen\conf;
class modules {

    public function autoloadRegister () {
        spl_autoload_register(array($this, 'modulesAutoLoader'));
    }
    
    public function modulesAutoLoader ($classname) {        
        $class = str_replace('\\', '/', $classname) . "";
        $class = conf::pathBase() . "/" . $class.= ".php";
        if (file_exists($class)) {
            require $class;
        } 
    }
}