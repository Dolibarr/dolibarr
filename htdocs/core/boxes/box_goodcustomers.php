<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2016      Charlie Benke        <charlie@patas-monkey.com>
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
 *	\file       htdocs/core/boxes/box_goodcustomers.php
 *	\ingroup    societes
 *	\brief      Module to generated widget of best customers (the most invoiced)
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last thirdparties
 */
class box_goodcustomers extends ModeleBoxes
{
    public $boxcode = "goodcustomers";
    public $boximg = "object_company";
    public $boxlabel = "BoxGoodCustomers";
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
		if (empty($conf->global->MAIN_BOX_ENABLE_BEST_CUSTOMERS)) $this->enabled = 0; // not enabled by default. Very slow on large database

		$this->hidden = !($user->rights->societe->lire);
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

        $this->info_box_head = array('text' => $langs->trans("BoxTitleGoodCustomers", $max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.rowid, s.nom as name, s.logo, s.code_client, s.code_fournisseur, s.client, s.fournisseur, s.tms as datem, s.status as status,";
			$sql .= " count(*) as nbfact, sum(".$this->db->ifsql('f.paye=1', '1', '0').") as nbfactpaye";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
			$sql .= ' WHERE s.entity IN ('.getEntity('societe').')';
			$sql .= ' AND s.rowid = f.fk_soc';
			$sql .= " GROUP BY s.rowid, s.nom, s.logo, s.code_client, s.code_fournisseur, s.client, s.fournisseur, s.tms, s.status";
			$sql .= $this->db->order("nbfact", "DESC");
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
					$datem = $this->db->jdate($objp->tms);
					$thirdpartystatic->id = $objp->rowid;
					$thirdpartystatic->name = $objp->name;
					$thirdpartystatic->code_client = $objp->code_client;
					$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->client = $objp->client;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$nbfact = $objp->nbfact;
					$nbimpaye = $objp->nbfact - $objp->nbfactpaye;

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
					    'td' => 'class="right"',
					    'text' => $nbfact.($nbimpaye != 0 ? ' ('.$nbimpaye.')' : '')
					);

					$this->info_box_contents[$line][] = array(
					    'td' => 'class="right" width="18"',
					    'text' => $thirdpartystatic->LibStatut($objp->status, 3)
					);

					$line++;
				}

				if ($num == 0) $this->info_box_contents[$line][0] = array('td' => 'class="center"', 'text'=>$langs->trans("NoRecordedCustomers"));

				$this->db->free($result);
			}
			else {
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($this->db->error().' sql='.$sql),
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
