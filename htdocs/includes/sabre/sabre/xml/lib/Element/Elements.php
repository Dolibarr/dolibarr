<?php

namespace Sabre\Xml\Element;

use Sabre\Xml;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Serializer;

/**
 * 'Elements' is a simple list of elements, without values or attributes.
 * For example, Elements will parse:
 *
 * <?xml version="1.0"?>
 * <s:root xmlns:s="http://sabredav.org/ns">
 *   <s:elem1 />
 *   <s:elem2 />
 *   <s:elem3 />
 *   <s:elem4>content</s:elem4>
 *   <s:elem5 attr="val" />
 * </s:root>
 *
 * Into:
 *
 * [
 *   "{http://sabredav.org/ns}elem1",
 *   "{http://sabredav.org/ns}elem2",
 *   "{http://sabredav.org/ns}elem3",
 *   "{http://sabredav.org/ns}elem4",
 *   "{http://sabredav.org/ns}elem5",
 * ];
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Elements implements Xml\Element {

    /**
     * Value to serialize
     *
     * @var array
     */
    protected $value;

    /**
     * Constructor
     *
     * @param array $value
     */
    function __construct(array $value = []) {

        $this->value = $value;

    }

    /**
     * The xmlSerialize metod is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializble should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     *
     * @param Writer $writer
     * @return void
     */
    function xmlSerialize(Xml\Writer $writer) {

        Serializer\enum($writer, $this->value);

    }

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statictly, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseSubTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @param Xml\Reader $reader
     * @return mixed
     */
    static function xmlDeserialize(Xml\Reader $reader) {

        return Deserializer\enum($reader);

    }

}
