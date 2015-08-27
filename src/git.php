<?php

namespace diversen;

use diversen\cli\common;
use diversen\conf;

class git {
    
    /**
     * Get a module name from a git repo url
     * Works with e.g. 
     * git@github.com:diversen/debug.git
     * git@github.com:diversen/debug
     * https://github.com/diversen/debug.git
     * https://github.com/diversen/debug
     * git://github.com/diversen/debug.git
     * git://github.com/diversen/debug
     * @param string $repo url
     * @return string $module_name
     */
    public static function getModulenameFromRepo($repo) {
        $url = parse_url($repo);
        $parts = explode('/', $url['path']);

        $module_name = array_pop($parts);
        
        if (strstr($module_name, '.git')) {
            $module_name = substr($module_name, 0, -4);
        }
        return $module_name;
    }

    /**
     * get tags for a system git repo
     * @return string $tags tags as a string
     */
    public static function getTagsInstall() {
        $command = "git tag -l";
        $ret = exec($command, $output);
        return common::parseShellArray($output);
    }
    
    /**
     * get system tags as array
     * @return array $tags
     */
    public static function getTagsInstallAsArray () {
        $tags = self::getTagsInstall();
        $ary = explode("\n", $tags);
        return array_filter($ary);
    }
    
    /**
     * get latest installed tag. This is the latest tag
     * @return array $tags
     */
    public static function getTagsInstallLatest () {
        $ary = self::getTagsInstallAsArray();
        return array_pop($ary);
    }

    /**
     * get tags for a module or template
     * @param string $module
     * @param string $type 'module' or 'template'
     * @return array|false a array or false 
     */
    public static function getTagsModule($module, $type = 'module') {

        if ($type == 'module') {
            $path = conf::pathModules() . "/$module";
        }

        if ($type == 'template') {
            $path = conf::pathHtdocs() . "/templates/$module";
        }

        $command = "cd $path && git tag -l";
        exec($command, $ary, $ret);

        // ok
        if ($ret == 0) {
            $str = shell_exec($command);

            $ary = explode("\n", $str);
            $tags = array();
            foreach ($ary as $line) {
                trim($line);
                if (empty($line))
                    continue;
                $tags[] = $line;
            }
        } else {
            return false;
        }

        return $tags;
    }

    /**
     * Get remote tags
     * 
     * See: https://github.com/troelskn/pearhub
     *
     * @param   string  $url a git url
     * @param   mixed   $clear set this and tags will not be cached in static var
     * @return  array   $ary array of remote tags
     */
    public static function getTagsRemote($url = null) {

        $tags = array();
        $output = array();
        $ret = 0;

        $command = "git ls-remote --tags $url";
        exec($command . ' 2>&1', $output, $ret);

        foreach ($output as $line) {
            trim($line);
            if (preg_match('~^[0-9a-f]{40}\s+refs/tags/(([a-zA-Z_-]+)?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?([A-Za-z]+[0-9A-Za-z-]*)?)$~', $line, $reg)) {
                $tags[] = $reg[1];
            }
        }

        return $tags;
    }

    /**
     * Get latest remote tag
     * 
     * See: https://github.com/troelskn/pearhub
     * 
     * @param   string  $repo a git url url
     * @param   boolean $clear and tags will not be cached in static var
     * @return  array   $tags array of remote tags
     */
    public static function getTagsRemoteLatest($repo) {
        $tags = self::getTagsRemote($repo);
        if (count($tags) > 0) {
            sort($tags);
            return $tags[count($tags) - 1];
        }
        return null;
    }
    
    public static function isMaster () {
        $branch = shell_exec('git branch');
        if ('* master' == trim($branch)){
            return true;
        }
        return false;
    }
    
    /**
     * get a SSH clone URL from a HTTPS clone URL
     * @param string $url
     * @return string $url
     */
    public static function getSshFromHttps($url) {
        
        $ary = parse_url(trim($url));
        $num = count($ary);
        if ($num == 1) {
            return $ary['path'];
        }
        
        // E.g. git://github.com/diversen/vote.git
        if (isset($ary['scheme']) && $ary['scheme'] == 'git') {
            return "git@$ary[host]:" . ltrim($ary['path'], '/');
        }
        
        return "git@$ary[host]:" . ltrim($ary['path'], '/');
    }

    
       /**
     * get a SSH clone URL from a HTTPS clone URL
     * @param string $url
     * @return string $url
     */
    public static function getHttpsFromSsh($url) {

        $ary = parse_url(trim($url));
        
        // Is it already https
        if (isset($ary['scheme']) && $ary['scheme'] == 'https') {
            return $url;
        }
        
        // E.g. git://github.com/diversen/vote.git
        if (isset($ary['scheme']) && $ary['scheme'] == 'git') {
            return 'https://' . $ary['host'] . $ary['path'];
        }
        

        $num = count($ary);
        if ($num == 1) {          
            return self::parsePrivateUrl($url);
        }
        return "$ary[scheme]@$ary[host]:$ary[path]";
    }
    
    /**
     * return a SSH path from a HTTPS URL
     * @param string $url
     * @return string $path
     */
    public static function parsePrivateUrl ($url) {
        $ary = explode('@', $url);
        $ary = explode(':', $ary[1]);
        $url = 'https://' . $ary[0] . "/$ary[1]";
        return $url;
    }
}
