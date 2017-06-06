<?php
namespace E4u;

class Registry
{
    private static $registry = [];

    public static function get($key) {
        if (isset(self::$registry[$key])) {
            return self::$registry[$key];
        }

        return null;
    }
    
    public static function set($key, $value) {
        self::$registry[$key] = $value;
    }
    
    public static function isRegistered($key) {
        return isset(self::$registry[$key]);
    }
}