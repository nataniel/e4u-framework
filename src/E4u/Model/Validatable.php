<?php
namespace E4u\Model;

interface Validatable
{
    /**
     * @return bool
     */
    public function valid();
    
    /**
     * @return array
     */
    public function getErrors($field = null);
}