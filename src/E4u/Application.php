<?php
namespace E4u;

use E4u\Request\Factory;
use E4u\Request\Request,
    E4u\Response\Response,
    E4u\Application\Exception,
    Laminas\Config\Config,
    Laminas\Mvc\Router\RouteStackInterface,
    Laminas\Mvc\Router\RouteMatch;

class Application
{
    protected Request $request;
    protected RouteStackInterface $router;

    protected Config $config;

    public function __construct(Config|array|null $config = null)
    {
        $this->setConfig($config ?: []);

        if (PHP_SAPI != 'cli') {
            $name = $this->config->get('session_name', 'E4uSession');
            session_set_cookie_params(0, $this->getRequest()->getBaseUrl() ?: '/');
            session_name($name);
            session_start();
        }
    }

    public function setConfig(Config|array $config): void
    {
        $this->config = ($config instanceof Config)
            ? $config
            : new Config((array)$config);
    }

    public function getConfig(): Config
    {
        if (!isset($this->config)) {
            $this->config = new Config([]);
        }

        return $this->config;
    }

    /**
     * MUST return Application object
     * @return $this
     */
    protected function init(): static
    {
        return $this;
    }

    public function run(): Response
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
     */
    protected function notFoundException(\Exception $e, int $status = 404): Response
    {
        if ($this->getConfig()->get('show_errors', false)) {
            echo '<pre>'; echo $e;
            exit();
        }

        $this->addToLog($e, 'not-found-%s.log');
        $response = $this->dispatch([
            'controller' => 'errors',
            'action' => 'not-found',
        ]);

        return $response->setStatus($status);
    }

    /**
     * 500 Internal Server Error
     */
    protected function invalidException(\Exception $e, int $status = 500): Response
    {
        if ($this->getConfig()->get('show_errors', false)) {
            echo '<pre>'; echo $e;
            exit();
        }

        $this->addToLog($e, 'invalid-%s.log');
        $response = $this->dispatch([
            'controller' => 'errors',
            'action' => 'invalid',
        ]);

        return $response->setStatus($status);
    }

    protected function addToLog(\Exception $e, string $filename = 'application-%s.log'): void
    {
        $filename = sprintf($filename, date('Y-m-d'));
        $message = sprintf("%s %s:\nREFERER: %s\nUSER_AGENT: %s\nREMOTE_ADDR: %s\nERROR: %s - %s\n%s\n\n",
            date('d.m.Y H:i:s'),
            $this->getRequest()->getCurrentPath(),
            $_SERVER['HTTP_REFERER'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_TRUE_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            get_class($e),
            $e->getMessage(),
            $e->getTraceAsString());
        file_put_contents('logs/' . $filename, $message, FILE_APPEND);
    }

    public function route(Request|array $request): void
    {
        $route = $request instanceof Request
            ? $this->getRouter()->match($request)
            : new RouteMatch($request);
        if ($route instanceof RouteMatch) {
            $this->getRequest()->setCurrentRoute($route);
        }
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
     */
    public function dispatch(?array $params = null): Response
    {
        $request = $this->getRequest();
        if (!empty($params)) {
            $route = new RouteMatch($params);
            $this->getRequest()->setCurrentRoute($route);
        }

        return $this->getController()->dispatch($request);
    }

    /**
     * Convention over configuration.
     *
     * @see    dispatch
     */
    function getController(): Application\Controller
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
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the request object. If no object available,
     * then create and configure one.
     */
    public function getRequest(): Request
    {
        if (!isset($this->request)) {
            $this->request = Factory::create();
            if ($routes = $this->getConfig()->get('routes')) {
                $this->request->getRouter()->addRoutes($routes->toArray());
            }
        }

        return $this->request;
    }

    /**
     * Get the router object from request.
     */
    public function getRouter(): RouteStackInterface
    {
        return $this->getRequest()->getRouter();
    }

    /**
     * Transform a module/controller name into a class name
     */
    public function getClassFromController(string $controller, ?string $module = null): string
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

        return (null !== $module)
            ? $this->getConfig()->get('namespace')."\\Controller\\$module\\$class"
            : $this->getConfig()->get('namespace')."\\Controller\\$class";
    }
}
