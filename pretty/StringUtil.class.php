<?php

namespace net\shawn_huang\pretty;

class StringUtil {

    public static function endsWith($str, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($str, -$length) === $needle);
    }

    public static function startWith($str, $needle) {
        $length = strlen($needle);
        return (substr($str, 0, $length) === $needle);
    }

    public static function toPascalCase($ori) {
        return ucfirst(self::toCamelCase($ori));
    }

    public static function toCamelCase($ori) {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $ori);
    }

    public static function getCamelTail($str) {
        $exp = '/^(.+)([A-Z][a-z0-9]+)$/';
        preg_match($exp, $str, $result);
        return count($result) == 0 ? array($str, '') : array($result[1], $result[2]);    
    }

}
