<?php
namespace E4u\Authentication;

interface Identity
{
    public function id(): int;

    public function getLogin(): ?string;

    public function getLocale(): ?string;

    public function getCookie(): ?string;

    /**
     * Should return TRUE if the current identity has a privilige
     * to take a specified action. In most cases $privilige will be
     * an integer, like E4u\Application\Controller::ACCESS_ADMIN
     * to be checked against privileges appointed to the user (or group).
     */
    public function hasPrivilege(int $privilege): bool;
    
    public static function login(string $login, string $password): static;
    
    public static function findByID(int $id): ?static;
    public static function findByCookie(string $cookie): ?static;
}