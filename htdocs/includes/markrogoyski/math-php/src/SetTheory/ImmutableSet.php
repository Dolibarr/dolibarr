<?php

namespace MathPHP\SetTheory;

/**
 * Immutable Set (Set Theory)
 * A set that cannot be changed once created.
 *
 * Add, remove, and clear do not work on an immutable set. No Exceptions will
 * be thrown; it will just do nothing.
 *
 * Other than that, it acts just like a Set.
 */
class ImmutableSet extends Set
{
    /**************************************************************************
     * SINGLE MEMBER OPERATIONS - OVERIDDEN FROM SET
     *  - Add (cannot add members)
     *  - Add multi (cannot add members)
     *  - Remove (cannot remove members)
     *  - Remove multi (cannot remove members)
     *  - Clear (cannot clear set)
     **************************************************************************/

    /**
     * Cannot add members to an immutable set
     *
     * @param mixed $x
     *
     * @return Set (this set unchanged)
     */
    public function add($x): Set
    {
        return $this;
    }

    /**
     * Cannot add members to an immutable set
     *
     * @param array $x
     *
     * @return Set (this set unchanged)
     */
    public function addMulti(array $x): Set
    {
        return $this;
    }

    /**
     * Cannot remove members of an immutable set
     *
     * @param  mixed $x
     *
     * @return Set (this set unchanged)
     */
    public function remove($x): Set
    {
        return $this;
    }

    /**
     * Cannot remove members of an immutable set
     *
     * @param  array $x
     *
     * @return Set (this set unchanged)
     */
    public function removeMulti(array $x): Set
    {
        return $this;
    }

    /**
     * Cannot clear an immutable set
     *
     * @return Set (this set unchanged)
     */
    public function clear(): Set
    {
        return $this;
    }
}
