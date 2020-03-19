<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2018      Josep Lluís Amador   <joseplluis@lliuretic.cat>
 * Copyright (C) 2020      Ferran Marcet	    <fmarcet@2byte.es>
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
 *	\file       htdocs/core/boxes/box_contacts.php
 *	\ingroup    contacts
 *	\brief      Module to show box of contacts
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


/**
 * Class to manage the box to show last contacts
 */
class box_contacts extends ModeleBoxes
{
    public $boxcode = "lastcontacts";
    public $boximg = "object_contact";
    public $boxlabel = "BoxLastContacts";
    public $depends = array("societe");

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

	    $this->hidden = !($user->rights->societe->lire && $user->rights->societe->contact->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;
		$langs->load("boxes");

		$this->max = $max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedContacts", $max));

		if ($user->rights->societe->lire && $user->rights->societe->contact->lire)
		{
			$sql = "SELECT sp.rowid as id, sp.lastname, sp.firstname, sp.civility as civility_id, sp.datec, sp.tms, sp.fk_soc, sp.statut as status";

			$sql .= ", sp.address, sp.zip, sp.town, sp.phone, sp.phone_perso, sp.phone_mobile, sp.email as spemail";
			$sql .= ", s.nom as socname, s.name_alias, s.email as semail";
			$sql .= ", s.client, s.fournisseur, s.code_client, s.code_fournisseur";
			$sql .= ", co.label as country, co.code as country_code";
			$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON sp.fk_pays = co.rowid";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON sp.fk_soc = s.rowid";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE sp.entity IN (".getEntity('socpeople').")";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND sp.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND sp.fk_soc = ".$user->socid;
			$sql .= " ORDER BY sp.tms DESC";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$contactstatic = new Contact($this->db);
				$societestatic = new Societe($this->db);

				$line = 0;
				while ($line < $num)
				{
					$objp = $this->db->fetch_object($result);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);

					$contactstatic->id = $objp->id;
					$contactstatic->lastname = $objp->lastname;
					$contactstatic->firstname = $objp->firstname;
					$contactstatic->civility_id = $objp->civility_id;
					$contactstatic->statut = $objp->status;
					$contactstatic->phone_pro = $objp->phone;
					$contactstatic->phone_perso = $objp->phone_perso;
					$contactstatic->phone_mobile = $objp->phone_mobile;
                    $contactstatic->email = $objp->spemail;
					$contactstatic->address = $objp->address;
					$contactstatic->zip = $objp->zip;
					$contactstatic->town = $objp->town;
					$contactstatic->country = $objp->country;
					$contactstatic->country_code = $objp->country_code;

					$societestatic->id = $objp->fk_soc;
					$societestatic->name = $objp->socname;
                    $societestatic->email = $objp->semail;
					$societestatic->name_alias = $objp->name_alias;
					$societestatic->code_client = $objp->code_client;
					$societestatic->code_fournisseur = $objp->code_fournisseur;
					$societestatic->client = $objp->client;
					$societestatic->fournisseur = $objp->fournisseur;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $contactstatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => ($objp->fk_soc > 0 ? $societestatic->getNomUrl(1) : ''),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right"',
						'text' => dol_print_date($datem, "day"),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowrap right" width="18"',
						'text' => $contactstatic->getLibStatut(3),
						'asis'=>1,
					);

					$line++;
				}

				if ($num == 0)
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedContacts").'</span>',
						'asis'=> 1
					);

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
				'td' => 'class="nohover opacitymedium left"',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head		Array with properties of box title
	 *	@param  array	$contents	Array with properties of box lines
	 *	@param	int	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
