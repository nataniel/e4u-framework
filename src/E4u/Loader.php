<?php
/**
 * Standard application loader for the E4u Application.
 *
 * The directory tree must follow the convention:
 * application/
 *     config/
 *         enviroment/
 *             development.php
 *             production.php
 *         locale/
 *             en.php
 *             pl.php
 *         application.php
 *         doctrine.php
 *         routes.php
 *     src/
 *         Controller/
 *         Form/
 *         Helper/
 *         Model/
 *         Proxies/
 *         View/
 *         Application.php
 *     views/
 *         layout/
 *         ...
 * library/
 *     Doctrine/
 *     E4u/
 *     Symfony/
 *     Zend/
 * public/
 * tools/
 * vendor/
 * .environment
 *
 * Current working dir MUST be set to the root directory - usually
 * as the first line of public/index.php:
 * <code>
 * chdir(dirname(__DIR__));
 * </code>
 */

namespace E4u;

use Zend\Config\Config,
    Zend\Loader\StandardAutoloader,
    Doctrine\ORM\Proxy,
    Doctrine\ORM\EntityManager;

#mb_internal_encoding('utf-8');

class Loader
{
    const
        DEFAULT_ENVIRONMENT      = 'development',
        DEFAULT_MIGRATIONS_TABLE = 'doctrine_migrations',
        DEFAULT_MIGRATIONS_DIR   = 'application/src/Migrations',
        DEFAULT_PROXY_DIR        = 'application/src/Proxies',
        DEFAULT_MODELS_DIR       = 'application/src/Model',
        DEFAULT_CACHE_CLASS      = 'Doctrine\Common\Cache\ArrayCache',
        DEFAULT_CACHE_NAMESPACE  = 'E4u';

    /**
     * Bootstrap E4u Application
     * There's no autoloader available at this point yet
     *
     * @param  string $namespace
     * @param  string $environment
     * @return \E4u\Application
     */
    public static function get($namespace = 'My', $environment = null)
    {
        // load configuration
        $config = class_exists(Registry::class)
               && Registry::isRegistered('application/config')
                ? Registry::get('application/config')
                : self::configureApplication($namespace, $environment);
        $config->setReadOnly();

        $appClass = "$namespace\\Application";
        $app = new $appClass($config);

        Registry::set('application/instance', $app);
        return $app;
    }

    /**
     * @return \E4u\Application
     */
    public static function getApplication()
    {
        return Registry::get('application/instance');
    }

    /**
     * @return Config
     */
    public static function getConfig()
    {
        if (Registry::isRegistered('application/instance')) {
            return Registry::get('application/instance')->getConfig();
        }

        if (Registry::isRegistered('application/config')) {
            return Registry::get('application/config');
        }

        return new Config([]);
    }

