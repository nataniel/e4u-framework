<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Authentication\Exception\AuthenticationException;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Laminas\Config\Config;

/**
 * Class Facebook
 * @package E4u\Authentication\Social
 * @require "facebook/graph-sdk": "^5.3"
 */
class Facebook implements Helper
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
     * @var \Facebook\Facebook
     */
    private $client;

    /**
     * @var \Facebook\GraphNodes\GraphUser
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
        if (!$config->get('app_id') || !$config->get('app_secret')) {
            throw new ConfigException('Facebook config must have "app_id" and "app_secret" keys set.');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * @return \Facebook\Facebook
     */
    private function getClient()
    {
        if (null === $this->client) {
            $this->client = new \Facebook\Facebook($this->config->toArray());
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        $permissions = [ 'email' ];
        $helper = $this->getClient()->getRedirectLoginHelper();

        $callback = $this->getRequest()->getCurrentPath();
        return $helper->getLoginUrl($this->urlTo($callback, true), $permissions);
    }

    /**
     * @return bool
     */
    public function loginFromRedirect()
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

        } catch (\Facebook\Exceptions\FacebookSDKException $e) {

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
        return $this->me->getFirstName();
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->me->getLastName();
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        $picture = $this->me->getPicture();
        return !empty($picture)
            ? $picture->getUrl()
            : '';
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
        // ["locale"] => string(5) "pl_PL"
        return strtok($this->me->getField('locale'), '_');
    }

    /**
     * Implements Helper\Url
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
