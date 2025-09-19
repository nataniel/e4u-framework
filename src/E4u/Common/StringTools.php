<?php
namespace E4u\Common;

class StringTools
{
    public static function wolacz(string $name): string
    {
        $vocativeRules = [
            'a' => [
                'świnia' => 'świnio',
                'śka' => 'śko',
                'sia' => 'siu',
                'zia' => 'ziu',
                'cia' => 'ciu',
                'nia' => 'niu',
                'aja' => 'aju',
                'lla' => 'llo',
                'ila' => 'ilo',
                'ula' => 'ulo',
                'nela' => 'nelo',
                'bela' => 'belo',
                'iela' => 'ielo',
                'mela' => 'melo',
                'la' => 'lu',
                'a' => 'o',
            ],
            'b' => [
                'b' => 'bie',
            ],
            'c' => [
                'ojciec' => 'ojcze',
                'starzec' => 'starcze',
                'ciec' => 'ćcu',
                'liec' => 'łcu',
                'niec' => 'ńcu',
                'siec' => 'ścu',
                'ziec' => 'źcu',
                'lec' => 'lcu',
                'c' => 'cu',
            ],
            'ć' => [
                'gość' => 'gościu',
                'ść' => 'ściu',
                'ć' => 'cio',
            ],
            'd' => [
                'łąd' => 'łędzie',
                'ód' => 'odzie',
                'd' => 'dzie',
            ],
            'f' => [
                'f' => 'fie',
            ],
            'g' => [
                'bóg' => 'boże',
                'g' => 'gu',
            ],
            'h' => [
                'ph' => 'ph',
                'h' => 'hu',
            ],
            'j' => [
                'ój' => 'oju',
                'j' => 'ju',
            ],
            'k' => [
                'człek' => 'człeku',
                'ciek' => 'ćku',
                'liek' => 'łku',
                'niek' => 'ńku',
                'siek' => 'śku',
                'ziek' => 'źku',
                'wiek' => 'wieku',
                'ek' => 'ku',
                'k' => 'ku',
            ],
            'l' => [
                'kornel' => 'kornelu',
                'sól' => 'solo',
                'mól' => 'mole',
                'awel' => 'awle',
                'al' => 'ale', // Michal -> Michale
                'l' => 'lu',
            ],
            'ł' => [
                'zioł' => 'źle',
                'ół' => 'ole',
                'eł' => 'le',
                'ł' => 'le',
            ],
            'm' => [
                'miriam' => 'miriam',
                'm' => 'mie',
            ],
            'n' => [
                'nikola' => 'nikolo',
                'syn' => 'synu',
                'n' => 'nie',
            ],
            'ń' => [
                'skroń' => 'skronio',
                'dzień' => 'dniu',
                'czeń' => 'czniu',
                'ń' => 'niu',
            ],
            'p' => [
                'p' => 'pie',
            ],
            'r' => [
                'per' => 'prze',
                'ór' => 'orze',
                'r' => 'rze',
            ],
            's' => [
                'ines' => 'ines',
                'ies' => 'sie',
                's' => 'sie',
            ],
            'ś' => [
                'gęś' => 'gęsio',
                'ś' => 'siu',
            ],
            't' => [
                'st' => 'ście',
                't' => 'cie',
            ],
            'w' => [
                'konew' => 'konwio',
                'sław' => 'sławie',
                'lew' => 'lwie',
                'łw' => 'łwiu',
                'ów' => 'owie',
                'w' => 'wie',
            ],
            'x' => [
                'x' => 'ksie',
            ],
            'z' => [
                'ksiądz' => 'księże',
                'dz' => 'dzu',
                'cz' => 'czu',
                'rz' => 'rzu',
                'sz' => 'szu',
                'óz' => 'ozie',
                'z' => 'zie',
            ],
            'ż' => [
                'ąż' => 'ężu',
                'ż' => 'żu',
            ],
        ];

        $firstname = trim(mb_strtolower(strtok($name, ' ')));

        $vocative = $firstname;
        if ($branch = @$vocativeRules[mb_substr($firstname, -1)])
        {
            while ($suffix = current($branch))
            {
                if (preg_match('/(.*)'.key($branch).'$/i', $firstname, $reg))
                {
                    $vocative = $reg[1].$suffix;
                    break;
                }
                next($branch);
            }
        }

        return self::ucFirst($vocative);
    }

