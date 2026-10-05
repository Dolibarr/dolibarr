<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
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
 *
 */

/**
 *  \file       htdocs/compta/prelevement/class/ligneprelevement.class.php
 *  \ingroup    prelevement
 *  \brief      File of class to manage lines of Direct Debit orders
 */


/**
 *	Class to manage withdrawals
 */
class LignePrelevement
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string String with name of icon
	 */
	public $picto = 'generic';

	/**
	 * @var float Amount
	 */
	public $amount;

	/**
	 * @var int Socid
	 */
	public $socid;

	/**
	 * @var int Status of the line
	 * @deprcated use $status
	 */
	public $statut;

	/**
	 * @var int Status of the line
	 */
	public $status;

	/**
	 * @var string Ref of bon
	 */
	public $bon_ref;

	/**
	 * @var int ID of bon
	 */
	public $bon_rowid;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string[]  Map status index to localized labels
	 */
	public $labelStatus = array();

	const STATUS_DRAFT = 0;
	const STATUS_NOT_USED = 1;
	const STATUS_CREDITED = 2;		// STATUS_CREDITED and STATUS_DEBITED is same. Difference is in ->type
	const STATUS_DEBITED = 2;		// STATUS_CREDITED and STATUS_DEBITED is same. Difference is in ->type
	const STATUS_REJECTED = 3;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db			Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;

		// List of language codes for status

		$langs->load("withdrawals");
		$this->labelStatus[0] = $langs->trans("StatusWaiting");
		$this->labelStatus[2] = $langs->trans("StatusPaid");
		$this->labelStatus[3] = $langs->trans("StatusRefused");
	}

	/**
	 *  Recupere l'objet prelevement
	 *
	 *  @param	int		$rowid      Id de la facture a recuperer
	 *  @return	integer				Return integer <0 if KO, >=0 if OK
	 */
	public function fetch($rowid)
	{
		global $conf;

		$error = 0;

		$sql = "SELECT pl.rowid, pl.amount, p.ref, p.rowid as bon_rowid";
		$sql .= ", pl.statut as status, pl.fk_soc";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
		$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as p";
		$sql .= " WHERE pl.rowid=".((int) $rowid);
		$sql .= " AND p.rowid = pl.fk_prelevement_bons";
		$sql .= " AND p.entity = ".((int) $conf->entity);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id              = $obj->rowid;
				$this->amount          = $obj->amount;
				$this->socid           = $obj->fk_soc;
				$this->statut          = $obj->status;
				$this->status          = $obj->status;
				$this->bon_ref         = $obj->ref;
				$this->bon_rowid       = $obj->bon_rowid;
			} else {
				$error++;
				dol_syslog("LignePrelevement::Fetch rowid=$rowid numrows=0");
			}

			$this->db->free($resql);
		} else {
			$error++;
			dol_syslog("LignePrelevement::Fetch rowid=$rowid");
			dol_syslog($this->db->error());
		}

		return $error;
	}

	/**
	 *    Return status label of object
	 *
	 *    @param	int		$mode       0=Label, 1=Picto + label, 2=Picto, 3=Label + Picto
	 * 	  @return   string      		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(isset($this->statut) ? $this->statut : $this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return status label for a status
	 *
	 *    @param	int		$status     Id status
	 *    @param    int		$mode       0=Label, 1=Picto + label, 2=Picto, 3=Label + Picto
	 * 	  @return   null|string      	Return status label (or null if $mode != 0, 1, 2, 3 or 4)
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if ($mode == 0) {
			return $langs->trans($this->labelStatus[$status]);
		} elseif ($mode == 1) {
			if ($status == 0) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut1', 'class="valignmiddle"').' '.$langs->transnoentitiesnoconv($this->labelStatus[$status]); // Waiting
			} elseif ($status == 2) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut6', 'class="valignmiddle"').' '.$langs->transnoentitiesnoconv($this->labelStatus[$status]); // Credited
			} elseif ($status == 3) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut8', 'class="valignmiddle"').' '.$langs->transnoentitiesnoconv($this->labelStatus[$status]); // Refused
			}
		} elseif ($mode == 2) {
			if ($status == 0) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut1', 'class="valignmiddle"');
			} elseif ($status == 2) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut6', 'class="valignmiddle"');
			} elseif ($status == 3) {
				return img_picto($langs->trans($this->labelStatus[$status]), 'statut8', 'class="valignmiddle"');
			}
		} elseif ($mode == 3) {
			if ($status == 0) {
				return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->transnoentitiesnoconv($this->labelStatus[$status]), 'statut1', 'class="valignmiddle"');
			} elseif ($status == 2) {
				return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->transnoentitiesnoconv($this->labelStatus[$status]), 'statut6', 'class="valignmiddle"');
			} elseif ($status == 3) {
				return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->transnoentitiesnoconv($this->labelStatus[$status]), 'statut8', 'class="valignmiddle"');
			}
		}
		// return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
		return null;
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		?array<string,mixed>	$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . $this->id . '</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
		}
		/*
		if (isset($this->date_trans)) {
			$return .= '<br><span class="opacitymedium">' . $langs->trans("TransData") . '</span> : <span class="info-box-label">' . dol_print_date($this->db->jdate($this->date_tans), 'day') . '</span>';
		}
		if (isset($this->date_credit)) {
			$return .= '<br><span class="opacitymedium">' . $langs->trans("CreditDate") . '</span> : <span class="info-box-label">' . dol_print_date($this->db->jdate($this->date_credit), 'day') . '</span>';
		}
		if (isset($this->date_echeance)) {
			$return .= '<br><span class="opacitymedium">' . $langs->trans("Date") . '</span> : <span class="info-box-label">' . dol_print_date($this->db->jdate($this->date_echeance), 'day') . '</span>';
		}
		*/
		if (isset($this->amount)) {
			$return .= '<br><span class="opacitymedium">' . $langs->trans("Amount") . '</span> : <span class="amount">' . price($this->amount) . '</span>';
		}
		$return .= '<br><div class="info-box-status">' . $this->getLibStatut(3) . '</div>';
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'prelevement_lignes'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}
}
