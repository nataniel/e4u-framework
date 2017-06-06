<?php
namespace E4u\Application\Helper;

use E4u\Application\View;

class BackUrl extends ViewHelper
{
    public function show()
    {
        $request = $this->getView()->getRequest();
        $query = $request->getQueryString();

        $current = $request->getCurrentPath();
        return $this->getView()->urlEncode(empty($query) ? $current : $current . '?' . $query);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->show();
    }
}