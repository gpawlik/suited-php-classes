<?php

namespace diversen\filter;
use diversen\conf as conf;
/**
 *
 * file contains method for rewriting language highlights in markdown
 * @package filters
 */

use diversen\strings\normalize as strings_normalize;
/**
 *
 * file contains method for rewriting language highlights in markdown
 * Normal highlight mode in a html document with CosCMS is 
 * <code>[hl:php] ... some php code ... [/hl:php]</code>
 * 
 * This filter will transform this into markdown highlighted code which is:
 * <code>
 * ```
 * ... some php code ...
 * ```
 * </code>
 * @package filters
 */
class mdHighlight {

    /**
     * @var string $language - the language to use.
     */
    private $lang;

    /**
     * Normal highlight mode in a html document with CosCMS is 
     * <code>[hl:php] ... some php code ... [/hl:php]</code>
     * 
     * This filter will transform this into markdown highlighted code which is:
     * <code>
     * ```
     * ... some php code ...
     * ```
     * </code>
     * @param string $article string to filter.
     * @return 
     */
    public function filter($article) {

        if (conf::getMainIni('filters_allow_files')) {
            $article = self::filterFile($article);
        }
        $article = self::filterInline($article);
        return $article;
    }

    /**
     * filter string for inline php
     * @param string $article
     * @return string $str
     */
    protected function filterInline($article) {
        // find all codes of type [hl:lang]
        $reg_ex = "{(\[hl:[a-z\]]+)}i";
        preg_match_all($reg_ex, $article, $match);
        $match = array_unique($match[1]);

        foreach ($match as $key => $val) {
            $ary = explode(":", $val);
            preg_match("{([a-z]+)}i", $ary[1], $lang);

            if (isset($lang[0]) && isset($lang[1])) {
                if ($lang[0] == $lang[1]) {
                    $article = $this->highlightCode($article, $lang[0]);
                }
            }
        }
        return $article;
    }

    /**
     * filter string for files
     * @param string $article
     * @return string $str
     */
    protected function filterFile($article) {
        // find all codes of type [hl:lang]
        $reg_ex = "{(\[hl_file:[a-z\]]+)}i";
        preg_match_all($reg_ex, $article, $match);
        $match = array_unique($match[1]);

        foreach ($match as $key => $val) {
            $ary = explode(":", $val);
            preg_match("{([a-z]+)}i", $ary[1], $lang);

            if (isset($lang[0]) && isset($lang[1])) {
                if ($lang[0] == $lang[1]) {
                    $article = $this->HighlightCodeFile($article, $lang[0]);
                }
            }
        }
        return $article;
    }

    /**
     *
     * @param string $str the string to perform highlighting on.
     * @param string $code the language (php, c++, etc.)
     * @return string the $highlighted string
     */
    protected function highlightCodeFile(&$str, $lang) {
        $this->lang = $lang;
        $ret = preg_replace_callback("{\[hl_file:$lang\]((.|\n)+?)\[/hl_file:$lang\]}i", array(get_class($this), 'replaceCodeFile'), $str);
        return $ret;
    }

    /**
     * highlight code
     * @param string $str the string to perform highlighting on.
     * @param string $code the language (php, c++, etc.)
     * @return string the $highlighted string
     */
    protected function highlightCode(&$str, $lang) {
        $this->lang = $lang;
        $ret = preg_replace_callback("{\[hl:$lang\]((.|\n)+?)\[/hl:$lang\]}i", array(get_class($this), 'replaceCode'), $str);
        return $ret;
    }

    /**
     * callback
     * @param array $replace string to highlight code from
     * @return string $str highlighted code.
     */
    protected function replaceCode(&$replace) {
        $str = trim($replace[1], "\n ");
        //$geshi = new GeSHi($str, $this->lang);        
        //return $geshi->parse_code();
        $lines = strings_normalize::newlinesToUnix($str);
        $ary = explode("\n", $lines);

        $newstr = '```' . $this->lang . "\n";
        foreach ($ary as $val) {
            $newstr.= "$val\n";
        }
        $newstr.= '```';
        return $newstr;
    }

    /**
     *
     * @param array $replace string to highlight code from
     * @return strng highlighted code.
     */
    protected function replaceCodeFile(&$replace) {

        $file = trim($replace[1]);
        if (!file_exists($file)) {
            return "File does not exists: $file";
        }
        $str = file_get_contents($file);

        $lines = strings_normalize::newlinesToUnix($str);
        $ary = explode("\n", $lines);

        $newstr = '```' . $this->lang . "\n";
        foreach ($ary as $val) {
            $newstr.= "$val\n";
        }
        $newstr.= '```';
        return $newstr;
    }

}

/**
 * extension of mdHighlight for autoloading purpose
 * @package filters
 */
class filters_mdHighlight extends mdHighlight {
    
}
