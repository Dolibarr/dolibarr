<?php
namespace Luracast\Restler\Format;

use Symfony\Component\Yaml\Yaml;
use Luracast\Restler\Data\Obj;

/**
 * YAML Format for Restler Framework
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
class YamlFormat extends DependentFormat
{
    const MIME = 'text/plain';
    const EXTENSION = 'yaml';

    const PACKAGE_NAME = 'symfony/yaml:*';
    const EXTERNAL_CLASS = 'Symfony\Component\Yaml\Yaml';

    public function encode($data, $humanReadable = false)
    {
        return @Yaml::dump(Obj::toArray($data), $humanReadable ? 10 : 4);
    }

    public function decode($data)
    {
        return Yaml::parse($data);
    }
}

