<?php
declare(strict_types = 1);

namespace DASPRiD\EnumTest;

use DASPRiD\Enum\AbstractEnum;

/**
 * @method static self MONDAY()
 * @method static self TUESDAY()
 * @method static self WEDNESDAY()
 * @method static self THURSDAY()
 * @method static self FRIDAY()
 * @method static self SATURDAY()
 * @method static self SUNDAY()
 */
final class WeekDay extends AbstractEnum
{
    protected const MONDAY = null;
    protected const TUESDAY = null;
    protected const WEDNESDAY = null;
    protected const THURSDAY = null;
    protected const FRIDAY = null;
    protected const SATURDAY = null;
    protected const SUNDAY = null;
}
