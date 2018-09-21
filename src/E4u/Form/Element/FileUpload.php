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
     * @param  string[] $file
     * @return bool
     */
    protected function checkFile($file)
    {
        if (empty($file)) {
            return true;
        }

        $error = $this->uploadErrorMessage($file['error']);
        if (empty($error)) {
            return true;
        }

        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            unlink($file['tmp_name']);
        }

        $this->addError(sprintf('Błąd ładowania pliku (%d): %s.', $file['error'], $file['name']));
        return false;
    }

    /**
     * @param  int $error
     * @return string
     */
    private function uploadErrorMessage($error)
    {
        switch ($error) {

            case UPLOAD_ERR_OK:
                return null;

            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';

            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';

            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';

            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';

            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';

            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';

            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';

            default:
                return 'Unknown upload error.';

        }
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