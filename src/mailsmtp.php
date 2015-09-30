<?php

namespace diversen;
use diversen\conf;

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
     * returns a PHPHMailer object with settings from config.php
     * @return \PHPMailer
     */
    /*
    public static function getPHPMailer () {
        $config = conf::get('smtp');

        $mail = new \PHPMailer;
        $mail->SMTPDebug = $config['SMTPDebug'];
        $mail->isSMTP();
        $mail->Host = $config['Host'];
        $mail->SMTPAuth = $config['SMTPAuth'];
        $mail->Username = $config['Username'];
        $mail->Password = $config['Password'];
        $mail->SMTPSecure = $config['SMTPSecure'];
        $mail->Port = $config['Port'];
        $mail->CharSet = $config['CharSet'];
        $mail->From = $config['From'];
        $mail->FromName = $config['FromName'];
        $mail->isHTML(true);    
        return $mail;
    }*/
    
    public static function getPHPMailer () {
        
        $mail = new \PHPMailer;
        $mail->SMTPDebug = conf::getMainIni('smtp_debug'); 
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
          
        $ary = func_get_args();
        //print_r($ary); die;
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
            log::error($mail->ErrorInfo);
            return false;
        } else {
            return true;
        }
    }
    
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

