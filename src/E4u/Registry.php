<?php
namespace E4u;

class Registry
{
    private static array $registry = [];

    public static function get(string $key): mixed {
        if (isset(self::$registry[ $key ])) {
            return self::$registry[ $key ];
        }

        return null;
    }
    
    public static function set(string $key, mixed $value): void {
        self::$registry[ $key ] = $value;
    }
    
    public static function isRegistered(string $key): bool {
        return isset(self::$registry[ $key ]);
    }
}