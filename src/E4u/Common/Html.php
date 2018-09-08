<?php
namespace E4u\Common;

class Html
{
    public static function attributes($attributes)
    {
        $attr = [];
        foreach ($attributes as $key => $value)
        {
            if (is_bool($value)) {
                if (true === $value) {
                    $attr[] = "$key";
                }
            }
            elseif (!is_null($value)) {
                $value = htmlentities($value, ENT_COMPAT, 'UTF-8');
                $value = str_replace('%2F', '/', $value);
                $attr[] = "$key=\"$value\"";
            }
        }

        return join(' ', $attr);
    }

    public static function tag($tag, $attributes = null, $content = null)
    {
        $attr = '';
        if (is_null($content) && (is_string($attributes) || is_object($attributes))) {
            $content = $attributes;
        }
        elseif (is_array($attributes) && !empty($attributes)) {
            $attr = ' '.self::attributes($attributes);
        }

        return (null === $content)
            ? sprintf('<%s%s />', $tag, $attr)
            : sprintf('<%s%s>%s</%s>', $tag, $attr, (is_object($content) ? $content->__toString() : $content), $tag);
    }
}