<?php

namespace Sabre\DAV\Mock;

use Sabre\DAV\Sharing\ISharedNode;
use Sabre\DAV\Sharing\Sharee;

class SharedNode extends \Sabre\DAV\Node implements ISharedNode {

    protected $name;
    protected $access;
    protected $invites = [];

    function __construct($name, $access) {

        $this->name = $name;
        $this->access = $access;

    }

    function getName() {

        return $this->name;

    }

    /**
     * Returns the 'access level' for the instance of this shared resource.
     *
     * The value should be one of the Sabre\DAV\Sharing\Plugin::ACCESS_
     * constants.
     *
     * @return int
     */
    function getShareAccess() {

        return $this->access;

    }

    /**
     * This function must return a URI that uniquely identifies the shared
     * resource. This URI should be identical across instances, and is
     * also used in several other XML bodies to connect invites to
     * resources.
     *
     * This may simply be a relative reference to the original shared instance,
     * but it could also be a urn. As long as it's a valid URI and unique.
     *
     * @return string
     */
    function getShareResourceUri() {

        return 'urn:example:bar';

    }

    /**
     * Updates the list of sharees.
     *
     * Every item must be a Sharee object.
     *
     * @param Sharee[] $sharees
     * @return void
     */
    function updateInvites(array $sharees) {

        foreach ($sharees as $sharee) {

            if ($sharee->access === \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS) {
                // Removal
                foreach ($this->invites as $k => $invitee) {

                    if ($invitee->href = $sharee->href) {
                        unset($this->invites[$k]);
                    }

                }

            } else {
                foreach ($this->invites as $k => $invitee) {

                    if ($invitee->href = $sharee->href) {
                        if (!$sharee->inviteStatus) {
                            $sharee->inviteStatus = $invitee->inviteStatus;
                        }
                        // Overwriting an existing invitee
                        $this->invites[$k] = $sharee;
                        continue 2;
                    }

                }
                if (!$sharee->inviteStatus) {
                    $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE;
                }
                // Adding a new invitee
                $this->invites[] = $sharee;
            }

        }

    }

    /**
     * Returns the list of people whom this resource is shared with.
     *
     * Every item in the returned array must be a Sharee object with
     * at least the following properties set:
     *
     * * $href
     * * $shareAccess
     * * $inviteStatus
     *
     * and optionally:
     *
     * * $properties
     *
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    function getInvites() {

        return $this->invites;

    }
}
