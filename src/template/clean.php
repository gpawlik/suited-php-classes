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
    public static function header () { ?>
<!doctype html>
<html lang="<?=conf::$vars['coscms_main']['lang']?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!--[if lt IE 9]>
	<script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
<![endif]-->
<title><?=assets::getTitle(); ?></title>

<?php

assets::setRelAsset('css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.min.css');  
assets::setRelAsset('js', '//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js');  
assets::setRelAsset('js', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js');

echo assets::getRelAssets();
echo assets::getJsHead();
echo meta::getMeta();

echo favicon::getFaviconHTML();
echo assets::getCompressedCss();
echo assets::getInlineCss();

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/2.23.0/css/uikit.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/2.23.0/js/uikit.min.js"></script>
</head>
<body><?php
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
