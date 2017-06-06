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
     * <input type="date" name="login[login]" id="login_login" value="yyyy-mm-dd" />
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
            'value' => $this->getValue() ? $this->getValue()->format('Y-m-d') : '',
            'onfocus' => $this->getPlaceholder() ? "this.removeAttribute('placeholder')" : null,
        ]);

        return \E4u\Common\Html::tag('input', $this->attributes);
    }

    /**
     * @param  mixed $value
     * @return Date
     */
    public function setValue($value)
    {
        if (!empty($value)) {
            $value = new \DateTime($value);
        }

        return parent::setValue($value);
    }
}