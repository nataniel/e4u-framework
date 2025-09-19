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
    const string
        ACCEPT_AUDIO = 'audio/*',
        ACCEPT_VIDEO = 'video/*',
        ACCEPT_IMAGE = 'image/*';

    protected int $maxSize = 5242880;  // 5MB

    public function setMaxSize(int $size): static
    {
        $this->maxSize = $size;
        return $this;
    }

    public function acceptImages(): static
    {
        $this->setAccept(self::ACCEPT_IMAGE);
        return $this;
    }

    public function setAccept(string $mime): static
    {
        if (defined('self::ACCEPT_' . strtoupper($mime))) {
            $mime = constant('self::ACCEPT_' . strtoupper($mime));
        }

        $this->attributes['accept'] = $mime;
        return $this;
    }

    protected function checkFile(array $file): bool
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

    private function uploadErrorMessage(int $error): ?string
    {
        return match ($error) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
            default => 'Unknown upload error.',
        };
    }

    public function isValid(): bool
    {
        $this->checkFile($this->getValue());
        return parent::isValid();
    }
}