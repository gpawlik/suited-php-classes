<?php

namespace diversen\filter;
use diversen\conf as conf;
/**
 * File containing media filter
 * @package filters
 */

/**
 * class that create inline vimeo, youtaube, and soundcloud from URLs
 * @package filters
 */
class media {

    /**
     * do the filtering
     * @param   string  $text text to filter.
     * @return  string  $text text (html) with inline videos.
     */
    public static function filter($text) {
        $text = self::doAll($text);
        return $text;
    }

    /**
     * do all filters
     * @param string $text
     * @return string $text
     */
    public static function doAll($text) {
        $text = self::linkifyVimeo($text);
        $text = self::linkifyYouTubeURLs2($text);
        $text = self::linkifySoundcloud($text);
        return $text;
    }
    
    /**
     * calculate a video ratio
     * @param int $default
     * @return int $ratio the ratio
     */
    public static function videoRatio($default = 600) {
        $width = conf::getMainIni('media_width');
        if ($width) {
            return $ratio = $width / $default;
        } else {
            // default ration is 1
            return 1;
        }
    }

    /**
     * make inline vimeo videos
     * @param string $text
     * @return string $text
     */
    public static function linkifyVimeo($text) {
        //$link = 'http://vimeo.com/10638288';
        $text = preg_replace_callback('~
        # Match non-linked youtube URL in the wild. (Rev:20111012)
        https?://         # Required scheme. Either http or https.
        vimeo\.com/      # or vimeo.com followed by
        (\d+)             # a number of digits
        ~ix', array('self', 'vimeoCallback'), $text);
        return $text;
    }

    /**
     * make inline soundcloud media from urls
     * @param type $text
     * @return type
     */
    public static function linkifySoundcloud($text) {
        include_once "vendor/diversen/simple-php-classes/src/filter/soundcloud.php";
        $regex = '~https?://soundcloud\.com/[\-a-z0-9_]+/[\-a-z0-9_]+~ix';
        $text = preg_replace_callback($regex, array('self', 'soundcloudCallback'), $text);
        return $text;
    }

    /**
     * soundcloud callback
     * @param array $match
     * @return string
     */
    public static function soundcloudCallback($match) {
        $url = $match[0];

        //include_once "soundcloud.php";
        //$atts = 'soundcloud params="color=33e040&theme_color=80e4a0&iframe=true';
        $atts = array(
            'color' => '33e040',
            'theme_color' => '80e4a0',
            'iframe' => true
        );
        return soundcloud_shortcode($atts, $url);
    }
    
    /**
     * vimeo callback
     * @param array $matches
     * @return string $text
     */
    public static function vimeoCallback($matches) {

        $ratio = self::videoRatio(400);
        $width = 400;
        $height = 225;
        $width = ceil($ratio * $width);
        $height = ceil($ratio * $height);
        $embed_code = $matches[1];

        $str = <<<EOF
<div class="media_container">
<iframe 
    src="http://player.vimeo.com/video/$embed_code?title=0&amp;byline=0&amp;portrait=0"
    width="$width" 
    height="$height" 
    frameborder="0" 
    webkitAllowFullScreen mozallowfullscreen allowFullScreen>
</iframe>
</div>
EOF;
        return $str;
    }

    /**
     * make inline youtube videos from links
     * @param string $text
     * @return string $text
     */
    public static function linkifyYouTubeURLs2($text) {
        $text = preg_replace_callback('~
        # Match non-linked youtube URL in the wild. (Rev:20111012)
        https?://         # Required scheme. Either http or https.
        (?:[0-9A-Z-]+\.)? # Optional subdomain.
        (?:               # Group host alternatives.
          youtu\.be/      # Either youtu.be,
        | youtube\.com    # or youtube.com followed by
          \S*             # Allow anything up to VIDEO_ID,
          [^\w\-\s]       # but char before ID is non-ID char.
        )                 # End host alternatives.
        ([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
        (?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
        (?!               # Assert URL is not pre-linked.
          [?=&+%\w]*      # Allow URL (query) remainder.
          (?:             # Group pre-linked alternatives.
            [\'"][^<>]*>  # Either inside a start tag,
          | </a>          # or inside <a> element text contents.
          )               # End recognized pre-linked alts.
        )                 # End negative lookahead assertion.
        [?=&+%\w\-]*      # Consume any URL (query) remainder.
        ~ix', array('self', 'youtubeCallback'), $text);
        return $text;
    }

    /**
     * youtube callback
     * @param array $matches
     * @return string $text
     */
    public static function youtubeCallback($matches) {
        $embed_code = $matches[1];
        $ratio = self::videoRatio(420);

        $width = 420;
        $height = 315;
        $width = ceil($ratio * $width);
        $height = ceil($ratio * $height);


        $str = <<<EOF
<div class="media_container">
<iframe 
    width="$width" 
            height="$height"
    src="http://www.youtube.com/embed/$embed_code" 
    frameborder="0" 
    allowfullscreen>
</iframe>
</div>
EOF;
        return $str;
    }

}

