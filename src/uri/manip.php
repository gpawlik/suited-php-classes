<?php

namespace diversen\uri;

class manip {
    
    /**
     * Delete a QUERY part of a URL path - do not include host and scheme 
     * @param string $url
     * @param string $key
     * @return string $url
     */
    
    public static function deleteQueryPart ($url, $key) {
        
        $ret = '';
        $ary = parse_url ($url);
        if (isset($ary['path'])) {
            $ret.= $ary['path'];
        }
        
        if (isset($ary['query'])) {
            parse_str($ary['query'], $query);
            foreach ($query as $k => $val) {
                if ($k == $key) {
                    unset($query[$key]);
                }
            }
            $ret.= "?" . http_build_query($query);
        }
        return $ret;
    }
    
    /**
     * Get query as array from string
     * @param string $url
     * @return array $ary
     */
    public static function getAryQueryFromStr ($url) {
        return parse_url ($url);
    }
    
    /**
     * Get query string from array
     * @param array $ary
     * @return string $query
     */
    public static function getStrQueryFromAry ($ary) {
        return http_build_query($ary);
    }
}
