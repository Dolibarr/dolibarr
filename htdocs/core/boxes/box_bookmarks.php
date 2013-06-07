<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	var $boxcode="bookmarks";
	var $boximg="object_bookmark";
	var $boxlabel="BoxMyLastBookmarks";
	var $depends = array("bookmark");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db;
		$langs->load("boxes");

		$this->max=$max;

		$this->info_box_head = array('text' => $langs->trans("BoxMyLastBookmarks",$max),
                                     'sublink' => DOL_URL_ROOT.'/bookmarks/liste.php');
		if ($user->rights->bookmark->creer)
		{
			$this->info_box_head['subpicto']='object_bookmark';
			$this->info_box_head['subtext']=$langs->trans("BookmarksManagement");
		}
		else
		{
			$this->info_box_head['subpicto']='object_bookmark';
			$this->info_box_head['subtext']=$langs->trans("ListOfBookmark");
		}

		if ($user->rights->bookmark->lire)
		{
			$sql = "SELECT b.title, b.url, b.target, b.favicon";
			$sql.= " FROM ".MAIN_DB_PREFIX."bookmark as b";
			$sql.= " WHERE fk_user = ".$user->id;
			$sql.= $db->order("position","ASC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);

				$i = 0;

				while ($i < $num)
				{
					$objp = $db->fetch_object($result);

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => $objp->url,
                    'target' => $objp->target?'newtab':'');
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->title,
                    'url' => $objp->url,
                    'target' => $objp->target?'newtab':'');

					$i++;
				}

				if ($num==0)
				{
					$mytxt=$langs->trans("NoRecordedBookmarks");
					if ($user->rights->bookmark->creer) $mytxt.=' '.$langs->trans("ClickToAdd");
					$this->info_box_contents[$i][0] = array('td' => 'align="center" colspan="2"', 'url'=> DOL_URL_ROOT.'/bookmarks/liste.php', 'text'=>$mytxt);
				}

				$db->free($result);
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
