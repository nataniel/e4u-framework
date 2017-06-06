<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

/**
 * $field = new Form\Element\MultiUpload('attachments', 'Załącz pliki');
 * $field->setMaxSize(10 * 1024 * 1024)
 *       ->setAccept('image');
 * $this->addField($field);
 */
class MultiUpload extends FileUpload
{
    protected $value = [];

    /**
     * <input type="file" name="foo[bar][]" id="foo_bar" multiple="multiple" />
     *
     * @deprecated use Form\Builder instead
     * @param  string $formName
     * @return string
     */
    public function render($formName)
    {
        $this->setAttributes([
            'type' => 'file',
            'name' => $this->htmlName($formName, true),
            'multiple' => 'multiple',
            'id' => $this->htmlId($formName),
        ]);

        return \E4u\Common\Html::tag('input', $this->attributes);
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        foreach ($this->getValue() as $file) {
            $this->checkFile($file);
        }

        return Element::isValid();
    }
}