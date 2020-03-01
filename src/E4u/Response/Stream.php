<?php
namespace E4u\Response;

class Stream extends Http
{
    protected $defaultContentType = 'application/octet-stream';
    protected $headers = [
        'Content-Transfer-Encoding' => 'binary',
    ];
    protected $type = 'attachment';

    public function __construct($content = null, $name = null)
    {
        parent::__construct($content);

        if (!is_null($name)) {
            $this->setName($name);
        }
    }

    /**
     * @param  string $name
     * @return File
     */
    public function setName($name)
    {
        $this->addHeader('Content-Disposition', sprintf('%s; filename=%s', $this->type, $name));
        return $this;
    }
}