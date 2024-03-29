<?php
namespace E4u\Application;

use E4u\Common\Variable;
use E4u\Exception\LogicException;
use E4u\Loader;
use Interop\Container\ContainerInterface;
use Laminas\View\Renderer\RendererInterface as Renderer,
    Laminas\View\Resolver\ResolverInterface as Resolver,
    ArrayObject, ArrayAccess;

/**
 * View component of the MVC architecture. It does NOT represent
 * a single file to render, but the rendering context for the set
 * of files, usually rendered during single action.
 */
abstract class View implements Renderer, Resolver, ContainerInterface
{
    const FLASH_MESSAGE = 'message',
          FLASH_ERROR   = 'error',
          FLASH_SUCCESS = 'success';

    use View\Plugins;
    use Helper\Url;

    /**
     * @var ArrayAccess
     */
    private $__vars;
    private $__partials;
    private $__controller;

    protected $_viewPath = 'application/views';
    protected $_viewSuffix;

    protected $locale;

    /**
     * @var \E4u\Request\Request
     */
    protected $request;

    /**
     * @var \E4u\Authentication\Identity
     */
    protected $current_user;

    /**
     * @return \E4u\Authentication\Identity
     */
    public function getCurrentUser()
    {
        if (null === $this->current_user) {
            $controller = $this->getController();
            if (null !== $controller) {
                $this->current_user = $controller->getCurrentUser();
            }
        }

        return $this->current_user;
    }

    /**
     * @param  \E4u\Authentication\Identity $user
     * @return $this
     */
    public function setCurrentUser($user)
    {
        $this->current_user = $user;
        return $this;
    }

    /**
     * @param  string|array $message
     * @return $this
     */
    public function addSuccessFlash($message)
    {
        return $this->addFlash($message, self::FLASH_SUCCESS);
    }

    /**
     * @param  string|array $message
     * @return $this
     */
    public function addMessageFlash($message)
    {
        return $this->addFlash($message, self::FLASH_MESSAGE);
    }

    /**
     * @param  string|array $message
     * @return $this
     */
    public function addErrorFlash($message)
    {
        return $this->addFlash($message, self::FLASH_ERROR);
    }

    /**
     * @param  string|array $message
     * @param  string $type
     * @return View
     */
    public function addFlash($message, $type = self::FLASH_MESSAGE)
    {
        if (!isset($_SESSION['flash'][ $type ])) {
            $_SESSION['flash'][ $type ] = [];
        }

        $_SESSION['flash'][ $type ][] = $this->t($message);
        return $this;
    }

