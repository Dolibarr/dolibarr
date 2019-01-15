<?php
namespace Luracast\Restler\Format;

use Luracast\Restler\RestException;

/**
 * Support for Multi Part Form Data and File Uploads
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
class UploadFormat extends Format
{
    const MIME = 'multipart/form-data';
    const EXTENSION = 'post';
    public static $errors = array(
        0 => false,
        1 => "The uploaded file exceeds the maximum allowed size",
        2 => "The uploaded file exceeds the maximum allowed size",
        3 => "The uploaded file was only partially uploaded",
        4 => "No file was uploaded",
        6 => "Missing a temporary folder",
        7 => "Failed to write file to disk",
        8 => "A PHP extension stopped the file upload"
    );
    /**
     * use it if you need to restrict uploads based on file type
     * setting it as an empty array allows all file types
     * default is to allow only png and jpeg images
     *
     * @var array
     */
    public static $allowedMimeTypes = array('image/jpeg', 'image/png');
    /**
     * use it to restrict uploads based on file size
     * set it to 0 to allow all sizes
     * please note that it upload restrictions in the server
     * takes precedence so it has to be lower than or equal to that
     * default value is 1MB (1024x1024)bytes
     * usual value for the server is 8388608
     *
     * @var int
     */
    public static $maximumFileSize = 1048576;
    /**
     * Your own validation function for validating each uploaded file
     * it can return false or throw an exception for invalid file
     * use anonymous function / closure in PHP 5.3 and above
     * use function name in other cases
     *
     * @var Callable
     */
    public static $customValidationFunction;
    /**
     * Since exceptions are triggered way before at the `get` stage
     *
     * @var bool
     */
    public static $suppressExceptionsAsError = false;

    protected static function checkFile(& $file, $doMimeCheck = false, $doSizeCheck = false)
    {
        try {
            if ($file['error']) {
                //server is throwing an error
                //assume that the error is due to maximum size limit
                throw new RestException($file['error'] > 5 ? 500 : 413, static::$errors[$file['error']]);
            }
            $typeElements = explode('/', $file['type']);
            $genericType = $typeElements[0].'/*';
            if (
                $doMimeCheck
                && !(
                    in_array($file['type'], self::$allowedMimeTypes)
                    || in_array($genericType, self::$allowedMimeTypes)
                )
            ) {
                throw new RestException(403, "File type ({$file['type']}) is not supported.");
            }
            if ($doSizeCheck && $file['size'] > self::$maximumFileSize) {
                throw new RestException(413, "Uploaded file ({$file['name']}) is too big.");
            }
            if (self::$customValidationFunction) {
                if (!call_user_func(self::$customValidationFunction, $file)) {
                    throw new RestException(403, "File ({$file['name']}) is not supported.");
                }
            }
        } catch (RestException $e) {
            if (static::$suppressExceptionsAsError) {
                $file['error'] = $e->getCode() == 413 ? 1 : 6;
                $file['exception'] = $e;
            } else {
                throw $e;
            }
        }
    }

    public function encode($data, $humanReadable = false)
    {
        throw new RestException(500, 'UploadFormat is read only');
    }

    public function decode($data)
    {
        $doMimeCheck = !empty(self::$allowedMimeTypes);
        $doSizeCheck = self::$maximumFileSize ? TRUE : FALSE;
        //validate
        foreach ($_FILES as & $file) {
            if (is_array($file['error'])) {
                foreach ($file['error'] as $i => $error) {
                    $innerFile = array();
                    foreach ($file as $property => $value) {
                        $innerFile[$property] = $value[$i];
                    }
                    if ($innerFile['name'])
                        static::checkFile($innerFile, $doMimeCheck, $doSizeCheck);

                    if (isset($innerFile['exception'])) {
                        $file['error'][$i] = $innerFile['error'];
                        $file['exception'] = $innerFile['exception'];
                        break;
                    }
                }
            } else {
                if ($file['name'])
                    static::checkFile($file, $doMimeCheck, $doSizeCheck);
                if (isset($innerFile['exception'])) {
                    break;
                }
            }
        }
        //sort file order if needed;
        return UrlEncodedFormat::decoderTypeFix($_FILES + $_POST);
    }

    function isWritable()
    {
        return false;
    }

}
