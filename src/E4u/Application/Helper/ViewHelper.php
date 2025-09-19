<?php
namespace E4u\Application\Helper;

use E4u\Application\View;
use E4u\Exception\LogicException;
use Laminas\View\Helper\HelperInterface,
    Laminas\View\Renderer\RendererInterface as Renderer;

abstract class ViewHelper implements HelperInterface
{
    /**
     * View object instance
     */
    protected View|Renderer $view;

    public function setView(Renderer $view): static
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Get the view object
     */
    public function getView(): ?View
    {
        if (!isset($this->view)) {
            throw new LogicException('View helper has not been set.');
        }
        
        return $this->view;
    }
    
    public function __invoke()
    {
        if (!method_exists($this, 'show')) {
            throw new LogicException(sprintf(
                'Invokable plugin %s should define show() method.',
                get_class($this)));
        }
        
        return call_user_func_array([$this, 'show'], func_get_args());
    }
}