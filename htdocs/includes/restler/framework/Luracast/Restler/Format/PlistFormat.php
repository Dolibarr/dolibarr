<?php
namespace Luracast\Restler\Format;

use Luracast\Restler\Data\Obj;
use CFPropertyList\CFTypeDetector;
use CFPropertyList\CFPropertyList;

/**
 * Plist Format for Restler Framework.
 * Plist is the native data exchange format for Apple iOS and Mac platform.
 * Use this format to talk to mac applications and iOS devices.
 * This class is capable of serving both xml plist and binary plist.
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
class PlistFormat extends DependentMultiFormat
{
    /**
     * @var boolean set it to true binary plist is preferred
     */
    public static $compact = null;
    const MIME = 'application/xml,application/x-plist';
    const EXTENSION = 'plist';

    public function setMIME($mime)
    {
        static::$mime = $mime;
        static::$compact = $mime == 'application/x-plist';
    }

    /**
     * Encode the given data in plist format
     *
     * @param array   $data
     *            resulting data that needs to
     *            be encoded in plist format
     * @param boolean $humanReadable
     *            set to true when restler
     *            is not running in production mode. Formatter has to
     *            make the encoded output more human readable
     *
     * @return string encoded string
     */
    public function encode($data, $humanReadable = false)
    {
        //require_once 'CFPropertyList.php';
        if (!isset(self::$compact)) {
            self::$compact = !$humanReadable;
        }
        /**
         *
         * @var CFPropertyList
         */
        $plist = new CFPropertyList ();
        $td = new CFTypeDetector ();
        $guessedStructure = $td->toCFType(
            Obj::toArray($data)
        );
        $plist->add($guessedStructure);

        return self::$compact
            ? $plist->toBinary()
            : $plist->toXML(true);
    }

    /**
     * Decode the given data from plist format
     *
     * @param string $data
     *            data sent from client to
     *            the api in the given format.
     *
     * @return array associative array of the parsed data
     */
    public function decode($data)
    {
        $plist = new CFPropertyList ();
        $plist->parse($data);

        return $plist->toArray();
    }

    /**
     * Get external class => packagist package name as an associative array
     *
     * @return array list of dependencies for the format
     *
     * @example return ['Illuminate\\View\\View' => 'illuminate/view:4.2.*']
     */
    public function getDependencyMap()
    {
        return array(
            'CFPropertyList\CFPropertyList' => 'rodneyrehm/plist:dev-master'
        );
    }
}

