<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class TextArea extends Element
{
    protected $cssClass = 'text_area';

    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->setAttributes([ 'rows' => 15, ]);
    }

    /**
     * <textarea type="text" name="login[login]" id="login_login">xxx</textarea>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setAttributes([
            'name' => $this->htmlName($formName),
            'id' => $this->htmlId($formName),
        ]);

        $value = htmlentities($this->getValue(), null, 'UTF-8');
        return \E4u\Common\Html::tag('textarea', $this->attributes, $value);
    }
}