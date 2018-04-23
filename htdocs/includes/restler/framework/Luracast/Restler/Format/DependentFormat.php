<?php
namespace Luracast\Restler\Format;


use Luracast\Restler\RestException;

abstract class DependentFormat extends Format
{
    /**
     * override in the extending class
     *
     * @example symfony/yaml:*
     */
    const PACKAGE_NAME = 'vendor/project:version';

    /**
     * override in the extending class
     *
     * fully qualified class name
     */
    const EXTERNAL_CLASS = 'Namespace\\ClassName';

    /**
     * Get external class => packagist package name as an associative array
     *
     * @return array list of dependencies for the format
     */
    public function getDependencyMap()
    {
        return array(
            static::EXTERNAL_CLASS => static::PACKAGE_NAME
        );
    }

    protected function checkDependency($class = null)
    {
        if (empty($class)) {
            $class = key($this->getDependencyMap());
        }
        if (!class_exists($class, true)) {
            $map = $this->getDependencyMap();
            $package = $map[$class];
            throw new RestException(
                500,
                get_called_class() . ' has external dependency. Please run `composer require ' .
                $package . '` from the project root. Read https://getcomposer.org for more info'
            );
        }
    }

    public function __construct()
    {
        $this->checkDependency();
    }

} 