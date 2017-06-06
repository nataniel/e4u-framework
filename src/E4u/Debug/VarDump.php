<?php
namespace E4u\Debug;

class VarDump
{
    public static function toHTML($var)
    {
        ob_start();
        var_dump($var);
        $content = ob_get_clean();
        
        $html = str_replace("=>\n", "=>\t", $content);
        $pattern = '/\n([^\n]*)\{(.*)\}/Us';
        while (preg_match($pattern, $html))
        {
            $id = rand(10000, 99999);
            $html = preg_replace($pattern, "\n".'<span class="hide" id="'.$id.'"><a onclick="toggle('.$id.')">$1</a><div class="element">$2</div></span>', $html, 1);
        }
        
        return '<pre>'.$html.'</pre>';
    }
}