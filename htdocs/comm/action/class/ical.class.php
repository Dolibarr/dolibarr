<?php
/* Copyright (C) 2006	Roman Ozana			<ozana@omdesign.cz>
 * Copyright (C) 2011	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
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
 *       \file       htdocs/comm/action/class/ical.class.php
 *       \ingroup    commercial
 *       \brief      File of class to parse ical calendars
 */
class ical
{
    var $file_text; // Text in file
    var $cal; // Array to save iCalendar parse data
    var $event_count; // Number of Events
    var $todo_count; // Number of Todos
    var $freebusy_count; // Number of Freebusy
    var $last_key; //Help variable save last key (multiline string)


	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
     * Read text file, icalender text file
     *
     * @param 	string 	$file		File
     * @return	string
     */
    function read_file($file)
    {
        $this->file = $file;
        $file_text = join("", file($file)); //load file
        $file_text = preg_replace("/[\r\n]{1,} ([:;])/","\\1",$file_text);

        return $file_text; // return all text
    }

    /**
     * Returns the number of calendar events
     *
     * @return int
     */
    function get_event_count()
    {
        return $this->event_count;
    }

    /**
     * Returns the number of to do
     *
     * @return int
     */
    function get_todo_count()
    {
        return $this->todo_count;
    }

    /**
     * Translate Calendar
     *
     * @param	string 	$uri	Url
     * @return	array
     */
    function parse($uri)
    {
        $this->cal = array(); // new empty array

        $this->event_count = -1;

        // read FILE text
        $this->file_text = $this->read_file($uri);

        $this->file_text = preg_split("[\n]", $this->file_text);

        // is this text vcalendar standart text ? on line 1 is BEGIN:VCALENDAR
        if (!stristr($this->file_text[0],'BEGIN:VCALENDAR')) return 'error not VCALENDAR';

        $insidealarm=0;
        foreach ($this->file_text as $text)
        {
            $text = trim($text); // trim one line
            if (!empty($text))
            {
                // get Key and Value VCALENDAR:Begin -> Key = VCALENDAR, Value = begin
                list($key, $value) = $this->retun_key_value($text);

                switch ($text) // search special string
                {
                    case "BEGIN:VTODO":
                        $this->todo_count = $this->todo_count+1; // new to do begin
                        $type = "VTODO";
                        break;

                    case "BEGIN:VEVENT":
                        $this->event_count = $this->event_count+1; // new event begin
                        $type = "VEVENT";
                        break;

                    case "BEGIN:VFREEBUSY":
                        $this->freebusy_count = $this->freebusy_count+1; // new event begin
                        $type = "VFREEBUSY";
                        break;

                    case "BEGIN:VCALENDAR": // all other special string
                    case "BEGIN:DAYLIGHT":
                    case "BEGIN:VTIMEZONE":
                    case "BEGIN:STANDARD":
                        $type = $value; // save tu array under value key
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
                        $insidealarm=1;
                        break;
                    case "END:VALARM":
                        $insidealarm=0;
                        break;

                    default: // no special string
                        if (! $insidealarm) $this->add_to_array($type, $key, $value); // add to array
                        break;
                }
            }
        }
        return $this->cal;
    }

