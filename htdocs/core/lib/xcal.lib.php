<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *  \file       htdocs/core/lib/xcal.lib.php
 *  \brief      Function to manage calendar files (vcal/ical/...)
 */

/**
 *	Build a file from an array of events
 *  All input params and data must be encoded in $conf->charset_output
 *
 *	@param		string	$format				'vcal' or 'ical'
 *	@param		string	$title				Title of export
 *	@param		string	$desc				Description of export
 *	@param		array	$events_array		Array of events ('eid','startdate','duration','enddate','title','summary','category','email','url','desc','author')
 *	@param		string	$outputfile			Output file
 *	@return		int							<0 if ko, Nb of events in file if ok
 */
function build_calfile($format,$title,$desc,$events_array,$outputfile)
{
	global $conf,$langs;

	dol_syslog("xcal.lib.php::build_calfile Build cal file ".$outputfile." to format ".$format);

	if (empty($outputfile)) return -1;

    // Note: A cal file is an UTF8 encoded file
	$calfileh=fopen($outputfile,'w');
	if ($calfileh)
	{
	    include_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');
		$now=dol_now();

		$encoding='';
		if ($format == 'vcal') $encoding='ENCODING=QUOTED-PRINTABLE:';

		// Print header
		fwrite($calfileh,"BEGIN:VCALENDAR\n");
		fwrite($calfileh,"VERSION:2.0\n");
		fwrite($calfileh,"METHOD:PUBLISH\n");
		//fwrite($calfileh,"PRODID:-//DOLIBARR ".DOL_VERSION."//EN\n");
		fwrite($calfileh,"PRODID:-//DOLIBARR ".DOL_VERSION."\n");
		fwrite($calfileh,"CALSCALE:GREGORIAN\n");
		fwrite($calfileh,"X-WR-CALNAME:".$encoding.format_cal($format,$title)."\n");
        fwrite($calfileh,"X-WR-CALDESC:".$encoding.format_cal($format,$desc)."\n");
        $hh=convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE,'hour');
        $mm=convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE,'min');
        $ss=convertSecondToTime($conf->global->MAIN_AGENDA_EXPORT_CACHE,'sec');
        //fwrite($calfileh,"X-WR-TIMEZONE:Europe/Paris\n");
        if (! empty($conf->global->MAIN_AGENDA_EXPORT_CACHE)
        && $conf->global->MAIN_AGENDA_EXPORT_CACHE > 60) fwrite($calfileh,"X-PUBLISHED-TTL: P".$hh."H".$mm."M".$ss."S\n");

		foreach ($events_array as $date => $event)
		{
			$eventqualified=true;
			if ($eventqualified)
			{
				// See http://fr.wikipedia.org/wiki/ICalendar for format
				// See http://www.ietf.org/rfc/rfc2445.txt for RFC
				$uid 		  = $event['uid'];
				$type         = $event['type'];
                $startdate    = $event['startdate'];
				$duration	  = $event['duration'];
				$enddate	  = $event['enddate'];
				$summary  	  = $event['summary'];
				$category	  = $event['category'];
                $priority     = $event['priority'];
                $fulldayevent = $event['fulldayevent'];
				$location     = $event['location'];
				$email 		  = $event['email'];
				$url		  = $event['url'];
				$transparency = $event['transparency'];		// OPAQUE (busy) or TRANSPARENT (not busy)
				$description=preg_replace('/<br[\s\/]?>/i',"\n",$event['desc']);
 				$description=dol_string_nohtmltag($description,0);	// Remove html tags
                $created      = $event['created'];
 				$modified     = $event['modified'];

				// Uncomment for tests
				//$summary="Resume";
				//$description="Description";
				//$description="MemberValidatedInDolibarr gd gdf gd gdff\nNom: tgdf g dfgdf gfd r ter\nType: gdfgfdf dfg fd gfd gd gdf gdf gfd gdfg dfg ddf\nAuteur: AD01fg dgdgdfg df gdf gd";

				// Format
				$summary=format_cal($format,$summary);
				$description=format_cal($format,$description);
				$category=format_cal($format,$category);
				$location=format_cal($format,$location);

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
                SUMMARY:Tache 1 heure
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
                SUMMARY:Tache 1 jour
                TRANSP:TRANSPARENT
                END:VEVENT
                */
				if ($type == 'event')
				{
					fwrite($calfileh,"BEGIN:VEVENT\n");
					fwrite($calfileh,"UID:".$uid."\n");
					if (! empty($email))
					{
						fwrite($calfileh,"ORGANIZER:MAILTO:".$email."\n");
						fwrite($calfileh,"CONTACT:MAILTO:".$email."\n");
					}
					if (! empty($url))
					{
						fwrite($calfileh,"URL:".$url."\n");
					};

                    if ($created)  fwrite($calfileh,"CREATED:".dol_print_date($created,'dayhourxcard',true)."\n");
                    if ($modified) fwrite($calfileh,"LAST-MODIFIED:".dol_print_date($modified,'dayhourxcard',true)."\n");
                    fwrite($calfileh,"SUMMARY:".$encoding.$summary."\n");
					fwrite($calfileh,"DESCRIPTION:".$encoding.$description."\n");

					/* Other keys:
					// Status values for a "VEVENT"
					statvalue  = "TENTATIVE"           ;Indicates event is
				                                        ;tentative.
				                / "CONFIRMED"           ;Indicates event is
				                                        ;definite.
				                / "CANCELLED"           ;Indicates event was
                    // Status values for "VTODO".
                    statvalue  =/ "NEEDS-ACTION"       ;Indicates to-do needs action.
				                / "COMPLETED"           ;Indicates to-do completed.
				                / "IN-PROCESS"          ;Indicates to-do in process of
				                / "CANCELLED"           ;Indicates to-do was cancelled.
                    // Status values for "VJOURNAL".
				    statvalue  =/ "DRAFT"              ;Indicates journal is draft.
				                / "FINAL"               ;Indicates journal is final.
				                / "CANCELLED"           ;Indicates journal is removed.
					*/
					//fwrite($calfileh,"CLASS:PUBLIC\n");				// PUBLIC, PRIVATE, CONFIDENTIAL
                    //fwrite($calfileh,"X-MICROSOFT-CDO-BUSYSTATUS:1\n");
                    //ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=Laurent Destailleur;X-NUM-GUESTS=0:mailto:eldy10@gmail.com

                    if (! empty($location)) fwrite($calfileh,"LOCATION:".$encoding.$location."\n");
					if ($fulldayevent) fwrite($calfileh,"X-FUNAMBOL-ALLDAY:1\n");
                    if ($fulldayevent) fwrite($calfileh,"X-MICROSOFT-CDO-ALLDAYEVENT:1\n");

					// Date must be GMT dates
					// Current date
					fwrite($calfileh,"DTSTAMP:".dol_print_date($now,'dayhourxcard',true)."\n");
					// Start date
                    $prefix='';
                    $startdatef = dol_print_date($startdate,'dayhourxcard',true);
                    if ($fulldayevent)
					{
                        $prefix=';VALUE=DATE';
					    $startdatef = dol_print_date($startdate,'dayxcard',false);     // Local time
					}
					fwrite($calfileh,"DTSTART".$prefix.":".$startdatef."\n");
                    // End date
					if ($fulldayevent)
					{
    					if (empty($enddate)) $enddate=dol_time_plus_duree($startdate,1,'d');
					}
					else
					{
                        if (empty($enddate)) $enddate=$startdate+$duration;
					}
                    $prefix='';
					$enddatef = dol_print_date($enddate,'dayhourxcard',true);
					if ($fulldayevent)
					{
                        $prefix=';VALUE=DATE';
					    $enddatef = dol_print_date($enddate+1,'dayxcard',false);
					    //$enddatef .= dol_print_date($enddate+1,'dayhourxcard',false);   // Local time
					}
                    fwrite($calfileh,"DTEND".$prefix.":".$enddatef."\n");
					fwrite($calfileh,'STATUS:CONFIRMED'."\n");
					if (! empty($transparency)) fwrite($calfileh,"TRANSP:".$transparency."\n");
					if (! empty($category)) fwrite($calfileh,"CATEGORIES:".$encoding.$category."\n");
					fwrite($calfileh,"END:VEVENT\n");
				}

				// Output the vCard/iCal VTODO object
				// ...
				//PERCENT-COMPLETE:39

				// Output the vCard/iCal VJOURNAL object
				if ($type == 'journal')
				{
					fwrite($calfileh,"BEGIN:VJOURNAL\n");
					fwrite($calfileh,"UID:".$uid."\n");
					if (! empty($email))
					{
						fwrite($calfileh,"ORGANIZER:MAILTO:".$email."\n");
						fwrite($calfileh,"CONTACT:MAILTO:".$email."\n");
					}
					if (! empty($url))
					{
						fwrite($calfileh,"URL:".$url."\n");
					};

                    if ($created)  fwrite($calfileh,"CREATED:".dol_print_date($created,'dayhourxcard',true)."\n");
                    if ($modified) fwrite($calfileh,"LAST-MODIFIED:".dol_print_date($modified,'dayhourxcard',true)."\n");
					fwrite($calfileh,"SUMMARY:".$encoding.$summary."\n");
					fwrite($calfileh,"DESCRIPTION:".$encoding.$description."\n");
					fwrite($calfileh,'STATUS:CONFIRMED'."\n");
					fwrite($calfileh,"CATEGORIES:".$category."\n");
					fwrite($calfileh,"LOCATION:".$location."\n");
					fwrite($calfileh,"TRANSP:OPAQUE\n");
					fwrite($calfileh,"CLASS:CONFIDENTIAL\n");
					fwrite($calfileh,"DTSTAMP:".dol_print_date($startdatef,'dayhourxcard',true)."\n");

					fwrite($calfileh,"END:VJOURNAL\n");
				}


				// Put other info in comment
				/*
				$comment=array();
				$comment ['eid']			= $eid;
				$comment ['url']			= $linktoevent;
				$comment ['date']			= dol_mktime($evttime,"Ymd");
				$comment ['duration']		= $duration;
				$comment ['startdate']		= $startdate;
				$comment ['enddate']		= $enddate;
				fwrite($calfileh,"COMMENT:" . serialize ($comment) . "\n");
				*/

			}
		}

		// Footer
		fwrite($calfileh,"END:VCALENDAR");

		fclose($calfileh);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
	}
	else
	{
		dol_syslog("xcal.lib.php::build_calfile Failed to open file ".$outputfile." for writing");
		return -2;
	}
}

