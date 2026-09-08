<?php
/* Copyright (C) 2012      Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2005-2015 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2024  Frédéric France        <frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/boxes/box_invoices_dispute.php
 *  \ingroup    invoice
 *  \brief      Module to show disputed invoices
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box of disputed invoices
 */
class box_invoices_dispute extends ModeleBoxes
{
	public $boxcode = "box_invoices_dispute";
	public $boximg = "object_invoice";
	public $boxlabel = 'BoxInvoicesDispute';
	public $depends = array("invoice");

	public $enabled = 1;

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)  // @phpstan-ignore constructor.unusedParameter
	{
		global $conf, $user;

		$this->db = $db;

		$this->enabled = getDolGlobalInt('MAIN_FEATURES_LEVEL'); // Not enabled by default due to bugs (see previous comments)

		$this->hidden = !(isModEnabled('invoice') && $user->hasRight('facture', 'read'));
	}

	/**
	 *  Charge les donnees en memoire pour affichage ulterieur
	 *
	 *  @param  int     $max        Maximum number of records to load
	 *  @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$totalnb = 0;
		$line = 0;
		$now = dol_now();
		$nbofperiod = 3;

		// Force use of cache for this box as it has very bad performances
		$savMAIN_ACTIVATE_FILECACHE = getDolGlobalInt('MAIN_ACTIVATE_FILECACHE');
		$conf->global->MAIN_ACTIVATE_FILECACHE = 1;


		$textHead = $langs->trans("InvoicesDispute").' - '.$langs->trans("LastXMonthRolling", $nbofperiod);
		$this->info_box_head = array(
			'text' => $textHead,
			'limit' => dol_strlen($textHead),
		);

		// compute the year limit to show
		$tmpdate = dol_time_plus_duree(dol_now(), -1 * $nbofperiod, "m");

		// list the summary of the bills
		if (isModEnabled('invoice') && $user->hasRight("facture", "lire")) {
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$facturestatic = new Facture($this->db);

			$data = array();
			$sql = "SELECT f.dispute_status, f.fk_statut, SUM(f.total_ttc) as mnttot, COUNT(*) as nb";
			$sql .= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= ")";
			$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
			if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql .= " AND f.fk_soc = s.rowid";
			$sql .= " AND f.dispute_status > 0";
			$sql .= " AND f.datef >= '".$this->db->idate($tmpdate)."' AND f.paye=1";
			$sql .= " GROUP BY f.dispute_status, f.fk_statut";
			$sql .= " ORDER BY f.dispute_status ASC";

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$j = 0;
				while ($j < $num) {
					$data[$j] = $this->db->fetch_object($result);
					$j++;
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			if (!empty($data)) {
				$j = 0;
				while ($j < count($data)) {
					$billurl = "search_status=2&paye=1&search_dispute_status=".$data[$j]->dispute_status;
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="left" width="16"',
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0, -1, -1, array("dispute_status" => $data[$j]->dispute_status)),
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills",
						'logo' => 'bill',
					);

					$this->info_box_contents[$line][1] = array(
						'td' => '',
						'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0, -1, -1, array("dispute_status" => $data[$j]->dispute_status)),
					);

					$this->info_box_contents[$line][2] = array(
						'td' => 'class="right"',
						'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0, -1, -1, array("dispute_status" => $data[$j]->dispute_status)),
						'text' => $data[$j]->nb,
						'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills",
					);

					$this->info_box_contents[$line][3] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($data[$j]->mnttot, 1, $langs, 0, 0, -1, $conf->currency)
					);

					// We add only for the current year
					$totalnb += $data[$j]->nb;

					$this->info_box_contents[$line][4] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut(1, $data[$j]->fk_statut, 3, -1, -1, array("dispute_status" => $data[$j]->dispute_status)),
					);
					$line++;
					$j++;
				}
				if (count($data) == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text' => $langs->trans("NoRecordedInvoices"),
					);
					$line++;
				}
			}
		}
		// Add the sum in the bottom of the boxes
		$this->info_box_contents[$line][0] = array('tr' => 'class="liste_total_wrap"');
		$this->info_box_contents[$line][1] = array('td' => 'class="liste_total left" ', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$line][2] = array('td' => 'class="liste_total right" ', 'text' => (string) $totalnb);
		$this->info_box_contents[$line][3] = array('td' => 'class="liste_total right" ', 'text' => '');
		$this->info_box_contents[$line][4] = array('td' => 'class="liste_total right" ', 'text' => "");

		$conf->global->MAIN_ACTIVATE_FILECACHE = $savMAIN_ACTIVATE_FILECACHE;
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
