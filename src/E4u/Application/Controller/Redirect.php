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

    private $url;

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function getUrl() {
        return $this->url;
    }
}