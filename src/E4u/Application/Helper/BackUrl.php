<?php
namespace E4u\Application\Helper;

use E4u\Exception\LogicException;
use E4u\Request\Http;

class BackUrl extends ViewHelper
{
    public function show(): string
    {
        $request = $this->view->getRequest();
        if (!$request instanceof Http) {
            throw new LogicException('Request must be Http to use BackUrl.');
        }
        
        $query = $request->getQueryString();
        $current = $request->getCurrentPath();
        return $this->view->urlEncode(empty($query) ? $current : $current . '?' . $query);
    }

    public function __toString()
    {
        return $this->show();
    }
}