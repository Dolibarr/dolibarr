<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 *
 */

/**
	    \file       htdocs/includes/boxes/box_factures_fourn_imp.php
        \ingroup    fournisseur
		\brief      Fichier de gestion d'une box des factures fournisseurs impayees
		\version    $Revision$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_factures_fourn_imp extends ModeleBoxes {

    var $boxcode="oldestunpayedsupplierbills";
    var $boximg="bill";
    var $boxlabel;
    var $depends = array("facture","fournisseur");

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_factures_fourn_imp()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxOldesUnpayedSupplierBills");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;

        if ($user->rights->facture->lire)
        {
            $langs->load("boxes");
            
            $this->info_box_head = array('text' => "Les $max plus anciennes factures fournisseurs impayées");

            $sql = "SELECT s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f WHERE f.fk_soc = s.idp AND f.paye=0 AND fk_statut = 1";
            if($user->societe_id)
            {
                $sql .= " AND s.idp = $user->societe_id";
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
                    'logo' => 'object_product',
                    'text' => $objp->facnumber,
                    'url' => DOL_URL_ROOT."/fourn/facture/fiche.php?facid=".$objp->facid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->idp);

                    $i++;
                }
            }

        }
    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
