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
    protected $default = [];

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