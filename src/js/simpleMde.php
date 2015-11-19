<?php

namespace diversen\js;

use diversen\template\assets;

/**
 * Loads the simpleMde - simple markdown editor - based on codemirror
 * https://github.com/NextStepWebs/simplemde-markdown-editor
 */
class simpleMde {
    
    public static function load () {
        
        assets::setCss('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.css', null, array('head' => true));
        assets::setJs('//cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', null, array ('head' => true));
        
    }
    // A simple copy / paste example.
    /*
    public function testAction () {
        simpleMde::load();
        $str = <<<EOF
<textarea id="demo1"># Intro
Go ahead, play around with the editor! Be sure to check out **bold** and *italic* styling, or even [links](http://google.com). You can type the Markdown syntax, use the toolbar, or use shortcuts like `cmd-b` or `ctrl-b`.

## Lists
Unordered lists can be started using the toolbar or by typing `* `, `- `, or `+ `. Ordered lists can be started by typing `1. `.

#### Unordered
* Lists are a piece of cake
* They even auto continue as you type
* A double enter will end them
* Tabs and shift-tabs work too

#### Ordered
1. Numbered lists...
2. ...work too!

## What about images?
![Yes](http://i.imgur.com/sZlktY7.png)</textarea>    
<script>

new SimpleMDE({
		element: document.getElementById("demo1"),
		spellChecker: true,
	});
	
</script>

EOF;
        echo $str;
    }*/
    
}