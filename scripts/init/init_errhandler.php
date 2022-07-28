<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Final handler of errors and exceptions.
 * @param string $detail The full detail of the error or exception.
 * @param int|null $errno <p>
 * For error, this argument should be the <a href='https://www.php.net/manual/en/errorfunc.constants.php'>error severity</a>;
 * For exception, this should be <b>null</b>.
 * </p>
 */
$handler = function(string $detail, ?int $errno = null):void {
    $db_log_id = db_log_error($detail,$errno);
    $lang = getlang();

    if ($db_log_id!==false)$query= 'log=' .str_pad(dechex($db_log_id),10,'0',STR_PAD_LEFT);
    else $query= 'msg=' .bin2hex(aes256_encrypt($detail, conf('log_encrypt_key')));
    $time_seed = (new DateTimeImmutable())->format('Y-m-d');
    $hash = sha1($query.$time_seed. 'You got that?');

    $http_prefix = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    header("Location: $http_prefix://{$_SERVER['HTTP_HOST']}/error?$query&auth=$hash&lang=$lang");
};

/**
 * The handler for exceptions.
 * @param Throwable $ex The exception to be handled.
 */
$ex_handler = function (Throwable $ex)use($handler){
    $trace = '';
    $write_trace = function (Throwable $ex)use(&$write_trace, &$trace){
        if ($ex->getPrevious()!==null) {
            $write_trace($ex->getPrevious());
        }
        $trace = "{$ex->getMessage()}\r\n".get_class($ex)." (code {$ex->getCode()}) ".
            "thrown in file \"{$ex->getFile()}\" on line {$ex->getLine()}\r\n".
            (count($ex->getTrace())===0? '' :"Stack trace:\r\n".get_stack_trace_v2($ex).
            ($ex->getPrevious()===null? '' :"↓ PREVIOUS EXCEPTION ↓\r\n").$trace);
    };
    $write_trace($ex);
    $handler($trace, -1);
};

/**
 * The handler for errors.
 * @param int $errno <p>
 * A code representing the <a href='https://www.php.net/manual/en/errorfunc.constants.php'>error's type</a>
 * (also aka 'severity')
 * </p>
 * @param string $errstr Message of the error.
 * @param string|null $errfile File that triggered the error.
 * @param int|null $errline Which line of the code triggered the error.
 * @param array|null $errcontext <i>deprecated</i>
 * @return false Returns <b>false</b>, indicating the default handler will not be called.
 */
$err_handler = function (int $errno, string $errstr, string $errfile = null, int $errline = null,
                     array $errcontext = null) use ($handler):bool {
    $trace = "$errstr\r\nError code $errno thrown".($errfile===null? '' :(" in file \"$errfile\"".
            ($errline===null? '' :" on line $errline")));
    $handler($trace, $errno);
    return false;
};

// Not setting any new handler for debug mode.
if (conf('mode_debug')) {
    error_reporting(E_ALL);
}
else{
    set_exception_handler($ex_handler);
    set_error_handler($err_handler);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
}
mysqli_report(MYSQLI_REPORT_OFF);
error_clear_last();
