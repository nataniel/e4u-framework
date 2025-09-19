<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Laminas\Config\Config;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft as MicrosoftProvider;
use Stevenmaguire\OAuth2\Client\Provider\MicrosoftResourceOwner;

/**
 * Class Google
 * @package E4u\Authentication\Social
 * @require "stevenmaguire/oauth2-microsoft": "^2.2"
 */
class Microsoft implements Helper
{
    use Url;

    private Config $config;

    private Request $request;

    private MicrosoftProvider $client;

    private MicrosoftResourceOwner $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->request = $request;
    }

    protected function setConfig(Config $config): void
    {
        if (!$config->get('clientId') || !$config->get('clientSecret')) {
            throw new ConfigException('Microsoft config must have "clientId" and "clientSecret" keys set.');
        }

        $this->config = $config;
    }

    public function getLoginUrl(): string
    {
        $client = $this->getClient();
        $authUrl = $client->getAuthorizationUrl([
            'scope' => [ 'wl.basic', 'wl.signin' ],
        ]);

        $_SESSION['oauth2state'] = $client->getState();
        return $authUrl;
    }

    public function loginFromRedirect(): bool
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

            $me = $client->getResourceOwner($token);
            if (!$me instanceof MicrosoftResourceOwner) {
                throw new AuthenticationException('Invalid resource owner.');
            }

            $this->me = $me;
            return true;

        }
        catch (\RuntimeException $e) {
            throw new AuthenticationException($e->getMessage(), null, $e);
        }
    }

    public function getId(): string
    {
        return $this->me->getId();
    }

    public function getFirstName(): ?string
    {
        return $this->me->getFirstname();
    }

    public function getLastName(): ?string
    {
        return $this->me->getLastname();
    }

    public function getPicture(): null
    {
        return null;
    }

    public function getEmail(): ?string
    {
        return $this->me->getEmail();
    }

    public function getLocale(): null
    {
        return null;
    }

    public function getClient(): MicrosoftProvider
    {
        if (!isset($this->client)) {
            $callback = $this->getRequest()->getCurrentPath();
            $this->client = new MicrosoftProvider([
                'clientId' => $this->config->get('clientId'),
                'clientSecret' => $this->config->get('clientSecret'),
                'redirectUri' => $this->urlTo($callback, true),
            ]);
        }

        return $this->client;
    }

    /**
     * Implements Helper\Url
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
