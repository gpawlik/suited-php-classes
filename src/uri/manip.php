<?php

namespace diversen\uri;

class manip {
    
    /**
     * Delete a QUERY part of a URL
     * @param string $url
     * @param string $key
     * @return string $url
     */
    
    public static function deleteQueryPart ($url, $key) {
        return preg_replace('/(?:&|(\?))'.$key.'=[^&]*(?(1)&|)?/i', "$1", $url);
    }
    
}