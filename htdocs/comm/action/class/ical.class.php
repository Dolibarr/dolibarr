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
	 * @var array<string,array{location:string,changes:array<int,array{start:int,before:int,after:int,rrule:array<string,string>,rdates:array<int,int>}>}>|null	Time zones (VTIMEZONE) declared in the file, by TZID. Null until they are read.
	 */
	private $vtimezones = null;

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

		$stalecachetext = '';	// Content of an expired cache file, used if the download fails
		$datefile = 0;

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
			} elseif ($datefile) {
				// The cache is too old: we download it again, but we keep the old content to use it if the download fails
				$oldtext = (string) file_get_contents($usecachefile);
				if ($this->isIcalText($oldtext)) {
					$stalecachetext = $oldtext;
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

		if (is_null($this->file_text) && $stalecachetext !== '') {
			// The download failed: we use the last copy we have (the cache file is not rewritten, so we will try again later)
			$this->file_text = $stalecachetext;
			$this->error .= ' (the copy downloaded on '.dol_print_date((int) $datefile, 'dayhour', 'gmt').' UTC is used)';
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
			// The same instance of an event written several times (same UID and same start, or same UID and RECURRENCE-ID): we keep the most recent version
			// (SEQUENCE, then LAST-MODIFIED or DTSTAMP, then the last one of the file). Events with the same UID but another start are other events:
			// some exporters reuse the same UID for the events of several years.
			$keptevents = [];
			foreach ($events as $evkey => $event) {
				if (empty($event['UID'])) {
					continue;
				}
				$eventstart = ($event['DTSTART'] ?? ($event['DTSTART;VALUE=DATE'] ?? ''));
				// A modified occurrence is identified by its UID and its RECURRENCE-ID (its start can differ), another event by its UID and its start
				$duplicatekey = $event['UID'].'|'.(empty($event['RECURRENCE-ID']) ? json_encode($eventstart) : 'RECURRENCE-ID='.$event['RECURRENCE-ID']);
				$rank = [(int) ($event['SEQUENCE'] ?? 0), (int) ($event['LAST-MODIFIED'] ?? ($event['DTSTAMP'] ?? 0))];
				if (isset($keptevents[$duplicatekey])) {
					if ($rank >= $keptevents[$duplicatekey]['rank']) {
						unset($events[$keptevents[$duplicatekey]['key']]);
						$keptevents[$duplicatekey] = ['key' => $evkey, 'rank' => $rank];
					} else {
						unset($events[$evkey]);
					}
				} else {
					$keptevents[$duplicatekey] = ['key' => $evkey, 'rank' => $rank];
				}
			}
			// An occurrence of a recurring event that was modified or cancelled is an event with the same UID and a RECURRENCE-ID.
			// We add its day to the excluded days (EXDATE) of the main event, so the original occurrence is not shown.
			// The modified occurrence stays an event of its own.
			$mainkeys = [];
			foreach ($events as $evkey => $event) {
				if (empty($event['RECURRENCE-ID']) && !empty($event['UID'])) {
					$mainkeys[$event['UID']] = $evkey;
				}
			}
			foreach ($events as $evkeyoverride => $event) {
				if (!empty($event['RECURRENCE-ID']) && !empty($event['UID']) && isset($mainkeys[$event['UID']])) {
					$mainkey = $mainkeys[$event['UID']];
					if (($event['X-RECURRENCE-RANGE'] ?? '') == 'THISANDFUTURE' && !empty($events[$mainkey]['RRULE']) && is_array($events[$mainkey]['RRULE'])) {
						// This occurrence and the following ones are modified: the series of the main event stops the day before, and the modified
						// event continues the series (same rule, with its own start and properties).
						$rule = $events[$mainkey]['RRULE'];
						$daystamp = gmmktime(0, 0, 0, (int) substr($event['RECURRENCE-ID'], 4, 2), (int) substr($event['RECURRENCE-ID'], 6, 2), (int) substr($event['RECURRENCE-ID'], 0, 4));
						$newrule = $rule;
						unset($newrule['UNTIL']);
						if (!empty($rule['COUNT'])) {
							// Remaining number of occurrences for the modified event
							$mainstart = $this->getWallStart($events[$mainkey]);
							unset($newrule['COUNT']);
							if ($mainstart !== null && $this->isRRuleSupported($rule)) {
								$remaining = (int) $rule['COUNT'] - count($this->expandRRule($rule, $mainstart, $daystamp));
								if ($remaining > 0) {
									$newrule['COUNT'] = (string) $remaining;
								}
							}
						}
						if (!empty($rule['UNTIL'])) {
							$newrule['UNTIL'] = $rule['UNTIL'];
						}
						unset($events[$mainkey]['RRULE']['COUNT']);
						$events[$mainkey]['RRULE']['UNTIL'] = gmdate('Ymd', $daystamp - 86400).'T235959Z';
						if (empty($events[$evkeyoverride]['RRULE'])) {
							$events[$evkeyoverride]['RRULE'] = $newrule;
						}
						unset($events[$evkeyoverride]['RECURRENCE-ID'], $events[$evkeyoverride]['X-RECURRENCE-RANGE']);
						continue;
					}
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

		if (!$iscontinuation && $type == 'VEVENT' && preg_match('/^(ATTENDEE|ORGANIZER)(;|$)/i', $key, $reg)) {
			// Organizer or attendee: "Name <email>" (the name is the parameter CN, the value is mailto:email). There can be several attendees.
			$name = (preg_match('/;CN=("[^"]*"|[^;]*)/i', $key, $regcn) ? trim($regcn[1], '"') : '');
			$email = (string) preg_replace('/^mailto:/i', '', trim($value));
			$person = (($name !== '' && $name != $email) ? $name.' <'.$email.'>' : $email);
			if (strtoupper($reg[1]) == 'ATTENDEE') {
				$attendees = (isset($this->cal[$type][$this->event_count]['ATTENDEE']) && is_array($this->cal[$type][$this->event_count]['ATTENDEE'])) ? $this->cal[$type][$this->event_count]['ATTENDEE'] : [];
				$attendees[] = $person;
				$this->cal[$type][$this->event_count]['ATTENDEE'] = $attendees;
				$this->last_key = 'ATTENDEE';
				return;
			}
			$key = 'ORGANIZER';
			$value = $person;
		}

		if (!$iscontinuation) {
			// RECURRENCE-ID;RANGE=THISANDFUTURE: this occurrence and all the following ones are modified (the parameter is removed with the other ones below)
			if ($type == 'VEVENT' && preg_match('/^RECURRENCE-ID;.*RANGE=THISANDFUTURE/i', $key)) {
				$this->cal[$type][$this->event_count]['X-RECURRENCE-RANGE'] = 'THISANDFUTURE';
			}
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
			if (in_array($key, ['SUMMARY', 'DESCRIPTION', 'LOCATION', 'COMMENT', 'CATEGORIES'])) {
				$value = strtr($value, ['\\\\' => '\\', '\\,' => ',', '\\;' => ';', '\\n' => "\n", '\\N' => "\n"]);
			}
		}

		if (($key == "DTSTAMP") || ($key == "LAST-MODIFIED") || ($key == "CREATED")) {
			$value = $this->ical_date_to_unix($value);
		}
		//if ($key == "RRULE" ) $value = $this->ical_rrule($value);

		if (preg_match('/^(DTSTART|DTEND|DUE)(;|$)/i', $key, $reg)) {
			$params = $this->getKeyParams($key);
			if (isset($params['VALUE']) && strtoupper($params['VALUE']) == 'DATE') {
				// All day date. The key is always 'DTSTART;VALUE=DATE' or 'DTEND;VALUE=DATE'
				$key = strtoupper($reg[1]).';VALUE=DATE';
			} else {
				// Date and time (the parameter VALUE=DATE-TIME, if any, is the default type and is ignored)
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
	 * Return if a recurrence rule can be expanded by expandRRule(): FREQ MINUTELY, HOURLY, DAILY, WEEKLY, MONTHLY or YEARLY with, as parts,
	 * BYDAY, BYMONTHDAY, BYMONTH, BYYEARDAY, BYWEEKNO, BYSETPOS, BYHOUR, BYMINUTE, BYSECOND, WKST (some of them only for some FREQ).
	 *
	 * @param 	array<string,string>	$rrule	Rule decoded by ical_rrule()
	 * @return 	bool					True if the rule can be expanded
	 */
	public function isRRuleSupported($rrule)
	{
		$freq = ($rrule['FREQ'] ?? '');
		if (!in_array($freq, array('MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'))) {
			return false;
		}
		$byparts = array();
		foreach (array_keys($rrule) as $key) {
			if (strpos($key, 'BY') === 0) {
				if (!in_array($key, array('BYDAY', 'BYMONTHDAY', 'BYMONTH', 'BYYEARDAY', 'BYWEEKNO', 'BYSETPOS', 'BYHOUR', 'BYMINUTE', 'BYSECOND'))) {
					return false;
				}
				$byparts[$key] = $key;
			}
		}
		if (isset($rrule['WKST']) && !in_array(strtoupper($rrule['WKST']), array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'))) {
			return false;
		}
		$isshort = in_array($freq, array('MINUTELY', 'HOURLY'));

		if (isset($rrule['BYDAY'])) {
			$byday = $this->parseByDay($rrule['BYDAY']);
			if ($byday === null) {
				return false;
			}
			foreach ($byday as $item) {
				if ($item[0] != 0 && !in_array($freq, array('MONTHLY', 'YEARLY'))) {
					return false;	// An ordinal (2MO, -1FR) has a meaning only for a monthly or yearly rule
				}
				if ($item[0] != 0 && $freq == 'YEARLY' && empty($rrule['BYMONTH'])) {
					return false;
				}
			}
		}
		$limits = array('BYMONTHDAY' => array(31, false), 'BYMONTH' => array(12, true), 'BYYEARDAY' => array(366, false), 'BYWEEKNO' => array(53, false), 'BYSETPOS' => array(366, false), 'BYHOUR' => array(23, true), 'BYMINUTE' => array(59, true), 'BYSECOND' => array(59, true));
		foreach ($limits as $key => $limit) {
			if (isset($rrule[$key])) {
				foreach (explode(',', $rrule[$key]) as $item) {
					$value = (int) $item;
					if (!preg_match('/^-?[0-9]{1,3}$/', trim($item)) || abs($value) > $limit[0] || ($limit[1] && $value < 0) || (!in_array($key, array('BYHOUR', 'BYMINUTE', 'BYSECOND')) && $value == 0)) {
						return false;
					}
				}
			}
		}
		if (isset($byparts['BYSETPOS']) && count($byparts) < 2) {
			return false;	// BYSETPOS selects among the occurrences made by the other BYxxx parts
		}
		if ($isshort && (isset($byparts['BYMINUTE']) || isset($byparts['BYSECOND']))) {
			return false;
		}
		if ($isshort && (isset($byparts['BYYEARDAY']) || isset($byparts['BYWEEKNO']) || isset($byparts['BYSETPOS']))) {
			return false;
		}
		if (isset($byparts['BYWEEKNO']) && ($freq != 'YEARLY' || isset($byparts['BYMONTH']) || isset($byparts['BYMONTHDAY']) || isset($byparts['BYYEARDAY']))) {
			return false;
		}
		if (isset($byparts['BYYEARDAY']) && ($freq != 'YEARLY' || isset($byparts['BYMONTH']) || isset($byparts['BYMONTHDAY']) || isset($byparts['BYDAY']))) {
			return false;
		}
		if (isset($byparts['BYMONTHDAY']) && $freq == 'WEEKLY') {
			return false;
		}
		if ($freq == 'YEARLY' && empty($rrule['BYMONTH']) && !isset($byparts['BYWEEKNO']) && !isset($byparts['BYYEARDAY']) && (isset($byparts['BYDAY']) || isset($byparts['BYMONTHDAY']))) {
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
	 * Return the start (as GMT timestamp of the date and time written in the file) of the first week of a year.
	 *
	 * @param 	int		$year	Year
	 * @param 	int		$wkst	First day of the week (0=Sunday...6=Saturday)
	 * @return 	int				Timestamp of the first day of the week number 1 (the first week with at least 4 days in the year)
	 */
	private function getFirstWeekOfYear($year, $wkst)
	{
		$jan1 = gmmktime(0, 0, 0, 1, 1, $year);
		$offset = (((int) gmdate('w', $jan1)) - $wkst + 7) % 7;
		$start = $jan1 - $offset * 86400;
		if ((7 - $offset) < 4) {
			$start += 7 * 86400;
		}
		return $start;
	}

	/**
	 * Return the start of an event as GMT timestamp of the date and time written in the file.
	 *
	 * @param 	array<string,mixed>	$event	Event
	 * @return 	int|null					Timestamp, or null if the event has no start
	 */
	private function getWallStart($event)
	{
		if (isset($event['DTSTART;VALUE=DATE']) && is_string($event['DTSTART;VALUE=DATE'])) {
			return gmmktime(0, 0, 0, (int) substr($event['DTSTART;VALUE=DATE'], 4, 2), (int) substr($event['DTSTART;VALUE=DATE'], 6, 2), (int) substr($event['DTSTART;VALUE=DATE'], 0, 4));
		}
		if (isset($event['DTSTART']) && is_array($event['DTSTART']) && isset($event['DTSTART']['unixtime'])) {
			return (int) $event['DTSTART']['unixtime'];
		}
		if (isset($event['DTSTART']) && !is_array($event['DTSTART'])) {
			return (int) $event['DTSTART'];
		}
		return null;
	}

	/**
	 * Return the starts of the occurrences of a recurrence rule (see isRRuleSupported()), the first one being always DTSTART:
	 * RFC 5545 defines DTSTART as the first instance of the recurrence set, even if it does not match the rule (the event
	 * is then still shown at its start date, and it counts for COUNT).
	 * The dates are handled as dates written in the file (GMT timestamps of the local date and time), the caller converts them.
	 *
	 * @param 	array<string,string>	$rrule		Rule decoded by ical_rrule()
	 * @param 	int						$dtstart	Start of the first occurrence
	 * @param 	int						$to			Occurrences starting from this date are not returned
	 * @param 	int						$until		Occurrences starting after this date are not returned (0 = no limit)
	 * @param 	int						$from		Date from which the occurrences are needed: when the rule has no COUNT, the periods before are skipped
	 * @return 	array<int,int>						Start of the occurrences, in ascending order, from $dtstart
	 */
	public function expandRRule($rrule, $dtstart, $to, $until = 0, $from = 0)
	{
		$result = $this->expandRRuleOccurrences($rrule, $dtstart, $to, $until, $from);
		if (!in_array($dtstart, $result) && $dtstart < $to && (!$until || $dtstart <= $until)) {
			$result[] = $dtstart;
			sort($result);
			$maxcount = (int) ($rrule['COUNT'] ?? 0);
			if ($maxcount) {
				$result = array_slice($result, 0, $maxcount);
			}
		}
		return $result;
	}

	/**
	 * Return the starts of the occurrences of a recurrence rule (see isRRuleSupported()).
	 * The dates are handled as dates written in the file (GMT timestamps of the local date and time), the caller converts them.
	 *
	 * @param 	array<string,string>	$rrule		Rule decoded by ical_rrule()
	 * @param 	int						$dtstart	Start of the first occurrence
	 * @param 	int						$to			Occurrences starting from this date are not returned
	 * @param 	int						$until		Occurrences starting after this date are not returned (0 = no limit)
	 * @param 	int						$from		Date from which the occurrences are needed: when the rule has no COUNT, the periods before are skipped
	 * @return 	array<int,int>						Start of the occurrences, in ascending order, from $dtstart
	 */
	private function expandRRuleOccurrences($rrule, $dtstart, $to, $until = 0, $from = 0)
	{
		$daysofweek = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);
		$freq = ($rrule['FREQ'] ?? '');
		$interval = max(1, (int) ($rrule['INTERVAL'] ?? 1));
		$maxcount = (int) ($rrule['COUNT'] ?? 0);
		$wkst = $daysofweek[strtoupper($rrule['WKST'] ?? 'MO')] ?? 1;
		$byday = (isset($rrule['BYDAY']) ? (array) $this->parseByDay($rrule['BYDAY']) : array());
		$bymonthday = (isset($rrule['BYMONTHDAY']) ? array_map('intval', explode(',', $rrule['BYMONTHDAY'])) : array());
		$bymonth = (isset($rrule['BYMONTH']) ? array_map('intval', explode(',', $rrule['BYMONTH'])) : array());
		$byyearday = (isset($rrule['BYYEARDAY']) ? array_map('intval', explode(',', $rrule['BYYEARDAY'])) : array());
		$byweekno = (isset($rrule['BYWEEKNO']) ? array_map('intval', explode(',', $rrule['BYWEEKNO'])) : array());
		$bysetpos = (isset($rrule['BYSETPOS']) ? array_map('intval', explode(',', $rrule['BYSETPOS'])) : array());
		$byhour = (isset($rrule['BYHOUR']) ? array_map('intval', explode(',', $rrule['BYHOUR'])) : array());
		$byminute = (isset($rrule['BYMINUTE']) ? array_map('intval', explode(',', $rrule['BYMINUTE'])) : array());
		$bysecond = (isset($rrule['BYSECOND']) ? array_map('intval', explode(',', $rrule['BYSECOND'])) : array());

		$timeofday = (($dtstart % 86400) + 86400) % 86400;
		$firstday = $dtstart - $timeofday;
		$year0 = (int) gmdate('Y', $firstday);
		$month0 = (int) gmdate('n', $firstday);
		$day0 = (int) gmdate('j', $firstday);
		$dayofweek0 = (int) gmdate('w', $firstday);
		$hour0 = intdiv($timeofday, 3600);
		$minute0 = intdiv($timeofday % 3600, 60);
		$second0 = $timeofday % 60;
		// Times of the day of an occurrence, for the rules that can have several by day (BYHOUR, BYMINUTE, BYSECOND)
		$times = array();
		foreach ((empty($byhour) ? array($hour0) : $byhour) as $hour) {
			foreach ((empty($byminute) ? array($minute0) : $byminute) as $minute) {
				foreach ((empty($bysecond) ? array($second0) : $bysecond) as $second) {
					$times[] = $hour * 3600 + $minute * 60 + $second;
				}
			}
		}
		$times = array_values(array_unique($times));
		sort($times);
		$skip = ($maxcount == 0 && $from > $dtstart);

		$result = array();
		$count = 0;
		for ($i = 0; $i < 20000; $i++) {
			$candidates = array();
			if ($freq == 'MINUTELY' || $freq == 'HOURLY') {
				$step = ($freq == 'HOURLY' ? 3600 : 60) * $interval;
				$index = $i + ($skip ? max(0, intdiv($from - $dtstart, $step) - 1) : 0);
				$time = $dtstart + $index * $step;
				$periodstart = $time;
				$day = $time - ((($time % 86400) + 86400) % 86400);
				$ok = (empty($bymonth) || in_array((int) gmdate('n', $day), $bymonth));
				if ($ok && !empty($bymonthday)) {
					$nbdays = (int) gmdate('t', $day);
					$dayofmonth = (int) gmdate('j', $day);
					$ok = (in_array($dayofmonth, $bymonthday) || in_array($dayofmonth - $nbdays - 1, $bymonthday));
				}
				if ($ok && !empty($byday)) {
					$ok = false;
					foreach ($byday as $item) {
						if ($item[1] == (int) gmdate('w', $day)) {
							$ok = true;
						}
					}
				}
				if ($ok && !empty($byhour)) {
					$ok = in_array((int) gmdate('G', $time), $byhour);
				}
				if ($ok) {
					$candidates[] = $time;
				}
			} else {
				$days = array();
				if ($freq == 'DAILY') {
					$index = $i + ($skip ? max(0, intdiv($from - $firstday, $interval * 86400) - 1) : 0);
					$day = $firstday + $index * $interval * 86400;
					$periodstart = $day;
					$ok = (empty($bymonth) || in_array((int) gmdate('n', $day), $bymonth));
					if ($ok && !empty($bymonthday)) {
						$nbdays = (int) gmdate('t', $day);
						$dayofmonth = (int) gmdate('j', $day);
						$ok = (in_array($dayofmonth, $bymonthday) || in_array($dayofmonth - $nbdays - 1, $bymonthday));
					}
					if ($ok && !empty($byday)) {
						$ok = false;
						foreach ($byday as $item) {
							if ($item[1] == (int) gmdate('w', $day)) {
								$ok = true;
							}
						}
					}
					if ($ok) {
						$days[] = $day;
					}
				} elseif ($freq == 'WEEKLY') {
					$weekstart0 = $firstday - ((($dayofweek0 - $wkst + 7) % 7) * 86400);
					$index = $i + ($skip ? max(0, intdiv($from - $weekstart0, $interval * 7 * 86400) - 1) : 0);
					$periodstart = $weekstart0 + $index * $interval * 7 * 86400;
					$weekdays = array();
					foreach ($byday as $item) {
						$weekdays[] = $item[1];
					}
					if (empty($weekdays)) {
						$weekdays[] = $dayofweek0;
					}
					foreach ($weekdays as $weekday) {
						$day = $periodstart + ((($weekday - $wkst + 7) % 7) * 86400);
						if (empty($bymonth) || in_array((int) gmdate('n', $day), $bymonth)) {
							$days[] = $day;
						}
					}
				} elseif ($freq == 'MONTHLY') {
					$monthindex = $month0 - 1 + $i * $interval;
					if ($skip) {
						$monthsfrom = ((int) gmdate('Y', $from) - $year0) * 12 + ((int) gmdate('n', $from)) - $month0;
						$monthindex += max(0, intdiv($monthsfrom, $interval) - 1) * $interval;
					}
					$year = $year0 + intdiv($monthindex, 12);
					$month = $monthindex % 12 + 1;
					$periodstart = gmmktime(0, 0, 0, $month, 1, $year);
					if (empty($bymonth) || in_array($month, $bymonth)) {
						$days = $this->getDaysOfMonth($year, $month, $byday, $bymonthday, $day0);
					}
				} else {
					$index = $i + ($skip ? max(0, intdiv((int) gmdate('Y', $from) - $year0, $interval) - 1) : 0);
					$year = $year0 + $index * $interval;
					$periodstart = gmmktime(0, 0, 0, 1, 1, $year);
					$nbdaysofyear = (int) gmdate('z', gmmktime(0, 0, 0, 12, 31, $year)) + 1;
					if (!empty($byweekno)) {
						$firstweek = $this->getFirstWeekOfYear($year, $wkst);
						$nbweeks = intdiv($this->getFirstWeekOfYear($year + 1, $wkst) - $firstweek, 7 * 86400);
						$weekdays = array();
						foreach ($byday as $item) {
							$weekdays[] = $item[1];
						}
						if (empty($weekdays)) {
							$weekdays[] = $dayofweek0;
						}
						foreach ($byweekno as $weekno) {
							$number = ($weekno > 0 ? $weekno : $nbweeks + $weekno + 1);
							if ($number < 1 || $number > $nbweeks) {
								continue;
							}
							foreach ($weekdays as $weekday) {
								$days[] = $firstweek + ($number - 1) * 7 * 86400 + ((($weekday - $wkst + 7) % 7) * 86400);
							}
						}
					} elseif (!empty($byyearday)) {
						foreach ($byyearday as $yearday) {
							$number = ($yearday > 0 ? $yearday : $nbdaysofyear + $yearday + 1);
							if ($number >= 1 && $number <= $nbdaysofyear) {
								$days[] = $periodstart + ($number - 1) * 86400;
							}
						}
					} else {
						$months = (empty($bymonth) ? array($month0) : $bymonth);
						sort($months);
						foreach ($months as $month) {
							$days = array_merge($days, $this->getDaysOfMonth($year, $month, $byday, $bymonthday, $day0));
						}
					}
				}
				foreach ($days as $day) {
					foreach ($times as $timevalue) {
						$candidates[] = $day + $timevalue;
					}
				}
			}
			if ($periodstart >= $to) {
				break;
			}

			$candidates = array_values(array_unique($candidates));
			sort($candidates);
			if (!empty($bysetpos)) {
				$selected = array();
				foreach ($bysetpos as $position) {
					$position = ($position > 0 ? $position - 1 : count($candidates) + $position);
					if (isset($candidates[$position])) {
						$selected[$candidates[$position]] = $candidates[$position];
					}
				}
				sort($selected);
				$candidates = $selected;
			}

			foreach ($candidates as $start) {
				if ($start < $dtstart) {
					continue;
				}
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
	 * @param 	bool		$floating		True if the date is a "floating" time (no Z and no TZID), so a local time and not a GMT time
	 * @param 	string		$timezone		Time zone of a floating time (the one of the calendar). If empty, the time zone of the user is used.
	 * @return 	int
	 */
	public function ical_date_to_unix($ical_date, $floating = false, $timezone = '')
	{
		// phpcs:enable
		$ical_date = str_replace('T', '', $ical_date);
		$ical_date = str_replace('Z', '', $ical_date);

		$ntime = 0;
		// TIME LIMITED EVENT
		$date = array();
		if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $ical_date, $date)) {
			$ntime = dol_mktime((int) $date[4], (int) $date[5], (int) $date[6], (int) $date[2], (int) $date[3], (int) $date[1], ($floating ? ($timezone ? 'tz,'.$timezone : 'tzuserrel') : true));
		}

		//if (empty($date[4])) print 'Error bad date: '.$ical_date.' - date1='.$date[1];
		//print dol_print_date($ntime,'dayhour');exit;
		return $ntime; // ntime is a GTM time
	}

	/**
	 * Return the properties of the calendar itself (VCALENDAR: PRODID, X-WR-TIMEZONE...).
	 *
	 * @return array<string,mixed>
	 */
	private function getCalendarProperties()
	{
		return (isset($this->cal['VCALENDAR']) && is_array($this->cal['VCALENDAR'])) ? $this->cal['VCALENDAR'] : array();
	}

	/**
	 * Return the time zone of the calendar, declared by the property X-WR-TIMEZONE (not standard but written by Google, Apple, Outlook...).
	 * It is the time zone of the "floating" times (date and time with no Z and no TZID) of the file.
	 *
	 * @return string	Name of the time zone (for example 'Europe/Paris'), or '' if there is none or if it is not a valid time zone
	 */
	public function getCalendarTimezone()
	{
		$calendar = $this->getCalendarProperties();
		$timezone = (isset($calendar['X-WR-TIMEZONE']) && is_string($calendar['X-WR-TIMEZONE'])) ? trim($calendar['X-WR-TIMEZONE'], " \t\"") : '';
		if ($timezone === '' || !in_array($timezone, timezone_identifiers_list())) {
			return '';
		}
		return $timezone;
	}

	/**
	 * Return the name of the PHP time zone that matches the TZID of a date.
	 * The TZID can be a name of the IANA database ('Europe/Paris'), written in any case, or a Windows name written by Outlook ('Romance Standard Time').
	 *
	 * @param 	string 	$tzid	TZID of a date
	 * @return 	string			Name of a time zone known by PHP, or '' if the TZID is not known
	 */
	public function getPhpTimezone($tzid)
	{
		$tzid = trim($tzid, " \t\"");
		if ($tzid === '') {
			return '';
		}
		$identifiers = timezone_identifiers_list();
		foreach ($identifiers as $identifier) {
			if (strcasecmp($identifier, $tzid) == 0) {
				return $identifier;
			}
		}
		// Most common Windows names (written by Outlook and Exchange)
		$windows = array(
			'romance standard time' => 'Europe/Paris', 'w. europe standard time' => 'Europe/Berlin', 'central europe standard time' => 'Europe/Budapest',
			'central european standard time' => 'Europe/Warsaw', 'gmt standard time' => 'Europe/London', 'greenwich standard time' => 'Atlantic/Reykjavik',
			'e. europe standard time' => 'Europe/Chisinau', 'fle standard time' => 'Europe/Kiev', 'gtb standard time' => 'Europe/Bucharest', 'russian standard time' => 'Europe/Moscow',
			'turkey standard time' => 'Europe/Istanbul', 'eastern standard time' => 'America/New_York', 'central standard time' => 'America/Chicago',
			'mountain standard time' => 'America/Denver', 'pacific standard time' => 'America/Los_Angeles', 'us mountain standard time' => 'America/Phoenix',
			'atlantic standard time' => 'America/Halifax', 'e. south america standard time' => 'America/Sao_Paulo', 'argentina standard time' => 'America/Buenos_Aires',
			'china standard time' => 'Asia/Shanghai', 'tokyo standard time' => 'Asia/Tokyo', 'korea standard time' => 'Asia/Seoul', 'india standard time' => 'Asia/Kolkata',
			'singapore standard time' => 'Asia/Singapore', 'arabian standard time' => 'Asia/Dubai', 'aus eastern standard time' => 'Australia/Sydney',
			'new zealand standard time' => 'Pacific/Auckland', 'south africa standard time' => 'Africa/Johannesburg', 'egypt standard time' => 'Africa/Cairo',
			'utc' => 'UTC', 'w. central africa standard time' => 'Africa/Lagos', 'morocco standard time' => 'Africa/Casablanca'
		);
		$key = strtolower($tzid);
		if (isset($windows[$key])) {
			return $windows[$key];
		}

		// Unknown name: the file can say which time zone it is (X-LIC-LOCATION of its VTIMEZONE)
		$vtimezones = $this->getVtimezones();
		return (isset($vtimezones[$tzid]) ? $vtimezones[$tzid]['location'] : '');
	}

	/**
	 * Return the time zones (VTIMEZONE) declared in the file, by TZID.
	 * For each one: the name of the PHP time zone given by X-LIC-LOCATION ('' if none or unknown by PHP), and the changes of offset
	 * of its STANDARD and DAYLIGHT parts (start as written in the file, offsets before and after in seconds, recurrence rule, RDATE).
	 *
	 * @return array<string,array{location:string,changes:array<int,array{start:int,before:int,after:int,rrule:array<string,string>,rdates:array<int,int>}>}>
	 */
	private function getVtimezones()
	{
		if ($this->vtimezones !== null) {
			return $this->vtimezones;
		}
		$this->vtimezones = [];

		$text = (string) preg_replace('/\r?\n[ \t]/', '', (string) $this->file_text);	// Unfold the lines
		if (!preg_match_all('/^BEGIN:VTIMEZONE\s*$(.*?)^END:VTIMEZONE\s*$/ims', $text, $blocks)) {
			return $this->vtimezones;
		}
		$identifiers = timezone_identifiers_list();
		foreach ($blocks[1] as $block) {
			if (!preg_match('/^TZID[^:\r\n]*:\s*"?([^"\r\n]+?)"?\s*$/im', $block, $reg)) {
				continue;
			}
			$tzid = $reg[1];
			$location = '';
			if (preg_match('/^X-LIC-LOCATION[^:\r\n]*:\s*([^\r\n]+?)\s*$/im', $block, $reg)) {
				foreach ($identifiers as $identifier) {
					if (strcasecmp($identifier, $reg[1]) == 0) {
						$location = $identifier;
						break;
					}
				}
			}
			$changes = [];
			if (preg_match_all('/^BEGIN:(STANDARD|DAYLIGHT)\s*$(.*?)^END:\1\s*$/ims', $block, $parts)) {
				foreach ($parts[2] as $part) {
					$props = [];
					$offsets = [];
					$rdates = [];
					foreach (preg_split('/\r?\n/', $part) as $line) {
						if (!preg_match('/^([A-Z-]+)[^:]*:(.*)$/i', trim($line), $reg)) {
							continue;
						}
						$name = strtoupper($reg[1]);
						if ($name == 'RDATE') {
							foreach (explode(',', $reg[2]) as $rdate) {
								if (preg_match('/^\s*(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $rdate, $d)) {
									$rdates[] = (int) gmmktime((int) $d[4], (int) $d[5], (int) $d[6], (int) $d[2], (int) $d[3], (int) $d[1]);
								}
							}
						} elseif ($name == 'TZOFFSETFROM') {
							$offsets['before'] = $this->parseUtcOffset($reg[2]);
						} elseif ($name == 'TZOFFSETTO') {
							$offsets['after'] = $this->parseUtcOffset($reg[2]);
						} else {
							$props[$name] = trim($reg[2]);
						}
					}
					if (empty($props['DTSTART']) || !preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $props['DTSTART'], $d)
						|| !isset($offsets['before']) || !isset($offsets['after'])) {
						continue;
					}
					$rrule = !empty($props['RRULE']) ? $this->ical_rrule($props['RRULE']) : [];
					$changes[] = [
						'start' => (int) gmmktime((int) $d[4], (int) $d[5], (int) $d[6], (int) $d[2], (int) $d[3], (int) $d[1]),
						'before' => $offsets['before'],
						'after' => $offsets['after'],
						'rrule' => ($rrule && $this->isRRuleSupported($rrule)) ? $rrule : [],
						'rdates' => $rdates,
					];
				}
			}
			$this->vtimezones[$tzid] = ['location' => $location, 'changes' => $changes];
		}
		return $this->vtimezones;
	}

	/**
	 * Convert an offset of a time zone written by iCal ('+0100', '-053000') into seconds.
	 *
	 * @param 	string 		$offset		Offset
	 * @return 	int|null				Offset in seconds, or null if it is not valid
	 */
	private function parseUtcOffset($offset)
	{
		if (!preg_match('/^([+-])(\d{2})(\d{2})(\d{2})?$/', trim($offset), $reg)) {
			return null;
		}
		return ($reg[1] == '-' ? -1 : 1) * ((int) $reg[2] * 3600 + (int) $reg[3] * 60 + (int) ($reg[4] ?? 0));
	}

	/**
	 * Return the offset from UTC (in seconds) of a local date and time of a time zone declared in the file (VTIMEZONE), from its rules.
	 *
	 * @param 	int 	$localtime	Local date and time written as GMT timestamp
	 * @param 	string 	$tzid		TZID of the date
	 * @return 	int|null			Offset in seconds, or null if the file has no usable rules for this TZID
	 */
	private function getVtimezoneOffset($localtime, $tzid)
	{
		$vtimezones = $this->getVtimezones();
		if (empty($vtimezones[$tzid]['changes'])) {
			return null;
		}
		$offset = null;
		$lastchange = null;
		$firstchange = null;
		$offsetbefore = null;
		foreach ($vtimezones[$tzid]['changes'] as $change) {
			$starts = [$change['start']];
			if ($change['rrule']) {
				$starts = $this->expandRRule($change['rrule'], $change['start'], $localtime + 2 * 86400, 0, $localtime - 400 * 86400);
			}
			foreach ($change['rdates'] as $rdate) {
				$starts[] = $rdate;
			}
			foreach ($starts as $start) {
				// A local time in the gap (spring forward) keeps the offset before, and a repeated local time (fall back) uses the first one
				$effective = $start + max(0, $change['after'] - $change['before']);
				if ($firstchange === null || $start < $firstchange) {
					$firstchange = $start;
					$offsetbefore = $change['before'];
				}
				if ($effective <= $localtime && ($lastchange === null || $start > $lastchange)) {
					$lastchange = $start;
					$offset = $change['after'];
				}
			}
		}
		if ($offset === null && $offsetbefore !== null) {
			$offset = $offsetbefore;
		}
		return $offset;
	}

	/**
	 * Tell if the dates of a TZID can be converted by tzidToTimestamp(): name known by PHP, or time zone with rules declared in the file.
	 *
	 * @param 	string 	$tzid	TZID of a date
	 * @return 	bool			True if the TZID is known
	 */
	public function isTzidKnown($tzid)
	{
		if ($this->getPhpTimezone($tzid) !== '') {
			return true;
		}
		$vtimezones = $this->getVtimezones();
		return !empty($vtimezones[trim($tzid, " \t\"")]['changes']);
	}

	/**
	 * Convert a local date and time of a time zone (TZID) into a real time, taking into account the daylight saving time of this date.
	 *
	 * @param 	int 	$localtime	Local date and time written as GMT timestamp (how ical_dt_date() returns a date with a TZID)
	 * @param 	string 	$tzid		TZID of the date
	 * @return 	int|null			Real timestamp, or null if the TZID is not known
	 */
	public function tzidToTimestamp($localtime, $tzid)
	{
		$timezone = $this->getPhpTimezone($tzid);
		if ($timezone === '') {
			// Not a name known by PHP: we use the rules of the time zone written in the file
			$offset = $this->getVtimezoneOffset($localtime, trim($tzid, " \t\""));
			return ($offset === null ? null : $localtime - $offset);
		}
		return dol_mktime((int) gmdate('G', $localtime), (int) gmdate('i', $localtime), (int) gmdate('s', $localtime), (int) gmdate('n', $localtime), (int) gmdate('j', $localtime), (int) gmdate('Y', $localtime), 'tz,'.$timezone);
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

		// Analyse the parameters of the key (TZID...)
		$params = $this->getKeyParams($key);
		$key = strtoupper(explode(';', $key, 2)[0]);

		if (empty($params['TZID'])) { // not TZID in key
			// A date time without Z (like 19970714T133000) is a "floating" time (RFC 5545): it is a local time, not a GMT time
			$floating = (strpos($value, 'T') !== false && strtoupper(substr(trim($value), -1)) != 'Z');
			$value = ($value === '' ? '' : $this->ical_date_to_unix($value, $floating, ($floating ? $this->getCalendarTimezone() : '')));
			return array($key, $value);
		}

		$value = str_replace('T', '', $value);

		$return_value['TZID'] = trim($params['TZID'], '"');	// RFC 5545 allows quoted parameter values (TZID="Europe/Berlin")
		$return_value['unixtime'] = ($value === '' ? '' : $this->ical_date_to_unix($value));	// Local time as GMT timestamp; TZ correction is done by caller (AGENDA_EXT_BUGGEDFILEx)

		return array($key, $return_value);
	}

	/**
	 * Check whether a parsed calendar component has a usable start date.
	 *
	 * @param	array<string,mixed>	$event	Parsed calendar component
	 * @return	bool
	 */
	public function hasStartDate($event)
	{
		if (isset($event['DTSTART;VALUE=DATE'])) {
			return $event['DTSTART;VALUE=DATE'] !== '';
		}

		if (!array_key_exists('DTSTART', $event)) {
			return false;
		}

		if (is_array($event['DTSTART'])) {
			return isset($event['DTSTART']['unixtime']) && $event['DTSTART']['unixtime'] !== '';
		}

		return $event['DTSTART'] !== '' && $event['DTSTART'] !== null;
	}

	/**
	 * Return the parameters of a property key (for 'DTSTART;VALUE=DATE-TIME;TZID=Europe/Paris', array('VALUE' => 'DATE-TIME', 'TZID' => 'Europe/Paris')).
	 *
	 * @param 	string 				$key	Key of the property, with its parameters
	 * @return 	array<string,string>		Parameters (names in upper case)
	 */
	public function getKeyParams($key)
	{
		$params = array();
		$parts = explode(';', $key);
		array_shift($parts);
		foreach ($parts as $part) {
			$item = explode('=', $part, 2);
			if (isset($item[1]) && $item[0] !== '') {
				$params[strtoupper(trim($item[0]))] = trim($item[1]);
			}
		}
		return $params;
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
