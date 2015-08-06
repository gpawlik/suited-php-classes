<?php

// class for extracting strings to be translated

namespace diversen\translate;

class extract {
    
    public static function fromStr ($str) {
    
        // find all strings matching inside lang::translate call
        $search = "/lang::translate\('([^']+)'/s";
        preg_match_all($search, $str, $out);
        $strings = $out[1];
        $strings = array_unique($strings);

        $search = '/lang::translate\("([^"]+)"/s';
        preg_match_all($search, $str, $out2);
        $strings2 = $out2[1];
        $strings = array_merge($strings, $strings2);
        return $strings;
    }
}