<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Google\Service\Oauth2\Userinfo;
use Laminas\Config\Config;

/**
 * Class Google
 * @package E4u\Authentication\Social
 * @require "google/apiclient": "^2.0"
 */
class Google implements Helper
{
    use Url;

    private Config $config;

    private Request $request;

    private \Google_Client $client;

    private array $scopes;

    private Userinfo $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->setScopes([ \Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Oauth2::USERINFO_PROFILE, ]);
        $this->request = $request;
    }

    protected function setConfig(Config $config): void
    {
        if (!$config->get('client_id') || !$config->get('client_secret')) {
            throw new ConfigException('Google config must have "client_id" and "client_secret" keys set.');
        }

        $this->config = $config;
    }

    public function getLoginUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function loginFromRedirect(): bool
    {
        $code = $this->request->getQuery('code');
        if (empty($code)) {
            return false;
        }

        $client = $this->getClient();
        try {

            $client->fetchAccessTokenWithAuthCode($code);
            $this->me = new \Google_Service_Oauth2($client)->userinfo->get();
            return true;

        }
        catch (\RuntimeException $e) {
            throw new AuthenticationException($e->getMessage(), null, $e);
        }
    }

    public function getId(): ?string
    {
        return $this->me->getId();
    }

    public function getFirstName(): ?string
    {
        return $this->me->getGivenName();
    }

    public function getLastName(): ?string
    {
        return $this->me->getFamilyName();
    }

    public function getPicture(): ?string
    {
        return $this->me->getPicture();
    }

    public function getEmail(): ?string
    {
        return $this->me->getEmail();
    }

    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function addScopes(array $scopes): void
    {
        $this->scopes = array_merge($this->scopes, $scopes);
    }

    public function getClient(): \Google_Client
    {
        if (!isset($this->client)) {
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
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getLocale(): string
    {
        // ["locale"] => string(2) "pl"
        return $this->me->getLocale();
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
