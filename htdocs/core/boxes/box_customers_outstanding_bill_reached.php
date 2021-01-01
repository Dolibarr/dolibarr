<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/boxes/box_clients.php
 *	\ingroup    Facture
 *	\brief      Module d'affichage pour les encours dépassés
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last thirdparties
 */
class box_customers_outstanding_bill_reached extends ModeleBoxes
{
	public $boxcode = "customersoutstandingbillreached";
	public $boximg = "object_company";
	public $boxlabel = "BoxCustomersOutstandingBillReached";
	public $depends = array("facture","societe");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $enabled = 1;

	public $info_box_head = array();
	public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
	 *  @param	string	$param		More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;

		// disable box for such cases
		if (!empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $this->enabled = 0; // disabled by this option

		$this->hidden = !($user->rights->societe->lire && empty($user->socid));
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
		$langs->load("boxes");

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$thirdpartystatic = new Societe($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastOutstandingBillReached", $max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
			$sql .= ", s.code_client, s.code_compta, s.client";
			$sql .= ", s.logo, s.email, s.entity";
			$sql .= ", s.outstanding_limit";
			$sql .= ", s.datec, s.tms, s.status";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE s.client IN (1, 3)";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND s.rowid = $user->socid";
			$sql .= " AND s.outstanding_limit > 0";
			$sql .= " AND s.rowid IN (SELECT fk_soc from ".MAIN_DB_PREFIX."facture as f WHERE f.fk_statut = 1 and f.fk_soc = s.rowid)";
			$sql .= " ORDER BY s.tms DESC";
			//$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);

				$nboutstandingbillreachedcustomers = 0;

				$line = 0;
				while ($line < $num)
				{
					$objp = $this->db->fetch_object($result);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);

					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->name;
					//$thirdpartystatic->name_alias = $objp->name_alias;
					$thirdpartystatic->code_client = $objp->code_client;
					$thirdpartystatic->code_compta = $objp->code_compta;
					$thirdpartystatic->client = $objp->client;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;
					$thirdpartystatic->outstanding_limit = $objp->outstanding_limit;

					$outstandingtotal = $thirdpartystatic->getOutstandingBills()['opened'];
					$outstandinglimit = $thirdpartystatic->outstanding_limit;

					if ($outstandingtotal >= $outstandinglimit)
					{
						$this->info_box_contents[$nboutstandingbillreachedcustomers][] = array(
							'td' => '',
							'text' => $thirdpartystatic->getNomUrl(1, 'customer'),
							'asis' => 1,
						);

						$this->info_box_contents[$nboutstandingbillreachedcustomers][] = array(
							'td' => 'class="right" width="18"',
							'text' => $thirdpartystatic->LibStatut($objp->status, 3)
						);
						$nboutstandingbillreachedcustomers++;
					}

					$line++;
				}

				if ($num == 0 || $nboutstandingbillreachedcustomers == 0) $this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text'=> '<span class="opacitymedium">'.$langs->trans("None").'</span>'
				);

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql)
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
