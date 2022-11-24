<?php

namespace MathPHP\Number;

use MathPHP\Exception;
use MathPHP\Functions\Special;

/**
 * Complex Numbers
 *
 * A complex number is a number that can be expressed in the form a + bi,
 * where a and b are real numbers and i is the imaginary unit, satisfying the
 * equation i² = −1. In this expression, a is the real part and b is the imaginary
 * part of the complex number.
 * https://en.wikipedia.org/wiki/Complex_number
 *
 * @property-read number $r
 * @property-read number $i
 */
class Complex implements ObjectArithmetic
{
    /**
     * Real part of the complex number
     * @var number
     */
    protected $r;

    /**
     * Imaginary part fo the complex number
     * @var number
     */
    protected $i;

    /**
     * Floating-point range near zero to consider insignificant.
     */
    private const EPSILON = 1e-6;

    /**
     * Constructor
     *
     * @param number $r Real part
     * @param number $i Imaginary part
     */
    public function __construct($r, $i)
    {
        $this->r = $r;
        $this->i = $i;
    }

    /**
     * Creates 0 + 0i
     *
     * @return Complex
     */
    public static function createZeroValue(): ObjectArithmetic
    {
        return new Complex(0, 0);
    }

    /**
     * String representation of a complex number
     * a + bi, a - bi, etc.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->r == 0 & $this->i == 0) {
            return '0';
        } elseif ($this->r == 0) {
            return "$this->i" . 'i';
        } elseif ($this->i == 0) {
            return "$this->r";
        } elseif ($this->i > 0) {
            return "$this->r" . ' + ' . "$this->i" . 'i';
        } else {
            return "$this->r" . ' - ' . (string) \abs($this->i) . 'i';
        }
    }

    /**
     * Get r or i
     *
     * @param string $part
     *
     * @return number
     *
     * @throws Exception\BadParameterException if something other than r or i is attempted
     */
    public function __get(string $part)
    {
        switch ($part) {
            case 'r':
            case 'i':
                return $this->$part;

            default:
                throw new Exception\BadParameterException("The $part property does not exist in Complex number");
        }
    }

    /**************************************************************************
     * UNARY FUNCTIONS
     **************************************************************************/

    /**
     * The conjugate of a complex number
     *
     * https://en.wikipedia.org/wiki/Complex_number#Conjugate
     *
     * @return Complex
     */
    public function complexConjugate(): Complex
    {
        return new Complex($this->r, -1 * $this->i);
    }

    /**
     * The absolute value (magnitude) of a complex number (modulus)
     * https://en.wikipedia.org/wiki/Complex_number#Absolute_value_and_argument
     *
     * If z = a + bi
     *        _______
     * |z| = √a² + b²
     *
     * @return number
     */
    public function abs()
    {
        return \sqrt($this->r ** 2 + $this->i ** 2);
    }

    /**
     * The argument (phase) of a complex number
     * The argument of z is the angle of the radius OP with the positive real axis, and is written as arg(z).
     * https://en.wikipedia.org/wiki/Complex_number#Absolute_value_and_argument
     *
     * If z = a + bi
     * arg(z) = atan(b, a)
     *
     * @return number
     */
    public function arg()
    {
        return \atan2($this->i, $this->r);
    }

    /**
     * The square root of a complex number
     * https://en.wikipedia.org/wiki/Complex_number#Square_root
     *
     * The roots of a + bi (with b ≠ 0) are ±(γ + δi), where
     *
     *         ____________
     *        /     _______
     *       / a + √a² + b²
     * γ =  /  ------------
     *     √         2
     *
     *               ____________
     *              /      _______
     *             / -a + √a² + b²
     * δ = sgn(b) /  -------------
     *           √         2
     *
     * The square root returns the positive root.
     *
     * @return Complex (positive root)
     */
    public function sqrt(): Complex
    {
        return $this->roots()[0];
    }

    /**
     * The roots of a complex number
     * https://en.wikipedia.org/wiki/Complex_number#Square_root
     *
     * The roots of a + bi (with b ≠ 0) are ±(γ + δi), where
     *
     *         ____________
     *        /     _______
     *       / a + √a² + b²
     * γ =  /  ------------
     *     √         2
     *
     *               ____________
     *              /      _______
     *             / -a + √a² + b²
     * δ = sgn(b) /  -------------
     *           √         2
     *
     *
     * @return array Complex[] (two roots)
     */
    public function roots(): array
    {
        $sgn = Special::sgn($this->i) >= 0 ? 1 : -1;
        $γ   = \sqrt(($this->r + $this->abs()) / 2);
        $δ   = $sgn * \sqrt((-$this->r + $this->abs()) / 2);

        $z₁ = new Complex($γ, $δ);
        $z₂ = new Complex(-$γ, -$δ);

        return [$z₁, $z₂];
    }

    /**
     * The inverse of a complex number (reciprocal)
     *
     * https://en.wikipedia.org/wiki/Complex_number#Reciprocal
     *
     * @return Complex
     *
     * @throws Exception\BadDataException if = to 0 + 0i
     */
    public function inverse(): Complex
    {
        if ($this->r == 0 && $this->i == 0) {
            throw new Exception\BadDataException('Cannot take inverse of 0 + 0i');
        }

        return $this->complexConjugate()->divide($this->abs() ** 2);
    }

    /**
     * Negate the complex number
     * Switches the signs of both the real and imaginary parts.
     *
     * @return Complex
     */
    public function negate(): Complex
    {
        return new Complex(-$this->r, -$this->i);
    }

