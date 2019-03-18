<?php
/* Copyright (C) 2018	Destailleur Laurent	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/dav/dav.class.php
 *      \ingroup    dav
 *      \brief      Server DAV
 */


/**
 * Define Common function to access calendar items and format it in vCalendar
 */
class CdavLib
{

	private $db;

	private $user;

	private $langs;

    /**
     * Constructor
     *
     * @param   User        $user   user
     * @param   DoliDB      $db     Database handler
     * @param   Translate   $langs  translation
     */
    public function __construct($user, $db, $langs)
	{
		$this->user = $user;
		$this->db = $db;
		$this->langs = $langs;
	}

	/**
	 * Base sql request for calendar events
	 *
	 * @param 	int 		$calid 			Calendard id
	 * @param 	int|boolean	$oid			Oid
	 * @param	int|boolean	$ouri			Ouri
	 * @return string
	 */
	public function getSqlCalEvents($calid, $oid = false, $ouri = false)
	{
		// TODO : replace GROUP_CONCAT by
		$sql = 'SELECT
					a.tms AS lastupd,
					a.*,
					sp.firstname,
					sp.lastname,
					sp.address,
					sp.zip,
					sp.town,
					co.label country_label,
					sp.phone,
					sp.phone_perso,
					sp.phone_mobile,
					s.nom AS soc_nom,
					s.address soc_address,
					s.zip soc_zip,
					s.town soc_town,
					cos.label soc_country_label,
					s.phone soc_phone,
					ac.sourceuid,
					(SELECT GROUP_CONCAT(u.login) FROM '.MAIN_DB_PREFIX.'actioncomm_resources ar
						LEFT OUTER JOIN '.MAIN_DB_PREFIX.'user AS u ON (u.rowid=fk_element)
						WHERE ar.element_type=\'user\' AND fk_actioncomm=a.id) AS other_users
				FROM '.MAIN_DB_PREFIX.'actioncomm AS a';
		if (! $this->user->rights->societe->client->voir )//FIXME si 'voir' on voit plus de chose ?
		{
			$sql.=' LEFT OUTER JOIN '.MAIN_DB_PREFIX.'societe_commerciaux AS sc ON (a.fk_soc = sc.fk_soc AND sc.fk_user='.$this->user->id.')
					LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON (s.rowid = sc.fk_soc)
					LEFT JOIN '.MAIN_DB_PREFIX.'socpeople AS sp ON (sp.fk_soc = sc.fk_soc AND sp.rowid = a.fk_contact)
					LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_cdav AS ac ON (a.id = ac.fk_object)';
		}
		else
		{
			$sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON (s.rowid = a.fk_soc)
					LEFT JOIN '.MAIN_DB_PREFIX.'socpeople AS sp ON (sp.rowid = a.fk_contact)
					LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_cdav AS ac ON (a.id = ac.fk_object)';
		}

		$sql.=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON co.rowid = sp.fk_pays
				LEFT JOIN '.MAIN_DB_PREFIX.'c_country as cos ON cos.rowid = s.fk_pays
				WHERE 	a.id IN (SELECT ar.fk_actioncomm FROM '.MAIN_DB_PREFIX.'actioncomm_resources ar WHERE ar.element_type=\'user\' AND ar.fk_element='.intval($calid).')
						AND a.code IN (SELECT cac.code FROM '.MAIN_DB_PREFIX.'c_actioncomm cac WHERE cac.type<>\'systemauto\')
						AND a.entity IN ('.getEntity('societe', 1).')';
		if($oid!==false) {
			if($ouri===false)
			{
				$sql.=' AND a.id = '.intval($oid);
			}
			else
			{
				$sql.=' AND (a.id = '.intval($oid).' OR ac.uuidext = \''.$this->db->escape($ouri).'\')';
			}
		}

		return $sql;
	}

	/**
	 * Convert calendar row to VCalendar string
	 *
	 * @param 	int		$calid		Calendar id
	 * @param	Object	$obj		Object id
	 * @return string
	 */
	public function toVCalendar($calid, $obj)
	{
		/*$categ = array();
		if($obj->soc_client)
		{
			$nick[] = $obj->soc_code_client;
			$categ[] = $this->langs->transnoentitiesnoconv('Customer');
		}*/

		$location=$obj->location;

		// contact address
		if(empty($location) && !empty($obj->address))
		{
			$location = trim(str_replace(array("\r","\t","\n"), ' ', $obj->address));
			$location = trim($location.', '.$obj->zip);
			$location = trim($location.' '.$obj->town);
			$location = trim($location.', '.$obj->country_label);
		}

		// contact address
		if(empty($location) && !empty($obj->soc_address))
		{
			$location = trim(str_replace(array("\r","\t","\n"), ' ', $obj->soc_address));
			$location = trim($location.', '.$obj->soc_zip);
			$location = trim($location.' '.$obj->soc_town);
			$location = trim($location.', '.$obj->soc_country_label);
		}

		$address=explode("\n", $obj->address, 2);
		foreach($address as $kAddr => $vAddr)
		{
			$address[$kAddr] = trim(str_replace(array("\r","\t"), ' ', str_replace("\n", ' | ', trim($vAddr))));
		}
		$address[]='';
		$address[]='';

		if($obj->percent==-1 && trim($obj->datep)!='')
			$type='VEVENT';
		else
			$type='VTODO';

		$timezone = date_default_timezone_get();

		$caldata ="BEGIN:VCALENDAR\n";
		$caldata.="VERSION:2.0\n";
		$caldata.="METHOD:PUBLISH\n";
		$caldata.="PRODID:-//Dolibarr CDav//FR\n";
		$caldata.="BEGIN:".$type."\n";
		$caldata.="CREATED:".gmdate('Ymd\THis', strtotime($obj->datec))."Z\n";
		$caldata.="LAST-MODIFIED:".gmdate('Ymd\THis', strtotime($obj->lastupd))."Z\n";
		$caldata.="DTSTAMP:".gmdate('Ymd\THis', strtotime($obj->lastupd))."Z\n";
		if($obj->sourceuid=='')
			$caldata.="UID:".$obj->id.'-ev-'.$calid.'-cal-'.CDAV_URI_KEY."\n";
		else
			$caldata.="UID:".$obj->sourceuid."\n";
		$caldata.="SUMMARY:".$obj->label."\n";
		$caldata.="LOCATION:".$location."\n";
		$caldata.="PRIORITY:".$obj->priority."\n";
		if($obj->fulldayevent)
		{
			$caldata.="DTSTART;VALUE=DATE:".date('Ymd', strtotime($obj->datep))."\n";
			if($type=='VEVENT')
			{
				if(trim($obj->datep2)!='')
					$caldata.="DTEND;VALUE=DATE:".date('Ymd', strtotime($obj->datep2)+1)."\n";
				else
					$caldata.="DTEND;VALUE=DATE:".date('Ymd', strtotime($obj->datep)+(25*3600))."\n";
			}
			elseif(trim($obj->datep2)!='')
				$caldata.="DUE;VALUE=DATE:".date('Ymd', strtotime($obj->datep2)+1)."\n";
		}
		else
		{
			$caldata.="DTSTART;TZID=".$timezone.":".strtr($obj->datep, array(" "=>"T", ":"=>"", "-"=>""))."\n";
			if($type=='VEVENT')
			{
				if(trim($obj->datep2)!='')
					$caldata.="DTEND;TZID=".$timezone.":".strtr($obj->datep2, array(" "=>"T", ":"=>"", "-"=>""))."\n";
				else
					$caldata.="DTEND;TZID=".$timezone.":".strtr($obj->datep, array(" "=>"T", ":"=>"", "-"=>""))."\n";
			}
			elseif(trim($obj->datep2)!='')
				$caldata.="DUE;TZID=".$timezone.":".strtr($obj->datep2, array(" "=>"T", ":"=>"", "-"=>""))."\n";
		}
		$caldata.="CLASS:PUBLIC\n";
		if($obj->transparency==1)
			$caldata.="TRANSP:TRANSPARENT\n";
		else
			$caldata.="TRANSP:OPAQUE\n";

		if($type=='VEVENT')
			$caldata.="STATUS:CONFIRMED\n";
		elseif($obj->percent==0)
			$caldata.="STATUS:NEEDS-ACTION\n";
		elseif($obj->percent==100)
			$caldata.="STATUS:COMPLETED\n";
		else
		{
			$caldata.="STATUS:IN-PROCESS\n";
			$caldata.="PERCENT-COMPLETE:".$obj->percent."\n";
		}

		$caldata.="DESCRIPTION:";
		$caldata.=strtr($obj->note, array("\n"=>"\\n", "\r"=>""));
		if(!empty($obj->soc_nom))
			$caldata.="\\n*DOLIBARR-SOC: ".$obj->soc_nom;
		if(!empty($obj->soc_phone))
			$caldata.="\\n*DOLIBARR-SOC-TEL: ".$obj->soc_phone;
		if(!empty($obj->firstname) || !empty($obj->lastname))
			$caldata.="\\n*DOLIBARR-CTC: ".trim($obj->firstname.' '.$obj->lastname);
		if(!empty($obj->phone) || !empty($obj->phone_perso) || !empty($obj->phone_mobile))
			$caldata.="\\n*DOLIBARR-CTC-TEL: ".trim($obj->phone.' '.$obj->phone_perso.' '.$obj->phone_mobile);
		if(strpos($obj->other_users, ',')) // several
			$caldata.="\\n*DOLIBARR-USR: ".$obj->other_users;
		$caldata.="\n";

		$caldata.="END:".$type."\n";
		$caldata.="END:VCALENDAR\n";

		return $caldata;
	}

    /**
     * getFullCalendarObjects
     *
     * @param int	 	$calendarId			Calendar id
     * @param int		$bCalendarData		Add calendar data
     * @return array|string[][]
     */
    public function getFullCalendarObjects($calendarId, $bCalendarData)
    {
		$calid = ($calendarId*1);
		$calevents = array();

		if(! $this->user->rights->agenda->myactions->read)
			return $calevents;

		if($calid!=$this->user->id && (!isset($this->user->rights->agenda->allactions->read) || !$this->user->rights->agenda->allactions->read))
			return $calevents;

		$sql = $this->getSqlCalEvents($calid);

		$result = $this->db->query($sql);

		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				$calendardata = $this->toVCalendar($calid, $obj);

				if($bCalendarData)
				{
					$calevents[] = array(
						'calendardata' => $calendardata,
						'uri' => $obj->id.'-ev-'.CDAV_URI_KEY,
						'lastmodified' => strtotime($obj->lastupd),
						'etag' => '"'.md5($calendardata).'"',
						'calendarid'   => $calendarId,
						'size' => strlen($calendardata),
						'component' => strpos($calendardata, 'BEGIN:VEVENT')>0 ? 'vevent' : 'vtodo',
					);
				}
				else
				{
					$calevents[] = array(
						// 'calendardata' => $calendardata,  not necessary because etag+size are present
						'uri' => $obj->id.'-ev-'.CDAV_URI_KEY,
						'lastmodified' => strtotime($obj->lastupd),
						'etag' => '"'.md5($calendardata).'"',
						'calendarid'   => $calendarId,
						'size' => strlen($calendardata),
						'component' => strpos($calendardata, 'BEGIN:VEVENT')>0 ? 'vevent' : 'vtodo',
					);
				}
			}
		}
		return $calevents;
	}
}
