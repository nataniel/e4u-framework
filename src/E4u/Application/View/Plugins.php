<?php
namespace E4u\Application\View;

use E4u\Exception\LogicException;
use Laminas\View\HelperPluginManager;

trait Plugins
{
    protected HelperPluginManager $__helpers;
    
    /**
     * Set helper plugin manager instance
     */
    public function setHelperPluginManager(string|HelperPluginManager $helpers): static
    {
        if (is_string($helpers)) {
            if (!class_exists($helpers)) {
                throw new LogicException(sprintf(
                    'Invalid helper helpers class provided (%s)',
                    $helpers
                ));
            }
            $helpers = new $helpers();
        }
        
        if (!$helpers instanceof HelperPluginManager) {
            throw new LogicException(sprintf(
                'Helper helpers must extend Laminas\View\HelperPluginManager; got type "%s" instead',
                (is_object($helpers) ? get_class($helpers) : gettype($helpers))
            ));
        }
        
        $helpers->setRenderer($this);
        $this->__helpers = $helpers;
        return $this;
    }

    /**
     * Get helper plugin manager instance
     */
    protected function getHelperPluginManager(): HelperPluginManager
    {
        if (!isset($this->__helpers)) {
            $this->setHelperPluginManager(new HelperPluginManager($this));
        }
        
        return $this->__helpers;
    }
    
    /**
     * Get helper plugin manager instance
     */
    protected function plugins(): HelperPluginManager
    {
        return $this->getHelperPluginManager();
    }
    
    /**
     * Get plugin instance
     */
    public function plugin(string $name, ?array $options = null): object
    {
        return $this->getHelperPluginManager()->get($name, $options);
    }
    
    public function _(string $name): mixed
    {
        if (!$this->plugins()->has($name)) {
            throw new LogicException(
                    sprintf('Request of undefined plugin %s::%s',
                    get_class($this), $name));
        }

        $plugin = $this->getHelperPluginManager()->get($name);
        $argv = func_get_args();
        array_shift($argv);

        return match (true) {
            empty($argv) => $plugin,
            method_exists($plugin, '__invoke') => call_user_func_array([$plugin, '__invoke'], $argv),
            method_exists($plugin, 'show') => call_user_func_array([$plugin, 'show'], $argv),
            default => $plugin,
        };
    }
    
    public function __call($method, $argv)
    {
        array_unshift($argv, $method);
        return call_user_func_array([ $this, '_' ], $argv);
    }
}