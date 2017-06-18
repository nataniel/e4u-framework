<?php
namespace E4u\Form\Element;

class CheckBoxGroup extends Options
{
    /**
     * @param  string $message
     * @return $this
     */
    public function setRequired($message = null)
    {
        parent::setRequired($message);
        unset($this->attributes['required']);
        return $this;
    }
}