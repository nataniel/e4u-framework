<?php
namespace E4u\Application\Controller;

use E4u\Request\Request;
use Zend\Stdlib\DispatchableInterface;

interface Errors extends DispatchableInterface
{
    public function notFoundAction();
    public function invalidAction();
    public function getActionName(Request $request);
}