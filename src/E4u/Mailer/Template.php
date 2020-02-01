<?php
namespace E4u\Mailer;

use E4u\Model\Entity;
use Zend\Mail;
use Zend\Mime;

class Template
{
    const FORMAT_TXT = 1;
    const FORMAT_HTML = 2;
    const CHARSET = 'utf-8';

    protected $from_name;
    protected $from_email;
    protected $to_name;
    protected $to_email;
    protected $subject = '';
    protected $content = '';
    protected $format = self::FORMAT_TXT;

    /**
     * @var Mail\Header\HeaderInterface[]
     */
    protected $headers = [];

    /**
     * @var Mime\Part[]
     */
    protected $attachments = [];

    protected $vars = [];

    /**
     * @var Mail\Transport\TransportInterface
     */
    protected $mailer;

    public function __construct($vars = [])
    {
        $this->addToHeaders('Date', gmdate('r'));
        $this->vars = $vars;
        $this->init();
    }

    /**
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    /**
     * @param  string $from_name
     * @return Template
     */
    public function setFromName($from_name)
    {
        $this->from_name = $from_name;
        return $this;
    }

    /**
     * @param  string $from_email
     * @return Template
     */
    public function setFromEmail($from_email)
    {
        $this->from_email = $from_email;
        return $this;
    }

    /**
     * @param  string $to_name
     * @return Template
     */
    public function setToName($to_name)
    {
        $this->to_name = $to_name;
        return $this;
    }

    /**
     * @param  string $to_email
     * @return Template
     */
    public function setToEmail($to_email)
    {
        $this->to_email = $to_email;
        return $this;
    }

    /**
     * @param  string $subject
     * @return Template
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return Mime\Part
     */
    public function getBody()
    {
        $body = \E4u\Common\Template::merge($this->content, $this->vars);
        $text = new Mime\Part(trim($body));
        $text->type = $this->getContentType();
        $text->charset = self::CHARSET;
        return $text;
    }

    /**
     * @param  string $content
     * @return Template
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return int
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param  int $format
     * @return Template
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param  string|Mail\Address\AddressInterface $email
     * @param  string $name
     * @return $this
     */
    public function setFrom($email, $name = null)
    {
        if ($email instanceof Mail\Address\AddressInterface) {
            return $this
                ->setFromEmail($email->getEmail())
                ->setFromName($email->getName());
        }

        return $this
            ->setFromEmail($email)
            ->setFromName($name);
    }

    /**
     * @param  string|Mail\Address\AddressInterface $email
     * @param  string $name
     * @return $this
     */
    public function setTo($email, $name = null)
    {
        if ($email instanceof Mail\Address\AddressInterface) {
            return $this
                ->setToEmail($email->getEmail())
                ->setToName($email->getName());
        }

        return $this
            ->setToEmail($email)
            ->setToName($name);
    }

    /**
     * @param  string $name
     * @param  string $value
     * @return $this
     */
    public function addToHeaders($name, $value)
    {
        if ($value instanceof Entity) {
            $value = $value->getId();
        }

        $this->headers[] = Mail\Header\GenericHeader::fromString($name . ':' . $value);
        return $this;
    }

    /**
     * @param  string $filename
     * @param  string|null $mimeType
     * @return $this
     */
    public function addToAttachments($filename, $mimeType = null)
    {
        if (!is_string($mimeType)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
        }

        $attachment = new Mime\Part(fopen($filename, 'r'));
        $attachment->type = $mimeType;
        $attachment->filename = basename($filename);
        $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Mime\Mime::ENCODING_BASE64;

        $this->attachments[] = $attachment;
        return $this;
    }

    /**
     * @return Mail\Transport\TransportInterface
     */
    public function getMailer()
    {
        if (null === $this->mailer) {
            $this->mailer = \E4u\Loader::getMailer();
        }

        return $this->mailer;
    }

    /**
     * @param Mail\Transport\TransportInterface $mailer
     * @return $this
     */
    public function setMailer(Mail\Transport\TransportInterface $mailer)
    {
        $this->mailer = $mailer;
        return $this;
    }

    /**
     * @return Mail\Headers
     */
    public function getHeaders()
    {
        $headers = new Mail\Headers();
        $contentType = sprintf('Content-Type: %s; charset=%s', $this->getContentType(), self::CHARSET);
        $headers->addHeader(Mail\Header\ContentType::fromString($contentType));
        foreach ($this->headers as $header) {
            $headers->addHeader($header);
        }

        return $headers;
    }

    private function getContentType()
    {
        return $this->format == self::FORMAT_HTML ?
            Mime\Mime::TYPE_HTML :
            Mime\Mime::TYPE_TEXT;
    }

    /**
     * @return Mime\Part[]
     */
    private function getMimeParts()
    {
        $parts = [ $this->getBody() ];
        foreach ($this->attachments as $attachment) {
            $parts[] = $attachment;
        }

        return $parts;
    }

    /**
     * @return string
     */
    private function getSubject()
    {
        return \E4u\Common\Template::merge($this->subject, $this->vars);
    }

    /**
     * @return Mail\Message
     */
    public function prepareMessage()
    {
        $message = new Mail\Message();

        $headers = $this->getHeaders();
        $parts = $this->getMimeParts();

        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts($parts);

        $message->setEncoding(self::CHARSET)
            ->setHeaders($headers)
            ->setFrom($this->from_email, $this->from_name)
            ->setTo($this->to_email, $this->to_name)
            ->setSubject($this->getSubject())
            ->setBody($mimeMessage);
        return $message;
    }

    /**
     * @return $this
     */
    public function send()
    {
        $mailer = $this->getMailer();
        $message = $this->prepareMessage();

        $mailer->send($message);
        return $this;
    }
}