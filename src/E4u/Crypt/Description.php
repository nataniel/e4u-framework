<?php
namespace E4u\Crypt;

interface Description
{
    public function encode($value, $key);
    public function decode($value, $key);
}