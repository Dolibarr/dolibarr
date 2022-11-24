<?php

namespace MathPHP\SetTheory;

/**
 * Set (Set Theory)
 * A set is a collection of distinct objects, considered as an object in
 * its own right.
 * https://en.wikipedia.org/wiki/Set_(mathematics)
 *
 * Sets can contain numbers, strings, arrays, objects, and other sets.
 *
 * Implementation:
 * For performance reasons, PHP arrays are used as a hash for quick access
 * via hash keys.
 *
 * The hash keys are as follows:
 *  - Numbers and strings: value itself
 *  - Sets: Set as a string.
 *  - Arrays: Array(array_serialization)
 *  - Objects: Object\Name(object_hash)
 *  - Resource: Resource(Resource id: #)
 *  - Null: ''
 *
 * The values of the associative array (hash) are the actual values or
 * objects themselves. If the set is iterated in a foreach loop you will
 * get back the original value, set, array, or object.
 *
 * An object cannot be in the set multiple times. For a regular value, like
 * a number or string, this is straight forward. For arrays and objects, the
 * behavior is based on whether they are the same thing. What that means depends
 * on whether it is an array or object.
 *
 * Example (arrays):
 * $array1 = [1, 2, 3];
 * $array2 = [1, 2, 3];
 * $set = new Set([$array1, $array2]);
 *
 * The set will have only one element, because the arrays are equal.
 * $array2 === $array2 evaluates to true.
 *
 * Example (different objects):
 * $object1 = new \StdClass();
 * $object2 = new \StdClass();
 * $set = new Set([$object1, $object2]);
 *
 * The set will have two elements, because they are different objects.
 * $object1 === $object2 evaluates to false.
 *
 * Example (same objects):
 * $object1 = new \StdClass();
 * $object2 = $object1;
 * $set = new Set([$object1, $object2]);
 *
 * The set will have only one element, because the objects are the same.
 * $object1 === $object2 evaluates to true.
 *
 * Example (Sets, a special case of object)
 * $set1 = new Set([1, 2, 3]);
 * $set2 = new Set([1, 2, 3]);
 * $set3 = new Set([$set1, $set2]);
 *
 * Set3 will have only one element, because sets 1 and 2 are the same. Sets are
 * not based on whether the object is the same, but whether the content of
 * the set are the same. Sets and arrays act similarly.
 *
 * When storing a Set object as a member of a set, its key will be a string
 * that uses mathematical set notation with the addtion of the word 'Set'.
 * For example: Set{1, 2, 3}
 * The one edge case of this, is that the Set object {1, 2, 3} and the string
 * 'Set{1, 2, 3}' would appear identical in the case of adding one when the other
 * already is a member of the set. When accessing the actual set member, you
 * will always get back the original one added, whether it was a Set object or
 * a string.
 */
class Set implements \Countable, \Iterator
{
    /**
     * Set as a hash.
     * Keys are a representation of the members of the set.
     * Values are the values/objects themselves.
     * @var array
     */
    protected $A = [];

    /**
     * Constructor - Initialize set members
     *
     * @param array $members
     */
    public function __construct(array $members = [])
    {
        foreach ($members as $member) {
            $this->A[$this->getKey($member)] = $member;
        }
    }

    /**************************************************************************
     * GET SET CONTENTS
     *  - Array
     *  - Length
     **************************************************************************/

    /**
     * Get the set as an array
     *
     * @return array (values are the set members)
     */
    public function asArray(): array
    {
        return $this->A;
    }

    /**
     * Get length of set (number of members in set)
     *
     * @return int
     */
    public function length(): int
    {
        return \count($this->A);
    }

    /**************************************************************************
     * SET PROPERTIES
     *  - Empty set
     **************************************************************************/

    public function isEmpty(): bool
    {
        return empty($this->A);
    }

    /**************************************************************************
     * SINGLE MEMBER PROPERTIES
     *  - Is member
     *  - Is not member
     **************************************************************************/

    /**
     * Set membership (x ∈ A)
     * Is x a member of the set?
     *
     * @param  mixed $x
     *
     * @return boolean
     */
    public function isMember($x): bool
    {
        return \array_key_exists($this->getKey($x), $this->A);
    }

