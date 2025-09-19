<?php
namespace E4u\Application\Controller;

use E4u\Response\Response;

/**
 * <code>
 *  $exception = new Controller\Redirect();
 *  throw $exception->setUrl('security/login');
 * </code>
 */
class Redirect extends \Exception
{
    protected $code = Response::STATUS_REDIRECT;

    private string $url;

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string 
    {
        return $this->url;
    }
}