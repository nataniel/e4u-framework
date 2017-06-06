<?php
namespace E4u\Validator;

use Zend\Validator\ValidatorInterface;

class NumerIdentyfikacjiPodatkowej implements ValidatorInterface
{
    private static $weights = [ 6, 5, 7, 2, 3, 4, 5, 6, 7 ];

    /**
     * https://pl.wikipedia.org/wiki/NIP
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $value = str_replace(['/', '-'], '', $value);
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            return false;
        }

        $digits = str_split($value);
        $weightedSum = $this->getWeightedSumOfDigits($digits);

        return ($weightedSum % 11) == intval($digits[9]);
    }

    private function getWeightedSumOfDigits($digits)
    {
        $sum = 0;
        foreach (self::$weights as $i => $weight) {
            $sum += intval($digits[$i]) * $weight;
        }
        return $sum;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return [ 'Niepoprawny numer NIP.' ];
    }
}