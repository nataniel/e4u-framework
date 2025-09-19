<?php
namespace E4u\Form\Element;

class SearchField extends TextField
{
    protected string $cssClass = 'text_field';
    protected string $inputType = 'search';
    
    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->setAutofocus();
    }
}