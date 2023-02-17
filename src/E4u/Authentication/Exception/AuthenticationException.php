<?php
namespace E4u\Authentication\Exception;
use E4u\Authentication\Identity;
use E4u\Exception\RuntimeException;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class AuthenticationException extends RuntimeException
{
    protected $user;

    public function __construct($message = "", $code = 0, Throwable $previous = null, ?Identity $user = null)
    {
        parent::__construct($message, $code, $previous);
        $this->user = $user;
    }

    public function setUser(?Identity $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): ?Identity
    {
        return $this->user;
    }
}