    /**
     * @return array
     */
    public function getFlash()
    {
        if (empty($_SESSION['flash'])) {
            return [];
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Defined by RendererInterface.
     * @return View
     */
    public function getEngine()
    {
        return $this;
    }

    /**
     * Defined by RendererInterface.
     * @return View
     */
    public function setResolver(Resolver $resolver)
    {
        return $this;
    }

    /**
     * Defined by ResolverInterface.
     * @param string $name
     * @param Renderer $renderer
     * @return string
     */
    public function resolve($name, Renderer $renderer = null)
    {
        return $this->renderFile($name);
    }

    /**
     * Renders a file and store it in partials table.
     * Defined by RendererInterface.
     *
     * @todo  Add variables from $vars instead of replacing
     * @param  string $name
     * @param  array $vars
     * @param  string $partial
     * @return string
     */
    public function render($name, $vars = null, $partial = null)
    {
        if (is_string($vars)) {
            $partial = $vars;
            $vars = null;
        }

        $oldVars = $this->vars();
        if (!empty($vars)) {
            $this->registerVars($vars);
        }

        $content = $this->renderFile($name);
        if (null !== $partial) {
            $this->setPartial($partial, $content);
        }

        if (!empty($vars)) {
            $this->__vars = $oldVars;
        }

        return $content;
    }

    /**
     * @param  string $name
     * @param  string $content
     * @return $this
     */
    public function setPartial($name, $content)
    {
        $this->__partials[ $name ] = $content;
        return $this;
    }

    /**
     *
     * @param  string $name
     * @return string
     */
    public function getPartial($name)
    {
        if (!isset($this->__partials[ $name ])) {
            return null;
        }

        return $this->__partials[ $name ];
    }

    /**
     * Renders a single file
     *
     * @param  string $name
     * @return string
     */
    protected function renderFile($name)
    {
        try {
            $filename = $this->getFilename($name);
            if (!is_file($filename)) {
                throw new LogicException("File $filename does not exist.");
            }

            ob_start();
            include $filename;
            return ob_get_clean();
        }
        catch (LogicException $e) {
            if (!Loader::getConfig()->get('show_errors', false)) {
                return null;
            }

            return '<pre><h3>'.$e->getMessage()."</h3>\n".$e->getTraceAsString().'</pre>';
        }
    }

    protected function getFilename($viewName)
    {
        return $this->_viewPath . DIRECTORY_SEPARATOR . $viewName . $this->_viewSuffix;
    }

    /**
     * @return ArrayAccess
     */
    public function vars()
    {
        if (null === $this->__vars) {
            $this->__vars = new ArrayObject();
        }

        return $this->__vars;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->vars()->offsetExists($name)
            ? $this->vars()->offsetGet($name)
            : null;
    }

    /**
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->vars()->offsetExists($name);
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->vars()->offsetGet($name);
    }

    /**
     * Overloading: proxy to Variables container
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->vars()->offsetExists($name);
    }

    /**
     * Set variable storage
     * Expects either an array, or an object implementing ArrayAccess.
     *
     * @param  array|ArrayAccess $variables
     * @return View
     * @throws LogicException
     */
    public function registerVars($variables)
    {
        if (null === $variables) {
            return $this;
        }

        if (!is_array($variables) && !$variables instanceof ArrayAccess) {
            throw new LogicException(sprintf(
                'Expected array or ArrayAccess object; received "%s"',
                Variable::getType($variables))
            );
        }

        // Enforce ArrayAccess
        $this->__vars = new ArrayObject(array_merge(
                (array)$this->vars(),
                (array)$variables
        ));
        return $this;
    }

    /*
     * Set MVC action name
     *
     * @param  string $action
     * @return View
     */
    function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    function getAction()
    {
        return $this->action;
    }

    /*
     * Set application Controller
     * @param Controller $controller
     */
    function setController(Controller $controller)
    {
        $this->__controller = $controller;
        return $this;
    }

    /**
     * @return Controller|null
     */
    function getController()
    {
        return $this->__controller;
    }

    /**
     * @param  \E4u\Request\Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return \E4u\Request\Request
     */
    public function getRequest()
    {
        if (null === $this->request) {

            $controller = $this->getController();
            if (!is_null($controller)) {
                $this->request = $controller->getRequest();
            }
            else {
                # echo "NO CONTROLLER / REQUEST"; exit();
                $this->request = \E4u\Request\Factory::create();
            }

        }

        return $this->request;
    }

    /**
     * @return string
     */
    public function getActiveModule()
    {
        $route = $this->getController()->getRequest()->getCurrentRoute();
        return $route->getParam('module');
    }

    /**
     * @return string
     */
    public function getActiveController()
    {
        $controller = $this->getController();
        if (!$controller) {
            return null;
        }

        $route = $controller->getRequest()->getCurrentRoute();
        return $route->getParam('controller');
    }

    /**
     * @param  string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param  mixed $message
     * @param  string $locale
     * @return string
     */
    public function translate($message, $locale = null)
    {
        $message = (string)$message;
        return Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getLocale());
    }

    /**
     * @param mixed $message
     * @param array $parameters
     * @return string
     */
    public function t($message, $parameters = null)
    {
        if (is_array($message)) {
            $parameters = $message;
            $message = array_shift($parameters);
        }

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
