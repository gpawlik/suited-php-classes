<?php

namespace diversen\filter;
/**
 * markdown filter. use Michelf markdown
 * @package    filters
 */
use diversen\uri\direct as direct;
use Michelf\Markdown as mark;
use diversen\conf as conf;
use diversen\file;
use diversen\log;
use diversen\http\headers;

/**
 * markdown filter.
 *
 * @package    filters
 */
class mdDownloadImages extends mark {

    /**
     * if set images will be downloaded to file system
     * @var $download 
     */
    public static $download = true;
    
    /**
     * 
     * If set we return raw markdown
     * @var type 
     */
    public static $getRaw = true;

    protected function _doImages_reference_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $link_id = strtolower($matches[3]);

        if ($link_id == "") {
            $link_id = strtolower($alt_text); # for shortcut links like ![this][].
        }

        $alt_text = $this->encodeAttribute($alt_text);
        if (isset($this->urls[$link_id])) {
            $url = $this->encodeAttribute($this->urls[$link_id]);
            
            if ($this->isImage($url)) {
                $this->saveImage($url);
            }
            
            return;
        } 
    }

    protected function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);

        if ($this->isImage($url)) {
            $this->saveImage($url);
        }
        // $url = $this->saveImage($url);
        return;

    }

    protected function doImages($text) {
        #
        # Turn Markdown image shortcuts into <img> tags.
        #
		#
		# First, handle reference-style labeled images: ![alt text][id]
        #
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				(' . $this->nested_brackets_re . ')		# alt text = $2
			  \]

			  [ ]?				# one optional space
			  (?:\n[ ]*)?		# one optional newline followed by spaces

			  \[
				(.*?)		# id = $3
			  \]

			)
			}xs', array(&$this, '_doImages_reference_callback'), $text);

        #
        # Next, handle inline images:  ![alt text](url "optional title")
        # Don't forget: encode * and _
        #
		$text = preg_replace_callback('{
			(				# wrap whole match in $1
			  !\[
				(' . $this->nested_brackets_re . ')		# alt text = $2
			  \]
			  \s?			# One optional whitespace character
			  \(			# literal paren
				[ \n]*
				(?:
					<(\S*)>	# src url = $3
				|
					(' . $this->nested_url_parenthesis_re . ')	# src url = $4
				)
				[ \n]*
				(			# $5
				  ([\'"])	# quote char = $6
				  (.*?)		# title = $7
				  \6		# matching quote
				  [ \n]*
				)?			# title is optional
			  \)
			)
			}xs', array(&$this, '_doImages_inline_callback'), $text);

        return $text;
    }
        /**
     * Checks broken media
     * @param type $url
     * @return boolean
     */
    protected function isImage($url) {
 
        $type = file::getExtension($url);
        if ($type == 'mp4') {    
            return false;
        }   
        return true;
    }
    
    protected function saveImage($url) {

        $id = direct::fragment(2, $url);
        $title = direct::fragment(3, $url);
        
        
        
        $path = "/images/$id/$title";
        $save_path = conf::getFullFilesPath($path);
        $web_path = conf::getWebFilesPath($path);
        $image_url = conf::getSchemeWithServerName() . $url;

        $code = headers::getReturnCode($image_url);
        if ($code != 200) {
            log::error("Could not get file content (image). Got: $code " . $image_url);
            return '';
        } else {
            $file = file_get_contents($image_url);
        }

        // make dir 
        $dir = dirname($path);
        file::mkdir($dir);
        file_put_contents($save_path, $file);
        return $web_path;
    }

    /**
     *
     * @param  string     string to markdown.
     * @return string
     */
    public static function filter($text) {

        static $md = null;
        if (!$md) {
            $md = new mdDownloadImages();
        }

        $md->no_entities = true;
        $md->no_markup = true;
        
        if (isset(self::$getRaw)) {
            return $md->doImages($text); 
        }

        $text = $md->transform($text);
        return $text;
    }
   
}
