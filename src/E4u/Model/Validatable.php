<?php
namespace E4u\Model;

interface Validatable
{
    public function valid(): bool;
    
    public function getErrors(?string $field = null): array;
}