<?php
namespace E4u\Tools\Console;

use E4u\Application\Helper\Url;
use E4u\Request\Request;
use E4u\Tools\Console;

abstract class Base implements Command
{
    use Url;

    const string HELP = '';

    protected array $arguments;

    protected Getopt $options;

    protected Console $console;

    protected Request $request;

    protected string $_locale;

    protected function getScript(): string
    {
        return $_SERVER['argv'][0];
    }

    protected function getOption(string $key, mixed $default = null): mixed
    {
        $value = $this->options->getOption($key);
        return !empty($value) ? $value : $default;
    }

    protected function getArgument(string $key): ?string
    {
        if (isset($this->arguments[$key])) {
            return $this->arguments[$key];
        }

        return null;
    }

    public function configure(array $arguments, Getopt $options): static
    {
        $this->arguments = $arguments;
        $this->options = $options;
        return $this;
    }

    public function getConsole(): Console
    {
        return $this->console;
    }

    public function setConsole(Console $console): static
    {
        $this->console = $console;
        return $this;
    }

    public function showHelp(): void
    {
        echo "Usage:\n";
        $this->getConsole()->showHelp($this);
    }

    public function help(): array|string
    {
        return static::HELP;
    }

    /**
     * Get the router object. If no object available,
     * then create and configure one.
     */
    public function getRequest(): Request
    {
        if (!isset($this->request)) {
            $this->request = \E4u\Request\Factory::create();

            $config = $this->getConsole()->getConfig();
            if ($routes = $config->get('routes')) {
                $this->request->getRouter()->addRoutes($routes->toArray());
            }
        }

        return $this->request;
    }

    public function translate(mixed $message, ?string $locale = null): string
    {
        $message = (string)$message;
        return \E4u\Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getCurrentLocale());
    }

    public function t(mixed $message, mixed $parameters = null): string
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

    public function getCurrentLocale(): string
    {
        if (!isset($this->_locale)) {
            $this->_locale = $this->detectCurrentLocale();
        }

        return $this->_locale;
    }

    protected function detectCurrentLocale(): string
    {
        return \E4u\Loader::getConfig()->get('default_locale')
            ?: strtok(\E4u\Loader::getTranslator()->getLocale(), '_');
    }
}
