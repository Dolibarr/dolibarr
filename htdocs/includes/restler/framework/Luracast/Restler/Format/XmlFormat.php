<?php
namespace Luracast\Restler\Format;

use Luracast\Restler\Data\Object;
use Luracast\Restler\RestException;
use SimpleXMLElement;
use XMLWriter;

/**
 * XML Markup Format for Restler Framework
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
class XmlFormat extends Format
{
    const MIME = 'application/xml';
    const EXTENSION = 'xml';

    // ==================================================================
    //
    // Properties related to reading/parsing/decoding xml
    //
    // ------------------------------------------------------------------
    public static $importSettingsFromXml = false;
    public static $parseAttributes = true;
    public static $parseNamespaces = true;
    public static $parseTextNodeAsProperty = true;

    // ==================================================================
    //
    // Properties related to writing/encoding xml
    //
    // ------------------------------------------------------------------
    public static $useTextNodeProperty = true;
    public static $useNamespaces = true;
    public static $cdataNames = array();

    // ==================================================================
    //
    // Common Properties
    //
    // ------------------------------------------------------------------
    public static $attributeNames = array();
    public static $textNodeName = 'text';
    public static $namespaces = array();
    public static $namespacedProperties = array();
    /**
     * Default name for the root node.
     *
     * @var string $rootNodeName
     */
    public static $rootName = 'response';
    public static $defaultTagName = 'item';

    /**
     * When you decode an XML its structure is copied to the static vars
     * we can use this function to echo them out and then copy paste inside
     * our service methods
     *
     * @return string PHP source code to reproduce the configuration
     */
    public static function exportCurrentSettings()
    {
        $s = 'XmlFormat::$rootName = "' . (self::$rootName) . "\";\n";
        $s .= 'XmlFormat::$attributeNames = ' .
            (var_export(self::$attributeNames, true)) . ";\n";
        $s .= 'XmlFormat::$defaultTagName = "' .
            self::$defaultTagName . "\";\n";
        $s .= 'XmlFormat::$parseAttributes = ' .
            (self::$parseAttributes ? 'true' : 'false') . ";\n";
        $s .= 'XmlFormat::$parseNamespaces = ' .
            (self::$parseNamespaces ? 'true' : 'false') . ";\n";
        if (self::$parseNamespaces) {
            $s .= 'XmlFormat::$namespaces = ' .
                (var_export(self::$namespaces, true)) . ";\n";
            $s .= 'XmlFormat::$namespacedProperties = ' .
                (var_export(self::$namespacedProperties, true)) . ";\n";
        }

        return $s;
    }

    public function encode($data, $humanReadable = false)
    {
        $data = Object::toArray($data);
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', $this->charset);
        if ($humanReadable) {
            $xml->setIndent(true);
            $xml->setIndentString('    ');
        }
        static::$useNamespaces && isset(static::$namespacedProperties[static::$rootName])
            ?
            $xml->startElementNs(
                static::$namespacedProperties[static::$rootName],
                static::$rootName,
                static::$namespaces[static::$namespacedProperties[static::$rootName]]
            )
            :
            $xml->startElement(static::$rootName);
        if (static::$useNamespaces) {
            foreach (static::$namespaces as $prefix => $ns) {
                if (isset(static::$namespacedProperties[static::$rootName])
                    && static::$namespacedProperties[static::$rootName] == $prefix
                ) {
                    continue;
                }
                $prefix = 'xmlns' . (empty($prefix) ? '' : ':' . $prefix);
                $xml->writeAttribute($prefix, $ns);
            }
        }
        $this->write($xml, $data, static::$rootName);
        $xml->endElement();

        return $xml->outputMemory();
    }

    public function write(XMLWriter $xml, $data, $parent)
    {
        $text = array();
        if (is_array($data)) {
            if (static::$useTextNodeProperty && isset($data[static::$textNodeName])) {
                $text [] = $data[static::$textNodeName];
                unset($data[static::$textNodeName]);
            }
            $attributes = array_flip(static::$attributeNames);
            //make sure we deal with attributes first
            $temp = array();
            foreach ($data as $key => $value) {
                if (isset($attributes[$key])) {
                    $temp[$key] = $data[$key];
                    unset($data[$key]);
                }
            }
            $data = array_merge($temp, $data);
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    if (!is_array($value)) {
                        $text [] = $value;
                        continue;
                    }
                    $key = static::$defaultTagName;
                }
                $useNS = static::$useNamespaces
                    && !empty(static::$namespacedProperties[$key])
                    && false === strpos($key, ':');
                if (is_array($value)) {
                    $useNS
                        ? $xml->startElementNs(
                        static::$namespacedProperties[$key],
                        $key,
                        null
                    )
                        : $xml->startElement($key);
                    $this->write($xml, $value, $key);
                    $xml->endElement();
                    continue;
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                if (isset($attributes[$key])) {
                    $xml->writeAttribute($useNS ? static::$namespacedProperties[$key] . ':' . $key : $key, $value);
                } else {
                    $useNS
                        ?
                        $xml->startElementNs(
                            static::$namespacedProperties[$key],
                            $key,
                            null
                        )
                        : $xml->startElement($key);
                    $this->write($xml, $value, $key);
                    $xml->endElement();
                }
            }
        } else {
            $text [] = (string)$data;
        }
        if (!empty($text)) {
            if (count($text) == 1) {
                in_array($parent, static::$cdataNames)
                    ? $xml->writeCdata(implode('', $text))
                    : $xml->text(implode('', $text));
            } else {
                foreach ($text as $t) {
                    $xml->writeElement(static::$textNodeName, $t);
                }
            }
        }
    }

    public function decode($data)
    {
        try {
            if ($data == '') {
                return array();
            }
            libxml_use_internal_errors(true);
            libxml_disable_entity_loader(true);
            $xml = simplexml_load_string($data,
                "SimpleXMLElement", LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_COMPACT);
            if (false === $xml) {
                $error = libxml_get_last_error();
                throw new RestException(400, 'Malformed XML. '
                    . trim($error->message, "\r\n") . ' at line ' . $error->line);
            }
            libxml_clear_errors();
            if (static::$importSettingsFromXml) {
                static::$attributeNames = array();
                static::$namespacedProperties = array();
                static::$namespaces = array();
                static::$rootName = $xml->getName();
                $namespaces = $xml->getNamespaces();
                if (count($namespaces)) {
                    $p = strpos($data, $xml->getName());
                    if ($p && $data{$p - 1} == ':') {
                        $s = strpos($data, '<') + 1;
                        $prefix = substr($data, $s, $p - $s - 1);
                        static::$namespacedProperties[static::$rootName] = $prefix;
                    }
                }
            }
            $data = $this->read($xml);
            if (count($data) == 1 && isset($data[static::$textNodeName])) {
                $data = $data[static::$textNodeName];
            }

            return $data;
        } catch (\RuntimeException $e) {
            throw new RestException(400,
                "Error decoding request. " . $e->getMessage());
        }
    }

    public function read(SimpleXMLElement $xml, $namespaces = null)
    {
        $r = array();
        $text = (string)$xml;

        if (static::$parseAttributes) {
            $attributes = $xml->attributes();
            foreach ($attributes as $key => $value) {
                if (static::$importSettingsFromXml
                    && !in_array($key, static::$attributeNames)
                ) {
                    static::$attributeNames[] = $key;
                }
                $r[$key] = static::setType((string)$value);
            }
        }
        $children = $xml->children();
        foreach ($children as $key => $value) {
            if ($key == static::$defaultTagName) {
                $r[] = $this->read($value);
            } elseif (isset($r[$key])) {
                if (is_array($r[$key])) {
                    if ($r[$key] != array_values($r[$key])) {
                        $r[$key] = array($r[$key]);
                    }
                } else {
                    $r[$key] = array($r[$key]);
                }
                $r[$key][] = $this->read($value, $namespaces);
            } else {
                $r[$key] = $this->read($value);
            }
        }

        if (static::$parseNamespaces) {
            if (is_null($namespaces)) {
                $namespaces = $xml->getDocNamespaces(true);
            }
            foreach ($namespaces as $prefix => $ns) {
                static::$namespaces[$prefix] = $ns;
                if (static::$parseAttributes) {
                    $attributes = $xml->attributes($ns);
                    foreach ($attributes as $key => $value) {
                        if (isset($r[$key])) {
                            $key = "{$prefix}:$key";
                        }
                        if (static::$importSettingsFromXml
                            && !in_array($key, static::$attributeNames)
                        ) {
                            static::$namespacedProperties[$key] = $prefix;
                            static::$attributeNames[] = $key;
                        }
                        $r[$key] = static::setType((string)$value);
                    }
                }
                $children = $xml->children($ns);
                foreach ($children as $key => $value) {
                    if (static::$importSettingsFromXml) {
                        static::$namespacedProperties[$key] = $prefix;
                    }
                    if (isset($r[$key])) {
                        if (is_array($r[$key])) {
                            if ($r[$key] != array_values($r[$key])) {
                                $r[$key] = array($r[$key]);
                            }
                        } else {
                            $r[$key] = array($r[$key]);
                        }
                        $r[$key][] = $this->read($value, $namespaces);
                    } else {
                        $r[$key] = $this->read($value, $namespaces);
                    }
                }
            }
        }

        if (empty($text) && $text !== '0') {
            if (empty($r)) {
                return null;
            }
        } else {
            empty($r)
                ? $r = static::setType($text)
                : (
            static::$parseTextNodeAsProperty
                ? $r[static::$textNodeName] = static::setType($text)
                : $r[] = static::setType($text)
            );
        }

        return $r;
    }

    public static function setType($value)
    {
        if (empty($value) && $value !== '0') {
            return null;
        }
        if ($value == 'true') {
            return true;
        }
        if ($value == 'false') {
            return true;
        }
        if (is_numeric($value)) {
            return 0 + $value;
        }

        return $value;
    }
}
