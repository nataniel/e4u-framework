<?php
namespace E4u\Form\Builder\Bootstrap4;

use Zend\Config\Config;
use E4u\Application\View\Html as HtmlView;

abstract class Input
{
    /** @var \E4u\Form\Element */
    protected $formElement;

    /** @var  Config */
    protected $options;

    /**
     * @var HtmlView,
     */
    protected $view;

    /**
     * @var Control
     */
    protected $control;


    /**
     * @param Control $control
     * @param $options
     * @param HtmlView $view
     */
    public function __construct(Control $control, $options, HtmlView $view)
    {
        $this->control = $control;
        $this->formElement = $control->getElement();
        $this->options = new Config($options);
        $this->view = $view;
    }


    public function getOption($name, $default = null)
    {
        return $this->options->get($name, $default);
    }

    /**
     * @return \E4u\Form\Element
     */
    public function getFormElement()
    {
        return $this->formElement;
    }



    public abstract function getContent();
}