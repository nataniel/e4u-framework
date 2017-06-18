<?php
namespace E4u\Application\View;

use E4u\Exception\LogicException;
use Zend\View\Helper\AbstractHelper;
use Zend\View\HelperPluginManager;

trait Plugins
{
    /** @var HelperPluginManager */
    protected $__helpers;
    
    /**
     * Set helper plugin manager instance
     *
     * @param  string|HelperPluginManager $helpers
     * @return Pluggable
     * @throws \E4u\Exception\LogicException
     */
    public function setHelperPluginManager($helpers)
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
                'Helper helpers must extend Zend\View\HelperPluginManager; got type "%s" instead',
                (is_object($helpers) ? get_class($helpers) : gettype($helpers))
            ));
        }
        
        $helpers->setRenderer($this);
        $this->__helpers = $helpers;
        return $this;
    }

    /**
     * Get helper plugin manager instance
     *
     * @return HelperPluginManager
     */
    protected function getHelperPluginManager()
    {
        if (null === $this->__helpers) {
            $this->setHelperPluginManager(new HelperPluginManager($this));
        }
        
        return $this->__helpers;
    }
    
    /**
     * Get helper plugin manager instance
     *
     * @return HelperPluginManager
     */
    protected function plugins()
    {
        return $this->getHelperPluginManager();
    }
    
    /**
     * Get plugin instance
     *
     * @param  string     $name Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return AbstractHelper
     */
    public function plugin($name, array $options = null)
    {
        return $this->getHelperPluginManager()->get($name, $options);
    }
    
    /**
     * @param  string $name Name of plugin to return
     * @param  mixed  $options Options to pass to plugin __invoke() method
     * @return AbstractHelper|mixed
     */
    public function _($name)
    {
        if (!$this->plugins()->has($name)) {
            throw new LogicException(
                    sprintf('Request of undefined plugin %s::%s',
                    get_class($this), $name));
        }

        $plugin = $this->getHelperPluginManager()->get($name);
        $argv = func_get_args();
        array_shift($argv);
        
        switch (true) {
            case empty($argv):
                return $plugin;
            case method_exists($plugin, '__invoke'):
                /* PHP 5.6+ */
                # return $plugin(...$argv);
                return call_user_func_array([ $plugin, '__invoke' ], $argv);
            case method_exists($plugin, 'show'):
                /* PHP 5.6+ */
                # return $plugin->show(...$argv);
                return call_user_func_array([ $plugin, 'show' ], $argv);
            default:
                return $plugin;
        }
    }
    
    public function __call($method, $argv)
    {
        array_unshift($argv, $method);
        return call_user_func_array([ $this, '_' ], $argv);
    }
}