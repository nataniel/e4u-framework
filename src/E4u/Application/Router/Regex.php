<?php
namespace E4u\Application\Router;

use Laminas\Mvc\Router\Http\Regex as LaminasRegex,
    Laminas\Mvc\Router\Http\RouteMatch,
    Laminas\Stdlib\RequestInterface as Request;

class Regex extends LaminasRegex
{
    /**
     * match(): defined by Route interface.
     * Overriden from Http\Regex so it does not use Request#getUri,
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
        $result = preg_match('(^' . $this->regex . '$)', $path, $matches);

        if (!$result) {
            return null;
        }

        $matchedLength = strlen($matches[0]);
        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                unset($matches[$key]);
            } else {
                $matches[$key] = rawurldecode($value);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $matches), $matchedLength);
    }
}