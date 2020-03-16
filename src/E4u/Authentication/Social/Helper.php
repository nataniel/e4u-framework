<?php
namespace E4u\Authentication\Social;

use E4u\Request\Request;
use Laminas\Config\Config;

interface Helper
{
    public function __construct(Config $config, Request $request);

    /**
     * @return string
     */
    public function getLoginUrl();

    /**
     * @return bool
     */
    public function loginFromRedirect();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @return string
     */
    public function getLastName();

    /**
     * @return string
     */
    public function getPicture();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getLocale();
}