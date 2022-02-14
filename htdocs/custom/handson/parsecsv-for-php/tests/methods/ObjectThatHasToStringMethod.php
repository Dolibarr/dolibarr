<?php

namespace ParseCsv\tests\methods;

/**
 * Class HasToString is just a helper to test if cells can be objects.
 */
class ObjectThatHasToStringMethod {

    public function __toString() {
        return 'some value';
    }
}
