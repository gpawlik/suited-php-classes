<?php

namespace diversen\uri;
use diversen\moduleloader;
use diversen\db\q as q;
use diversen\conf as conf;
/**
 * simple url dispatch class
 * @package uri 
 */

/**
 * simple class for dispatching url patterns to a controller file or class
 * call. This gives option for setting a path e.g. /my/homemade/path to
 * call a specified controller. This should be set in a modules install 
 * file as the urlmatching patterns are stored in database. Example from 
 * a install.inc file: 
 * 
 * @package uri 
 */
class dispatch {
    
    /**
     * var holding pathInfo
     * @var array $pathInfo
     */
    public static $pathInfo = array();
    
    /**
     * parses pathinfo with parse_url
     */
    public static function parse () {       
        self::$pathInfo = parse_url($_SERVER['REQUEST_URI']);
    }
   
    /**
     * method for calling function or static method
     * @param type $call
     * @param type $matches
     * @return boolean $res if no function or method is found return false. 
     */
    public static function call ($call) {
        $ary = explode('::', $call);
        $call_exists = null;
        
        ob_start();
        
        // call is a function
        if (count($ary) == 1) {
            if (function_exists($call)) {
                $call_exists = 1;
                $call();
                
            }
        }
        
        // call is a class
        if (count($ary == 2)) {
            $class = $ary[0]; $method = $ary[1];
            if (method_exists($class, $method)) {
                
                $call_exists = 1;
                $o = new $class;
                $o->$method();
                //$class::$method();
                if (isset(moduleloader::$status[403])){
                    moduleloader::includeModule('error');
                    moduleloader::includeController('error/403');
                }  
                
                if (isset(moduleloader::$status[404])){
                    moduleloader::includeModule('error');
                    moduleloader::includeController('error/404');
                }   
            }
        }  

        return ob_get_clean();
    }
    
    /**
     * returns false if no matches are found. Return true
     * if a match is found and called. 
     * @param array $routes array of routes
     * @return boolean $res true on success and false on failure.  
     */
    public static function match ($routes) {
        self::parse();        
        $matches = array();
        foreach ($routes as $pattern => $call) {           
            if (preg_match($pattern, self::$pathInfo['path'] , $matches)) {
                $res = self::call($call, $matches);
                return $res;
            } else {
                return false;
            }
        }
    }
    
    /**
     * sets db routes
     */
    public static function setDbRoutes () {
        $routes = q::setSelect('system_route')->fetch();
        if (empty($routes)) { 
            conf::$vars['coscms_main']['routes'] = array ();
        }
        
        foreach ($routes as $route) {
            conf::$vars['coscms_main']['routes'][$route['route']] = unserialize($route['value']);  
        }
    }
    
    /**
     *  return all database routes
     * @return array $routes
     */
    public static function getDbRoutes () {
        return conf::$vars['coscms_main']['routes'];
    }
    
    /**
     * examine path traverse routes and return match if any
     * @return mixed $res false if no match or array with match
     */
    public static function getMatchRoutes () {
        self::parse();        
        $matches = array();
        $routes = self::getDbRoutes();
        
        foreach ($routes as $pattern => $call) { 
            if (preg_match($pattern, self::$pathInfo['path'] , $matches)) {
                return $call;
            } 
        }
        return false;
    }
}
