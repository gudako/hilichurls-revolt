<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

/**
 * A class that provides access to the data in the main config file.
 * The class is static and cannot be instantiated.
 */
final class Config
{
    // PRIVATE PROPERTIES

    private static array $config;

    // CONSTRUCTORS AND MAGIC METHODS

    /** The class is static. */
    private function __construct(){}

    /**
     * @throws RuntimeException When failed to read the config file.
     * @throws JsonException When failed to parse the config file content as json.
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function __constructStatic():void {
        $file_url = $_SERVER['DOCUMENT_ROOT'] . '/scripts/sys/conf/config.json';
        $file_content = file_get_contents($file_url);
        if ($file_content === false) {
            throw new RuntimeException("Failed to read file \"$file_url\"", 0, error_wrap_last());
        }
        self::$config = json_decode($file_content, true, 512, JSON_THROW_ON_ERROR);
    }

    // PUBLIC METHODS

    /**
     * Gets a config value from its key.
     * @param string $key A config key.
     * @return mixed Returns the config value, or <b>null</b> if undefined.
     */
    public static function get(string $key):mixed {
        if (!isset(self::$config[$key])) return null;
        return self::$config[$key];
    }
}
