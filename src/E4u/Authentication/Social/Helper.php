<?php
namespace E4u\Authentication\Social;

use E4u\Request\Request;
use Laminas\Config\Config;

interface Helper
{
    public function __construct(Config $config, Request $request);
    public function getLoginUrl(): string;
    public function loginFromRedirect(): bool;
    public function hasId(): bool;
    public function getId(): ?string;
    public function getFirstName(): ?string;
    public function getLastName(): ?string;
    public function getPicture(): ?string;
    public function getEmail(): ?string;
    public function getLocale(): ?string;
}
