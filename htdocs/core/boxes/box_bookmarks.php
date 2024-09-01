<?php
/* Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/boxes/box_bookmarks.php
 *      \ingroup    bookmark
 *      \brief      Module to generate box of bookmarks list
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show bookmarks
 */
class box_bookmarks extends ModeleBoxes
{
	public $boxcode = "bookmarks";
	public $boximg = "bookmark";
	public $boxlabel = "BoxMyLastBookmarks";
	public $depends = array("bookmark");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->hidden = !$user->hasRight('bookmark', 'lire');
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;
		$langs->load("boxes");

		$this->max = $max;

		$this->info_box_head = array(
			'text' => $langs->trans("BoxMyLastBookmarks", $max),
			'sublink' => DOL_URL_ROOT.'/bookmarks/list.php',
		);
		if ($user->hasRight("bookmark", "creer")) {
			$this->info_box_head['subpicto'] = 'bookmark';
			$this->info_box_head['subtext'] = $langs->trans("BookmarksManagement");
		} else {
			$this->info_box_head['subpicto'] = 'bookmark';
			$this->info_box_head['subtext'] = $langs->trans("ListOfBookmark");
		}

		if ($user->hasRight('bookmark', 'lire')) {
			$sql = "SELECT b.title, b.url, b.target, b.favicon";
			$sql .= " FROM ".MAIN_DB_PREFIX."bookmark as b";
			$sql .= " WHERE fk_user = ".((int) $user->id);
			$sql .= " AND b.entity = ".$conf->entity;
			$sql .= $this->db->order("position", "ASC");
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'logo' => $this->boximg,
						'url' => $objp->url,
						'tooltip' => $objp->title,
						'target' => $objp->target ? 'newtab' : '',
					);
					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $objp->title,
						'url' => $objp->url,
						'tooltip' => $objp->title,
						'target' => $objp->target ? 'newtab' : '',
					);

					$line++;
				}

				if ($num == 0) {
					$mytxt = $langs->trans("NoRecordedBookmarks");
					if ($user->hasRight("bookmark", "creer")) {
						$mytxt .= ' '.$langs->trans("ClickToAdd");
					}
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center" colspan="2"',
						'tooltip' => $mytxt,
						'url' => DOL_URL_ROOT.'/bookmarks/list.php', 'text' => $mytxt,
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength' => 500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}

	/**
	 *  Method to show box
	 *
	 *	@param	?array{text?:string,sublink?:string,subpicto:?string,nbcol?:int,limit?:int,subclass?:string,graph?:string}	$head	Array with properties of box title
	 *	@param	?array<array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:string}>>	$contents	Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *  @return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
