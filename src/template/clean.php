<?php

namespace diversen\template;

use diversen\conf;
use diversen\template;
use diversen\template\assets;

/**
 * file contains a clean template, which can be used
 * if we need to print a clean page
 * @package template
 */

/**
 * class contains a clean template, which can be used
 * if we need to print a clean page
 * @package template
 */
class clean {
    
    /**
     * echo the header
     */
    public static function header () { 
        $lang = conf::getMainIni('lang');
        if (!$lang) {
            $lang = 'en';
        }
        
        ?>
<!DOCTYPE html>
<html>
    <head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!--[if lt IE 9]>
                <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
        <![endif]-->
        <title><?= assets::getTitle(); ?></title>

        <?php
        assets::setRelAsset('css', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
        assets::setRelAsset('css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css');
        //assets::setRelAsset('css', '/bower_components/normalize.css/normalize.css');
        assets::setRelAsset('js', '//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js');
        assets::setRelAsset('js', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js');
        assets::setRelAsset('css', '//cdn.jsdelivr.net/highlight.js/8.7/styles/default.min.css');
        
        if (!conf::getModuleIni('uikit_highlight_disable')) {
            assets::setRelAsset('js', '//cdn.jsdelivr.net/highlight.js/8.7/highlight.min.js');
        }
        echo assets::getRelAssets();
        echo assets::getJsHead();
        echo meta::getMeta();
        
        //assets::setTemplateCss('uikit', null, 10);

        // assets::setJs('/bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js', 0);

        echo favicon::getFaviconHTML();
        echo assets::getCompressedCss();
        echo assets::getInlineCss();
        
        
        if (!conf::getModuleIni('uikit_highlight_disable')) { ?>
        <script>hljs.initHighlightingOnLoad();</script>
        <?php } ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/2.22.0/js/uikit.min.js"></script>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/2.22.0/css/components/notify.gradient.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/2.22.0/js/components/notify.min.js"></script>
        
    </head>
<body>
        
    <style>
        a.uk-aktive {
            color: #333;
        }
        .main {
            overflow-y: scroll;
        }
        
    </style><?php
    }

    /**
     * eho the footer
     */
    public static function footer () {

echo template::getEndHTML();
echo assets::getCompressedJs();
echo assets::getInlineJs();

?>
</body>
</html><?php 
    }
}
