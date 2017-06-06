<?php
namespace E4u\Mailer;

use Zend\Mail;

class Test implements Mail\Transport\TransportInterface
{
    /**
     * Echo a mail message
     * 
     * @param  Mail\Message $message
     * @return void
     */
    public function send(Mail\Message $message)
    {
        echo('<pre>'.htmlentities($message->toString()).'</pre>');
    }
}