<?php
namespace E4u\Response;

use Laminas\Stdlib\Response as LaminasResponse;

abstract class Base extends LaminasResponse implements Response
{
    protected $status = self::STATUS_OK;

    public function __construct($content = null)
    {
        if (!is_null($content)) {
            $this->setContent($content);
        }
    }

    public abstract function send();
    
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
}