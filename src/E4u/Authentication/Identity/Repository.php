<?php
namespace E4u\Authentication\Identity;

interface Repository
{
    public function findOneByLogin(string $login): ?User;
}