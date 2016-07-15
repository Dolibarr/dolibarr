<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   \file       htdocs/core/boxes/box_prospect.php
 *   \ingroup    societe
 *   \brief      Module to generate the last prospects box.
 */


include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';


/**
 * Class to manage the box to show last prospects
 */
class box_prospect extends ModeleBoxes
{
	var $boxcode="lastprospects";
	var $boximg="object_company";
	var $boxlabel="BoxLastProspects";
	var $depends = array("societe");

	var $db;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user;

		$this->db = $db;

		// disable box for such cases
		if (! empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) $this->enabled=0;	// disabled by this option
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		$thirdpartystatic=new Client($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedProspects",$max));

		if ($user->rights->societe->lire)
		{
			$sql = "SELECT s.nom as name, s.rowid as socid";
			$sql.= ", s.code_client";
            $sql.= ", s.client";
            $sql.= ", s.code_fournisseur";
            $sql.= ", s.fournisseur";
            $sql.= ", s.logo";
			$sql.= ", s.fk_stcomm, s.datec, s.tms, s.status";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE s.client IN (2, 3)";
			$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY s.tms DESC";
			$sql.= $db->plimit($max, 0);

			dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				$line = 0;
				while ($line < $num)
				{
					$objp = $db->fetch_object($resql);
					$datec=$db->jdate($objp->datec);
					$datem=$db->jdate($objp->tms);
					$thirdpartystatic->id = $objp->socid;
                    $thirdpartystatic->name = $objp->name;
                    $thirdpartystatic->code_client = $objp->code_client;
                    $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartystatic->client = $objp->client;
                    $thirdpartystatic->fournisseur = $objp->fournisseur;
                    $thirdpartystatic->logo = $objp->logo;

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="left"',
                        'text' => $thirdpartystatic->getNomUrl(1),
                    	'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right"',
                        'text' => dol_print_date($datem, "day"),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => str_replace('img ','img height="14" ',$thirdpartystatic->LibProspCommStatut($objp->fk_stcomm,3)),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => $thirdpartystatic->LibStatut($objp->status,3),
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'align="center"',
                        'text'=>$langs->trans("NoRecordedProspects"),
                );

                $db->free($resql);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

