<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2008-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/class/stats.class.php
 *  \ingroup    core
 *  \brief      Common class to manage statistics reports
 */

/**
 * 	Parent class of statistics class
 */
abstract class Stats
{
	protected $db;
	protected $lastfetchdate = array(); // Dates of cache file read by methods
	public $cachefilesuffix = ''; // Suffix to add to name of cache file (to avoid file name conflicts)

	/**
	 *  @param	int		$year 			number
	 * 	@param	int 	$format 		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * 	@return int						value
	 */
	protected abstract function getNbByMonth($year, $format = 0);

	/**
	 * Return nb of elements by month for several years
	 *
	 * @param 	int		$endyear		Start year
	 * @param 	int		$startyear		End year
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @param	int		$format			0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @param   int 	$startmonth		month of the fiscal year start min 1 max 12 ; if 1 = january
	 * @return 	array					Array of values
	 */
	public function getNbByMonthWithPrevYear($endyear, $startyear, $cachedelay = 0, $format = 0, $startmonth = 1)
	{
		global $conf, $user, $langs;

		if ($startyear > $endyear) {
			return -1;
		}

		$datay = array();

		// Search into cache
		if (!empty($cachedelay)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
		}

		$newpathofdestfile = $conf->user->dir_temp.'/'.get_class($this).'_'.__FUNCTION__.'_'.(empty($this->cachefilesuffix) ? '' : $this->cachefilesuffix.'_').$langs->defaultlang.'_entity.'.$conf->entity.'_user'.$user->id.'.cache';
		$newmask = '0644';

		$nowgmt = dol_now();

		$foundintocache = 0;
		if ($cachedelay > 0) {
			$filedate = dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay)) {
				$foundintocache = 1;

				$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $filedate;
			} else {
				dol_syslog(get_class($this).'::'.__FUNCTION__." cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
		}
		// Load file into $data
		if ($foundintocache) {    // Cache file found and is not too old
			dol_syslog(get_class($this).'::'.__FUNCTION__." read data from cache file ".$newpathofdestfile." ".$filedate.".");
			$data = json_decode(file_get_contents($newpathofdestfile), true);
		} else {
			$year = $startyear;
			$sm = $startmonth - 1;
			if ($sm != 0) {
				$year = $year - 1;
			}
			while ($year <= $endyear) {
				$datay[$year] = $this->getNbByMonth($year, $format);
				$year++;
			}

			$data = array();

			for ($i = 0; $i < 12; $i++) {
				$data[$i][] = $datay[$endyear][($i + $sm) % 12][0];
				$year = $startyear;
				while ($year <= $endyear) {
					$data[$i][] = $datay[$year - (1 - ((int) ($i + $sm) / 12)) + ($sm == 0 ? 1 : 0)][($i + $sm) % 12][1];
					$year++;
				}
			}
		}

		// Save cache file
		if (empty($foundintocache) && ($cachedelay > 0 || $cachedelay == -1)) {
			dol_syslog(get_class($this).'::'.__FUNCTION__." save cache file ".$newpathofdestfile." onto disk.");
			if (!dol_is_dir($conf->user->dir_temp)) {
				dol_mkdir($conf->user->dir_temp);
			}
			$fp = fopen($newpathofdestfile, 'w');
			fwrite($fp, json_encode($data));
			fclose($fp);
			if (!empty($conf->global->MAIN_UMASK)) {
				$newmask = $conf->global->MAIN_UMASK;
			}
			@chmod($newpathofdestfile, octdec($newmask));

			$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $nowgmt;
		}

		// return array(array('Month',val1,val2,val3),...)
		return $data;
	}

	/**
	 * @param	int		$year			year number
	 * @param	int 	$format			0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	int						value
	 */
	protected abstract function getAmountByMonth($year, $format = 0);