/**
 *	Build a file from an array of events.
 *  All input data must be encoded in $conf->charset_output
 *
 *	@param		string	$format				'rss'
 *	@param		string	$title				Title of export
 *	@param		string	$desc				Description of export
 *	@param		array	$events_array		Array of events ('uid','startdate','summary','url','desc','author','category')
 *	@param		string	$outputfile			Output file
 *	@param		string	$filter				Filter
 *	@return		int							<0 if ko, Nb of events in file if ok
 */
function build_rssfile($format,$title,$desc,$events_array,$outputfile,$filter='')
{
	global $user,$conf,$langs;
	global $dolibarr_main_url_root;

	dol_syslog("xcal.lib.php::build_rssfile Build rss file ".$outputfile." to format ".$format);

	if (empty($outputfile)) return -1;

	$fichier=fopen($outputfile,'w');
	if ($fichier)
	{
		$date=date("r");

		// Print header
		$form='<?xml version="1.0" encoding="'.$langs->charset_output.'"?>';
		fwrite($fichier, $form);
		fwrite($fichier, "\n");
		$form='<rss version="2.0">';
		fwrite($fichier, $form);
		fwrite($fichier, "\n");

		$form="<channel>\n<title>".$title."</title>\n";
		fwrite($fichier, $form);

		$form='<description><![CDATA['.$desc.'.]]></description>'."\n".
//		'<language>fr</language>'."\n".
		'<copyright>Dolibarr</copyright>'."\n".
		'<lastBuildDate>'.$date.'</lastBuildDate>'."\n".
		'<generator>Dolibarr</generator>'."\n";

        $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',$dolibarr_main_url_root);
  		$url=$urlwithouturlroot.DOL_URL_ROOT.'/public/agenda/agendaexport.php?format=rss&exportkey='.urlencode($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY);
		$form.='<link><![CDATA['.$url.']]></link>'."\n";

		//print $form;
		fwrite($fichier, $form);


		foreach ($events_array as $date => $event)
		{
			$eventqualified=true;
			if ($filter)
			{
				// TODO Add a filter

				$eventqualified=false;
			}

			if ($eventqualified)
			{
				$uid		  = $event['uid'];
				$startdate	  = $event['startdate'];
				$summary  	  = $event['summary'];
				$url		  = $event['url'];
				$author		  = $event['author'];
				$category	  = $event['category'];
				/* No place inside a RSS
                $priority     = $event['priority'];
                $fulldayevent = $event['fulldayevent'];
                $location     = $event['location'];
                $email        = $event['email'];
                */
				$description=preg_replace('/<br[\s\/]?>/i',"\n",$event['desc']);
 				$description=dol_string_nohtmltag($description,0);	// Remove html tags

				fwrite($fichier, "<item>\n");
				fwrite($fichier, "<title><![CDATA[".$summary."]]></title>\n");
				fwrite($fichier, "<link><![CDATA[".$url."]]></link>\n");
				fwrite($fichier, "<author><![CDATA[".$author."]]></author>\n");
				fwrite($fichier, "<category><![CDATA[".$category."]]></category>\n");
				fwrite($fichier, "<description><![CDATA[");
				if ($description) fwrite($fichier, $description);
				//else fwrite($fichier, 'NoDesc');
				fwrite($fichier, "]]></description>\n");
				fwrite($fichier, "<pubDate>".date("r", $startdate)."</pubDate>\n");
				fwrite($fichier, "<guid isPermaLink=\"true\"><![CDATA[".$uid."]]></guid>\n");
				fwrite($fichier, "<source><![CDATA[Dolibarr]]></source>\n");
				fwrite($fichier, "</item>\n");
			}
		}

		fwrite($fichier, '</channel>');
		fwrite($fichier, "\n");
		fwrite($fichier, '</rss>');

		fclose($fichier);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
	}
}


