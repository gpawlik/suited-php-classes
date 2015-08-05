<?php

/**
 * @description silly router
 * 
 * 
 */
namespace diversen;

class silly {
    
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
        $file =  $this->modules . "/" . $this->controller . ".php";
        if (file_exists($file)) {
            include_once $file;
        }
        $this->setAction();
    }
    
    public function setController () {
        
        // $ary = explode('/', $_SERVER['REQUEST_URI']);
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $ary = explode('/', $url);
        
        if (!isset($ary[2])) {
            $this->controller = "default";
            $this->action = $ary[1];
            if (empty($this->action)) {
                $this->action = 'index';
            }

        } else {
            $this->controller = $ary[1];
            $this->action = $ary[2];
            if (empty($this->action)) {
                $this->action = 'index';
            }
        }
    }
    
    public function setAction () {
        
        $class = $this->controller . "Module";
        $action = $this->action . "Action";
        if (method_exists($class, $action)) {
            $object = new $class();
            $object->$action();
        } else {
            $this->notFound();
        }
    }
    
    public function notFound () {
        
        $file =  $this->modules . "/error.php";
        if (file_exists($file)) {
            include_once $file;
            $object = new \errorModule();
            $object->notFound();
            return;
        }
        header("HTTP/1.0 404 Not Found");
        echo "Page was not found!";
    }
}
