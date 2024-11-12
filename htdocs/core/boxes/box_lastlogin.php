<?php
/* Copyright (C) 2012      Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2005-2017 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2020 Frederic France        <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/boxes/box_lastlogin.php
 *  \ingroup    core
 *  \brief      Module to show box of last user logins
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 *  Class to manage the box of last login
 */
class box_lastlogin extends ModeleBoxes
{
	public $boxcode  = "lastlogin";
	public $boximg   = "object_user";
	public $boxlabel = 'BoxLoginInformation';
	public $depends  = array("user");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $conf;

		$this->db = $db;
	}

	/**
	 *  Load data into memory for later display
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$textHead = $langs->trans("BoxLoginInformation");
		$this->info_box_head = array(
			'text' => $textHead,
			'limit' => dol_strlen($textHead),
		);

		$line = 0;
		$this->info_box_contents[$line][0] = array(
			'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
			'text' => $langs->trans("User"),
		);
		$this->info_box_contents[$line][1] = array(
			'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
			'text' => $user->getNomUrl(-1),
			'asis' => 1
		);

		$line = 1;
		$this->info_box_contents[$line][0] = array(
			'td' => '',
			'text' => $langs->trans("PreviousConnexion"),
		);
		if ($user->datepreviouslogin) {
			$tmp = dol_print_date($user->datepreviouslogin, "dayhour", 'tzuserrel');
		} else {
			$tmp = $langs->trans("Unknown");
		}
		$this->info_box_contents[$line][1] = array(
			'td' => '',
			'text' => $tmp,
		);
	}




	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
