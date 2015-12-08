<?php

namespace diversen\filter;

/**
 * Markdown filter that 
 * 1) add 'id' refrences to all media
 * 2) optional return of all media as a markdown doc.
 */

use diversen\file;
use diversen\lang;
use diversen\uri\direct;
use Michelf\Markdown as mark;
use modules\image\module as imageModule;



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
        
        
            if (!$this->isFigure($type, $url)) {
                return $str = "![$alt_text]($url)";
            }

            $id = uniqid();
            $str = "<div id =\"$id\">";
            $str.= '</div>';
            $str.= "![$alt_text]($url)";

            if ($type == 'mp4') {
                $m = ++self::$m;
                self::$media['movie'][] = "Movie $m [$alt_text](#$id).";
            } else  {
                $f = ++self::$f;
                self::$media['figure'][] = "Figur $f [$alt_text](#$id).";    
            }        
            return $str;
            
        } else {
            # If there's no such link ID, leave intact:
            $result = $whole_match;
        }

        return $result;
    }
    
    public static $media = array ();
    public static $f = 0; 
    public static $m = 0;

    protected function _doImages_inline_callback($matches) {
        
        $whole_match = $matches[1];
        $alt_text = $matches[2];
        $url = $matches[3] == '' ? $matches[4] : $matches[3];
        $title = & $matches[7];

        $alt_text = $this->encodeAttribute($alt_text);
        $url = $this->encodeAttribute($url);

        $type = $this->getType($url);
        
        
        if (!$this->isFigure($type, $url)) {
            return $str = "![$alt_text]($url)";
        }
        
        $id = uniqid();
        $str = "<div id =\"$id\">";
        $str.= '</div>';
        $str.= "![$alt_text]($url)";
        
        if ($type == 'mp4') {
            $m = ++self::$m;
            self::$media['movie'][] = "Movie $m [$alt_text](#$id).";
        } else  {
            $f = ++self::$f;
            self::$media['figure'][] = "Figur $f [$alt_text](#$id).";    
        }        
        return $str;
    }
    
    /**
     * Check to see if a image is a figure.
     * @param string $type (mp4 or image type)
     * @param string $url
     * @return false|string $res
     */
    public function isFigure ($type, $url) {
        
        // Movie is always a reference.
        if ($type == 'mp4') {
            return $url;
        }
        
        // Image. 
        if ($type != 'mp4') {
           
            // Check if image is a figure. 
            $a = parse_url($url);
            
            //Off-site image. Not a figure
            if (isset($a['scheme'])) {
                return false;
            }
            
            $mod = direct::fragment(0, $url);
            if ($mod == 'image') {
                $id = direct::fragment(2, $url);
                $i = new imageModule();
                $row = $i->getSingleFileInfo($id);
                if ($row['figure'] == 1) {
                    return $row;
                }    
            }
            return false;
        }
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
     * Get type of extension
     * @param type $url
     * @return type
     */
    protected function getType($url) {
        $type = file::getExtension($url);
        return strtolower($type);        
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

    