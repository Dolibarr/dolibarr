<?php

namespace Sabre\DAVACL\PrincipalBackend;

class Mock extends AbstractBackend {

    public $groupMembers = [];
    public $principals;

    function __construct(array $principals = null) {

        $this->principals = $principals;

        if (is_null($principals)) {

            $this->principals = [
                [
                    'uri'                                   => 'principals/user1',
                    '{DAV:}displayname'                     => 'User 1',
                    '{http://sabredav.org/ns}email-address' => 'user1.sabredav@sabredav.org',
                    '{http://sabredav.org/ns}vcard-url'     => 'addressbooks/user1/book1/vcard1.vcf',
                ],
                [
                    'uri'               => 'principals/admin',
                    '{DAV:}displayname' => 'Admin',
                ],
                [
                    'uri'                                   => 'principals/user2',
                    '{DAV:}displayname'                     => 'User 2',
                    '{http://sabredav.org/ns}email-address' => 'user2.sabredav@sabredav.org',
                ],
            ];

        }

    }

    function getPrincipalsByPrefix($prefix) {

        $prefix = trim($prefix, '/');
        if ($prefix) $prefix .= '/';
        $return = [];

        foreach ($this->principals as $principal) {

            if ($prefix && strpos($principal['uri'], $prefix) !== 0) continue;

            $return[] = $principal;

        }

        return $return;

    }

    function addPrincipal(array $principal) {

        $this->principals[] = $principal;

    }

    function getPrincipalByPath($path) {

        foreach ($this->getPrincipalsByPrefix('principals') as $principal) {
            if ($principal['uri'] === $path) return $principal;
        }

    }

    function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {

        $matches = [];
        foreach ($this->getPrincipalsByPrefix($prefixPath) as $principal) {

            foreach ($searchProperties as $key => $value) {

                if (!isset($principal[$key])) {
                    continue 2;
                }
                if (mb_stripos($principal[$key], $value, 0, 'UTF-8') === false) {
                    continue 2;
                }

                // We have a match for this searchProperty!
                if ($test === 'allof') {
                    continue;
                } else {
                    break;
                }

            }
            $matches[] = $principal['uri'];

        }
        return $matches;

    }

    function getGroupMemberSet($path) {

        return isset($this->groupMembers[$path]) ? $this->groupMembers[$path] : [];

    }

    function getGroupMembership($path) {

        $membership = [];
        foreach ($this->groupMembers as $group => $members) {
            if (in_array($path, $members)) $membership[] = $group;
        }
        return $membership;

    }

    function setGroupMemberSet($path, array $members) {

        $this->groupMembers[$path] = $members;

    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch) {

        $value = null;
        foreach ($this->principals as $principalIndex => $value) {
            if ($value['uri'] === $path) {
                $principal = $value;
                break;
            }
        }
        if (!$principal) return;

        $propPatch->handleRemaining(function($mutations) use ($principal, $principalIndex) {

            foreach ($mutations as $prop => $value) {

                if (is_null($value) && isset($principal[$prop])) {
                    unset($principal[$prop]);
                } else {
                    $principal[$prop] = $value;
                }

            }

            $this->principals[$principalIndex] = $principal;

            return true;

        });

    }


}
