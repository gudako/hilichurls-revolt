<?php

require_once $_SERVER['DOCUMENT_ROOT']. '/vendor/autoload.php';

/**
 * Get the current language's string abbr.
 * @return string The current language abbr such "en", "zh".
 */
function getlang():string{
    if (!isset($_COOKIE['lang'])) return 'en';
    $lang_cookie = $_COOKIE['lang'];
    $lang_set = SysConfig::GetAllLanguages();
    return in_array($lang_cookie, $lang_set) ? $lang_cookie : 'en';
}

/**
 * Get the exact readable text in the current language with a textcode, or directly from a memory part.
 * Pass in any arguments that matches one of below:
 * (1). A string representing the textcode;
 * (2). A int with the memory offset, another with the size;
 * (3). The two ints in (2) packed into an array.
 * @return string The readable text in the current language.
 */
function memtxt():string{
    $shmop = shmop_open(SysConfig::GetShmopIdLang(), 'a', 0600, SysConfig::GetShmopSizeLang());

    $args = func_get_args();
    if (count($args) == 1){
        if (gettype($args[0])==='array'){
            if (count($args[0])!==2||gettype($args[0][0])!== 'integer' ||gettype($args[0][1])!== 'integer')
                throw new TypeError("Function called with invalid array, must be [int \$offset, int \$size]");
            return memtxt($args[0][0], $args[0][1]);
        }
        if (gettype($args[0])!=='string'){
            throw new TypeError('Function called with unrecognized type');
        }
        return memtxt(\utils\HashtableParser::ParseByTextcode($shmop, $args[0]));
    }
    elseif (count($args) == 2){
        $offset = $args[0];
        $size = $args[1];
        if (gettype($offset) !== 'integer' || gettype($size) !== 'integer')
            throw new TypeError("Notice: \$offset and \$size they must be integers");

        $langItem = shmop_read($shmop, $offset, $size);
        $lang = getlang();
        $matches = array();
        if (preg_match("/(?<=<$lang>)(.|\r|\n)*(?=<\/$lang>)/m",$langItem,$matches)!==1 || !isset($matches[0]))
            throw new Error("The specified textcode does not have an definition for the language named \"$lang\".");
        return $matches[0];
    }
    else throw new ArgumentCountError('Argument count error.');
}
