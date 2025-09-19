<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class Submit extends Element
{
    /**
     * @param  mixed $value
     * @return $this
     */
    public function setValue(mixed $value): static
    {
        parent::setValue((bool)$value);
        return $this;
    }
}