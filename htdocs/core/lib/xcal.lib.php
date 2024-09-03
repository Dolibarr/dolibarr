<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/lib/xcal.lib.php
 *  \brief      Function to manage calendar files (vcal/ical/...)
 */

/**
 *	Build a file from an array of events
 *  All input params and data must be encoded in $conf->charset_output
 *
 *  @param      string  $format             "vcal" or "ical"
 *  @param      string  $title              Title of export
 *  @param      string  $desc               Description of export
 *  @param      array   $events_array       Array of events ("uid","startdate","duration","enddate","title","summary","category","email","url","desc","author")
 *  @param      string  $outputfile         Output file
 *  @return     int                         Return integer < 0 if KO, Nb of events in file if OK
 */
function build_calfile($format, $title, $desc, $events_array, $outputfile)
{
	global $conf, $langs;

	dol_syslog("xcal.lib.php::build_calfile Build cal file ".$outputfile." to format ".$format);

	if (empty($outputfile)) {
		// -1 = error
		return -1;
	}

	$nbevents = 0;

	// Note: A cal file is an UTF8 encoded file
	$calfileh = fopen($outputfile, "w");

	if ($calfileh) {
		include_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

		$now      = dol_now();
		$encoding = "";

		if ($format === "vcal") {
			$encoding = "ENCODING=QUOTED-PRINTABLE:";
		}

		// Print header
		fwrite($calfileh, "BEGIN:VCALENDAR\n");

		// version is always "2.0"
		fwrite($calfileh, "VERSION:2.0\n");

		fwrite($calfileh, "METHOD:PUBLISH\n");
		fwrite($calfileh, "PRODID:-//DOLIBARR ".DOL_VERSION."\n");
		fwrite($calfileh, "CALSCALE:GREGORIAN\n");
		fwrite($calfileh, "X-WR-CALNAME:".$encoding.format_cal($format, $title)."\n");
		fwrite($calfileh, "X-WR-CALDESC:".$encoding.format_cal($format, $desc)."\n");
		//fwrite($calfileh,"X-WR-TIMEZONE:Europe/Paris\n");

		if (getDolGlobalString('MAIN_AGENDA_EXPORT_CACHE') && getDolGlobalInt('MAIN_AGENDA_EXPORT_CACHE') > 60) {
			$hh = convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE, "hour");
			$mm = convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE, "min");
			$ss = convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE, "sec");

			fwrite($calfileh, "X-PUBLISHED-TTL: P".$hh."H".$mm."M".$ss."S\n");
		}

		foreach ($events_array as $key => $event) {
			// See http://fr.wikipedia.org/wiki/ICalendar for format
			// See http://www.ietf.org/rfc/rfc2445.txt for RFC

			// TODO: avoid use extra event array, use objects direct thahtwas created before

			$uid           = $event["uid"];
			$type          = $event["type"];
			$startdate     = $event["startdate"];
			$duration      = $event["duration"];
			$enddate       = $event["enddate"];
			$summary       = $event["summary"];
			$category      = $event["category"];
			$priority      = $event["priority"];
			$fulldayevent  = $event["fulldayevent"];
			$location      = $event["location"];
			$email         = $event["email"];
			$url           = $event["url"];
			$transparency  = $event["transparency"];
			$description   = dol_string_nohtmltag(preg_replace("/<br[\s\/]?>/i", "\n", $event["desc"]), 0);
			$created       = $event["created"];
			$modified      = $event["modified"];
			$assignedUsers = $event["assignedUsers"];
			//print $fulldayevent.' '.dol_print_date($startdate, 'dayhour', 'gmt');

			// Format
			$summary     = format_cal($format, $summary);
			$description = format_cal($format, $description);
			$category    = format_cal($format, $category);
			$location    = format_cal($format, $location);

			// Output the vCard/iCal VEVENT object
			/*
			Example from Google ical export for a 1 hour event:
			BEGIN:VEVENT
			DTSTART:20101103T120000Z
			DTEND:20101103T130000Z
			DTSTAMP:20101121T144902Z
			UID:4eilllcsq8r1p87ncg7vc8dbpk@google.com
			CREATED:20101121T144657Z
			DESCRIPTION:
			LAST-MODIFIED:20101121T144707Z
			LOCATION:
			SEQUENCE:0
			STATUS:CONFIRMED
			SUMMARY:Tâche 1 heure
			TRANSP:OPAQUE
			END:VEVENT

			Example from Google ical export for a 1 day event:
			BEGIN:VEVENT
			DTSTART;VALUE=DATE:20101102
			DTEND;VALUE=DATE:20101103
			DTSTAMP:20101121T144902Z
			UID:d09t43kcf1qgapu9efsmmo1m6k@google.com
			CREATED:20101121T144607Z
			DESCRIPTION:
			LAST-MODIFIED:20101121T144607Z
			LOCATION:
			SEQUENCE:0
			STATUS:CONFIRMED
			SUMMARY:Tâche 1 jour
			TRANSP:TRANSPARENT
			END:VEVENT
			*/

			if ($type === "event") {
				$nbevents++;

				fwrite($calfileh, "BEGIN:VEVENT\n");
				fwrite($calfileh, "UID:".$uid."\n");

				if (!empty($email)) {
					fwrite($calfileh, "ORGANIZER:MAILTO:".$email."\n");
					fwrite($calfileh, "CONTACT:MAILTO:".$email."\n");
				}

				if (!empty($url)) {
					fwrite($calfileh, "URL:".$url."\n");
				}

				if (is_array($assignedUsers)) {
					foreach ($assignedUsers as $assignedUser) {
						if ($assignedUser->email === $email) {
							continue;
						}

						fwrite($calfileh, "ATTENDEE;RSVP=TRUE:mailto:".$assignedUser->email."\n");
					}
				}

				if ($created) {
					fwrite($calfileh, "CREATED:".dol_print_date($created, "dayhourxcard", true)."\n");
				}

				if ($modified) {
					fwrite($calfileh, "LAST-MODIFIED:".dol_print_date($modified, "dayhourxcard", true)."\n");
				}

				fwrite($calfileh, "SUMMARY:".$encoding.$summary."\n");
				fwrite($calfileh, "DESCRIPTION:".$encoding.$description."\n");

				if (!empty($location)) {
					fwrite($calfileh, "LOCATION:".$encoding.$location."\n");
				}

				if ($fulldayevent) {
					fwrite($calfileh, "X-FUNAMBOL-ALLDAY:1\n");
				}

				// see https://docs.microsoft.com/en-us/openspecs/exchange_server_protocols/ms-oxcical/0f262da6-c5fd-459e-9f18-145eba86b5d2
				if ($fulldayevent) {
					fwrite($calfileh, "X-MICROSOFT-CDO-ALLDAYEVENT:TRUE\n");
				}

				// Date must be GMT dates
				// Current date
				fwrite($calfileh, "DTSTAMP:".dol_print_date($now, "dayhourxcard", 'gmt')."\n");

				// Start date
				$prefix     = "";
				$startdatef = dol_print_date($startdate, "dayhourxcard", 'gmt');

				if ($fulldayevent) {
					// For fullday event, date was stored with old version by using the user timezone instead of storing the date at UTC+0
					// in the timezone of server (so for a PHP timezone of -3, we should store '2023-05-31 21:00:00.000'
					// Using option MAIN_STORE_FULL_EVENT_IN_GMT=1 change the behaviour to store in GMT for full day event. This must become
					// the default behaviour but there is no way to change keeping old saved date compatible.
					$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
					// Local time should be used to prevent users in time zones earlier than GMT from being one day earlier
					$prefix     = ";VALUE=DATE";
					if ($tzforfullday) {
						$startdatef = dol_print_date($startdate, "dayxcard", 'gmt');
					} else {
						$startdatef = dol_print_date($startdate, "dayxcard", 'tzserver');
					}
				}

				fwrite($calfileh, "DTSTART".$prefix.":".$startdatef."\n");

				// End date
				if ($fulldayevent) {
					if (empty($enddate)) {
						// We add 1 day needed for full day event (DTEND must be next day after event).
						// This is mention in https://datatracker.ietf.org/doc/html/rfc5545:
						// "The "DTEND" property for a "VEVENT" calendar component specifies the non-inclusive end of the event."
						$enddate = dol_time_plus_duree($startdate, 1, "d");
					}
				} else {
					if (empty($enddate)) {
						$enddate = $startdate + $duration;
					}
				}

				$prefix   = "";
				$enddatef = dol_print_date($enddate, "dayhourxcard", 'gmt');

				if ($fulldayevent) {
					$prefix   = ";VALUE=DATE";
					// We add 1 second so we reach the +1 day needed for full day event (DTEND must be next day after event)
					// This is mention in https://datatracker.ietf.org/doc/html/rfc5545:
					// "The "DTEND" property for a "VEVENT" calendar component specifies the non-inclusive end of the event."
					$enddatef = dol_print_date($enddate + 1, "dayxcard", 'tzserver');
				}

				fwrite($calfileh, "DTEND".$prefix.":".$enddatef."\n");
				fwrite($calfileh, "STATUS:CONFIRMED\n");

				if (!empty($transparency)) {
					fwrite($calfileh, "TRANSP:".$transparency."\n");
				}

				if (!empty($category)) {
					fwrite($calfileh, "CATEGORIES:".$encoding.$category."\n");
				}

				fwrite($calfileh, "END:VEVENT\n");
			}

			// Output the vCard/iCal VJOURNAL object
			if ($type === "journal") {
				$nbevents++;

				fwrite($calfileh, "BEGIN:VJOURNAL\n");
				fwrite($calfileh, "UID:".$uid."\n");

				if (!empty($email)) {
					fwrite($calfileh, "ORGANIZER:MAILTO:".$email."\n");
					fwrite($calfileh, "CONTACT:MAILTO:".$email."\n");
				}

				if (!empty($url)) {
					fwrite($calfileh, "URL:".$url."\n");
				}

				if ($created) {
					fwrite($calfileh, "CREATED:".dol_print_date($created, "dayhourxcard", 'gmt')."\n");
				}

				if ($modified) {
					fwrite($calfileh, "LAST-MODIFIED:".dol_print_date($modified, "dayhourxcard", 'gmt')."\n");
				}

				fwrite($calfileh, "SUMMARY:".$encoding.$summary."\n");
				fwrite($calfileh, "DESCRIPTION:".$encoding.$description."\n");
				fwrite($calfileh, "STATUS:CONFIRMED\n");
				fwrite($calfileh, "CATEGORIES:".$category."\n");
				fwrite($calfileh, "LOCATION:".$location."\n");
				fwrite($calfileh, "TRANSP:OPAQUE\n");
				fwrite($calfileh, "CLASS:CONFIDENTIAL\n");
				fwrite($calfileh, "DTSTAMP:".dol_print_date($startdatef, "dayhourxcard", 'gmt')."\n");

				fwrite($calfileh, "END:VJOURNAL\n");
			}
		}

		// Footer
		fwrite($calfileh, "END:VCALENDAR");

		fclose($calfileh);
		dolChmod($outputfile);
	} else {
		dol_syslog("xcal.lib.php::build_calfile Failed to open file ".$outputfile." for writing");
		return -2;
	}

	return $nbevents;
}