	/**
	 * Return amount of elements by month for several years.
	 * Criterias used to build request are defined into the constructor of parent class into xxx/class/xxxstats.class.php
	 * The caller of class can add more filters into sql request by adding criteris into the $stats->where property just after
	 * calling constructor.
	 *
	 * @param	int		$endyear		Start year
	 * @param	int		$startyear		End year
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @param	int		$format			0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @param   int 	$startmonth		month of the fiscal year start min 1 max 12 ; if 1 = january
	 * @return 	array					Array of values
	 */
	public function getAmountByMonthWithPrevYear($endyear, $startyear, $cachedelay = 0, $format = 0, $startmonth = 1)
	{
		global $conf, $user, $langs;

		if ($startyear > $endyear) {
			return -1;
		}

		$datay = array();

		// Search into cache
		if (!empty($cachedelay)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
		}

		$newpathofdestfile = $conf->user->dir_temp.'/'.get_class($this).'_'.__FUNCTION__.'_'.(empty($this->cachefilesuffix) ? '' : $this->cachefilesuffix.'_').$langs->defaultlang.'_entity.'.$conf->entity.'_user'.$user->id.'.cache';
		$newmask = '0644';

		$nowgmt = dol_now();

		$foundintocache = 0;
		if ($cachedelay > 0) {
			$filedate = dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay)) {
				$foundintocache = 1;

				$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $filedate;
			} else {
				dol_syslog(get_class($this).'::'.__FUNCTION__." cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
		}

		// Load file into $data
		if ($foundintocache) {    // Cache file found and is not too old
			dol_syslog(get_class($this).'::'.__FUNCTION__." read data from cache file ".$newpathofdestfile." ".$filedate.".");
			$data = json_decode(file_get_contents($newpathofdestfile), true);
		} else {
			$year = $startyear;
			$sm = $startmonth - 1;
			if ($sm != 0) {
				$year = $year - 1;
			}
			while ($year <= $endyear) {
				$datay[$year] = $this->getAmountByMonth($year, $format);
				$year++;
			}

			$data = array();
			// $data = array('xval'=>array(0=>xlabel,1=>yval1,2=>yval2...),...)
			for ($i = 0; $i < 12; $i++) {
				$data[$i][] = isset($datay[$endyear][($i + $sm) % 12]['label']) ? $datay[$endyear][($i + $sm) % 12]['label'] : $datay[$endyear][($i + $sm) % 12][0]; // set label
				$year = $startyear;
				while ($year <= $endyear) {
					$data[$i][] = $datay[$year - (1 - ((int) ($i + $sm) / 12)) + ($sm == 0 ? 1 : 0)][($i + $sm) % 12][1]; // set yval for x=i
					$year++;
				}
			}
		}

		// Save cache file
		if (empty($foundintocache) && ($cachedelay > 0 || $cachedelay == -1)) {
			dol_syslog(get_class($this).'::'.__FUNCTION__." save cache file ".$newpathofdestfile." onto disk.");
			if (!dol_is_dir($conf->user->dir_temp)) {
				dol_mkdir($conf->user->dir_temp);
			}
			$fp = fopen($newpathofdestfile, 'w');
			if ($fp) {
				fwrite($fp, json_encode($data));
				fclose($fp);
				if (!empty($conf->global->MAIN_UMASK)) {
					$newmask = $conf->global->MAIN_UMASK;
				}
				@chmod($newpathofdestfile, octdec($newmask));
			} else {
				dol_syslog("Failed to write cache file", LOG_ERR);
			}
			$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $nowgmt;
		}

		return $data;
	}

	/**
	 * @param	int     $year           year number
	 * @return 	int						value
	 */
	protected abstract function getAverageByMonth($year);

	/**
	 * Return average of entity by month for several years
	 *
	 * @param	int		$endyear		Start year
	 * @param	int		$startyear		End year
	 * @return 	array					Array of values
	 */
	public function getAverageByMonthWithPrevYear($endyear, $startyear)
	{
		if ($startyear > $endyear) {
			return -1;
		}

		$datay = array();

		$year = $startyear;
		while ($year <= $endyear) {
			$datay[$year] = $this->getAverageByMonth($year);
			$year++;
		}

		$data = array();

		for ($i = 0; $i < 12; $i++) {
			$data[$i][] = $datay[$endyear][$i][0];
			$year = $startyear;
			while ($year <= $endyear) {
				$data[$i][] = $datay[$year][$i][1];
				$year++;
			}
		}

		return $data;
	}

