<?php
namespace E4u\Authentication;

use E4u\Authentication\Identity,
    E4u\Request\Request,
    Zend\Config\Config;

class Resolver
{
    const DEFAULT_PATH  = 'security/login';
    const COOKIE_NAME = 'E4uAuthentication';
    const COOKIE_LIFETIME = 2592000; // 30 days

    /**
     * @var Identity
     */
    protected $currentUser;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;


    public function __construct(Request $request, $config = null)
    {
        $this->request = $request;
        $this->config  = $config;
    }

    /**
     *
     * @return Config
     */
    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = new Config([]);
        }

        return $this->config;
    }

    /**
     * @return string
     */
    public function getLoginPath()
    {
        return $this->getConfig()->get('login', self::DEFAULT_PATH);
    }

    /**
     * @return string|null Class name
     */
    public function getIdentityModel()
    {
        $class = $this->getConfig()->get('model');
        if (!is_null($class) && !class_exists($class)) {
            throw new \E4u\Exception\LogicException(
                "Identity class defined by authentication config $class does not exist.");
        }

        return $class;
    }

    /**
     * @return string
     */
    public function getCookieName()
    {
        return $this->getConfig()->get('cookie_name', self::COOKIE_NAME);
    }

    /**
     * @return int
     */
    public function getCookieLifetime()
    {
        return (int)$this->getConfig()->get('cookie_lifetime', self::COOKIE_LIFETIME);
    }

    /**
     * Read cookies / headers from the request and return
     * authorized user if found in request.
     *
     * @return boolean
     */
    public function authenticate()
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
                $this->loginAs($user);
                return true;
            }
        }

        return false;
    }

    private function setCookie($value, $expiration = null)
    {
        if (null === $expiration) {
            $expiration = $this->getCookieLifetime();
        }

        $path = $this->request->getBaseUrl() ?: '/';
        setcookie($this->getCookieName(), $value, time() + $expiration, $path);
        if (empty($value) || $expiration < 0) {
            unset($_COOKIE[ $this->getCookieName() ]);
        }

        return $this;
    }

    /**
     * @return Resolver
     */
    public function logout()
    {
        $this->authenticate();
        session_regenerate_id(true);
        unset($_SESSION['current_user']);

        $this->currentUser = null;
        $this->setCookie('', -3600);
        return $this;
    }

    /**
     * @param Identity $user
     * @param boolean  $remember
     * @return Resolver
     */
    public function loginAs(Identity $user, $remember = false)
    {
        // add user info to session
        $_SESSION['current_user'] = $user->id();

        // setup current user object
        $this->currentUser = $user;

        // set persistent cookie
        if ($remember && ($cookie = $user->getCookie())) {
            $this->setCookie($cookie);
        }

        return $this;
    }

    /**
     * Try to authenticate the user using provided login and password.
     * Return authenticated user if login/password match.
     * Return NULL if user not found or FALSE if password is invalid.
     * Set a cookie in user's browser if $remember is TRUE.
     * @link http://fishbowl.pastiche.org/2004/01/19/persistent_login_cookie_best_practice/
     *
     * @param  string $login
     * @param  string $password Open text password
     * @param  bool   $remember If true, the cookie will be persistent,
     *                          instead of session-only.
     * @return Identity|null
     */
    public function login($login, $password, $remember = false)
    {
        $class = $this->getIdentityModel();
        $user = $class::login($login, $password);
        if ($user instanceof Identity) {
            $this->loginAs($user, $remember);
            return $user;
        }

        return null;
    }

    /**
     * @return Identity|null
     */
    public function getCurrentUser()
    {
        if (null === $this->currentUser) {
            $this->authenticate();
        }

        return $this->currentUser;
    }

    /**
     * Returns FALSE if the current user does not meet
     * any privileges required by controller for current action.
     *
     * @param array|int|boolean $requiredPrivileges
     * @param string $action
     * @return boolean
     */
    public function checkPrivileges($requiredPrivileges, $action = null)
    {
        if (!is_array($requiredPrivileges) && !($requiredPrivileges instanceof \Traversable)) {
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