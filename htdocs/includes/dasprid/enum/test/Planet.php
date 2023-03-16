<?php
declare(strict_types = 1);

namespace DASPRiD\EnumTest;

use DASPRiD\Enum\AbstractEnum;

/**
 * @method static self MERCURY()
 * @method static self VENUS()
 * @method static self EARTH()
 * @method static self MARS()
 * @method static self JUPITER()
 * @method static self SATURN()
 * @method static self URANUS()
 * @method static self NEPTUNE()
 */
final class Planet extends AbstractEnum
{
    protected const MERCURY = [3.303e+23, 2.4397e6];
    protected const VENUS = [4.869e+24, 6.0518e6];
    protected const EARTH = [5.976e+24, 6.37814e6];
    protected const MARS = [6.421e+23, 3.3972e6];
    protected const JUPITER = [1.9e+27, 7.1492e7];
    protected const SATURN = [5.688e+26, 6.0268e7];
    protected const URANUS = [8.686e+25, 2.5559e7];
    protected const NEPTUNE = [1.024e+26, 2.4746e7];

    /**
     * Universal gravitational constant.
     */
    private const G = 6.67300E-11;

    /**
     * Mass in kilograms.
     *
     * @var float
     */
    private $mass;

    /**
     * Radius in meters.
     *
     * @var float
     */
    private $radius;

    protected function __construct(float $mass, float $radius)
    {
        $this->mass = $mass;
        $this->radius = $radius;
    }

    public function mass() : float
    {
        return $this->mass;
    }

    public function radius() : float
    {
        return $this->radius;
    }

    public function surfaceGravity() : float
    {
        return self::G * $this->mass / ($this->radius * $this->radius);
    }

    public function surfaceWeight(float $otherMass) : float
    {
        return $otherMass * $this->surfaceGravity();
    }
}
