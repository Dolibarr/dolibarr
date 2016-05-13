<?php
namespace Luracast\Restler\Format;


use Luracast\Restler\Data\Object;
use Luracast\Restler\RestException;

/**
 * Comma Separated Value Format
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class CsvFormat extends Format implements iDecodeStream
{

    const MIME = 'text/csv';
    const EXTENSION = 'csv';
    public static $delimiter = ',';
    public static $enclosure = '"';
    public static $escape = '\\';
    public static $haveHeaders = null;

    /**
     * Encode the given data in the csv format
     *
     * @param array   $data
     *            resulting data that needs to
     *            be encoded in the given format
     * @param boolean $humanReadable
     *            set to TRUE when restler
     *            is not running in production mode. Formatter has to
     *            make the encoded output more human readable
     *
     * @return string encoded string
     *
     * @throws RestException 500 on unsupported data
     */
    public function encode($data, $humanReadable = false)
    {
        $char = Object::$separatorChar;
        Object::$separatorChar = false;
        $data = Object::toArray($data);
        Object::$separatorChar = $char;
        if (is_array($data) && array_values($data) == $data) {
            //if indexed array
            $lines = array();
            $row = array_shift($data);
            if (array_values($row) != $row) {
                $lines[] = static::putRow(array_keys($row));
            }
            $lines[] = static::putRow(array_values($row));
            foreach ($data as $row) {
                $lines[] = static::putRow(array_values($row));
            }
            return implode(PHP_EOL, $lines) . PHP_EOL;
        }
        throw new RestException(
            500,
            'Unsupported data for ' . strtoupper(static::EXTENSION) . ' format'
        );
    }

    protected static function putRow($data)
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $data, static::$delimiter, static::$enclosure);
        rewind($fp);
        $data = fread($fp, 1048576);
        fclose($fp);
        return rtrim($data, PHP_EOL);
    }

    /**
     * Decode the given data from the csv format
     *
     * @param string $data
     *            data sent from client to
     *            the api in the given format.
     *
     * @return array associative array of the parsed data
     */
    public function decode($data)
    {
        $decoded = array();

        if (empty($data)) {
            return $decoded;
        }

        $lines = array_filter(explode(PHP_EOL, $data));

        $keys = false;
        $row = static::getRow(array_shift($lines));

        if (is_null(static::$haveHeaders)) {
            //try to guess with the given data
            static::$haveHeaders = !count(array_filter($row, 'is_numeric'));
        }

        static::$haveHeaders ? $keys = $row : $decoded[] = $row;

        while (($row = static::getRow(array_shift($lines), $keys)) !== FALSE)
            $decoded [] = $row;

        $char = Object::$separatorChar;
        Object::$separatorChar = false;
        $decoded = Object::toArray($decoded);
        Object::$separatorChar = $char;
        return $decoded;
    }

    protected static function getRow($data, $keys = false)
    {
        if (empty($data)) {
            return false;
        }
        $line = str_getcsv(
            $data,
            static::$delimiter,
            static::$enclosure,
            static::$escape
        );

        $row = array();
        foreach ($line as $key => $value) {
            if (is_numeric($value))
                $value = floatval($value);
            if ($keys) {
                if (isset($keys [$key]))
                    $row [$keys [$key]] = $value;
            } else {
                $row [$key] = $value;
            }
        }
        if ($keys) {
            for ($i = count($row); $i < count($keys); $i++) {
                $row[$keys[$i]] = null;
            }
        }
        return $row;
    }

    /**
     * Decode the given data stream
     *
     * @param string $stream A stream resource with data
     *                       sent from client to the api
     *                       in the given format.
     *
     * @return array associative array of the parsed data
     */
    public function decodeStream($stream)
    {
        $decoded = array();

        $keys = false;
        $row = static::getRow(stream_get_line($stream, 0, PHP_EOL));
        if (is_null(static::$haveHeaders)) {
            //try to guess with the given data
            static::$haveHeaders = !count(array_filter($row, 'is_numeric'));
        }

        static::$haveHeaders ? $keys = $row : $decoded[] = $row;

        while (($row = static::getRow(stream_get_line($stream, 0, PHP_EOL), $keys)) !== FALSE)
            $decoded [] = $row;

        $char = Object::$separatorChar;
        Object::$separatorChar = false;
        $decoded = Object::toArray($decoded);
        Object::$separatorChar = $char;
        return $decoded;
    }
}