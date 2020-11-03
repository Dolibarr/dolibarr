<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/boxes/mybox.php
 * 	\ingroup	intracommreport
 * 	\brief		This file is a sample box definition file
 * 				Put some comments here
 */
include_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class intracommreportbox extends ModeleBoxes
{

	public $boxcode = "mybox";
	public $boximg = "intracommreport";
	public $boxlabel;
	public $depends = array("intracommreport");
	public $db;
	public $param;
	public $info_box_head = array();
	public $info_box_contents = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel = $langs->transnoentitiesnoconv("MyBox");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 * 	@param		int		$max		Maximum number of records to load
	 * 	@return		void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$this->max = $max;

		//include_once DOL_DOCUMENT_ROOT . "/intracommreport/class/intracommreport.class.php";

		$text = $langs->trans("MyBoxDescription", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);

		$this->info_box_contents[0][0] = array('td' => 'align="left"',
			'text' => $langs->trans("MyBoxContent"));
	}

	/**
	 * 	Method to show box
	 *
	 * 	@param	array	$head       Array with properties of box title
	 * 	@param  array	$contents   Array with properties of box lines
	 * 	@return	void
	 */
	public function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
