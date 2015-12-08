<?php

namespace diversen\http;

/**
 * @package http
 * 
 */

/**
 * class for doing http headers
 * @package http
 */
class headers {

    
    /**
     * parse curl headers string and return array
     * @param string $response
     * @return array $headers
     */
    public static function parseCurlHeaders($response) {

        $headers = array();
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * get url headers as array
     * @param string $url
     * @return array $headers
     */
    public static function getCurlHeadersAry($url) {
        $cookie_file = sys_get_temp_dir() . '/curl_headers_cookie.txt';
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILETIME, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEFILE,$cookie_file);
        $headers = curl_exec($curl);
        return self::parseCurlHeaders($headers);
    }

    public static function getCurlLastLocation($url, $max = 7) {
        static $i = 0;

        $headers = self::getCurlHeadersAry($url);
        if (isset($headers['Location'])) {
            $i++;
            if ($i > $max) {
                return false;
            } else {
                return self::getCurlLastLocation($headers['Location'], $max);
            }
        }
        return $url;
    }

    /**
     * http://stackoverflow.com/questions/2280394/how-can-i-check-if-a-url-exists-via-php
     * return a urls return code
     * @param string $url
     * @return int $int e.g. 200 or 301
     */
    public static function getReturnCode($url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpcode;
    }

}
