<?php

/**
 * @description modulized php micro framework
 * a php micro framework with modules
 */
namespace diversen;

class micro {
    
    /** Controller name */
    private $controller;
    /** Modules placement name */
    public $modules ='modules';
    /** Default module */
    public $default = 'main';
    /** Controller action */
    private $action = null;
    
    /**
     * Route a request to a module
     * Parse module action or parse default error action
     */
    public function parse() {
        
        $this->setControllerAction();       
        $file =  $this->modules . "/" . $this->controller . "/module.php";    
        if (file_exists($file)) {
            include_once $file;
        }
        $this->parseRequest();
    }
    
    /**
     * Route a request to a module
     * Parse module action or parse default error action
     * @return str $str
     */
    public function parseGetStr() {
        ob_start();
        $this->parse();
        $str = ob_get_contents();
        ob_clean();
        return $str;
    }
    
    /**
     * Sets a controller and a action
     */
    private function setControllerAction () {
        
        // Parse url. 3 case:
        // a) Default controller
        // b) Module controller
        // C) Sub module controller
        
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $ary = explode('/', $url);
        
        // Submodule e.g. /github/connect/index
        if (isset($ary[3])) {
            $this->controller = $ary[1] . "/" . $ary[2];
            $this->action = $ary[3];
            if (empty($this->action)) {
                $this->action = 'index';
            }
            return;
        }
        
        // Default e.g /index or / or /test
        if (!isset($ary[2])) {
            $this->controller = $this->default; //"main";
            $this->action = $ary[1];
            if (empty($this->action)) {
                $this->action = 'index';
            }
        // module (base) e.g. /github/index or /github/gist   
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
    private function pathToClass ($path) {
        return str_replace('/', '\\', $path);
    }
    
    /**
     * set a module action
     */
    private function parseRequest () {
        
        $path = "modules/". $this->controller . "/module";
        $class = $this->pathToClass($path);
        $action = $this->action . "Action";
        
        if (method_exists($class, $action)) {
            $object = new $class();
            
            // Run actions based on class name and method
            // security
            
            $object->$action();
        } else {
            $this->notFound();
        }
    }
    
    /**
     * Check for error module or display short 
     * error notice with 404 header
     * @return void
     */
    private function notFound () {
        
        $path = "modules/". $this->controller . "/module";
        $class = $this->pathToClass($path);
        
        $path =  $this->modules . "/error/module";
        $class = $this->pathToClass($path);
        if (file_exists($path)) {
            include_once $path;
            $object = $class();
            $object->notFound();
            return;
        }
        header("HTTP/1.0 404 Not Found");
        echo "Page was not found!";
    }
    
    public function accessControl () {
        
    }
}
