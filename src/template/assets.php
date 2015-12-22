<?php

namespace diversen\template;

use diversen\template;
use diversen\conf;
use diversen\file;
use diversen\layout;

/**
 * File containing class for parsing template assets. 
 * @package template
 */


/**
 * class used for parsing assets (css and js) and caching them
 * @package template
 */

class assets extends template {
    
    /**
     * holding css files
     * @var array   $css
     */
    public static $css = array();
    

    /**
     *  holding js files
     * @var array $js
     */
    public static $js = array();
    
    /**
     * holding head js
     * @var array $jsHead
     */
    public static $jsHead = array ();

    /**
     * holding rel elements
     * @var array $rel
     */
    public static $rel = array ();
    
    /**
     * holding inline js strings
     * @var array $inlineJs
     */
    public static $inlineJs = array();

    /**
     * holding inline css strings
     * @var array $inlineCss 
     */
    public static $inlineCss = array();
    
        
    /**
     * name of dir where we cache assets
     * @var string $cacheDir 
     */
    public static $cacheDir = 'cached_assets';
    
    /**
     * Checks if cache dir exists. If not, then it will be created.
     * @staticvar boolean $cacheChecked
     */    
    public static function checkCreateCacheDir () {
        static $cacheChecked = false;
        if (!$cacheChecked) {
            self::$cacheDir = conf::getFullFilesPath() . '/' . self::$cacheDir;
            if (!file_exists(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0770, true);
            }  
            $cacheChecked = true;
        }
    }

    /**
     * gets rel assets. assure that we only get every asset once.
     * @return string $assets 
     */
    public static function getRelAssets () {
        $css = self::getRelAssetsType('css');
        $js = self::getRelAssetsType('js');
        return $css . PHP_EOL . $js . PHP_EOL;
    }
    
    public static function getRelAssetsType ($type) {
        $str = '';
        
        if (!isset(self::$rel[$type])) {
            return '';
        }
        
        foreach (self::$rel[$type] as $val) {
            $str.=$val;
        }
        return $str;
    }
    
    /**
     * Method for adding CSS or JS to a HTML document. 
     * @param string $type 'css' or 'js'
     * @param string $link 'src' link of the asset 
     */
    public static function setRelAsset ($type, $link) {
        if ($type == 'css') {
            self::$rel[$type][] = "<link rel=\"stylesheet\" href=\"$link\" />" . PHP_EOL;
        }
        if ($type == 'js') {
            self::$rel[$type][] = "<script src=\"$link\"></script>" . PHP_EOL;
        }
    }
    
    
    /**
     * Method for setting CSS which is in the public space, e.g. htdocs
     * @param string $css_url e.g. /templates/module/good.css
     * @param int  $order 0 is loaded first and > 0 is loaded later
     * @param array $options
     */
    public static function setCss($css_url, $order = null, $options = null){

        if (isset(self::$css[$order])) {
            self::setCss($css_url, $order + 1, $options);
        } else {
            self::$css[$order] = $css_url;
        }
    }

    /**
     * method for getting css for displaing in user template
     * @return  string  the css as a string
     */
    public static function getCss(){
        
        $str = "";
        ksort(self::$css);
        foreach (self::$css as $key => $val){
            $str.= "<link rel=\"stylesheet\" href=\"$val\" />\n";
            unset(self::$css[$key]);
        }
        return $str;
        
    }
    
    /**
     * Return a CSS take with $css 
     * @param string $css
     * @return string $str
     */
    public static function getCssLinkRel ($css) {
        return "<link rel=\"stylesheet\" type=\"text/css\" href=\"$css\" />\n";
    } 
    
    /**
     * Get all content of self::$css as a single CSS string
     * @return string $css
     */
    public static function getCssAsSingleStr () {
        
        $str = '';
        foreach (self::$css as $key => $val) {
            $file = conf::pathHtdocs() . "$val";
            $str.= PHP_EOL . "/* Caching $file */" . PHP_EOL;
            $str.= file::getCachedFile($file) . PHP_EOL;
            unset(self::$css[$key]);
        }
        foreach (self::$inlineCss as $key => $val) {
            $str.= PHP_EOL . $val . PHP_EOL;
            unset(self::$inlineCss[$key]);
        }
        
        return $str;
        
    }
    
    /**
     * Gets all contents of self::$css, and place the content in a single CSS
     * file with a named based on md5
     * This file is the set as the only file in self::$css
     */
    public static function setCssAsSingleFile () {
        
        // Note. All files are read every time
        $str = self::getCssAsSingleStr ();    
        $md5 = md5($str);
        $domain = conf::getDomain();
            
        $web_path = "/files/$domain/cached_assets";
        $file = "/css_all-$md5.css";
           
        $full_path = conf::pathFilesBase() . "$web_path";
        if (!file_exists($full_path)) {
            mkdir($full_path, 0770);
        }
        
        $full_file_path = $full_path . $file;
        if (!file_exists($full_file_path)) {                  
            file_put_contents($full_file_path, $str, LOCK_EX);
        }            
        self::setCss($web_path . "$file"); 
    }
    
