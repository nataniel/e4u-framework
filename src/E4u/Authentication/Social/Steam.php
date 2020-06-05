<?php
namespace E4u\Authentication\Social;

use E4u\Application\Helper\Url;
use E4u\Exception\ConfigException;
use E4u\Request\Request;
use Laminas\Config\Config;

/**
 * Class Steam
 * @package E4u\Authentication\Social
 * @require "iignatov/lightopenid": "^1.0"
 */
class Steam implements Helper
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
     * @var \LightOpenID
     */
    protected $client;

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
        if (!$config->get('api_key')) {
            throw new ConfigException('Steam config must have "api_key" key set.');
        }

        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->getClient()->authUrl();
    }

    /**
     * @return bool
     */
    public function loginFromRedirect()
    {
        if ($this->getClient()->mode) {
            if ($this->getClient()->validate()) {

                $url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?'
                    . http_build_query([
                        'key' => $this->config->get('api_key'),
                        'steamids' => $this->getClient()->identity,
                ]);

                $json = file_get_contents($url);
                $this->me = json_decode($json)->response->players[0];
                return true;

            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->me->steamid;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return strtok($this->me->realname, ' ') ?: $this->me->personaname;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        strtok($this->me->realname, ' ');
        return (string)strtok('');
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->me->avatarfull;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return null;
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
     * @return \LightOpenID
     */
    public function getClient()
    {
        if (null == $this->client) {

            $callback = $this->getRequest()->getCurrentPath();
            $this->client = new \LightOpenID($this->urlTo($callback, true));
            $this->client->identity = 'http://steamcommunity.com/openid';

        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        // "loccountrycode": "PL",
        return strtolower($this->me->loccountrycode);
    }
}
