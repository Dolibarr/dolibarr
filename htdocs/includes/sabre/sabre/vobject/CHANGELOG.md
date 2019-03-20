ChangeLog
=========

4.1.2 (2016-12-15)
------------------

* #340: Support for `BYYEARDAY` recurrence when `FREQ=YEARLY`. (@PHPGangsta)
* #341: Support for `BYWEEKNO` recurrence when `FREQ=YEARLY`. (@PHPGangsta)
* Updated to the latest windows timezone data mappings.
* #344: Auto-detecting more Outlook 365-generated timezone identifiers.
  (@jpirkey)
* #348: `FreeBusyGenerator` can now accept streams.
* Support sabre/xml 1.5 and 2.0.
* #355: Support `DateTimeInterface` in more places where only `DateTime` was
  supported. (@gharlan).
* #351: Fixing an inclusive/exclusive problem with `isInTimeRange` and
  `fastForward` with all-day events. (@strokyl, thanks you are brilliant).


4.1.1 (2016-07-15)
------------------

* #327: Throwing `InvalidDataException` in more cases where invalid iCalendar
  dates and times were provided. (@rsto)
* #331: Fix dealing with multiple overridden instances falling on the same
  date/time (@afedyk-sugarcrm).
* #333: Fix endless loop on invalid `BYMONTH` values in recurrence.
  (@PHPGangsta)
* #339: Fixed a few `validate()` results when repair is off. (@PHPGangsta)
* #338: Stripping invalid `BYMONTH=` rules during `validate()` (@PHPGangsta)
* #336: Fix incorrect `BYSECOND=` validation. (@PHPGangsta)


4.1.0 (2016-04-06)
------------------

* #309: When expanding recurring events, the first event should also have a
  `RECURRENCE-ID` property.
* #306: iTip REPLYs to the first instance of a recurring event was not handled
  correctly.
* Slightly better error message during validation of `N` and `ADR` properties.
* #312: Correctly extracing timezone in the iTip broker, even when we don't
  have a master event. (@vkomrakov-sugar).
* When validating a component's property that must appear once and which could
  automatically be repaired, make sure we report the change as 'repaired'.
* Added a PHPUnitAssertions trait. This trait makes it easy to compare two
  vcards or iCalendar objects semantically.
* Better error message when parsing objects with an invalid `VALUE` parameter.


4.0.3 (2016-03-12)
------------------

* #300: Added `VCard::getByType()` to quickly get a property with a specific
  `TYPE` parameter. (@kbond)
* #302: `UNTIL` was not encoded correctly when converting to jCal.
  (@GrahamLinagora)
* #303: `COUNT` is now encoded as an int in jCal instead of a string. (@strokyl)
* #295: `RRULE` now has more validation and repair rules.


4.0.2 (2016-01-11)
------------------

* #288: Only decode `CHARSET` if we're reading vCard 2.1. If it appears
  in any other document, we must ignore it.


4.0.1 (2016-01-04)
------------------

* #284: When generating `CANCEL` iTip messages, we now include `DTEND`.
  (@kewisch)


4.0.0 (2015-12-11)
------------------

* #274: When creating new vCards, the default vCard version is now 4.0.
* #275: `VEVENT`, `VTODO` and `VCARD` now automatically get a `UID` and
  `DTSTAMP` property if this was not already specified.
* `ParseException` now extends `\Exception`.
* `Sabre\VObject\Reader::read` now has a `$charset` argument.
* #272: `Sabre\VObject\Recur\EventIterator::$maxInstances` is now
  `Sabre\VObject\Settings::$maxRecurrences` and is also honored by the
  FreeBusyGenerator.
* #278: `expand()` did not work correctly on events with sub-components.


4.0.0-beta1 (2015-12-02)
------------------------

* #258: Support for expanding events that use `RDATE`. (@jabdoa2)
* #258: Correctly support TZID for events that use `RDATE`. (@jabdoa2)
* #240: `Component\VCalendar::expand()` now returns a new expanded `VCalendar`
  object, instead of editing the existing `VCalendar` in-place. This is a BC
  break.
* #265: Using the new `InvalidDataException` in place of
  `InvalidArgumentException` and `LogicException` in all places where we fail
  because there was something wrong with input data.
