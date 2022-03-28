<?php
namespace E4u;

use Laminas\Config\Config;

abstract class Configuration
{
    /** @var Config */
    private static $_config;

    public static function isSSLRequired(): bool
    {
        return (bool)self::getConfigValue('ssl_required', false) ?: false;
    }

    public static function baseUrl(): string
    {
        $url = self::getConfigValue('base_url');
        return rtrim($url, '/');
    }

    public static function resourcesUrl(): string
    {
        $url = self::getConfigValue('resources_url', false) ?: self::getConfigValue('base_url');
        return rtrim($url, '/');
    }

    /**
     * @return mixed
     * @throws \E4u\Exception\ConfigException
     */
    protected static function getConfigValue(string $key, bool $exceptionIfEmpty = true)
    {
        $value = self::getConfig()->get($key);
        if (empty($value) && $exceptionIfEmpty) {
            throw new Exception\ConfigException(sprintf(
                'Missing "%s" key in application configuration.', $key));
        }

        return $value;
    }

    protected static function getConfig(): Config
    {
        if (null === self::$_config) {
            self::$_config = Loader::getConfig();
        }

        return self::$_config;
    }
}
