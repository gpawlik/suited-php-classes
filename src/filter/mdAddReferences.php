<?php

// <div id ="image">


namespace diversen\filter;
/**
 * Markdown filter that 
 * 1) add 'id' refrences to all media
 * 2) optional return of all media as a markdown doc.
 */
use diversen\uri\direct;
use Michelf\Markdown as mark;
use diversen\conf;
use diversen\file;
use diversen\log;

/**
 * markdown filter.
 *
 * @package    filters
 */
class mdAddReferences extends mark {

    

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
            $type = $this->getType($url);
            
            $id = uniqid();
            $str = "<div id =\"$id\">";
            $str.= "![$alt_text]($url)";
            $str.= '</div>';
            
            if ($type == 'mp4') {
                self::$media['movie'][] = lang::translate('Movie') . " [$alt_text](#$id).";   
            } else {
                self::$media['figure'][] = lang::translate('Figure') . " [$alt_text](#$id).";
                
            }
            //echo $str;
            return $str;
            
        } else {
            # If there's no such link ID, leave intact:
            $result = $whole_match;
        }

        return $result;
    }
    
    public static $media = array ();
    

    protected function _doImages_inline_callback($matches) {
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);

        $type = $this->getType($url);
        
        $id = uniqid();
        $str = "<div id =\"$id\">";
        $str.= "![$alt_text]($url)";
        $str.= '</div>';

        if ($type == 'mp4') {
            self::$media['movie'][] = "Movie [$alt_text](#$id).";
        } else {
            
            self::$media['figure'][] = "Figure [$alt_text](#$id).";
            
        }
        
        return $str;
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
    public function checkImage ($url) {
        $id = direct::fragment(2, $url);
        $title = direct::fragment(3, $url);

        $path = "/images/$id/$title";
        $save_path = conf::getFullFilesPath($path);
        $web_path = conf::getWebFilesPath($path);
        $image_url = conf::getSchemeWithServerName() . $url;

        $file = @file_get_contents($image_url);
        if ($file === false) {
            log::error('Could not get file content ' . $file);
            return false;
        }

        return $url;
        
    }
    
    public function checkMp4($url) {
        $file = conf::pathHtdocs() . $url;
        if (!file_exists($file)) {
            return false;
        }
        return $url;
    }

    protected function getType($url) {
 
        $type = file::getExtension($url);
        return strtolower($type);
        
        /*
        if ($type == 'mp4') {    
            return $this->checkMp4($url);
        } else {
            return $this->checkImage($url);
        } */
        
        
        
    }

    /**
     *
     * @param  string     string to markdown.
     * @return string
     */
    public static function filter($text) {

        static $md = null;
        if (!$md) {
            $md = new mdAddReferences();
        }

        return $md->doMedia($text); 

    }
}

    