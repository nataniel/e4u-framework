<?php
namespace E4u\Request;

use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\ParametersInterface;

class Test extends Cli
{
    protected string $currentPath = '/';
    protected ParametersInterface $postParams;
    protected ParametersInterface $queryParams;

    public function setCurrentPath(string $path): void
    {
        $this->currentPath = $path;
    }

    public function getCurrentPath(): string
    {
        return $this->currentPath;
    }
    
    /**
     * Mock $_POST parameters
     * @return ParametersInterface|mixed
     */
    public function getPost($name = null, $default = null)
    {
        if (!isset($this->postParams)) {
            $this->postParams = new Parameters();
        }

        if ($name === null) {
            return $this->postParams;
        }

        return $this->postParams->get($name, $default);
    }
    
    /**
     * Mock $_GET parameters
     * @return ParametersInterface|mixed
     */
    public function getQuery($name = null, $default = null) 
    {
        if (!isset($this->queryParams)) {
            $this->queryParams = new Parameters();
        }

        if ($name === null) {
            return $this->queryParams;
        }

        return $this->queryParams->get($name, $default);
    }
}