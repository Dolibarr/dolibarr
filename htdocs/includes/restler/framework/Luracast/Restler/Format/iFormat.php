<?php
namespace Luracast\Restler\Format;

/**
 * Interface for creating custom data formats
 * like xml, json, yaml, amf etc
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iFormat
{
    /**
     * Get MIME type => Extension mappings as an associative array
     *
     * @return array list of mime strings for the format
     * @example array('application/json'=>'json');
     */
    public function getMIMEMap();

    /**
     * Set the selected MIME type
     *
     * @param string $mime
     *            MIME type
     */
    public function setMIME($mime);

    /**
     * Content-Type field of the HTTP header can send a charset
     * parameter in the HTTP header to specify the character
     * encoding of the document.
     * This information is passed
     * here so that Format class can encode data accordingly
     * Format class may choose to ignore this and use its
     * default character set.
     *
     * @param string $charset
     *            Example utf-8
     */
    public function setCharset($charset);

    /**
     * Content-Type accepted by the Format class
     *
     * @return string $charset Example utf-8
     */
    public function getCharset();

    /**
     * Get selected MIME type
     */
    public function getMIME();

    /**
     * Set the selected file extension
     *
     * @param string $extension
     *            file extension
     */
    public function setExtension($extension);

    /**
     * Get the selected file extension
     *
     * @return string file extension
     */
    public function getExtension();

    /**
     * Encode the given data in the format
     *
     * @param array $data
     *            resulting data that needs to
     *            be encoded in the given format
     * @param boolean $humanReadable
     *            set to TRUE when restler
     *            is not running in production mode. Formatter has to
     *            make the encoded output more human readable
     * @return string encoded string
     */
    public function encode($data, $humanReadable = false);

    /**
     * Decode the given data from the format
     *
     * @param string $data
     *            data sent from client to
     *            the api in the given format.
     * @return array associative array of the parsed data
     */
    public function decode($data);

    /**
     * @return boolean is parsing the request supported?
     */
    public function isReadable();

    /**
     * @return boolean is composing response supported?
     */
    public function isWritable();
}

