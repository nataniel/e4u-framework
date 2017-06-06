<?php
namespace E4u\Application\Controller;

use E4u\Request\Request;

interface Security extends \Zend\Stdlib\DispatchableInterface
{
    public function loginAction();
    public function logoutAction();
    public function getActionName(Request $request);
}