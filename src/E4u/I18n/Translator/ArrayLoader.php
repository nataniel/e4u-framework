<?php
namespace E4u\I18n\Translator;

use Laminas\I18n\Exception;
use Laminas\I18n\Translator\Loader\AbstractFileLoader;
use Laminas\I18n\Translator\TextDomain;

class ArrayLoader extends AbstractFileLoader
{
    /**
     * load(): defined by FileLoaderInterface.
     *
     * @see    FileLoaderInterface::load()
     * @param  string $locale
     * @param  string $filename
     * @return TextDomain|null
     * @throws Exception\InvalidArgumentException
     */
    public function load($locale, $filename)
    {
        $resolvedIncludePath = stream_resolve_include_path($filename);
        $fromIncludePath = ($resolvedIncludePath !== false) ? $resolvedIncludePath : $filename;
        if (!$fromIncludePath || !is_file($fromIncludePath) || !is_readable($fromIncludePath)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Could not find or open file %s for reading',
                $filename
            ));
        }

        $messages = include $fromIncludePath;

        if (!is_array($messages)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expected an array, but received %s',
                gettype($messages)
            ));
        }

        return $this->textDomain($messages);
    }

    /**
     * @param  array $input
     * @return TextDomain
     */
    private function textDomain($input)
    {
        $output = new TextDomain();
        $this->mergeInto($input, $output);
        return $output;
    }

    /**
     * @param  mixed $input
     * @param  TextDomain$textDomain
     * @param  string $prefix
     * @return $this
     */
    private function mergeInto($input, $textDomain, $prefix = '')
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $key = !empty($prefix) ? $prefix . '.' . $key : $key;
                $this->mergeInto($value, $textDomain, $key);
            }
        }
        else {
            $textDomain[ $prefix ] = $input;
        }

        return $this;
    }
}