    /**
     * @return string
     */
    public static function getEnvironment()
    {
        return self::getConfig()->get('environment');
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public static function getConnection()
    {
        return self::getDoctrine()->getConnection();
    }

    /**
     * @return \Zend\I18n\Translator\Translator
     */
    public static function getTranslator()
    {
        if (Registry::isRegistered('application/translator')) {
            return Registry::get('application/translator');
        }

        $config = self::getConfig();
        $translator = new \Zend\I18n\Translator\Translator();
        $translator->setFallbackLocale($config->get('default_locale'));

        if (!empty($config->translator)) {

            $files_path = $config->translator->get('files_path', 'application/config/locale');

        }
        else {

            $files_path = 'application/config/locale';

        }

        $translator->addTranslationFilePattern(\E4u\I18n\Translator\ArrayLoader::class, $files_path, '%s.php');
        Registry::set('application/translator', $translator);
        return $translator;
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public static function getMailer()
    {
        if (Registry::isRegistered('application/mailer')) {
            return Registry::get('application/mailer');
        }

        $config = self::getConfig();
        if (!empty($config->mailer)) {
            $type = $config->mailer->get('type', 'sendmail');
            $options = $config->mailer->get('options');
            if ($options instanceof Config) {
                $options = $options->toArray();
            }
        }
        else {
            $type = 'sendmail';
            $options = null;
        }

        $mailer = Mailer\Factory::get($type, $options);
        Registry::set('application/mailer', $mailer);
        return $mailer;
    }

    /**
     * @param Config $config
     * @return \Doctrine\ORM\Configuration
     */
    protected static function configureDoctrine(Config $config)
    {
        if (empty($config->doctrine)) {
            throw new \E4u\Exception\ConfigException('Config passed to E4u\Loader::getDoctrine() must have "doctrine" key set');
        }

        $ormConfig = new \Doctrine\ORM\Configuration();

        $ormConfig->setProxyDir($config->doctrine->get('proxy_dir', self::DEFAULT_PROXY_DIR));
        $ormConfig->setProxyNamespace($config->doctrine->get('proxy_namespace', $config->namespace . '\Proxies'));
        $ormConfig->setAutoGenerateProxyClasses($config->doctrine->get('auto_generate_proxies', true));

        if ($entities_xml = $config->doctrine->get('entities_xml')) {
            if ($entities_xml instanceof Config) { $entities_xml = $entities_xml->toArray(); }
            $driverImpl = new \Doctrine\ORM\Mapping\Driver\XmlDriver($entities_xml);
        }
        else {
            $entities_dir = $config->doctrine->get('entities_dir', [ self::DEFAULT_MODELS_DIR ]);
            if ($entities_dir instanceof Config) { $entities_dir = $entities_dir->toArray(); }
            $driverImpl = $ormConfig->newDefaultAnnotationDriver($entities_dir);
        }

        if ($sqlLogger = $config->doctrine->get('sql_logger')) {
            $ormConfig->setSQLLogger(new $sqlLogger());
        }

        $cacheClass = $config->doctrine->get('cache_class', self::DEFAULT_CACHE_CLASS);
        $cacheNamespace = $config->doctrine->get('cache_namespace', self::DEFAULT_CACHE_NAMESPACE);

        $cache = new $cacheClass();
        if ($cache instanceof \Doctrine\Common\Cache\CacheProvider) {
            $cache->setNamespace($cacheNamespace);
        }

        $ormConfig->setMetadataDriverImpl($driverImpl);
        $ormConfig->setMetadataCacheImpl($cache);
        $ormConfig->setQueryCacheImpl($cache);
        return $ormConfig;
    }

    /**
     * @return \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    public static function configureMigrations(Config $config = null)
    {
        if (is_null($config)) {
            $config = self::getConfig();
        }

        if (empty($config->doctrine)) {
            throw new \E4u\Exception\ConfigException('Config passed to E4u\Loader::getMigrations() must have "doctrine" key set');
        }

        $migrationsConfig = new \Doctrine\DBAL\Migrations\Configuration\Configuration(self::getConnection());

        $namespace = $config->doctrine->get('migrations_namespace', $config->namespace . '\Migrations');
        $migrationsConfig->setMigrationsNamespace($namespace);

        $tableName = $config->doctrine->get('migrations_table', self::DEFAULT_MIGRATIONS_TABLE);
        $migrationsConfig->setMigrationsTableName($tableName);

        $directory = $config->doctrine->get('migrations_dir', self::DEFAULT_MIGRATIONS_DIR);
        $migrationsConfig->setMigrationsDirectory($directory);
        $migrationsConfig->registerMigrationsFromDirectory($directory);

        return $migrationsConfig;
    }

    /**
     * @return EntityManager
     */
    public static function getDoctrine()
    {
        if (Registry::isRegistered('doctrine/em')) {
            return Registry::get('doctrine/em');
        }

        $config = self::getConfig();
        if (empty($config->database)) {
            throw new \E4u\Exception\ConfigException('Config passed to E4u\Loader::getDoctrine() must have a "database" key set');
        }

        $ormConfig = self::configureDoctrine($config);
        $events = new \Doctrine\Common\EventManager;
        $em = EntityManager::create($config->database->toArray(), $ormConfig, $events);

        Registry::set('doctrine/em', $em);
        return $em;
    }

    /**
     *
     * @param  string $file
     * @param  string $path
     * @return array
     */
    public static function load($file, $path = 'application/config')
    {
        if (is_file("$path/$file.php")) {
            return include("$path/$file.php");
        }

        return [];
    }

    /**
     * @return string
     */
    protected static function discoverEnvironment()
    {
        $environment = self::DEFAULT_ENVIRONMENT;
        if (is_file('.environment')) {
            $environment = trim(file_get_contents('.environment', null, null, 0, 30));
        }

        if (isset($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $key => $arg) {
                if (preg_match('/^--env(ironment)?=([^\s]+)$/', $arg, $regs)) {
                    unset($_SERVER['argv'][$key]);
                    return $regs[2];
                }

                if ($arg == '--test') {
                    unset($_SERVER['argv'][$key]);
                    return 'test';
                }
            }
        }

        return $environment;
    }

    /**
     * @param  string $environment
     * @return Config
     */
    protected static function configureEnvironment($environment)
    {
        $config = self::load("environment/$environment");
        return new Config($config);
    }

    /**
     * @param  string $namespace
     * @param  string $environment
     * @return Config
     */
    public static function configureApplication($namespace = 'My', $environment = null)
    {
        // register autoloader
        self::register($namespace);

        // load main configuration
        $config = self::load('application');
        $appConfig = new Config($config, true);
        $appConfig->namespace = $namespace;

        // load global configuration files
        $files = [ 'doctrine', 'routes' ];
        foreach ($files as $file) {
            $appConfig[$file] = self::load($file);
        }

        // auto-discover environment if empty
        if (null === $environment) {
            $environment = self::discoverEnvironment();
        }

        // configure environment
        $envConfig = self::configureEnvironment($environment);
        $appConfig->merge($envConfig);
        $appConfig->environment = $environment;

        // setup Proxies autoloader for proper deserialization
        $doctrine = $appConfig->get('doctrine');
        $proxyDir = $doctrine->get('proxy_dir', self::DEFAULT_PROXY_DIR);
        $proxyNamespace = $doctrine->get('proxy_namespace', $namespace . '\Proxies');
        Proxy\Autoloader::register($proxyDir, $proxyNamespace);

        Registry::set('application/config', $appConfig);
        return $appConfig;
    }

    /**
     * @param  string $namespace
     * @param  string $path to src/ directory
     * @return StandardAutoloader
     */
    public static function register($namespace = 'My', $path = 'application/src')
    {
        if (is_file('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
        }

        set_include_path(get_include_path() . PATH_SEPARATOR . 'library');

        $autoloader = new StandardAutoloader([ StandardAutoloader::ACT_AS_FALLBACK => true ]);
        $autoloader->registerNamespace($namespace, $path)->register();

        Registry::set('application/autoloader', $autoloader);
        return $autoloader;
    }
}