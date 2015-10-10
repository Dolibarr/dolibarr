<?php
/**
 * Small helper class to inspect the stacktrace
 *
 * @package raven
 */
class Raven_Stacktrace
{
    public static $statements = array(
        'include',
        'include_once',
        'require',
        'require_once',
    );

    public static function get_stack_info($frames, $trace = false, $shiftvars = true, $errcontext = null,
                            $frame_var_limit = Raven_Client::MESSAGE_LIMIT)
    {
        /**
         * PHP's way of storing backstacks seems bass-ackwards to me
         * 'function' is not the function you're in; it's any function being
         * called, so we have to shift 'function' down by 1. Ugh.
         */
        $result = array();
        for ($i = 0; $i < count($frames); $i++) {
            $frame = $frames[$i];
            $nextframe = isset($frames[$i + 1]) ? $frames[$i + 1] : null;

            if (!array_key_exists('file', $frame)) {
                // XXX: Disable capturing of anonymous functions until we can implement a better grouping mechanism.
                // In our examples these generally didn't help with debugging as the information was found elsewhere
                // within the exception or the stacktrace
                continue;
                // if (isset($frame['args'])) {
                //     $args = is_string($frame['args']) ? $frame['args'] : @json_encode($frame['args']);
                // }
                // else {
                //     $args = array();
                // }
                // if (!empty($nextframe['class'])) {
                //     $context['line'] = sprintf('%s%s%s(%s)',
                //         $nextframe['class'], $nextframe['type'], $nextframe['function'],
                //         $args);
                // }
                // else {
                //     $context['line'] = sprintf('%s(%s)', $nextframe['function'], $args);
                // }
                // $abs_path = '';
                // $context['prefix'] = '';
                // $context['suffix'] = '';
                // $context['filename'] = $filename = '[Anonymous function]';
                // $context['lineno'] = 0;
            } else {
                $context = self::read_source_file($frame['file'], $frame['line']);
                $abs_path = $frame['file'];
                $filename = basename($frame['file']);
            }

            $module = $filename;
            if (isset($nextframe['class'])) {
                $module .= ':' . $nextframe['class'];
            }

            if (empty($result) && isset($errcontext)) {
                // If we've been given an error context that can be used as the vars for the first frame.
                $vars = $errcontext;
            } else {
                if ($trace) {
                    if ($shiftvars) {
                        $vars = self::get_frame_context($nextframe, $frame_var_limit);
                    } else {
                        $vars = self::get_caller_frame_context($frame);
                    }
                } else {
                    $vars = array();
                }
            }

            $frame = array(
                'abs_path' => $abs_path,
                'filename' => $context['filename'],
                'lineno' => (int) $context['lineno'],
                'module' => $module,
                'function' => $nextframe['function'],
                'pre_context' => $context['prefix'],
                'context_line' => $context['line'],
                'post_context' => $context['suffix'],
            );
            // dont set this as an empty array as PHP will treat it as a numeric array
            // instead of a mapping which goes against the defined Sentry spec
            if (!empty($vars)) {
                foreach ($vars as $key => $value) {
                    if (is_string($value) || is_numeric($value)) {
                        $vars[$key] = substr($value, 0, $frame_var_limit);
                    }
                }
                $frame['vars'] = $vars;
            }

            $result[] = $frame;
        }

        return array_reverse($result);
    }

    public static function get_caller_frame_context($frame)
    {
        if (!isset($frame['args'])) {
            return array();
        }

        $i = 1;
        $args = array();
        foreach ($frame['args'] as $arg) {
            $args['param'.$i] = $arg;
            $i++;
        }
        return $args;
    }

    public static function get_frame_context($frame, $frame_arg_limit = Raven_Client::MESSAGE_LIMIT)
    {
        // The reflection API seems more appropriate if we associate it with the frame
        // where the function is actually called (since we're treating them as function context)
        if (!isset($frame['function'])) {
            return array();
        }

        if (!isset($frame['args'])) {
            return array();
        }

        if (strpos($frame['function'], '__lambda_func') !== false) {
            return array();
        }
        if (isset($frame['class']) && $frame['class'] == 'Closure') {
            return array();
        }
        if (strpos($frame['function'], '{closure}') !== false) {
            return array();
        }
        if (in_array($frame['function'], self::$statements)) {
            if (empty($frame['args'])) {
                // No arguments
                return array();
            } else {
                // Sanitize the file path
                return array($frame['args'][0]);
            }
        }
        try {
            if (isset($frame['class'])) {
                if (method_exists($frame['class'], $frame['function'])) {
                    $reflection = new ReflectionMethod($frame['class'], $frame['function']);
                } elseif ($frame['type'] === '::') {
                    $reflection = new ReflectionMethod($frame['class'], '__callStatic');
                } else {
                    $reflection = new ReflectionMethod($frame['class'], '__call');
                }
            } else {
                $reflection = new ReflectionFunction($frame['function']);
            }
        } catch (ReflectionException $e) {
            return array();
        }

        $params = $reflection->getParameters();

        $args = array();
        foreach ($frame['args'] as $i => $arg) {
            if (isset($params[$i])) {
                // Assign the argument by the parameter name
                if (is_array($arg)) {
                    foreach ($arg as $key => $value) {
                        if (is_string($value) || is_numeric($value)) {
                            $arg[$key] = substr($value, 0, $frame_arg_limit);
                        }
                    }
                }
                $args[$params[$i]->name] = $arg;
            } else {
                // TODO: Sentry thinks of these as context locals, so they must be named
                // Assign the argument by number
                // $args[$i] = $arg;
            }
        }

        return $args;
    }

    private static function read_source_file($filename, $lineno, $context_lines = 5)
    {
        $frame = array(
            'prefix' => array(),
            'line' => '',
            'suffix' => array(),
            'filename' => $filename,
            'lineno' => $lineno,
        );

        if ($filename === null || $lineno === null) {
            return $frame;
        }

        // Code which is eval'ed have a modified filename.. Extract the
        // correct filename + linenumber from the string.
        $matches = array();
        $matched = preg_match("/^(.*?)\((\d+)\) : eval\(\)'d code$/",
            $filename, $matches);
        if ($matched) {
            $frame['filename'] = $filename = $matches[1];
            $frame['lineno'] = $lineno = $matches[2];
        }

        // In the case of an anonymous function, the filename is sent as:
        // "</path/to/filename>(<lineno>) : runtime-created function"
        // Extract the correct filename + linenumber from the string.
        $matches = array();
        $matched = preg_match("/^(.*?)\((\d+)\) : runtime-created function$/",
            $filename, $matches);
        if ($matched) {
            $frame['filename'] = $filename = $matches[1];
            $frame['lineno'] = $lineno = $matches[2];
        }

        try {
            $file = new SplFileObject($filename);
            $target = max(0, ($lineno - ($context_lines + 1)));
            $file->seek($target);
            $cur_lineno = $target+1;
            while (!$file->eof()) {
                $line = rtrim($file->current(), "\r\n");
                if ($cur_lineno == $lineno) {
                    $frame['line'] = $line;
                } elseif ($cur_lineno < $lineno) {
                    $frame['prefix'][] = $line;
                } elseif ($cur_lineno > $lineno) {
                    $frame['suffix'][] = $line;
                }
                $cur_lineno++;
                if ($cur_lineno > $lineno + $context_lines) {
                    break;
                }
                $file->next();
            }
        } catch (RuntimeException $exc) {
            return $frame;
        }

        return $frame;
    }
}
