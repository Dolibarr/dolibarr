<?php
namespace Luracast\Restler;

/**
 * Static Class to pass through content outside of web root
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class PassThrough
{
    public static $mimeTypes = array(
        'js' => 'text/javascript',
        'css' => 'text/css',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'html' => 'text/html',
    );

    /**
     * Serve a file outside web root
     *
     * Respond with a file stored outside web accessible path
     *
     * @param string $filename      full path for the file to be served
     * @param bool   $forceDownload should the we download instead of viewing
     * @param int    $expires       cache expiry in number of seconds
     * @param bool   $isPublic      cache control, is it public or private
     *
     * @throws RestException
     * @internal param string $pragma
     *
     */
    public static function file($filename, $forceDownload = false, $expires = 0, $isPublic = true)
    {
        if (!is_file($filename))
            throw new RestException(404);
        if (!is_readable($filename))
            throw new RestException(403);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!$mime = Util::nestedValue(static::$mimeTypes, $extension)) {
            if (!function_exists('finfo_open')) {
                throw new RestException(
                    500,
                    'Unable to find media type of ' .
                    basename($filename) .
                    ' either enable fileinfo php extension or update ' .
                    'PassThrough::$mimeTypes to include mime type for ' . $extension .
                    ' extension'
                );
            }
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filename);
        }
        if (!is_array(Defaults::$headerCacheControl))
            Defaults::$headerCacheControl = array(Defaults::$headerCacheControl);
        $cacheControl = Defaults::$headerCacheControl[0];
        if ($expires > 0) {
            $cacheControl = $isPublic ? 'public' : 'private';
            $cacheControl .= end(Defaults::$headerCacheControl);
            $cacheControl = str_replace('{expires}', $expires, $cacheControl);
            $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $expires);
        }
        header('Cache-Control: ' . $cacheControl);
        header('Expires: ' . $expires);
        $lastModified = filemtime($filename);
        if (
            isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified
        ) {
            header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
            exit;
        }
        header('Last-Modified: ' . date('r', $lastModified));
        header('X-Powered-By: Luracast Restler v' . Restler::VERSION);
        header('Content-type: ' . $mime);
        header("Content-Length: " . filesize($filename));
        if ($forceDownload) {
            header("Content-Transfer-Encoding: binary");
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        readfile($filename);
        exit;
    }
} 