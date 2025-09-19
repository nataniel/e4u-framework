<?php
namespace E4u\Mailer;

use E4u\Model\Entity;
use Laminas\Mail;
use Laminas\Mime;

class Template
{
    const int
        FORMAT_TXT = 1,
        FORMAT_HTML = 2;
    
    const string CHARSET = 'utf-8';

    protected string $from_name;
    protected string $from_email;
    protected string $to_name;
    protected string $to_email;
    protected string $subject = '';
    protected string $content = '';
    protected int $format = self::FORMAT_TXT;

    /**
     * @var Mail\Header\HeaderInterface[]
     */
    protected array $headers = [];

    /**
     * @var Mime\Part[]
     */
    protected array $attachments = [];

    protected array $vars = [];

    protected Mail\Transport\TransportInterface $mailer;

    public function __construct(array $vars = [])
    {
        $this->addToHeaders('Date', gmdate('r'));
        $this->addToHeaders(new Mail\Header\MessageId());
        $this->vars = $vars;
        $this->init();
    }

    public function init(): void
    {
    }

    public function setFromName(string $from_name): static
    {
        $this->from_name = $from_name;
        return $this;
    }

    public function setFromEmail(string $from_email): static
    {
        $this->from_email = $from_email;
        return $this;
    }

    public function setToName(string $to_name): static
    {
        $this->to_name = $to_name;
        return $this;
    }

    public function setToEmail(string $to_email): static
    {
        $this->to_email = $to_email;
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): Mime\Part
    {
        $body = \E4u\Common\Template::merge($this->content, $this->vars);
        $text = new Mime\Part(trim($body));
        $text->type = $this->getContentType();
        $text->charset = self::CHARSET;
        return $text;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getFormat(): int
    {
        return $this->format;
    }

    public function setFormat(int $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return Mime\Part[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setFrom(string|Mail\Address\AddressInterface $email, ?string $name = null): static
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

    public function setTo(string|Mail\Address\AddressInterface $email, ?string $name = null): static
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

    public function addToHeaders(string|Mail\Header\HeaderInterface $name, Entity|string|null $value = null): static
    {
        if ($name instanceof Mail\Header\HeaderInterface) {
            $this->headers[] = $name;
            return $this;
        }

        if ($value instanceof Entity) {
            $value = $value->getId();
        }

        $this->headers[] = Mail\Header\GenericHeader::fromString($name . ':' . $value);
        return $this;
    }

    public function addToAttachments(string $filename, ?string $mimeType = null): static
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

    public function getMailer(): Mail\Transport\TransportInterface
    {
        if (!isset($this->mailer)) {
            $this->mailer = \E4u\Loader::getMailer();
        }

        return $this->mailer;
    }

    public function setMailer(Mail\Transport\TransportInterface $mailer): static
    {
        $this->mailer = $mailer;
        return $this;
    }

    public function getHeaders(): Mail\Headers
    {
        $headers = new Mail\Headers();
        $contentType = sprintf('Content-Type: %s; charset=%s', $this->getContentType(), self::CHARSET);
        $headers->addHeader(Mail\Header\ContentType::fromString($contentType));
        foreach ($this->headers as $header) {
            $headers->addHeader($header);
        }

        return $headers;
    }

    private function getContentType(): string
    {
        return $this->format == self::FORMAT_HTML ?
            Mime\Mime::TYPE_HTML :
            Mime\Mime::TYPE_TEXT;
    }

    /**
     * @return Mime\Part[]
     */
    private function getMimeParts(): array
    {
        $parts = [ $this->getBody() ];
        foreach ($this->attachments as $attachment) {
            $parts[] = $attachment;
        }

        return $parts;
    }

    private function getSubject(): string
    {
        return \E4u\Common\Template::merge($this->subject, $this->vars);
    }

    public function prepareMessage(): Mail\Message
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

    public function send(): void
    {
        $mailer = $this->getMailer();
        $message = $this->prepareMessage();

        $mailer->send($message);
    }
}