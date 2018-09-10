<?php
namespace E4u\Form;

class UploadedFile
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param  string $destination
     * @return bool
     */
    public function moveTo($destination)
    {
        return move_uploaded_file($this->data['tmp_name'], $destination);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return basename($this->data['name']);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->data['type'];
    }

    /**
     * @return string
     */
    public function getTmpName()
    {
        return $this->data['tmp_name'];
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->data['size'];
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->data['error'];
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->data['error'] == UPLOAD_ERR_OK;
    }

    /**
     * @return string[]
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->data['name'];
    }
}