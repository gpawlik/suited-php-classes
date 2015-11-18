<?php

namespace diversen\js;

use diversen\template\assets;

/**
 * Loads the simpleMde - simple markdown editor - based on codemirror
 * https://github.com/NextStepWebs/simplemde-markdown-editor
 */
class simpleMde {
    
    public static function load () {
        
        assets::setCss('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', null, array('head' => true));
        assets::setJs('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', null, array ('head' => true));
        
    }
    
}