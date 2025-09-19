<?php
namespace E4u\Application\Helper;

use E4u\Application\View;
use E4u\Common\Html;

class Flash extends ViewHelper
{
    public function show(): self
    {
        return $this;
    }
    
    public function __toString()
    {
        $html = '';
        $types = [ View::FLASH_MESSAGE, View::FLASH_SUCCESS, View::FLASH_ERROR ];
        $flash = $this->view->getFlash();

        foreach ($types as $type) {
            if (!empty($flash[ $type ])) {
                $html .= Html::tag('p', [ 'class' => $type ], join('<br />', $flash[ $type ]));
            }
        }
        
        if (!empty($html)) {
            $html = Html::tag('div', [ 'id' => 'flash' ], $html);
        }
        
        return $html;
    }
}