<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;

/**
 * Locks Mock backend.
 *
 * This backend stores lock information in memory. Mainly useful for testing.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Mock extends AbstractBackend {

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
    function getLocks($uri, $returnChildLocks) {

        $newLocks = [];

        $locks = $this->getData();

        foreach ($locks as $lock) {

            if ($lock->uri === $uri ||
                //deep locks on parents
                ($lock->depth != 0 && strpos($uri, $lock->uri . '/') === 0) ||

                // locks on children
                ($returnChildLocks && (strpos($lock->uri, $uri . '/') === 0))) {

                $newLocks[] = $lock;

            }

        }

        // Checking if we can remove any of these locks
        foreach ($newLocks as $k => $lock) {
            if (time() > $lock->timeout + $lock->created) unset($newLocks[$k]);
        }
        return $newLocks;

    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    function lock($uri, LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();
        $lockInfo->uri = $uri;

        $locks = $this->getData();

        foreach ($locks as $k => $lock) {
            if (
                ($lock->token == $lockInfo->token) ||
                (time() > $lock->timeout + $lock->created)
            ) {
                unset($locks[$k]);
            }
        }
        $locks[] = $lockInfo;
        $this->putData($locks);
        return true;

    }

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param LockInfo $lockInfo
     * @return bool
     */
    function unlock($uri, LockInfo $lockInfo) {

        $locks = $this->getData();
        foreach ($locks as $k => $lock) {

            if ($lock->token == $lockInfo->token) {

                unset($locks[$k]);
                $this->putData($locks);
                return true;

            }
        }
        return false;

    }

    protected $data = [];

    /**
     * Loads the lockdata from the filesystem.
     *
     * @return array
     */
    protected function getData() {

        return $this->data;

    }

    /**
     * Saves the lockdata
     *
     * @param array $newData
     * @return void
     */
    protected function putData(array $newData) {

        $this->data = $newData;

    }

}
