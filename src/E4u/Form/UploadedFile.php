<?php
namespace E4u\Form;

class UploadedFile
{
    public function __construct(
        protected array $data)
    {
    }

    public function moveTo(string $destination): bool
    {
        return move_uploaded_file($this->data['tmp_name'], $destination);
    }

    public function getName(): string
    {
        return basename($this->data['name']);
    }

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function getTmpName(): string
    {
        return $this->data['tmp_name'];
    }

    public function getSize(): string
    {
        return $this->data['size'];
    }

    public function getError(): int
    {
        return $this->data['error'];
    }

    public function isValid(): bool
    {
        return $this->data['error'] === UPLOAD_ERR_OK;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString()
    {
        return $this->data['name'];
    }
}