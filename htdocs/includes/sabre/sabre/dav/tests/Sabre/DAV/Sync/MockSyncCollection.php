<?php

namespace Sabre\DAV\Sync;

use Sabre\DAV;

/**
 * This mocks a ISyncCollection, for unittesting.
 *
 * This object behaves the same as SimpleCollection. Call addChange to update
 * the 'changelog' that this class uses for the collection.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MockSyncCollection extends DAV\SimpleCollection implements ISyncCollection {

    public $changeLog = [];

    public $token = null;

    /**
     * This method returns the current sync-token for this collection.
     * This can be any string.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return string|null
     */
    function getSyncToken() {

        // Will be 'null' in the first round, and will increment ever after.
        return $this->token;

    }

    function addChange(array $added, array $modified, array $deleted) {

        $this->token++;
        $this->changeLog[$this->token] = [
            'added'    => $added,
            'modified' => $modified,
            'deleted'  => $deleted,
        ];

    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken and the current collection.
     *
     * This function should return an array, such as the following:
     *
     * array(
     *   'syncToken' => 'The current synctoken',
     *   'modified'   => array(
     *      'new.txt',
     *   ),
     *   'deleted' => array(
     *      'foo.php.bak',
     *      'old.txt'
     *   )
     * );
     *
     * The syncToken property should reflect the *current* syncToken of the
     * collection, as reported getSyncToken(). This is needed here too, to
     * ensure the operation is atomic.
     *
     * If the syncToken is specified as null, this is an initial sync, and all
     * members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The second argument is basically the 'depth' of the report. If it's 1,
     * you only have to report changes that happened only directly in immediate
     * descendants. If it's 2, it should also include changes from the nodes
     * below the child collections. (grandchildren)
     *
     * The third (optional) argument allows a client to specify how many
     * results should be returned at most. If the limit is not specified, it
     * should be treated as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    function getChanges($syncToken, $syncLevel, $limit = null) {

        // This is an initial sync
        if (is_null($syncToken)) {
            return [
               'added' => array_map(
                    function($item) {
                        return $item->getName();
                    }, $this->getChildren()
                ),
                'modified'  => [],
                'deleted'   => [],
                'syncToken' => $this->getSyncToken(),
            ];
        }

        if (!is_int($syncToken) && !ctype_digit($syncToken)) {

            return null;

        }
        if (is_null($this->token)) return null;

        $added = [];
        $modified = [];
        $deleted = [];

        foreach ($this->changeLog as $token => $change) {

            if ($token > $syncToken) {

                $added = array_merge($added, $change['added']);
                $modified = array_merge($modified, $change['modified']);
                $deleted = array_merge($deleted, $change['deleted']);

                if ($limit) {
                    // If there's a limit, we may need to cut things off.
                    // This alghorithm is weird and stupid, but it works.
                    $left = $limit - (count($modified) + count($deleted));
                    if ($left > 0) continue;
                    if ($left === 0) break;
                    if ($left < 0) {
                        $modified = array_slice($modified, 0, $left);
                    }
                    $left = $limit - (count($modified) + count($deleted));
                    if ($left === 0) break;
                    if ($left < 0) {
                        $deleted = array_slice($deleted, 0, $left);
                    }
                    break;

                }

            }

        }

        return [
            'syncToken' => $this->token,
            'added'     => $added,
            'modified'  => $modified,
            'deleted'   => $deleted,
        ];

    }


}
