<?php
namespace E4u\Form\Element;

class SearchField extends TextField
{
    protected $cssClass = 'text_field';
    protected $inputType = 'search';
    
    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->setAutofocus();
    }
}