* #227: Always add `VALUE=URI` to `PHOTO` properties.
* #235: Always add `VALUE=URI` to `URL` properties.
* It's now possible to override which class is used instead of
  `Component\VCalendar` or `Component\VCard` during parsing.
* #263: Lots of small cleanups. (@jakobsack)
* #220: Automatically stop recurring after 3500 recurrences.
* #41: Allow user to set different encoding than UTF-8 when decoding vCards.
* #41: Support the `ENCODING` parameter from vCard 2.1.
  Both ISO-8859-1 and Windows-1252 are currently supported.
* #185: Fix encoding/decoding of `TIME` values in jCal/jCard.


4.0.0-alpha2 (2015-09-04)
-------------------------

* Updated windows timezone file to support new mexican timezone.
* #239: Added a `BirthdayCalendarGenerator`. (@DominikTo)
* #250: `isInTimeRange()` now considers the timezone for floating dates and
  times. (@armin-hackmann)
* Added a duplicate vcard merging tool for the command line.
* #253: `isInTimeRange()` now correctly handles events that throw the
  `NoInstancesException` exception. (@migrax, @DominikTo)
* #254: The parser threw an `E_NOTICE` for certain invalid objects. It now
  correctly throws a `ParseException`.


4.0.0-alpha1 (2015-07-17)
-------------------------

* sabre/vobject now requires PHP 5.5.
* #244: PHP7 support.
* Lots of speedups and reduced memory usage!
* #160: Support for xCal a.k.a. RFC6321! (@Hywan)
* #192: Support for xCard a.k.a. RFC6351! (@Hywan)
* #139: We now accept `DateTimeInterface` wherever it accepted `DateTime`
   before in arguments. This means that either `DateTime` or
  `DateTimeImmutable` may be used everywhere.
* #242: Full support for the `VAVAILABILITY` component, and calculating
  `VFREEBUSY` based on `VAVAILABILITY` data.
* #186: Fixing conversion of `UTC-OFFSET` properties when going back and
  forward between jCal and iCalendar.
* Properties, Components and Parameters now implement PHP's `JsonSerializable`
  interface.
* #139: We now _always_ return `DateTimeImmutable` from any method. This could
  potentially have big implications if you manipulate Date objects anywhere.
* #161: Simplified `ElementList` by extending `ArrayIterator`.
* Removed `RecurrenceIterator` (use Recur\EventIterator instead).
* Now using php-cs-fixer to automatically enforce and correct CS.
* #233: The `+00:00` timezone is now recognized as UTC. (@c960657)
* #237: Added a `destroy()` method to all documents. This method breaks any
  circular references, allowing PHP to free up memory.
* #197: Made accessing properties and objects by their name a lot faster. This
  especially helps objects that have a lot of sub-components or properties,
  such as large iCalendar objects.
* #197: The `$children` property on components has been changed from `public`
  to `protected`. Use the `children()` method instead to get a flat list of
  objects.
* #244: The `Float` and `Integer` classes have been renamed to `FloatValue`
  and `IntegerValue` to allow PHP 7 compatibility.


3.5.3 (2016-10-06)
------------------

* #331: Fix dealing with multiple overridden instances falling on the same
  date/time (@afedyk-sugarcrm).


3.5.2 (2016-04-24)
-----------------

* #312: Backported a fix related to iTip processing of events with timezones,
  without a master event.


3.5.1 (2016-04-06)
------------------

* #309: When expanding recurring events, the first event should also have a
  `RECURRENCE-ID` property.
* #306: iTip REPLYs to the first instance of a recurring event was not handled
  correctly.


3.5.0 (2016-01-11)
------------------

* This release supports PHP 7, contrary to 3.4.x versions.
* BC Break: `Sabre\VObject\Property\Float` has been renamed to
  `Sabre\VObject\Property\FloatValue`.
* BC Break: `Sabre\VObject\Property\Integer` has been renamed to
  `Sabre\VObject\Property\IntegerValue`.


3.4.9 (2016-01-11)
------------------

* This package now specifies in composer.json that it does not support PHP 7.
  For PHP 7, use version 3.5.x or 4.x.


3.4.8 (2016-01-04)
------------------

* #284: When generating `CANCEL` iTip messages, we now include `DTEND`.
  (@kewisch).


3.4.7 (2015-09-05)
------------------

