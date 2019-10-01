<?php
/* Lead
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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
include_once DOL_DOCUMENT_ROOT . '/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


/**
 * Class to manage statistics on projects
 */
class ProjectStats extends Stats
{
    private $project;
    public $userid;
    public $socid;
    public $year;

    /**
     * Constructor
     *
     * @param   DoliDB $db     Database handler
     */
    public function __construct($db)
    {
        global $conf, $user;

        $this->db = $db;

        require_once 'project.class.php';
        $this->project = new Project($this->db);
    }


	/**
	 * Return all leads grouped by opportunity status.
	 * Warning: There is no filter on WON/LOST because we want this for statistics.
	 *
	 * @param  int             $limit Limit results
	 * @return array|int       Array with value or -1 if error
	 * @throws Exception
	 */
	public function getAllProjectByStatus($limit = 5)
	{
		global $conf, $user, $langs;

		$datay = array ();

		$sql = "SELECT";
		$sql .= " SUM(t.opp_amount), t.fk_opp_status, cls.code, cls.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->socid)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= ", ".MAIN_DB_PREFIX."c_lead_status as cls";
		$sql .= $this->buildWhere();
		// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
		//if ($socid > 0) $sql.= " AND t.fk_soc = ".$socid;
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
		$sql .= " AND t.fk_opp_status = cls.rowid";
		$sql .= " AND t.fk_statut <> 0";     // We want historic also, so all projects not draft
		$sql .= " GROUP BY t.fk_opp_status, cls.code, cls.label";

		$result = array ();
		$res = array ();

		dol_syslog(get_class($this) . '::' . __METHOD__ . "", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$other = 0;
			while ( $i < $num ) {
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit)
				{
					$label = (($langs->trans("OppStatus".$row[2]) != "OppStatus".$row[2]) ? $langs->trans("OppStatus".$row[2]) : $row[2]);
					$result[$i] = array(
					$label. ' (' . price(price2num($row[0], 'MT'), 1, $langs, 1, -1, -1, $conf->currency) . ')',
					$row[0]
					);
				}
				else
					$other += $row[1];
					$i++;
			}
			if ($num > $limit)
				$result[$i] = array (
				$langs->transnoentitiesnoconv("Other"),
				$other
				);
				$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . '::' . __METHOD__ . ' ' . $this->error, LOG_ERR);
			return -1;
		}

		return $result;
	}

	/**
	 * Return count, and sum of products
	 *
	 * @return array of values
	 */
	public function getAllByYear()
	{
		global $conf, $user, $langs;

		$datay = array ();

		$wonlostfilter=0; // No filter on status WON/LOST

		$sql = "SELECT date_format(t.datec,'%Y') as year, COUNT(t.rowid) as nb, SUM(t.opp_amount) as total, AVG(t.opp_amount) as avg,";
		$sql.= " SUM(t.opp_amount * ".$this->db->ifsql("t.opp_percent IS NULL".($wonlostfilter?" OR cls.code IN ('WON','LOST')":""), '0', 't.opp_percent')." / 100) as weighted";
		$sql.= " FROM " . MAIN_DB_PREFIX . "projet as t LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls ON cls.rowid = t.fk_opp_status";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql.= $this->buildWhere();
		// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
		//if ($socid > 0) $sql.= " AND t.fk_soc = ".$socid;
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
		$sql.= " GROUP BY year";
		$sql.= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}


	/**
	 * Build the where part
	 *
	 * @return string
	 */
	public function buildWhere()
	{
		global $user;

		$sqlwhere_str = '';
		$sqlwhere = array();

		// Get list of project id allowed to user (in a string list separated by coma)
		$object = new Project($this->db);
		$projectsListId='';
		if (! $user->rights->projet->all->lire) $projectsListId = $object->getProjectsAuthorizedForUser($user, 0, 1, $user->socid);

		$sqlwhere[] = ' t.entity IN (' . getEntity('project') . ')';

		if (! empty($this->userid))
			$sqlwhere[] = ' t.fk_user_resp=' . $this->userid;

		// Forced filter on socid is similar to forced filter on project. TODO Use project assignement to allow to not use filter on project
		if (! empty($this->socid))
			$sqlwhere[] = ' t.fk_soc=' . $this->socid;
		if (! empty($this->year) && empty($this->yearmonth))
			$sqlwhere[] = " date_format(t.datec,'%Y')='" . $this->db->escape($this->year) . "'";
		if (! empty($this->yearmonth))
			$sqlwhere[] = " t.datec BETWEEN '" . $this->db->idate(dol_get_first_day($this->yearmonth)) . "' AND '" . $this->db->idate(dol_get_last_day($this->yearmonth)) . "'";

		if (! empty($this->status))
			$sqlwhere[] = " t.fk_opp_status IN (" . $this->status . ")";

		if (! $user->rights->projet->all->lire) $sqlwhere[] = " t.rowid IN (".$projectsListId.")";     // public and assigned to, or restricted to company for external users

		if (count($sqlwhere) > 0) {
			$sqlwhere_str = ' WHERE ' . implode(' AND ', $sqlwhere);
		}

		return $sqlwhere_str;
	}

	/**
	 * Return Project number by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$this->yearmonth = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$this->yearmonth=0;

		$res = $this->_getNbByMonth($year, $sql, $format);
		// var_dump($res);print '<br>';
		return $res;
	}

	/**
	 * Return the Project amount by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array with amount by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		global $user;

		$this->yearmonth = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, SUM(t.opp_amount)";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		$this->yearmonth=0;

		$res = $this->_getAmountByMonth($year, $sql, $format);
		// var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * Return amount of elements by month for several years
	 *
	 * @param	int		$endyear		Start year
	 * @param	int		$startyear		End year
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @param   int     $wonlostfilter  Add a filter on status won/lost
	 * @return 	array					Array of values
	 */
	public function getWeightedAmountByMonthWithPrevYear($endyear, $startyear, $cachedelay = 0, $wonlostfilter = 1)
	{
		global $conf,$user,$langs;

		if ($startyear > $endyear) return -1;

		$datay=array();

		// Search into cache
		if (! empty($cachedelay))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
		}

		$newpathofdestfile=$conf->user->dir_temp.'/'.get_class($this).'_'.__FUNCTION__.'_'.(empty($this->cachefilesuffix)?'':$this->cachefilesuffix.'_').$langs->defaultlang.'_user'.$user->id.'.cache';
		$newmask='0644';

		$nowgmt = dol_now();

		$foundintocache=0;
		if ($cachedelay > 0)
		{
			$filedate=dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay))
			{
				$foundintocache=1;

				$this->lastfetchdate[get_class($this).'_'.__FUNCTION__]=$filedate;
			}
			else
			{
				dol_syslog(get_class($this).'::'.__FUNCTION__." cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
		}

		// Load file into $data
		if ($foundintocache)    // Cache file found and is not too old
		{
			dol_syslog(get_class($this).'::'.__FUNCTION__." read data from cache file ".$newpathofdestfile." ".$filedate.".");
			$data = json_decode(file_get_contents($newpathofdestfile), true);
		}
		else
		{
			$year=$startyear;
			while($year <= $endyear)
			{
				$datay[$year] = $this->getWeightedAmountByMonth($year, $wonlostfilter);
				$year++;
			}

			$data = array();
			// $data = array('xval'=>array(0=>xlabel,1=>yval1,2=>yval2...),...)
			for ($i = 0 ; $i < 12 ; $i++)
			{
				$data[$i][]=$datay[$endyear][$i][0];	// set label
				$year=$startyear;
				while($year <= $endyear)
				{
					$data[$i][]=$datay[$year][$i][1];	// set yval for x=i
					$year++;
				}
			}
		}

		// Save cache file
		if (empty($foundintocache) && ($cachedelay > 0 || $cachedelay == -1))
		{
			dol_syslog(get_class($this).'::'.__FUNCTION__." save cache file ".$newpathofdestfile." onto disk.");
			if (! dol_is_dir($conf->user->dir_temp)) dol_mkdir($conf->user->dir_temp);
			$fp = fopen($newpathofdestfile, 'w');
			if ($fp)
			{
				fwrite($fp, json_encode($data));
				fclose($fp);
				if (! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
				@chmod($newpathofdestfile, octdec($newmask));
			}
			else dol_syslog("Failed to write cache file", LOG_ERR);
			$this->lastfetchdate[get_class($this).'_'.__FUNCTION__]=$nowgmt;
		}

		return $data;
	}


	/**
	 * Return the Project weighted opp amount by month for a year.
	 *
	 * @param  int $year               Year to scan
	 * @param  int $wonlostfilter      Add a filter on status won/lost
	 * @return array                   Array with amount by month
	 */
	public function getWeightedAmountByMonth($year, $wonlostfilter = 1)
	{
		global $user;

		$this->yearmonth = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, SUM(t.opp_amount * ".$this->db->ifsql("t.opp_percent IS NULL".($wonlostfilter?" OR cls.code IN ('WON','LOST')":""), '0', 't.opp_percent')." / 100)";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t LEFT JOIN ".MAIN_DB_PREFIX.'c_lead_status as cls ON t.fk_opp_status = cls.rowid';
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		$this->yearmonth=0;

		$res = $this->_getAmountByMonth($year, $sql);
		// var_dump($res);print '<br>';
		return $res;
	}

	/**
	 * Return amount of elements by month for several years
	 *
	 * @param int $endyear		End year
	 * @param int $startyear	Start year
	 * @param int $cachedelay accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @return array of values
	 */
	public function getTransformRateByMonthWithPrevYear($endyear, $startyear, $cachedelay = 0)
	{
		global $conf, $user, $langs;

		if ($startyear > $endyear) return - 1;

		$datay = array();

		// Search into cache
		if (! empty($cachedelay))
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			include_once DOL_DOCUMENT_ROOT . '/core/lib/json.lib.php';
		}

		$newpathofdestfile = $conf->user->dir_temp . '/' . get_class($this) . '_' . __FUNCTION__ . '_' . (empty($this->cachefilesuffix) ? '' : $this->cachefilesuffix . '_') . $langs->defaultlang . '_user' . $user->id . '.cache';
		$newmask = '0644';

		$nowgmt = dol_now();

		$foundintocache = 0;
		if ($cachedelay > 0) {
			$filedate = dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay)) {
				$foundintocache = 1;

				$this->lastfetchdate[get_class($this) . '_' . __FUNCTION__] = $filedate;
			} else {
				dol_syslog(get_class($this) . '::' . __FUNCTION__ . " cache file " . $newpathofdestfile . " is not found or older than now - cachedelay (" . $nowgmt . " - " . $cachedelay . ") so we can't use it.");
			}
		}

		// Load file into $data
		if ($foundintocache) // Cache file found and is not too old
		{
			dol_syslog(get_class($this) . '::' . __FUNCTION__ . " read data from cache file " . $newpathofdestfile . " " . $filedate . ".");
			$data = json_decode(file_get_contents($newpathofdestfile), true);
		} else {
			$year = $startyear;
			while ( $year <= $endyear ) {
				$datay[$year] = $this->getTransformRateByMonth($year);
				$year ++;
			}

			$data = array ();
			// $data = array('xval'=>array(0=>xlabel,1=>yval1,2=>yval2...),...)
			for($i = 0; $i < 12; $i ++) {
				$data[$i][] = $datay[$endyear][$i][0]; // set label
				$year = $startyear;
				while ( $year <= $endyear ) {
					$data[$i][] = $datay[$year][$i][1]; // set yval for x=i
					$year ++;
				}
			}
		}

		// Save cache file
		if (empty($foundintocache) && ($cachedelay > 0 || $cachedelay == - 1)) {
			dol_syslog(get_class($this) . '::' . __FUNCTION__ . " save cache file " . $newpathofdestfile . " onto disk.");
			if (! dol_is_dir($conf->user->dir_temp))
				dol_mkdir($conf->user->dir_temp);
			$fp = fopen($newpathofdestfile, 'w');
			fwrite($fp, json_encode($data));
			fclose($fp);
			if (! empty($conf->global->MAIN_UMASK))
				$newmask = $conf->global->MAIN_UMASK;
			@chmod($newpathofdestfile, octdec($newmask));

			$this->lastfetchdate[get_class($this) . '_' . __FUNCTION__] = $nowgmt;
		}

		return $data;
	}

	/**
	 * Return the Project transformation rate by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array with amount by month
	 */
	public function getTransformRateByMonth($year, $format = 0)
	{
		global $user;

		$this->yearmonth = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, count(t.opp_amount)";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res_total = $this->_getNbByMonth($year, $sql, $format);

		$this->status=6;

		$sql = "SELECT date_format(t.datec,'%m') as dm, count(t.opp_amount)";
		$sql .= " FROM " . MAIN_DB_PREFIX . "projet as t";
		// No check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $user->soc_id)
		//	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=" . $user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$this->status=0;
		$this->yearmonth=0;

		$res_only_wined = $this->_getNbByMonth($year, $sql, $format);

		$res=array();

		foreach($res_total as $key=>$total_row) {
			//var_dump($total_row);
			if (!empty($total_row[1])) {
				$res[$key]=array($total_row[0],(100*$res_only_wined[$key][1])/$total_row[1]);
			} else {
				$res[$key]=array($total_row[0],0);
			}
		}
		// var_dump($res);print '<br>';
		return $res;
	}
}
