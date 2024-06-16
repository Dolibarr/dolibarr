<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 *		\file       htdocs/core/boxes/box_accountancy_suspense_account.php
 *		\ingroup    Accountancy
 *		\brief      Module to generated widget of suspense account
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show suspense account
 */
class box_accountancy_suspense_account extends ModeleBoxes
{
	public $boxcode = "accountancy_suspense_account";
	public $boximg = "accountancy";
	public $boxlabel = "BoxSuspenseAccount";
	public $depends = array("accounting");

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

		$this->hidden = !$user->hasRight('accounting', 'mouvements', 'lire');
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @return	void
	 */
	public function loadBox()
	{
		global $user, $langs, $conf;

		include_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

		//$bookkeepingstatic = new BookKeeping($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleSuspenseAccount"));

		if ($user->hasRight('accounting', 'mouvements', 'lire')) {
			$suspenseAccount = getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE');
			if (!empty($suspenseAccount) && $suspenseAccount > 0) {
				$sql = "SELECT COUNT(*) as nb_suspense_account";
				$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b";
				$sql .= " WHERE b.numero_compte = '".$this->db->escape($suspenseAccount)."'";
				$sql .= " AND b.entity = ".$conf->entity;

				$result = $this->db->query($sql);
				$nbSuspenseAccount = 0;
				if ($result) {
					$obj = $this->db->fetch_object($result);
					$nbSuspenseAccount = $obj->nb_suspense_account;
				}

				$this->info_box_contents[0][0] = array(
					'td' => '',
					'text' => $langs->trans("NumberOfLinesInSuspenseAccount")
				);

				$this->info_box_contents[0][1] = array(
					'td' => 'class="right"',
					'text' => '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?search_accountancy_code_start='.urlencode($suspenseAccount).'&search_accountancy_code_end='.urlencode($suspenseAccount).'">'.$nbSuspenseAccount.'</a>',
					'asis' => 1
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="nohover"',
					'text' => '<span class="opacitymedium">'.$langs->trans("SuspenseAccountNotDefined").'</span>'
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
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
