<?php

use diversen\cli\common;



/**
 * readline
 * found on php.net 
 *
 * @param string ouput to print to screen
 * @return string the input which readline reads
 */
/*
function common::readSingleline($str){
    return common::readSingleline($str);
}*/

/**
 * a function for getting a confirm from command prompt
 * @param string $line a line to inform user what is going to happen
 * @param mixed $silence. If set we answer 'y' to all confirm readlines
 * @return int 1 on 'y' or 'Y' and 0 on anything else.
 */
/*
function common::readlineConfirm($line = null, $set_silence = null){
    return common::readlineConfirm($line, $set_silence);
}
*/
/**
 * command for aborting a script and printing info about abort
 * @param   string  $str string to be printed on abort
 * @return  int     $res  16
 */
/*
function common::abort($str = null){
    return common::abort($str);
}
*/
/**
 * function for executing commands with php command exec
 * @param   string  $command to execute
 * @param   array   $options defaults to:
 *                  array ('silence' => false);
 * @return  mixed   $ret the value returned by the shell script being
 *                  executed through exec()
 */
/*
function common::execCommand($command, $options = array(), $echo_output = 1){
    return common::execCommand($command, $options, $echo_output);
}*/

/**
 * calculate and gets correct length of a status message,
 * e.g. [OK] and [NOTICE]
 * @param string $status [OK]
 * @return string $status
 */
/*
function cos_get_color_status ($status) {
    return common::getColorStatus($status);
}
*/

/**
 * method for coloring output to command line
 * @param string $output
 * @param char $color_code (e.g. 'g', 'y', 'r')
 * @return string $colorered output
 */

/*
function color_output($output, $color_code = 'g') {
    return common::colorOutput($output, $color_code);
}
*/
/**
 * transform an array of output from exec into a single string
 * @param array $output
 * @return string $str
 */
/*
function cos_parse_shell_output ($output){
    return common::parseShellArray($output);
}
*/
/**
 * function for executing commands with php built-in command system
 * @param string $command to execute
 * @return int   $ret the value returned by the shell script being
 *                 executed through exec()
 */
/*
function common::systemExec($command){
    return common::systemExec($command);
}
*/
/**
 * simple function for printing a message
 * @param  string $mes the message to echo
 */
/*
function common::echoMessage($mes, $color = null){
    return common::echoMessage($mes, $color);
}
*/
/**
 * echos a colored status message
 * @param string $status (e.g. UPGRADE, NOTICE, ERROR)
 * @param char $color the color to print, e.g. 'y', 'r', 'g'
 * @param string $mes the long status message to be appended to e.g. 
 *               'module upgrade failed'
 * @return void
 */
/*
function common::echoStatus ($status, $color, $mes) {
    return common::echoStatus($status, $color, $mes);
}
*/


/**
 * examine if user is root. If not we die and echos a message
 * @param string $str
 * @return int 0 on success else a positive int. 
 */
/*
function common::needRoot ($str = '') {
    return common::needRoot($str);
}
*/