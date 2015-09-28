<?php

namespace diversen;

use diversen\date;
use diversen\html;

/**
 * class for doing feeds
 */
class rss {

    public function feedAction($rows) {

        header("Content-Type: application/xml; utf-8");
        $feed = $this->getFeed($rows);
        echo $feed;
        die;
    }

    public static function getFeedLink($url, $name = null) {
  
        $link = '<div class="rss">';
        $link.= "<a href=\"$url\" " ;
        $link.= "target = \"_blank\">\n";
        if (!$name) {
            $link.="<i class=\"fa fa-rss fa-2x\"></i>";
        } else {
            $link.= $name;
        }
        
        $link.= "</a>";
        $link.= '</div>';
        return $link;
    }

    /**
     *
     * @return  string  feed string
     */
    public function getFeed($rows) {

        $str = '';
        $str.= $this->getStart();
        $str.= $this->getItems($rows);
        $str.= $this->getEnd();
        return $str;
    }

    public $options = array();
    
    /**
     * 
     * set channel info
     * @param array $ary = array ($title, $link, $description, $lang)
     */
    public function __construct($options) {
        $this->options = $options;
    }
    
   
    
    /**
     *
     * @return string $str start of feed
     */
    public function getStart() {
        $details = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
        $details.= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $details.= "<channel>\n";
        $details.= '<atom:link href="'.$this->options['link'].'" rel="self" type="application/rss+xml" />' . "\n";
        $details.= "<title>" . $this->options['title'] . "</title>\n";
        $details.= "<link>" . $this->options['link'] . "</link>\n";
        $details.= "<description>" . $this->options['description'] . "</description>";
        $details.= "<language>" . $this->options['lang'] . "</language>\n";
        return $details;
    }

    public function getEnd() {
        $end = '';
        $end.= "</channel>\n";
        $end.= "</rss>\n";
        return $end;
    }

    /**
     * 
     * get feed string
     * @return  string  items in the feed
     */
    public function getItems($rows) {
        $items = '';
        foreach ($rows as  $val) {
            $val = html::specialEncode($val);
            $items.= "<item>\n";
            $items.= "<title>$val[title]</title>\n";
            if (!isset($val['guid'])) {
                $items.= "<guid>$val[url]</guid>\n";
            } else {
                $items.= "<guid>$val[guid]</guid>\n";
            }
            
            $items.= "<link>$val[url]</link>\n";
            $items.= "<description>$val[abstract]</description>\n";
            $items.= "<pubDate>" . $this->timestampToPubdate($val['created']) . "</pubDate>\n";
            $items.= "</item>";
        }
        return $items;
    }

    public function timestampToPubdate($ts) {
        return date('D, d M Y H:i:s O', strtotime($ts));
    }
}
