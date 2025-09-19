<?php
namespace E4u\Response;

class InlineFile extends File
{
    protected string $type = 'inline';

    public function __construct($filename = null, $name = null)
    {
        parent::__construct($filename, $name);
        $this->setContentType(mime_content_type($filename));
    }
}