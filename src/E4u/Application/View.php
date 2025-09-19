<?php
namespace E4u\Application;

use E4u\Authentication\Identity;
use E4u\Exception\LogicException;
use E4u\Loader;
use E4u\Request\Factory;
use E4u\Request\Request;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Renderer\RendererInterface as Renderer,
    Laminas\View\Resolver\ResolverInterface as Resolver,
    ArrayObject, ArrayAccess;

/**
 * View component of the MVC architecture. It does NOT represent
 * a single file to render, but the rendering context for the set
 * of files, usually rendered during single action.
 */
abstract class View implements Renderer, Resolver, ContainerInterface, ConfigInterface
{
    const string
        FLASH_MESSAGE = 'message',
        FLASH_ERROR   = 'error',
        FLASH_SUCCESS = 'success';

    use View\Plugins;
    use Helper\Url;

    private ArrayAccess $__vars;
    private ArrayAccess $__partials;
    private ?Controller $__controller;

    protected string $_viewPath = 'application/views';
    protected string $_viewSuffix;

    protected string $locale;

    protected Request $request;

    protected ?Identity $current_user;
    protected ?string $action;

    public function getCurrentUser(): ?Identity
    {
        if (!isset($this->current_user)) {
            $controller = $this->getController();
            if (null !== $controller) {
                $this->current_user = $controller->getCurrentUser();
            }
        }

        return $this->current_user;
    }

    public function configureServiceManager(ServiceManager $serviceManager)
    {
        
    }
    
    public function setCurrentUser(?Identity $user): static
    {
        $this->current_user = $user;
        return $this;
    }

    public function addSuccessFlash(string|array $message): static
    {
        return $this->addFlash($message, self::FLASH_SUCCESS);
    }

    public function addMessageFlash(string|array $message): static
    {
        return $this->addFlash($message, self::FLASH_MESSAGE);
    }

    public function addErrorFlash(string|array $message): static
    {
        return $this->addFlash($message, self::FLASH_ERROR);
    }

    public function addFlash(string|array $message, string $type = self::FLASH_MESSAGE): static
    {
        if (!isset($_SESSION['flash'][ $type ])) {
            $_SESSION['flash'][ $type ] = [];
        }

        $_SESSION['flash'][ $type ][] = $this->t($message);
        return $this;
    }

    public function getFlash(): array
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
     */
    public function getEngine(): static
    {
        return $this;
    }

    /**
     * Defined by RendererInterface.
     */
    public function setResolver(Resolver $resolver): static
    {
        return $this;
    }

    /**
     * Defined by ResolverInterface.
     */
    public function resolve($name, ?Renderer $renderer = null): string
    {
        return $this->renderFile($name);
    }

    /**
     * Renders a file and store it in partials table.
     * Defined by RendererInterface.
     */
    public function render($nameOrModel, $values = null, $partial = null): ?string
    {
        if (is_string($values)) {
            $partial = $values;
            $values = null;
        }

        $oldVars = $this->vars();
        if (!empty($values)) {
            $this->registerVars($values);
        }

        $content = $this->renderFile($nameOrModel);
        if (null !== $partial) {
            $this->setPartial($partial, $content);
        }

        if (!empty($values)) {
            $this->__vars = $oldVars;
        }

        return $content;
    }

    public function setPartial(string $name, string $content): static
    {
        $this->__partials[ $name ] = $content;
        return $this;
    }

    public function getPartial(string $name): ?string
    {
        if (!isset($this->__partials[ $name ])) {
            return null;
        }

        return $this->__partials[ $name ];
    }

    /**
     * Renders a single file
     */
    protected function renderFile(string $name): ?string
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

    protected function getFilename($viewName): string
    {
        return $this->_viewPath . DIRECTORY_SEPARATOR . $viewName . $this->_viewSuffix;
    }

    public function vars(): ArrayAccess
    {
        if (!isset($this->__vars)) {
            $this->__vars = new ArrayObject();
        }

        return $this->__vars;
    }
    
    public function get($id): mixed
    {
        return $this->vars()->offsetExists($id)
            ? $this->vars()->offsetGet($id)
            : null;
    }

    public function has($id): bool
    {
        return $this->vars()->offsetExists($id);
    }

    public function __get(string $name): mixed
    {
        return $this->vars()->offsetGet($name);
    }

    /**
     * Overloading: proxy to Variables container
     */
    public function __isset(string $name): bool
    {
        return $this->vars()->offsetExists($name);
    }

    /**
     * Set variable storage
     * Expects either an array, or an object implementing ArrayAccess.
     */
    public function registerVars(array|ArrayAccess $variables): static
    {
        if (empty($variables)) {
            return $this;
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
    function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    function getAction(): ?string
    {
        return $this->action;
    }

    /*
     * Set application Controller
     */
    function setController(Controller $controller): static
    {
        $this->__controller = $controller;
        return $this;
    }

    function getController(): ?Controller
    {
        return $this->__controller;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest(): Request
    {
        if (!isset($this->request)) {

            $controller = $this->getController();
            if (!is_null($controller)) {
                $this->request = $controller->getRequest();
            }
            else {
                # echo "NO CONTROLLER / REQUEST"; exit();
                $this->request = Factory::create();
            }

        }

        return $this->request;
    }

    public function getActiveModule(): ?string
    {
        $route = $this->getController()->getRequest()->getCurrentRoute();
        return $route->getParam('module');
    }

    public function getActiveController(): ?string
    {
        $controller = $this->getController();
        if (!$controller) {
            return null;
        }

        $route = $controller->getRequest()->getCurrentRoute();
        return $route->getParam('controller');
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function translate(mixed $message, ?string $locale = null): string
    {
        $message = (string)$message;
        return Loader::getTranslator()->translate($message, 'default', $locale ?: $this->getLocale());
    }

    public function t(mixed $message, ?array $parameters = null): string
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
