<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Laminas\Config\Config;

/**
 * Class Google
 * @package E4u\Authentication\Social
 * @require "stevenmaguire/oauth2-microsoft": "^2.2"
 */
class Microsoft implements Helper
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
     * @var \Stevenmaguire\OAuth2\Client\Provider\Microsoft
     */
    private $client;

    /**
     * @var \Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner
     */
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
        if (!$config->get('clientId') || !$config->get('clientSecret')) {
            throw new ConfigException('Microsoft config must have "clientId" and "clientSecret" keys set.');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        $client = $this->getClient();
        $authUrl = $client->getAuthorizationUrl([
            'scope' => [ 'wl.basic', 'wl.signin' ],
        ]);

        $_SESSION['oauth2state'] = $client->getState();
        return $authUrl;
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

        $state = $this->request->getQuery('state');
        if (empty($state) || ($state !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            throw new AuthenticationException('Invalid state.');
        }

        $client = $this->getClient();
        try {

            $token = $client->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            $this->me = $client->getResourceOwner($token);
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
        return $this->me->getFirstname();
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->me->getLastname();
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->me->getEmail();
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return null;
    }

    /**
     * @return \Stevenmaguire\OAuth2\Client\Provider\Microsoft
     */
    public function getClient()
    {
        if (null === $this->client) {
            $callback = $this->getRequest()->getCurrentPath();
            $this->client = new \Stevenmaguire\OAuth2\Client\Provider\Microsoft([
                'clientId' => $this->config->get('clientId'),
                'clientSecret' => $this->config->get('clientSecret'),
                'redirectUri' => $this->urlTo($callback, true),
            ]);
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
}
