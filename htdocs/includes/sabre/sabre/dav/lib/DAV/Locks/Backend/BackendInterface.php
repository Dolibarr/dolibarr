<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks;

/**
 * If you are defining your own Locks backend, you must implement this
 * interface.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface BackendInterface {

    /**
     * Returns a list of Sabre\DAV\Locks\LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    function getLocks($uri, $returnChildLocks);

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param Locks\LockInfo $lockInfo
     * @return bool
     */
    function lock($uri, Locks\LockInfo $lockInfo);

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param Locks\LockInfo $lockInfo
     * @return bool
     */
    function unlock($uri, Locks\LockInfo $lockInfo);

}
