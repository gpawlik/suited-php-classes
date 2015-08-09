<?php

namespace diversen;

use diversen\cli\common;
use diversen\conf;

class git {
    
    /**
     * get a module name from a git repo url
     * @param string $repo url
     * @return boolean
     */
    public static function getModulenameFromRepo($repo) {
        $url = parse_url($repo);
        $parts = explode('/', $url['path']);

        if (count($parts) == 1) {
            return false;
        }
        $last = array_pop($parts);
        $module_name = substr($last, 0, -4);
        return $module_name;
    }

    /**
     * get tags for a git repo
     * @return type 
     */
    public static function getTags() {
        $command = "git tag -l";
        $ret = exec($command, $output);
        return common::parseShellArray($output);
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
    public static function getTagsRemote($url = null, $clear = null) {
        static $tags = null;

        // clear tags if operation will be used more than once.
        if ($clear) {
            $tags = null;
        }

        if ($tags == null) {
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
    public static function getTagsRemoteLatest($repo, $clear = null) {
        $tags = self::getTagsRemote($repo, $clear);
        if (count($tags) > 0) {
            sort($tags);
            return $tags[count($tags) - 1];
        }
        return null;
    }
}
