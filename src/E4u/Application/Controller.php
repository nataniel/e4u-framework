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

    const int
        ACCESS_ADMIN = 1,
        ACCESS_USER  = 2,
        ACCESS_ALL = 255;

    protected array $requiredPrivileges = [];

    protected Config $_config;

    protected Authentication\Resolver $_authentication;

    /**
     * Should be populated via dispatch()
     */
    protected Request\Request $_request;

    protected string $_locale;

    protected View $_view;
    protected string $viewClass = View\Html::class;

    protected string $currentLayout;
    protected string $defaultLayout =  'layout/default';

    protected bool $layoutEnabled;

    /**
     * To render the action view or not?
     * If renderView is false, the default response object
     * will be E4u\Response\Debug populated with the action result.
     */
    protected bool $renderView = true;

    public function __construct(Config $config)
    {
        $this->_config = $config;
    }

    public function getConfig(): Config
    {
        return $this->_config;
    }

    public function isXhr(): bool
    {
        return $this->getRequest() instanceof \E4u\Request\Xhr;
    }

    /**
     * @deprecated Use new Response\Xhr instead
     */
    protected function sendXhrResponse(mixed $content, ?array $data = null): Response\Xhr
    {
        return new Response\Xhr($content, $data);
    }

    /**
     * @throws Controller\Redirect
     */
    protected function redirectBackOrTo(mixed $target, null|string|array $message = null, string $type = View::FLASH_MESSAGE): void
    {
        if ($back = $this->getRequest()->getQuery('back')) {
            $target = $back;
        }

        $this->redirectTo($target, $message, $type);
    }

    /**
     * @throws Controller\Redirect
     */
    protected function redirectTo(mixed $target, null|string|array $message = null, string $type = View::FLASH_MESSAGE): void
    {
        if (!empty($message)) {
            $this->getView()->addFlash($message, $type);
        }

        $exception = new Controller\Redirect();
        throw $exception->setUrl($target);
    }

    /**
     * @throws Controller\Redirect
     */
    protected function redirectToSelf(null|string|array $message = null, string $type = View::FLASH_MESSAGE): void
    {
        $this->redirectTo($this->currentUrl(), $message, $type);
    }

    public function getParams(): array
    {
        return $this->getRequest()->getCurrentRoute()->getParams();
    }

    public function getParam(string $name, mixed $default = null): mixed
    {
        $params = $this->getParams();
        return $params[$name] ?? $default;
    }

    public function getCurrentUser(): ?Authentication\Identity
    {
        return $this->getAuthentication()->getCurrentUser();
    }

    /**
     * @todo   Use interface instead of actual resolver class
     */
    public function setAuthentication(Authentication\Resolver $authentication): void
    {
        $this->_authentication = $authentication;
    }

    public function getAuthentication(): Authentication\Resolver
    {
        if (!isset($this->_authentication)) {
            $resolver = new Authentication\Resolver($this->getRequest(), $this->getConfig()->get('authentication'));
            $this->setAuthentication($resolver);
        }

        return $this->_authentication;
    }

    protected function denyAccess(null|string|array $message = null): void
    {
        if (!empty($message)) {
            $this->getView()->addFlash($message, View::FLASH_ERROR);
        }

        $loginPath = $this->getAuthentication()->getLoginPath();
        $url = $loginPath . '?back=' . $this->backUrl();
        $this->redirectTo($url);
    }

    /**
     * Dispatch a request
     */
    public function dispatch(Request\Request $request): Response\Response
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
                $this->denyAccess(!empty($user) ? 'Nie masz uprawnień do wybranego zasobu.' : null);

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
     */
    protected function init(?string $action = null): ?Response\Response
    {
        return null;
    }

    /**
     * Invoke view for an action
     */
    protected function renderView(string $action, null|array|ArrayAccess $vars = null): Response\Response
    {
        if ($view = $this->getView()) {
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
        else {
            // If no view defined ($this->renderView === false),
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
     * @deprecated Use renderView instead
     */
    protected function render(string $action, null|array|ArrayAccess $vars = null): Response\Response
    {
        return $this->renderView($action, $vars);
    }

    protected function detectCurrentLocale(): string
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

    public function getCurrentLocale(): string
    {
        if (!isset($this->_locale)) {
            $this->_locale = $this->detectCurrentLocale();
        }

        return $this->_locale;
    }

    public function setView(View $view): static
    {
        $view->setController($this)
             ->setLocale($this->getCurrentLocale());

        $this->_view = $view;
        return $this;
    }

    /**
     * Get the view object
     */
    public function getView(): View
    {
        if (!isset($this->_view) && $this->renderView) {
            $this->setView(new $this->viewClass);
        }
        
        return $this->_view;
    }

    public function getCurrentLayout(): string
    {
        if (!isset($this->currentLayout)) {
            $this->currentLayout = $this->defaultLayout;
        }

        return $this->currentLayout;
    }

    public function setLayout(string $layout): static
    {
        $this->currentLayout = $layout;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isLayoutEnabled(): bool
    {
        if (!isset($this->layoutEnabled)) {
            $this->layoutEnabled = !$this->isXhr();
        }

        return $this->layoutEnabled;
    }

    public function disableLayout(): static
    {
        $this->layoutEnabled = false;
        return $this;
    }

    public function disableView(): static
    {
        $this->disableLayout();
        $this->renderView = false;
        return $this;
    }

    protected function getActionPath(string $action): string
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
     */
    public function getActionName(): string
    {
        $routeMatch = $this->getRequest()->getCurrentRoute();
        return $routeMatch->getParam('action', 'index');
    }

    /**
     * Default action if none provided
     */
    public function indexAction(): array
    {
        return [ 'content' => 'It works!' ];
    }

    /**
     * Get the request object
     */
    public function getRequest(): Request\Request
    {
        if (!isset($this->_request)) {
            throw new LogicException('No valid Request set.');
        }

        return $this->_request;
    }

    /**
     * Get the response object
     */
    public function getDefaultResponse(): Response\Xhr|Response\Http
    {
        return $this->isXhr()
            ? new Response\Xhr()
            : new Response\Http();
    }

    /**
     * Transform an action name into a method name
     */
    public static function getMethodFromAction(string $action): string
    {
        $method  = StringTools::camelCase($action);
        $method  = lcfirst($method);
        $method .= 'Action';
        return $method;
    }

    public function translate(mixed $message, ?string $locale = null): string
    {
        $message = (string)$message;
        return \E4u\Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getCurrentLocale());
    }

    public function t(mixed $message, ?array $parameters = null): string
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