* #253: Handle `isInTimeRange` for recurring events that have 0 valid
  instances. (@DominikTo, @migrax).


3.4.6 (2015-08-06)
------------------

* #250: Recurring all-day events are incorrectly included in time range
  requests when not using UTC in the time range. (@armin-hackmann)


3.4.5 (2015-06-02)
------------------

* #229: Converting vcards from 3.0 to 4.0 that contained a `LANG` property
  would throw an error.


3.4.4 (2015-05-27)
------------------

* #228: Fixed a 'party crasher' bug in the iTip broker. This would break
  scheduling in some cases.


3.4.3 (2015-05-19)
------------------

* #219: Corrected validation of `EXDATE` properties with more than one value.
* #212: `BYSETPOS` with values below `-1` was broken and could cause infinite
  loops.
* #211: Fix `BYDAY=-5TH` in recurrence iterator. (@lindquist)
* #216: `ENCODING` parameter is now validated for all document types.
* #217: Initializing vCard `DATE` objects with a PHP DateTime object will now
  work correctly. (@thomascube)


3.4.2 (2015-02-25)
------------------

* #210: iTip: Replying to an event without a master event was broken.


3.4.1 (2015-02-24)
------------------

* A minor change to ensure that unittests work correctly in the sabre/dav
  test-suite.


3.4.0 (2015-02-23)
------------------

* #196: Made parsing recurrence rules a lot faster on big calendars.
* Updated windows timezone mappings to latest unicode version.
* #202: Support for parsing and validating `VAVAILABILITY` components. (@Hywan)
* #195: PHP 5.3 compatibility in 'generatevcards' script. (@rickdenhaan)
* #205: Improving handling of multiple `EXDATE` when processing iTip changes.
  (@armin-hackmann)
* #187: Fixed validator rules for `LAST-MODIFIED` properties.
* #188: Retain floating times when generating instances using
  `Recur\EventIterator`.
* #203: Skip tests for timezones that are not supported on older PHP versions,
  instead of a hard fail.
* #204: Dealing a bit better with vCard date-time values that contained
  milliseconds. (which is normally invalid). (@armin-hackmann)


3.3.5 (2015-01-09)
------------------

* #168: Expanding calendars now removes objects with recurrence rules that
  don't have a valid recurrence instance.
* #177: SCHEDULE-STATUS should not contain a reason phrase, only a status
  code.
* #175: Parser can now read and skip the UTF-8 BOM.
* #179: Added `isFloating` to `DATE-TIME` properties.
* #179: Fixed jCal serialization of floating `DATE-TIME` properties.
* #173: vCard converter failed for `X-ABDATE` properties that had no
  `X-ABLABEL`.
* #180: Added `PROFILE_CALDAV` and `PROFILE_CARDDAV` to enable validation rules
  specific for CalDAV/CardDAV servers.
* #176: A missing `UID` is no longer an error, but a warning for the vCard
  validator, unless `PROFILE_CARDDAV` is specified.


3.3.4 (2014-11-19)
------------------

* #154: Converting `ANNIVERSARY` to `X-ANNIVERSARY` and `X-ABDATE` and
  vice-versa when converting to/from vCard 4.
* #154: It's now possible to easily select all vCard properties belonging to
  a single group with `$vcard->{'ITEM1.'}` syntax. (@armin-hackmann)
* #156: Simpler way to check if a string is UTF-8. (@Hywan)
* Unittest improvements.
* #159: The recurrence iterator, freebusy generator and iCalendar DATE and
  DATE-TIME properties can now all accept a reference timezone when working
  floating times or all-day events.
* #159: Master events will no longer get a `RECURRENCE-ID` when expanding.
* #159: `RECURRENCE-ID` for all-day events will now be correct when expanding.
* #163: Added a `getTimeZone()` method to `VTIMEZONE` components.


3.3.3 (2014-10-09)
------------------

* #142: `CANCEL` and `REPLY` messages now include the `DTSTART` from the
  original event.
* #143: `SCHEDULE-AGENT` on the `ORGANIZER` property is respected.
* #144: `PARTSTAT=NEEDS-ACTION` is now set for new invites, if no `PARTSTAT` is
  set to support the inbox feature of iOS.
* #147: Bugs related to scheduling all-day events.
* #148: Ignore events that have attendees but no organizer.
* #149: Avoiding logging errors during timezone detection. This is a workaround
  for a PHP bug.
