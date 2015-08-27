<?php

namespace diversen\filter;
/**
 * markdownext filter. using Michelf markdown extra
 * @package    filters
 */

use \Michelf\MarkdownExtra;

/**
 * markdownext filter.
 *
 * @package    filters
 */
class markdownComments {
    


    /**
     *
     * @param array     array of elements to filter.
     * @return <type>
     */
    public static function filter($text){


        static $md;
        if (!$md){
            $md = new MarkdownExtra();
        }

        $md->no_entities = true;
        $md->no_markup = true;

        return $md->transform($text);

    }
}
