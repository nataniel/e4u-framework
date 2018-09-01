<?php
namespace E4u\Application\Controller;

use E4u\Request\Request;

interface Security
{
    public function loginAction();
    public function logoutAction();
    public function getActionName();
}