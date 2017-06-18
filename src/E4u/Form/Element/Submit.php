<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class Submit extends Element
{
    /**
     * @param  mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        parent::setValue((bool)$value);
        return $this;
    }
}