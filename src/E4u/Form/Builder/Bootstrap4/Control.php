<?php
namespace E4u\Form\Builder\Bootstrap4;

use E4u\Form\Base;

class Control
{
    /** @var  string */
    private $name;

    /** @var  Base */
    private $form;

    /**
     * @param string $name
     * @param Base $form
     */
    public function __construct($name, Base $form)
    {
        $this->name = $name;
        $this->form = $form;
    }

    public function id($value = null)
    {
        $parts = array_filter([$this->form->getName(), $this->name, $value]);
        return join('_', $parts);
    }

    public function getName()
    {
        return sprintf("%s[%s]", $this->form->getName(), $this->name);
    }

    public function getHelp()
    {
        return sprintf("%s_%sHelp", $this->form->getName(), $this->name);
    }

    public function getElement()
    {
        return $this->form->getElement($this->name);
    }
}