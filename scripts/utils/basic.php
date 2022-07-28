<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Converts an IPv4 or IPv6 string to a 4-bytes or 16-bytes hex.
 * @param string $val An address to be converted.
 * @return string|false Returns a hex, or <b>false</b> of the input address is invalid.
 */
function ip2hex(string $val):string|false{
    $is_ipv6 = str_contains($val, ':');
    $payload = '';
    $parts = explode($is_ipv6 ? ':' : '.', $val);
    /** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
    if ($parts === false) return false;
    $cnt = count($parts);
    if ($is_ipv6 && $cnt !==8 || !$is_ipv6 && $cnt !== 4) return false;
    if ($is_ipv6){
        foreach ($parts as $part){
            if (preg_match('/^[\da-f]*?$/i',$part) !== 1) return false;
            $payload .= str_pad($part,4,'0',STR_PAD_LEFT);
        }
    }else{
        foreach ($parts as $part){
            if (preg_match('/^\d+?$/',$part) !== 1) return false;
            if ((int)$part>255) return false;
            $payload .= str_pad(dechex($part),2,'0',STR_PAD_LEFT);
        }
    }
    return strtolower($payload);
}

/**
 * Converts a 4-bytes or 16-bytes hex to an IPv4 or IPv6 string.
 * @param string $val A hex to be converted.
 * @return string|false Returns an address, or <b>false</b> of the input hex is invalid.
 */
function hex2ip(string $val):string|false{
    $cnt = strlen($val);
    $is_ipv6 = $cnt === 32;
    if ($cnt !==8 && !$is_ipv6)return false;
    if (preg_match('/^[\da-f]+?$/i',$val) !== 1) return false;
    $payload = [];
    if ($is_ipv6){
        $chunks = str_split($val, 4);
        foreach ($chunks as $chunk) {
            $payload []= ltrim(strtoupper($chunk),'0');
        }
    }else{
        $chunks = str_split($val, 2);
        foreach ($chunks as $chunk) {
            $payload []= hexdec($chunk);
        }
    }
    return strtolower(implode($is_ipv6 ? ':' : '.', $payload));
}

/**
 * Gets the IPv4 or IPv6 address.
 * @param bool $hex Whether to return the IP as hex.
 * @return string The IP address.
 * @codeCoverageIgnore Untestable, because running this without a server environment implies erratic behaviors.
 */
function getip(bool $hex=true):string{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $hex ? ip2hex($ip ?? '0.0.0.0') : $ip ?? '0.0.0.0';
}

/**
 * Sets a GET parameter to a URL link.
 * @param string $url A URL link.
 * @param string $name Parameter name to set.
 * @param mixed $value Parameter value.
 * @return string|false <p>
 * Returns the modified URL, or <b>false</b> on error.<br>
 * <i><b>NOTICE: </b>The function does not check for validity of input URL. Malformed URL are kept as it is. Make sure
 * they are in good format when calling this function.</i>
 * </p>
 */
function set_url_parameter(string $url, string $name, mixed $value): string|false{
    try {
        $val_str = match ($value) {
            true => 'true',
            false => 'false',
            default => (string)$value
        };
    } catch(Throwable $ex) {
        trigger_error($ex->getMessage(),E_USER_WARNING);
        return false;
    }
    if (preg_match('/^\w+$/i',$name) !== 1) {
        trigger_error('Invalid parameter name', E_USER_WARNING);
        return false;
    } elseif (preg_match('/^\w+$/i',$value) !== 1) {
        trigger_error('Invalid parameter value', E_USER_WARNING);
        return false;
    }
    $url_trim = trim($url);
    $split = explode('?',$url_trim);
    $cnt = count($split);
    if ($cnt === 1) {
        return "$url_trim?$name=$val_str";
    } else {
        $query = &$split[$cnt-1];
        if (str_ends_with($query, '&')) {
            $query = substr($query, 0, strlen($query) - 1);
            if (str_ends_with($query, '&')) {
                trigger_error('Do not make 2 or more "&" at the end of the link', E_USER_WARNING);
                return false;
            }
        }
        if (preg_match('/^(\w+=\w*&)*(\w+=\w*)?$/i',$query)!==1) {
            trigger_error('Malformed GET query parameters in input URL', E_USER_WARNING);
            return false;
        }
        $rep_cnt = null;
        $query = preg_replace('/('.preg_quote($name).'=)\w*(&|$)/i',"$1 $val_str $2",$query,1,$rep_cnt);
        $query = str_replace(' ', '', $query);
        if ($rep_cnt === 0) {
            $query .= "&$name=$val_str";
        }
        return implode('?', $split);
    }
}
