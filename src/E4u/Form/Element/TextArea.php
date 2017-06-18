<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class TextArea extends Element
{
    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->setAttributes([ 'rows' => 15, ]);
    }
}