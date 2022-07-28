<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Logs an error or exception to database and get its ID.
 * @param string $detail Details of the error or exception.
 * @param int|null $errno Denotes the error's type. Pass a <b>null</b> for exceptions.
 * @return int|false Returns the ID of the table row, or <b>false</b> if failed.
 */
function db_log_error(string $detail, ?int $errno = null):int|false {
    if (!isset($_SESSION['log_slot'])) {
        $_SESSION['log_slot'] = [];
        /* The global log_slot is an array of arrays like this:
         * [
         *     0 => int    $timestamp_when_logged,
         *     1 => uint   $id_in_database,
         *     2 => string $stack_trace_hash
         * ];
         */
    }
    $log_slot = &$_SESSION['log_slot'];
    $hash = sha1(($errno ?? 'dogs?').str_replace([' ', "\r", "\n", "\t"], '', $detail));
    $now = time();
    $out = false;
    $item_merge = null;
    for($i = 0;$i <= count($log_slot) - 1;){
        $item = $log_slot[$i];
        if ($out){
            if (hash_equals($hash, $item[2])){
                $item_merge = $item;
                unset($log_slot[$i]);
                $log_slot = array_values($log_slot);
                $log_slot []= $item_merge;
                $item_merge[0] = $now;
                break;
            }
            $i++;
        }elseif ($item[0] + conf('log_slot_lifespan')>$now){
            $out = true;
        }else{
            array_shift($log_slot);
        }
    }
    $db = defdb();
    if ($db === false) return false;
    if ($item_merge === null) {
        $uid = $_SESSION['uid'] ?? 'NULL';
        $ip = getip();
        $errtype = $errno === null ? 'NULL' : $errno;
        $curr = dechex(time());
        $result = $db->query("INSERT INTO error_logs SET user_id={$uid}, remote_addr=0x{$ip}, error_type={$errtype}, ".
            "error_detail={$detail}, first_log_time=0x{$curr}");
        if ($result === false) return false;
        $result = $db->query('SELECT LAST_INSERT_ID()');
        if ($result === false) return false;
        $row = $result->fetch_row();
        if ($row === false) return false;
        $id = (int)($row[0]);
        $log_slot []= [$now, $id, $hash];
        if (count($log_slot) > conf('log_slot_max_count')) {
            array_shift($log_slot);
        }
        return $id;
    } else {
        $now = dechex($now);
        $result = $db->query("UPDATE error_logs SET logs_count=logs_count+1, last_log_time={$now} WHERE id={$item_merge[1]}");
        if ($result === false) return false;
        return $item_merge[1];
    }
}
