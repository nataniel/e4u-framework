<?php
namespace E4u\Response;

use Laminas\Stdlib\Response as LaminasResponse;

abstract class Base extends LaminasResponse implements Response
{
    protected int $status = self::STATUS_OK;

    public function __construct($content = null)
    {
        if (!is_null($content)) {
            $this->setContent($content);
        }
    }

    public abstract function send(): void;
    
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
    
    public function getStatus(): int
    {
        return $this->status;
    }
}