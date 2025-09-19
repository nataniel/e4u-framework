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

    private Config $config;

    private Request $request;

    protected \LightOpenID $client;

    private object $me;

    public function __construct(Config $config, Request $request)
    {
        $this->setConfig($config);
        $this->request = $request;
    }

    protected function setConfig(Config $config): void
    {
        if (!$config->get('api_key')) {
            throw new ConfigException('Steam config must have "api_key" key set.');
        }

        $this->config = $config;
    }

    public function getLoginUrl(): string
    {
        return $this->getClient()->authUrl();
    }

    public function loginFromRedirect(): bool
    {
        if ($this->getClient()->mode) {
            if ($this->getClient()->validate()) {

                $url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?'
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

    public function getId(): ?string
    {
        return $this->me->steamid;
    }

    public function getFirstName(): ?string
    {
        return strtok($this->me->realname, ' ') ?: $this->me->personaname;
    }

    public function getLastName(): string
    {
        strtok($this->me->realname, ' ');
        return (string)strtok('');
    }

    public function getPicture(): ?string
    {
        return $this->me->avatarfull;
    }

    public function getEmail(): null
    {
        return null;
    }

    /**
     * Implements Helper\Url
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getClient(): \LightOpenID
    {
        if (!isset($this->client)) {

            $callback = $this->getRequest()->getCurrentPath();
            $this->client = new \LightOpenID($this->urlTo($callback, true));
            $this->client->identity = 'https://steamcommunity.com/openid';

        }

        return $this->client;
    }

    public function getLocale(): string
    {
        // "loccountrycode": "PL",
        return strtolower($this->me->loccountrycode);
    }

    public function hasId(): bool
    {
        return !empty($this->me);
    }
}
