<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Gets the current language.
 * @return string Returns the current language.
 */
function getlang():string {
    if (isset($_COOKIE['lang'])) {
        $is_lang = in_array($_COOKIE['lang'], conf('all_lang'), true);
        if ($is_lang) return $_COOKIE['lang'];
        else trigger_error("Unknown language {$_COOKIE['lang']} defined as in use", E_USER_WARNING);
    }
    setcookie('lang', conf('all_lang')[0], time() + conf('lang_cookie_lifespan'));
    return conf('all_lang')[0];
}

/**
 * Sets the current language.
 * @param string $lang Language to set.
 * @return bool <p>
 * Returns <b>true</b> on success; Returns <b>false</b> if the input language is invalid (no error will be triggered),
 * or if an error occurred.
 * </p>
 */
function setlang(string $lang):bool {
    if (!in_array($lang,conf('all_lang'), true)) {
        error_clear_last();
        return false;
    }
    return setcookie('lang',$lang,time()+conf('lang_cookie_lifespan'));
}

/**
 * @param ...$args
 * @return string|false|null
 */
function memtxt(...$args):string|null|false {
    // todo
}

