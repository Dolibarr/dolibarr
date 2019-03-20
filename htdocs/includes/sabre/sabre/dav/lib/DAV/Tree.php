<?php

namespace Sabre\DAV;

use Sabre\HTTP\URLUtil;

/**
 * The tree object is responsible for basic tree operations.
 *
 * It allows for fetching nodes by path, facilitates deleting, copying and
 * moving.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Tree {

    /**
     * The root node
     *
     * @var ICollection
     */
    protected $rootNode;

    /**
     * This is the node cache. Accessed nodes are stored here.
     * Arrays keys are path names, values are the actual nodes.
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Creates the object
     *
     * This method expects the rootObject to be passed as a parameter
     *
     * @param ICollection $rootNode
     */
    function __construct(ICollection $rootNode) {

        $this->rootNode = $rootNode;

    }

    /**
     * Returns the INode object for the requested path
     *
     * @param string $path
     * @return INode
     */
    function getNodeForPath($path) {

        $path = trim($path, '/');
        if (isset($this->cache[$path])) return $this->cache[$path];

        // Is it the root node?
        if (!strlen($path)) {
            return $this->rootNode;
        }

        // Attempting to fetch its parent
        list($parentName, $baseName) = URLUtil::splitPath($path);

        // If there was no parent, we must simply ask it from the root node.
        if ($parentName === "") {
            $node = $this->rootNode->getChild($baseName);
        } else {
            // Otherwise, we recursively grab the parent and ask him/her.
            $parent = $this->getNodeForPath($parentName);

            if (!($parent instanceof ICollection))
                throw new Exception\NotFound('Could not find node at path: ' . $path);

            $node = $parent->getChild($baseName);

        }

        $this->cache[$path] = $node;
        return $node;

    }

    /**
     * This function allows you to check if a node exists.
     *
     * Implementors of this class should override this method to make
     * it cheaper.
     *
     * @param string $path
     * @return bool
     */
    function nodeExists($path) {

        try {

            // The root always exists
            if ($path === '') return true;

            list($parent, $base) = URLUtil::splitPath($path);

            $parentNode = $this->getNodeForPath($parent);
            if (!$parentNode instanceof ICollection) return false;
            return $parentNode->childExists($base);

        } catch (Exception\NotFound $e) {

            return false;

        }

    }

    /**
     * Copies a file from path to another
     *
     * @param string $sourcePath The source location
     * @param string $destinationPath The full destination path
     * @return void
     */
    function copy($sourcePath, $destinationPath) {

        $sourceNode = $this->getNodeForPath($sourcePath);

        // grab the dirname and basename components
        list($destinationDir, $destinationName) = URLUtil::splitPath($destinationPath);

        $destinationParent = $this->getNodeForPath($destinationDir);
        $this->copyNode($sourceNode, $destinationParent, $destinationName);

        $this->markDirty($destinationDir);

    }

    /**
     * Moves a file from one location to another
     *
     * @param string $sourcePath The path to the file which should be moved
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    function move($sourcePath, $destinationPath) {

        list($sourceDir) = URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = URLUtil::splitPath($destinationPath);

        if ($sourceDir === $destinationDir) {
            // If this is a 'local' rename, it means we can just trigger a rename.
            $sourceNode = $this->getNodeForPath($sourcePath);
            $sourceNode->setName($destinationName);
        } else {
            $newParentNode = $this->getNodeForPath($destinationDir);
            $moveSuccess = false;
            if ($newParentNode instanceof IMoveTarget) {
                // The target collection may be able to handle the move
                $sourceNode = $this->getNodeForPath($sourcePath);
                $moveSuccess = $newParentNode->moveInto($destinationName, $sourcePath, $sourceNode);
            }
            if (!$moveSuccess) {
                $this->copy($sourcePath, $destinationPath);
                $this->getNodeForPath($sourcePath)->delete();
            }
        }
        $this->markDirty($sourceDir);
        $this->markDirty($destinationDir);

    }

    /**
     * Deletes a node from the tree
     *
     * @param string $path
     * @return void
     */
    function delete($path) {

        $node = $this->getNodeForPath($path);
        $node->delete();

        list($parent) = URLUtil::splitPath($path);
        $this->markDirty($parent);

    }

    /**
     * Returns a list of childnodes for a given path.
     *
     * @param string $path
     * @return array
     */
    function getChildren($path) {

        $node = $this->getNodeForPath($path);
        $children = $node->getChildren();
        $basePath = trim($path, '/');
        if ($basePath !== '') $basePath .= '/';

        foreach ($children as $child) {

            $this->cache[$basePath . $child->getName()] = $child;

        }
        return $children;

    }

    /**
     * This method is called with every tree update
     *
     * Examples of tree updates are:
     *   * node deletions
     *   * node creations
     *   * copy
     *   * move
     *   * renaming nodes
     *
     * If Tree classes implement a form of caching, this will allow
     * them to make sure caches will be expired.
     *
     * If a path is passed, it is assumed that the entire subtree is dirty
     *
     * @param string $path
     * @return void
     */
    function markDirty($path) {

        // We don't care enough about sub-paths
        // flushing the entire cache
        $path = trim($path, '/');
        foreach ($this->cache as $nodePath => $node) {
            if ($path === '' || $nodePath == $path || strpos($nodePath, $path . '/') === 0)
                unset($this->cache[$nodePath]);

        }

    }

    /**
     * This method tells the tree system to pre-fetch and cache a list of
     * children of a single parent.
     *
     * There are a bunch of operations in the WebDAV stack that request many
     * children (based on uris), and sometimes fetching many at once can
     * optimize this.
     *
     * This method returns an array with the found nodes. It's keys are the
     * original paths. The result may be out of order.
     *
     * @param array $paths List of nodes that must be fetched.
     * @return array
     */
    function getMultipleNodes($paths) {

        // Finding common parents
        $parents = [];
        foreach ($paths as $path) {
            list($parent, $node) = URLUtil::splitPath($path);
            if (!isset($parents[$parent])) {
                $parents[$parent] = [$node];
            } else {
                $parents[$parent][] = $node;
            }
        }

        $result = [];

        foreach ($parents as $parent => $children) {

            $parentNode = $this->getNodeForPath($parent);
            if ($parentNode instanceof IMultiGet) {
                foreach ($parentNode->getMultipleChildren($children) as $childNode) {
                    $fullPath = $parent . '/' . $childNode->getName();
                    $result[$fullPath] = $childNode;
                    $this->cache[$fullPath] = $childNode;
                }
            } else {
                foreach ($children as $child) {
                    $fullPath = $parent . '/' . $child;
                    $result[$fullPath] = $this->getNodeForPath($fullPath);
                }
            }

        }

        return $result;

    }


    /**
     * copyNode
     *
     * @param INode $source
     * @param ICollection $destinationParent
     * @param string $destinationName
     * @return void
     */
    protected function copyNode(INode $source, ICollection $destinationParent, $destinationName = null) {

        if (!$destinationName) $destinationName = $source->getName();

        if ($source instanceof IFile) {

            $data = $source->get();

            // If the body was a string, we need to convert it to a stream
            if (is_string($data)) {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $data);
                rewind($stream);
                $data = $stream;
            }
            $destinationParent->createFile($destinationName, $data);
            $destination = $destinationParent->getChild($destinationName);

        } elseif ($source instanceof ICollection) {

            $destinationParent->createDirectory($destinationName);

            $destination = $destinationParent->getChild($destinationName);
            foreach ($source->getChildren() as $child) {

                $this->copyNode($child, $destination);

            }

        }
        if ($source instanceof IProperties && $destination instanceof IProperties) {

            $props = $source->getProperties([]);
            $propPatch = new PropPatch($props);
            $destination->propPatch($propPatch);
            $propPatch->commit();

        }

    }

}
