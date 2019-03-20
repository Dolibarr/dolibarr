<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataFormatter;

class DataFormatter implements DataFormatterInterface
{
    public function formatVar($data)
    {
        return $this->kintLite($data);
    }

    public function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

    public function formatBytes($size, $precision = 2)
    {
        if ($size === 0 || $size === null) {
            return "0B";
        }
        $base = log($size) / log(1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

     /**
     * lightweight version of Kint::dump(). Uses whitespace for formatting instead of html
     * sadly not DRY yet
     *
     * Extracted from Kint.class.php in raveren/kint, https://github.com/raveren/kint
     * Copyright (c) 2013 Rokas Šleinius (raveren@gmail.com)
     *
     * @param mixed $var
     * @param int $level
     *
     * @return string
     */
    protected function kintLite(&$var, $level = 0)
    {
        // initialize function names into variables for prettier string output (html and implode are also DRY)
        $html     = "htmlspecialchars";
        $implode  = "implode";
        $strlen   = "strlen";
        $count    = "count";
        $getClass = "get_class";

        if ( $var === null ) {
            return 'NULL';
        } elseif ( is_bool( $var ) ) {
            return 'bool ' . ( $var ? 'TRUE' : 'FALSE' );
        } elseif ( is_float( $var ) ) {
            return 'float ' . $var;
        } elseif ( is_int( $var ) ) {
            return 'integer ' . $var;
        } elseif ( is_resource( $var ) ) {
            if ( ( $type = get_resource_type( $var ) ) === 'stream' and $meta = stream_get_meta_data( $var ) ) {
                if ( isset( $meta['uri'] ) ) {
                    $file = $meta['uri'];

                    return "resource ({$type}) {$html( $file, 0 )}";
                } else {
                    return "resource ({$type})";
                }
            } else {
                return "resource ({$type})";
            }
        } elseif ( is_string( $var ) ) {
            return "string ({$strlen( $var )}) \"{$html( $var )}\"";
        } elseif ( is_array( $var ) ) {
            $output = array();
            $space  = str_repeat( $s = '    ', $level );

            static $marker;

            if ( $marker === null ) {
                // Make a unique marker
                $marker = uniqid( "\x00" );
            }

            if ( empty( $var ) ) {
                return "array()";
            } elseif ( isset( $var[$marker] ) ) {
                $output[] = "[\n$space$s*RECURSION*\n$space]";
            } elseif ( $level < 7 ) {
                $isSeq = array_keys( $var ) === range( 0, count( $var ) - 1 );

                $output[] = "[";

                $var[$marker] = true;

                foreach ( $var as $key => &$val ) {
                    if ( $key === $marker ) {
                        continue;
                    }

                    $key = $space . $s . ( $isSeq ? "" : "'{$html( $key, 0 )}' => " );

                    $dump     = $this->kintLite( $val, $level + 1 );
                    $output[] = "{$key}{$dump}";
                }

                unset( $var[$marker] );
                $output[] = "$space]";
            } else {
                $output[] = "[\n$space$s*depth too great*\n$space]";
            }
            return "array({$count( $var )}) {$implode( "\n", $output )}";
        } elseif ( is_object( $var ) ) {
            if ( $var instanceof SplFileInfo ) {
                return "object SplFileInfo " . $var->getRealPath();
            }

            // Copy the object as an array
            $array = (array) $var;

            $output = array();
            $space  = str_repeat( $s = '    ', $level );

            $hash = spl_object_hash( $var );

            // Objects that are being dumped
            static $objects = array();

            if ( empty( $array ) ) {
                return "object {$getClass( $var )} {}";
            } elseif ( isset( $objects[$hash] ) ) {
                $output[] = "{\n$space$s*RECURSION*\n$space}";
            } elseif ( $level < 7 ) {
                $output[]       = "{";
                $objects[$hash] = true;

                foreach ( $array as $key => & $val ) {
                    if ( $key[0] === "\x00" ) {
                        $access = $key[1] === "*" ? "protected" : "private";

                        // Remove the access level from the variable name
                        $key = substr( $key, strrpos( $key, "\x00" ) + 1 );
                    } else {
                        $access = "public";
                    }

                    $output[] = "$space$s$access $key -> " . $this->kintLite( $val, $level + 1 );
                }
                unset( $objects[$hash] );
                $output[] = "$space}";
            } else {
                $output[] = "{\n$space$s*depth too great*\n$space}";
            }

            return "object {$getClass( $var )} ({$count( $array )}) {$implode( "\n", $output )}";
        } else {
            return gettype( $var ) . htmlspecialchars( var_export( $var, true ), ENT_NOQUOTES );
        }
    }
}
