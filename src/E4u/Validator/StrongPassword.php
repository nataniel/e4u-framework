<?php
namespace E4u\Validator;

use Laminas\Validator\AbstractValidator;

class StrongPassword extends AbstractValidator
{
    const int MIN_LENGTH = 8;
    /**
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

        if (strlen($value) < self::MIN_LENGTH) {
            $this->error('too_short');
            return false;
        }

        return true;
    }
}