    /**
     * Add to $this->ical array one value and key.
     *
     * @param 	string 	$type		Type ('VTODO', 'VEVENT', 'VFREEBUSY', 'VCALENDAR'...)
     * @param 	string 	$key		Key	('DTSTART', ...). Note: Field is never 'DTSTART;TZID=...' because ';...' was before removed and added as another property
     * @param 	string 	$value		Value
     * @return	void
     */
    function add_to_array($type, $key, $value)
    {

        //print 'type='.$type.' key='.$key.' value='.$value.'<br>'."\n";

        if ($key == false)
        {
            $key = $this->last_key;
            switch ($type)
            {
                case 'VEVENT': $value = $this->cal[$type][$this->event_count][$key].$value;break;
                case 'VFREEBUSY': $value = $this->cal[$type][$this->freebusy_count][$key].$value;break;
                case 'VTODO': $value = $this->cal[$type][$this->todo_count][$key].$value;break;
            }
        }

        if (($key == "DTSTAMP") or ($key == "LAST-MODIFIED") or ($key == "CREATED")) $value = $this->ical_date_to_unix($value);
        if ($key == "RRULE" ) $value = $this->ical_rrule($value);

        if (stristr($key,"DTSTART") or stristr($key,"DTEND") or stristr($key,"DTSTART;VALUE=DATE") or stristr($key,"DTEND;VALUE=DATE"))
        {
        	if (stristr($key,"DTSTART;VALUE=DATE") or stristr($key,"DTEND;VALUE=DATE"))
        	{
        		list($key,$value) = array($key,$value);
        	}
        	else
        	{
        		list($key,$value) = $this->ical_dt_date($key,$value);
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

    /**
     * Parse text "XXXX:value text some with : " and return array($key = "XXXX", $value="value");
     *
     * @param 	string 	$text	Text
     * @return 	array
     */
    function retun_key_value($text)
    {
        preg_match("/([^:]+)[:]([\w\W]+)/", $text, $matches);

        if (empty($matches))
        {
            return array(false,$text);
        }
        else
        {
            $matches = array_splice($matches, 1, 2);
            return $matches;
        }

    }

    /**
     * Parse RRULE  return array
     *
     * @param 	string 	$value	string
     * @return 	array
     */
    function ical_rrule($value)
    {
        $rrule = explode(';',$value);
        foreach ($rrule as $line)
        {
            $rcontent = explode('=', $line);
            $result[$rcontent[0]] = $rcontent[1];
        }
        return $result;
    }
    /**
     * Return Unix time from ical date time fomrat (YYYYMMDD[T]HHMMSS[Z] or YYYYMMDD[T]HHMMSS)
     *
     * @param 	string		$ical_date		String date
     * @return 	timestamp
     */
    function ical_date_to_unix($ical_date)
    {
        $ical_date = str_replace('T', '', $ical_date);
        $ical_date = str_replace('Z', '', $ical_date);

        $ntime=0;
        // TIME LIMITED EVENT
        if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $ical_date, $date))
            $ntime=dol_mktime($date[4], $date[5], $date[6], $date[2],$date[3], $date[1], true);

        //if (empty($date[4])) print 'Error bad date: '.$ical_date.' - date1='.$date[1];
        //print dol_print_date($ntime,'dayhour');exit;
        return $ntime;      // ntime is a GTM time
    }

    /**
     * Return unix date from iCal date format
     *
     * @param 	string 		$key			Key
     * @param 	string 		$value			Value
     * @return 	array
     */
    function ical_dt_date($key, $value)
    {
        $value = $this->ical_date_to_unix($value);

        // Analyse TZID
        $temp = explode(";",$key);

        if (empty($temp[1])) // not TZID
        {
            $value = str_replace('T', '', $value);
            return array($key,$value);
        }
        // adding $value and $tzid
        $key =     $temp[0];
        $temp = explode("=", $temp[1]);
        $return_value[$temp[0]] = $temp[1];
        $return_value['unixtime'] = $value;

        return array($key,$return_value);
    }

    /**
     * Return sorted eventlist as array or false if calenar is empty
     *
     * @return array
     */
    function get_sort_event_list()
    {
        $temp = $this->get_event_list();
        if (!empty($temp))
        {
            usort($temp, array(&$this, "ical_dtstart_compare"));
            return $temp;
        } else
        {
            return false;
        }
    }

    /**
     * Compare two unix timestamp
     *
     * @param 	array 	$a		Operand a
     * @param 	array 	$b		Operand b
     * @return 	integer
     */
    function ical_dtstart_compare($a, $b)
    {
        return strnatcasecmp($a['DTSTART']['unixtime'], $b['DTSTART']['unixtime']);
    }

    /**
     * Return eventlist array (not sort eventlist array)
     *
     * @return array
     */
    function get_event_list()
    {
        return (! empty($this->cal['VEVENT'])?$this->cal['VEVENT']:'');
    }

    /**
     * Return eventlist array (not sort eventlist array)
     *
     * @return array
     */
    function get_freebusy_list()
    {
        return $this->cal['VFREEBUSY'];
    }

    /**
     * Return to do array (not sort to do array)
     *
     * @return array
     */
    function get_todo_list()
    {
        return $this->cal['VTODO'];
    }

    /**
     * Return base calendar data
     *
     * @return array
     */
    function get_calender_data()
    {
        return $this->cal['VCALENDAR'];
    }

    /**
     * Return array with all data
     *
     * @return array
     */
    function get_all_data()
    {
        return $this->cal;
    }
}
?>