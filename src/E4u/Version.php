<?php
namespace E4u;

class Version
{
    /**
     * Current E4u Version
     */
    const VERSION = '4.2.0-dev';

    /**
     * Compares an E4u version with the current one.
     *
     * @param string $version E4u version to compare.
     * @return int Returns -1 if older, 0 if it is the same, 1 if version
     *             passed as argument is newer.
     */
    public static function compare($version)
    {
        $currentVersion = str_replace(' ', '', strtolower(self::VERSION));
        $version = str_replace(' ', '', $version);

        return version_compare($version, $currentVersion);
    }
}