/**
 *  Build a file from an array of events.
 *  All input data must be encoded in $conf->charset_output
 *
 *  @param      string	$format             "rss"
 *  @param      string	$title              Title of export
 *  @param      string	$desc               Description of export
 *  @param      array	$events_array       Array of events ("uid","startdate","summary","url","desc","author","category","image") or Array of WebsitePage
 *  @param      string	$outputfile         Output file
 *  @param      string	$filter             (optional) Filter
 *  @param		string	$url				Url (If empty, forge URL for agenda RSS export)
 *  @param		string	$langcode			Language code to show in header
 *  @return     int                         Return integer < 0 if KO, Nb of events in file if OK
 */
function build_rssfile($format, $title, $desc, $events_array, $outputfile, $filter = '', $url = '', $langcode = '')
{
	global $user, $conf, $langs, $mysoc;
	global $dolibarr_main_url_root;

	dol_syslog("xcal.lib.php::build_rssfile Build rss file ".$outputfile." to format ".$format);

	if (empty($outputfile)) {
		// -1 = error
		return -1;
	}

	$nbevents = 0;

	$fichier = fopen($outputfile, "w");

	if ($fichier) {
		// Print header
		fwrite($fichier, '<?xml version="1.0" encoding="'.$langs->charset_output.'"?>');
		fwrite($fichier, "\n");

		fwrite($fichier, '<rss version="2.0">');
		fwrite($fichier, "\n");

		fwrite($fichier, "<channel>\n");
		fwrite($fichier, "<title>".dol_escape_xml($title)."</title>\n");
		fwrite($fichier, "<description>".dol_escape_xml($title)."</description>\n");
		if ($langcode) {
			fwrite($fichier, "<language>".dol_escape_xml($langcode)."</language>\n");
		}

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace("/".preg_quote(DOL_URL_ROOT, "/")."$/i", "", trim($dolibarr_main_url_root));
		$urlwithroot       = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;                       // This is to use same domain name than current

		// Url
		if (empty($url)) {
			$url = $urlwithroot."/public/agenda/agendaexport.php?format=rss&exportkey=".urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY'));
		}
		fwrite($fichier, "<link><![CDATA[".$url."]]></link>\n");

		// Image
		if (!empty($mysoc->logo_squarred_small)) {
			$urlimage = $urlwithroot.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_small);
			//$urlimage = $GLOBALS['website']->virtualhost
			if ($urlimage && (empty($GLOBALS['website']) || preg_match('/'.preg_quote($GLOBALS['website']->virtualhost, '/').'/', $urlwithroot))) {
				fwrite($fichier, "<image><url><![CDATA[".$urlimage."]]></url><title>".htmlspecialchars($title)."</title><link><![CDATA[".$url."]]></link></image>\n");
			}
		}

		foreach ($events_array as $key => $event) {
			$eventqualified = true;

			if ($filter) {
				// TODO Add a filter

				$eventqualified = false;
			}

			if ($eventqualified) {
				$nbevents++;

				if (is_object($event) && get_class($event) == 'WebsitePage') {
					// Convert object WebsitePage into an array $event
					$tmpevent = array();
					$tmpevent['uid'] = (string) $event->id;
					$tmpevent['startdate'] = $event->date_creation;
					$tmpevent['summary'] = $event->title;
					$tmpevent['url'] = $event->fullpageurl ? $event->fullpageurl : $event->pageurl.'.php';
					$tmpevent['author'] = $event->author_alias ? $event->author_alias : 'unknown';
					//$tmpevent['category'] = '';
					$tmpevent['desc'] = $event->description;
					if (!empty($event->image)) {
						$tmpevent['image'] = $GLOBALS['website']->virtualhost.'/medias/'.$event->image;
					}
					$tmpevent['content'] = $event->content;

					$event = $tmpevent;
				}

				$uid		  = $event["uid"];
				$startdate	  = $event["startdate"];
				$summary  	  = $event["summary"];
				$description  = $event["desc"];
				$url		  = empty($event["url"]) ? '' : $event["url"];
				$author       = $event["author"];
				$category     = empty($event["category"]) ? null : $event["category"];
				$image        = '';
				if (!empty($event["image"])) {
					$image = $event["image"];
				} else {
					$reg = array();
					// If we found a link into content like <img alt="..." class="..." src="..."
					if (!empty($event["content"]) && preg_match('/<img\s*(?:alt="[^"]*"\s*)?(?:class="[^"]*"\s*)?src="([^"]+)"/m', $event["content"], $reg)) {
						if (!empty($reg[0])) {
							$image = $reg[1];
						}
						// Convert image "/medias/...." and "/viewimage.php?modulepart=medias&file=(.*)"
						if (!empty($GLOBALS['website']->virtualhost)) {
							if (preg_match('/^\/medias\//', $image)) {
								$image = $GLOBALS['website']->virtualhost.$image;
							} elseif (preg_match('/^\/viewimage\.php\?modulepart=medias&[^"]*file=([^&"]+)/', $image, $reg)) {
								$image = $GLOBALS['website']->virtualhost.'/medias/'.$reg[1];
							}
						}
					}
				}


				/* No place inside a RSS
				$priority     = $event["priority"];
				$fulldayevent = $event["fulldayevent"];
				$location     = $event["location"];
				$email        = $event["email"];
				*/

				$description = dol_string_nohtmltag(preg_replace("/<br[\s\/]?>/i", "\n", $event["desc"]), 0);

				fwrite($fichier, "<item>\n");
				fwrite($fichier, "<title><![CDATA[".$summary."]]></title>\n");
				fwrite($fichier, "<link><![CDATA[".$url."]]></link>\n");
				//fwrite($fichier, "<author><![CDATA[".$author."]]></author>\n");
				if (!empty($category)) {
					fwrite($fichier, "<category><![CDATA[".$category."]]></category>\n");
				}
				//fwrite($fichier, "<description><![CDATA[".$summary."]]></description>\n");
				fwrite($fichier, "<description><![CDATA[");
				if (!empty($image)) {
					fwrite($fichier, '<p><img class="center" src="'.$image.'"/></p>');
				}

				if ($description) {
					fwrite($fichier, $description);
				}
				// else
				//     fwrite($fichier, "NoDesc");

				fwrite($fichier, "]]></description>\n");
				fwrite($fichier, "<pubDate>".date("r", $startdate)."</pubDate>\n");
				fwrite($fichier, '<guid isPermaLink="false"><![CDATA['.str_pad($uid, 10, "0", STR_PAD_LEFT).']]></guid>'."\n");
				fwrite($fichier, '<source url="'.$url.'"><![CDATA[Dolibarr]]></source>'."\n");
				fwrite($fichier, "</item>\n");
			}
		}

		fwrite($fichier, "</channel>");
		fwrite($fichier, "\n");
		fwrite($fichier, "</rss>");

		fclose($fichier);
		dolChmod($outputfile);
	}

	return $nbevents;
}

