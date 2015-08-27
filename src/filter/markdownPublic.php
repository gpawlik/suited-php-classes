<?php

namespace diversen\filter;
/**
 * MarkdownExt filter.
 * Uses MarkdownExtra. 
 * Gives images the class media_image
 * Disallows entities and markup
 * @package    filter
 */


/**
 * MarkdownExt filter.
 * Uses MarkdownExtra. 
 * Gives images the class media_image
 * Disallows entities and markup
 * @package    filter
 */
class markdownPublic extends \diversen\filter\markdownExt {
    


    /**
     *
     * @param string $text string to filter
     * @return string $text
     */
    public static function filter($text){
        
        static $md = null;
        if (!$md){
            $md = new self();
        }

        $md->no_entities = true;
        $md->no_markup = true;

        return $md->transform($text);

    }
}
