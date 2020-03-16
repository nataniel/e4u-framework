<?php
namespace E4u\Application\Router;

use Laminas\Mvc\Router\Http\Literal as LaminasLiteral,
    Laminas\Mvc\Router\Http\RouteMatch,
    Laminas\Stdlib\RequestInterface as Request;

class Literal extends LaminasLiteral
{
    /**
     * match(): defined by Route interface.
     * Overriden from Http\Literal so it does not use Request#getUri,
     * but Request#getCurrentPath instead.
     *
     * @see    Route::match()
     * @param  Request $request
     * @param  int $pathOffset
     * @return RouteMatch
     */
    public function match(Request $request, $pathOffset = null)
    {
        if (!method_exists($request, 'getCurrentPath')) {
            return null;
        }

        $path = $request->getCurrentPath();
        if ($path === $this->route) {
            return new RouteMatch($this->defaults, strlen($this->route));
        }

        return null;
    }
}