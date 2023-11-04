<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 * 	    \file       htdocs/core/boxes/box_external_rss.php
 *      \ingroup    external_rss
 *      \brief      Fichier de gestion d'une box pour le module external_rss
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/rssparser.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show RSS feeds
 */
class box_external_rss extends ModeleBoxes
{
	public $boxcode = "lastrssinfos";
	public $boximg = "object_rss";
	public $boxlabel = "BoxLastRssInfos";
	public $depends = array("externalrss");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $paramdef; // Params of box definition (not user params)

	public $info_box_head = array();
	public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param)
	{
		$this->db = $db;
		$this->paramdef = $param;
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        	Maximum number of records to load
	 *  @param	int		$cachedelay		Delay we accept for cache file
	 *  @return	void
	 */
	public function loadBox($max = 5, $cachedelay = 3600)
	{
		global $user, $langs, $conf;
		$langs->load("boxes");

		$this->max = $max;

		// On recupere numero de param de la boite
		$reg = array();
		preg_match('/^([0-9]+) /', $this->paramdef, $reg);
		$site = $reg[1];

		// Create dir nor required
		// documents/externalrss is created by module activation
		// documents/externalrss/tmp is created by rssparser

		$keyforparamurl = "EXTERNAL_RSS_URLRSS_".$site;
		$keyforparamtitle = "EXTERNAL_RSS_TITLE_".$site;

		// Get RSS feed
		$url = $conf->global->$keyforparamurl;

		$rssparser = new RssParser($this->db);
		$result = $rssparser->parser($url, $this->max, $cachedelay, $conf->externalrss->dir_temp);

		// INFO on channel
		$description = $rssparser->getDescription();
		$link = $rssparser->getLink();

		$title = $langs->trans("BoxTitleLastRssInfos", $max, $conf->global->$keyforparamtitle);
		if ($result < 0 || !empty($rssparser->error)) {
			// Show warning
			$errormessage = $langs->trans("FailedToRefreshDataInfoNotUpToDate", ($rssparser->getLastFetchDate() ? dol_print_date($rssparser->getLastFetchDate(), "dayhourtext") : $langs->trans("Unknown")));
			if ($rssparser->error) {
				$errormessage .= " - ".$rssparser->error;
			}
			$title .= " ".img_error($errormessage);
			$this->info_box_head = array('text' => $title, 'limit' => 0);
		} else {
			$this->info_box_head = array(
				'text' => $title,
				'sublink' => $link,
				'subtext'=>$langs->trans("LastRefreshDate").': '.($rssparser->getLastFetchDate() ? dol_print_date($rssparser->getLastFetchDate(), "dayhourtext") : $langs->trans("Unknown")),
				'subpicto'=>'globe',
				'target'=>'_blank',
			);
		}

		// INFO on items
		$items = $rssparser->getItems();
		//print '<pre>'.print_r($items,true).'</pre>';
		$nbitems = count($items);
		for ($line = 0; $line < $max && $line < $nbitems; $line++) {
			$item = $items[$line];

			// Feed common fields
			$href = $item['link'];
			$title = urldecode($item['title']);
			$date = $item['date_timestamp']; // date will be empty if conversion into timestamp failed
			if ($rssparser->getFormat() == 'rss') {   // If RSS
				if (!$date && isset($item['pubdate'])) {
					$date = $item['pubdate'];
				}
				if (!$date && isset($item['dc']['date'])) {
					$date = $item['dc']['date'];
				}
				//$item['dc']['language']
				//$item['dc']['publisher']
			}
			if ($rssparser->getFormat() == 'atom') {	// If Atom
				if (!$date && isset($item['issued'])) {
					$date = $item['issued'];
				}
				if (!$date && isset($item['modified'])) {
					$date = $item['modified'];
				}
				//$item['issued']
				//$item['modified']
				//$item['atom_content']
			}
			if (is_numeric($date)) {
				$date = dol_print_date($date, "dayhour", 'tzuserrel');
			}

			$isutf8 = utf8_check($title);
			if (!$isutf8 && $conf->file->character_set_client == 'UTF-8') {
				$title = utf8_encode($title);
			} elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') {
				$title = utf8_decode($title);
			}

			$title = preg_replace("/([[:alnum:]])\?([[:alnum:]])/", "\\1'\\2", $title); // Gere probleme des apostrophes mal codee/decodee par utf8
			$title = preg_replace("/^\s+/", "", $title); // Supprime espaces de debut
			$this->info_box_contents["$href"] = "$title";

			$tooltip = $title;
			$description = !empty($item['description']) ? $item['description'] : '';
			$isutf8 = utf8_check($description);
			if (!$isutf8 && $conf->file->character_set_client == 'UTF-8') {
				$description = utf8_encode($description);
			} elseif ($isutf8 && $conf->file->character_set_client == 'ISO-8859-1') {
				$description = utf8_decode($description);
			}
			$description = preg_replace("/([[:alnum:]])\?([[:alnum:]])/", "\\1'\\2", $description);
			$description = preg_replace("/^\s+/", "", $description);
			$description = str_replace("\r\n", "", $description);
			$tooltip .= '<br>'.$description;

			$this->info_box_contents[$line][0] = array(
				'td' => 'class="left" width="16"',
				'logo' => $this->boximg,
				'url' => $href,
				'tooltip' => $tooltip,
				'target' => 'newrss',
			);

			$this->info_box_contents[$line][1] = array(
				'td' => '',
				'text' => $title,
				'url' => $href,
				'tooltip' => $tooltip,
				'maxlength' => 64,
				'target' => 'newrss',
			);

			$this->info_box_contents[$line][2] = array(
				'td' => 'class="right nowrap"',
				'text' => $date,
			);
		}
	}


	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
