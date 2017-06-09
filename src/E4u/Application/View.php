<?php
namespace E4u\Application;

use E4u\Loader;
use Interop\Container\ContainerInterface;
use Zend\View\Renderer\RendererInterface as Renderer,
    Zend\View\Resolver\ResolverInterface as Resolver,
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

    protected $flash = [];
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

    public function addSuccessFlash($message)
    {
        return $this->addFlash($message, self::FLASH_SUCCESS);
    }

    public function addMessageFlash($message)
    {
        return $this->addFlash($message, self::FLASH_MESSAGE);
    }

    public function addErrorFlash($message)
    {
        return $this->addFlash($message, self::FLASH_ERROR);
    }

    /**
     * @param string $message
     * @param string $type
     * @return View
     */
    public function addFlash($message, $type = self::FLASH_MESSAGE)
    {
        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }

        $_SESSION['flash'][$type][] = $message;
        return $this;
    }

    /**
     * @param string $type
     * @param string $glue
     * @return string
     */
    public function getFlash($type = null, $glue = "\n")
    {
        if (null == $this->flash) {
            if (!empty($_SESSION['flash'])) {
                $this->flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
            }
        }

        if (!empty($type)) {
            if (!empty($this->flash[$type])) {
                return join($glue, $this->flash[$type]);
            }
            else {
                return null;
            }
        }

        return $this->flash;
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
     * @param string $name
     * @param string $partial
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

    public function setPartial($name, $content)
    {
        $this->__partials[$name] = $content;
        return $this;
    }

    /**
     *
     * @param  string $name
     * @return string
     */
    public function getPartial($name)
    {
        if (!isset($this->__partials[$name])) {
            return null;
        }

        return $this->__partials[$name];
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
            $filename = $this->_viewPath.DIRECTORY_SEPARATOR.$name.$this->_viewSuffix;
            if (!is_file($filename)) {
                throw new \E4u\Exception\LogicException("File $filename does not exist.");
            }

            ob_start();
            include $filename;
            return ob_get_clean();
        }
        catch (\E4u\Exception\LogicException $e) {
            return '<pre><h3>'.$e->getMessage()."</h3>\n".$e->getTraceAsString().'</pre>';
        }
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
     * @throws \E4u\Exception\LogicException
     */
    public function registerVars($variables)
    {
        if (null === $variables) {
            return $this;
        }

        if (!is_array($variables) && !$variables instanceof ArrayAccess) {
            throw new \E4u\Exception\LogicException(sprintf(
                'Expected array or ArrayAccess object; received "%s"',
                \E4u\Common\Variable::getType($variables))
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
     * @return Controller
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
        $route = $this->getController()->getRequest()->getCurrentRoute();
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