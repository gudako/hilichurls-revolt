<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * A class that manages the database connections.
 * The class is static and cannot be instantiated.
 */
final class Database
{
    // PRIVATE PROPERTIES

    private static array $connections =[];

    // CONSTRUCTORS AND MAGIC METHODS

    /** The class is static. */
    private function __construct(){}

    // PUBLIC METHODS

    /**
     * Initializes a new named MySQL connection.
     * @param string $name <p>
     * A name to assign to the created connection, so you can use {@link get} to get the connection later on.
     * </p>
     * @param string|null $hostname <p>
     * [optional] Can be either a host name or an IP address. Passing the NULL value or the string "localhost" to this parameter,
     * the local host is assumed. When possible, pipes will be used instead of the TCP/IP protocol. Prepending host by p:
     * opens a persistent connection. mysqli_change_user() is automatically called on connections opened from the connection pool.
     * Defaults to ini_get("mysqli.default_host")
     * </p>
     * @param string|null $username [optional] The MySQL user name. Defaults to ini_get("mysqli.default_user")
     * @param string|null $password <p>
     * [optional] If not provided or NULL, the MySQL server will attempt to authenticate the user against those user records which
     * have no password only. This allows one username to be used with different permissions (depending on if a password as provided
     * or not). Defaults to ini_get("mysqli.default_pw")
     * </p>
     * @param string|null $database <p>
     * [optional] If provided will specify the default database to be used when performing queries. Defaults to ""
     * </p>
     * @return mysqli|false <p>
     * Returns a MySQL connection object on success, or <b>false</b> if any error occurred.
     * Use {@link error_get_last()} to get the error.
     * </p>
     * @see https://php.net/manual/en/mysqli.construct.php
     */
    public static function init(string $name, ?string $hostname = null, ?string $username = null, ?string $password = null,
                         ?string $database = null):mysqli|false {
        if (isset(self::$connections[$name])) {
            trigger_error("Database connection \"$name\" already occupied", E_USER_WARNING);
            return false;
        }
        $result = mysqli_connect($hostname, $username, $password, $database);
        if ($result !== false) {
            self::$connections[$name] = $result;
        }
        return $result;
    }

    /**
     * Gets a database connection with its name.
     * @param string $name A name of named database connection.
     * @return mysqli|false Returns a MySQL connection object on success, or <b>false</b> on error.
     */
    public static function get(string $name): mysqli|false {
        if (!isset(self::$connections[$name])) {
            trigger_error("Cannot find any database connection named \"$name\"",E_USER_WARNING);
            return false;
        }
        return self::$connections[$name];
    }

    /**
     * Closes and removes a database connection with its name.
     * @param string $name A name of named database connection.
     * @return bool Returns <b>true</b> on success and <b>false</b> if an error occurred.
     */
    public static function drop(string $name):bool {
        if (!isset(self::$connections[$name])) {
            trigger_error("Cannot find any database connection named \"$name\"",E_USER_WARNING);
            return false;
        }
        if (self::$connections[$name]->close() === false) {
            return false;
        }
        unset(self::$connections[$name]);
        self::$connections = array_values(self::$connections);
        return true;
    }
}
