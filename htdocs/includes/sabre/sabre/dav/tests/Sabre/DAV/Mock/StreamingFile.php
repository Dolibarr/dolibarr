<?php

namespace Sabre\DAV\Mock;

/**
 * Mock Streaming File File
 *
 * Works similar to the mock file, but this one works with streams and has no
 * content-length or etags.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class StreamingFile extends File {

    protected $size;

    /**
     * Updates the data
     *
     * The data argument is a readable stream resource.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * If you don't plan to store the file byte-by-byte, and you return a
     * different object on a subsequent GET you are strongly recommended to not
     * return an ETag, and just return null.
     *
     * @param resource $data
     * @return string|null
     */
    function put($data) {

        if (is_string($data)) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $data);
            rewind($stream);
            $data = $stream;
        }
        $this->contents = $data;

    }

    /**
     * Returns the data
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    function get() {

        return $this->contents;

    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return void
     */
    function getETag() {

        return null;

    }

    /**
     * Returns the size of the node, in bytes
     *
     * @return int
     */
    function getSize() {

        return $this->size;

    }

    /**
     * Allows testing scripts to set the resource's file size.
     *
     * @param int $size
     * @return void
     */
    function setSize($size) {

        $this->size = $size;

    }

}
