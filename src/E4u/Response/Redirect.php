<?php
namespace E4u\Response;

class Redirect extends Http
{
    protected $status = self::STATUS_REDIRECT;

    public function __construct($url = null)
    {
        $this->setMetadata('location', $url);
    }

    public function send()
    {
        $this->addHeader('Location', $this->getMetadata('location') ?: '/');

        return $this
            ->sendStatus()
            ->sendHeaders()
            ->sendContent();
    }
}