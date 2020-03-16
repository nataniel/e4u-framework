<?php
namespace E4u\Form\Element;

class Url extends TextField
{
    protected $inputType = 'url';

    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->addValidator('Laminas\Validator\Uri', 'Nieprawidłowy adres www.');
    }
}