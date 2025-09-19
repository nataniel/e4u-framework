<?php
namespace E4u;

class Version
{
    /**
     * Current E4u Version
     */
    const string VERSION = '5.0.0-dev';

    /**
     * Compares an E4u version with the current one.
     * Returns -1 if older, 0 if it is the same, 1 if version
     *             passed as argument is newer.
     */
    public static function compare(string $version): bool|int
    {
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }
}