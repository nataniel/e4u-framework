<?php
namespace E4u\Tools\Console;

use E4u\Request\Request;
use E4u\Tools\Console;

abstract class Base implements Command
{
    const HELP = '';

    protected $arguments;
    protected $options;
    protected $console;
    protected $request;

    protected function getScript()
    {
        return $_SERVER['argv'][0];
    }

    protected function getOption($key, $default = null)
    {
        $value = $this->options->getOption($key);
        return !empty($value) ? $value : $default;
    }

    protected function getArgument($key)
    {
        if (isset($this->arguments[$key])) {
            return $this->arguments[$key];
        }

        return null;
    }

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
        }

        return $this->request;
    }
}