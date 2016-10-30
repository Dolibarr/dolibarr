<?php
/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

/**
 * This helper is based on code from Facebook's Phabricator project
 *
 *   https://github.com/facebook/phabricator
 *
 * Specifically, it is an adaptation of the PhutilReadableSerializer class.
 *
 * @package raven
 */
class Raven_Serializer
{
    /**
     * Serialize an object (recursively) into something safe for data
     * sanitization and encoding.
     */
    public static function serialize($value, $max_depth=9, $_depth=0)
    {
        if (is_object($value) || is_resource($value)) {
            return self::serializeValue($value);
        } elseif ($_depth < $max_depth && is_array($value)) {
            $new = array();
            foreach ($value as $k => $v) {
                $new[self::serializeValue($k)] = self::serialize($v, $max_depth, $_depth + 1);
            }

            return $new;
        } else {
            return self::serializeValue($value);
        }
    }

    public static function serializeValue($value)
    {
        if ($value === null) {
            return 'null';
        } elseif ($value === false) {
            return 'false';
        } elseif ($value === true) {
            return 'true';
        } elseif (is_float($value) && (int) $value == $value) {
            return $value.'.0';
        } elseif (is_object($value) || gettype($value) == 'object') {
            return 'Object '.get_class($value);
        } elseif (is_resource($value)) {
            return 'Resource '.get_resource_type($value);
        } elseif (is_array($value)) {
            return 'Array of length ' . count($value);
        } elseif (is_integer($value)) {
            return (integer) $value;
        } else {
            $value = (string) $value;

            if (function_exists('mb_convert_encoding')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            return $value;
        }
    }
}
