<?php
namespace E4u\Request;

use E4u\Request\Request as RequestDescription;
use Laminas\Http\PhpEnvironment\Request as HttpRequest,
    Laminas\Mvc\Router\Http\TreeRouteStack as HttpRouter,
    Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch;

class Http extends HttpRequest implements RequestDescription
{
    protected RouteStackInterface $router;

    protected RouteMatch $currentRoute;

    protected function detectBaseUrl(): string
    {
        $baseUrl = parent::detectBaseUrl();
        return preg_replace('/index.php$/', '', $baseUrl);
    }

    public function setRouter(RouteStackInterface $router): static
    {
        $this->router = $router;
        return $this;
    }

    public function getRouter(): RouteStackInterface
    {
        if (!isset($this->router)) {
            $this->router = new HttpRouter();
        }

        return $this->router;
    }

    public function setCurrentRoute(RouteMatch $route): static
    {
        $this->currentRoute = $route;
        return $this;
    }

    public function getCurrentRoute(): RouteMatch
    {
        return $this->currentRoute;
    }

    public function getCurrentPath(): string
    {
        $route = $this->getQuery('route');
        if (is_null($route)) {
            $uri = $this->getUri()->getPath();
            return substr($uri, strlen($this->getBaseUrl()));
        }

        return $route;
    }

    public function getFullUrl(): string
    {
        $uri = $this->getUri();
        $port = $uri->getPort();
        return $uri->getScheme().'://'
            . $uri->getHost()
            . ($this->isDefaultPort($port) ? '' : ':' . $port)
            . $this->getBaseUrl();
    }

    public function getQueryString(): ?string
    {
        return $this->getUri()->getQuery();
    }

    public function mergeQuery(array $merge = []): string
    {
        $query = $this->getUri()->getQueryAsArray();
        $query = array_merge($query, $merge);
        return str_replace('+', '%20', http_build_query($query));
    }

    protected function isDefaultPort(int $port): bool
    {
        return in_array($port, [ 80, 443 ]);
    }

    public function isSSL(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }
}
