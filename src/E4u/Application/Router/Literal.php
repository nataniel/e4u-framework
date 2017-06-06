<?php
namespace E4u\Application\Router;

use Zend\Mvc\Router\Http\Literal as ZendLiteral,
    Zend\Mvc\Router\Http\RouteMatch,
    Zend\Stdlib\RequestInterface as Request;

class Literal extends ZendLiteral
{
    /**
     * match(): defined by Route interface.
     * Overriden from Http\Literal so it does not use Request#getUri,
     * but Request#getCurrentPath instead.
     *
     * @see    Route::match()
     * @param  Request $request
     * @return RouteMatch
     */
    public function match(Request $request, $pathOffset = null)
    {
        if (!method_exists($request, 'getCurrentPath')) {
            return null;
        }

        $path = $request->getCurrentPath();
        $path = '/'.trim($path, '/');

        if ($path === $this->route) {
            return new RouteMatch($this->defaults, strlen($this->route));
        }

        return null;
    }
}