	/**
	 * Return count, and sum of products
	 *
	 * @param	int		$year			Year
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @param  	int     $limit      	Limit
	 * @return 	array					Array of values
	 */
	public function getAllByProductEntry($year, $cachedelay = 0, $limit = 10)
	{
		global $conf, $user, $langs;

		$data = array();

		// Search into cache
		if (!empty($cachedelay)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
		}

		$newpathofdestfile = $conf->user->dir_temp.'/'.get_class($this).'_'.__FUNCTION__.'_'.(empty($this->cachefilesuffix) ? '' : $this->cachefilesuffix.'_').$langs->defaultlang.'_entity.'.$conf->entity.'_user'.$user->id.'.cache';
		$newmask = '0644';

		$nowgmt = dol_now();

		$foundintocache = 0;
		if ($cachedelay > 0) {
			$filedate = dol_filemtime($newpathofdestfile);
			if ($filedate >= ($nowgmt - $cachedelay)) {
				$foundintocache = 1;

				$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $filedate;
			} else {
				dol_syslog(get_class($this).'::'.__FUNCTION__." cache file ".$newpathofdestfile." is not found or older than now - cachedelay (".$nowgmt." - ".$cachedelay.") so we can't use it.");
			}
		}

		// Load file into $data
		if ($foundintocache) {    // Cache file found and is not too old
			dol_syslog(get_class($this).'::'.__FUNCTION__." read data from cache file ".$newpathofdestfile." ".$filedate.".");
			$data = json_decode(file_get_contents($newpathofdestfile), true);
		} else {
			$data = $this->getAllByProduct($year, $limit);
			//					$data[$i][]=$datay[$year][$i][1];	// set yval for x=i
		}

		// Save cache file
		if (empty($foundintocache) && ($cachedelay > 0 || $cachedelay == -1)) {
			dol_syslog(get_class($this).'::'.__FUNCTION__." save cache file ".$newpathofdestfile." onto disk.");
			if (!dol_is_dir($conf->user->dir_temp)) {
				dol_mkdir($conf->user->dir_temp);
			}
			$fp = fopen($newpathofdestfile, 'w');
			if ($fp) {
				fwrite($fp, json_encode($data));
				fclose($fp);
				if (!empty($conf->global->MAIN_UMASK)) {
					$newmask = $conf->global->MAIN_UMASK;
				}
				@chmod($newpathofdestfile, octdec($newmask));
			}
			$this->lastfetchdate[get_class($this).'_'.__FUNCTION__] = $nowgmt;
		}

		return $data;
	}


