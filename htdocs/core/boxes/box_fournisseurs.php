<?php
/* Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * \file       htdocs/core/boxes/box_fournisseurs.php
 * \ingroup    fournisseurs
 * \brief      Module to generate box of suppliers
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last suppliers
 */
class box_fournisseurs extends ModeleBoxes
{
    var $boxcode="lastsuppliers";
    var $boximg="object_company";
    var $boxlabel="BoxLastSuppliers";
    var $depends = array("fournisseur");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max=5)
    {
        global $conf, $user, $langs, $db;
        $langs->load("boxes");

		$this->max=$max;

        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $thirdpartystatic=new Societe($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedSuppliers",$max));

        if ($user->rights->societe->lire)
        {
            $sql = "SELECT s.nom, s.rowid as socid, s.datec, s.tms, s.status";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.fournisseur = 1";
            $sql.= " AND s.entity IN (".getEntity('societe', 1).")";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
            $sql.= " ORDER BY s.tms DESC ";
            $sql.= $db->plimit($max, 0);

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);

                $i = 0;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
    				$datec=$db->jdate($objp->datec);
    				$datem=$db->jdate($objp->tms);

                    $this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/fourn/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/fourn/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => dol_print_date($datem, "day"));

                    $this->info_box_contents[$i][3] = array('td' => 'align="right" width="18"',
                    'text' => $thirdpartystatic->LibStatut($objp->status,3));

                    $i++;
                }

                if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedSuppliers"));

				$db->free($result);
            }
            else {
                $this->info_box_contents[0][0] = array( 'td' => 'align="left"',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
            }
        }
        else {
            $this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }

    }

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
