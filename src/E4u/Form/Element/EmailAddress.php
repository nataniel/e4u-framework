<?php
namespace E4u\Form\Element;

class EmailAddress extends TextField
{
    protected $inputType = 'email';
    
    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->addValidator(\Laminas\Validator\EmailAddress::class, 'Nieprawidłowy adres e-mail.');
    }
}