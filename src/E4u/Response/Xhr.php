<?php
namespace E4u\Response;

class Xhr extends Base
{
    public function __construct($content = null, $data = null)
    {
        parent::__construct($content);
        $this->setMetadata($data);
    }

    /**
     * @return $this
     */
    public function send()
    {
        $result = $this->getMetadata();
        $result['status'] = $this->getStatus();
        $result['content'] = ($this->getStatus() == self::STATUS_OK)
                           ? $this->getContent()
                           : '';

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($result);
        return $this;
    }
}