<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/boxes/box_factures_imp.php
 *	\ingroup    invoices
 *	\brief      Widget to show remain to get on sale invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


/**
 * Class to manage the box to show not paid sales invoices
 */
class box_factures_imp extends ModeleBoxes
{
	public $boxcode = "oldestunpaidcustomerbills";
	public $boximg = "object_bill";
	public $boxlabel = "BoxOldestUnpaidCustomerBills";
	public $depends = array("facture");

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

		$this->hidden = !($user->hasRight('facture', 'lire'));
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max = $max;
		//$this->max = 1000;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$facturestatic = new Facture($this->db);
		$societestatic = new Societe($this->db);

		$langs->load("bills");

		$textHead = $langs->trans("BoxTitleOldestUnpaidCustomerBills");
		$this->info_box_head = array(
			'text' => $langs->trans("BoxTitleOldestUnpaidCustomerBills", $this->max).'<a class="paddingleft valignmiddle" href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status=1&sortfield=f.date_lim_reglement,f.ref&sortorder=ASC,ASC"><span class="badge">...</span></a>',
			'limit' => dol_strlen($textHead));

		if ($user->hasRight('facture', 'lire')) {
			$sql1 = "SELECT s.rowid as socid, s.nom as name, s.name_alias, s.code_client, s.client";
			if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql1 .= ", spe.accountancy_code_customer as code_compta_client";
			} else {
				$sql1 .= ", s.code_compta as code_compta_client";
			}
			$sql1 .= ", s.logo, s.email, s.entity";
			$sql1 .= ", s.tva_intra, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6";
			$sql1 .= ", f.ref, f.date_lim_reglement as datelimite";
			$sql1 .= ", f.type";
			$sql1 .= ", f.datef as date";
			$sql1 .= ", f.total_ht";
			$sql1 .= ", f.total_tva";
			$sql1 .= ", f.total_ttc";
			$sql1 .= ", f.paye, f.fk_statut as status, f.rowid as facid";
			$sql1 .= ", SUM(pf.amount) as am";
			$sql2 = " FROM ".MAIN_DB_PREFIX."societe as s";
			if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql2 .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
			}
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql2 .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql2 .= ", ".MAIN_DB_PREFIX."facture as f";
			$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
			$sql2 .= " WHERE f.fk_soc = s.rowid";
			$sql2 .= " AND f.entity IN (".getEntity('invoice').")";
			$sql2 .= " AND f.paye = 0";
			$sql2 .= " AND fk_statut = 1";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql2 .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql2 .= " AND s.rowid = ".((int) $user->socid);
			}
			$sql3 = " GROUP BY s.rowid, s.nom, s.name_alias, s.code_client, s.client, s.logo, s.email, s.entity, s.tva_intra, s.siren, s.siret, s.ape, s.idprof4, s.idprof5, s.idprof6,";
			if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql3 .= " spe.accountancy_code_customer as code_compta,";
			} else {
				$sql3 .= " s.code_compta,";
			}
			$sql3 .= " f.rowid, f.ref, f.date_lim_reglement,";
			$sql3 .= " f.type, f.datef, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.fk_statut";
			$sql3 .= " ORDER BY datelimite ASC, f.ref ASC";
			$sql3 .= $this->db->plimit($this->max + 1, 0);

			$sql = $sql1.$sql2.$sql3;

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateDue')).': %s)';

				while ($line < min($num, $this->max)) {
					$objp = $this->db->fetch_object($result);

					$date = $this->db->jdate($objp->date);
					$datem = $this->db->jdate($objp->tms);
					$datelimit = $this->db->jdate($objp->datelimite);

					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->type = $objp->type;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->date = $date;
					$facturestatic->date_lim_reglement = $datelimit;
					$facturestatic->statut = $objp->status;
					$facturestatic->status = $objp->status;

					$facturestatic->paye = $objp->paye;
					$facturestatic->paid = $objp->paye;
					$facturestatic->alreadypaid = $objp->am;
					$facturestatic->totalpaid = $objp->am;

					$societestatic->id = $objp->socid;
					$societestatic->name = $objp->name;
					//$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_compta = $objp->code_compta_client;
					$societestatic->code_compta_client = $objp->code_compta_client;
					$societestatic->client = $objp->client;
					$societestatic->logo = $objp->logo;
					$societestatic->email = $objp->email;
					$societestatic->entity = $objp->entity;
					$societestatic->tva_intra = $objp->tva_intra;

					$societestatic->idprof1 = !empty($objp->idprof1) ? $objp->idprof1 : '';
					$societestatic->idprof2 = !empty($objp->idprof2) ? $objp->idprof2 : '';
					$societestatic->idprof3 = !empty($objp->idprof3) ? $objp->idprof3 : '';
					$societestatic->idprof4 = !empty($objp->idprof4) ? $objp->idprof4 : '';
					$societestatic->idprof5 = !empty($objp->idprof5) ? $objp->idprof5 : '';
					$societestatic->idprof6 = !empty($objp->idprof6) ? $objp->idprof6 : '';

					$late = '';
					if ($facturestatic->hasDelay()) {
						// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
						$late = img_warning(sprintf($l_due_date, dol_print_date($datelimit, 'day', 'tzuserrel')));
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $facturestatic->getNomUrl(1),
						'text2' => $late,
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $societestatic->getNomUrl(1, '', 44),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateDue").': '.dol_print_date($datelimit, 'day', 'tzuserrel')).'"',
						'text' => dol_print_date($datelimit, 'day', 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $facturestatic->LibStatut($objp->paye, $objp->status, 3, $objp->am, $objp->type),
					);

					$line++;
				}
				if ($this->max < $num) {
					$this->info_box_contents[$line][] = array('td' => 'colspan="6"', 'text' => '...');
					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center" colspan="3"',
						'text' => '<span class="opacitymedium">'.$langs->trans("NoUnpaidCustomerBills").'</span>'
					);
				} else {
					$sql = "SELECT SUM(f.total_ht) as total_ht ".$sql2;

					$result = $this->db->query($sql);
					$objp = $this->db->fetch_object($result);
					$totalamount = $objp->total_ht;

					// Add the sum à the bottom of the boxes
					$this->info_box_contents[$line][] = array(
						'tr' => 'class="liste_total_wrap"',
						'td' => 'class="liste_total"',
						'text' => $langs->trans("Total"),
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total"',
						'text' => "&nbsp;",
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="right liste_total" ',
						'text' => price($totalamount, 0, $langs, 0, -1, -1, $conf->currency),
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total"',
						'text' => "&nbsp;",
					);
					$this->info_box_contents[$line][] = array(
						'td' => 'class="liste_total"',
						'text' => "&nbsp;",
					);

					$this->db->free($result);
				}
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
