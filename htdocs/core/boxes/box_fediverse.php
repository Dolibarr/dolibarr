<?php
/* Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	    \file       htdocs/core/boxes/box_fediverse.php
 *      \ingroup    social
 *      \brief      Fichier de gestion d'une box pour le module Fediverse
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/fediverseparser.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show Fediverse posts
 */
class box_fediverse extends ModeleBoxes
{
	public $boxcode = "lastfediverseinfos";
	public $boximg = "object_share-alt";
	public $boxlabel = "BoxLastFediverseInfos";
	public $depends = array("socialnetworks");

	/**
	 * @var string
	 */
	public $paramdef;

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
		global $langs, $conf;
		$langs->load("boxes");

		$this->max = $max;

		// Get Fediverse feed URL
		$sql = '';
		if (!empty($this->paramdef)) {
			$sql = "SELECT value FROM ".MAIN_DB_PREFIX."const";
			$sql .= " WHERE name like '%SOCIAL_NETWORKS_DATA_".$this->db->escape($this->paramdef)."%'";
		}
		$resql = $this->db->query($sql);
		$num = $this->db->num_rows($resql);

		$socialNetworkTitle = '';
		$socialNetworkUrl = '';
		if ($num > 0) {
			$obj = $this->db->fetch_row($resql);
			$socialNetworkData = json_decode($obj[0], true);
			$socialNetworkTitle = $socialNetworkData['title'];
			$socialNetworkUrl = $socialNetworkData['url'];
		}

		$fediverseParser = new FediverseParser($this->db);
		$path_fediverse = DOL_DATA_ROOT.'/fediverse/temp/'.$socialNetworkTitle;

		$result = $fediverseParser->parser($socialNetworkUrl, $this->max, $cachedelay, $path_fediverse, 'mastodon');

		$title = $langs->trans("BoxTitleLastFediverseInfos", $max, dol_escape_htmltag($socialNetworkTitle));
		if ($result < 0 || !empty($fediverseParser->error)) {
			$errormessage = $langs->trans("FailedToRefreshDataInfoNotUpToDate", ($fediverseParser->getLastFetchDate() ? dol_print_date($fediverseParser->getLastFetchDate(), "dayhourtext") : $langs->trans("Unknown")));
			if ($fediverseParser->error) {
				$errormessage .= " - ".$fediverseParser->error;
			}
			$title .= " ".img_error($errormessage);
			$this->info_box_head = array('text' => $title, 'limit' => 0);
		} else {
			$this->info_box_head = array(
				'text' => $title,
				'sublink' => $socialNetworkUrl,
				'subtext' => $langs->trans("LastRefreshDate").': '.($fediverseParser->getLastFetchDate() ? dol_print_date($fediverseParser->getLastFetchDate(), "dayhourtext") : $langs->trans("Unknown")),
				'subpicto' => 'globe',
				'target' => '_blank',
			);
		}

		$posts = $fediverseParser->getPosts();
		$nbitems = count($posts);

		for ($line = 0; $line < $max && $line < $nbitems; $line++) {
			$post = $posts[$line];
			$title = dol_escape_htmltag($post['content']);
			$date = dol_escape_htmltag($post['created_at']);
			$href = dol_escape_htmltag($post['url']);
			$tooltip = dol_escape_htmltag($title);

			$this->info_box_contents[$line][0] = array(
				'td' => 'class="left" width="16"',
				'text' => img_picto('', 'share-alt'),
				'url' => $href,
				'tooltip' => $tooltip,
				'target' => 'newfediverse',
			);

			$this->info_box_contents[$line][1] = array(
				'td' => 'class="tdoverflowmax300"',
				'text' => $title,
				'url' => $href,
				'tooltip' => $tooltip,
				'maxlength' => 0,
				'target' => 'newfediverse',
			);

			$this->info_box_contents[$line][2] = array(
				'td' => 'class="right nowraponall"',
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
