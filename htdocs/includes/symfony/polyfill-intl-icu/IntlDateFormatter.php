<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu;

use Symfony\Polyfill\Intl\Icu\DateFormat\FullTransformer;
use Symfony\Polyfill\Intl\Icu\Exception\MethodArgumentNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\MethodArgumentValueNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException;

/**
 * Replacement for PHP's native {@link \IntlDateFormatter} class.
 *
 * The only methods currently supported in this class are:
 *
 *  - {@link __construct}
 *  - {@link create}
 *  - {@link format}
 *  - {@link getCalendar}
 *  - {@link getDateType}
 *  - {@link getErrorCode}
 *  - {@link getErrorMessage}
 *  - {@link getLocale}
 *  - {@link getPattern}
 *  - {@link getTimeType}
 *  - {@link getTimeZoneId}
 *  - {@link isLenient}
 *  - {@link parse}
 *  - {@link setLenient}
 *  - {@link setPattern}
 *  - {@link setTimeZoneId}
 *  - {@link setTimeZone}
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
abstract class IntlDateFormatter
{
    /**
     * The error code from the last operation.
     *
     * @var int
     */
    protected $errorCode = Icu::U_ZERO_ERROR;

    /**
     * The error message from the last operation.
     *
     * @var string
     */
    protected $errorMessage = 'U_ZERO_ERROR';

    /* date/time format types */
    public const NONE = -1;
    public const FULL = 0;
    public const LONG = 1;
    public const MEDIUM = 2;
    public const SHORT = 3;

    /* date format types */
    public const RELATIVE_FULL = 128;
    public const RELATIVE_LONG = 129;
    public const RELATIVE_MEDIUM = 130;
    public const RELATIVE_SHORT = 131;

    /* calendar formats */
    public const TRADITIONAL = 0;
    public const GREGORIAN = 1;

    /**
     * Patterns used to format the date when no pattern is provided.
     */
    private $defaultDateFormats = [
        self::NONE => '',
        self::FULL => 'EEEE, MMMM d, y',
        self::LONG => 'MMMM d, y',
        self::MEDIUM => 'MMM d, y',
        self::SHORT => 'M/d/yy',
        self::RELATIVE_FULL => 'EEEE, MMMM d, y',
        self::RELATIVE_LONG => 'MMMM d, y',
        self::RELATIVE_MEDIUM => 'MMM d, y',
        self::RELATIVE_SHORT => 'M/d/yy',
    ];

    /**
     * Patterns used to format the time when no pattern is provided.
     */
    private $defaultTimeFormats = [
        self::FULL => 'h:mm:ss a zzzz',
        self::LONG => 'h:mm:ss a z',
        self::MEDIUM => 'h:mm:ss a',
        self::SHORT => 'h:mm a',
    ];

    private $dateType;
    private $timeType;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;

    /**
     * @var bool
     */
    private $uninitializedTimeZoneId = false;

    /**
     * @var string
     */
    private $timezoneId;

    /**
     * @var bool
     */
    private $isRelativeDateType = false;

    /**
     * @param string|null                             $locale   The locale code. The only currently supported locale is "en" (or null using the default locale, i.e. "en")
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone Timezone identifier
     * @param \IntlCalendar|int|null                  $calendar Calendar to use for formatting or parsing. The only currently
     *                                                          supported value is IntlDateFormatter::GREGORIAN (or null using the default calendar, i.e. "GREGORIAN")
     *
     * @see https://php.net/intldateformatter.create
     * @see http://userguide.icu-project.org/formatparse/datetime
     *
     * @throws MethodArgumentValueNotImplementedException When $locale different than "en" or null is passed
     * @throws MethodArgumentValueNotImplementedException When $calendar different than GREGORIAN is passed
     */
    public function __construct(?string $locale, ?int $dateType, ?int $timeType, $timezone = null, $calendar = null, ?string $pattern = '')
    {
        if ('en' !== $locale && null !== $locale) {
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'locale', $locale, 'Only the locale "en" is supported');
        }

        if (self::GREGORIAN !== $calendar && null !== $calendar) {
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'calendar', $calendar, 'Only the GREGORIAN calendar is supported');
        }

        if (\PHP_VERSION_ID >= 80100) {
            if (null === $dateType) {
                @trigger_error('Passing null to parameter #2 ($dateType) of type int is deprecated', \E_USER_DEPRECATED);
            }

            if (null === $timeType) {
                @trigger_error('Passing null to parameter #3 ($timeType) of type int is deprecated', \E_USER_DEPRECATED);
            }
        }

        $this->dateType = $dateType ?? self::FULL;
        $this->timeType = $timeType ?? self::FULL;

        if ('' === ($pattern ?? '')) {
            $pattern = $this->getDefaultPattern();
        }

        $this->setPattern($pattern);
        $this->setTimeZone($timezone);

        if (\in_array($this->dateType, [self::RELATIVE_FULL, self::RELATIVE_LONG, self::RELATIVE_MEDIUM, self::RELATIVE_SHORT], true)) {
            $this->isRelativeDateType = true;
        }
    }

    /**
     * Static constructor.
     *
     * @param string|null                             $locale   The locale code. The only currently supported locale is "en" (or null using the default locale, i.e. "en")
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone Timezone identifier
     * @param \IntlCalendar|int|null                  $calendar Calendar to use for formatting or parsing; default is Gregorian
     *                                                          One of the calendar constants
     *
     * @return static
     *
     * @see https://php.net/intldateformatter.create
     * @see http://userguide.icu-project.org/formatparse/datetime
     *
     * @throws MethodArgumentValueNotImplementedException When $locale different than "en" or null is passed
     * @throws MethodArgumentValueNotImplementedException When $calendar different than GREGORIAN is passed
     */
    public static function create(?string $locale, ?int $dateType, ?int $timeType, $timezone = null, int $calendar = null, ?string $pattern = '')
    {
        return new static($locale, $dateType, $timeType, $timezone, $calendar, $pattern);
    }

    /**
     * Format the date/time value (timestamp) as a string.
     *
     * @param int|string|\DateTimeInterface $datetime The timestamp to format
     *
     * @return string|bool The formatted value or false if formatting failed
     *
     * @see https://php.net/intldateformatter.format
     *
     * @throws MethodArgumentValueNotImplementedException If one of the formatting characters is not implemented
     */
    public function format($datetime)
    {
        // intl allows timestamps to be passed as arrays - we don't
        if (\is_array($datetime)) {
            $message = 'Only Unix timestamps and DateTime objects are supported';

            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'datetime', $datetime, $message);
        }

        if (\is_string($datetime) && $dt = \DateTime::createFromFormat('U', $datetime)) {
            $datetime = $dt;
        }

        // behave like the intl extension
        $argumentError = null;
        if (!\is_int($datetime) && !$datetime instanceof \DateTimeInterface) {
            $argumentError = sprintf('datefmt_format: string \'%s\' is not numeric, which would be required for it to be a valid date', $datetime);
        }

        if (null !== $argumentError) {
            Icu::setError(Icu::U_ILLEGAL_ARGUMENT_ERROR, $argumentError);
            $this->errorCode = Icu::getErrorCode();
            $this->errorMessage = Icu::getErrorMessage();

            return false;
        }

        if ($datetime instanceof \DateTimeInterface) {
            $datetime = $datetime->format('U');
        }

        $pattern = $this->getPattern();
        $formatted = '';

        if ($this->isRelativeDateType && $formatted = $this->getRelativeDateFormat($datetime)) {
            if (self::NONE === $this->timeType) {
                $pattern = '';
            } else {
                $pattern = $this->defaultTimeFormats[$this->timeType];
                if (\in_array($this->dateType, [self::RELATIVE_MEDIUM, self::RELATIVE_SHORT], true)) {
                    $formatted .= ', ';
                } else {
                    $formatted .= ' at ';
                }
            }
        }

        $transformer = new FullTransformer($pattern, $this->getTimeZoneId());
        $formatted .= $transformer->format($this->createDateTime($datetime));

        // behave like the intl extension
        Icu::setError(Icu::U_ZERO_ERROR);
        $this->errorCode = Icu::getErrorCode();
        $this->errorMessage = Icu::getErrorMessage();

        return $formatted;
    }

    /**
     * Not supported. Formats an object.
     *
     * @return string The formatted value
     *
     * @see https://php.net/intldateformatter.formatobject
     *
     * @throws MethodNotImplementedException
     */
    public static function formatObject($datetime, $format = null, string $locale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns the formatter's calendar.
     *
     * @return int The calendar being used by the formatter. Currently always returns
     *             IntlDateFormatter::GREGORIAN.
     *
     * @see https://php.net/intldateformatter.getcalendar
     */
    public function getCalendar()
    {
        return self::GREGORIAN;
    }

    /**
     * Not supported. Returns the formatter's calendar object.
     *
     * @return object The calendar's object being used by the formatter
     *
     * @see https://php.net/intldateformatter.getcalendarobject
     *
     * @throws MethodNotImplementedException
     */
    public function getCalendarObject()
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns the formatter's datetype.
     *
     * @return int The current value of the formatter
     *
     * @see https://php.net/intldateformatter.getdatetype
     */
    public function getDateType()
    {
        return $this->dateType;
    }

    /**
     * Returns formatter's last error code. Always returns the U_ZERO_ERROR class constant value.
     *
     * @return int The error code from last formatter call
     *
     * @see https://php.net/intldateformatter.geterrorcode
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Returns formatter's last error message. Always returns the U_ZERO_ERROR_MESSAGE class constant value.
     *
     * @return string The error message from last formatter call
     *
     * @see https://php.net/intldateformatter.geterrormessage
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Returns the formatter's locale.
     *
     * @param int $type Not supported. The locale name type to return (Locale::VALID_LOCALE or Locale::ACTUAL_LOCALE)
     *
     * @return string The locale used to create the formatter. Currently always
     *                returns "en".
     *
     * @see https://php.net/intldateformatter.getlocale
     */
    public function getLocale(int $type = Locale::ACTUAL_LOCALE)
    {
        return 'en';
    }

    /**
     * Returns the formatter's pattern.
     *
     * @return string The pattern string used by the formatter
     *
     * @see https://php.net/intldateformatter.getpattern
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Returns the formatter's time type.
     *
     * @return int The time type used by the formatter
     *
     * @see https://php.net/intldateformatter.gettimetype
     */
    public function getTimeType()
    {
        return $this->timeType;
    }

    /**
     * Returns the formatter's timezone identifier.
     *
     * @return string The timezone identifier used by the formatter
     *
     * @see https://php.net/intldateformatter.gettimezoneid
     */
    public function getTimeZoneId()
    {
        if (!$this->uninitializedTimeZoneId) {
            return $this->timezoneId;
        }

        return date_default_timezone_get();
    }

    /**
     * Not supported. Returns the formatter's timezone.
     *
     * @return mixed The timezone used by the formatter
     *
     * @see https://php.net/intldateformatter.gettimezone
     *
     * @throws MethodNotImplementedException
     */
    public function getTimeZone()
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns whether the formatter is lenient.
     *
     * @return bool Currently always returns false
     *
     * @see https://php.net/intldateformatter.islenient
     *
     * @throws MethodNotImplementedException
     */
    public function isLenient()
    {
        return false;
    }

    /**
     * Not supported. Parse string to a field-based time value.
     *
     * @return string Localtime compatible array of integers: contains 24 hour clock value in tm_hour field
     *
     * @see https://php.net/intldateformatter.localtime
     *
     * @throws MethodNotImplementedException
     */
    public function localtime(string $string, &$offset = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Parse string to a timestamp value.
     *
     * @return int|false Parsed value as a timestamp
     *
     * @see https://php.net/intldateformatter.parse
     *
     * @throws MethodArgumentNotImplementedException When $offset different than null, behavior not implemented
     */
    public function parse(string $string, &$offset = null)
    {
        // We don't calculate the position when parsing the value
        if (null !== $offset) {
            throw new MethodArgumentNotImplementedException(__METHOD__, 'offset');
        }

        $dateTime = $this->createDateTime(0);
        $transformer = new FullTransformer($this->getPattern(), $this->getTimeZoneId());

        $timestamp = $transformer->parse($dateTime, $string);

        // behave like the intl extension. FullTransformer::parse() set the proper error
        $this->errorCode = Icu::getErrorCode();
        $this->errorMessage = Icu::getErrorMessage();

        return $timestamp;
    }

    /**
     * Not supported. Set the formatter's calendar.
     *
     * @param \IntlCalendar|int|null $calendar
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/intldateformatter.setcalendar
     *
     * @throws MethodNotImplementedException
     */
    public function setCalendar($calendar)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Set the leniency of the parser.
     *
     * Define if the parser is strict or lenient in interpreting inputs that do not match the pattern
     * exactly. Enabling lenient parsing allows the parser to accept otherwise flawed date or time
     * patterns, parsing as much as possible to obtain a value. Extra space, unrecognized tokens, or
     * invalid values ("February 30th") are not accepted.
     *
     * @param bool $lenient Sets whether the parser is lenient or not. Currently
     *                      only false (strict) is supported.
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/intldateformatter.setlenient
     *
     * @throws MethodArgumentValueNotImplementedException When $lenient is true
     */
    public function setLenient(bool $lenient)
    {
        if ($lenient) {
            throw new MethodArgumentValueNotImplementedException(__METHOD__, 'lenient', $lenient, 'Only the strict parser is supported');
        }

        return true;
    }

    /**
     * Set the formatter's pattern.
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/intldateformatter.setpattern
     * @see http://userguide.icu-project.org/formatparse/datetime
     */
    public function setPattern(string $pattern)
    {
        $this->pattern = $pattern;

        return true;
    }

    /**
     * Sets formatterʼs timezone.
     *
     * @param \IntlTimeZone|\DateTimeZone|string|null $timezone
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/intldateformatter.settimezone
     */
    public function setTimeZone($timezone)
    {
        if ($timezone instanceof \IntlTimeZone) {
            $timezone = $timezone->getID();
        }

        if ($timezone instanceof \DateTimeZone) {
            $timezone = $timezone->getName();

            // DateTimeZone returns the GMT offset timezones without the leading GMT, while our parsing requires it.
            if (!empty($timezone) && ('+' === $timezone[0] || '-' === $timezone[0])) {
                $timezone = 'GMT'.$timezone;
            }
        }

        if (null === $timezone) {
            $timezone = date_default_timezone_get();

            $this->uninitializedTimeZoneId = true;
        }

        // Backup original passed time zone
        $timezoneId = $timezone;

        // Get an Etc/GMT time zone that is accepted for \DateTimeZone
        if ('GMT' !== $timezone && 0 === strpos($timezone, 'GMT')) {
            try {
                $timezone = DateFormat\TimezoneTransformer::getEtcTimeZoneId($timezone);
            } catch (\InvalidArgumentException $e) {
                // Does nothing, will fallback to UTC
            }
        }

        try {
            $this->dateTimeZone = new \DateTimeZone($timezone);
            if ('GMT' !== $timezone && $this->dateTimeZone->getName() !== $timezone) {
                $timezoneId = $this->getTimeZoneId();
            }
        } catch (\Exception $e) {
            $timezoneId = $timezone = $this->getTimeZoneId();
            $this->dateTimeZone = new \DateTimeZone($timezone);
        }

        $this->timezoneId = $timezoneId;

        return true;
    }

    /**
     * Create and returns a DateTime object with the specified timestamp and with the
     * current time zone.
     *
     * @return \DateTime
     */
    protected function createDateTime($timestamp)
    {
        $dateTime = \DateTime::createFromFormat('U', $timestamp);
        $dateTime->setTimezone($this->dateTimeZone);

        return $dateTime;
    }

    /**
     * Returns a pattern string based in the datetype and timetype values.
     *
     * @return string
     */
    protected function getDefaultPattern()
    {
        $pattern = '';
        if (self::NONE !== $this->dateType) {
            $pattern = $this->defaultDateFormats[$this->dateType];
        }
        if (self::NONE !== $this->timeType) {
            if (\in_array($this->dateType, [self::FULL, self::LONG, self::RELATIVE_FULL, self::RELATIVE_LONG], true)) {
                $pattern .= ' \'at\' ';
            } elseif (self::NONE !== $this->dateType) {
                $pattern .= ', ';
            }
            $pattern .= $this->defaultTimeFormats[$this->timeType];
        }

        return $pattern;
    }

    private function getRelativeDateFormat(int $timestamp): string
    {
        $today = $this->createDateTime(time());
        $today->setTime(0, 0, 0);

        $datetime = $this->createDateTime($timestamp);
        $datetime->setTime(0, 0, 0);

        $interval = $today->diff($datetime);

        if (false !== $interval) {
            if (0 === $interval->days) {
                return 'today';
            }

            if (1 === $interval->days) {
                return 1 === $interval->invert ? 'yesterday' : 'tomorrow';
            }
        }

        return '';
    }
}
