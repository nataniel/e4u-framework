<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class TextField extends Element
{
    protected string $inputType = 'text';

    public function getInputType(): string
    {
        return $this->inputType;
    }
}