* Support for "Line Islands Standard Time" windows timezone.
* #154: Correctly work around vCard parameters that have a value but no name.


3.3.2 (2014-09-19)
------------------

* Changed: iTip broker now sets RSVP status to false when replies are received.
* #118: iTip Message now has a `getScheduleStatus()` method.
* #119: Support for detecting 'significant changes'.
* #120: Support for `SCHEDULE-FORCE-SEND`.
* #121: iCal demands parameters containing the + sign to be quoted.
* #122: Don't generate REPLY messages for events that have been cancelled.
* #123: Added `SUMMARY` to iTip messages.
* #130: Incorrect validation rules for `RELATED` (should be `RELATED-TO`).
* #128: `ATTACH` in iCalendar is `URI` by default, not `BINARY`.
* #131: RRULE that doesn't provide a single valid instance now throws an
  exception.
* #136: Validator rejects *all* control characters. We were missing a few.
* #133: Splitter objects will throw exceptions when receiving incompatible
  objects.
* #127: Attendees who delete recurring event instances events they had already
  declined earlier will no longer generate another reply.
* #125: Send CANCEL messages when ORGANIZER property gets deleted.


3.3.1 (2014-08-18)
------------------

* Changed: It's now possible to pass DateTime objects when using the magic
  setters on properties. (`$event->DTSTART = new DateTime('now')`).
* #111: iTip Broker does not process attendee adding events to EXDATE.
* #112: EventIterator now sets TZID on RECURRENCE-ID.
* #113: Timezone support during creation of iTip REPLY messages.
* #114: VTIMEZONE is retained when generating new REQUEST objects.
* #114: Support for 'MAILTO:' style email addresses (in uppercase) in the iTip
  broker. This improves evolution support.
* #115: Using REQUEST-STATUS from REPLY messages and now propegating that into
  SCHEDULE-STATUS.


3.3.0 (2014-08-07)
------------------

* We now use PSR-4 for the directory structure. This means that everything
  that was used to be in the `lib/Sabre/VObject` directory is now moved to
  `lib/`. If you use composer to load this library, you shouldn't have to do
  anything about that though.
* VEVENT now get populated with a DTSTAMP and UID property by default.
* BC Break: Removed the 'includes.php' file. Use composer instead.
* #103: Added support for processing [iTip][iTip] messages. This allows a user
  to parse incoming iTip messages and apply the result on existing calendars,
  or automatically generate invites/replies/cancellations based on changes that
  a user made on objects.
* #75, #58, #18: Fixes related to overriding the first event in recurrences.
* Added: VCalendar::getBaseComponent to find the 'master' component in a
  calendar.
* #51: Support for iterating RDATE properties.
* Fixed: Issue #101: RecurrenceIterator::nextMonthly() shows events that are
  excluded events with wrong time


3.2.4 (2014-07-14)
------------------

* Added: Issue #98. The VCardConverter now takes `X-APPLE-OMIT-YEAR` into
  consideration when converting between vCard 3 and 4.
* Fixed: Issue #96. Some support for Yahoo's broken vcards.
* Fixed: PHP 5.3 support was broken in the cli tool.


3.2.3 (2014-06-12)
------------------

* Validator now checks if DUE and DTSTART are of the same type in VTODO, and
  ensures that DUE is always after DTSTART.
* Removed documentation from source repository, to http://sabre.io/vobject/
* Expanded the vobject cli tool validation output to make it easier to find
  issues.
* Fixed: vobject repair. It was not working for iCalendar objects.


3.2.2 (2014-05-07)
------------------

* Minor tweak in unittests to make it run on PHP 5.5.12. Json-prettifying
  slightly changed which caused the test to fail.


3.2.1 (2014-05-03)
------------------

* Minor tweak to make the unittests run with the latest hhvm on travis.
* Updated timezone definitions.
* Updated copyright links to point to http://sabre.io/


3.2.0 (2014-04-02)
------------------

* Now hhvm compatible!
* The validator can now detect a _lot_ more problems. Many rules for both
  iCalendar and vCard were added.
* Added: bin/generate_vcards, a utility to generate random vcards for testing
  purposes. Patches are welcome to add more data.
