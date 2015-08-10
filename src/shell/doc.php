<?php

use diversen\conf;
use diversen\cli\common;

/**
 * File containing documentation functions for shell mode
 *
 * @package     shell
 */


/**
 * wrapper function for creating documention with phpdoc
 * hi! I'am created with this function :)
 *
 * @return int  value from cos_system command
 */
function create_docs(){
    // check if command exists
    $command = "whereis phpdoc";
    $ret = common::execCommand($command);
    if ($ret){
        common::echoMessage("Could not find command phpdoc on your system");
        common::echoMessage("If the command phpdoc is not on your system we will not be able to create documentation.");
        common::echoMessage("One way to do this is to: pear install PhpDocumentor");
        exit(127);
    }

    $command = "phpdoc run ";
    $command.= "-d coslib ";
    $command.= "--template abstract -t " . conf::pathBase() . "/htdocs/phpdocs ";
    common::systemCommand($command);
}

// }}}

self::setCommand('doc', array(
    'description' => 'Command for creating documentation',
));

self::setOption('create_docs', array(
    'long_name'   => '--create-docs',
    'description' => 'Will make phpdoc documentation. Will be found in htdocs/phpdoc',
    'action'      => 'StoreTrue'
));

