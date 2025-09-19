<?php
namespace E4u\Form\Element;

class Url extends TextField
{
    protected string $inputType = 'url';

    public function __construct(string $name, string|array|null $properties = null)
    {
        parent::__construct($name, $properties);
        $this->addValidator('Laminas\Validator\Uri', 'Nieprawidłowy adres www.');
    }
}