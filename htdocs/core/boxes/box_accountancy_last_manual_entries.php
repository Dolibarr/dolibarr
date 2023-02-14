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
 *		\file       htdocs/core/boxes/box_accountancy_last_manual_entries.php
 *		\ingroup    Accountancy
 *		\brief      Module to generated widget of last manual entries
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last manual entries
 */
class box_accountancy_last_manual_entries extends ModeleBoxes
{
	public $boxcode = "accountancy_last_manual_entries";
	public $boximg = "accounting";
	public $boxlabel = "BoxLastManualEntries";
	public $depends = array("accounting");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;

	public $info_box_head = array();
	public $info_box_contents = array();


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

		$this->hidden = empty($user->rights->accounting->mouvements->lire);
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

		include_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

		$bookkeepingstatic = new BookKeeping($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastManualEntries", $max));

		if ($user->rights->accounting->mouvements->lire) {
			$sql = "SELECT DISTINCT b.piece_num";
			$sql .= ", b.doc_date as date_movement";
			$sql .= ", b.label_operation";
			$sql .= ", b.montant as amount";
			$sql .= ", b.code_journal";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b";
			$sql .= " WHERE b.fk_doc = 0";
			$sql .= " AND b.entity = ".$conf->entity;
			$sql .= " ORDER BY b.piece_num DESC ";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;

				while ($line < $num) {
					$objp		= $this->db->fetch_object($result);
					$date		= $this->db->jdate($objp->date_movement);
					$journal	= $objp->code_journal;
					$label = $objp->label_operation;
					$amount = $objp->amount;

					// adding id (rowid) will give two lines (debit and credit)
					// so rowid isn't in sql request
					// $bookkeepingstatic->id = $objp->id;
					$bookkeepingstatic->piece_num = $objp->piece_num;

					$this->info_box_contents[$line][] = array(
						'td' => '',
						'text' => $bookkeepingstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall"',
						'text' => dol_print_date($date, 'day'),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center"',
						'text' => $journal,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $label,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($amount, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedManualEntries").'</span>'
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
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
