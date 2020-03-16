<?php
namespace E4u\Application;

use E4u\Exception\LogicException;

use Laminas\Config\Config,
    Laminas\Stdlib\Message;

use E4u\Authentication,
    E4u\Common\StringTools,
    E4u\Request,
    E4u\Response;

use ArrayAccess;

abstract class Controller
{
    use Helper\Url;

    const ACCESS_ADMIN = 1;
    const ACCESS_USER  = 2;
    const ACCESS_ALL = 255;

    protected $requiredPrivileges = [];

    /**
     * @var Config
     */
    protected $_config;

    /**
     * Authentication resolver.
     * @var Authentication\Resolver
     */
    protected $_authentication;

    /**
     * Should be populated via dispatch()
     * @var Request\Request
     */
    protected $_request;

    /**
     * @var string
     */
    protected $_locale;

    /**
     * @var View
     */
    protected $_view;
    protected $viewClass = View\Html::class;

    /**
     * @var string
     */
    protected $currentLayout;
    protected $defaultLayout =  'layout/default';

    /**
     * Is layout enabled?
     * @var boolean
     */
    protected $layoutEnabled;

    /**
     * To render the action view or not?
     * If renderView is false, the default response object
     * will be E4u\Response\Debug populated with the action result.
     *
     * @var boolean
     */
    protected $renderView = true;

