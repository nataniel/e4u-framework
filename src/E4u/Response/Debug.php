<?php
namespace E4u\Response;

class Debug extends Base
{
    public function send(): void
    {
        if (PHP_SAPI != 'cli') { echo '<pre>'; }
        echo 'STATUS: '.$this->getStatus()."\n";
        var_dump($this->getContent());
    }
}