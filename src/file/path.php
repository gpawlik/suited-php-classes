<?php

namespace diversen\file;
/**
 * File contains single function for getting utf8 about a path. 
 * works in the same way as the native function pathinfo
 * @package file 
 */

/**
 * class version of pathinfo_utf8
 * @package file
 */
class path {

    public static function utf8($path) {
        return self::pathinfo_utf8($path);
    }

    //////////////////////////////////////////////////////
    //
    // http://xszhuchao.blogbus.com/logs/130081187.html
    // I Refer above article.
    // Fix Some Exception.
    // Make a Function like pathinfo.
    // This is useful for multi byte characters.
    // There some example on the bottom。
    // I Use 繁體中文
    // My Blog
    // http://samwphp.blogspot.com/2012/04/pathinfo-function.html
    //////////////////////////////////////////////////////
    /**
     * function returns utf8 pathinfo as the native pathinfo returns pathinfo
     * @param string $path
     * @return array $pathinfo
     */
    public static function pathinfo_utf8($path) {

        $dirname = '';
        $basename = '';
        $extension = '';
        $filename = '';

        $pos = strrpos($path, '/');

        if ($pos !== false) {
            $dirname = substr($path, 0, strrpos($path, '/'));
            $basename = substr($path, strrpos($path, '/') + 1);
        } else {
            $basename = $path;
        }

        $ext = strrchr($path, '.');
        if ($ext !== false) {
            $extension = substr($ext, 1);
        }

        $filename = $basename;
        $pos = strrpos($basename, '.');
        if ($pos !== false) {
            $filename = substr($basename, 0, $pos);
        }

        return array(
            'dirname' => $dirname,
            'basename' => $basename,
            'extension' => $extension,
            'filename' => $filename
        );
    }
    
    /**
     * http://stackoverflow.com/questions/4049856/replace-phps-realpath
     * This function is to replace PHP's extremely buggy realpath().
     * @param string The original path, can be relative etc.
     * @return string The resolved path, it might not exist.
     */
    public static function truepath($path) {
        $isEmptyPath = (strlen($path) == 0);
        $isRelativePath = ($path{0} != '/');
        $isWindowsPath = !(strpos($path, ':') === false);

        if (($isEmptyPath || $isRelativePath) && !$isWindowsPath){
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }
        // resolve path parts (single dot, double dot and double delimiters)
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $pathParts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutePathParts = array();
        foreach ($pathParts as $part) {
            if ($part == '.')
                continue;

            if ($part == '..') {
                array_pop($absolutePathParts);
            } else {
                $absolutePathParts[] = $part;
            }
        }
        $path = implode(DIRECTORY_SEPARATOR, $absolutePathParts);

        // resolve any symlinks
        if (file_exists($path) && linkinfo($path) > 0){
            $path = readlink($path);
        }

        // put initial separator that could have been lost
        $path = (!$isWindowsPath ? '/' . $path : $path);

        return $path;
    }

}
