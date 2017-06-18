<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class CheckBox extends Element
{
    /**
     * If the checkbox is not set the POST/GET will not send any value,
     * so we need to manually convert NULL to false.
     *
     * @param  mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        parent::setValue((bool)$value);
        return $this;
    }
}