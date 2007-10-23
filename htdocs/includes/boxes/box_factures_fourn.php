<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
	    \file       htdocs/includes/boxes/box_factures_fourn.php
        \ingroup    fournisseur
		\brief      Fichier de gestion d'une box des factures fournisseurs
		\version    $Revision$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_factures_fourn extends ModeleBoxes {

    var $boxcode="lastsupplierbills";
    var $boximg="object_bill";
    var $boxlabel;
    var $depends = array("facture","fournisseur");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_factures_fourn()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastSupplierBills");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;

		include_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
        $facturestatic=new FactureFournisseur($db);

        $this->info_box_head = array(
				'text' => $langs->trans("BoxTitleLastSupplierBills",$max),
			);

        if ($user->rights->fournisseur->facture->lire)
        {
            $sql = "SELECT s.nom, s.rowid as socid,";
            $sql.= " f.rowid as facid, f.facnumber, f.amount,".$db->pdate("f.datef")." as df,";
            $sql.= " f.paye, f.fk_statut, f.datec";
            if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f";
            if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql .= " WHERE f.fk_soc = s.rowid";
            if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if($user->societe_id)
            {
                $sql .= " AND s.rowid = ".$user->societe_id;
            }
            $sql .= " ORDER BY f.datef DESC, f.facnumber DESC ";
            $sql .= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result)
            {
                $num = $db->num_rows();

                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => $objp->facnumber,
                    'url' => DOL_URL_ROOT."/fourn/facture/fiche.php?facid=".$objp->facid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/fourn/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][2] = array(
                    'align' => 'right',
                    'text' => dolibarr_print_date($objp->datec,'day'));

                    $this->info_box_contents[$i][3] = array(
                    'align' => 'right',
					'width' => 18,
                    'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3));
                    
                    $i++;
                }
            }
            else {
                dolibarr_print_error($db);
            }
        }
        else {
            $this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
