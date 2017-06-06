<?php
namespace E4u\Validator;

class StrongPassword extends \Zend\Validator\AbstractValidator
{
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a strong password
     * 
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error('invalid');
            return false;
        }

        if (strlen($value) < 6) {
            $this->error('too_short');
            return false;
        }

        return true;
    }
}