* Updated: Windows timezone mapping to latest version from unicode.org
* Changed: The timezone maps are now loaded in from external files, in
  lib/Sabre/VObject/timezonedata.
* Added: Fixing badly encoded URL's from google contacts vcards.
* Fixed: Issue #68. Couldn't decode properties ending in a colon.
* Fixed: Issue #72. RecurrenceIterator should respect timezone in the UNTIL
  clause.
* Fixed: Issue #67. BYMONTH limit on DAILY recurrences.
* Fixed: Issue #26. Return a more descriptive error when coming across broken
  BYDAY rules.
* Fixed: Issue #28. Incorrect timezone detection for some timezones.
* Fixed: Issue #70. Casting a parameter with a null value to string would fail.
* Added: Support for rfc6715 and rfc6474.
* Added: Support for DateTime objects in the VCard DATE-AND-OR-TIME property.
* Added: UUIDUtil, for easily creating unique identifiers.
* Fixed: Issue #83. Creating new VALUE=DATE objects using php's DateTime.
* Fixed: Issue #86. Don't go into an infinite loop when php errors are
  disabled and an invalid file is read.


3.1.4 (2014-03-30)
------------------

* Fixed: Issue #87: Several compatibility fixes related to timezone handling
  changes in PHP 5.5.10.


3.1.3 (2013-10-02)
------------------

* Fixed: Support from properties from draft-daboo-valarm-extensions-04. Issue
  #56.
* Fixed: Issue #54. Parsing a stream of multiple vcards separated by more than
  one newline. Thanks @Vedmak for the patch.
* Fixed: Serializing vcard 2.1 parameters with no name caused a literal '1' to
  be inserted.
* Added: VCardConverter removed properties that are no longer supported in vCard
  4.0.
* Added: vCards with a minimum number of values (such as N), but don't have that
  many, are now automatically padded with empty components.
* Added: The vCard validator now also checks for a minimum number of components,
  and has the ability to repair these.
* Added: Some support for vCard 2.1 in the VCard converter, to upgrade to vCard
  3.0 or 4.0.
* Fixed: Issue 60 Use Document::$componentMap when instantiating the top-level
  VCalendar and VCard components.
* Fixed: Issue 62: Parsing iCalendar parameters with no value.
* Added: --forgiving option to vobject utility.
* Fixed: Compound properties such as ADR were not correctly split up in vCard
  2.1 quoted printable-encoded properties.
* Fixed: Issue 64: Encoding of binary properties of converted vCards. Thanks
  @DominikTo for the patch.


3.1.2 (2013-08-13)
------------------

* Fixed: Setting correct property group on VCard conversion


3.1.1 (2013-08-02)
------------------

* Fixed: Issue #53. A regression in RecurrenceIterator.


3.1.0 (2013-07-27)
------------------

* Added: bad-ass new cli debugging utility (in bin/vobject).
* Added: jCal and jCard parser.
* Fixed: URI properties should not escape ; and ,.
* Fixed: VCard 4 documents now correctly use URI as a default value-type for
  PHOTO and others. BINARY no longer exists in vCard 4.
* Added: Utility to convert between 2.1, 3.0 and 4.0 vCards.
* Added: You can now add() multiple parameters to a property in one call.
* Added: Parameter::has() for easily checking if a parameter value exists.
* Added: VCard::preferred() to find a preferred email, phone number, etc for a
  contact.
* Changed: All $duration properties are now public.
* Added: A few validators for iCalendar documents.
* Fixed: Issue #50. RecurrenceIterator gives incorrect result when exception
  events are out of order in the iCalendar file.
* Fixed: Issue #48. Overridden events in the recurrence iterator that were past
  the UNTIL date were ignored.
* Added: getDuration for DURATION values such as TRIGGER. Thanks to
  @SimonSimCity.
* Fixed: Issue #52. vCard 2.1 parameters with no name may lose values if there's
  more than 1. Thanks to @Vedmak.


3.0.0 (2013-06-21)
------------------

* Fixed: includes.php file was still broken. Our tool to generate it had some
  bugs.


3.0.0-beta4 (2013-06-21)
------------------------

* Fixed: includes.php was no longer up to date.


3.0.0-beta3 (2013-06-17)
------------------------

