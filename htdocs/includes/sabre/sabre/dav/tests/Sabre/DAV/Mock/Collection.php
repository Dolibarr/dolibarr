<?php

namespace Sabre\DAV\Mock;

use Sabre\DAV;

/**
 * Mock Collection.
 *
 * This collection quickly allows you to create trees of nodes.
 * Children are specified as an array.
 *
 * Every key a filename, every array value is either:
 *   * an array, for a sub-collection
 *   * a string, for a file
 *   * An instance of \Sabre\DAV\INode.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Collection extends DAV\Collection {

    protected $name;
    protected $children;
    protected $parent;

    /**
     * Creates the object
     *
     * @param string $name
     * @param array $children
     * @param Collection $parent
     * @return void
     */
    function __construct($name, array $children = [], Collection $parent = null) {

        $this->name = $name;
        foreach ($children as $key => $value) {
            if (is_string($value)) {
                $this->children[] = new File($key, $value, $this);
            } elseif (is_array($value)) {
                $this->children[] = new self($key, $value, $this);
            } elseif ($value instanceof \Sabre\DAV\INode) {
                $this->children[] = $value;
            } else {
                throw new \InvalidArgumentException('Unknown value passed in $children');
            }
        }
        $this->parent = $parent;

    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    function getName() {

        return $this->name;

    }

    /**
     * Creates a new file in the directory
     *
     * Data will either be supplied as a stream resource, or in certain cases
     * as a string. Keep in mind that you may have to support either.
     *
     * After successful creation of the file, you may choose to return the ETag
     * of the new file here.
     *
     * The returned ETag must be surrounded by double-quotes (The quotes should
     * be part of the actual string).
     *
     * If you cannot accurately determine the ETag, you should not return it.
     * If you don't store the file exactly as-is (you're transforming it
     * somehow) you should also not return an ETag.
     *
     * This means that if a subsequent GET to this new file does not exactly
     * return the same contents of what was submitted here, you are strongly
     * recommended to omit the ETag.
     *
     * @param string $name Name of the file
     * @param resource|string $data Initial payload
     * @return null|string
     */
    function createFile($name, $data = null) {

        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
        $this->children[] = new File($name, $data, $this);
        return '"' . md5($data) . '"';

    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @return void
     */
    function createDirectory($name) {

        $this->children[] = new self($name);

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return \Sabre\DAV\INode[]
     */
    function getChildren() {

        return $this->children;

    }

    /**
     * Adds an already existing node to this collection.
     *
     * @param \Sabre\DAV\INode $node
     */
    function addNode(\Sabre\DAV\INode $node) {

        $this->children[] = $node;

    }

    /**
     * Removes a childnode from this node.
     *
     * @param string $name
     * @return void
     */
    function deleteChild($name) {

        foreach ($this->children as $key => $value) {

            if ($value->getName() == $name) {
                unset($this->children[$key]);
                return;
            }

        }

    }

    /**
     * Deletes this collection and all its children,.
     *
     * @return void
     */
    function delete() {

        foreach ($this->getChildren() as $child) {
            $this->deleteChild($child->getName());
        }
        $this->parent->deleteChild($this->getName());

    }

}
