<?php

namespace diversen;
use diversen\moduleloader;
use diversen\html;
use diversen\conf as conf;
use diversen\lang;
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
     * create the captcha. 
     * @param string $method
     * @return  string  $str the catcha to be used in forms
     */
    static public function createCaptcha($method = 'stringrandom'){
        if (moduleloader::moduleExists('image')){
            moduleloader::includeModule('image');
        }
        
        $method = conf::getModuleIni ('image_captcha_method');
        if ($method) {
            return self::$method ();
        } else {
            return self::simpleadd();
        }
    }

    /**
     * captcha method which creates a simple modification
     * @return  string  $str the catcha to be used in forms
     */
    static public function simpleadd(){
        
        if (!isset($_SESSION['ctries'])) {
            $_SESSION['ctries'] = 0;
        }
        
        if ($_SESSION['ctries'] == 3) {
            $_SESSION['ctries'] = 0;
        }
        
        $_SESSION['ctries']++;
        if (isset($_SESSION['cstr']) && $_SESSION['ctries'] != '3'){
            if (conf::getMainIni('captcha_image_module')) {
                return self::createCaptchaImage();
            }
            return "* " . lang::system('captcha_numbers') . $_SESSION['cstr'];
        }
        $num_1 = mt_rand  ( 20  , 40  );
        $num_2 = mt_rand  ( 20  , 40  );
        $str = "$num_1 + $num_2 = ?";
        $res = $num_1 + $num_2;
        $_SESSION['cstr'] = $str;
        $_SESSION['ckey'] = md5($res);
        
        if (conf::getMainIni('captcha_image_module')) {
            return self::createCaptchaImage();
        }
        return "* " . lang::system('captcha_numbers') . $str;
    }

    /**
     * very simple captcha function doing a multiplication
     * @return  string  the catcha to be used in forms
     */
    static public function stringrandom(){
        if (!isset($_SESSION['ctries'])) {
            $_SESSION['ctries'] = 0;
        }
        
        if ($_SESSION['ctries'] == 3) {
            $_SESSION['ctries'] = 0;
        }
        
        $_SESSION['ctries']++;
        if (isset($_SESSION['cstr']) && $_SESSION['ctries'] != '3'){
            if (conf::getMainIni('captcha_image_module')) {
                return self::createCaptchaImage();
            }
            return "* " . lang::system('captcha_numbers') . MENU_SUB_SEPARATOR_SEC . $_SESSION['cstr'];
        }
        
        $_SESSION['cstr'] = $str = self::genRandomString();
        $_SESSION['ckey'] = md5($str);
        
        if (conf::getMainIni('captcha_image_module')) {
            return self::createCaptchaImage();
        }
        return "* " . lang::system('captcha_numbers') . MENU_SUB_SEPARATOR_SEC  . $str;
    }

    /**
     * gets a random string of numbers to use with captcha
     * @return string $str
     */
    public static function genRandomString() {
        $length = conf::getMainIni('captcha_image_chars');
        if (!$length) { 
            $length = 4;
        }
        $characters = '0123456789';
        $string ='';    

        for ($p = 0; $p < $length; $p++) {
            $string.= $characters[mt_rand(0, strlen($characters)-1)];
        }

        return $string;
    }

    /**
     * Method for checking if entered answer to captcha is correct
     *
     * @param   int  checks if the entered int in a captcha form
     * @return  int 1 on success and 0 on failure.
     */
    static public function checkCaptcha($res){
        if (isset($_SESSION['ckey']) && md5($res) == $_SESSION['ckey']){
            return 1;
        } else {
            return 0;
        }
    }
    
    /**
     * create captcha element with captcha image
     * @return string $html string with captcha
     */
    static public function createCaptchaImage () {
        $options = array ('align' => 'top');
        $options['title'] = lang::translate('system_captcha_alt_image');
        $options['required'] = true;
        return "* " . lang::system('captcha_numbers') . ' ' . html::createImage('/image/captcha/index', $options);
    }
}
