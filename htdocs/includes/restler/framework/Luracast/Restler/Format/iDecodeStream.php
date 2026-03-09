<?php
namespace Luracast\Restler\Format;

/**
 * Interface for creating formats that accept steams for decoding
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iDecodeStream
{

    /**
     * Decode the given data stream
     *
     * @param string $stream A stream resource with data
     *                       sent from client to the api
     *                       in the given format.
     *
     * @return array associative array of the parsed data
     */
    public function decodeStream($stream);

} 