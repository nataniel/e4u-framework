<?php
namespace E4u\Request;

use Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch,
    Laminas\Stdlib\RequestInterface,
    Laminas\Stdlib\ParametersInterface;

interface Request extends RequestInterface
{
    /**
     * @param  RouteStackInterface $router
     * @return Request
     */
    public function setRouter(RouteStackInterface $router);

    /**
     * @return RouteStackInterface
     */
    public function getRouter();

    /**
     * @param  RouteMatch $route
     * @return Request
     */
    public function setCurrentRoute(RouteMatch $route);

    /** @return RouteMatch */
    public function getCurrentRoute();

    /** @return string */
    public function getCurrentPath();

    /** @return string */
    public function getBaseUrl();

    /** @return string */
    public function getFullUrl();

    /**
     * @param  string $name
     * @param  mixed $default
     * @return ParametersInterface|mixed
     */
    public function getPost($name = null, $default = null);

    /**
     * @param  string $name
     * @param  mixed $default
     * @return ParametersInterface|mixed
     */
    public function getQuery($name = null, $default = null);

    /**
     * @param  string $name
     * @param  mixed $default
     * @return ParametersInterface|mixed
     */
    public function getFiles($name = null, $default = null);
}