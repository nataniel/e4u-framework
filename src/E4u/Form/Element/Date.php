<?php
namespace E4u\Form\Element;

class Date extends TextField
{
    protected $cssClass = 'text_field';
    protected $inputType = 'date';

    /**
     * Current value of the field
     * @var \DateTime
     */
    protected $value;

    /**
     * @param  mixed $value
     * @return $this
     */
    public function setValue($value)
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