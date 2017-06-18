<?php
namespace E4u\Application\Controller;

use E4u\Request\Request;

interface Errors
{
    public function notFoundAction();
    public function invalidAction();
    public function getActionName(Request $request);
}