<?php
/* Copyright (C) 2006      Roman Ozana			<ozana@omdesign.cz>
 * Copyright (C) 2011	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré	<vincent@de-grandpre.quebec>
 * Copyright (C) 2026		Joachim Kueter			<git-jk@bloxera.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/comm/action/class/ical.class.php
 *       \ingroup    agenda
 *       \brief      File of class to parse ical calendars
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/xcal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';


/**
 *  Class to read/parse ICal calendars
 */
class ICal
{
	/**
	 * @var string	Name of remote HTTP file to read
	 */
	public $file;

	/**
	 * @var string|null  Text in file (null if it could not be read or is not an iCal file)
	 */
	public $file_text;

	/**
	 * @var  array<string,array<'VEVENT'|'VFREEBUSY'|'VTODO',array<int,array<string,string>>>>|array<string,array<int,array<string,array<string,int>>>>|array<string,array<int,array<string,int>>>|array<string,array<int,array<string,string>>>	Array to save iCalendar parse data
	 */
	public $cal;

	/**
	 * @var int Number of Events
	 */
	public $event_count;

	/**
	 * @var int Number of Todos
	 */
	public $todo_count;

	/**
	 * @var int Number of Freebusy
	 */
	public $freebusy_count;

	/**
	 * @var int Number of Journals
	 */
	public $journal_count;

	/**
	 * @var string Help variable save last key (multiline string)
	 */
	public $last_key;

	/**
	 * @var string error message
	 */
	public $error;


	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Read text file, icalender text file
	 *
	 *  @param  string  		$file       File
	 *  @return string|null					Content of remote file read or null if error
	 */
	public function read_file($file)
	{
		// phpcs:enable
		$this->file = $file;
		$file_text = '';

		//$tmpresult = getURLContent($file, 'GET', '', 1, [], ['http', 'https'], 2, 0);	// To test with any URL
		$localip = 0;
		$sslverify = -1;
		if (getDolGlobalString('AGENDA_EXT_CALENDAR_IP_MODE')) {
			$localip = intval(getDolGlobalString('AGENDA_EXT_CALENDAR_IP_MODE'));
		}
		if (getDolGlobalString('AGENDA_EXT_CALENDAR_SSLVERIFY_MODE')) {
			$sslverify = intval(getDolGlobalString('AGENDA_EXT_CALENDAR_SSLVERIFY_MODE'));
		}
		// See documentation of getURLContent function for $localip and $sslverify possible values
		$tmpresult = getURLContent($file, 'GET', '', 1, [], ['http', 'https'], $localip, $sslverify);
		if ($tmpresult['http_code'] != 200) {
			$file_text = null;
			$this->error = 'Error: '.$tmpresult['http_code'].' '.dol_trunc(trim((string) preg_replace('/\s+/', ' ', dol_string_nohtmltag((string) $tmpresult['content']))), 200);
		} else {
			// RFC 5545 requires UTF-8, but some calendars are in another charset (declared by the HTTP header, or not declared)
			$content = $this->convertToUtf8((string) $tmpresult['content'], (string) ($tmpresult['content_type'] ?? ''));
			// Unfold long lines (RFC 5545: a CRLF followed by one space or one tab is a line continuation)
			$file_text = preg_replace("/[\r\n]{1,}[ \t]/", "", $content);
		}
		//var_dump($tmpresult);

		return $file_text; // return all text
	}

	/**
	 * Convert a text to UTF-8. A text that is already valid UTF-8 is returned unchanged.
	 * The source charset is, in this order: the BOM of a UTF-16 file, the charset of the Content-Type (or "charset=xxx"), Windows-1252
	 * (a superset of ISO-8859-1, the most common encoding of old calendars).
	 *
	 * @param 	string 	$text			Text
	 * @param 	string 	$contenttype	Content-Type of the HTTP answer (for example 'text/calendar; charset=ISO-8859-1') or 'charset=xxx'
	 * @return 	string					Text in UTF-8
	 */
	public function convertToUtf8($text, $contenttype = '')
	{
		if (!function_exists('mb_check_encoding')) {
			return $text;
		}
		if (strncmp($text, "\xFF\xFE", 2) == 0 || strncmp($text, "\xFE\xFF", 2) == 0) {
			return mb_convert_encoding(substr($text, 2), 'UTF-8', (strncmp($text, "\xFF\xFE", 2) == 0 ? 'UTF-16LE' : 'UTF-16BE'));
		}
		if (mb_check_encoding($text, 'UTF-8')) {
			return $text;
		}
		$charset = 'Windows-1252';
		$reg = array();
		if (preg_match('/charset="?([A-Za-z0-9_.:-]+)"?/i', $contenttype, $reg)) {
			$found = false;
			foreach (mb_list_encodings() as $encoding) {
				if (strtolower($encoding) == strtolower($reg[1]) && strtoupper($encoding) != 'UTF-8') {
					$charset = $encoding;
					$found = true;
					break;
				}
			}
			if (!$found && function_exists('iconv')) {
				// Charset not known by mbstring (Windows-1250...): we try iconv
				$converted = @iconv($reg[1], 'UTF-8//IGNORE', $text);
				if ($converted !== false) {
					return $converted;
				}
			}
		}
		return mb_convert_encoding($text, 'UTF-8', $charset);
	}

