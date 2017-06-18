<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Zend\Config\Config;

/**
 * Class Google
 * @package E4u\Authentication\Social
 * @require "google/apiclient": "^2.0"
 */
class Google implements Helper
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
     * @var \Google_Client
     */
    private $client;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @var \Google_Service_Oauth2_Userinfoplus
     */
    private $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->setScopes([ \Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Oauth2::USERINFO_PROFILE, ]);
        $this->request = $request;
    }

    /**
     * @param  Config $config
     * @return $this
     */
    protected function setConfig(Config $config)
    {
        if (!$config->get('client_id') || !$config->get('client_secret')) {
            throw new ConfigException('Google config must have "client_id" and "client_secret" keys set.');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->getClient()->createAuthUrl();
    }

    /**
     * @return bool
     */
    public function loginFromRedirect()
    {
        $code = $this->request->getQuery('code');
        if (empty($code)) {
            return false;
        }

        $client = $this->getClient();
        try {

            $client->fetchAccessTokenWithAuthCode($code);
            $this->me = (new \Google_Service_Oauth2($client))->userinfo->get();
            return true;

        }
        catch (\RuntimeException $e) {

            throw new AuthenticationException($e->getMessage(), null, $e);

        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->me->getId();
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->me->getGivenName();
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->me->getFamilyName();
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->me->getPicture();
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->me->getEmail();
    }

    /**
     * @param  string[] $scopes
     * @return $this
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * @param  string[] $scopes
     * @return $this
     */
    public function addScopes($scopes)
    {
        $this->scopes = array_merge($this->scopes, $scopes);
        return $this;
    }

    /**
     * @return \Google_Client
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new \Google_Client();
            $this->client->setClientId($this->config->get('client_id'));
            $this->client->setClientSecret($this->config->get('client_secret'));
            $this->client->setScopes($this->scopes);
            $this->client->setApprovalPrompt('auto');
            $this->client->setAccessType('online');

            $callback = $this->getRequest()->getCurrentPath();
            $this->client->setRedirectUri($this->urlTo($callback, true));
        }

        return $this->client;
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
     * @return string
     */
    public function getLocale()
    {
        // ["locale"] => string(2) "pl"
        return $this->me->getLocale();
    }
}