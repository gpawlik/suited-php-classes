<?php

namespace diversen\html;

class common {

    /**
     * defines common layout constants for forms and
     * menu displays. 
     * 
     * Remeber that template is included before layout so you are
     * able to define these constants in your template. 
     */
    public static function defineConstants () {
        if (!defined('HTML_FORM_TEXT_SIZE')) {
            define('HTML_FORM_TEXT_SIZE', 30);
        }

        if (!defined('HTML_FORM_PASSWD_SIZE')) {
            define('HTML_FORM_PASSWD_SIZE', 8);
        }

        if (!defined('HTML_FORM_TEXTAREA_WT')) {
            define('HTML_FORM_TEXTAREA_WT', 60);
        }

        if (!defined('HTML_FORM_TEXTAREA_HT')) {
            define('HTML_FORM_TEXTAREA_HT', 16);
        }
        if (!defined('MENU_LIST_START')) {
            define('MENU_LIST_START', '<ul>');
        }

        if (!defined('MENU_LIST_END')) {
            define('MENU_LIST_END', '</ul>');
        }

        if (!defined('MENU_SUBLIST_START')) {
            define('MENU_SUBLIST_START', '<li>');
        }
        if (!defined('MENU_SUBLIST_END')) {
            define('MENU_SUBLIST_END', '</li>');
        }

        if (!defined('MENU_SUB_SEPARATOR')) {   
            define('MENU_SUB_SEPARATOR', ' | ');
        }

        if (!defined('MENU_SUB_SEPARATOR_SEC')) {
            define('MENU_SUB_SEPARATOR_SEC', ' :: ');
        }
    }
}