    /**
     * takes all CSS and puts in one file. It works the same way as 
     * template::getCss. You can sepcify this in your ini settings by using
     * cached_assets_compress = 1
     * Usefull if you have many css files. 
     * @return string $str
     */
    public static function getCompressedCss(){
        
        // Generate single source file 
        ksort(self::$css);
        if (conf::getMainIni('cached_assets')) {
            self::setCssAsSingleFile();  
        }
        return self::getCss();
        
    }

    
    /**
     * Will load the js as file and place and add it to array which can
     * be parsed in user templates.
     * 
     * @param   string   $js file path of the javascript
     * @param   int $order the loading order of javascript 0 is first > 0 is
     *                   later.
     * @param array $options
     */
    public static function setStringJs($js, $order = null, $options = array()){
        
        if (isset($options['search'])){
            $js = str_replace($options['search'], $options['replace'], $js);
        }
        
        if (isset($order)){
            if (isset(self::$inlineJs[$order])) {
                self::setStringJs($js, $order +1);
            }
            self::$inlineJs[$order] = $js;
            
        } else {
            self::$inlineJs[] = $js;
        }
    }
    
        /**
     * Will load the js as file and place and add it to array which can
     * be parsed in user templates.
     * 
     * @param   string   $js file path of the javascript
     * @param   int $order the loading order of javascript 0 is first > 0 is
     *                   later.
     * @param array $options
     */
    public static function setStringCss($css, $order = null, $options = array()){
        
        if (isset($options['search'])){
            $css = str_replace($options['search'], $options['replace'], $css);
        }
        
        if (isset($order)){
            if (isset(self::$inlineCss[$order])) {
                self::setStringCss($css, $order +1);
            }
            self::$inlineCss[$order] = $css;
            
        } else {
            self::$inlineCss[] = $css;
        }
    }
    
    /**
     * method for setting js files to be used in user templates.
     * @param   string   $js_url /web/path/to/my.js 
     * @param   int      $order loading order 0 = start e.g 10000 = late
     * @param   array    $options defaults: array ('head' => false)
     */
    public static function setJs($js_url, $order = null, $options = null){
        if (isset($options['head'])) {
            self::$jsHead[] = $js_url;
            return;
        }

        if (isset(self::$js[$order])) {
            self::setJs($js_url, $order + 1, $options);
        } else {
            self::$js[$order] = $js_url;
        }
    }
    
    /**
     * Set JS in head of document
     * @param string $js_url
     * @param int $order
     */
    public static function setJsHead ($js_url, $order = null) {
        self::setJs($js_url, $order, array ('head' => true));
    }
    
    /**
     * method for getting css files used in user templates
     * @return  string  the css as a string
     */
    public static function getJs(){
        $str = "";
        ksort(self::$js);

        foreach (self::$js as $key => $val){
            $str.= "<script src=\"$val\"></script>\n";
            unset(self::$js[$key]);
        }
        return $str;
        
    }
    
    /**
     * gets all css as a single string
     * @return string $css
     */
    public static function getJsAsSingleStr () {
        
        $str = '';
        foreach (self::$js as $key => $val) {
            $file = conf::pathHtdocs() . "$val";
            $str.= PHP_EOL . "/* Caching JS $file */" . PHP_EOL;
            $str.= file::getCachedFile($file) . PHP_EOL;
            unset(self::$js[$key]);
        }
        foreach (self::$inlineJs as $key => $val) {
            $str.= PHP_EOL . $val . PHP_EOL;
            unset(self::$inlineJs[$key]);
        }
        
        return $str;
    }
    
    /**
     * sets js as a single file in js-all file 
     */
    public static function setJsAsSingleFile () {
        $str = self::getJsAsSingleStr();
        
        $md5 = md5($str);
        $domain = conf::getDomain();
            
        $web_path = "/files/$domain/cached_assets"; 
        $file = "/js_all-$md5.js";
           
        $full_path = conf::pathFilesBase() . "/$web_path";
        $full_file_path = $full_path . $file;
            
        // create file if it does not exist
        if (!file_exists($full_file_path)) {
            file_put_contents($full_file_path, $str, LOCK_EX);
        }
        self::setJs($web_path . $file);
    }
    
    /**
     * takes all JS and puts them in one file. It works the same way as 
     * template::getJs (except you only get one file) 
     * You can sepcify this in your ini settings by using
     * cached_assets_compress = 1
     * Usefull if you have many JS files. 
     * @return string $str
     */
    public static function getCompressedJs(){
        
        ksort(self::$js);        
        if (conf::getMainIni('cached_assets')) {
            self::setJsAsSingleFile();
        }
        return self::getJs();   
        
    }
    
