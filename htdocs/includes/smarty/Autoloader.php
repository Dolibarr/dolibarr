<?php
/**
 * Smarty Autoloader
 *
 * @package    Smarty
 */

/**
 * Smarty Autoloader
 *
 * @package    Smarty
 * @author     Uwe Tews
 *             Usage:
 *             require_once '...path/Autoloader.php';
 *             Smarty_Autoloader::register();
 *             $smarty = new Smarty();
 *             Note:       This autoloader is not needed if you use Composer.
 *             Composer will automatically add the classes of the Smarty package to it common autoloader.
 */
class Smarty_Autoloader
{
    /**
     * Filepath to Smarty root
     *
     * @var string
     */
    public static $SMARTY_DIR = '';

    /**
     * Filepath to Smarty internal plugins
     *
     * @var string
     */
    public static $SMARTY_SYSPLUGINS_DIR = '';

    /**
     * Array with Smarty core classes and their filename
     *
     * @var array
     */
    public static $rootClasses = array('smarty' => 'Smarty.class.php', 'smartybc' => 'SmartyBC.class.php',);

    /**
     * Registers Smarty_Autoloader backward compatible to older installations.
     *
     * @param bool $prepend Whether to prepend the autoloader or not.
     */
    public static function registerBC($prepend = false)
    {
        /**
         * register the class autoloader
         */
        if (!defined('SMARTY_SPL_AUTOLOAD')) {
            define('SMARTY_SPL_AUTOLOAD', 0);
        }
        if (SMARTY_SPL_AUTOLOAD &&
            set_include_path(get_include_path() . PATH_SEPARATOR . SMARTY_SYSPLUGINS_DIR) !== false
        ) {
            $registeredAutoLoadFunctions = spl_autoload_functions();
            if (!isset($registeredAutoLoadFunctions[ 'spl_autoload' ])) {
                spl_autoload_register();
            }
        } else {
            self::register($prepend);
        }
    }

    /**
     * Registers Smarty_Autoloader as an SPL autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not.
     */
    public static function register($prepend = false)
    {
        self::$SMARTY_DIR = defined('SMARTY_DIR') ? SMARTY_DIR : dirname(__FILE__) . DIRECTORY_SEPARATOR;
        self::$SMARTY_SYSPLUGINS_DIR = defined('SMARTY_SYSPLUGINS_DIR') ? SMARTY_SYSPLUGINS_DIR :
            self::$SMARTY_DIR . 'sysplugins' . DIRECTORY_SEPARATOR;
        if (version_compare(phpversion(), '5.3.0', '>=')) {
            spl_autoload_register(array(__CLASS__, 'autoload'), true, $prepend);
        } else {
            spl_autoload_register(array(__CLASS__, 'autoload'));
        }
    }

    /**
     * Handles auto loading of classes.
     *
     * @param string $class A class name.
     */
    public static function autoload($class)
    {
        $_class = strtolower($class);
        if (strpos($_class, 'smarty') !== 0) {
            return;
        }
        $file = self::$SMARTY_SYSPLUGINS_DIR . $_class . '.php';
        if (is_file($file)) {
            include $file;
        } else if (isset(self::$rootClasses[ $_class ])) {
            $file = self::$SMARTY_DIR . self::$rootClasses[ $_class ];
            if (is_file($file)) {
                include $file;
            }
        }
        return;
    }
}
