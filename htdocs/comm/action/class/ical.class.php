<?php
/* Copyright (C) 2006      Roman Ozana			<ozana@omdesign.cz>
 * Copyright (C) 2011	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2014 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2019       Frédéric France     <frederic.france@netlogic.fr>
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


/**
 *  Class to read/parse ICal calendars
 */
class ICal
{
    // Text in file
    public $file_text;
    public $cal; // Array to save iCalendar parse data
    public $event_count; // Number of Events
    public $todo_count; // Number of Todos
    public $freebusy_count; // Number of Freebusy
    public $last_key; //Help variable save last key (multiline string)


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
     *  @param  string  $file       File
     *  @return string
     */
    public function read_file($file)
    {
        // phpcs:enable
        $this->file = $file;
        $file_text = '';

        $tmparray = file($file);
        if (is_array($tmparray))
        {
            $file_text = join("", $tmparray); //load file
            $file_text = preg_replace("/[\r\n]{1,} /", "", $file_text);
        }
        return $file_text; // return all text
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
     * Translate Calendar
     *
     * @param	string 	$uri	Url
     * @return	array|string
     */
    public function parse($uri)
    {
        $this->cal = array(); // new empty array

        $this->event_count = -1;

        // read FILE text
        $this->file_text = $this->read_file($uri);

        $this->file_text = preg_split("[\n]", $this->file_text);

        // is this text vcalendar standard text ? on line 1 is BEGIN:VCALENDAR
        if (!stristr($this->file_text[0], 'BEGIN:VCALENDAR')) return 'error not VCALENDAR';

        $insidealarm = 0;
        $tmpkey = ''; $tmpvalue = ''; $type = '';
        foreach ($this->file_text as $text)
        {
            $text = trim($text); // trim one line
            if (!empty($text))
            {
                // get Key and Value VCALENDAR:Begin -> Key = VCALENDAR, Value = begin
                list($key, $value) = $this->retun_key_value($text);
                //var_dump($text.' -> '.$key.' - '.$value);

                switch ($text) // search special string
                {
                    case "BEGIN:VTODO":
                        $this->todo_count = $this->todo_count + 1; // new to do begin
                        $type = "VTODO";
                        break;

                    case "BEGIN:VEVENT":
                        $this->event_count = $this->event_count + 1; // new event begin
                        $type = "VEVENT";
                        break;

                    case "BEGIN:VFREEBUSY":
                        $this->freebusy_count = $this->freebusy_count + 1; // new event begin
                        $type = "VFREEBUSY";
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
                    	if ($tmpvalue)
						{
							$tmpvalue .= $text;
							if (!preg_match('/=$/', $text))	// No more lines
							{
								$key = $tmpkey;
								$value = quotedPrintDecode(preg_replace('/^ENCODING=QUOTED-PRINTABLE:/i', '', $tmpvalue));
								$tmpkey = '';
								$tmpvalue = '';
							}
						}
                    	elseif (preg_match('/^ENCODING=QUOTED-PRINTABLE:/i', $value))
                    	{
                    		if (preg_match('/=$/', $value))
                    		{
                    			$tmpkey = $key;
                    			$tmpvalue = $tmpvalue.preg_replace('/=$/', "", $value); // We must wait to have next line to have complete message
                    		}
                    		else
                    		{
                    			$value = quotedPrintDecode(preg_replace('/^ENCODING=QUOTED-PRINTABLE:/i', '', $tmpvalue.$value));
                    		}
                    	}                    	//$value=quotedPrintDecode($tmpvalue.$value);
                    	if (!$insidealarm && !$tmpkey) $this->add_to_array($type, $key, $value); // add to array
                        break;
                }
            }
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

        if (empty($key))
        {
            $key = $this->last_key;
            switch ($type)
            {
                case 'VEVENT': $value = $this->cal[$type][$this->event_count][$key].$value; break;
                case 'VFREEBUSY': $value = $this->cal[$type][$this->freebusy_count][$key].$value; break;
                case 'VTODO': $value = $this->cal[$type][$this->todo_count][$key].$value; break;
            }
        }

        if (($key == "DTSTAMP") || ($key == "LAST-MODIFIED") || ($key == "CREATED")) $value = $this->ical_date_to_unix($value);
        //if ($key == "RRULE" ) $value = $this->ical_rrule($value);

        if (stristr($key, "DTSTART") || stristr($key, "DTEND") || stristr($key, "DTSTART;VALUE=DATE") || stristr($key, "DTEND;VALUE=DATE"))
        {
        	if (stristr($key, "DTSTART;VALUE=DATE") || stristr($key, "DTEND;VALUE=DATE"))
        	{
        		list($key, $value) = array($key, $value);
        	}
        	else
        	{
        		list($key, $value) = $this->ical_dt_date($key, $value);
        	}
        }

        switch ($type)
        {
            case "VTODO":
                $this->cal[$type][$this->todo_count][$key] = $value;
                break;

            case "VEVENT":
                $this->cal[$type][$this->event_count][$key] = $value;
                break;

            case "VFREEBUSY":
                $this->cal[$type][$this->freebusy_count][$key] = $value;
                break;

            default:
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
     * @return 	array
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
     * @return 	array
     */
    public function ical_rrule($value)
    {
        // phpcs:enable
        $result = array();
        $rrule = explode(';', $value);
        foreach ($rrule as $line)
        {
            $rcontent = explode('=', $line);
            $result[$rcontent[0]] = $rcontent[1];
        }
        return $result;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return Unix time from ical date time fomrat (YYYYMMDD[T]HHMMSS[Z] or YYYYMMDD[T]HHMMSS)
     *
     * @param 	string		$ical_date		String date
     * @return 	int
     */
    public function ical_date_to_unix($ical_date)
    {
        // phpcs:enable
        $ical_date = str_replace('T', '', $ical_date);
        $ical_date = str_replace('Z', '', $ical_date);

        $ntime = 0;
        // TIME LIMITED EVENT
        if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $ical_date, $date))
            $ntime = dol_mktime($date[4], $date[5], $date[6], $date[2], $date[3], $date[1], true);

        //if (empty($date[4])) print 'Error bad date: '.$ical_date.' - date1='.$date[1];
        //print dol_print_date($ntime,'dayhour');exit;
        return $ntime; // ntime is a GTM time
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return unix date from iCal date format
     *
     * @param 	string 		$key			Key
     * @param 	string 		$value			Value
     * @return 	array
     */
    public function ical_dt_date($key, $value)
    {
        // phpcs:enable
        $return_value = array();
        $value = $this->ical_date_to_unix($value);

        // Analyse TZID
        $temp = explode(";", $key);

        if (empty($temp[1])) // not TZID
        {
            $value = str_replace('T', '', $value);
            return array($key, $value);
        }

        $key = $temp[0];
        $temp = explode("=", $temp[1]);
        $return_value[$temp[0]] = $temp[1];
        $return_value['unixtime'] = $value;

        return array($key, $return_value);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return sorted eventlist as array or false if calendar is empty
     *
     * @return array|false
     */
    public function get_sort_event_list()
    {
        // phpcs:enable
        $temp = $this->get_event_list();
        if (!empty($temp))
        {
            usort($temp, array(&$this, "ical_dtstart_compare"));
            return $temp;
        }
        else
        {
            return false;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Compare two unix timestamp
     *
     * @param 	array 	$a		Operand a
     * @param 	array 	$b		Operand b
     * @return 	integer
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
     * @return array
     */
    public function get_event_list()
    {
        // phpcs:enable
        return (!empty($this->cal['VEVENT']) ? $this->cal['VEVENT'] : '');
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return eventlist array (not sort eventlist array)
     *
     * @return array
     */
    public function get_freebusy_list()
    {
        // phpcs:enable
        return $this->cal['VFREEBUSY'];
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Return to do array (not sorted todo array)
     *
     * @return array
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
     * @return array
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
     * @return array
     */
    public function get_all_data()
    {
        // phpcs:enable
        return $this->cal;
    }
}
