<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class CheckBox extends Element
{
    protected $cssClass = 'check_box';

    /**
     * <input type="checkbox" name="login[login]" id="login_login" value="xxx" checked="checked" />
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setAttributes([
            'type' => 'checkbox',
            'name' => $this->htmlName($formName),
            'id' => $this->htmlId($formName),
            'checked' => $this->getValue() ? 'checked' : null,
            'value' => 1,
        ]);

        return \E4u\Common\Html::tag('input', $this->attributes);
    }

    /**
     * If the checkbox is not set the POST/GET will not send any value,
     * so we need to manually convert NULL to false.
     *
     * @param  mixed $value
     * @return Element
     */
    public function setValue($value)
    {
        return parent::setValue((bool)$value);
    }
}