<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class TextField extends Element
{
    protected $cssClass = 'text_field';
    protected $inputType = 'text';

    /**
     * <input type="text" name="login[login]" id="login_login" value="xxx" />
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setAttributes([
            'type' => $this->inputType,
            'name' => $this->htmlName($formName),
            'id' => $this->htmlId($formName),
            'value' => $this->getValue(),
            'onfocus' => $this->getPlaceholder() ? "this.removeAttribute('placeholder')" : null,
        ]);
        
        return \E4u\Common\Html::tag('input', $this->attributes);
    }
}