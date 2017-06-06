<?php
namespace E4u\Response;

class Debug extends Base
{
    /**
     * @return Debug
     */
    public function send()
    {
        if (PHP_SAPI != 'cli') { echo '<pre>'; }
        echo 'STATUS: '.$this->getStatus()."\n";
        var_dump($this->getContent());
        return $this;
    }
}