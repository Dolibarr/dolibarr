<?php

/**
 * Alias de str_pad con compatibilidad con UTF-8
 *
 * @param string $string Cadena de origen
 * @param int $length Longitud establecida
 * @param string $fill Cadena de relleno
 * @return string
 */
function af_fill($string, $length, $fill) {

    mb_internal_encoding('UTF-8');

    $str_length = mb_strlen($string);
    $str_diff = $length - $str_length;

    if ($str_diff > 0) {
        $return = $string.str_repeat($fill, $str_diff);
    } else {
        $return = mb_substr($string, 0, $length);
    }

    return $return;

}