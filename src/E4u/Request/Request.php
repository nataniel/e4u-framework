<?php
namespace E4u\Request;

use Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch,
    Laminas\Stdlib\RequestInterface;

interface Request extends RequestInterface
{
    public function setRouter(RouteStackInterface $router): static;
    public function getRouter(): RouteStackInterface;
    public function setCurrentRoute(RouteMatch $route): static;
    public function getCurrentRoute(): RouteMatch;
    public function getCurrentPath(): string;
    public function getBaseUrl();
    public function getFullUrl(): string;
    public function getPost($name = null, $default = null);
    public function getQuery($name = null, $default = null);
    public function getFiles(?string $name = null, mixed $default = null);
}