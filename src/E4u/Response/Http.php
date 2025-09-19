<?php
namespace E4u\Response;

class Http extends Base
{
    const string
        STATUS_DESCRIPTION_100 = 'Continue',
        STATUS_DESCRIPTION_101 = 'Switching Protocols',
        STATUS_DESCRIPTION_102 = 'Processing',
        STATUS_DESCRIPTION_200 = 'OK',
        STATUS_DESCRIPTION_201 = 'Created',
        STATUS_DESCRIPTION_202 = 'Accepted',
        STATUS_DESCRIPTION_203 = 'Non-Authoritative Information',
        STATUS_DESCRIPTION_204 = 'No Content',
        STATUS_DESCRIPTION_205 = 'Reset Content',
        STATUS_DESCRIPTION_206 = 'Partial Content',
        STATUS_DESCRIPTION_207 = 'Multi-status',
        STATUS_DESCRIPTION_208 = 'Already Reported',
        STATUS_DESCRIPTION_300 = 'Multiple Choices',
        STATUS_DESCRIPTION_301 = 'Moved Permanently',
        STATUS_DESCRIPTION_302 = 'Found',
        STATUS_DESCRIPTION_303 = 'See Other',
        STATUS_DESCRIPTION_304 = 'Not Modified',
        STATUS_DESCRIPTION_305 = 'Use Proxy',
        STATUS_DESCRIPTION_306 = 'Switch Proxy',
        STATUS_DESCRIPTION_307 = 'Temporary Redirect',
        STATUS_DESCRIPTION_400 = 'Bad Request',
        STATUS_DESCRIPTION_401 = 'Unauthorized',
        STATUS_DESCRIPTION_402 = 'Payment Required',
        STATUS_DESCRIPTION_403 = 'Forbidden',
        STATUS_DESCRIPTION_404 = 'Not Found',
        STATUS_DESCRIPTION_405 = 'Method Not Allowed',
        STATUS_DESCRIPTION_406 = 'Not Acceptable',
        STATUS_DESCRIPTION_407 = 'Proxy Authentication Required',
        STATUS_DESCRIPTION_408 = 'Request Time-out',
        STATUS_DESCRIPTION_409 = 'Conflict',
        STATUS_DESCRIPTION_410 = 'Gone',
        STATUS_DESCRIPTION_411 = 'Length Required',
        STATUS_DESCRIPTION_412 = 'Precondition Failed',
        STATUS_DESCRIPTION_413 = 'Request Entity Too Large',
        STATUS_DESCRIPTION_414 = 'Request-URI Too Large',
        STATUS_DESCRIPTION_415 = 'Unsupported Media Type',
        STATUS_DESCRIPTION_416 = 'Requested range not satisfiable',
        STATUS_DESCRIPTION_417 = 'Expectation Failed',
        STATUS_DESCRIPTION_418 = 'I\'m a teapot',
        STATUS_DESCRIPTION_422 = 'Unprocessable Entity',
        STATUS_DESCRIPTION_423 = 'Locked',
        STATUS_DESCRIPTION_424 = 'Failed Dependency',
        STATUS_DESCRIPTION_425 = 'Unordered Collection',
        STATUS_DESCRIPTION_426 = 'Upgrade Required',
        STATUS_DESCRIPTION_428 = 'Precondition Required',
        STATUS_DESCRIPTION_429 = 'Too Many Requests',
        STATUS_DESCRIPTION_431 = 'Request Header Fields Too Large',
        STATUS_DESCRIPTION_500 = 'Internal Server Error',
        STATUS_DESCRIPTION_501 = 'Not Implemented',
        STATUS_DESCRIPTION_502 = 'Bad Gateway',
        STATUS_DESCRIPTION_503 = 'Service Unavailable',
        STATUS_DESCRIPTION_504 = 'Gateway Time-out',
        STATUS_DESCRIPTION_505 = 'HTTP Version not supported',
        STATUS_DESCRIPTION_506 = 'Variant Also Negotiates',
        STATUS_DESCRIPTION_507 = 'Insufficient Storage',
        STATUS_DESCRIPTION_508 = 'Loop Detected',
        STATUS_DESCRIPTION_511 = 'Network Authentication Required';

    protected array $headers = [];
    protected string $statusDescription;
    protected string $defaultContentType = 'text/html; charset=UTF-8';

    public function sendStatus(): static
    {
        $description = $this->statusDescription ?: constant('self::STATUS_DESCRIPTION_'.$this->getStatus());
        $status = sprintf(
            'HTTP/1.1 %d %s',
            $this->getStatus(),
            $description
        );

        header($status);
        return $this;
    }

    /**
     * @return Http
     */
    public function sendContent(): static
    {
        echo $this->getContent();
        return $this;
    }

    public function setContentType(string $contentType): static
    {
        $this->addHeader('Content-Type', $contentType);
        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function sendHeaders(): static
    {
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        return $this;
    }

    public function send(): void
    {
        switch ($this->getStatus()) {
            case self::STATUS_REDIRECT:
            case self::STATUS_FORBIDDEN:
                $this->addHeader('Location', $this->getMetadata('location') ?: '/');
                break;
            default:
                if (!$this->hasHeader('Content-Type')) {
                    $this->setContentType($this->defaultContentType);
                }
        }

        $this
            ->sendStatus()
            ->sendHeaders()
            ->sendContent();
    }
}