<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Facebook\Exception\SDKException;
use Facebook\GraphNode\GraphUser;
use Laminas\Config\Config;

/**
 * Class Facebook
 * @package E4u\Authentication\Social
 * @require "facebook/graph-sdk": "^5.3"
 */
class Facebook implements Helper
{
    use Url;

    private Config $config;

    private Request $request;

    private \Facebook\Facebook $client;

    private GraphUser $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->request = $request;
    }

    protected function setConfig(Config $config): void
    {
        if (!$config->get('app_id') || !$config->get('app_secret')) {
            throw new ConfigException('Facebook config must have "app_id" and "app_secret" keys set.');
        }

        $this->config = $config;
    }

    private function getClient(): \Facebook\Facebook
    {
        if (!isset($this->client)) {
            $this->client = new \Facebook\Facebook($this->config->toArray());
        }

        return $this->client;
    }

    public function getLoginUrl(): string
    {
        $permissions = [ 'email' ];
        $helper = $this->getClient()->getRedirectLoginHelper();

        $callback = $this->getRequest()->getCurrentPath();
        return $helper->getLoginUrl($this->urlTo($callback, true), $permissions);
    }

    public function loginFromRedirect(): bool
    {
        $helper = $this->getClient()->getRedirectLoginHelper();
        try {

            $accessToken = $helper->getAccessToken();
            if (empty($accessToken)) {
                return false;
            }

            $response = $this->getClient()->sendRequest('GET', '/me', [
                'type' => 'Facebook\GraphUser',
                'fields' => 'id,first_name,last_name,email,locale,picture.type(large)',
            ], $accessToken);

            $this->me = $response->getGraphUser();
            return true;

        } catch (SDKException $e) {
            throw new AuthenticationException($e->getMessage(), null, $e);
        }
    }

    public function getId(): ?string
    {
        return $this->me->getId();
    }

    public function getFirstName(): ?string
    {
        return $this->me->getFirstName();
    }

    public function getLastName(): ?string
    {
        return $this->me->getLastName();
    }

    public function getPicture(): string
    {
        $picture = $this->me->getPicture();
        return !empty($picture)
            ? $picture->getUrl()
            : '';
    }

    public function getEmail(): ?string
    {
        return $this->me->getEmail();
    }

    public function getLocale(): string
    {
        // ["locale"] => string(5) "pl_PL"
        return strtok($this->me->getField('locale'), '_');
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
