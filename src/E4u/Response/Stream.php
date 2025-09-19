<?php
namespace E4u\Response;

class Stream extends Http
{
    protected string $defaultContentType = 'application/octet-stream';
    protected array $headers = [
        'Content-Transfer-Encoding' => 'binary',
    ];
    protected string $type = 'attachment';

    public function __construct($content = null, $name = null)
    {
        parent::__construct($content);

        if (!is_null($name)) {
            $this->setName($name);
        }
    }

    public function setName(string $name): static
    {
        $this->addHeader('Content-Disposition', sprintf('%s; filename=%s', $this->type, $name));
        return $this;
    }
}