<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *		\file       htdocs/core/boxes/box_commandes.php
 *		\ingroup    commande
 *		\brief      Widget for latest sale orders
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last customer orders
 */
class box_commandes extends ModeleBoxes
{
	public $boxcode  = "lastcustomerorders";
	public $boximg   = "object_order";
	public $boxlabel = "BoxLastCustomerOrders";
	public $depends  = array("commande");

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

		$this->hidden = empty($user->rights->commande->lire);
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
		$langs->load('orders');

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$commandestatic = new Commande($this->db);
		$societestatic = new Societe($this->db);
		$userstatic = new User($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLast".(getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE') ? "" : "Modified")."CustomerOrders", $max));

		if ($user->hasRight('commande', 'lire')) {
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", c.ref, c.tms";
			$sql .= ", c.rowid";
			$sql .= ", c.date_commande";
			$sql .= ", c.ref_client";
			$sql .= ", c.fk_statut";
			$sql .= ", c.fk_user_valid";
			$sql .= ", c.facture";
			$sql .= ", c.total_ht";
			$sql .= ", c.total_tva";
			$sql .= ", c.total_ttc";
			$sql .= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
			if (!$user->hasRight('societe', 'client', 'voir') && !$user->socid) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE c.fk_soc = s.rowid";
			$sql .= " AND c.entity IN (".getEntity('commande').")";
			if (getDolGlobalString('ORDER_BOX_LAST_ORDERS_VALIDATED_ONLY')) {
				$sql .= " AND c.fk_statut = 1";
			}
			if (!$user->hasRight('societe', 'client', 'voir') && !$user->socid) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($user->socid) {
				$sql .= " AND s.rowid = ".((int) $user->socid);
			}
			if (getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE')) {
				$sql .= " ORDER BY c.date_commande DESC, c.ref DESC ";
			} else {
				$sql .= " ORDER BY c.tms DESC, c.ref DESC ";
			}
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$date = $this->db->jdate($objp->date_commande);
					$datem = $this->db->jdate($objp->tms);

					$commandestatic->id = $objp->rowid;
					$commandestatic->ref = $objp->ref;
					$commandestatic->ref_client = $objp->ref_client;
					$commandestatic->total_ht = $objp->total_ht;
					$commandestatic->total_tva = $objp->total_tva;
					$commandestatic->total_ttc = $objp->total_ttc;
					$commandestatic->date = $date;
					$commandestatic->date_modification = $datem;

					$societestatic->id = $objp->socid;
					$societestatic->name = $objp->name;
					//$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_compta = $objp->code_compta;
					$societestatic->client = $objp->client;
					$societestatic->logo = $objp->logo;
					$societestatic->email = $objp->email;
					$societestatic->entity = $objp->entity;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $commandestatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $societestatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
					);

					if (getDolGlobalString('ORDER_BOX_LAST_ORDERS_SHOW_VALIDATE_USER')) {
						if ($objp->fk_user_valid > 0) {
							$userstatic->fetch($objp->fk_user_valid);
						}
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => (($objp->fk_user_valid > 0) ? $userstatic->getNomUrl(1) : ''),
							'asis' => 1,
						);
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, 'day', 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $commandestatic->LibStatut($objp->fk_statut, $objp->facture, 3),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedOrders").'</span>'
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
