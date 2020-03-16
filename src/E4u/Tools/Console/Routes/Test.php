<?php
namespace E4u\Tools\Console\Routes;

use E4u\Tools\Console\Base;
use Laminas\Mvc\Router\RouteMatch;

class Test extends Base
{
    public function help()
    {
        return [
            '/show/page'   => 'Show action/controller and other RouteMatch params for a specified path',
        ];
    }
    
    public function execute()
    {
        $path = $this->getArgument(0);
        if (empty($path)) {
            $this->showHelp();
            return false;
        }

        echo sprintf("Current path set to: %s\n", $path);
        
        $request = new \E4u\Request\Test();
        $app = \E4u\Loader::get(APPLICATION);
        $app->setRequest($request);
        
        $request->setCurrentPath($path);
        
        $router  = $app->getRouter();
        $routeMatch = $router->match($request);
        
        if (!$routeMatch instanceof RouteMatch) {
            echo sprintf("NO MATCH!\n");
            return false;
        }
        
        $params = $routeMatch->getParams();
        
        echo sprintf("Matched route: %s\n\n", $routeMatch->getMatchedRouteName());
        foreach ($params as $key => $value) {
            echo sprintf("  %s: %s\n", $key, $value);
        }

        return $this;
    }
}