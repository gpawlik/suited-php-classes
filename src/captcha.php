<?php

namespace diversen;

use Securimage;
/**
 * File contains very simple captcha class
 *
 * @package    captcha
 */

/**
 * Class contains contains simple methods for doing captcha
 *
 * @package    captcha
 */
class captcha {


    /**
     * Method for checking if entered answer to captcha is correct
     */
    static public function checkCaptcha(){
        $securimage = new Securimage();
        if ($securimage->check($_POST['captcha_code']) == false) {
            return 0;
        } else {
            return 1;
        }
    }
}
