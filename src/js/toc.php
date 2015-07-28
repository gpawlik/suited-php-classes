<?php

namespace diversen\js;

use diversen\template;
/**
 * file conatins php code for setting toc js in templates
 * @package js
 */

/**
 * class conatins php code for setting toc js in templates
 * @package js
 */
class toc {
    
    /**
     * function to create a easy TOC for any module. 
     * @param array $options e.g. array ('exclude' => 'h1', 'content' => '#content_article'); 
     */
    public static function set ($options = array ()) {
        template::setJs('/bower_components/toc/dist/toc.min.js');
        if (!isset($options['exclude'])) {
            $options['exclude'] = 'h4,h5,h6';
        }
        if (!isset($options['context'])) {
            $options['context'] = '#content';
        }
        $str = <<<EOF
    $(document).ready(function() {
        $('#toc').toc({exclude: '{$options['exclude']}' , context: '{$options['context']}', autoId: true, numerate: true, 'smoothScrolling': false});
    });
EOF;
        template::setStringJs($str);
    }
}