	// Here we have low level of shared code called by XxxStats.class.php


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * 	Return nb of elements by year
	 *
	 *	@param	string	$sql		SQL request
	 * 	@return	array
	 */
	protected function _getNbByYear($sql)
	{
		// phpcs:enable
		$result = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$result[$i] = $row;
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * 	Return nb of elements, total amount and avg amount each year
	 *
	 *	@param	string	$sql	SQL request
	 * 	@return	array			Array with nb, total amount, average for each year
	 */
	protected function _getAllByYear($sql)
	{
		// phpcs:enable
		$result = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_object($resql);
				$result[$i]['year'] = $row->year;
				$result[$i]['nb'] = $row->nb;
				if ($i > 0 && $row->nb > 0) {
					$result[$i - 1]['nb_diff'] = ($result[$i - 1]['nb'] - $row->nb) / $row->nb * 100;
				}
				$result[$i]['total'] = $row->total;
				if ($i > 0 && $row->total > 0) {
					$result[$i - 1]['total_diff'] = ($result[$i - 1]['total'] - $row->total) / $row->total * 100;
				}
				$result[$i]['avg'] = $row->avg;
				if ($i > 0 && $row->avg > 0) {
					$result[$i - 1]['avg_diff'] = ($result[$i - 1]['avg'] - $row->avg) / $row->avg * 100;
				}
				// For some $sql only
				if (isset($row->weighted)) {
					$result[$i]['weighted'] = $row->weighted;
					if ($i > 0 && $row->weighted > 0) {
						$result[$i - 1]['avg_weighted'] = ($result[$i - 1]['weighted'] - $row->weighted) / $row->weighted * 100;
					}
				}
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *     Renvoie le nombre de documents par mois pour une annee donnee
	 *     Return number of documents per month for a given year
	 *
	 *     @param   int		$year       Year
	 *     @param   string	$sql        SQL
	 *     @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *     @return	array				Array of nb each month
	 */
	protected function _getNbByMonth($year, $sql, $format = 0)
	{
		// phpcs:enable
		global $langs;

		$result = array();
		$res = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$j = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$j = $row[0] * 1;
				$result[$j] = $row[1];
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		for ($i = 1; $i < 13; $i++) {
			$res[$i] = (isset($result[$i]) ? $result[$i] : 0);
		}

		$data = array();

		for ($i = 1; $i < 13; $i++) {
			$month = 'unknown';
			if ($format == 0) {
				$month = $langs->transnoentitiesnoconv('MonthShort'.sprintf("%02d", $i));
			} elseif ($format == 1) {
				$month = $i;
			} elseif ($format == 2) {
				$month = $langs->transnoentitiesnoconv('MonthVeryShort'.sprintf("%02d", $i));
			}
			//$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),($format?"%m":"%b"));
			//$month=dol_substr($month,0,3);
			$data[$i - 1] = array($month, $res[$i]);
		}

		return $data;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *     Return the amount per month for a given year
	 *
	 *     @param	int		$year       Year
	 *     @param   string	$sql		SQL
	 *     @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *     @return	array
	 */
	protected function _getAmountByMonth($year, $sql, $format = 0)
	{
		// phpcs:enable
		global $langs;

		$result = array();
		$res = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$j = $row[0] * 1;
				$result[$j] = $row[1];
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		for ($i = 1; $i < 13; $i++) {
			$res[$i] = (int) round((isset($result[$i]) ? $result[$i] : 0));
		}

		$data = array();

		for ($i = 1; $i < 13; $i++) {
			$month = 'unknown';
			if ($format == 0) {
				$month = $langs->transnoentitiesnoconv('MonthShort'.sprintf("%02d", $i));
			} elseif ($format == 1) {
				$month = $i;
			} elseif ($format == 2) {
				$month = $langs->transnoentitiesnoconv('MonthVeryShort'.sprintf("%02d", $i));
			}
			//$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),($format?"%m":"%b"));
			//$month=dol_substr($month,0,3);
			$data[$i - 1] = array($month, $res[$i]);
		}

		return $data;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Renvoie le montant moyen par mois pour une annee donnee
	 *  Return the amount average par month for a given year
	 *
	 *  @param  int     $year       Year
	 *  @param  string  $sql        SQL
	 *  @param  int     $format     0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array
	 */
	protected function _getAverageByMonth($year, $sql, $format = 0)
	{
		// phpcs:enable
		global $langs;

		$result = array();
		$res = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$j = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$j = $row[0] * 1;
				$result[$j] = $row[1];
				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		for ($i = 1; $i < 13; $i++) {
			$res[$i] = (isset($result[$i]) ? $result[$i] : 0);
		}

		$data = array();

		for ($i = 1; $i < 13; $i++) {
			$month = 'unknown';
			if ($format == 0) {
				$month = $langs->transnoentitiesnoconv('MonthShort'.sprintf("%02d", $i));
			} elseif ($format == 1) {
				$month = $i;
			} elseif ($format == 2) {
				$month = $langs->transnoentitiesnoconv('MonthVeryShort'.sprintf("%02d", $i));
			}
			//$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),($format?"%m":"%b"));
			//$month=dol_substr($month,0,3);
			$data[$i - 1] = array($month, $res[$i]);
		}

		return $data;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Return number or total of product refs
	 *
	 *  @param  string  $sql        SQL
	 *  @param  int     $limit      Limit
	 *  @return array
	 */
	protected function _getAllByProduct($sql, $limit = 10)
	{
		// phpcs:enable
		global $langs;

		$result = array();

		dol_syslog(get_class($this).'::'.__FUNCTION__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$other = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit) {
					$result[$i] = array($row[0], $row[1]); // Ref of product, nb
				} else {
					$other += $row[1];
				}
				$i++;
			}
			if ($num > $limit) {
				$result[$i] = array($langs->transnoentitiesnoconv("Other"), $other);
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Returns the summed amounts per year for a given number of past years ending now
	 *  @param  string  $sql    SQL
	 *  @return array
	 */
	protected function _getAmountByYear($sql)
	{
		$result = array();
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$j = (int) $row[0];
				$result[] = [
					0 => (int) $row[0],
					1 => (int) $row[1],
				];
				$i++;
			}
			$this->db->free($resql);
		}
		return $result;
	}
}
