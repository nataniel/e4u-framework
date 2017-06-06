<?php
namespace E4u\Authentication\Identity;

interface Repository
{
    /**
     * @param  string $login
     * @return User
     */
    public function findOneByLogin($login);
}