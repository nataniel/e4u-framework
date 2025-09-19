<?php
namespace E4u\Form\Element;

class Date extends TextField
{
    protected string $cssClass = 'text_field';
    protected string $inputType = 'date';

    protected mixed $value;

    public function setValue(mixed $value): static
    {
        if (is_string($value)) {
            $value = !empty($value)
                ? new \DateTime($value)
                : null;
        }

        parent::setValue($value);
        return $this;
    }
}