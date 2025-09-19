<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class TextArea extends Element
{
    public function __construct(string $name, string|array|null $properties = null)
    {
        parent::__construct($name, $properties);
        $this->setAttributes([ 'rows' => 15, ]);
    }
}