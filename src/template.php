<?php

namespace diversen;
use diversen\html;
use diversen\moduleloader;
use diversen\conf as conf;
use diversen\template\assets;
use diversen\template\meta as meta;
use diversen\template\favicon;
use diversen\template\logo;
/**
 * File containing template class. 
 * 
 * @package template
 */

/**
 * simple template class for cos cms
 * which will be used for display the page
 * 
 * @package template
 */
class template {

    /**
     * holding title of page being parsed
     * @var string $title
     */
    public static $title = '';

    /**
     * holding end html string
     * @var string $endHTML
     */
    public static $endHTML = '';

    /**
     * holding start html string
     * @var string $startHTML
     */
    public static $startHTML = '';
    
    /**
     * holding end of content string
     * @var string $endContent  
     */
    public static $endContent = '';
    
    /**
     * holding templateName
     * @var string  $templateName 
     */
    public static $templateName = null;

    /**
     * var telling how to render 
     * @var string $render basic|all
     */
    public static $render = 'all';

    /**
     * what to render of the template
     * 
     * 'all' => default 
     * 'basic' => leave body out, only render <html> ... <body> and
     *                                        </body> ... </html>
     *                                            
     */
    public static function render ($render = 'all') {
        self::$render = $render;
    }

    
    /**
     * method for setting title of page
     * @param string $title the title of the document
     */
    public static function setTitle($title){
        self::$title = html::specialEncode($title);
    }

    /**
     * method for getting title of page
     * @return string   $title title of document
     */
    public static function getTitle(){
        if (!empty(self::$title)) {
            return self::$title;
        }
        return html::specialEncode(conf::getMainIni('meta_title'));
    }

    /**
     * method for setting meta tags. The tags will be special encoded
     * @param   array   $ary of metatags e.g. 
     *                         <code>array('description' => 'content of description meta tags')</code>
     *                         or string which will be set direct. E.g. 
     *                         
     */
    public static function setMeta($ary){
        meta::setMeta($ary);
    }
    
    /**
     * sets meta tags directly. 
     * @param string $str e.g. <code><meta name="description" content="test" /></code>
     */
    public static function setMetaAsStr ($str) {
        meta::setMetaAsStr($str);
    }
    
    /**
     * check if template common.inc exists
     * @param string $template
     * @return boolean $res true if exists else false
     */
    public static function templateCommonExists ($template) {
        if (file_exists( conf::pathHtdocs() . "/templates/$template/common.inc")) {
            return true;
        }
        return false;
    }

    
    /**
     * returns a included files content with vars substitued
     * @param string $filename
     * @param array $vars
     * @return mixed $res false on failure and string on success
     */
    
    public static function getFileIncludeContents($filename, $vars = null) {
        if (is_file($filename)) {
            ob_start();
            include $filename;
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        return false;
    }

    /**
     * method for adding string to end of html
     * @param   string  string to add to end of html
     */
    public static function setStartHTML($str){
        self::$startHTML.=$str;
    }

    /**
     * method for getting end of html
     * @return  string  end of html
     */
    public static function getStartHTML(){
        return self::$startHTML;
    }
    
    /**
     * method for adding string to end of html
     * @param   string  string to add to end of html
     */
    public static function setEndHTML($str){
        self::$endHTML.=$str;
    }

    /**
     * method for getting end of html
     * @return  string  end of html
     */
    public static function getEndHTML(){
        return self::$endHTML;
    }

    /**
     * method for setting end html
     * @param string    end content
     */
    public static function setEndContent($str){
        self::$endContent.=$str;
    }

    /**
     * method for getting end of html
     * @return <type>
     */
    public static function getEndContent(){
        return self::$endContent;
    }
    
    /**
     * inits a template
     * set template name and load init settings
     * @param string $template name of the template to init. 
     */
    public static function init ($template) {       
        self::$templateName = $template;
        if (!isset(conf::$vars['template'])) {
            conf::$vars['template'] = array();
        }       
        moduleloader::setModuleIniSettings($template, 'template');
        $css = conf::getMainIni('css');
        if ($css) {
            assets::setTemplateCssIni($template, $css);
        }
    }
    
    public static function loadTemplateIniAssets () {
        assets::loadTemplateIniAssets();
    }
}
