<?php
namespace Luracast\Restler\Format;


use Luracast\Restler\RestException;

abstract class DependentMultiFormat extends MultiFormat
{
    /**
     * Get external class => packagist package name as an associative array
     *
     * @return array list of dependencies for the format
     *
     * @example return ['Illuminate\\View\\View' => 'illuminate/view:4.2.*']
     */
    abstract public function getDependencyMap();

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