<?php

namespace Sabre\DAVACL;

use Sabre\DAV;

class MockPrincipal extends DAV\Node implements IPrincipal {

    public $name;
    public $principalUrl;
    public $groupMembership = [];
    public $groupMemberSet = [];

    function __construct($name, $principalUrl, array $groupMembership = [], array $groupMemberSet = []) {

        $this->name = $name;
        $this->principalUrl = $principalUrl;
        $this->groupMembership = $groupMembership;
        $this->groupMemberSet = $groupMemberSet;

    }

    function getName() {

        return $this->name;

    }

    function getDisplayName() {

        return $this->getName();

    }

    function getAlternateUriSet() {

        return [];

    }

    function getPrincipalUrl() {

        return $this->principalUrl;

    }

    function getGroupMemberSet() {

        return $this->groupMemberSet;

    }

    function getGroupMemberShip() {

        return $this->groupMembership;

    }

    function setGroupMemberSet(array $groupMemberSet) {

        $this->groupMemberSet = $groupMemberSet;

    }
}
