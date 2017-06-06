<?php
namespace E4u\Application\Helper;

use Zend\View\Helper\AbstractHelper,
    Zend\View\Helper\HelperInterface,
    Zend\View\Renderer\RendererInterface as Renderer;

abstract class ViewHelper implements HelperInterface
{
    /**
     * View object instance
     *
     * @var Renderer
     */
    protected $view = null;

    /**
     * Set the View object
     *
     * @param  Renderer $view
     * @return AbstractHelper
     */
    public function setView(Renderer $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Get the view object
     *
     * @return null|Renderer
     */
    public function getView()
    {
        return $this->view;
    }
    
    public function __invoke()
    {
        if (!method_exists($this, 'show')) {
            throw new \E4u\Exception\LogicException(sprintf(
                'Invokable plugin %s should define show() method.',
                get_class($this)));
        }
        
        return call_user_func_array([$this, 'show'], func_get_args());
    }
}