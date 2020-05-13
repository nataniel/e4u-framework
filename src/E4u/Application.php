<?php
namespace E4u;

use E4u\Application\Controller;
use E4u\Exception\LogicException;
use E4u\Request\Request,
    E4u\Response\Response,
    E4u\Application\Exception,
    Laminas\Config\Config,
    Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch;

class Application
{
    protected $request;
    protected $router;

    /**
     * @var Config
     */
    protected $config;

    public function __construct($config = null)
    {
        if ($config) {
            $this->setConfig($config);
        }

        if (PHP_SAPI != 'cli') {
            $name = $this->config->get('session_name', 'E4uSession');
            session_set_cookie_params(0, $this->getRequest()->getBaseUrl() ?: '/');
            session_name($name);
            session_start();
        }
    }

    /**
     * @param  Config|array $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = ($config instanceof Config)
            ? $config
            : new Config((array)$config);
        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        if (null == $this->config) {
            $this->config = new Config([]);
        }

        return $this->config;
    }

    /**
     * MUST return Application object
     * @return $this
     */
    protected function init()
    {
        return $this;
    }

    /**
     * @return Response
     */
    public function run()
    {
        try {

            $request = $this->getRequest();
            $this->init()->route($request);
            return $this->dispatch();

        }
        catch (Application\Exception\PageNotFound $e) {

            return $this->notFoundException($e);

        }
        catch (\Exception $e) {

            return $this->invalidException($e);

        }
    }

    /**
     * 404 Not Found
     * @param  \Exception $e
     * @return Response
     */
    protected function notFoundException(\Exception $e)
    {
        $this->addToLog($e, 'not-found.log');
        return $this->dispatch([
            'controller' => 'errors',
            'action' => 'not-found',
        ]);
    }

    /**
     * 500 Internal Server Error
     * @param  \Exception $e
     * @return Response
     */
    protected function invalidException(\Exception $e)
    {
        if ($this->getConfig()->show_errors) {
            echo '<pre>'; echo $e;
            exit();
        }

        $this->addToLog($e, 'invalid.log');
        return $this->dispatch([
            'controller' => 'errors',
            'action' => 'invalid',
        ]);
    }

    /**
     * @param  \Exception $e
     * @param  string $filename
     * @return $this
     */
    protected function addToLog($e, $filename = 'application.log')
    {
        $message = sprintf("%s %s:\nREFERER: %s\nUSER_AGENT: %s\nREMOTE_ADDR: %s\nERROR %s\n%s\n\n",
            date('d.m.Y H:i:s'),
            $this->getRequest()->getCurrentPath(),
            isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            $e->getMessage(),
            $e->getTraceAsString());
        file_put_contents('logs/' . $filename, $message, FILE_APPEND);
        return $this;
    }

    /**
     * @param  Request|array $request
     * @return $this
     */
    public function route($request)
    {
        $route = $request instanceof Request
            ? $this->getRouter()->match($request)
            : new RouteMatch($request);
        if ($route instanceof RouteMatch) {
            $this->getRequest()->setCurrentRoute($route);
        }

        return $this;
    }

    /**
     * Rely on convention over configuration.
     *
     * All controllers should be located in the application
     * namespace (defined as APPLICATION constant, for example "My")
     * and follow the naming convention:
     * /site/pages  -> My\Controller\Site\PagesController
     * /admin/users -> My\Controller\Admin\UsersController
     * /security    -> My\Controller\SecurityController
     *
     * @param  array $params
     * @return Response
     */
    public function dispatch($params = null)
    {
        $request = $this->getRequest();
        if (!empty($params)) {
            $route = new RouteMatch($params);
            $this->getRequest()->setCurrentRoute($route);
        }

        $controller = $this->getController();
        if (!$controller instanceof Controller) {
            throw new LogicException(
                sprintf('Controller must be an instance of E4u\Application\Controller, %s given.',
                Common\Variable::getType($controller)));
        }

        $response = $controller->dispatch($request);
        return $response;
    }

    /**
     * Convention over configuration.
     *
     * @see    dispatch
     * @return Application\Controller
     */
    function getController()
    {
        $routeMatch = $this->getRequest()->getCurrentRoute();
        if (!$routeMatch instanceof RouteMatch) {
            throw new Exception\NoRouteMatch(
                "No route match for {$this->getRequest()->getCurrentPath()}.");
        }

        $moduleName     = $routeMatch->getParam('module');
        $controllerName = $routeMatch->getParam('controller', 'index');
        $controllerClass = $this->getClassFromController($controllerName, $moduleName);

        if (!class_exists($controllerClass))
        {
            throw new Exception\NoControllerClass(
                sprintf("No class %s found for controller %s in %s.",
                $controllerClass, $controllerName, $this->getRequest()->getCurrentPath()));
        }

        // setup controller
        return new $controllerClass($this->getConfig());
    }

    /**
     * Set the request object
     *
     * @param  Request $request
     * @return Application Current instance
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the request object. If no object available,
     * then create and configure one.
     *
     * @return Request
     */
    public function getRequest()
    {
        if (!$this->request instanceof Request) {
            $this->request = \E4u\Request\Factory::create();
            if ($routes = $this->getConfig()->get('routes')) {
                $this->request->getRouter()->addRoutes($routes->toArray());
            }
        }

        return $this->request;
    }

    /**
     * Get the router object from request.
     *
     * @return RouteStackInterface
     */
    public function getRouter()
    {
        return $this->getRequest()->getRouter();
    }

    /**
     * Transform a module/controller name into a class name
     *
     * @param  string $controller
     * @param  string $module
     * @return string
     */
    public function getClassFromController($controller, $module = null)
    {
        $class  = str_replace(['.', '-', '_'], ' ', $controller);
        $class  = ucwords($class);
        $class  = str_replace(' ', '', $class);
        $class  = $class.'Controller';

        if (null !== $module) {
            $module  = str_replace(['.', '-', '_'], ' ', $module);
            $module  = ucwords($module);
            $module  = str_replace(' ', '', $module);
        }

        $class = (null !== $module)
            ? $this->getConfig()->namespace."\\Controller\\$module\\$class"
            : $this->getConfig()->namespace."\\Controller\\$class";
        return $class;
    }
}