    /**
     * Set non-membership (x ∉ A)
     * Is x not a member of the set?
     *
     * @param  mixed $x
     *
     * @return boolean
     */
    public function isNotMember($x): bool
    {
        return !\array_key_exists($this->getKey($x), $this->A);
    }

    /**************************************************************************
     * MEMBER OPERATIONS
     *  - Add
     *  - Add multi
     *  - Remove
     *  - Remove multi
     **************************************************************************/

    /**
     * Add an element to the set
     * Does nothing if element already exists in the set.
     *
     * @param mixed $x
     *
     * @return Set (this set)
     */
    public function add($x): Set
    {
        $this->A[$this->getKey($x)] = $x;

        return $this;
    }

    /**
     * Add an array of elements to the set
     * Does nothing if element already exists in the set.
     *
     * @param array $members
     *
     * @return Set (this set)
     */
    public function addMulti(array $members): Set
    {
        foreach ($members as $member) {
            $this->A[$this->getKey($member)] = $member;
        }

        return $this;
    }

    /**
     * Remove an element from the set
     * Does nothing if the element does not exist in the set.
     *
     * @param  mixed $x
     *
     * @return Set (this set)
     */
    public function remove($x): Set
    {
        unset($this->A[$this->getKey($x)]);

        return $this;
    }

    /**
     * Remove elements from the set
     * Does nothing if the element does not exist in the set.
     *
     * @param  array $x
     *
     * @return Set (this set)
     */
    public function removeMulti(array $x): Set
    {
        foreach ($x as $member) {
            unset($this->A[$this->getKey($member)]);
        }

        return $this;
    }

    /**
     * Determine the key for the member to be added
     *
     * Based on the type of member to be added, the key differs:
     *  - Number: value as is
     *  - String: value as is
     *  - Set: String representation of set. Example: {1, 2}
     *  - Array: Array(array_serialization)
     *  - Object: Class\Name(object_hash)
     *  - Resource: Resource(Resource id #)
     *  - Null: ''
     *
     * @param mixed $x
     *
     * @return string|null
     */
    protected function getKey($x): ?string
    {
        if (\is_int($x) || \is_float($x) || \is_string($x) || $x instanceof Set) {
            return "$x";
        } elseif (\is_object($x)) {
            return \get_class($x) . '(' . \spl_object_hash($x) . ')';
        } elseif (\is_array($x)) {
            return 'Array(' . serialize($x) . ')';
        } elseif (\is_resource($x)) {
            return 'Resource(' . \strval($x) . ')';
        }

        return null;
    }

    /**************************************************************************
     * SET PROPERTIES AGAINST OTHER SETS
     *  - Disjoint
     *  - Subset
     *  - Proper subset
     *  - Super set
     *  - Proper superset
     **************************************************************************/

    /**
     * Disjoint
     * Does the set have no elements in common with the other set?
     *
     * Example of disjoint sets:
     *  A = {1, 2, 3}
     *  B = {4, 5, 6}
     *
     * @param  Set $other
     *
     * @return boolean
     */
    public function isDisjoint(Set $other): bool
    {
        return empty(\array_intersect_key($this->A, $other->asArray()));
    }

    /**
     * Subset (A ⊆ B)
     * Is the set a subset of the other set?
     * In other words, does the other set contain all the elements of the set?
     *
     * @param  Set $B
     *
     * @return boolean
     */
    public function isSubset(Set $B): bool
    {
        $B_array  = $B->asArray();

        $A∩B = \array_intersect_key($this->A, $B_array);
        $A∖B = \array_diff_key($this->A, $B_array);

        return (\count($A∩B) === \count($this->A)) && (empty($A∖B));
    }

    /**
     * Proper subset (A ⊆ B & A ≠ B)
     * Is the set a proper subset of the other set?
     * In other words, does the other set contain all the elements of the set,
     * and the set is not the same set as the other set?
     *
     * @param  Set $B
     *
     * @return boolean
     */
    public function isProperSubset(Set $B): bool
    {
        $B_array  = $B->asArray();

        $A∩B = \array_intersect_key($this->A, $B_array);
        $A∖B = \array_diff_key($this->A, $B_array);

        return (\count($A∩B) === \count($this->A)) && (empty($A∖B)) && (\count($this->A) === \count($B));
    }

