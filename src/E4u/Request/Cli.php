<?php
namespace E4u\Request;

use E4u\Exception\LogicException;
use E4u\Request\Request as RequestDescription;
use Laminas\Stdlib\Message,
    Laminas\Mvc\Router\SimpleRouteStack,
    Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch,
    Laminas\Console\Getopt;

class Cli extends Message implements RequestDescription
{
    /** @var RouteStackInterface */
    protected $router;

    /** @var RouteMatch */
    protected $currentRoute;

    /** @var Getopt */
    protected $options;

    /**
     * @return Getopt
     */
    public function getOpt()
    {
        if (null == $this->options)
        {
            $rules = [
                'help|h' => 'This help message.',
                'verbose|v-i' => 'Print all messages.',
            ];

            $options = [ 'freeformFlags' => true ];
            $this->options = new Getopt($rules, null, $options);
        }

        return $this->options;
    }

    public function getOption($flag)
    {
        return $this->getOpt()->getOption($flag);
    }

    public function getPost($name = null, $default = null)
    {
        throw new LogicException('POST values are not available with CLI Request.');
    }

    public function getQuery($name = null, $default = null)
    {
        throw new LogicException('GET values are not available with CLI Request.');
    }

    public function getFiles($name = null, $default = null)
    {
        throw new LogicException('FILES are not available with CLI Request.');
    }

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
            $this->router = new SimpleRouteStack();
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
        $arguments = $this->getOpt()->getRemainingArgs();
		if (!empty($arguments[0])) {
			return $arguments[0];
		}

        return '/';
    }

    public function getBaseUrl()
    {
        $config = \E4u\Loader::getConfig();
        return $config->get('base_url', '/');
    }

    public function getFullUrl()
    {
        return $this->getBaseUrl();
    }
}