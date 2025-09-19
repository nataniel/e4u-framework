<?php
namespace E4u\Authentication;

use Laminas\Config\Config;
use E4u\Request\Request,
    E4u\Exception\LogicException;

class Resolver
{
    const string DEFAULT_PATH  = 'security/login';
    const string COOKIE_NAME = 'E4uAuthentication';
    const int COOKIE_LIFETIME = 2592000; // 30 days

    protected ?Identity $currentUser;
    protected Request $request;
    protected Config $config;


    public function __construct(Request $request, ?Config $config = null)
    {
        $this->request = $request;
        $this->config  = $config ?? new Config([]);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLoginPath(): string
    {
        return $this->getConfig()->get('login', self::DEFAULT_PATH);
    }

    public function getIdentityModel(): ?string
    {
        $class = $this->getConfig()->get('model');
        if (!is_null($class) && !class_exists($class)) {
            throw new LogicException(
                "Identity class defined by authentication config $class does not exist.");
        }

        return $class;
    }

    public function getCookieName(): string
    {
        return $this->getConfig()->get('cookie_name', self::COOKIE_NAME);
    }

    public function getCookieLifetime(): int
    {
        return (int)$this->getConfig()->get('cookie_lifetime', self::COOKIE_LIFETIME);
    }

    /**
     * Read cookies / headers from the request and return
     * authorized user if found in request.
     */
    public function authenticate(): bool
    {
        $class = $this->getIdentityModel();
        if (is_null($class)) {
            return false;
        }

        // auto-login from Session
        if (!empty($_SESSION['current_user'])) {
            $user = $class::findByID((int)$_SESSION['current_user']);
            if ($user instanceof Identity) {
                $this->loginAs($user);
                return true;
            }
        }

        // auto-login from Cookie
        if (isset($_COOKIE[ $this->getCookieName() ])) {
            $user = $class::findByCookie($_COOKIE[ $this->getCookieName() ]);
            if ($user instanceof Identity) {
                $this->loginAs($user, true);
                return true;
            }
        }

        return false;
    }

    private function setCookie(string $value, ?int $expiration = null): void
    {
        if (null === $expiration) {
            $expiration = $this->getCookieLifetime();
        }

        $path = $this->request->getBaseUrl() ?: '/';
        setcookie($this->getCookieName(), $value, time() + $expiration, $path);
        if (empty($value) || $expiration < 0) {
            unset($_COOKIE[ $this->getCookieName() ]);
        }
    }

    public function logout(bool $removeSession = true): void
    {
        $this->authenticate();
        unset($_SESSION['current_user']);

        if ($removeSession)
            session_regenerate_id(true);

        $this->currentUser = null;
        $this->setCookie('', -3600);
    }

    public function loginAs(Identity $user, bool $remember = false): void
    {
        // add user info to session
        $_SESSION['current_user'] = $user->id();

        // setup current user object
        $this->currentUser = $user;

        // set persistent cookie
        if ($remember && ($cookie = $user->getCookie())) {
            $this->setCookie($cookie);
        }
    }

    /**
     * Try to authenticate the user using provided login and password.
     * Return authenticated user if login/password match.
     * Return NULL if user not found or FALSE if password is invalid.
     * Set a cookie in user's browser if $remember is TRUE.
     * @link http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice/
     */
    public function login(string $login, string $password, bool $remember = false): ?Identity
    {
        $class = $this->getIdentityModel();
        $user = $class::login($login, $password);
        if ($user instanceof Identity) {
            $this->loginAs($user, $remember);
            return $user;
        }

        return null;
    }

    public function getCurrentUser(): ?Identity
    {
        if (!isset($this->currentUser)) {
            $this->authenticate();
        }

        return $this->currentUser;
    }

    /**
     * Returns FALSE if the current user does not meet
     * any privileges required by controller for current action.
     */
    public function checkPrivileges(int|bool|iterable $requiredPrivileges, ?string $action = null): bool
    {
        if (!is_iterable($requiredPrivileges)) {
            $requiredPrivileges = [ $requiredPrivileges ];
        }

        foreach ($requiredPrivileges as $key => $params)
        {
            if ((is_int($params) && ($key = $params))
              || (is_bool($params) && ($key = $params))
              || (is_string($params) && ($params == $action))
              || (is_array($params) && in_array($action, $params)))
            {
                $user = $this->getCurrentUser();
                if (!$user || !$user->hasPrivilege($key)) {
                    return false;
                }
            }
        }

        // everything is ok
        return true;
    }
}