<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
    \file       htdocs/includes/boxes/box_factures_imp.php
    \ingroup    factures
    \brief      Module de génération de l'affichage de la box factures impayees
*/

require_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');


class box_factures_imp extends ModeleBoxes {

    var $boxcode="oldestunpayedcustomerbills";
    var $boximg="object_bill";
    var $boxlabel;
    var $depends = array("facture");

	var $db;
	var $param;

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
        global $conf, $user, $langs, $db;

        $facturestatic=new Facture($db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpayedCustomerBills",$max));

        if ($user->rights->facture->lire)
        {
            $sql = "SELECT s.nom, s.rowid as socid,";
            $sql.= " f.facnumber,".$db->pdate("f.date_lim_reglement")." as datelimite,";
            $sql.= " f.amount,".$db->pdate("f.datef")." as df,";
            $sql.= " f.paye, f.fk_statut, f.rowid as facid";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql .= " WHERE f.fk_soc = s.rowid AND f.paye=0 AND fk_statut = 1";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if($user->societe_id)
            {
                $sql .= " AND s.rowid = ".$user->societe_id;
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
                    if ($objp->datelimite < (time() - $conf->facture->warning_delay)) $late=img_warning($langs->trans("Late"));

                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => $objp->facnumber,
                    'text2'=> $late,
                    'url' => DOL_URL_ROOT."/compta/facture.php?facid=".$objp->facid);

                    $this->info_box_contents[$i][1] = array('align' => 'left',
                    'text' => $objp->nom,
                    'maxlength'=>44,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][2] = array(
                    'align' => 'right',
                    'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3));

                    $i++;
                }
                
                $i=$num;
                while ($i < $max)
                {
                    if ($num==0 && $i==$num)
                    {
                        $this->info_box_contents[$i][0] = array('align' => 'center','text'=>$langs->trans("NoUnpayedCustomerBills"));
                        $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                    } else {
                        $this->info_box_contents[$i][0] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][2] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][3] = array('text'=>'&nbsp;');
                    }
                    $i++;
                }
            }
            else
            {
    	        $this->info_box_contents[0][0] = array(	'align' => 'left',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
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