/**
 * 	Encode for cal export
 *
 * 	@param		string	$format		vcal or ical
 * 	@param 		string	$string		string to encode
 * 	@return		string				string encoded
 */
function format_cal($format,$string)
{
	global $conf;

	$newstring=$string;

	if ($format == 'vcal')
	{
		$newstring=quotedPrintEncode($newstring);
	}
	if ($format == 'ical')
	{
		// Replace new lines chars by '\n'
		$newstring=preg_replace('/'."\r\n".'/i',"\n",$newstring);
		$newstring=preg_replace('/'."\n\r".'/i',"\n",$newstring);
		$newstring=preg_replace('/'."\n".'/i','\n',$newstring);
		// Must not exceed 75 char. Cut with "\r\n"+Space
		$newstring=calEncode($newstring);
	}

	return $newstring;
}

/**
 *	Cut string after 75 chars. Add CRLF+Space.
 *	line must be encoded in UTF-8
 *
 *	@param		string	$line	String to convert
 *	@return		string 			String converted
 */
function calEncode($line)
{
	$out = '';

	$newpara = '';

	// If mb_ functions exists, it's better to use them
	if (function_exists('mb_strlen'))
	{
	    $strlength=mb_strlen($line, 'UTF-8');
		for ($j = 0; $j <= ($strlength - 1); $j++)
		{
			$char = mb_substr($line, $j, 1, 'UTF-8');	// Take char at position $j

			if ((mb_strlen($newpara, 'UTF-8') + mb_strlen($char, 'UTF-8')) >= 75)
			{
				$out .= $newpara . "\r\n ";	// CRLF + Space for cal
				$newpara = '';
			}
			$newpara .= $char;
		}
		$out .= $newpara;
	}
	else
	{
	    $strlength=dol_strlen($line);
		for ($j = 0; $j <= ($strlength - 1); $j++)
		{
			$char = substr($line, $j, 1);	// Take char at position $j

			if ((dol_strlen($newpara) + dol_strlen($char)) >= 75 )
			{
				$out .= $newpara . "\r\n ";	// CRLF + Space for cal
				$newpara = '';
			}
			$newpara .= $char;
		}
		$out .= $newpara;
	}

	return trim($out);
}


