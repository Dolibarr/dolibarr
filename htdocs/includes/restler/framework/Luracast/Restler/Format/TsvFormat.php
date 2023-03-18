<?php
namespace Luracast\Restler\Format;

/**
 * Tab Separated Value Format
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
class TsvFormat extends CsvFormat
{
    const MIME = 'text/csv';
    const EXTENSION = 'csv';
    public static $delimiter = "\t";
    public static $enclosure = '"';
    public static $escape = '\\';
    public static $haveHeaders = null;
}