	/**
	 * Return the to do (VTODO) and the journal entries (VJOURNAL) as events, so they can be shown in an agenda.
	 * A to do is an event from its DTSTART (or its due date) to its due date, a journal entry is an event on its date.
	 * A to do or journal entry without date, or cancelled, is not returned. The title starts with a symbol (to do, done, journal).
	 *
	 * @return array<int,array<string,string|int|array<string,int|string>>>
	 */
	public function getTodoAndJournalAsEvents()
	{
		$result = array();
		foreach (array('VTODO', 'VJOURNAL') as $component) {
			if (empty($this->cal[$component]) || !is_array($this->cal[$component])) {
				continue;
			}
			foreach ($this->cal[$component] as $item) {
				if (!is_array($item) || (isset($item['STATUS']) && strtoupper(trim((string) $item['STATUS'])) == 'CANCELLED')) {
					continue;
				}
				$event = $item;
				unset($event['DUE'], $event['DUE;VALUE=DATE']);
				if ($component == 'VTODO') {
					// The end of a to do is its due date. Without start, the start is the due date.
					if (isset($item['DUE;VALUE=DATE'])) {
						$event['DTEND;VALUE=DATE'] = $item['DUE;VALUE=DATE'];
						if (!isset($event['DTSTART']) && !isset($event['DTSTART;VALUE=DATE'])) {
							$event['DTSTART;VALUE=DATE'] = $item['DUE;VALUE=DATE'];
							$event['DTEND;VALUE=DATE'] = gmdate('Ymd', gmmktime(0, 0, 0, (int) substr($item['DUE;VALUE=DATE'], 4, 2), (int) substr($item['DUE;VALUE=DATE'], 6, 2), (int) substr($item['DUE;VALUE=DATE'], 0, 4)) + 86400);
						}
					} elseif (isset($item['DUE'])) {
						$event['DTEND'] = $item['DUE'];
						if (!isset($event['DTSTART']) && !isset($event['DTSTART;VALUE=DATE'])) {
							$event['DTSTART'] = $item['DUE'];
						}
					}
				}
				if (!isset($event['DTSTART']) && !isset($event['DTSTART;VALUE=DATE'])) {
					continue;
				}
				$done = ($component == 'VTODO' && ((isset($item['STATUS']) && strtoupper(trim((string) $item['STATUS'])) == 'COMPLETED') || isset($item['COMPLETED'])));
				$symbol = ($component == 'VJOURNAL' ? "\u{270E} " : ($done ? "\u{2611} " : "\u{2610} "));
				$title = (isset($item['SUMMARY']) && $item['SUMMARY'] !== '') ? (string) $item['SUMMARY'] : trim(strtok((string) ($item['DESCRIPTION'] ?? ''), "\n"));
				$event['SUMMARY'] = $symbol.$title;
				$result[] = $event;
			}
		}
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns the number of calendar events
	 *
	 * @return int
	 */
	public function get_event_count()
	{
		// phpcs:enable
		return $this->event_count;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns the number of to do
	 *
	 * @return int
	 */
	public function get_todo_count()
	{
		// phpcs:enable
		return $this->todo_count;
	}

	/**
	 * Return if a text looks like an iCal file (it starts, after a BOM and blank lines, with BEGIN:VCALENDAR)
	 *
	 * @param	string	$text	Text
	 * @return	bool			True if it looks like an iCal file
	 */
	private function isIcalText($text)
	{
		return (bool) preg_match('/^[\xEF\xBB\xBF\s]*BEGIN:VCALENDAR/i', $text);
	}

	/**
	 * Translate Calendar
	 *
	 * @param	string	 	$uri			Url
	 * @param	string		$usecachefile	Full path of a cache file to use a cache file
	 * @param	int			$delaycache		Delay in seconds for cache (by default 3600 secondes)
	 * @return  string|array<string,array<'VEVENT'|'VFREEBUSY'|'VTODO',array<int,array<string,string>>>>|array<string,array<int,array<string,array<string,int>>>>|array<string,array<int,array<string,int>>>|array<string,array<int,array<string,string>>>
	 */
	public function parse($uri, $usecachefile = '', $delaycache = 3600)
	{
		$this->cal = array(); // new empty array

		$this->event_count = -1;
		$this->file_text = '';

		// Save file into a cache
		if ($usecachefile) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$datefile = dol_filemtime($usecachefile);
			$now = dol_now('gmt');
			//print $datefile.' '.$now.' ...';
			if ($datefile && $datefile > ($now - $delaycache)) {
				// We reuse the cache file
				$this->file_text = file_get_contents($usecachefile);
				if (!$this->isIcalText($this->file_text)) {
					$this->file_text = '';	// Not an iCal file (for example a HTML page cached by an old version), so we download it again
				}
			}
		}

		// read FILE text
		if (empty($this->file_text)) {
			// A failure is remembered a few minutes, to not download again and wait for the timeout at each display of the page
			$failfile = ($usecachefile ? $usecachefile.'.fail' : '');
			if ($failfile && dol_filemtime($failfile) > (dol_now('gmt') - min($delaycache, 300))) {
				$this->error = (string) file_get_contents($failfile);
				$this->file_text = null;
			} else {
				$this->file_text = $this->read_file($uri);
				if (!is_null($this->file_text) && !$this->isIcalText($this->file_text)) {
					// We got something that is not an iCal file (an error page in HTML, a login page...). We do not keep it.
					$this->error = 'Error: the server did not return an iCal file (received: '.dol_trunc(trim((string) preg_replace('/\s+/', ' ', dol_string_nohtmltag($this->file_text))), 100).')';
					$this->file_text = null;
				}
				if ($failfile) {
					if (is_null($this->file_text)) {
						file_put_contents($failfile, $this->error, LOCK_EX);
						dolChmod($failfile);
					} else {
						dol_delete_file($failfile);
					}
				}
			}

			if ($usecachefile && !is_null($this->file_text)) {
				// Save the file content into cache file
				// Write into a temporary file then rename it, so a concurrent reader never gets a partial file
				$tmpcachefile = $usecachefile.'.'.getmypid().'.tmp';
				if (file_put_contents($tmpcachefile, $this->file_text, LOCK_EX) !== false) {
					dolChmod($tmpcachefile);
					rename($tmpcachefile, $usecachefile);
				}
			}
		}

		$file_text_array = preg_split("[\n]", ltrim((string) $this->file_text, "\xEF\xBB\xBF \t\r\n"));	// Ignore a BOM and the blank lines before BEGIN:VCALENDAR

		// is this text vcalendar standard text ? on line 1 is BEGIN:VCALENDAR
		if (!stristr($file_text_array[0], 'BEGIN:VCALENDAR')) {
			return 'error not VCALENDAR';
		}

		$insidealarm = 0;
		$tmpkey = '';
		$tmpvalue = '';
		$type = '';
		foreach ($file_text_array as $text) {
			$text = trim($text); // trim one line
			if (!empty($text)) {
				// get Key and Value VCALENDAR:Begin -> Key = VCALENDAR, Value = begin
				list($key, $value) = $this->retun_key_value($text);
				//var_dump($text.' -> '.$key.' - '.$value);

				switch ($text) { // search special string
					case "BEGIN:VTODO":
						$this->todo_count += 1; // new to do begin
						$type = "VTODO";
						break;

					case "BEGIN:VEVENT":
						$this->event_count +=  1; // new event begin
						$type = "VEVENT";
						break;

					case "BEGIN:VFREEBUSY":
						$this->freebusy_count += 1; // new event begin
						$type = "VFREEBUSY";
						break;

					case "BEGIN:VJOURNAL":
						$this->journal_count += 1; // new journal begin
						$type = "VJOURNAL";
						break;

					case "BEGIN:VCALENDAR": // all other special string
					case "BEGIN:DAYLIGHT":
					case "BEGIN:VTIMEZONE":
					case "BEGIN:STANDARD":
						$type = $value; // save array under value key
						break;

					case "END:VTODO": // end special text - goto VCALENDAR key
					case "END:VEVENT":
					case "END:VFREEBUSY":
					case "END:VJOURNAL":
					case "END:VCALENDAR":
					case "END:DAYLIGHT":
					case "END:VTIMEZONE":
					case "END:STANDARD":
						$type = "VCALENDAR";
						break;

						// Manage VALARM that are inside a VEVENT to avoid fields of VALARM to overwrites fields of VEVENT
					case "BEGIN:VALARM":
						$insidealarm = 1;
						break;
					case "END:VALARM":
						$insidealarm = 0;
						break;

					default: // no special string (SUMMARY, DESCRIPTION, ...)
						if ($tmpvalue) {
							$tmpvalue .= $text;
							if (!preg_match('/=$/', $text)) {	// No more lines
								$key = $tmpkey;
								$value = quotedPrintDecode(preg_replace('/^ENCODING=QUOTED-PRINTABLE:/i', '', $tmpvalue));
								$tmpkey = '';
								$tmpvalue = '';
							}
						} elseif (preg_match('/^ENCODING=QUOTED-PRINTABLE:/i', $value)) {
							if (preg_match('/=$/', $value)) {
								$tmpkey = $key;
								$tmpvalue .= preg_replace('/=$/', "", $value); // We must wait to have next line to have complete message
							} else {
								$value = quotedPrintDecode(preg_replace('/^ENCODING=QUOTED-PRINTABLE:/i', '', $tmpvalue.$value));
							}
						}                    	//$value=quotedPrintDecode($tmpvalue.$value);
						if (!$insidealarm && !$tmpkey) {
							$this->add_to_array($type, $key, $value); // add to array
						}
						break;
				}
			}
		}

		$events = $this->get_event_list();
		if (!empty($events)) {
			// An occurrence of a recurring event that was modified or cancelled is an event with the same UID and a RECURRENCE-ID.
			// We add its day to the excluded days (EXDATE) of the main event, so the original occurrence is not shown.
			// The modified occurrence stays an event of its own.
			$mainkeys = [];
			foreach ($events as $evkey => $event) {
				if (empty($event['RECURRENCE-ID']) && !empty($event['UID'])) {
					$mainkeys[$event['UID']] = $evkey;
				}
			}
			foreach ($events as $event) {
				if (!empty($event['RECURRENCE-ID']) && !empty($event['UID']) && isset($mainkeys[$event['UID']])) {
					$mainkey = $mainkeys[$event['UID']];
					$exdates = (isset($events[$mainkey]['EXDATE']) && is_array($events[$mainkey]['EXDATE'])) ? $events[$mainkey]['EXDATE'] : [];
					$exdates[] = $event['RECURRENCE-ID'];
					$events[$mainkey]['EXDATE'] = $exdates;
				}
			}
			// An event can have a DURATION instead of a DTEND: we define the end from the duration.
			foreach ($events as $evkey => $event) {
				if (empty($event['DURATION']) || !is_string($event['DURATION']) || isset($event['DTEND']) || isset($event['DTEND;VALUE=DATE'])) {
					continue;
				}
				$seconds = $this->ical_duration_to_seconds($event['DURATION']);
				if ($seconds === null) {
					continue;
				}
				if (!empty($event['DTSTART;VALUE=DATE']) && is_string($event['DTSTART;VALUE=DATE'])) {
					// All day event: the end is the day after the last day, so a duration of 1 day is DTEND = next day
					$start = gmmktime(0, 0, 0, (int) substr($event['DTSTART;VALUE=DATE'], 4, 2), (int) substr($event['DTSTART;VALUE=DATE'], 6, 2), (int) substr($event['DTSTART;VALUE=DATE'], 0, 4));
					$events[$evkey]['DTEND;VALUE=DATE'] = gmdate('Ymd', $start + max(1, (int) ceil($seconds / 86400)) * 86400);
				} elseif (isset($event['DTSTART']) && is_array($event['DTSTART']) && isset($event['DTSTART']['unixtime'])) {
					$events[$evkey]['DTEND'] = array('TZID' => ($event['DTSTART']['TZID'] ?? ''), 'unixtime' => (int) $event['DTSTART']['unixtime'] + $seconds);
				} elseif (!empty($event['DTSTART']) && !is_array($event['DTSTART'])) {
					$events[$evkey]['DTEND'] = (int) $event['DTSTART'] + $seconds;
				}
			}
			// Events (or occurrences) with STATUS:CANCELLED are not returned
			foreach ($events as $evkey => $event) {
				if (isset($event['STATUS']) && is_string($event['STATUS']) && strtoupper(trim($event['STATUS'])) == 'CANCELLED') {
					unset($events[$evkey]);
				}
			}
			$this->cal['VEVENT'] = $events;
		}

		//var_dump($this->cal);
		return $this->cal;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Add to $this->ical array one value and key.
	 *
	 * @param 	string 	$type		Type ('VTODO', 'VEVENT', 'VFREEBUSY', 'VCALENDAR'...)
	 * @param 	string 	$key		Key	('DTSTART', ...). Note: Field is never 'DTSTART;TZID=...' because ';...' was before removed and added as another property
	 * @param 	string 	$value		Value
	 * @return	void
	 */
	public function add_to_array($type, $key, $value)
	{
		// phpcs:enable

		//print 'type='.$type.' key='.$key.' value='.$value.'<br>'."\n";

		// RDATE (dates added to a recurrence). Values are kept as written (a PERIOD "start/end" is reduced to its start), accumulated over all the RDATE lines of the event.
		if ($type == 'VEVENT' && !empty($key) && strtoupper(explode(';', $key, 2)[0]) == 'RDATE') {
			$rdates = (isset($this->cal[$type][$this->event_count]['RDATE']) && is_array($this->cal[$type][$this->event_count]['RDATE'])) ? $this->cal[$type][$this->event_count]['RDATE'] : [];
			foreach (explode(',', $value) as $rdate) {
				$rdate = trim(explode('/', $rdate)[0]);
				if (preg_match('/^[0-9]{8}(T[0-9]{6}Z?)?$/', $rdate)) {
					$rdates[] = $rdate;
				}
			}
			$this->cal[$type][$this->event_count]['RDATE'] = $rdates;
			$this->last_key = 'RDATE';
			return;
		}

		// EXDATE (dates excluded from a recurrence). Only the days are kept, accumulated over all the EXDATE lines of the event.
		if ($type == 'VEVENT' && !empty($key) && strtoupper(explode(';', $key, 2)[0]) == 'EXDATE') {
			$exdates = (isset($this->cal[$type][$this->event_count]['EXDATE']) && is_array($this->cal[$type][$this->event_count]['EXDATE'])) ? $this->cal[$type][$this->event_count]['EXDATE'] : [];
			foreach (explode(',', $value) as $exdate) {
				if (preg_match('/^\s*([0-9]{8})/', $exdate, $reg)) {
					$exdates[] = $reg[1];
				}
			}
			$this->cal[$type][$this->event_count]['EXDATE'] = $exdates;
			$this->last_key = 'EXDATE';
			return;
		}

		$iscontinuation = false;
		if (empty($key)) {
			$iscontinuation = true;
			$key = $this->last_key;
			switch ($type) {
				case 'VEVENT':
					// @phan-suppress-next-line PhanTypeMismatchDimFetch
					$value = $this->cal[$type][$this->event_count][$key].$value;
					break;
				case 'VFREEBUSY':
					// @phan-suppress-next-line PhanTypeMismatchDimFetch
					$value = $this->cal[$type][$this->freebusy_count][$key].$value;
					break;
				case 'VTODO':
					// @phan-suppress-next-line PhanTypeMismatchDimFetch
					$value = $this->cal[$type][$this->todo_count][$key].$value;
					break;
				case 'VJOURNAL':
					// @phan-suppress-next-line PhanTypeMismatchDimFetch
					$value = $this->cal[$type][$this->journal_count][$key].$value;
					break;
			}
		}

		if (!$iscontinuation) {
			// Value in a legacy encoding (vCalendar 1.0 style: ENCODING=QUOTED-PRINTABLE and/or CHARSET=ISO-8859-1 as parameters of the property)
			if (strpos($key, ';') !== false && !stristr($key, "DTSTART") && !stristr($key, "DTEND")) {
				if (preg_match('/;ENCODING=(QUOTED-PRINTABLE|Q)(;|$)/i', $key)) {
					$value = quotedPrintDecode($value);
				}
				if ($value !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
					$value = $this->convertToUtf8($value, (preg_match('/;CHARSET=([^;]+)/i', $key, $reg) ? 'charset='.trim($reg[1], '"') : ''));
				}
			}
			// Remove the parameters of a property (SUMMARY;LANGUAGE=fr -> SUMMARY), except for dates that are managed below
			if (strpos($key, ';') !== false && !stristr($key, "DTSTART") && !stristr($key, "DTEND") && !stristr($key, "DUE")) {
				$key = explode(';', $key, 2)[0];
			}
			// Decode escaped characters of TEXT values
			if (in_array($key, ['SUMMARY', 'DESCRIPTION', 'LOCATION', 'COMMENT'])) {
				$value = strtr($value, ['\\\\' => '\\', '\\,' => ',', '\\;' => ';', '\\n' => "\n", '\\N' => "\n"]);
			}
		}

		if (($key == "DTSTAMP") || ($key == "LAST-MODIFIED") || ($key == "CREATED")) {
			$value = $this->ical_date_to_unix($value);
		}
		//if ($key == "RRULE" ) $value = $this->ical_rrule($value);

		if (stristr($key, "DTSTART") || stristr($key, "DTEND") || stristr($key, "DUE") || stristr($key, "DTSTART;VALUE=DATE") || stristr($key, "DTEND;VALUE=DATE")) {
			if (stristr($key, "DTSTART;VALUE=DATE") || stristr($key, "DTEND;VALUE=DATE") || stristr($key, "DUE;VALUE=DATE")) {
				list($key, $value) = array($key, $value);
			} else {
				list($key, $value) = $this->ical_dt_date($key, $value);
			}
		}

		switch ($type) {
			case "VTODO":
				// @phan-suppress-next-line PhanTypeMismatchReturn
				$this->cal[$type][$this->todo_count][$key] = $value;
				break;

			case "VJOURNAL":
				// @phan-suppress-next-line PhanTypeMismatchReturn
				$this->cal[$type][$this->journal_count][$key] = $value;
				break;

			case "VEVENT":
				if (strtoupper(explode(';', $key, 2)[0]) == 'RECURRENCE-ID') {
					// Occurrence of a recurring event that is replaced by this event. We keep only the day (as written in the file).
					$key = 'RECURRENCE-ID';
					if (preg_match('/^\s*([0-9]{8})/', $value, $reg)) {
						$value = $reg[1];
					}
				}
				// @phan-suppress-next-line PhanTypeMismatchReturn
				$this->cal[$type][$this->event_count][$key] = ($key == 'RRULE' ? $this->ical_rrule($value) : $value);	// RRULE of an event is decoded into an array
				break;

			case "VFREEBUSY":
				// @phan-suppress-next-line PhanTypeMismatchReturn
				$this->cal[$type][$this->freebusy_count][$key] = $value;
				break;

			default:
				// @phan-suppress-next-line PhanTypeMismatchProperty
				$this->cal[$type][$key] = $value;
				break;
		}
		$this->last_key = $key;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Parse text "XXXX:value text some with : " and return array($key = "XXXX", $value="value");
	 *
	 * @param 	string 	$text	Text
	 * @return 	array{0:string,1:string}
	 */
	public function retun_key_value($text)
	{
		// phpcs:enable
		/*
		preg_match("/([^:]+)[:]([\w\W]+)/", $text, $matches);

		if (empty($matches))
		{
			return array(false,$text);
		}
		else
		{
			$matches = array_splice($matches, 1, 2);
			return $matches;
		}*/
		return explode(':', $text, 2);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Parse RRULE  return array
	 *
	 * @param 	string 	$value	string
	 * @return 	array<string,string>
	 */
	public function ical_rrule($value)
	{
		// phpcs:enable
		$result = array();
		$rrule = explode(';', $value);
		foreach ($rrule as $line) {
			$rcontent = explode('=', $line, 2);
			if (count($rcontent) == 2) {
				$result[strtoupper($rcontent[0])] = $rcontent[1];
			}
		}
		return $result;
	}

	/**
	 * Decode a BYDAY value ("MO,TU", "2MO", "-1FR"...)
	 *
	 * @param 	string 	$value	Value of BYDAY
	 * @return 	array<int,array{0:int,1:int}>|null	List of array(ordinal (0 if none), day of week (0=Sunday...6=Saturday)), or null if the value is not valid
	 */
	private function parseByDay($value)
	{
		$daysofweek = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);
		$result = array();
		foreach (explode(',', strtoupper($value)) as $item) {
			$reg = array();
			if (!preg_match('/^([+-]?[0-9]{1,2})?(SU|MO|TU|WE|TH|FR|SA)$/', trim($item), $reg)) {
				return null;
			}
			$ordinal = ($reg[1] === '' ? 0 : (int) $reg[1]);
			if (abs($ordinal) > 5) {
				return null;
			}
			$result[] = array($ordinal, $daysofweek[$reg[2]]);
		}
		return $result;
	}

	/**
	 * Return if a recurrence rule can be expanded by expandRRule(): FREQ DAILY, WEEKLY, MONTHLY or YEARLY with, as BYxxx parts, only BYDAY, BYMONTHDAY and BYMONTH.
	 *
	 * @param 	array<string,string>	$rrule	Rule decoded by ical_rrule()
	 * @return 	bool					True if the rule can be expanded
	 */
	public function isRRuleSupported($rrule)
	{
		$freq = ($rrule['FREQ'] ?? '');
		if (!in_array($freq, array('DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'))) {
			return false;
		}
		foreach (array_keys($rrule) as $key) {
			if (strpos($key, 'BY') === 0 && !in_array($key, array('BYDAY', 'BYMONTHDAY', 'BYMONTH'))) {
				return false;
			}
		}
		if (isset($rrule['BYDAY'])) {
			$byday = $this->parseByDay($rrule['BYDAY']);
			if ($byday === null) {
				return false;
			}
			foreach ($byday as $item) {
				if ($item[0] != 0 && !in_array($freq, array('MONTHLY', 'YEARLY'))) {
					return false;	// An ordinal (2MO, -1FR) has a meaning only for a monthly or yearly rule
				}
			}
		}
		foreach (array('BYMONTHDAY' => 31, 'BYMONTH' => 12) as $key => $max) {
			if (isset($rrule[$key])) {
				foreach (explode(',', $rrule[$key]) as $item) {
					if (!preg_match('/^-?[0-9]{1,2}$/', trim($item)) || (int) $item == 0 || abs((int) $item) > $max || ($key == 'BYMONTH' && (int) $item < 0)) {
						return false;
					}
				}
			}
		}
		if (isset($rrule['BYMONTHDAY']) && $freq == 'WEEKLY') {
			return false;
		}
		if ($freq == 'YEARLY' && empty($rrule['BYMONTH']) && (isset($rrule['BYDAY']) || isset($rrule['BYMONTHDAY']))) {
			return false;
		}
		return true;
	}

	/**
	 * Return the days (midnight, as GMT timestamps) of a month that match BYDAY and BYMONTHDAY.
	 *
	 * @param 	int									$year			Year
	 * @param 	int									$month			Month (1-12)
	 * @param 	array<int,array{0:int,1:int}>		$byday			Decoded BYDAY
	 * @param 	array<int,int>						$bymonthday		BYMONTHDAY (negative values count from the end of the month)
	 * @param 	int									$defaultday		Day to use when there is no BYDAY and no BYMONTHDAY (day of the start)
	 * @return 	array<int,int>									Timestamps of the days, sorted
	 */
	private function getDaysOfMonth($year, $month, $byday, $bymonthday, $defaultday)
	{
		$nbdays = (int) gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));
		$fromdayofmonth = array();
		foreach ($bymonthday as $monthday) {
			$day = ($monthday > 0 ? $monthday : $nbdays + $monthday + 1);
			if ($day >= 1 && $day <= $nbdays) {
				$fromdayofmonth[$day] = $day;
			}
		}
		$fromdayofweek = array();
		foreach ($byday as $item) {
			$matches = array();
			for ($day = 1; $day <= $nbdays; $day++) {
				if ((int) gmdate('w', gmmktime(0, 0, 0, $month, $day, $year)) == $item[1]) {
					$matches[] = $day;
				}
			}
			if ($item[0] == 0) {
				foreach ($matches as $day) {
					$fromdayofweek[$day] = $day;
				}
			} else {
				$index = ($item[0] > 0 ? $item[0] - 1 : count($matches) + $item[0]);
				if (isset($matches[$index])) {
					$fromdayofweek[$matches[$index]] = $matches[$index];
				}
			}
		}
		if (!empty($bymonthday) && !empty($byday)) {
			$days = array_intersect($fromdayofmonth, $fromdayofweek);
		} elseif (!empty($bymonthday)) {
			$days = $fromdayofmonth;
		} elseif (!empty($byday)) {
			$days = $fromdayofweek;
		} else {
			$days = ($defaultday <= $nbdays ? array($defaultday => $defaultday) : array());
		}
		sort($days);
		$result = array();
		foreach ($days as $day) {
			$result[] = gmmktime(0, 0, 0, $month, $day, $year);
		}
		return $result;
	}

	/**
	 * Return the starts of the occurrences of a recurrence rule with BYDAY, BYMONTHDAY or BYMONTH (see isRRuleSupported()).
	 * The dates are handled as dates written in the file (GMT timestamps of the local date and time), the caller converts them.
	 *
	 * @param 	array<string,string>	$rrule		Rule decoded by ical_rrule()
	 * @param 	int						$dtstart	Start of the first occurrence
	 * @param 	int						$to			Occurrences starting from this date are not returned
	 * @param 	int						$until		Occurrences starting after this date are not returned (0 = no limit)
	 * @return 	array<int,int>						Start of the occurrences, in ascending order, from $dtstart
	 */
	public function expandRRule($rrule, $dtstart, $to, $until = 0)
	{
		$freq = ($rrule['FREQ'] ?? '');
		$interval = max(1, (int) ($rrule['INTERVAL'] ?? 1));
		$maxcount = (int) ($rrule['COUNT'] ?? 0);
		$byday = (isset($rrule['BYDAY']) ? (array) $this->parseByDay($rrule['BYDAY']) : array());
		$bymonthday = (isset($rrule['BYMONTHDAY']) ? array_map('intval', explode(',', $rrule['BYMONTHDAY'])) : array());
		$bymonth = (isset($rrule['BYMONTH']) ? array_map('intval', explode(',', $rrule['BYMONTH'])) : array());

		$timeofday = (($dtstart % 86400) + 86400) % 86400;
		$firstday = $dtstart - $timeofday;
		$year0 = (int) gmdate('Y', $firstday);
		$month0 = (int) gmdate('n', $firstday);
		$day0 = (int) gmdate('j', $firstday);
		$dayofweek0 = (int) gmdate('w', $firstday);

		$result = array();
		$count = 0;
		for ($i = 0; $i < 20000; $i++) {
			$days = array();
			if ($freq == 'DAILY') {
				$days[] = $firstday + $i * $interval * 86400;
				$periodstart = $days[0];
			} elseif ($freq == 'WEEKLY') {
				// Weeks start on monday
				$periodstart = $firstday - ((($dayofweek0 + 6) % 7) * 86400) + $i * $interval * 7 * 86400;
				$daysofweek = array();
				foreach ($byday as $item) {
					$daysofweek[] = $item[1];
				}
				if (empty($daysofweek)) {
					$daysofweek[] = $dayofweek0;
				}
				foreach ($daysofweek as $dayofweek) {
					$days[] = $periodstart + (($dayofweek + 6) % 7) * 86400;
				}
				sort($days);
			} elseif ($freq == 'MONTHLY') {
				$monthindex = $month0 - 1 + $i * $interval;
				$year = $year0 + intdiv($monthindex, 12);
				$month = $monthindex % 12 + 1;
				$periodstart = gmmktime(0, 0, 0, $month, 1, $year);
				if (empty($bymonth) || in_array($month, $bymonth)) {
					$days = $this->getDaysOfMonth($year, $month, $byday, $bymonthday, $day0);
				}
			} else {
				$year = $year0 + $i * $interval;
				$periodstart = gmmktime(0, 0, 0, 1, 1, $year);
				$months = (empty($bymonth) ? array($month0) : $bymonth);
				sort($months);
				foreach ($months as $month) {
					$days = array_merge($days, $this->getDaysOfMonth($year, $month, $byday, $bymonthday, $day0));
				}
			}
			if ($periodstart >= $to) {
				break;
			}

			foreach ($days as $day) {
				if ($day < $firstday) {
					continue;
				}
				if ($freq == 'DAILY' || $freq == 'WEEKLY') {
					// BYMONTH, and for a daily rule BYMONTHDAY and BYDAY, only filter the days
					if (!empty($bymonth) && !in_array((int) gmdate('n', $day), $bymonth)) {
						continue;
					}
					if ($freq == 'DAILY' && !empty($bymonthday)) {
						$nbdays = (int) gmdate('t', $day);
						$dayofmonth = (int) gmdate('j', $day);
						if (!in_array($dayofmonth, $bymonthday) && !in_array($dayofmonth - $nbdays - 1, $bymonthday)) {
							continue;
						}
					}
					if ($freq == 'DAILY' && !empty($byday)) {
						$found = false;
						foreach ($byday as $item) {
							if ($item[1] == (int) gmdate('w', $day)) {
								$found = true;
							}
						}
						if (!$found) {
							continue;
						}
					}
				}
				$start = $day + $timeofday;
				if ($start >= $to || ($until && $start > $until)) {
					return $result;
				}
				$result[] = $start;
				$count++;
				if ($maxcount && $count >= $maxcount) {
					return $result;
				}
			}
		}
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the number of seconds of an iCal duration (RFC 5545: P1W, P1D, PT1H30M, -PT15M, P1DT2H...)
	 *
	 * @param 	string		$duration	Duration
	 * @return 	int|null				Number of seconds, or null if the value is not a valid duration
	 */
	public function ical_duration_to_seconds($duration)
	{
		// phpcs:enable
		$reg = array();
		if (!preg_match('/^([+-])?P(?:([0-9]+)W)?(?:([0-9]+)D)?(?:T(?:([0-9]+)H)?(?:([0-9]+)M)?(?:([0-9]+)S)?)?$/', strtoupper(trim($duration)), $reg) || $reg[0] == 'P' || substr($reg[0], -1) == 'T') {
			return null;
		}
		$seconds = ((int) ($reg[2] ?? 0)) * 7 * 86400 + ((int) ($reg[3] ?? 0)) * 86400 + ((int) ($reg[4] ?? 0)) * 3600 + ((int) ($reg[5] ?? 0)) * 60 + ((int) ($reg[6] ?? 0));
		return (($reg[1] ?? '') == '-' ? -$seconds : $seconds);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return Unix time from ical date time format (YYYYMMDD[T]HHMMSS[Z] or YYYYMMDD[T]HHMMSS)
	 *
	 * @param 	string		$ical_date		String date
	 * @param 	bool		$floating		True if the date is a "floating" time (no Z and no TZID), so a local time of the user and not a GMT time
	 * @return 	int
	 */
	public function ical_date_to_unix($ical_date, $floating = false)
	{
		// phpcs:enable
		$ical_date = str_replace('T', '', $ical_date);
		$ical_date = str_replace('Z', '', $ical_date);

		$ntime = 0;
		// TIME LIMITED EVENT
		$date = array();
		if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $ical_date, $date)) {
			$ntime = dol_mktime((int) $date[4], (int) $date[5], (int) $date[6], (int) $date[2], (int) $date[3], (int) $date[1], ($floating ? 'tzuserrel' : true));
		}

		//if (empty($date[4])) print 'Error bad date: '.$ical_date.' - date1='.$date[1];
		//print dol_print_date($ntime,'dayhour');exit;
		return $ntime; // ntime is a GTM time
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return unix date from iCal date format
	 *
	 * @param 	string 		$key			Key. Example: 'DTSTART', 'DTSTART;TZID=US-Eastern'
	 * @param 	string 		$value			Value. Example: '19970714T133000', '19970714T173000Z', '19970714T133000'
	 * @return 	array{0:string,1:int}|array{0:string,1:array<string,int|string>}
	 */
	public function ical_dt_date($key, $value)
	{
		// phpcs:enable
		$return_value = array();

		// Analyse TZID
		$temp = explode(";", $key);

		if (empty($temp[1])) { // not TZID in key
			// A date time without Z (like 19970714T133000) is a "floating" time (RFC 5545): it is a local time, not a GMT time
			$floating = (strpos($value, 'T') !== false && strtoupper(substr(trim($value), -1)) != 'Z');
			$value = $this->ical_date_to_unix($value, $floating);
			return array($key, $value);
		}

		$value = str_replace('T', '', $value);

		$key = $temp[0];
		$temp = explode("=", $temp[1]);
		$return_value[$temp[0]] = trim($temp[1], '"');	// RFC 5545 allows quoted parameter values (TZID="Europe/Berlin")
		$return_value['unixtime'] = $this->ical_date_to_unix($value);	// Local time as GMT timestamp; TZ correction is done by caller (AGENDA_EXT_BUGGEDFILEx)

		return array($key, $return_value);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return sorted eventlist as array or false if calendar is empty
	 *
	 * @return array<int,array<string,string|array<int|string,string>>>|false
	 */
	public function get_sort_event_list()
	{
		// phpcs:enable
		$temp = $this->get_event_list();
		if (!empty($temp)) {
			usort($temp, array($this, "ical_dtstart_compare"));  // false-positive @phpstan-ignore-line
			return $temp;
		} else {
			return false;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Compare two unix timestamp
	 *
	 * @param 	array{DTSTART:array{unixtime:string}} 	$a		Operand a
	 * @param 	array{DTSTART:array{unixtime:string}}  	$b		Operand b
	 * @return 	int
	 */
	public function ical_dtstart_compare($a, $b)
	{
		// phpcs:enable
		return strnatcasecmp($a['DTSTART']['unixtime'], $b['DTSTART']['unixtime']);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return eventlist array (not sorted eventlist array)
	 *
	 * @return array<int,array<string,string|array<int|string,string>>>
	 */
	public function get_event_list()
	{
		// phpcs:enable
		return (empty($this->cal['VEVENT']) ? array() : $this->cal['VEVENT']);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return freebusy array (not sort eventlist array)
	 *
	 * @return array<int,array<string,string|array<int|string,string>>>
	 */
	public function get_freebusy_list()
	{
		// phpcs:enable
		return (empty($this->cal['VFREEBUSY']) ? array() : $this->cal['VFREEBUSY']);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return to do array (not sorted todo array)
	 *
	 * @return array<int,array<string,string|array<int|string,string>>>
	 */
	public function get_todo_list()
	{
		// phpcs:enable
		return $this->cal['VTODO'];
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return base calendar data
	 *
	 * @return array<int,array<string,string|array<int|string,string>>>
	 */
	public function get_calender_data()
	{
		// phpcs:enable
		return $this->cal['VCALENDAR'];
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return array with all data
	 *
	 * @return  array<string,array<'VEVENT'|'VFREEBUSY'|'VTODO',array<int,array<string,string>>>>|array<string,array<int,array<string,array<string,int>>>>|array<string,array<int,array<string,int>>>|array<string,array<int,array<string,string>>>
	 */
	public function get_all_data()
	{
		// phpcs:enable
		return $this->cal;
	}
}
