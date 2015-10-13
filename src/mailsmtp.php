<?php

namespace diversen;

use diversen\conf;
use PHPMailer;
use diversen\log;
/**
 * simple wrapper of PHPMailer. 
 * just loads most basic settings from config.php 
 * and returns a PHPMailer object
 * 
<code>

$mail = mailsmtp::getPHPMailer();
$mail->addAddress('dennis.iversen@gmail.com');
$mail->addAttachment('composer.json');
$mail->Subject = 'ÆØÅ test Here is a subject';
$mail->Body    = 'And now with danish signs! This is the HTML message body <b>in bold!</b>';
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}

</code>
 * 
 */
class mailsmtp {
    
    /**
     * 
     * @return PHPMailer
     */
    public static function getPHPMailer () {
        
        $mail = new PHPMailer;
        
        // only debug if debug flag is set
        if (conf::getMainIni('debug')) {
            $mail->SMTPDebug = conf::getMainIni('smtp_debug'); 
            
        }
        $mail->isSMTP();
        $mail->Host = conf::getMainIni('smtp_params_host');
        $mail->SMTPAuth = conf::getMainIni('smtp_params_auth');
        $mail->Username = conf::getMainIni('smtp_params_username'); // $config['Username'];
        $mail->Password = conf::getMainIni('smtp_params_password'); // $config['Password'];
        $mail->SMTPSecure = conf::getMainIni('smtp_secure'); // $config['SMTPSecure'];
        $mail->Port = conf::getMainIni('smtp_params_port'); 
        $mail->CharSet = 'UTF-8';
        $mail->From = conf::getMainIni('smtp_params_email'); // $config['From'];
        $mail->FromName = conf::getMainIni('smtp_params_name'); // $config['FromName'];
        
        if (conf::getMainIni('smtp_params_bounce')){
            $return_path = conf::getMainIni('smtp_params_bounce');
            $mail->addCustomHeader("Return-Path: <$return_path>");
        }
           
        return $mail;
    }
    
    
    /**
     * mail using smtp 
     * @param string $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @param array $attachments filenames
     * @return boolean
     */
    public static function mail ($to, $subject, $text = null, $html = null, $attachments = array()) {
        $mail = self::getPHPMailer();
        
        $mail->addAddress($to);
        $mail->Subject = $subject;

        if ($html) {
            $mail->isHTML(true);
            $mail->Body    = $html;
            
            if ($text) {
                $mail->AltBody = $text;
            }        
        } else {
            $mail->isHTML(false);
            $mail->Body  = $text;
        }
        
        foreach ($attachments as $val) {
            $mail->addAttachment($val);
        }
        
        if(!$mail->send()) {
            self::$log = $mail->ErrorInfo;
            log::error(self::$log); 
            return false;
        } else {
            return true;
        }
    }
    
    public static $log = null;
    
   /**
     * Send mail to main ini setting 'system_email'
     * @param string $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @param array $attachments filenames
     * @return boolean
     */
    public static function system ($subject, $text = null, $html = null, $attachments = array()) {
        $system_email = conf::getMainIni('system_email');
        return self::mail($system_email, $subject, $text, $html, $attachments);
    }
}

