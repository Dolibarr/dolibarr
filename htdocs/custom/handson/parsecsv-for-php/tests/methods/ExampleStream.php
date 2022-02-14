<?php

namespace ParseCsv\tests\methods;

/**
 * This is a very simple implementation of a stream wrapper. All URLs are mapped
 * to just one buffer. It exists to prove that ParseCsv can read and write
 * streams.
 *
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
class ExampleStream {

    private static $position = 0;

    private static $stream_content;

    public function stream_open($uri, $mode) {
        if (strpos($mode, 'a') === false) {
            self::$position = 0;
        }
        if (strpos($mode, 'w') !== false) {
            self::$stream_content = '';
        }
        return true;
    }

    public function stream_read($count) {
        $ret = substr(self::$stream_content, self::$position, $count);
        self::$position += strlen($ret);
        return $ret;
    }

    public function stream_write($data) {
        $left = substr(self::$stream_content, 0, self::$position);
        $right = substr(self::$stream_content, self::$position + strlen($data));
        self::$stream_content = $left . $data . $right;
        self::$position += strlen($data);
        return strlen($data);
    }

    public function stream_stat() {
        return ['size' => strlen(self::$stream_content)];
    }


    public function stream_tell() {
        return self::$position;
    }

    public function stream_eof() {
        return self::$position >= strlen(self::$stream_content);
    }

    public function url_stat() {
        return ['size' => strlen(self::$stream_content)];
    }

    public function stream_seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen(self::$stream_content) && $offset >= 0) {
                    self::$position = $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                    self::$position += $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            case SEEK_END:
                if (strlen(self::$stream_content) + $offset >= 0) {
                    self::$position = strlen(self::$stream_content) + $offset;
                    return true;
                } else {
                    return false;
                }
                break;

            default:
                return false;
        }
    }

    public function stream_lock($operation) {
        return true;
    }

    public function stream_metadata() {
        return false;
    }
}
