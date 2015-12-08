<?php

namespace diversen\filter;

class cebeMarkdown {
    
    public function filter ($text) {
    
        $parser = new \cebe\markdown\GithubMarkdown();
        return $parser->parse($text);

    }
}