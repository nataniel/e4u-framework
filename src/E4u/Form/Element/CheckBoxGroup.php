<?php
namespace E4u\Form\Element;

class CheckBoxGroup extends Options
{
    protected mixed $default = [];

    public function setRequired(null|bool|string $message = null): static
    {
        parent::setRequired($message);
        unset($this->attributes['required']);
        return $this;
    }

    public function setValue(mixed $value): static
    {
        if (empty($value)) {
            $value = [];
        }

        parent::setValue($value);
        return $this;
    }
}