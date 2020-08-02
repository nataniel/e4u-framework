<?php
namespace E4u\Authentication\Social;

use Abraham\TwitterOAuth\TwitterOAuth;
use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Laminas\Config\Config;

/**
 * Class Twitter
 * @package E4u\Authentication\Social
 * @require "abraham/twitteroauth": "^0.6.4"
 */
class Twitter implements Helper
{
    use Url;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TwitterOAuth
     */
    protected $connection;

    /**
     * @var array
     */
    protected $requestToken;

    private $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->request = $request;
    }

    /**
     * @param  Config $config
     * @return $this
     */
    protected function setConfig(Config $config)
    {
        if (!$config->get('consumer_key') || !$config->get('consumer_secret')) {
            throw new ConfigException('Twitter config must have "consumer_key" and "consumer_secret" keys set.');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        $callback = $this->getRequest()->getCurrentPath();
        $token = $this->getConnection()->oauth('oauth/request_token', [ 'oauth_callback' => $this->urlTo($callback, true) ]);

        $this->setRequestToken($token);
        $_SESSION['request_token'] = $token;

        return $this->getConnection()->url('oauth/authorize', [ 'oauth_token' => $token['oauth_token'] ]);
    }

    /**
     * @return bool
     */
    public function loginFromRedirect()
    {
        $oauth_verifier = $this->request->getQuery('oauth_verifier');
        if (empty($oauth_verifier) || empty($_SESSION['request_token'])) {
            return false;
        }

        $token = $_SESSION['request_token'];
        unset($_SESSION['request_token']);

        $oauth_token = $this->getRequest()->getQuery('oauth_token');
        if ($token['oauth_token'] != $oauth_token) {
            // cos poszło nie tak
            throw new AuthenticationException('Nieprawidłowy token w sesji.');
        }

        $this->setRequestToken($token);
        $token = $this->getConnection()->oauth("oauth/access_token", [ 'oauth_verifier' => $oauth_verifier ]);

        $this->connection = new TwitterOAuth(
            $this->config->get('consumer_key'),
            $this->config->get('consumer_secret'),
            $token['oauth_token'],
            $token['oauth_token_secret']
        );

        $this->me = $this->getConnection()->get("account/verify_credentials");
        return true;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->me->id;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return strtok($this->me->name, ' ');
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        strtok($this->me->name, ' ');
        return (string)strtok('');
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->me->profile_image_url;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->me->screen_name . '@twitter.com';
    }

    /**
     * Implements Helper\Url
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return TwitterOAuth
     */
    private function getConnection()
    {
        if (null == $this->connection) {

            if (null === $this->requestToken) {

                $this->connection = new TwitterOAuth(
                    $this->config->get('consumer_key'),
                    $this->config->get('consumer_secret')
                );

            }
            else {

                $this->connection = new TwitterOAuth(
                    $this->config->get('consumer_key'),
                    $this->config->get('consumer_secret'),
                    $this->requestToken['oauth_token'],
                    $this->requestToken['oauth_token_secret']
                );

            }
        }

        return $this->connection;
    }

    /**
     * @return array
     */
    private function getRequestToken()
    {
        return $this->requestToken;
    }

    /**
     *
     * @param  array $token
     * @return self
     */
    private function setRequestToken($token)
    {
        $this->requestToken = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        // ["lang"] => string(2) "pl"
        return $this->me->lang;
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
