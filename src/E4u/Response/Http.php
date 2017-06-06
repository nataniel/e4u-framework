<?php
namespace E4u\Response;

class Http extends Base
{
    const STATUS_DESCRIPTION_100 = 'Continue';
    const STATUS_DESCRIPTION_101 = 'Switching Protocols';
    const STATUS_DESCRIPTION_102 = 'Processing';
    const STATUS_DESCRIPTION_200 = 'XOK';
    const STATUS_DESCRIPTION_201 = 'Created';
    const STATUS_DESCRIPTION_202 = 'Accepted';
    const STATUS_DESCRIPTION_203 = 'Non-Authoritative Information';
    const STATUS_DESCRIPTION_204 = 'No Content';
    const STATUS_DESCRIPTION_205 = 'Reset Content';
    const STATUS_DESCRIPTION_206 = 'Partial Content';
    const STATUS_DESCRIPTION_207 = 'Multi-status';
    const STATUS_DESCRIPTION_208 = 'Already Reported';
    const STATUS_DESCRIPTION_300 = 'Multiple Choices';
    const STATUS_DESCRIPTION_301 = 'Moved Permanently';
    const STATUS_DESCRIPTION_302 = 'Found';
    const STATUS_DESCRIPTION_303 = 'See Other';
    const STATUS_DESCRIPTION_304 = 'Not Modified';
    const STATUS_DESCRIPTION_305 = 'Use Proxy';
    const STATUS_DESCRIPTION_306 = 'Switch Proxy';
    const STATUS_DESCRIPTION_307 = 'Temporary Redirect';
    const STATUS_DESCRIPTION_400 = 'Bad Request';
    const STATUS_DESCRIPTION_401 = 'Unauthorized';
    const STATUS_DESCRIPTION_402 = 'Payment Required';
    const STATUS_DESCRIPTION_403 = 'Forbidden';
    const STATUS_DESCRIPTION_404 = 'Not Found';
    const STATUS_DESCRIPTION_405 = 'Method Not Allowed';
    const STATUS_DESCRIPTION_406 = 'Not Acceptable';
    const STATUS_DESCRIPTION_407 = 'Proxy Authentication Required';
    const STATUS_DESCRIPTION_408 = 'Request Time-out';
    const STATUS_DESCRIPTION_409 = 'Conflict';
    const STATUS_DESCRIPTION_410 = 'Gone';
    const STATUS_DESCRIPTION_411 = 'Length Required';
    const STATUS_DESCRIPTION_412 = 'Precondition Failed';
    const STATUS_DESCRIPTION_413 = 'Request Entity Too Large';
    const STATUS_DESCRIPTION_414 = 'Request-URI Too Large';
    const STATUS_DESCRIPTION_415 = 'Unsupported Media Type';
    const STATUS_DESCRIPTION_416 = 'Requested range not satisfiable';
    const STATUS_DESCRIPTION_417 = 'Expectation Failed';
    const STATUS_DESCRIPTION_418 = 'I\'m a teapot';
    const STATUS_DESCRIPTION_422 = 'Unprocessable Entity';
    const STATUS_DESCRIPTION_423 = 'Locked';
    const STATUS_DESCRIPTION_424 = 'Failed Dependency';
    const STATUS_DESCRIPTION_425 = 'Unordered Collection';
    const STATUS_DESCRIPTION_426 = 'Upgrade Required';
    const STATUS_DESCRIPTION_428 = 'Precondition Required';
    const STATUS_DESCRIPTION_429 = 'Too Many Requests';
    const STATUS_DESCRIPTION_431 = 'Request Header Fields Too Large';
    const STATUS_DESCRIPTION_500 = 'Internal Server Error';
    const STATUS_DESCRIPTION_501 = 'Not Implemented';
    const STATUS_DESCRIPTION_502 = 'Bad Gateway';
    const STATUS_DESCRIPTION_503 = 'Service Unavailable';
    const STATUS_DESCRIPTION_504 = 'Gateway Time-out';
    const STATUS_DESCRIPTION_505 = 'HTTP Version not supported';
    const STATUS_DESCRIPTION_506 = 'Variant Also Negotiates';
    const STATUS_DESCRIPTION_507 = 'Insufficient Storage';
    const STATUS_DESCRIPTION_508 = 'Loop Detected';
    const STATUS_DESCRIPTION_511 = 'Network Authentication Required';

    protected $headers = [];
    protected $statusDescription;
    protected $defaultContentType = 'text/html; charset=UTF-8';

    public function sendStatus()
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
    public function sendContent()
    {
        echo $this->getContent();
        return $this;
    }

    /**
     * @param  string $contentType
     * @return Http
     */
    public function setContentType($contentType)
    {
        $this->addHeader('Content-Type', $contentType);
        return $this;
    }

    /**
     * @param  string $name
     * @param  string $value
     * @return Http
     */
    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * @return Http
     */
    public function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        return $this;
    }

    /**
     * @return Http
     */
    public function send()
    {
        switch ($this->getStatus()) {
            case self::STATUS_REDIRECT:
                $this->addHeader('Location', $this->getMetadata('location') ?: '/');
                break;
            case self::STATUS_FORBIDDEN:
                $this->addHeader('Location', $this->getMetadata('location') ?: '/');
                break;
            default:
                if (!$this->hasHeader('Content-Type')) {
                    $this->setContentType($this->defaultContentType);
                }
        }

        return $this
            ->sendStatus()
            ->sendHeaders()
            ->sendContent();
    }
}