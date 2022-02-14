<?php

namespace ParseCsv\enums;

abstract class AbstractEnum {

    /**
     * Creates a new value of some type
     *
     * @param mixed $value
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($value) {
        if (!$this->isValid($value)) {
            throw new \UnexpectedValueException("Value '$value' is not part of the enum " . get_called_class());
        }
        $this->value = $value;
    }

    public static function getConstants() {
        $class = get_called_class();
        $reflection = new \ReflectionClass($class);

        return $reflection->getConstants();
    }

    /**
     * Check if enum value is valid
     *
     * @param $value
     *
     * @return bool
     */
    public static function isValid($value) {
        return in_array($value, static::getConstants(), true);
    }
}
