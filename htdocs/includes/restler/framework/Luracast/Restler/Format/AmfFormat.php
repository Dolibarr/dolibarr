<?php
namespace Luracast\Restler\Format;

use ZendAmf\Parser\Amf3\Deserializer;
use ZendAmf\Parser\Amf3\Serializer;
use ZendAmf\Parser\InputStream;
use ZendAmf\Parser\OutputStream;

/**
 * AMF Binary Format for Restler Framework.
 * Native format supported by Adobe Flash and Adobe AIR
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
class AmfFormat extends DependentFormat
{
    const MIME = 'application/x-amf';
    const EXTENSION = 'amf';

    const PACKAGE_NAME = 'zendframework/zendamf:dev-master';
    const EXTERNAL_CLASS = 'ZendAmf\\Parser\\Amf3\\Deserializer';

    public function encode($data, $humanReadable = false)
    {

        $stream = new OutputStream();
        $serializer = new Serializer($stream);
        $serializer->writeTypeMarker($data);

        return $stream->getStream();
    }

    public function decode($data)
    {
        $stream = new InputStream(substr($data, 1));
        $deserializer = new Deserializer($stream);

        return $deserializer->readTypeMarker();
    }
}

