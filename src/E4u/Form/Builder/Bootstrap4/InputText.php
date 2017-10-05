<?php
namespace E4u\Form\Builder\Bootstrap;

use E4u\Form\Base;
use Zend\Config\Config;
use E4u\Application\View\Html as HtmlView;

class InputText
{
    private $formElement;
    /**
     * @var Base
     */
    private $form;

    /** @var  Config */
    private $options;

    /**
     * @var HtmlView,
     */
    private $view;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Control
     */
    private $control;

    /**
     * @param string $name
     * @param array $options
     * @param Base $form
     * @param HtmlView $view
     */
    public function __construct($name, $options, Base $form, HtmlView $view)
    {
        $this->form = $form;
        $this->options = new Config($options);
        $this->view = $view;
        $this->name = $name;
        $this->control = new Control($name, $form);
        $this->formElement = $this->form->getElement($this->name);
    }


    /**
     * @return string
     */
    public function getTag()
    {
        $attributes = $this->getAttributesMergedWithFieldAttributes();

        return $this->view->tag('input', $attributes);
    }

    public function option($name, $default = null)
    {
        return $this->options->get($name, $default);
    }

    private function getClass()
    {
        return trim('form-control ' . $this->option('input_class'));
    }

    /**
     * @return array
     */
    private function getAttributesMergedWithFieldAttributes()
    {
        $attributes = array_merge($this->formElement->getAttributes(), $this->getAttributes());
        return $attributes;
    }


    /**
     * @return array
     */
    private function getAttributes()
    {
        return [

            'name' => $this->control->name(),
            'id' => $this->control->id(),
            'required' => $this->formElement->isRequired() ? 'required' : null,
            'value' => $this->getValue(),

            'type' => $this->option('input_type', 'text'),
            'class' => $this->getClass(),
            'style' => $this->option('style'),
            'placeholder' => $this->view->t($this->option('placeholder', $this->formElement->getLabel())),
            'aria-describedby' => $this->control->help(),

        ];
    }


    private function getValue()
    {
        $value = $this->formElement->getValue();
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        } elseif (is_null($value)) {
            $value = '';
        }
        return $value;
    }
}