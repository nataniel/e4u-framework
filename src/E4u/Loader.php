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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL,
    Doctrine\ORM,
    Doctrine\Common\Proxy;
use Laminas\Config\Config;
use Laminas\I18n\Translator\Translator;
use Laminas\Mail\Transport\TransportInterface;

#mb_internal_encoding('utf-8');

class Loader
{
    const
        DEFAULT_ENVIRONMENT      = 'development',
        DEFAULT_MIGRATIONS_TABLE = 'doctrine_migrations',
        DEFAULT_MIGRATIONS_DIR   = 'application/src/Migrations',
        DEFAULT_PROXY_DIR        = 'application/src/Proxies',
        DEFAULT_MODELS_DIR       = 'application/src/Model';

    /**
     * Bootstrap E4u Application
     * There's no autoloader available at this point yet
     */
    public static function get(string $namespace = 'My', ?string $environment = null): Application
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

    public static function getApplication(): Application
    {
        return Registry::get('application/instance');
    }

    public static function getConfig(): Config
    {
        if (Registry::isRegistered('application/instance')) {
            return Registry::get('application/instance')->getConfig();
        }

        if (Registry::isRegistered('application/config')) {
            return Registry::get('application/config');
        }

        return new Config([]);
    }

    public static function getEnvironment(): string
    {
        return self::getConfig()->get('environment');
    }

    public static function getConnection(): DBAL\Connection
    {
        return self::getDoctrine()->getConnection();
    }

    public static function getTranslator(): Translator
    {
        if (Registry::isRegistered('application/translator')) {
            return Registry::get('application/translator');
        }

        $config = self::getConfig();
        $translator = new \Laminas\I18n\Translator\Translator();
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

    public static function getMailer(): TransportInterface
    {
        if (Registry::isRegistered('application/mailer')) {
            return Registry::get('application/mailer');
        }

        $config = self::getConfig();
        if (!empty($config->mailer)) {
            $type = $config->mailer->get('type', Mailer\Factory::SENDMAIL);
            $options = $config->mailer->get('options');
            if ($options instanceof Config) {
                $options = $options->toArray();
            }
        }
        else {
            $type = Mailer\Factory::SENDMAIL;
            $options = null;
        }

        $mailer = Mailer\Factory::get($type, $options);
        Registry::set('application/mailer', $mailer);
        return $mailer;
    }

    protected static function configureDoctrine(Config $config): ORM\Configuration
    {
        if (empty($config->doctrine)) {
            throw new \E4u\Exception\ConfigException('Config passed to E4u\Loader::getDoctrine() must have "doctrine" key set');
        }

        $isDevMode = self::getEnvironment() === self::DEFAULT_ENVIRONMENT;
        $proxyDir = getcwd() . DIRECTORY_SEPARATOR . $config->doctrine->get('proxy_dir', self::DEFAULT_PROXY_DIR);

        if ($entities_xml = $config->doctrine->get('entities_xml')) {
            $paths = [ $entities_xml ];
            $ormConfig = ORM\ORMSetup::createXMLMetadataConfiguration($paths, $isDevMode, $proxyDir);
        }
        else {
            $entities_dir = $config->doctrine->get('entities_dir', [ self::DEFAULT_MODELS_DIR ]);
            $ormConfig = ORM\ORMSetup::createAnnotationMetadataConfiguration($entities_dir, $isDevMode, $proxyDir);
        }

        AnnotationReader::addGlobalIgnoredName('assert');
        $ormConfig->setAutoGenerateProxyClasses($config->doctrine->get('auto_generate_proxies', $isDevMode));

        if ($sqlLogger = $config->doctrine->get('sql_logger')) {
            $ormConfig->setSQLLogger(new $sqlLogger());
        }

        // $ormConfig->setSecondLevelCacheEnabled();
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

    public static function getDoctrine(): ORM\EntityManager
    {
        if (Registry::isRegistered('doctrine/em')) {
            return Registry::get('doctrine/em');
        }

        $config = self::getConfig();
        if (empty($config->database)) {
            throw new \E4u\Exception\ConfigException('Config passed to E4u\Loader::getDoctrine() must have a "database" key set');
        }

        $ormConfig = self::configureDoctrine($config);
        $em = ORM\EntityManager::create($config->database->toArray(), $ormConfig);

        Registry::set('doctrine/em', $em);
        return $em;
    }

    public static function load(string $file, string $path = 'application/config'): array
    {
        if (is_file("$path/$file.php")) {
            return include("$path/$file.php");
        }

        return [];
    }

    protected static function discoverEnvironment(): string
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

    protected static function configureEnvironment(string $environment): Config
    {
        $config = self::load("environment/$environment");
        return new Config($config);
    }

    public static function configureApplication(string $namespace = 'My', ?string $environment = null): Config
    {
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
}
