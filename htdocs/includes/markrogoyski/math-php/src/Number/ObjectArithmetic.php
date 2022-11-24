<?php

namespace MathPHP\Number;

interface ObjectArithmetic
{
    /**
     * Add two objects together
     *
     * @param mixed $object_or_scalar the value to be added
     *
     * @return ObjectArithmetic sum.
     */
    public function add($object_or_scalar);

    /*
     * Subtract one objects from another
     *
     * @param mixed $object_or_scalar the value to be subtracted
     *
     * @return ObjectArithmetic result.
     */
    public function subtract($object_or_scalar);

    /*
     * Multiply two objects together
     *
     * @param mixed $object_or_scalar value to be multiplied
     *
     * @return ObjectArithmetic product.
     */
    public function multiply($object_or_scalar);

    /**
     * Factory method to create the zero value of the object
     *
     * @return ObjectArithmetic
     */
    public static function createZeroValue(): ObjectArithmetic;
}
