<?php
namespace E4u\Mailer\Header;

class MessageId extends \Laminas\Mail\Header\MessageId
{
    public function __construct($id = null)
    {
        if (!empty($id)) {
            $this->setId($id);
        }
    }
}