<?php
namespace E4u\Application\Helper;

use E4u\Application\View;

class Flash extends ViewHelper
{
    public function show()
    {
        return $this;
    }
    
    public function __toString()
    {
        $html = '';
        $types = [ View::FLASH_MESSAGE, View::FLASH_SUCCESS, View::FLASH_ERROR ];

        foreach ($types as $type) {
            $msg = $this->view->getFlash($type);
            if (!empty($msg)) {
                $html .= $this->view->tag('p', [ 'class' => $type ], $msg);
            }
        }
        
        if (!empty($html)) {
            $html = $this->view->tag('div', [ 'id' => 'flash' ], $html);
        }
        
        return $html;
    }
}