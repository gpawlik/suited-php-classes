<?php

/**
 * @description silly router
 * 
 * 
 */
namespace diversen;

class micro {
    
    public $controller;
    public $modules ='modules';
    public $default = 'default';
    public $routes = array ();
    public $action = null;
    public function addRoute ($url, $file = null) {
        $this->routes[$url] = $file;
    }
    
    /**
     * set a controller file
     * include controller file
     * execute controller action
     */
    public function execute() {
        
        $this->setController();       
        $file =  $this->modules . "/" . $this->controller . "/module.php";
        
        if (file_exists($file)) {
            include_once $file;
        }
        
        $this->setAction();
    }
    
    public function setController () {
        
        // parse url. 3 cases 
        // a) default controller
        // b) module controller
        // C) sub module controller
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $ary = explode('/', $url);
        
        // submodule e.g. /github/connect/index
        if (isset($ary[3])) {
            $this->controller = $ary[1] . "/" . $ary[2];
            $this->action = $ary[3];
            if (empty($this->action)) {
                $this->action = 'index';
            }
            return;
        }
        
        // default e.g /index or / or /test
        if (!isset($ary[2])) {
            $this->controller = "main";
            $this->action = $ary[1];
            if (empty($this->action)) {
                $this->action = 'index';
            }
        // module e.g. /github/index or /github/gist   
        } else {
            $this->controller = $ary[1];
            $this->action = $ary[2];
            if (empty($this->action)) {
                $this->action = 'index';
            }
        }
    }
    
    /**
     * transforms a path to a class name
     * @param string $path
     * @return string $class
     */
    public function pathToClass ($path) {
        return str_replace('/', '\\', $path);
    }
    
    /**
     * set a module action
     */
    public function setAction () {
        
        $path = "modules/". $this->controller . "/module";
        $class = $this->pathToClass($path);
        
        $action = $this->action . "Action";
        if (method_exists($class, $action)) {
            $object = new $class();
            $object->$action();
        } else {
            $this->notFound();
        }
    }
    
    /**
     * check for error module or display short 
     * error notice with 404 header
     * @return void
     */
    public function notFound () {
        
        $path = "modules/". $this->controller . "/module";
        $class = $this->pathToClass($path);
        
        $path =  $this->modules . "/error/module.php";
        $class = $this->pathToClass($path);
        if (file_exists($path)) {
            include_once $path;
            $object = new \errorModule();
            $object->notFound();
            return;
        }
        header("HTTP/1.0 404 Not Found");
        echo "Page was not found!";
    }
}
