<?php
namespace E4u;

use Zend\Config\Config;

abstract class Configuration
{
    /** @var Config */
    private static $_config;

    /**
     * @return bool
     */
    public static function isSSLRequired()
    {
        return (bool)self::getConfigValue('ssl_required', false);
    }

    /**
     * @return string http://myhost.com/my/path
     */
    public static function baseUrl()
    {
        $url = self::getConfigValue('base_url');
        return rtrim($url, '/');
    }

    /**
     * @return string http://myhost.com/my/path
     */
    public static function resourcesUrl()
    {
        $url = self::getConfigValue('resources_url', false) ?: self::getConfigValue('base_url');
        return rtrim($url, '/');
    }

    /**
     * @param  string $key
     * @return mixed
     * @throws \E4u\Exception\ConfigException
     */
    protected static function getConfigValue($key, $exceptionIfEmpty = true)
    {
        $value = self::getConfig()->get($key);
        if (empty($value) && $exceptionIfEmpty) {
            throw new Exception\ConfigException(sprintf(
                'Missing "%s" key in application configuration.', $key));
        }

        return $value;
    }

    /**
     * @return Config
     */
    protected static function getConfig()
    {
        if (null === self::$_config) {
            self::$_config = Loader::getConfig();
        }

        return self::$_config;
    }
}