<?php
namespace E4u\Authentication;

interface Identity
{
    /**
     * @return int
     */
    public function id();

    /**
     * @return string
     */
    public function getLogin();

    /**
     * @return string|null
     */
    public function getLocale();

    /**
     * @return string
     */
    public function getCookie();

    /**
     * Should return TRUE if the current identity has a privilige
     * to take a specified action. In most cases $privilige will be
     * an integer, like E4u\Application\Controller::ACCESS_ADMIN
     * to be checked against privileges appointed to the user (or group).
     * 
     * @param  int
     * @return bool
     */
    public function hasPrivilege($privilege);
    
    /**
     * @param  string $user
     * @param  string $password
     * @return Identity
     */
    public static function login($user, $password);
    
    /**
     * @param  int $id
     * @return Identity
     */
    public static function findByID($id);
    
    /**
     * @param  string $cookie
     * @return Identity
     */
    public static function findByCookie($cookie);
}