    /**
     * Superset (A ⊇ B)
     * Is the set a superset of the other set?
     * In other words, does the the set contain all the elements of the other set?
     *
     * @param  Set $B
     *
     * @return boolean
     */
    public function isSuperset(Set $B): bool
    {
        $B_array  = $B->asArray();

        $A∩B = \array_intersect_key($this->A, $B_array);
        $A∖B = \array_diff_key($B_array, $this->A);

        return (\count($A∩B) === $B->length()) && (empty($A∖B));
    }

    /**
     * Superset (A ⊇ B & A ≠ B)
     * Is the set a superset of the other set?
     * In other words, does the the set contain all the elements of the other set,
     * and the set is not the same set as the other set?
     *
     * @param  Set $B
     *
     * @return boolean
     */
    public function isProperSuperset(Set $B): bool
    {
        $B_array  = $B->asArray();

        $A∩B = \array_intersect_key($this->A, $B_array);
        $A∖B = \array_diff_key($B_array, $this->A);

        return (\count($A∩B) === $B->length()) && (empty($A∖B)) && ($this != $B);
    }

    /**************************************************************************
     * SET OPERATIONS ON OTHER SETS
     *  - Union
     *  - Intersection
     *  - Difference
     *  - Symmetric difference
     **************************************************************************/

    /**
     * Union (A ∪ B)
     * Produces a new set with all elements from all sets.
     *
     * Example:
     *  {1, 2} ∪ {2, 3} = {1, 2, 3}
     *
     * @param  Set ...$Bs One or more sets
     *
     * @return Set
     */
    public function union(Set ...$Bs): Set
    {
        $union       = $this->A;
        $new_members = [];

        foreach ($Bs as $B) {
            $new_members += \array_diff_key($B->asArray(), $union);
        }

        foreach ($new_members as $member => $value) {
            $union[$member] = $value;
        }

        return new Set($union);
    }

    /**
     * Intersect (A ∩ B)
     * Produces a new set with all the elements common to all sets.
     *
     * Example:
     *  {1, 2} ∩ {2, 3} = {2}
     *
     * @param  Set ...$Bs One or more sets
     *
     * @return Set
     */
    public function intersect(Set ...$Bs): Set
    {
        $B_members = [];
        foreach ($Bs as $B) {
            $B_members[] = $B->asArray();
        }

        $intersection = \array_intersect_key($this->A, ...$B_members);

        return new Set($intersection);
    }

    /**
     * Difference (relative complement) (A ∖ B) or (A - B)
     * Produces a new set with elements that are not in the other sets.
     *
     * @param  Set ...$Bs One or more sets
     *
     * @return Set
     */
    public function difference(Set ...$Bs): Set
    {
        $B_members = [];
        foreach ($Bs as $B) {
            $B_members += $B->asArray();
        }

        $difference = \array_diff_key($this->A, $B_members);

        return new Set($difference);
    }

    /**
     * Symmetric Difference (A Δ B) = (A ∖ B) ∪ (B ∖ A)
     * Produces a new set with elements that are in the set or the other,
     * but not both.
     *
     * Example:
     *  {7, 8, 9, 10} Δ {9, 10, 11, 12} = {7, 8, 11, 12}
     *
     * @param  Set $B
     *
     * @return Set
     */
    public function symmetricDifference(Set $B): Set
    {
        $B_array = $B->asArray();

        $A∪B = \array_intersect_key($this->A, $B_array);

        $A∖B = \array_diff_key($this->A, $A∪B);
        $B∖A = \array_diff_key($B_array, $A∪B);

        return new Set($A∖B + $B∖A);
    }

