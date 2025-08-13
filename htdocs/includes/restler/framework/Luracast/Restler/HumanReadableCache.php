<?php
namespace Luracast\Restler;

/**
 * Default Cache that writes/reads human readable files for caching purpose
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class HumanReadableCache implements iCache
{
    /**
     * @var string path of the folder to hold cache files
     */
    public static $cacheDir;

    public function __construct()
    {
        if (is_null(self::$cacheDir)) {
            self::$cacheDir = Defaults::$cacheDirectory;
        }
    }

    /**
     * store data in the cache
     *
     * @param string $name
     * @param mixed  $data
     *
     * @throws \Exception
     * @return boolean true if successful
     */
    public function set($name, $data)
    {
        if (is_array($data)) {
            $s = '$o = array();' . PHP_EOL . PHP_EOL;
            $s .= '// ** THIS IS AN AUTO GENERATED FILE.'
                . ' DO NOT EDIT MANUALLY ** ';
            foreach ($data as $key => $value) {
                $s .= PHP_EOL . PHP_EOL .
                    "//==================== $key ===================="
                    . PHP_EOL . PHP_EOL;
                if (is_array($value)) {
                    $s .= '$o[\'' . $key . '\'] = array();';
                    foreach ($value as $ke => $va) {
                        $s .= PHP_EOL . PHP_EOL . "//==== $key $ke ===="
                            . PHP_EOL . PHP_EOL;
                        $s .= '$o[\'' . $key . '\'][\'' . $ke . '\'] = ' .
                            str_replace('  ', '    ',
                                var_export($va, true)) . ';';
                    }
                } else {
                    $s .= '$o[\'' . $key . '\'] = '
                        . var_export($value, true) . ';';
                }
            }
            $s .= PHP_EOL . 'return $o;';
        } else {
            $s = 'return ' . var_export($data, true) . ';';
        }
        $file = $this->_file($name);
        $r = @file_put_contents($file, "<?php $s");
        @chmod($file, 0777);
        if ($r === false) {
            $this->throwException();
        }
        return $r;
    }

    /**
     * retrieve data from the cache
     *
     * @param string $name
     * @param bool   $ignoreErrors
     *
     * @return mixed
     */
    public function get($name, $ignoreErrors = false)
    {
        $file = $this->_file($name);
        if (file_exists($file)) {
            return include($file);
        }
    }

    /**
     * delete data from the cache
     *
     * @param string $name
     * @param bool   $ignoreErrors
     *
     * @return boolean true if successful
     */
    public function clear($name, $ignoreErrors = false)
    {
        return @unlink($this->_file($name));
    }

    /**
     * check if the given name is cached
     *
     * @param string $name
     *
     * @return boolean true if cached
     */
    public function isCached($name)
    {
        return file_exists($this->_file($name));
    }

    private function _file($name)
    {
        return self::$cacheDir . '/' . $name . '.php';
    }

    private function throwException()
    {
        throw new \Exception(
            'The cache directory `'
            . self::$cacheDir . '` should exist with write permission.'
        );
    }
}

