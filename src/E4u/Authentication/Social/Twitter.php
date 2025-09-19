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

    private Config $config;

    private Request $request;

    protected TwitterOAuth $client;

    protected array $requestToken;

    private $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->request = $request;
    }

    protected function setConfig(Config $config): void
    {
        if (!$config->get('consumer_key') || !$config->get('consumer_secret')) {
            throw new ConfigException('Twitter config must have "consumer_key" and "consumer_secret" keys set.');
        }

        $this->config = $config;
    }

    public function getLoginUrl(): string
    {
        $callback = $this->getRequest()->getCurrentPath();
        $token = $this->getClient()->oauth('oauth/request_token', [ 'oauth_callback' => $this->urlTo($callback, true) ]);

        $this->requestToken = $token;
        $_SESSION['request_token'] = $token;

        return $this->getClient()->url('oauth/authorize', [ 'oauth_token' => $token['oauth_token'] ]);
    }

    public function loginFromRedirect(): bool
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

        $this->requestToken = $token;
        $token = $this->getClient()->oauth("oauth/access_token", [ 'oauth_verifier' => $oauth_verifier ]);

        $this->client = new TwitterOAuth(
            $this->config->get('consumer_key'),
            $this->config->get('consumer_secret'),
            $token['oauth_token'],
            $token['oauth_token_secret']
        );

        $this->me = $this->getClient()->get("account/verify_credentials");
        return true;
    }

    public function getId(): ?string
    {
        return $this->me->id;
    }

    public function getFirstName(): string
    {
        return strtok($this->me->name, ' ');
    }

    public function getLastName(): string
    {
        strtok($this->me->name, ' ');
        return (string)strtok('');
    }

    public function getPicture(): ?string
    {
        return $this->me->profile_image_url;
    }

    public function getEmail(): string
    {
        return $this->me->screen_name . '@twitter.com';
    }

    /**
     * Implements Helper\Url
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    private function getClient(): TwitterOAuth
    {
        if (!isset($this->client)) {

            if (!isset($this->requestToken)) {

                $this->client = new TwitterOAuth(
                    $this->config->get('consumer_key'),
                    $this->config->get('consumer_secret')
                );

            }
            else {

                $this->client = new TwitterOAuth(
                    $this->config->get('consumer_key'),
                    $this->config->get('consumer_secret'),
                    $this->requestToken['oauth_token'],
                    $this->requestToken['oauth_token_secret']
                );

            }
        }

        return $this->client;
    }

    public function getLocale(): ?string
    {
        // ["lang"] => string(2) "pl"
        return $this->me->lang;
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
