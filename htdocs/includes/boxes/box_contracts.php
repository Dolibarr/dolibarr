<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 		\file       htdocs/includes/boxes/box_contracts.php
 * 		\ingroup    contracts
 * 		\brief      Module de generation de l'affichage de la box contracts
 * 		\version	$Id: box_contracts.php,v 1.10 2011/07/31 23:29:10 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_contracts extends ModeleBoxes {

    var $boxcode="lastcontracts";
    var $boximg="object_contract";
    var $boxlabel;
    var $depends = array("contrat");	// conf->contrat->enabled

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *      \brief      Constructeur de la classe
     */
    function box_contracts()
    {
    	global $langs;

    	$langs->load("contracts");

    	$this->boxlabel=$langs->trans("BoxLastContracts");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
    	global $user, $langs, $db, $conf;

    	$this->max=$max;

    	include_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    	$contractstatic=new Contrat($db);

    	$this->info_box_head = array('text' => $langs->trans("BoxTitleLastContracts",$max));

    	if ($user->rights->contrat->lire)
    	{
    		$sql = "SELECT s.nom, s.rowid as socid,";
    		$sql.= " c.rowid, c.ref, c.statut as fk_statut, c.date_contrat, c.datec, c.fin_validite, c.date_cloture";
    		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    		$sql.= " WHERE c.fk_soc = s.rowid";
    		$sql.= " AND c.entity = ".$conf->entity;
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    		if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
    		$sql.= " ORDER BY c.date_contrat DESC, c.ref DESC ";
    		$sql.= $db->plimit($max, 0);

    		$resql = $db->query($sql);
    		if ($resql)
    		{
    			$num = $db->num_rows($resql);
    			$now=gmmktime();

    			$i = 0;

    			while ($i < $num)
    			{
    				$objp = $db->fetch_object($resql);
    				$datec=$db->jdate($objp->datec);
    				$dateterm=$db->jdate($objp->fin_validite);
    				$dateclose=$db->jdate($objp->date_cloture);
    				$late = '';

    				$contractstatic->statut=$objp->fk_statut;
    				$contractstatic->id=$objp->rowid;
    				$result=$contractstatic->fetch_lines();

    				// fin_validite is no more on contract but on services
    				// if ($objp->fk_statut == 1 && $dateterm < ($now - $conf->contrat->cloture->warning_delay)) { $late = img_warning($langs->trans("Late")); }

    				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
    				'logo' => $this->boximg,
    				'url' => DOL_URL_ROOT."/contrat/fiche.php?id=".$objp->rowid);

    				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
    				'text' => ($objp->ref?$objp->ref:$objp->rowid),	// Some contracts have no ref
    				'text2'=> $late,
    				'url' => DOL_URL_ROOT."/contrat/fiche.php?id=".$objp->rowid);

    				$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
    				'logo' => 'company',
    				'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

    				$this->info_box_contents[$i][3] = array('td' => 'align="left"',
    				'text' => dol_trunc($objp->nom,40),
    				'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

    				$this->info_box_contents[$i][4] = array('td' => 'align="right"',
    				'text' => dol_print_date($datec,'day'));

    				$this->info_box_contents[$i][5] = array('td' => 'align="right" nowrap="nowrap"',
    				'text' => $contractstatic->getLibStatut(6),
    				'asis'=>1
    				);

    				$i++;
    			}

    			if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedContracts"));
    		}
    		else
    		{
    			dol_print_error($db);
    		}
    	}
    	else
    	{
    		$this->info_box_contents[0][0] = array('td' => 'align="left"',
    		'text' => $langs->trans("ReadPermissionNotAllowed"));
    	}
    }

    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
