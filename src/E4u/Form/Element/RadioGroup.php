<?php
namespace E4u\Form\Element;

class RadioGroup extends Options
{
    protected $cssClass = 'radio_group';

    /**
     * @deprecated use Form\Builder instead
     * @param string $formName
     * @param mixed $value
     * @param string $caption
     * @return string
     */
    public function renderOption($formName, $value, $caption)
    {
        $checked = $this->getValue() == $value ? 'checked="checked"' : '';
        $attributes = \E4u\Common\Html::attributes($this->attributes);

        return '<span><input '.$attributes.' type="radio"'
              .' name="'.$this->htmlName($formName).'" id="'.$this->optionId($formName, $value).'" value="'.$value.'" '.$checked.' />'
              .'<label for="'.$this->optionId($formName, $value).'">'.$caption.'</label></span>';
    }
}