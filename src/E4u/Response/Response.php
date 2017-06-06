<?php
namespace E4u\Response;

use Zend\Stdlib\ResponseInterface;

interface Response extends ResponseInterface
{
    const STATUS_OK = 200;
    const STATUS_REDIRECT = 302;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_INVALID = 500;

    public function send();
    public function setStatus($status);
    public function getStatus();
}