<?php
namespace E4u\Form\Element;

use E4u\Form\Element;

/**
 * $field = new Form\Element\FileUpload('attachment', 'Załącz plik');
 * $field->setMaxSize(10 * 1024 * 1024)
 *       ->setAccept('image');
 * $this->addField($field);
 */
class FileUpload extends Element
{
    const
        ACCEPT_AUDIO = 'audio/*',
        ACCEPT_VIDEO = 'video/*',
        ACCEPT_IMAGE = 'image/*';

    protected $maxSize = 5242880;  // 5MB

    /**
     * @param  int $size
     * @return FileUpload
     */
    public function setMaxSize($size)
    {
        $this->maxSize = $size;
        return $this;
    }

    /**
     * @return FileUpload
     */
    public function acceptImages()
    {
        $this->setAccept(self::ACCEPT_IMAGE);
        return $this;
    }

    /**
     * @param  string $mime
     * @return FileUpload
     */
    public function setAccept($mime)
    {
        if (defined('self::ACCEPT_' . strtoupper($mime))) {
            $mime = constant('self::ACCEPT_' . strtoupper($mime));
        }

        $this->attributes['accept'] = $mime;
        return $this;
    }

    /**
     * @param  array $file
     * @return FileUpload
     */
    protected function checkFile($file)
    {
        if (!empty($file)) {
            if (empty($file['tmp_name']) || empty($file['name'])) {
                $this->addError('Błąd ładowania pliku.');
            }
            elseif (!is_uploaded_file($file['tmp_name'])) {
                $this->addError(sprintf('Błąd ładowania pliku: %s.', $file['name']));
            }
            elseif ($file['error'] != UPLOAD_ERR_OK) {
                unlink($file['tmp_name']);
                $this->addError(sprintf('Błąd ładowania pliku (%d): %s.', (int)$file['error'], $file['name']));
            } elseif ($file['size'] > $this->maxSize) {
                unlink($file['tmp_name']);
                $this->addError(sprintf('Zbyt duży rozmiar pliku (max. %.2f MB): %s.', $this->maxSize / 1048576, $file['name']));
            }
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        $this->checkFile($this->getValue());
        return parent::isValid();
    }
}