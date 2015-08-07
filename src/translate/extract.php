<?php

// class for extracting strings to be translated

namespace diversen\translate;

use diversen\conf;
use diversen\file;
use diversen\layout;
use diversen\moduleloader;
use diversen\translate;
use Symfony\Component\Filesystem\Filesystem;

class extract {

    /**
     * what will be the name of the array where translations are extracted
     * @var string $str default is _COS_LANG_MODULE
     */
    public static $arrayName = '_COS_LANG_MODULE';

    /**
     * method to look for in files we want to translate
     * @var type 
     */
    public static $extractMethodName = 'lang::translate';

    /**
     * extract all method with self::$extractMethodNamwe from a string
     * @param string $str
     * @return array $strings
     */
    public static function fromStr($str) {
        $method_name = self::$extractMethodName;

        // find all strings matching inside lang::translate call
        $search = "/$method_name\('([^']+)'/s";
        preg_match_all($search, $str, $out);
        $strings = $out[1];
        $strings = array_unique($strings);

        $search = '/' . $method_name . '\("([^"]+)"/s';
        preg_match_all($search, $str, $out2);
        $strings2 = $out2[1];
        $strings = array_merge($strings, $strings2);
        return $strings;
    }

    /**
     * $based on a $key and $value we set correct quote for given $key and $value
     * @return string
     */
    public static function setCorrectQuotes($key, $value = '') {

        $str = '';
        if (empty($value)) {
            $value = $key;
        }


        // search for apostrophe (') somewhere 
        // in order to know how to write out the array
        $apo_key = strpos($key, "'");
        if ($apo_key) {
            $str.= "\$" . self::$arrayName . '["' . $key . '"] = ';
        } else {
            $str.= "\$" . self::$arrayName . "['$key'] = ";
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

    public static function generate($options) {

        $lang_dir = self::getPath($options, 'lang_dir');
        $module_dir = self::getPath($options, 'module_dir');
        
        $strings_all = array();
        $strings_all[] = '';

        //$module_dir = self::getPath($options, 'module_dir');

        if (!file_exists($module_dir)) {
            cos_cli_print_status('Notice', 'y', "No such module dir. Skipping: $module_dir");
            return;
        }

        if (!file_exists($module_dir . "/lang")) {
            mkdir($module_dir . "/lang");
        }

        if (!file_exists($module_dir . "/lang/$options[language]")) {
            mkdir($module_dir . "/lang/$options[language]");
        }

        // get all files  from modules dir
        $file_list = file::getFileListRecursive($module_dir);

        // compose a php file
        $str = $sys_str = "<?php\n\n";

        foreach ($file_list as $val) {

            if (!extract::isText($val)) {
                continue;
            }

            $file_str = file_get_contents($val);
            $strings = self::fromStr($file_str);


            // no strings we continue
            if (empty($strings)) {
                continue;
            }

            if (strstr($val, 'menu.inc') || strstr($val, 'install.inc') || strstr($val, 'system_lang.inc')) {
                // system translation
                // we add the file info to translation as comment
                $file = str_replace(conf::pathBase(), '', $val);
                $sys_str.="// Translation of file $file\n\n";

                // and we add all strings in that file
                foreach ($strings as $trans) {
                    $sys_str.= self::setCorrectQuotes($trans);
                }
            } else {

                // we add the file info to translation as comment
                $file = str_replace(conf::pathBase(), '', $val);
                $str.="// Translation of file $file\n\n";

                // and we add all strings in that file
                foreach ($strings as $trans) {
                    // check if string already has been translated
                    if (array_search($trans, $strings_all)) {
                        continue;
                    }
                    $str.= self::setCorrectQuotes($trans);
                }
            }
            $values = array_values($strings);
            $strings_all = array_merge($strings_all, $values);
        }

        if (!file_exists($lang_dir)) {
            $res = mkdir($lang_dir);
            if ($res) {
                cos_cli_print("Dir: $lang_dir created\n");
            } else {
                cos_cli_abort("Dir could not be created: $lang_dir\n");
            }
        }

        // final: write the translation file
        $write_file = $lang_dir . "/language.inc";

        // Issue warning if language file already exists
        if (file_exists($write_file)) {
            if (!cos_confirm_readline("language files already exists.\nThese file will be over written")) {
                cos_cli_abort();
            }
        }

        file_put_contents($write_file, rtrim($str) . "\n");

        // final: write the translation file
        $write_sys_file = $lang_dir . "/system.inc";
        file_put_contents($write_sys_file, rtrim($sys_str) . "\n");
    }

    /**
     * 
     * @param array $options
     * @param string $get module_dir or lang_dir
     * @return string
     */
    public static function getPath ($options, $get = '') {
        if (isset($options['vendor'])) {
            $module_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src";
            $lang_dir = conf::pathBase() . "/vendor/diversen/simple-php-classes/src/lang/$options[language]";
        } elseif (isset($options['template'])) {
            $module_dir = conf::pathHtdocs() . '/templates/' . $options['module'];
            $lang_dir = conf::pathHtdocs() . "/templates/$options[module]/lang/$options[language]";
        } else {
            $module_dir = conf::pathModules() . "/$options[module]";
            $lang_dir = conf::pathModules() . "/$options[module]/lang/$options[language]";
        }
        
        if ($get == 'module_dir') {
            return $module_dir;
        }
        
        if ($get == 'lang_dir') {
            return $lang_dir;
        }
    }
    
    public function update($options) {
        $lang_dir = self::getPath($options, 'lang_dir');
        $module_dir = self::getPath($options, 'module_dir');

        if (!file_exists($module_dir)) {
            cos_cli_print_status('Notice', 'y', "No such module|template dir. Skipping: $module_dir");
            return;
        }

        $translate_dir = $module_dir . "/lang/$options[language]";
        $translate_file = $translate_dir . "/language.inc";
        $system_file = $translate_dir . "/system.inc";

        // just do start translation if file does not exists
        if (!file_exists($translate_file)) {
            translate($options);
            return;
        }

        include $translate_file;
        if (!isset($_COS_LANG_MODULE)) {
            $org_lang = $_COS_LANG_MODULE = array();
        } else {
            $org_lang = $_COS_LANG_MODULE;
        }

        if (file_exists($system_file)) {
            include $system_file;
            $org_lang = array_merge($_COS_LANG_MODULE, $org_lang);
        }


        // compose a php file
        $translation_str = $translation_sys_str = "<?php\n\n";

        // get all files  from modules dir
        $file_list = file::getFileListRecursive($module_dir);

        // arrays to use if we know if some strings are extracted
        $done_system = $done_lang = array();
        foreach ($file_list as $val) {

            if (!extract::isText($val)) {
                continue;
            }

            $file_str = file_get_contents($val);


            $strings = extract::fromStr($file_str);
            // no strings we continue
            if (empty($strings)) {
                continue;
            }
            // and we add all strings
            // all menus are added to system translation

            if (strstr($val, 'menu.inc') || strstr($val, 'install.inc') || strstr($val, 'system_lang.inc')) {

                $file = str_replace(conf::pathBase(), '', $val);
                $translation_sys_str.="\n// Translation of file $file\n\n";

                foreach ($strings as $trans) {
                    if (isset($done_system[$trans]))
                        continue;
                    $done_system[$trans] = 1;

                    // if string is not set in _COS_LANG_MODULE
                    if (!isset($org_lang[$trans])) {
                        $translation_sys_str.=extract::setCorrectQuotes($trans);
                    } else {
                        $new_str = $org_lang[$trans];
                        $translation_sys_str.= extract::setCorrectQuotes($trans, $new_str);
                    }
                }
            } else {

                $file = str_replace(conf::pathBase(), '', $val);
                $translation_str.="\n// Translation of file $file\n\n";

                foreach ($strings as $trans) {

                    if (isset($done_lang[$trans])){
                        continue;
                    }
                    $done_lang[$trans] = 1;

                    if (!isset($org_lang[$trans])) {
                        $translation_str.= extract::setCorrectQuotes($trans);
                    } else {
                        $new_str = $org_lang[$trans];
                        $translation_str.=extract::setCorrectQuotes($trans, $new_str);
                    }
                }
            }
        }

        $lang_dir = $module_dir . "/lang/$options[language]";
        if (!file_exists($lang_dir)) {
            $res = mkdir($lang_dir);
            if ($res) {
                cos_cli_print("Dir: $lang_dir created\n");
            } else {
                cos_cli_abort("Dir could not be created: $lang_dir\n");
            }
        }

        // final: write the translation file
        $write_file = $lang_dir . "/language.inc";
        file_put_contents($write_file, rtrim($translation_str) . "\n");

        // final: write the translation file
        $write_sys_file = $lang_dir . "/system.inc";
        file_put_contents($write_sys_file, rtrim($translation_sys_str) . "\n");
    }
    
    /**
     * check if prim mime type is text
     * @param string $filename
     * @return boolean $res true if text else false
     */
    public static function isText($file) {
        $prim_mime = file::getPrimMime($file);
        if ($prim_mime == 'text') {
            return true;
        }
        return false;
    }

    /**
     * @deprecated since 4.01
     * @param type $options
     * 
     * shell method used:
     self::setOption('translate_collect', array(
        'long_name' => '--collect',
        'description' => 'Collect all translations for a language into a single file into the enabled template. Specify template and language',
        'action' => 'StoreTrue'
    ));
     */
    public function collect($options) {
        $mods = moduleloader::getAllModules();
        $system = array();
        $language = array();

        // write out string
        $str = "<?php\n\n";
        foreach ($mods as $mod) {

            $mod_lang = conf::pathModules() . "/$mod[module_name]/lang/$options[language]";
            $system_file = $mod_lang . "/system.inc";
            $language_file = $mod_lang . "/language.inc";
            $_COS_LANG_MODULE = array();
            if (file_exists($system_file)) {

                $file = str_replace(conf::pathBase(), '', $system_file);
                $str.="\n// Translation of file $file\n\n";

                include $system_file;

                foreach ($_COS_LANG_MODULE as $key => $val) {
                    $str.=extract::setCorrectQuotes($key, $val);
                }
                $_COS_LANG_MODULE = array();
            }

            if (file_exists($language_file)) {

                $file = str_replace(conf::pathBase(), '', $language_file);
                $str.="\n// Translation of file $file\n\n";

                include $language_file;

                foreach ($_COS_LANG_MODULE as $key => $val) {
                    $str.=extract::setCorrectQuotes($key, $val);
                }
                $_COS_LANG_MODULE = array();
            }
        }

        $temps = layout::getAllTemplates();
        foreach ($temps as $temp) {
            $mod_lang = conf::pathHtdocs() . "/templates/$temp/lang/$options[language]";
            $system_file = $mod_lang . "/system.inc";
            $language_file = $mod_lang . "/language.inc";

            $_COS_LANG_MODULE = array();
            if (file_exists($system_file)) {

                $file = str_replace(conf::pathHtdocs(), '', $system_file);
                $str.="\n// Translation of file $file\n\n";

                include $system_file;
                foreach ($_COS_LANG_MODULE as $key => $val) {
                    $str.=extract::setCorrectQuotes($key, $val);
                }
                $_COS_LANG_MODULE = array();
            }

            if (file_exists($language_file)) {

                $file = str_replace(conf::pathHtdocs(), '', $language_file);
                $str.="\n// Translation of file $file\n\n";

                include $language_file;
                foreach ($_COS_LANG_MODULE as $key => $val) {
                    $str.=extract::setCorrectQuotes($key, $val);
                }
                $_COS_LANG_MODULE = array();
            }
        }

        $mod_lang = conf::pathHtdocs() . "/templates/$options[module]";
        if (!file_exists($mod_lang)) {
            cos_cli_abort("No such template: $options[module]");
        }

        // generate path if it does not exist
        $mod_lang.= "/lang/$options[language]";
        $fs = new Filesystem();
        $fs->mkdir($mod_lang, 0755);
        $lang_all = $mod_lang . "/language-all.inc";
        file_put_contents($lang_all, $str);
    }

}
