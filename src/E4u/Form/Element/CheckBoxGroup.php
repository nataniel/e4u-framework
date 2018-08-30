<?php
namespace E4u\Form\Element;

class CheckBoxGroup extends Options
{
    protected $default = [];

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

    /**
     * @param  mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        if (empty($value)) {
            $value = [];
        }

        parent::setValue($value);
        return $this;
    }
}