    public static function ucFirst(string $txt): string
    {
        return mb_strtoupper(mb_substr($txt, 0, 1)) . mb_substr($txt, 1);
    }

    /**
     * @assert ('Łódź') == 'Lodz'
     * @param  string $txt
     * @return string
     */
    public static function toAscii(string $txt): string
    {
        $normalizeChars = [
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Ą'=>'A', 'Å'=>'A', 'Æ'=>'A',
            'Ç'=>'C', 'Ć'=>'C', 'Ę'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ł'=>'L', 'Ñ'=>'N', 'Ń'=>'N', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Ś'=>'S', 'Ż'=>'Z', 'Ź'=>'Z', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'ą'=>'a', 'å'=>'a', 'æ'=>'a',
            'ç'=>'c', 'ć'=>'c', 'ę'=>'e', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ł'=>'l', 'ð'=>'o', 'ń'=>'n', 'ñ'=>'n', 'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ś'=>'s', 'ż'=>'z', 'ź'=>'z', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
        ];

        return strtr($txt, $normalizeChars);
    }

    /**
     * @assert ('ProductName') == 'product_name'
     */
    public static function underscore(string $txt): string
    {
        $txt = preg_replace('/([A-Z\d]+)([A-Z][a-z])/', '$1_$2', $txt);
        $txt = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $txt);
        $txt = str_replace(['.', '-', ' '], '_', $txt);
        $txt = trim(preg_replace('/[^\w\d_]/si', '', $txt));
        $txt = preg_replace('/__+/', '_', $txt);
        $txt = trim($txt, '_');
        return strtolower($txt);
    }

    /**
     * @assert ('product_name') == 'ProductName'
     */
    public static function camelCase(string $txt): string
    {
        $txt = str_replace(['.', '-', '_'], ' ', $txt);
        $txt = ucwords($txt);
        return str_replace(' ', '', $txt);
    }

    /**
     * http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
     * @assert ('Łodź... bardzo SKOMPLIKOWANE?!') == 'łódź.-bardzo-skomplikowane'
     */
    public static function toUrl(string $txt, bool $transliterate = false): string
    {
        // remove tags
        $txt = strip_tags($txt);

        // remove nbsp;
        $txt = str_replace('&nbsp;', '-', $txt);

        // replace non letter or digits by -
        $txt = preg_replace('~[^\pL\d.]+~u', '-', $txt);

        // trim
        $txt = trim($txt, '-.');

        // remove duplicate - and .
        $txt = preg_replace('~-+~', '-', $txt);
        $txt = preg_replace('~\.+~', '.', $txt);

        // remove unwanted characters
        $txt = preg_replace('~[^-.\w]+~u', '', $txt);

        $txt = mb_strtolower($txt);

        if ($transliterate) {
            $txt = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $txt);
        }

        return !empty($txt)
            ? $txt : 'default';
    }

    public static function lineToUl(string $txt): ?string
    {
        if (empty($txt)) {
            return null;
        }

        $txt = trim($txt);
        $txt = str_ireplace("\r\n", "<li>", $txt);
        return "<ul>\n<li>".$txt."</ul>\n";
    }

    public static function shortVersion(string $txt, int $maxWords = 20): string
    {
        $content = strip_tags($txt);
        $content = preg_replace('/\[\[.*]]/U', '', $content);
        $content = str_replace('&nbsp;', ' ', $content);
        $content = str_replace("\r\n", ' ', $content);
        $content = str_replace("\n", ' ', $content);
        $content = preg_replace('/\[\[.*]]/U', '', $content);
        $content = preg_replace('/ +/', ' ', $content);

        $short = strtok($content, ' ').' ';
        for ($i = 0; $i < $maxWords-1; $i++)
        {
            $token = strtok(' ');
            $short .= $token.' ';
        }

        return trim($short);
    }
}
