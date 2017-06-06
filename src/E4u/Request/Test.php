<?php
namespace E4u\Request;

use Zend\Stdlib\Parameters;

class Test extends Cli
{
    protected $currentPath = '/';
    protected $postParams;
    protected $queryParams;

    public function setCurrentPath($path)
    {
        $this->currentPath = $path;
    }

    public function getCurrentPath()
    {
        return $this->currentPath;
    }
    
    /**
     * Mock $_POST parameters
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return \Zend\Stdlib\ParametersInterface
     */
    public function getPost($name = null, $default = null)
    {
        if ($this->postParams === null) {
            $this->postParams = new Parameters();
        }

        if ($name === null) {
            return $this->postParams;
        }

        return $this->postParams->get($name, $default);
    }
    
    /**
     * Mock $_GET parameters
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return \Zend\Stdlib\ParametersInterface
     */
    public function getQuery($name = null, $default = null)
    {
        if ($this->queryParams === null) {
            $this->queryParams = new Parameters();
        }

        if ($name === null) {
            return $this->queryParams;
        }

        return $this->queryParams->get($name, $default);
    }
}