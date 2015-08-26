<?php

namespace diversen;

use Console_CommandLine;
use diversen\cli\common;
use Exception;

/**
 * Main shell script which parses all functions put in commands
 *
 * @package cli
 */

/**
 * class shell is a wrapper function around PEAR::commandLine
 *
 * @package cli
 */
class cli {

    /**
     * var holding commands
     * @var array $commands
     */
    static $commands = array();

    /**
     * var holding parser
     * @var object $parser
     */
    static $parser;

    /**
     * var holding command
     * @var string  $command
     */
    static $command;


    /**
     * constructor
     * static function for initing command parser
     * creates parser and sets version and description
     */
    public static function init() {

        self::$parser = new Console_CommandLine();
        self::$parser->description = <<<EOF
    Modulized Command line
EOF;
        self::$parser->version = '0.0.1';

        // Adding an main option for setting domain
        self::$parser->addOption(
            'verbose', array(
            'short_name' => '-v',
            'long_name' => '--verbose',
            'description' => 'Produce extra output',
            'action' => 'StoreTrue',
                )
        );
    }

    /**
     * method for setting a command
     *
     * @param string command
     * @param array options
     */
    public static function setCommand($command, $options) {
        if (isset($options['description'])) {
            $options['description'] = preg_replace('/\s+/', ' ', trim($options['description']));
        }
        self::$command = self::$parser->addCommand($command, $options);
    }

    /**
     * method for setting an option
     *
     * @param string    command
     * @param array     options
     */
    public static function setOption($command, $options) {
        self::$command->addOption($command, $options);
    }

    /**
     * method for setting an argument
     *
     * @param string argument
     * @param array  options
     */
    public static function setArgument($argument, $options) {
        self::$command->addArgument($argument, $options);
    }

    /**
     * method for running the commandline parser
     *                              
     * @return  int     0 on success any other int is failure
     */
    public static function run() {
        
        $result = self::parse();
        
        // Execute the result
        $ret = self::execute($result);
        
        // Exit with result from execution
        exit($ret);
    }

    /**
     * Execute the parser. 
     * @param array $result
     * @return int $ret the result of the command execution
     */
    public function execute($result) {
        $ret = 0;

        if (is_object($result) && isset($result->command_name)) {
            if (isset($result->command->options)) {
                foreach ($result->command->options as $key => $val) {
                    // command option if set run call back
                    if ($val == 1) {
                        if (function_exists($key)) {
                            $ret = $key($result->command->args);
                        } else {
                            common::abort("No such function $key");
                        }
                    } else {
                        $no_sub = 1;
                    }
                }
                return $ret;
            } else {
                $no_base = 1;
            }
        }
        if (isset($no_sub)) {
            common::echoMessage('No sub commands given use -h or --help for help');
        }
        if (isset($no_base)) {
            common::echoMessage('No base commands given use -h or --help for help');
        }
    }

    /**
     * Parse the options and return the result
     * @return array $result
     */
    public static function parse() {
        
        // Try to parse the commandline options
        // If it is a -h command, then the parser will exit here. 
        try {
            $result = self::$parser->parse();
        } catch (Exception $e) {
            common::abort($e->getMessage());
        }
        return $result;
    }
}