    /**
     * Cartesian product (A×B)
     * Produces a new set by associating every element of the set with every
     * element of the other set(s).
     *
     * Example:
     *  A   = (1, 2)
     *  B   = (a, b)
     *  A×B = ((1, a), (1, b), (2, a), (2, b))
     *
     * @param  Set ...$Bs One or more sets
     *
     * @return Set
     */
    public function cartesianProduct(Set ...$Bs): Set
    {
        $A×B     = [];
        $product = $this->length();
        $sets    = [$this->asArray()];

        foreach ($Bs as $B) {
            $product *= $B->length();
            $sets[]   = $B->asArray();
        }

        $l = \count($sets);

        for ($i = 0; $i < $product; ++$i) {
            $elements = [];
            for ($i2 = 0; $i2 < $l; ++$i2) {
                $elements[] = \current($sets[$i2]);
            }

            $A×B[] = new Set($elements);

            // Advance array pointers
            for ($i2 = 0; $i2 < $l; ++$i2) {
                \next($sets[$i2]);
                $key = key($sets[$i2]);
                if ($key !== null) {
                    break;
                }
                \reset($sets[$i2]);
            }
        }

        return new Set($A×B);
    }

    /**************************************************************************
     * OTHER SET OPERATIONS
     *  - Power set
     *  - Copy
     *  - Clear
     *  - To string
     **************************************************************************/

    /**
     * Power set P(S)
     * The set of all subsets of S, including the empty set and S itself.
     *
     * Example:
     *  S = {x, y, z}
     *  P(S) = {Ø, {x}, {y}, {z}, {x,y}, {x,z}, {y,z}, {x,y,z}}
     *
     * Algorithm:
     *  Setup:
     *   - n:     size of the original set
     *   - 2ⁿ:    size of the power set
     *   - A:     original set as an array with numbered indices 0 to n - 1
     *   - P(S):  power set to be created
     *
     *  Iterative loop algorithm:
     *   - Loop i from 0 to < 2ⁿ
     *    - Create empty temporary Set
     *    - Loop j from 0 to < n
     *      - If the jᵗʰ bit of the i counter is set, add A[j] to temporary Set
     *    - Add temporary set to power set
     *
     * Time complexity: O(n2ⁿ)
     * Reference: http://www.geeksforgeeks.org/power-set/
     *
     * @return Set
     */
    public function powerSet(): Set
    {
        // Setup
        $n   = \count($this->A);         // Size of the original set
        $２ⁿ = \pow(2, $n);              // Size of the power set
        $A   = \array_values($this->A); //  Original set as an array with numbered indices
        $P⟮S⟯ = new Set();              //  Power set to be created

        // Populate power set
        for ($i = 0; $i < $２ⁿ; $i++) {
            $member_set = new Set();
            for ($j = 0; $j < $n; $j++) {
                if ($i & (1 << $j)) {
                    $member_set->add($A[$j]);
                }
            }
            $P⟮S⟯->add($member_set);
        }

        return $P⟮S⟯;
    }

    /**
     * Copy
     * Produces a new set with the same elements as the set.
     *
     * @return Set
     */
    public function copy(): Set
    {
        // ImmutableSet extends Set, so return the calling class' type.
        return new static($this->A);
    }

    /**
     * Clear the set. Removes all members.
     * Results in an empty set.
     *
     * @return Set (this set)
     */
    public function clear(): Set
    {
        $this->A = [];

        return $this;
    }

    /**
     * Return the set as a string
     * Set{a, b, c, ...}
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->isEmpty()) {
            return 'Ø';
        }
        return 'Set{' . \implode(', ', \array_keys($this->A)) . '}';
    }

    /**************************************************************************
     * PHP INTERFACES
     *  - Countable
     *  - Iterator (Traversable)
     **************************************************************************/

    /**
     * Countable interface
     * Computes cardinality of a set S, |S|
     *
     * @return int
     */
    public function count(): int
    {
        return \count($this->A);
    }

    /**
     * Iterator interface array to iterate over
     * @var array
     */
    protected $iterator_keys;

    /**
     * Iterator interface position of iterator keys we are at (the key)
     * @var mixed
     */
    protected $iterator_position;

    /**
     * Rewind (Iterator interface)
     */
    public function rewind(): void
    {
        $this->iterator_keys     = \array_keys($this->A);
        $this->iterator_position = \array_shift($this->iterator_keys);
    }

    /**
     * Valid (Iterator interface)
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return isset($this->A[$this->iterator_position]);
    }

    /**
     * Current (Iterator interface)
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->A[$this->iterator_position];
    }

    /**
     * Key (Iterator interface)
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->iterator_position;
    }

    /**
     * Next (Iterator interface)
     */
    public function next(): void
    {
        $this->iterator_position = \array_shift($this->iterator_keys);
    }
}
