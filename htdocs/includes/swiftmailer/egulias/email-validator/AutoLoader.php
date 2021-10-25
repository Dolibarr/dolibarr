<?php

namespace Egulias;

/**
 * PSR-0 Autoloader
 *
 * @author ieter Hordijk <info@pieterhordijk.com>
 */
class EguliasAutoLoader
{
    /**
     * @var string The namespace prefix for this instance.
     */
    protected $namespace = '';

    /**
     * @var string The filesystem prefix to use for this instance
     */
    protected $path = '';

    /**
     * Build the instance of the autoloader
     *
     * @param string $namespace The prefixed namespace this instance will load
     * @param string $path      The filesystem path to the root of the namespace
     */
    public function __construct($namespace, $path)
    {
        $this->namespace = ltrim($namespace, '\\');
        $this->path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Try to load a class
     *
     * @param string $class The class name to load
     *
     * @return boolean If the loading was successful
     */
    public function load($class)
    {
        $class = ltrim($class, '\\');
        if (strpos($class, $this->namespace) === 0) {
            $nsparts = explode('\\', $class);
            $class = array_pop($nsparts);
            $path = $this->path . 'swiftmailer/egulias/email-validator/EmailValidator/';
            $max=count($nsparts);
            for ($i=2; $i<$max;$i++) {
                $path .= $nsparts[$i].'/';
            }
            $nsparts = array();
            $path .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
            if (file_exists($path)) {
                require $path;
                return true;
            }
        }

        return false;
    }

    /**
     * Register the autoloader to PHP
     *
     * @return boolean The status of the registration
     */
    public function register()
    {
        return spl_autoload_register(array($this, 'load'));
    }

    /**
     * Unregister the autoloader to PHP
     *
     * @return boolean The status of the unregistration
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this, 'load'));
    }
}