* Added: OPTION_FORGIVING now also allows slashes in property names.
* Fixed: DateTimeParser no longer fails on dates with years < 1000 & > 4999
* Fixed: Issue 36: Workaround for the recurrenceiterator and caldav events with
  a missing base event.
* Fixed: jCard encoding of TIME properties.
* Fixed: jCal encoding of REQUEST-STATUS, GEO and PERIOD values.


3.0.0-beta2 (2013-06-10)
------------------------

* Fixed: Corrected includes.php file.
* Fixed: vCard date-time parser supported extended-format dates as well.
* Changed: Properties have been moved to an ICalendar or VCard directory.
* Fixed: Couldn't parse vCard 3 extended format dates and times.
* Fixed: Couldn't export jCard DATE values correctly.
* Fixed: Recursive loop in ICalendar\DateTime property.


3.0.0-beta1 (2013-06-07)
------------------------

* Added: jsonSerialize() for creating jCal and jCard documents.
* Added: helper method to parse vCard dates and times.
* Added: Specialized classes for FLOAT, LANGUAGE-TAG, TIME, TIMESTAMP,
  DATE-AND-OR-TIME, CAL-ADDRESS, UNKNOWN and UTC-OFFSET properties.
* Removed: CommaSeparatedText property. Now included into Text.
* Fixed: Multiple parameters with the same name are now correctly encoded.
* Fixed: Parameter values containing a comma are now enclosed in double-quotes.
* Fixed: Iterating parameter values should now fully work as expected.
* Fixed: Support for vCard 2.1 nameless parameters.
* Changed: $valueMap, $componentMap and $propertyMap now all use fully-qualified
  class names, so they are actually overridable.
* Fixed: Updating DATE-TIME to DATE values now behaves like expected.


3.0.0-alpha4 (2013-05-31)
-------------------------

* Added: It's now possible to send parser options to the splitter classes.
* Added: A few tweaks to improve component and property creation.


3.0.0-alpha3 (2013-05-13)
-------------------------

* Changed: propertyMap, valueMap and componentMap are now static properties.
* Changed: Component::remove() will throw an exception when trying to a node
  that's not a child of said component.
* Added: Splitter objects are now faster, line numbers are accurately reported
  and use less memory.
* Added: MimeDir parser can now continue parsing with the same stream buffer.
* Fixed: vobjectvalidate.php is operational again.
* Fixed: \r is properly stripped in text values.
* Fixed: QUOTED-PRINTABLE is now correctly encoded as well as encoded, for
  vCards 2.1.
* Fixed: Parser assumes vCard 2.1, if no version was supplied.


3.0.0-alpha2 (2013-05-22)
-------------------------

* Fixed: vCard URL properties were referencing a non-existant class.


3.0.0-alpha1 (2013-05-21)
-------------------------

* Fixed: Now correctly dealing with escaping of properties. This solves the
  problem with double-backslashes where they don't belong.
* Added: Easy support for properties with more than one value, using setParts
  and getParts.
* Added: Support for broken 2.1 vCards produced by microsoft.
* Added: Automatically decoding quoted-printable values.
* Added: Automatically decoding base64 values.
* Added: Decoding RFC6868 parameter values (uses ^ as an escape character).
* Added: Fancy new MimeDir parser that can also parse streams.
* Added: Automatically mapping many, many properties to a property-class with
  specialized API's.
* Added: remove() method for easily removing properties and sub-components
  components.
* Changed: Components, Properties and Parameters can no longer be created with
  Component::create, Property::create and Parameter::create. They must instead
  be created through the root component. (A VCalendar or VCard object).
* Changed: API for DateTime properties has slightly changed.
* Changed: the ->value property is now protected everywhere. Use getParts() and
  getValue() instead.
* BC Break: No support for mac newlines (\r). Never came across these anyway.
* Added: add() method to the Property class.
* Added: It's now possible to easy set multi-value properties as arrays.
* Added: When setting date-time properties you can just pass PHP's DateTime
  object.
* Added: New components automatically get a bunch of default properties, such as
  VERSION and CALSCALE.
* Added: You can add new sub-components much quicker with the magic setters, and
  add() method.


2.1.7 (2015-01-21)
------------------

* Fixed: Issue #94, a workaround for bad escaping of ; and , in compound
  properties. It's not a full solution, but it's an improvement for those
  stuck in the 2.1 versions.


2.1.6 (2014-12-10)
------------------

