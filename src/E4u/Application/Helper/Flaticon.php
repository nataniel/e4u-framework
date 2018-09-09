<?php
namespace E4u\Application\Helper;

class Flaticon extends ViewHelper
{
    public function show($icon, $title = null)
    {
        return $this->view->tag('i', [
            'class' => 'flaticon flaticon-' . $icon,
            'title' => $title,
        ], '');
    }
}