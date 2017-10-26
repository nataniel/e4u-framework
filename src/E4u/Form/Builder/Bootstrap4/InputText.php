<?php
namespace E4u\Form\Builder\Bootstrap4;



class InputText extends  Input
{



    /**
     * @return string
     */
    public function getContent()
    {
        $attributes = $this->getAttributesMergedWithFieldAttributes();

        return $this->view->tag('input', $attributes);
    }


    private function getClass()
    {
        return trim('form-control ' . $this->getOption('input_class'));
    }

    /**
     * @return array
     */
    private function getAttributesMergedWithFieldAttributes()
    {
        $attributes = array_merge($this->formElement->getAttributes(), $this->getAttributes());
        return $attributes;
    }


    /**
     * @return array
     */
    private function getAttributes()
    {
        return [

            'name' => $this->control->getName(),
            'id' => $this->control->id(),
            'required' => $this->formElement->isRequired() ? 'required' : null,
            'value' => $this->getValue(),

            'type' => $this->getOption('input_type', 'text'),
            'class' => $this->getClass(),
            'style' => $this->getOption('style'),
            'placeholder' => $this->view->t($this->getOption('placeholder', $this->formElement->getLabel())),
            'aria-describedby' => $this->control->getHelp(),

        ];
    }


    private function getValue()
    {
        $value = $this->formElement->getValue();
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d');
        } elseif (is_null($value)) {
            $value = '';
        }
        return $value;
    }
}