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
 *	\ingroup    societes
 *	\brief      Module de generation de l'affichage de la box clients
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last thirdparties
 */
class box_clients extends ModeleBoxes
{
    public $boxcode = "lastcustomers";
    public $boximg = "object_company";
    public $boxlabel = "BoxLastCustomers";
    public $depends = array("societe");

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

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedCustomers", $max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.nom as name, s.rowid as socid";
            $sql .= ", s.code_client";
            $sql .= ", s.client";
            $sql .= ", s.code_fournisseur";
            $sql .= ", s.fournisseur";
            $sql .= ", s.code_compta";
            $sql .= ", s.code_compta_fournisseur";
            $sql .= ", s.logo";
            $sql .= ", s.email";
            $sql .= ", s.datec, s.tms, s.status, s.entity";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE s.client IN (1, 3)";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			if ($user->socid) $sql .= " AND s.rowid = $user->socid";
			$sql .= " ORDER BY s.tms DESC";
			$sql .= $this->db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);

				$line = 0;
				while ($line < $num)
				{
					$objp = $this->db->fetch_object($result);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->tms);
                    $thirdpartystatic->id = $objp->socid;
                    $thirdpartystatic->name = $objp->name;
                    $thirdpartystatic->code_client = $objp->code_client;
                    $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartystatic->code_compta = $objp->code_compta;
                    $thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
                    $thirdpartystatic->client = $objp->client;
                    $thirdpartystatic->fournisseur = $objp->fournisseur;
                    $thirdpartystatic->logo = $objp->logo;
                    $thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $thirdpartystatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($datem, "day")
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $thirdpartystatic->LibStatut($objp->status, 3)
                    );

					$line++;
				}

				if ($num == 0) $this->info_box_contents[$line][0] = array(
					'td' => 'class="center opacitymedium"',
					'text'=>$langs->trans("NoRecordedCustomers")
				);

				$this->db->free($result);
			}
			else {
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($this->db->error().' sql='.$sql)
                );
			}
		}
		else {
			$this->info_box_contents[0][0] = array(
			    'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
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
