<?php
namespace E4u\Application\Helper;

class GaduGadu extends ViewHelper
{
    const GG_IMG = '//status.gadu-gadu.pl/users/status.asp?id=%d&styl=1';
    const GG_HREF = 'gg:%d';

    public function show($gg, $description = null)
    {
        $img = $this->view->image(sprintf(self::GG_IMG, $gg), $gg);
        if (is_null($description)) {
            $description = $gg;
        }
        
        return $this->view->tag('a', [ 'href' => sprintf(self::GG_HREF, $gg), 'class' => 'gadugadu' ], $img.'&nbsp;'.$description);
    }
}