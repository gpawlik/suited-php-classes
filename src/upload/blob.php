<?php

namespace diversen\upload;
use diversen\upload;

/**
 * class for doing a upload of a blob to db
 * @package     upload
 */
class blob extends upload {

    /**
     * gets file pointer
     * @param array $file
     * @param array $options
     * @return mixed $fp file pointer | true | false
     */
    public static function getFP($file, $options = array()){
        if (!empty($options)) {
            self::$options = $options;
        }

        if(isset($file['name'])) {
            
            // check native
            $res = self::checkUploadNative($file);
            if (!$res) { 
                return false;
            }
            
            // check mime
            if (isset(self::$options['allow_mime'])) {

                $res = self::checkAllowedMime($file);
                if (!$res) { 
                    return false;                
                }
            }

            // check maxsize. Note: Will overrule php ini settings
            if (isset(self::$options['maxsize'])) {               
                $res = self::checkMaxSize($file);
                if (!$res) { 
                    return false;
                }
            }

            $fp = fopen($file['tmp_name'], 'rb');
            return $fp;
        }
        // no files
        return true;
    }

    /**
     * gets a file pointer from a specified file
     * @param   string $filename
     * @param array $options
     * @return  mixed $res file pointer | true | false
     */
    public static function getFPFromFile($filename, $options = array()){

        if (isset($options)) { 
            self::$options = $options;
        }
        
        if (!file_exists($filename)) {
            self::$errors[] = 
            lang::translate('File does not exists')
            . ' : ' . $options['filename'];
            return false;
        }
        
        if (isset($options['maxsize'])) {
            $size = filesize($options['filename']);

            //  check the file is less than the maximum file size
            if($size > $options['maxsize'] ){
                $error = lang::translate('File is too large.');
                $error.= lang::translate('Max size is ') . 
                        upload::bytesToGreek($options['maxsize']);
                error_log($error);
                self::$errors[] = $error; 
                return false;
            }
        }

        // check for right content
        if (isset($options['allow_mime'])){
            $type = file::getMime($options['filename']);
            if (!in_array($type, $options['allow_mime'])) {
                self::$errors[] = lang::translate('This Content type is not allowed') .
                MENU_SUB_SEPARATOR_SEC . $type;
                return false;
                
            }
        }
        $fp = fopen($filename, 'rb');
        return $fp;
    }
}