/**
 *	Encode into vcal format
 *
 *	@param		string	$str		String to convert
 *	@param		int		$forcal		1=For cal
 *	@return		string 				String converted
 */
function quotedPrintEncode($str,$forcal=0)
{
	$lines = preg_split("/\r\n/", $str);
	$out = '';

	foreach ($lines as $line)
	{
		$newpara = '';

		$strlength=dol_strlen($line);
		for ($j = 0; $j <= ($strlength - 1); $j++)
		{
			$char = substr($line, $j, 1);
			$ascii = ord($char);

			if ( $ascii < 32 || $ascii == 61 || $ascii > 126 )
			$char = '=' . strtoupper(sprintf("%02X", $ascii));

			if ((dol_strlen($newpara) + dol_strlen($char)) >= 76 )
			{
				$out .= $newpara . '=' . "\r\n";	// CRLF
				if ($forcal) $out .= " ";		// + Space for cal
				$newpara = '';
			}
			$newpara .= $char;
		}
		$out .= $newpara;
	}
	return trim($out);
}

/**
 *	Decode vcal format
 *
 *	@param		string	$str		String to convert
 *	@return		string 				String converted
 */
function quotedPrintDecode($str)
{
	$out = preg_replace('/=\r?\n/', '', $str);
	$out = preg_replace('/=([A-F0-9]{2})/e', chr(hexdec('\\1')), $out);

	return trim($out);
}

?>