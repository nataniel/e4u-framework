<?php
namespace E4u\Application\Router;

use Zend\Mvc\Router\Http\Segment,
    Zend\Mvc\Router\Http\RouteMatch,
    Zend\Stdlib\RequestInterface as Request;

class Route extends Segment
{
    /**
     * match(): defined by Route interface.
     * Overriden from Http\Segment so it does not use Request#getUri,
     * but Request#getCurrentPath instead.
     *
     * @see    Route::match()
     * @param  Request $request
     * @param  int $pathOffset
     * @param  array $options
     * @return RouteMatch
     */
    public function match(Request $request, $pathOffset = null, array $options = [])
    {
        if (!method_exists($request, 'getCurrentPath')) {
            return null;
        }

        $path = $request->getCurrentPath();
        $path = urldecode(urldecode($path));

        $result = preg_match('(^' . $this->regex . '$)u', $path, $matches);
        if (!$result) {
            return null;
        }

        $params = array();
        foreach ($this->paramMap as $index => $name) {
            if (isset($matches[$index])) {
                $params[$name] = urldecode($matches[$index]);
            }
        }
        
        $matchedLength = strlen($request->getCurrentPath());
        return new RouteMatch(array_merge($this->defaults, $params), $matchedLength);
    }
}