    /**
     * Polar form
     * https://en.wikipedia.org/wiki/Complex_number#Polar_form
     *
     * z = a + bi = r(cos(θ) + i  sin(θ))
     * Where
     *  r = |z|
     *  θ = arg(z) (in radians)
     *
     * @return number[]
     */
    public function polarForm(): array
    {
        $r = $this->abs();
        $θ = $this->arg();

        return [$r, $θ];
    }

    /**
     * Complex Exponentiation
     * https://en.wikipedia.org/wiki/Complex_number#Exponential_function
     *
     * eˣ⁺ⁱʸ = eˣ*cos(y) + i*eˣ*sin(y)
     *
     * @return Complex
     */
    public function exp(): Complex
    {
        $r = \exp($this->r) * \cos($this->i);
        $i = \exp($this->r) * \sin($this->i);
        return new Complex($r, $i);
    }
    /**************************************************************************
     * BINARY FUNCTIONS
     **************************************************************************/

    /**
     * Complex addition
     * https://en.wikipedia.org/wiki/Complex_number#Addition_and_subtraction
     *
     * (a + bi) + (c + di) = (a + c) + (b + d)i
     *
     * @param mixed $c
     *
     * @return Complex
     *
     * @throws Exception\IncorrectTypeException if the argument is not numeric or Complex.
     */
    public function add($c): Complex
    {
        if (\is_numeric($c)) {
            $r = $this->r + $c;
            $i = $this->i;
        } elseif ($c instanceof Complex) {
            $r = $this->r + $c->r;
            $i = $this->i + $c->i;
        } else {
            throw new Exception\IncorrectTypeException('Argument must be real or complex number');
        }

        return new Complex($r, $i);
    }

    /**
     * Complex subtraction
     * https://en.wikipedia.org/wiki/Complex_number#Addition_and_subtraction
     *
     * (a + bi) - (c + di) = (a - c) + (b - d)i
     *
     * @param mixed $c
     *
     * @return Complex
     *
     * @throws Exception\IncorrectTypeException if the argument is not numeric or Complex.
     */
    public function subtract($c): Complex
    {
        if (\is_numeric($c)) {
            $r = $this->r - $c;
            $i = $this->i;
        } elseif ($c instanceof Complex) {
            $r = $this->r - $c->r;
            $i = $this->i - $c->i;
        } else {
            throw new Exception\IncorrectTypeException('Argument must be real or complex number');
        }

        return new Complex($r, $i);
    }

    /**
     * Complex multiplication
     * https://en.wikipedia.org/wiki/Complex_number#Multiplication_and_division
     *
     * (a + bi)(c + di) = (ac - bd) + (bc + ad)i
     *
     * @param mixed $c
     *
     * @return Complex
     *
     * @throws Exception\IncorrectTypeException if the argument is not numeric or Complex.
     */
    public function multiply($c): Complex
    {
        if (\is_numeric($c)) {
            $r = $c * $this->r;
            $i = $c * $this->i;
        } elseif ($c instanceof Complex) {
            $r = $this->r * $c->r - $this->i * $c->i;
            $i = $this->i * $c->r + $this->r * $c->i;
        } else {
            throw new Exception\IncorrectTypeException('Argument must be real or complex number');
        }

        return new Complex($r, $i);
    }

    /**
     * Complex division
     * Dividing two complex numbers is accomplished by multiplying the first by the inverse of the second
     * https://en.wikipedia.org/wiki/Complex_number#Multiplication_and_division
     *
     * @param mixed $c
     *
     * @return Complex
     *
     * @throws Exception\IncorrectTypeException if the argument is not numeric or Complex.
     */
    public function divide($c): Complex
    {
        if (\is_numeric($c)) {
            $r = $this->r / $c;
            $i = $this->i / $c;
            return new Complex($r, $i);
        } elseif ($c instanceof Complex) {
            return $this->multiply($c->inverse());
        } else {
            throw new Exception\IncorrectTypeException('Argument must be real or complex number');
        }
    }

    /**
     * Complex exponentiation
     * Raise a complex number to a power.
     *  - https://en.wikipedia.org/wiki/Complex_number#Exponentiation
     *  - https://mathworld.wolfram.com/ComplexExponentiation.html
     *
     * @param Complex|number $c
     *
     * @return Complex
     *
     * @throws Exception\IncorrectTypeException if the argument is not numeric or Complex.
     */
    public function pow($c): Complex
    {
        if (\is_numeric($c)) {
            $tmp = new Complex(0, $c * $this->arg());
            return $tmp->exp()->multiply($this->abs() ** $c);
        }

        if ($c instanceof Complex) {
            $r = $this->abs();
            $θ = $this->arg();
            $real = $r ** $c->r * exp(-1 * $θ * $c->i);
            $inner = $r == 0 ? 0 : $c->i * log($r) + $c->r * $θ;
            $new_r = $real * \cos($inner);
            $new_i = $real * \sin($inner);
            return new Complex($new_r, $new_i);
        }

        throw new Exception\IncorrectTypeException('Argument must be real or complex number');
    }

    /**************************************************************************
     * COMPARISON FUNCTIONS
     **************************************************************************/

    /**
     * Test for equality
     * Two complex numbers are equal if and only if both their real and imaginary parts are equal.
     *
     * https://en.wikipedia.org/wiki/Complex_number#Equality
     *
     * @param Complex $c
     *
     * @return bool
     */
    public function equals(Complex $c): bool
    {
        return \abs($this->r - $c->r) < self::EPSILON && \abs($this->i - $c->i) < self::EPSILON;
    }
}
