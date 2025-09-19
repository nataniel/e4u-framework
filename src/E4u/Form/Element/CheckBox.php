<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class CheckBox extends Element
{
    /**
     * If the checkbox is not set the POST/GET will not send any value,
     * so we need to manually convert NULL to false.
     */
    public function setValue(mixed $value): static
    {
        parent::setValue((bool)$value);
        return $this;
    }
}