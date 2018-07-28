<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Caster;

use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Casts DateTimeInterface related classes to array representation.
 *
 * @author Dany Maillard <danymaillard93b@gmail.com>
 */
class DateCaster
{
    private const PERIOD_LIMIT = 3;

    public static function castDateTime(\DateTimeInterface $d, array $a, Stub $stub, $isNested, $filter)
    {
        $prefix = Caster::PREFIX_VIRTUAL;
        $location = $d->getTimezone()->getLocation();
        $fromNow = (new \DateTime())->diff($d);

        $title = $d->format('l, F j, Y')
            ."\n".self::formatInterval($fromNow).' from now'
            .($location ? ($d->format('I') ? "\nDST On" : "\nDST Off") : '')
        ;

        $a = array();
        $a[$prefix.'date'] = new ConstStub(self::formatDateTime($d, $location ? ' e (P)' : ' P'), $title);

        $stub->class .= $d->format(' @U');

        return $a;
    }

    public static function castInterval(\DateInterval $interval, array $a, Stub $stub, $isNested, $filter)
    {
        $now = new \DateTimeImmutable();
        $numberOfSeconds = $now->add($interval)->getTimestamp() - $now->getTimestamp();
        $title = number_format($numberOfSeconds, 0, '.', ' ').'s';

        $i = array(Caster::PREFIX_VIRTUAL.'interval' => new ConstStub(self::formatInterval($interval), $title));

        return $filter & Caster::EXCLUDE_VERBOSE ? $i : $i + $a;
    }

    private static function formatInterval(\DateInterval $i)
    {
        $format = '%R ';

        if (0 === $i->y && 0 === $i->m && ($i->h >= 24 || $i->i >= 60 || $i->s >= 60)) {
            $i = date_diff($d = new \DateTime(), date_add(clone $d, $i)); // recalculate carry over points
            $format .= 0 < $i->days ? '%ad ' : '';
        } else {
            $format .= ($i->y ? '%yy ' : '').($i->m ? '%mm ' : '').($i->d ? '%dd ' : '');
        }

        $format .= $i->h || $i->i || $i->s || $i->f ? '%H:%I:'.self::formatSeconds($i->s, substr($i->f, 2)) : '';
        $format = '%R ' === $format ? '0s' : $format;

        return $i->format(rtrim($format));
    }

    public static function castTimeZone(\DateTimeZone $timeZone, array $a, Stub $stub, $isNested, $filter)
    {
        $location = $timeZone->getLocation();
        $formatted = (new \DateTime('now', $timeZone))->format($location ? 'e (P)' : 'P');
        $title = $location && extension_loaded('intl') ? \Locale::getDisplayRegion('-'.$location['country_code']) : '';

        $z = array(Caster::PREFIX_VIRTUAL.'timezone' => new ConstStub($formatted, $title));

        return $filter & Caster::EXCLUDE_VERBOSE ? $z : $z + $a;
    }

    public static function castPeriod(\DatePeriod $p, array $a, Stub $stub, $isNested, $filter)
    {
        $dates = array();
        if (\PHP_VERSION_ID >= 70107) { // see https://bugs.php.net/bug.php?id=74639
            foreach (clone $p as $i => $d) {
                if (self::PERIOD_LIMIT === $i) {
                    $now = new \DateTimeImmutable();
                    $dates[] = sprintf('%s more', ($end = $p->getEndDate())
                        ? ceil(($end->format('U.u') - $d->format('U.u')) / ($now->add($p->getDateInterval())->format('U.u') - $now->format('U.u')))
                        : $p->recurrences - $i
                    );
                    break;
                }
                $dates[] = sprintf('%s) %s', $i + 1, self::formatDateTime($d));
            }
        }

        $period = sprintf(
            'every %s, from %s (%s) %s',
            self::formatInterval($p->getDateInterval()),
            self::formatDateTime($p->getStartDate()),
            $p->include_start_date ? 'included' : 'excluded',
            ($end = $p->getEndDate()) ? 'to '.self::formatDateTime($end) : 'recurring '.$p->recurrences.' time/s'
        );

        $p = array(Caster::PREFIX_VIRTUAL.'period' => new ConstStub($period, implode("\n", $dates)));

        return $filter & Caster::EXCLUDE_VERBOSE ? $p : $p + $a;
    }

    private static function formatDateTime(\DateTimeInterface $d, $extra = '')
    {
        return $d->format('Y-m-d H:i:'.self::formatSeconds($d->format('s'), $d->format('u')).$extra);
    }

    private static function formatSeconds($s, $us)
    {
        return sprintf('%02d.%s', $s, 0 === ($len = strlen($t = rtrim($us, '0'))) ? '0' : ($len <= 3 ? str_pad($t, 3, '0') : $us));
    }
}