/**
 *  Encode for cal export
 *
 *  @param      string  $format     "vcal" or "ical"
 *  @param      string  $string     String to encode
 *  @return     string              String encoded
 */
function format_cal($format, $string)
{
	$newstring = $string;

	if ($format === "vcal") {
		$newstring = quotedPrintEncode($newstring);
	}

	if ($format === "ical") {
		// Replace new lines chars by "\n"
		$newstring = preg_replace("/\r\n/i", "\\n", $newstring);
		$newstring = preg_replace("/\n\r/i", "\\n", $newstring);
		$newstring = preg_replace("/\n/i", "\\n", $newstring);

		// Must not exceed 75 char. Cut with "\r\n"+Space
		$newstring = calEncode($newstring);
	}

	return $newstring;
}

/**
 *  Cut string after 75 chars. Add CRLF+Space.
 *  line must be encoded in UTF-8
 *
 *  @param      string    $line     String to convert
 *  @return     string              String converted
 */
function calEncode($line)
{
	$out     = "";
	$newpara = "";

	// If mb_ functions exists, it"s better to use them
	if (function_exists("mb_strlen")) {
		$strlength = mb_strlen($line, "UTF-8");

		for ($j = 0; $j < $strlength; $j++) {
			// Take char at position $j
			$char = dol_substr($line, $j, 1, "UTF-8");

			if ((mb_strlen($newpara, "UTF-8") + mb_strlen($char, "UTF-8")) >= 75) {
				// CRLF + Space for cal
				$out .= $newpara."\r\n ";

				$newpara = "";
			}

			$newpara .= $char;
		}

		$out .= $newpara;
	} else {
		$strlength = dol_strlen($line);

		for ($j = 0; $j < $strlength; $j++) {
			// Take char at position $j
			$char = substr($line, $j, 1);

			if ((dol_strlen($newpara) + dol_strlen($char)) >= 75) {
				// CRLF + Space for cal
				$out .= $newpara."\r\n ";

				$newpara = "";
			}

			$newpara .= $char;
		}

		$out .= $newpara;
	}

	return trim($out);
}


