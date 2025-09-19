<?php
namespace E4u\Application\Helper;

use E4u\Common\Html;

class Flaticon extends ViewHelper
{
    public function show(string $icon, ?string $title = null): string
    {
        return Html::tag('i', [
            'class' => 'flaticon flaticon-' . $icon,
            'title' => $title,
        ], '');
    }
}