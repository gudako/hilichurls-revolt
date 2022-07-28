<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * Gets the default database.
 * @return mysqli|false Returns the connection on success and otherwise <b>false</b>.
 */
function defdb():mysqli|false {
    $conf_name = conf('db_root_name');
    if ($conf_name === null) return false;
    return Database::get($conf_name);
}

/**
 * Gets a config value from its key.
 * @param string $key A config key.
 * @return mixed Returns the config value, or <b>null</b> if undefined. <b>E_USER_WARNING</b> is triggered if undefined.
 */
function conf(string $key):mixed {
    $result = Config::get($key);
    if ($result === null) {
        trigger_error("Failed to find config key \"$key\"", E_USER_WARNING);
        return null;
    } else {
        return $result;
    }
}
