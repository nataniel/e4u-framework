<?php
namespace E4u\Tools\Console;

use E4u\Application\Helper\Url;
use E4u\Request\Request;
use E4u\Tools\Console;
use Zend\Console\Getopt;

abstract class Base implements Command
{
    use Url;

    const HELP = '';

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var Getopt
     */
    protected $options;

    /**
     * @var Console
     */
    protected $console;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $_locale;

    /**
     * @return string
     */
    protected function getScript()
    {
        return $_SERVER['argv'][0];
    }

    /**
     * @param  string $key
     * @param  mixed $default
     * @return mixed|null
     */
    protected function getOption($key, $default = null)
    {
        $value = $this->options->getOption($key);
        return !empty($value) ? $value : $default;
    }

    /**
     * @param  string $key
     * @return string|null
     */
    protected function getArgument($key)
    {
        if (isset($this->arguments[$key])) {
            return $this->arguments[$key];
        }

        return null;
    }

    /**
     * @param  array $arguments
     * @param  Getopt $options
     * @return $this
     */
    public function configure($arguments, $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;
        return $this;
    }

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param  Console $console
     * @return Base    Current instance
     */
    public function setConsole(Console $console)
    {
        $this->console = $console;
        return $this;
    }

    public function showHelp()
    {
        echo "Usage:\n";
        $this->getConsole()->showHelp($this);
    }

    public function help()
    {
        return static::HELP;
    }

    /**
     * Get the router object. If no object available,
     * then create and configure one.
     *
     * @return Request
     */
    public function getRequest()
    {
        if (!$this->request instanceof Request) {
            $this->request = \E4u\Request\Factory::create();

            $config = $this->getConsole()->getConfig();
            if ($routes = $config->get('routes')) {
                $this->request->getRouter()->addRoutes($routes->toArray());
            }
        }

        return $this->request;
    }

    /**
     * @param  mixed $message
     * @param  string $locale
     * @return string
     */
    public function translate($message, $locale = null)
    {
        $message = (string)$message;
        return \E4u\Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getCurrentLocale());
    }

    /**
     * @param mixed $message
     * @param array $parameters
     * @return string
     */
    public function t($message, $parameters = null)
    {
        $txt = $this->translate($message);
        if (!empty($parameters)) {

            if (!is_array($parameters)) {
                $parameters = func_get_args();
                array_shift($parameters);
            }

            return vsprintf($txt, $parameters);
        }

        return $txt;
    }

    /**
     * @return string
     */
    public function getCurrentLocale()
    {
        if (null === $this->_locale) {
            $this->_locale = $this->detectCurrentLocale();
        }

        return $this->_locale;
    }

    /**
     * @return string
     */
    protected function detectCurrentLocale()
    {
        return \E4u\Loader::getConfig()->get('default_locale')
            ?: strtok(\E4u\Loader::getTranslator()->getLocale(), '_');
    }
}
