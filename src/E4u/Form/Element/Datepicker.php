<?php
namespace E4u\Form\Element;

/**
 * @deprecated use Form\Builder instead
 */
class Datepicker extends TextField
{
    protected $cssClass = 'text_field';
    protected $inputType = 'datepicker';
    
    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->setPattern('[0-9]{2}\.[0-9]{2}\.2[0-9]{3}');
    }
}