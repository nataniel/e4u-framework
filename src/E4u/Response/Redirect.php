<?php
namespace E4u\Response;

class Redirect extends Http
{
    protected int $status = self::STATUS_REDIRECT;

    public function __construct($url = null)
    {
        parent::__construct('');
        $this->setMetadata('location', $url);
    }

    public function send(): void
    {
        $this->addHeader('Location', $this->getMetadata('location') ?: '/');

        $this
            ->sendStatus()
            ->sendHeaders()
            ->sendContent();
    }
}