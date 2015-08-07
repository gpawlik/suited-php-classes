<?php

namespace diversen\translate;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use diversen\file;


class extractor {
    
    var $dirs = [];
    /** file where translation will be placed */
    var $translateFile = 'language.php';
    /** translation dir where translation files will be placed */
    var $translateDir = 'lang';
    /** name of var inside translateFile LANG */
    var $translateAryName = 'LANG';
    /** pattern to search for */
    var $extractMethodName = 'lang::translate';
    /** default language. This is default language we extract from */
    var $defaultLanguage ='en';
    
    /**
     * attach all dirs inside a dir to $this->dirs
     * e.g. modules/* will set the following dirs
     * /modules/account
     * /modules/blog
     * @param string $path modules/*
     */
    public function setDirsInsideDir ($path) {
        $dirs = file::getDirsGlob($path);
        $this->addDirs($dirs);
    }
    
    /**
     * add some dirs
     * @param array $dirs
     */
    private function addDirs ($dirs) { 
        foreach($dirs as $dir) {
            $this->dirs[] = $dir;
        }
    }
    
    /**
     * set a single path for e.g. a vendor lib
     * @param string $path
     */
    public function setSingleDir ($path) {
        $this->dirs[] = $path;
    }
    
    /**
     * search for strings which we should extract
     * @param type $str
     * @return type
     */
    public function search ($str = '') {
        $method_name = $this->extractMethodName;

        // find all strings matching inside $this->extractMethodName
        // by default we look for lang::translate calls
        
        // search for strings inside ''
        $search = "/$method_name\('([^']+)'/s";
        preg_match_all($search, $str, $out);
        $strings = $out[1];
        $strings = array_unique($strings);

        // search for strings inside ""
        $search = '/' . $method_name . '\("([^"]+)"/s';
        preg_match_all($search, $str, $out2);
        $strings2 = $out2[1];
        $strings = array_merge($strings, $strings2);
        return $strings;
    }
    

    /**
     * get a line of the translation file with correct quotes
     * e.g $LANG["This is many single quots'"] = "This '"
     * @param string $key
     * @param string $value
     * @return string $str
     */
    private function setCorrectQuotes($key, $value = '') {

        $str = '';
        if (empty($value)) {
            $value = $key;
        }


        // search for apostrophe (') somewhere 
        // in order to know how to write out the array
        $apo_key = strpos($key, "'");
        if ($apo_key) {
            $str.= "\$" . $this->translateAryName . '["' . $key . '"] = ';
        } else {
            $str.= "\$" . $this->translateAryName . "['$key'] = ";
        }

        // and also for the value
        $apo_val = strpos($value, "'");
        if ($apo_val) {
            $str.= '"' . $value . '";' . "\n";
        } else {
            $str.= "'$value';" . "\n";
        }
        return $str;
    }
    
    /**
     * check if prim mime type is text
     * @param string $file
     * @return boolean $res true if text else false
     */
    private function isText($file) {
        $prim_mime = file::getPrimMime($file);
        if ($prim_mime == 'text') {
            return true;
        }
        return false;
    }
    
    /**
     * generate a language file in a language dir
     * will overwrite previous translations
     * e.g.:
     * modules/account/lang/en/language.php
     * 
     * @param type $lang
     */
    public function generateLang () {
        foreach ($this->dirs as $dir) {
            $str = $this->generateLangStrForPath($dir);
            $this->createFile($dir, $str);
        }
    }
    
    /**
     * get language dir name to be used in source dir
     * @param string $dir
     * @return string $dir
     */
    public function getLanguageDirFromDir ($dir) {
        return 
                $dir . '/' . 
                $this->translateDir . '/' . 
                $this->defaultLanguage;
    }
    
    /**
     * get language file name from dir
     * @param string $dir
     * @return string $file
     */
    public function getLanguageFileFromDir($dir) {
        return
                $this->getLanguageDirFromDir($dir) . '/' .
                $this->translateFile;
    }
    
    /**
     * create translation file
     * @param string $dir
     * @param string $str translation string
     */
    public function createFile ($dir, $str) {
        
        $save_dir = $this->getLanguageDirFromDir($dir);
        if (!file_exists($save_dir)) {
            $res = mkdir($save_dir, 0755, true);
            if (!$res) {
                echo "Could not make dir $dir with permission 0755";
            }
        }
        $file = $this->getLanguageFileFromDir($dir);
        file_put_contents($file, $str);
  
    }
    
    /**
     * generates a PHP string with extracted translation
     * @param string $dir
     * @return string $translation_str
     */
    public function generateLangStrForPath ($dir) {
        $file_list = file::getFileListRecursive($dir);

        // compose a php file
        $translation_str = "<?php\n\n";
        
        foreach ($file_list as $file) {
            if (!$this->isText($file)) {
                continue;
            }
            
            $file_str = file_get_contents($file);
            $strings = $this->search($file_str);
            
            // no strings we continue
            if (empty($strings)) {
                continue;
            }
            
            $translation_str.="// Translation of file $file\n\n";

            // and we add all strings in that file
            foreach ($strings as $trans) {
                $translation_str.= $this->setCorrectQuotes($trans);
            }
        }
        
        return $translation_str;
    }
}






