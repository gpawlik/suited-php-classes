<?php

namespace diversen\filter;
/**
 * Markdown filter that downloads media, and make paths to absoulte file-system
 * paths. 
 */
use diversen\uri\direct;
use Michelf\Markdown as mark;
use diversen\conf;
use diversen\file;
use diversen\log;
use diversen\http\headers;

/**
 * markdown filter.
 *
 * @package    filters
 */
class mdMediaToAbsPath extends mark {

    

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
            $url = $this->saveMedia($url);
            if (!$url) {
                return '';
            } else {
                return "![$alt_text]($url)";
            }
        } else {
            # If there's no such link ID, leave intact:
            $result = $whole_match;
        }

        return $result;
    }

    protected function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);

        $url = $this->saveMedia($url);
        if (!$url) {
            return '';
        }
        //$url = conf::pathHtdocs() . "$url";
        return "![$alt_text]($url)";

    }

    protected function doMedia($text) {
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
     * Images are stored in database. 
     * @param type $url
     * @return boolean
     */
    public function saveImage ($url) {
        $id = direct::fragment(2, $url);
        $title = direct::fragment(3, $url);

        $title = rawurlencode($title);
        $path = "/images/$id/$title";
        $save_path = conf::getFullFilesPath($path);
        $web_path = conf::getSchemeWithServerName() . conf::getWebFilesPath($path);
        
        $code = headers::getReturnCode($web_path);
        
        if ($code != '200') {
            log::error("Could not get file content (image). Got: $code " . $web_path . ' in ' . __CLASS__);
            return false;
        } 
 
        return $save_path;
        
    }
    
    public function saveMp4($url) {
        $file = conf::pathFilesBase() . $url;
        if (!file_exists($file)) {
            return false;
        }
        return $file;
    }

    protected function saveMedia($url) {
 
        $type = file::getExtension($url);
        if ($type == 'mp4') {    
            return $this->saveMp4($url);
        } else {
            return $this->saveImage($url);
        }
    }

    /**
     *
     * @param  string     string to markdown.
     * @return string
     */
    public static function filter($text) {

        static $md = null;
        if (!$md) {
            $md = new mdMediaToAbsPath();
        }

        return $md->doMedia($text); 

    }
}
