<?php
namespace E4u\Authentication\Exception;
use E4u\Authentication\Identity;
use E4u\Exception\RuntimeException;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class AuthenticationException extends RuntimeException
{
    protected $user;

    public function __construct(?Identity $user = null, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->user = $user;
        parent::__construct($message, $code, $previous);
    }

    public function getUser(): ?Identity
    {
        return $this->user;
    }
}