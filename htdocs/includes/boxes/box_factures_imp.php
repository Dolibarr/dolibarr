<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    \file       htdocs/includes/boxes/box_factures_imp.php
    \ingroup    factures
    \brief      Module de génération de l'affichage de la box factures impayees
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_factures_imp extends ModeleBoxes {

    var $boxcode="oldestunpayedcustomerbills";
    var $boximg="object_bill";
    var $boxlabel;
    var $depends = array("facture");

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *      \brief      Constructeur de la classe
     */
    function box_factures_imp()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxOldestUnpayedCustomerBills");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        $warning_delay=31*24*60*60; // Delai affichage warning retard (si retard paiement facture > delai)

        $this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpayedCustomerBills",$max));

        if ($user->rights->facture->lire)
        {
            $sql = "SELECT s.nom,s.idp,f.facnumber,".$db->pdate("f.date_lim_reglement")." as datelimite, f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp AND f.paye=0 AND fk_statut = 1";
            if($user->societe_id)
            {
                $sql .= " AND s.idp = $user->societe_id";
            }
            //$sql .= " ORDER BY f.datef DESC, f.facnumber DESC ";
            $sql .= " ORDER BY f.datef ASC, f.facnumber ASC ";
            $sql .= $db->plimit($max, 0);

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);

                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $late="";
                    if ($objp->datelimite < (time() - $warning_delay)) $late=img_warning($langs->trans("Late"));

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => $objp->facnumber,
                    'text2'=> $late,
                    'url' => DOL_URL_ROOT."/compta/facture.php?facid=".$objp->facid);
                   
                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->nom,
                    'maxlength'=>44,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->idp);

                    $i++;
                }
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
