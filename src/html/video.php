<?php

namespace diversen\html;

use diversen\template\assets;

class video {

    public function videojsInclude() {

        static $loaded = null;
        $css = <<<EOF
.video-js {
    min-width:100%; 
    max-width:100%;

}
.vjs-fullscreen {padding-top: 0px}
EOF;
        if (!$loaded) {
            assets::setJs("http://vjs.zencdn.net/5.0.2/video.js");
            assets::setCss('http://vjs.zencdn.net/5.0.2/video-js.css');
            assets::setStringCss($css, null, array('head' => true));
            $loaded = true;
            return $css;
        }
    }

    /**
     * 
     * @param string $title
     * @param array $formats files to use
     * @return string $html
     */
    function getVideojsHtml($title, $formats) {

        $mp4 = $formats['mp4'];
        $str = <<<EOF
<div class="wrapper">
 <div class="videocontent">
	
<video id="really-cool-video" class="video-js vjs-default-skin" controls
 preload="auto" 
 data-setup='{}'>
  <source type="video/mp4" src="$mp4">  
  <p class="vjs-no-js">
    To view this video please enable JavaScript, and consider upgrading to a web browser
    that <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
  </p>
</video>
  </div>
</div>
EOF;
        return $str;
    }

    /**
     * 
     * @param string $title
     * @param array $formats files to use
     * @return string $html
     */
    public function getHtml5($title, $formats) {

        $mp4 = $formats['mp4'];
        $str = <<<EOF
<video width="100%" controls="true">
  <source src="$mp4" type="video/mp4" /> 
  Your browser does not support HTML5 video.
</video>
EOF;
        return $str;
    }
}