    public function __construct(Config $config)
    {
        $this->_config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @return boolean
     */
    public function isXhr()
    {
        return $this->getRequest() instanceof \E4u\Request\Xhr;
    }

    /**
     * @deprecated Use new Response\Xhr instead
     * @param  mixed $content
     * @param  array  $data
     * @return Response\Xhr|Message
     */
    protected function sendXhrResponse($content, $data = null)
    {
        return new Response\Xhr($content, $data);
    }

    /**
     * @param  array|string $target
     * @param  string $message
     * @param  string $type
     * @return Response\Redirect
     */
    protected function redirectBackOrTo($target, $message = null, $type = View::FLASH_MESSAGE)
    {
        if ($back = $this->getRequest()->getQuery('back')) {
            $target = $back;
        }

        return $this->redirectTo($target, $message, $type);
    }

    /**
     * @param  array|string $target
     * @param  array|string $message
     * @param  string $type
     * @return Response\Redirect
     * @throws Controller\Redirect
     */
    protected function redirectTo($target, $message = null, $type = View::FLASH_MESSAGE)
    {
        if (!empty($message)) {
            $this->getView()->addFlash($message, $type);
        }

        $exception = new Controller\Redirect();
        throw $exception->setUrl($target);
    }

    /**
     * @param  array|string $message
     * @param  string $type
     * @return Response\Redirect
     * @throws Controller\Redirect
     */
    protected function redirectToSelf($message = null, $type = View::FLASH_MESSAGE)
    {
        return $this->redirectTo($this->currentUrl(), $message, $type);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->getRequest()->getCurrentRoute()->getParams();
    }

    /**
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        $params = $this->getParams();
        return isset($params[$name])
               ? $params[$name]
               : $default;
    }

    /**
     * @return Authentication\Identity
     */
    public function getCurrentUser()
    {
        return $this->getAuthentication()->getCurrentUser();
    }

    /**
     * @todo   Use interface instead of actual resolver class
     * @param  Authentication\Resolver $authentication
     * @return $this
     */
    public function setAuthentication(Authentication\Resolver $authentication)
    {
        $this->_authentication = $authentication;
        return $this;
    }

    /**
     * @return Authentication\Resolver
     */
    public function getAuthentication()
    {
        if (null == $this->_authentication) {
            $resolver = new Authentication\Resolver($this->getRequest(), $this->getConfig()->get('authentication'));
            $this->setAuthentication($resolver);
        }

        return $this->_authentication;
    }

    /**
     * @param  string|null $message
     * @throws Controller\Redirect
     * @return Response\Redirect
     */
    protected function denyAccess($message = null)
    {
        if (!empty($message)) {
            $this->getView()->addFlash($message, View::FLASH_ERROR);
        }

        $loginPath = $this->getAuthentication()->getLoginPath();
        $url = $loginPath . '?back=' . $this->backUrl();
        return $this->redirectTo($url);
    }

    /**
     * Dispatch a request
     *
     * @param  Request\Request $request
     * @return Response\Response
     */
    public function dispatch(Request\Request $request)
    {
        $this->_request = $request;
        $action = $this->getActionName();
        $method = static::getMethodFromAction($action);

        if (!method_exists($this, $method)) {
            throw new Exception\NoMethodForAction("No method for '$action' action.");
        }

        try {

            // check autorization status for current action
            $authResult = $this->getAuthentication()->checkPrivileges($this->requiredPrivileges, $action);
            if (false === $authResult) {

                $user = $this->getAuthentication()->getCurrentUser();
                return $this->denyAccess(!empty($user) ? 'Nie masz uprawnień do wybranego zasobu.' : null);

            }

            // perform init() method if declared, then perform the selected
            // action if init() has returned no response
            $actionResult = $this->init($action) ?: $this->$method();

            // if we already get a Response, just send it back
            if ($actionResult instanceof Response\Response) {
                return $actionResult;
            }

            // otherwise, render the selected action into a response
            return $this->renderView($action, $actionResult);

        } catch (Controller\Redirect $e) {

            $url = $this->urlTo($e->getUrl());
            return new Response\Redirect($url);

        }
    }

    /**
     * Placeholder to be overloaded in extending classes,
     * executed during the dispatch(), just before the action method.
     *
     * If returns Response object, the return value will be passed
     * to the application, otherwise the action result will.
     *
     * @return null|Response\Response
     */
    protected function init($action)
    {
        return null;
    }

    /**
     * Invoke view for an action
     *
     * @param  string $action
     * @param  array|ArrayAccess $vars
     * @return Response\Response
     */
    protected function renderView($action, $vars = null)
    {
        if ($view = $this->getView())
        {
            // render a view for selected action
            // and place it into the "content" partial
            $view->setAction($action);
            $script = $this->getActionPath($action);
            $content = $view->render($script, $vars, 'content');

            // If layout is enabled, render the layout file
            // and send it back instead of action view.
            // The layout file should use the "content" partial
            // somewhere, to include the action view.
            if ($this->isLayoutEnabled() && ($layout = $this->getCurrentLayout())) {
                $content = $view->render($layout, $vars);
            }
        }
        else
        {
            // If no view defined ($this->renderView == false),
            // just pass the action result to the response.
            $content = (($vars instanceof ArrayAccess) && isset($vars['content']))
                ? $vars['content']
                : $vars;
        }

        // attach to response
        $response = $this->getDefaultResponse();
        $response->setContent($content);

        return $response;
    }

    /**
     * Invoke view for an action
     *
     * @deprecated Use renderView instead
     */
    protected function render($action, $vars = null)
    {
        return $this->renderView($action, $vars);
    }

    /**
     * @return string
     */
    protected function detectCurrentLocale()
    {
        if (!empty($_REQUEST['locale'])) {
            return $_REQUEST['locale'];
        }

        if (!empty($_SESSION['locale'])) {
            return $_SESSION['locale'];
        }

        if ($user = $this->getCurrentUser()) {
            if ($locale = $user->getLocale()) {
                return $locale;
            }
        }

        return \E4u\Loader::getConfig()->get('default_locale')
            ?: strtok(\E4u\Loader::getTranslator()->getLocale(), '_');
    }

    /**
     * @return string
     */
    public function getCurrentLocale()
    {
        if (null === $this->_locale) {
            $this->_locale = $this->detectCurrentLocale();
        }

        return $this->_locale;
    }

    /**
     * @param  View $view
     * @return $this
     */
    public function setView(View $view)
    {
        $view->setController($this)
             ->setLocale($this->getCurrentLocale());

        $this->_view = $view;
        return $this;
    }

    /**
     * Get the view object
     *
     * @return View
     */
    public function getView()
    {
        if (!($this->_view instanceof View)
           && $this->renderView)
        {
            $this->setView(new $this->viewClass);
        }
        return $this->_view;
    }

    /**
     * @return string
     */
    public function getCurrentLayout()
    {
        if (null === $this->currentLayout) {
            $this->currentLayout = $this->defaultLayout;
        }

        return $this->currentLayout;
    }

    /**
     * @param  string $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->currentLayout = $layout;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isLayoutEnabled()
    {
        if (null === $this->layoutEnabled) {
            $this->layoutEnabled = $this->isXhr() ? false : true;
        }

        return $this->layoutEnabled;
    }

    /**
     * @return $this
     */
    public function disableLayout()
    {
        $this->layoutEnabled = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableView()
    {
        $this->disableLayout();
        $this->renderView = false;
        return $this;
    }

    /**
     * @param  string $action
     * @return string
     */
    protected function getActionPath($action)
    {
        $route = $this->getRequest()->getCurrentRoute();
        $segments = array
        (
            $route->getParam('module'),
            $route->getParam('controller', 'index'),
            $action,
        );

        return join(DIRECTORY_SEPARATOR, array_filter($segments));
    }

    /**
     * Pulls action name from current request.
     *
     * @return string
     */
    public function getActionName()
    {
        $routeMatch = $this->getRequest()->getCurrentRoute();
        return $routeMatch->getParam('action', 'index');
    }

    /**
     * Default action if none provided
     *
     * @return array
     */
    public function indexAction()
    {
        return [ 'content' => 'It works!' ];
    }

    /**
     * Get the request object
     *
     * @return Request\Http|Request\Request
     */
    public function getRequest()
    {
        if (!$this->_request instanceof Request\Request) {
            throw new LogicException('No valid Request set.');
        }

        return $this->_request;
    }

    /**
     * Get the response object
     *
     * @return Response\Xhr|Response\Http
     */
    public function getDefaultResponse()
    {
        return $this->isXhr()
            ? new Response\Xhr()
            : new Response\Http();
    }

    /**
     * Transform an action name into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $method  = StringTools::camelCase($action);
        $method  = lcfirst($method);
        $method .= 'Action';
        return $method;
    }

    /**
     * @param  mixed $message
     * @param  string $locale
     * @return string
     */
    public function translate($message, $locale = null)
    {
        $message = (string)$message;
        return \E4u\Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getCurrentLocale());
    }

    /**
     * @param  mixed $message
     * @param  array $parameters
     * @return string
     */
    public function t($message, $parameters = null)
    {
        $txt = $this->translate($message);
        if (!empty($parameters)) {

            if (!is_array($parameters)) {
                $parameters = func_get_args();
                array_shift($parameters);
            }

            return vsprintf($txt, $parameters);
        }

        return $txt;
    }
}