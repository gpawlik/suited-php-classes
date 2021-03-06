<?php

namespace diversen\template;
use diversen\html;
use diversen\moduleloader;
use diversen\conf as conf;


/**
 * File containing class getting logo
 * @package template
 */

/**
 * 
 * File containing class getting logo
 * @package template
 */

class logo {
    
        /**
     * method for getting html for front page. If no logo has been 
     * uploaded. You will get logo as html
     * @param type $options options to give to html::createHrefImage
     * @return string $str the html compsoing the logo or main title
     */
    public static function getLogoHTML ($options = array()) {
        $logo = conf::getMainIni('logo');
        if (!$logo) {

            $title = $_SERVER['HTTP_HOST'];
            $link = html::createLink('/', $title);
            return $str = "<div id=\"logo_title\">$link</div>";
        } else {
            $file ="/logo/" . conf::getMainIni('logo');
            $src = conf::getWebFilesPath($file);
            if (!isset($options['alt'])){           
                $options['alt'] = $_SERVER['HTTP_HOST'];
            }
            $href = html::createHrefImage('/', $src, $options);
            $str = '<div id="logo_img">' . $href . '</div>' . "\n"; 
            //die($str);
            return $str;
        }
    }
    
}