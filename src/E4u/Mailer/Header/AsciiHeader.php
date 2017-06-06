<?php
namespace E4u\Mailer\Header;

class AsciiHeader extends AbstractHeader
{
    public function setEncoding($encoding)
    {
        // This header must be always in US-ASCII
        return $this;
    }

    public function getEncoding()
    {
        return 'ASCII';
    }
}