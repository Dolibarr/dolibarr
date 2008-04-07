<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
 
 
/**
   \file       htdocs/lib/xcal.lib.php
   \brief      Function to manage calendar files (vcal/ical/...)
   \version    $Id$
*/

/**
	\brief		Build a file from an array of events
	\param		format				'vcal' or 'ical'
	\param		title				Title of export
	\param		desc				Description of export
	\param		events_array		Array of events ('eid','startdate','duration','enddate','title','summary','category','email','url','desc','author')
	\param		outputfile			Output file
	\param		filter				Filter
	\return		int					<0 if ko, Nb of events in file if ok
*/
function build_calfile($format='vcal',$title,$desc,$events_array,$outputfile,$filter='')
{
	dolibarr_syslog("xcal.lib.php::build_cal_file Build cal file ".$outputfile." to format ".$format);

	if (empty($outputfile)) return -1;
	
	$calfileh=fopen($outputfile,'w');
	if ($calfileh)
	{
		$now=mktime();
		
		// Print header
		fwrite($calfileh,"BEGIN:VCALENDAR\n");
		fwrite($calfileh,"VERSION:2.0\n");
		fwrite($calfileh,"METHOD:PUBLISH\n");
		fwrite($calfileh,"PRODID:-//DOLIBARR ".DOL_VERSION."//EN\n");
		fwrite($calfileh,"CALSCALE:GREGORIAN\n");
		fwrite($calfileh,"X-WR-CALNAME:".utf8_encode($title)."\n");
		fwrite($calfileh,"X-WR-CALDESC:".utf8_encode($desc)."\n");
		//fwrite($calfileh,"X-WR-TIMEZONE:Europe/Paris\n");
		
		foreach ($events_array as $date => $event)
		{
			$eventqualified=true;
			if ($filter)
			{
				// \TODO Add a filter

				$eventqualified=false;
			}
			
			if ($eventqualified)
			{
				// See http://fr.wikipedia.org/wiki/ICalendar for format
				//$uid 		= dolibarr_print_date($now,'dayhourxcard').'-'.$event['uid']."-export@".$_SERVER["SERVER_NAME"];
				$uid 		  = $event['uid'];
				$type         = $event['type'];
				$startdate	  = $event['startdate'];
				$duration	  = $event['duration'];
				$enddate	  = $event['enddate'];
				$summary  	  = $event['summary'];
				$category	  = $event['category'];
				$location	  = $event['location'];
				$email 		  = $event['email'];
				$url		  = $event['url'];
				$transparency = $event['transparency'];
				$description=eregi_replace('<br[ \/]?>',"\n",$event['desc']);
 				$description=clean_html($description,0);	// Remove html tags

				// Uncomment for tests
				//$summary="Resume";
				//$description="Description";
				//$description="MemberValidatedInDolibarr gd gdf gd gdff\nNom: tgdf g dfgdf gfd r ter\nType: gdfgfdf dfg fd gfd gd gdf gdf gfd gdfg dfg ddf\nAuteur: AD01fg dgdgdfg df gdf gd";

				// Format
				$summary=format_cal($format,$summary);
				$description=format_cal($format,$description);
				
				$encoding='';
				if ($format == 'vcal') $encoding='ENCODING=QUOTED-PRINTABLE:';
				
				// Output the vCard/iCal VEVENT object
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
					
					fwrite($calfileh,"SUMMARY:".$encoding.$summary."\n");
					fwrite($calfileh,"DESCRIPTION:".$encoding.$description."\n");
					//fwrite($calfileh,'STATUS:CONFIRMED'."\n");
					/*
					statvalue  = "TENTATIVE"           ;Indicates event is
				                                        ;tentative.
				                / "CONFIRMED"           ;Indicates event is
				                                        ;definite.
				                / "CANCELLED"           ;Indicates event was
				                                        ;cancelled.
				        ;Status values for a "VEVENT"

				     statvalue  =/ "NEEDS-ACTION"       ;Indicates to-do needs action.
				                / "COMPLETED"           ;Indicates to-do completed.
				                / "IN-PROCESS"          ;Indicates to-do in process of
				                / "CANCELLED"           ;Indicates to-do was cancelled.
				        ;Status values for "VTODO".

				     statvalue  =/ "DRAFT"              ;Indicates journal is draft.
				                / "FINAL"               ;Indicates journal is final.
				                / "CANCELLED"           ;Indicates journal is removed.
							;Status values for "VJOURNAL".
					*/
					if (! empty($category)) fwrite($calfileh,"CATEGORIES:".$category."\n");
					if (! empty($location)) fwrite($calfileh,"LOCATION:".$location."\n");
					//fwrite($calfileh,"TRANSP:".$transparency."\n");
					//fwrite($calfileh,"CLASS:PUBLIC\n");				// PUBLIC, PRIVATE, CONFIDENTIAL
					fwrite($calfileh,"DTSTAMP:".dolibarr_print_date($now,'dayhourxcard')."\n");
					$startdatef = dolibarr_print_date($startdate,'dayhourxcard');
					fwrite($calfileh,"DTSTART:".$startdatef."\n");
					if (empty($enddate)) $enddate=$startdate+$duration;
					$enddatef = dolibarr_print_date($enddate,'dayhourxcard');
					fwrite($calfileh,"DTEND:".$enddatef."\n");
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
					
					fwrite($calfileh,"SUMMARY:".$encoding.$summary."\n");
					fwrite($calfileh,"DESCRIPTION:".$encoding.$description."\n");
					fwrite($calfileh,'STATUS:CONFIRMED'."\n");
					fwrite($calfileh,"CATEGORIES:".$category."\n");
					fwrite($calfileh,"LOCATION:".$location."\n");
					fwrite($calfileh,"TRANSP:OPAQUE\n");
					fwrite($calfileh,"CLASS:CONFIDENTIAL\n");
					fwrite($calfileh,"DTSTAMP:".dolibarr_print_date($startdatef,'dayhourxcard')."\n");

					fwrite($calfileh,"END:VJOURNAL\n");
				}

				
				// Put other info in comment
				/*
				$comment=array();
				$comment ['eid']			= $eid;
				$comment ['url']			= $linktoevent;
				$comment ['date']			= dolibarr_mktime($evttime,"Ymd");
				$comment ['duration']		= $duration;
				$comment ['startdate']		= $startdate;
				$comment ['enddate']		= $enddate;
				fwrite($calfileh,"COMMENT:" . serialize ($comment) . "\n");
				*/
				
			}
		}

		// Footer
		fwrite($calfileh,"END:VCALENDAR\n");
		
		fclose($calfileh);
	}
	else
	{
		dolibarr_syslog("xcal.lib.php::build_cal_file Failed to open file ".$outputfile." for writing");
		return -2;
	}
}


