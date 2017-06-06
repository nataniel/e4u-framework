<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

class Submit extends Element
{
    protected $cssClass = 'submit';

    /**
     * <button id="login_submit" type="submit">Zaloguj się</button>
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setAttributes([
            'type' => 'submit',
            'id' => $this->htmlId($formName),
        ]);
        
        return \E4u\Common\Html::tag('button', $this->attributes, $this->label);
    }

    /**
     *  <div class="field submit" id="field-login_submit">
     *      <div class="input">
     *          <button id="login_submit" type="submit">Zaloguj się</button>
     *      </div>
     *  </div>
     *
     * @deprecated use Form\Builder instead
     */
    public function showHTML($formName)
    {

        $id    = "field-".$this->htmlId($formName);
        $class = join(' ', ['field', $this->cssClass, $this->isRequired() ? 'required' : null]);

        return \E4u\Common\Html::tag('div', [
            'class' => $class,
            'id' => $id,
        ], $this->showInput($formName));
    }
    
    public function setValue($value)
    {
        parent::setValue((bool)$value);
        return $this;
    }
}