* Fixed: Minor change to make sure that unittests succeed on every PHP version.


2.1.5 (2014-06-03)
------------------

* Fixed: #94: Better parameter escaping.
* Changed: Documentation cleanups.


2.1.4 (2014-03-30)
------------------

* Fixed: Issue #87: Several compatibility fixes related to timezone handling
  changes in PHP 5.5.10.


2.1.3 (2013-10-02)
------------------

* Fixed: Issue #55. \r must be stripped from property values.
* Fixed: Issue #65. Putting quotes around parameter values that contain a colon.


2.1.2 (2013-08-02)
------------------

* Fixed: Issue #53. A regression in RecurrenceIterator.


2.1.1 (2013-07-27)
------------------

* Fixed: Issue #50. RecurrenceIterator gives incorrect result when exception
  events are out of order in the iCalendar file.
* Fixed: Issue #48. Overridden events in the recurrence iterator that were past
  the UNTIL date were ignored.


2.1.0 (2013-06-17)
------------------

* This version is fully backwards compatible with 2.0.\*. However, it contains a
  few new API's that mimic the VObject 3 API. This allows it to be used a
  'bridge' version. Specifically, this new version exists so SabreDAV 1.7 and
  1.8 can run with both the 2 and 3 versions of this library.
* Added: Property\DateTime::hasTime().
* Added: Property\MultiDateTime::hasTime().
* Added: Property::getValue().
* Added: Document class.
* Added: Document::createComponent and Document::createProperty.
* Added: Parameter::getValue().


2.0.7 (2013-03-05)
------------------

* Fixed: Microsoft re-uses their magic numbers for different timezones,
  specifically id 2 for both Sarajevo and Lisbon). A workaround was added to
  deal with this.


2.0.6 (2013-02-17)
------------------

* Fixed: The reader now properly parses parameters without a value.


2.0.5 (2012-11-05)
------------------

* Fixed: The FreeBusyGenerator is now properly using the factory methods for
  creation of components and properties.


2.0.4 (2012-11-02)
------------------

* Added: Known Lotus Notes / Domino timezone id's.


2.0.3 (2012-10-29)
------------------

* Added: Support for 'GMT+????' format in TZID's.
* Added: Support for formats like SystemV/EST5EDT in TZID's.
* Fixed: RecurrenceIterator now repairs recurrence rules where UNTIL < DTSTART.
* Added: Support for BYHOUR in FREQ=DAILY (@hollodk).
* Added: Support for BYHOUR and BYDAY in FREQ=WEEKLY.


2.0.2 (2012-10-06)
------------------

* Added: includes.php file, to load the entire library in one go.
* Fixed: A problem with determining alarm triggers for TODO's.


2.0.1 (2012-09-22)
------------------

* Removed: Element class. It wasn't used.
* Added: Basic validation and repair methods for broken input data.
* Fixed: RecurrenceIterator could infinitely loop when an INTERVAL of 0 was
  specified.
* Added: A cli script that can validate and automatically repair vcards and
  iCalendar objects.
* Added: A new 'Compound' property, that can automatically split up parts for
  properties such as N, ADR, ORG and CATEGORIES.
* Added: Splitter classes, that can split up large objects (such as exports)
  into individual objects (thanks @DominikTo and @armin-hackmann).
* Added: VFREEBUSY component, which allows easily checking wether timeslots are
  available.
* Added: The Reader class now has a 'FORGIVING' option, which allows it to parse
  properties with incorrect characters in the name (at this time, it just allows
  underscores).
* Added: Also added the 'IGNORE_INVALID_LINES' option, to completely disregard
  any invalid lines.
* Fixed: A bug in Windows timezone-id mappings for times created in Greenlands
  timezone (sorry Greenlanders! I do care!).
* Fixed: DTEND was not generated correctly for VFREEBUSY reports.
* Fixed: Parser is at least 25% faster with real-world data.


2.0.0 (2012-08-08)
------------------

* VObject is now a separate project from SabreDAV. See the SabreDAV changelog
  for version information before 2.0.
* New: VObject library now uses PHP 5.3 namespaces.
* New: It's possible to specify lists of parameters when constructing
  properties.
* New: made it easier to construct the FreeBusyGenerator.

[iTip]: http://tools.ietf.org/html/rfc5546