    /**
     * gets js for head as a string
     */
    public static function getJsHead(){
        $str = "";
        ksort(self::$jsHead);
        foreach (self::$jsHead as $val){
            $str.= "<script src=\"$val\" type=\"text/javascript\"></script>\n";
        }
        return $str;
    }
    
    
    /**
     * search and replace in a asset, e.g. js or css
     * @param string $str asset
     * @param type $options array ('search' => 'SEARCH STRING', 'replace' => 'REPLACE STRING')
     * @return string $str asset
     */
    public static function searchReplace($str, $options) {
        $str = str_replace($options['search'], $options['replace'], $str);
        return $str;
    }
    /**
     * Will load the js as file and place and add it to array which can
     * be parsed in user templates. This is used with js files that exists
     * outside webspace, e.g. in modules
     * 
     * @param   string   $js file path of the javascript
     * @param   int      $order the loading order of javascript 0 is first > 0 is
     *                   later.
     * @param array $options
     */
    public static function setInlineJs($js, $order = null, $options = array()){

        $str = file::getCachedFile($js);
        if (isset($options['search'])){
            $str = self::searchReplace($str, $options);
        }

        if (isset(self::$inlineJs[$order])) {
            self::setInlineJs($js, $order + 1);
        } else {
            self::$inlineJs[$order] = $str;
        }
    }

    /**
     * method for getting all inline js as a string
     * @return  string  $str the js as a string
     */
    public static function getInlineJs(){
        $str = "";
        ksort(self::$inlineJs);
        foreach (self::$inlineJs as $val){         
            $str.= "<script type=\"text/javascript\">$val</script>\n";
        }
        return $str;
    }

    /**
     * Set 'inline' CSS. This will add CSS files from outside of public HTML
     * to be added to a template.
     * @param   string   $css file path of the css
     * @param   int      $order the loading order of css 0 is first > 0 is
     *                   later
     * @param array $options array ('no_cache' => 0)
     */
    public static function setInlineCss($css, $order = null, $options = array()){
          
        $str = file::getCachedFile($css);        
        if (isset(self::$inlineCss[$order])){
            self::setInlineCss($css, $order + 1);
        } else {
            self::$inlineCss[] = $str;
        }
    }
    
    /**
     * Set CSS from a module name and a CSS file. This can then be overridded
     * in a template 
     * @param   string   $module the module in context, e.g. account
     * @param   string   $css the CSS file in context, e.g. /assets/style.css
     * @param   int      $order the loading order of css 0 is first > 0 is
     *                   later.
     */
    public static function setModuleInlineCss($module, $css, $order = null, $options = array()){
        
        $module_css = conf::pathModules() . "/$module/$css";   
        $template_name = layout::getTemplateName();
        $template_override =  "/templates/$template_name/$module$css";  
        if (file_exists(conf::pathHtdocs() . $template_override) ) {
            self::setCss($template_override);
            return;
        }        
        self::setInlineCss($module_css);
    }
    
    /**
     * method for parsing a css file and substituing css var with
     * php defined values
     * @param string $css
     * @param array  $vars
     * @param int    $order
     */
    public static function setParseVarsCss($css, $vars, $order = null){
        $str = self::getFileIncludeContents($css, $vars);
        //$str = file_get_contents($css);
        if (isset($order)){
            self::$inlineCss[$order] = $str;
        } else {
            self::$inlineCss[] = $str;
        }
    }

    /**
     * method for getting css used in inline in user templates
     * @return  string  the css as a string
     */
    public static function getInlineCss(){
        $str = "";
        ksort(self::$inlineCss);
        foreach (self::$inlineCss as $key => $val){
            $str.= "<style>$val</style>\n";
        }
        return $str;
    }
    
    /**
     * load assets specified in ini settings from template
     */
    public static function loadTemplateIniAssets () {
        
        $css = conf::getModuleIni('template_rel_css');
        if ($css) {
            foreach ($css as $val) {
                self::setRelAsset('css', $val);
            }
        }
        
        $js = conf::getModuleIni('template_rel_js');
        if ($js) {
            foreach ($js as $val) {
                self::setRelAsset('js', $val);
            }   
        }
        
        $js = conf::getModuleIni('template_js');
        if ($js) {
            foreach ($js as $val) {
                self::setJs($val);
            }
        }
    }
    
    
    /**
     * checks if a css style is registered. If not
     * we use common.css in template folder.
     * 
     * @param string $template
     * @param int $order
     * @param string $version
     */
    public static function setTemplateCss ($template = '', $order = 0){

        $css = conf::getMainIni('css');
        if (!$css) {
            // If no css, use default/default.css
            self::setCss("/templates/$template/default/default.css", $order);
            return;
        }
        
        $css = "/templates/$template/$css/$css.css";
        self::setCss($css, $order);


    }
    
    /**
     * sets template css from template css ini files
     * @param string $template
     * @param string $css
     */
    public static function setTemplateCssIni ($template, $css) {
        $ini_file = conf::pathHtdocs() . "/templates/$template/$css/$css.ini";
        if (file_exists($ini_file)) {
            
            $ary = conf::getIniFileArray($ini_file, true);
            conf::$vars['coscms_main']['module'] = 
                    array_merge_recursive(conf::$vars['coscms_main']['module'], $ary);
        }        
    }
}
