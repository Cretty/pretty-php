<?php

namespace net\shawn_huang\pretty;
/**
 * String utils.
 */
class StringUtil {

    /**
     * Check whether given string is ends with needle.
     * @param string $str string to search
     * @param string $needle searching content
     * @return bool true if str is ends with needle.
     */
    public static function endsWith($str, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($str, -$length) === $needle);
    }

    /**
     * Check whether given string starts with needle.
     * @param string $str string to search
     * @param string $needle searching content
     * @return bool true if str starts with needle.
     */
    public static function startWith($str, $needle) {
        $length = strlen($needle);
        return (substr($str, 0, $length) === $needle);
    }

    /**
     * Parse string into Pcasal style.
     * @param string $ori origin string
     * @return string parsed string
     */
    public static function toPascalCase($ori) {
        return ucfirst(self::toCamelCase($ori));
    }

    /**
     * Parse string into Camel style.
     * @param string $ori origin string
     * @return string parsed string
     */
    public static function toCamelCase($ori, $exp = '/_([a-z])/') {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback($exp, $func, $ori);
    }

    /**
     * Get a tail word of camel style.
     * @param string $str string to handle
     * @return array returns an array contains the head and the tail.
     */
    public static function getCamelTail($str, $exp = '/^(.+)([A-Z][a-z0-9]+)$/') {
        preg_match($exp, $str, $result);
        return count($result) == 0 ? array($str, '') : array($result[1], $result[2]);
    }

}