/**
 *  Encode into vcal format
 *
 *  @param      string  $str        String to convert
 *  @param      int     $forcal     (optional) 1 = For cal
 *  @return     string              String converted
 */
function quotedPrintEncode($str, $forcal = 0)
{
	$lines = preg_split("/\r\n/", $str);
	$out   = "";

	foreach ($lines as $line) {
		$newpara = "";

		// Do not use dol_strlen here, we need number of bytes
		$strlength = strlen($line);

		for ($j = 0; $j < $strlength; $j++) {
			$char  = substr($line, $j, 1);
			$ascii = ord($char);

			if ($ascii < 32 || $ascii === 61 || $ascii > 126) {
				$char = "=".strtoupper(sprintf("%02X", $ascii));
			}

			// Do not use dol_strlen here, we need number of bytes
			if ((strlen($newpara) + strlen($char)) >= 76) {
				// New line with carray-return (CR) and line-feed (LF)
				$out .= $newpara."=\r\n";

				// extra space for cal
				if ($forcal) {
					$out .= " ";
				}

				$newpara = "";
			}

			$newpara .= $char;
		}

		$out .= $newpara;
	}
	return trim($out);
}

/**
 *  Decode vcal format
 *
 *  @param      string  $str    String to convert
 *  @return     string          String converted
 */
function quotedPrintDecode($str)
{
	return trim(quoted_printable_decode(preg_replace("/=\r?\n/", "", $str)));
}
