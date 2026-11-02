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

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)  // @phpstan-ignore constructor.unusedParameter
	{
		global $user;

		$this->db = $db;

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

		$langs->load("bills");

		// Force use of cache for this box as it has very bad performances
		$savMAIN_ACTIVATE_FILECACHE = getDolGlobalInt('MAIN_ACTIVATE_FILECACHE');
		$conf->global->MAIN_ACTIVATE_FILECACHE = 1;


		$textHead = $langs->trans("InvoicesDispute");
		$this->info_box_head = array(
			'text' => $textHead,
			'limit' => dol_strlen($textHead),
		);

		// compute the date limit for lost and won disputes
		$tmpdate = dol_time_plus_duree(dol_now(), -1 * $nbofperiod, "m");
		$tmparray = dol_getdate($tmpdate);

		// list the summary of the bills
		if (isModEnabled('invoice') && $user->hasRight("facture", "lire")) {
			// Init the 3 lines always visible: open (any date), lost and won (last months)
			$data = array(
				1 => array('nb' => 0, 'mnttot' => 0, 'label' => $langs->trans("DisputesOpenInProgress"), 'datefilter' => false),
				8 => array('nb' => 0, 'mnttot' => 0, 'label' => $langs->trans("DisputesLostLastXMonths", $nbofperiod), 'datefilter' => true),
				9 => array('nb' => 0, 'mnttot' => 0, 'label' => $langs->trans("DisputesWonLastXMonths", $nbofperiod), 'datefilter' => true),
			);

			$sql = "SELECT f.dispute_status, SUM(f.total_ttc) as mnttot, COUNT(*) as nb";
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
			$sql .= " AND (f.dispute_status = 1 OR (f.dispute_status IN (8, 9) AND f.datef >= '".$this->db->idate($tmpdate)."'))";
			$sql .= " GROUP BY f.dispute_status";

			$result = $this->db->query($sql);
			if ($result) {
				while ($obj = $this->db->fetch_object($result)) {
					if (isset($data[$obj->dispute_status])) {
						$data[$obj->dispute_status]['nb'] = (int) $obj->nb;
						$data[$obj->dispute_status]['mnttot'] = $obj->mnttot;
					}
				}

				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}

			foreach ($data as $disputestatus => $val) {
				$billurl = DOL_URL_ROOT."/compta/facture/list.php?search_dispute_status=".((int) $disputestatus);
				if ($val['datefilter']) {
					$billurl .= "&search_date_startday=".((int) $tmparray['mday'])."&search_date_startmonth=".((int) $tmparray['mon'])."&search_date_startyear=".((int) $tmparray['year']);
				}
				$billurl .= "&mainmenu=accountancy&leftmenu=customers_bills";

				if ($disputestatus == 1) {
					$badgeclass = ($val['nb'] > 0 ? 'badge badge-danger' : 'badge badge-status4');
				} else {
					//$badgeclass = ($val['nb'] > 0 ? 'badge badge-info' : 'badge badge-info');
					$badgeclass = 'badge badge-info';
				}

				// Column picto
				$this->info_box_contents[$line][0] = array(
					'td' => 'class="left" width="16"',
					'tooltip' => $val['label'],
					'url' => $billurl,
					'logo' => 'bill',
				);

				// Column label
				$this->info_box_contents[$line][1] = array(
					'td' => '',
					'text' => $val['label'],
				);

				// Column value
				$this->info_box_contents[$line][2] = array(
					'td' => 'class="right"',
					'tooltip' => $val['label'],
					'textnoformat' => '<a href="'.$billurl.'" class="nounderlineimp"><span class="center '.$badgeclass.'">'.$val['nb'].'</span></a>',
				);

				// Column amount
				$this->info_box_contents[$line][3] = array(
					'td' => 'class="nowraponall right amount"',
					'text' => price($val['mnttot'], 1, $langs, 0, 0, -1, $conf->currency)
				);

				$line++;
			}
		}

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
