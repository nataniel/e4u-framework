<?php
namespace E4u\Form\Element;

class CheckBoxGroup extends Options
{
    protected $cssClass = 'check_box_group';

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
     * <span>
     * <input type="checkbox" name="foo[bar][]" id="foo_bar_1" value="1" checked="checked" />
     * <label for="foo_bar_1">Foo Bar 1</label>
     * </span>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @param  mixed  $value
     * @param  string $caption
     * @return string
     */
    public function renderOption($formName, $value, $caption)
    {
        $current = $this->getValue() ?: [];
        $checked = in_array($value, $current) ? 'checked="checked"' : '';
        $attributes = \E4u\Common\Html::attributes($this->attributes);

        return '<span><input '.$attributes.' type="checkbox"'
              .' name="'.$this->htmlName($formName, true).'" id="'.$this->optionId($formName, $value).'" value="'.$value.'" '.$checked.' />'
              .'<label for="'.$this->optionId($formName, $value).'">'.$caption.'</label></span>';
    }
}