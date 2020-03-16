<?php
namespace E4u\Request;

use E4u\Request\Request as RequestDescription;
use Laminas\Http\PhpEnvironment\Request as HttpRequest,
    Laminas\Mvc\Router\Http\TreeRouteStack as HttpRouter,
    Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch;

class Http extends HttpRequest implements RequestDescription
{
    /** @var RouteStackInterface */
    protected $router;

    /** @var RouteMatch */
    protected $currentRoute;

    public function setRouter(RouteStackInterface $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @return RouteStackInterface
     */
    public function getRouter()
    {
        if (!$this->router instanceof RouteStackInterface)
        {
            $this->router = new HttpRouter();
        }

        return $this->router;
    }

    public function setCurrentRoute(RouteMatch $route)
    {
        $this->currentRoute = $route;
        return $this;
    }

     /**
     * @return RouteMatch
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function getCurrentPath()
    {
        $uri = $this->getUri()->getPath();
        return substr($uri, strlen($this->getBaseUrl()));
    }

    /**
     * @return string
     */
    public function getFullUrl()
    {
        $uri = $this->getUri();
        $port = $uri->getPort();
        return $uri->getScheme().'://'
            . $uri->getHost()
            . ($port == $this->defaultPort() ? '' : ':' . $port)
            . $this->getBaseUrl();
    }

    public function getQueryString()
    {
        return $this->getUri()->getQuery();
    }

    /**
     * @param  array $merge
     * @return string
     */
    public function mergeQuery($merge = [])
    {
        $query = $this->getUri()->getQueryAsArray();
        $query = array_merge($query, $merge);
        return str_replace('+', '%20', http_build_query($query));
    }

    protected function defaultPort()
    {
        return $this->isSSL() ? 443 : 80;
    }

    /**
     * @return bool
     */
    public function isSSL()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }
}