<?php
namespace E4u\Response;

use Laminas\Stdlib\ResponseInterface;

interface Response extends ResponseInterface
{
    const int
        STATUS_OK = 200,
        STATUS_REDIRECT = 302,
        STATUS_FORBIDDEN = 403,
        STATUS_NOT_FOUND = 404,
        STATUS_INVALID = 500;

    public function send(): void;
    public function setStatus(int $status): void;
    public function getStatus(): int;
}