function format_cal($format,$string)
{
	$newstring=$string;
	
	if ($format == 'vcal')
	{
		$newstring=QPEncode($newstring);
	}
	if ($format == 'ical')
	{
		// Replace new lines chars by '\n'
		$newstring=eregi_replace("\r\n","\n",$newstring);
		$newstring=eregi_replace("\n\r","\n",$newstring);
		$newstring=eregi_replace("\n",'\n',$newstring);
		// Must not exceed 75 char. Cut with "\r\n"+Space
		$newstring=CalEncode($newstring);
	}
	
	return $newstring;
}

/**
	\brief		Cut string after 75 chars. Add CRLF+Space.
	\param		string		String to convert
	\return		string 		String converted
*/
function CalEncode($line)
{
	$out = '';

	$newpara = '';

	for ($j = 0; $j <= strlen($line) - 1; $j++)
	{
		$char = substr ( $line, $j, 1 );

		if ( ( strlen ( $newpara ) + strlen ( $char ) ) >= 75 )
		{
			$out .= $newpara . "\r\n ";	// CRLF + Space for cal
			$newpara = '';
		}
		$newpara .= $char;
	}
	$out .= $newpara;

	return utf8_encode(trim($out));
}


function QPEncode($str,$forcal=0)
{
	$lines = preg_split("/\r\n/", $str);
	$out = '';

	foreach ($lines as $line)
	{
		$newpara = '';

		for ($j = 0; $j <= strlen($line) - 1; $j++)
		{
			$char = substr ( $line, $j, 1 );
			$ascii = ord ( $char );

			if ( $ascii < 32 || $ascii == 61 || $ascii > 126 )
			$char = '=' . strtoupper ( sprintf("%02X", $ascii ) );

			if ( ( strlen ( $newpara ) + strlen ( $char ) ) >= 76 )
			{
				$out .= $newpara . '=' . "\r\n";	// CRLF
				if ($forcal) $out .= " ";		// + Space for cal
				$newpara = '';
			}
			$newpara .= $char;
		}
		$out .= $newpara;
	}
	return trim ( $out );
}

function QPDecode( $str )
{
	$out = preg_replace('/=\r?\n/', '', $str);
	$out = preg_replace('/=([A-F0-9]{2})/e', chr( hexdec ('\\1' ) ), $out);

	return trim($out);
} 

?>