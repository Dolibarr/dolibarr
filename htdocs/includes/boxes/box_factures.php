<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/includes/boxes/box_factures.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures
 *	\version	$Id$
 */
include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_factures extends ModeleBoxes {

	var $boxcode="lastcustomerbills";
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
	function box_factures()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("BoxLastCustomerBills");
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
		$facturestatic=new Facture($db);

		$text = $langs->trans("BoxTitleLastCustomerBills",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> strlen($text)
		);

		if ($user->rights->facture->lire)
		{
			$sql = "SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.datef as df";
			$sql.= ", f.paye, f.fk_statut, f.datec, f.tms";
			$sql.= ", s.nom, s.rowid as socid";
			$sql.= ", f.date_lim_reglement as datelimite";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= ")";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND s.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id)	$sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY f.tms DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=gmmktime();

				$i = 0;
				$l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateEcheance')).': %s)';

				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$datelimite=$db->jdate($objp->datelimite);
					$datec=$db->jdate($objp->datec);

					$picto='bill';
					if ($objp->type == 1) $picto.='r';
					if ($objp->type == 2) $picto.='a';
					$late = '';
					if ($objp->paye == 0 && ($objp->fk_statut != 2 && $objp->fk_statut != 3) && $datelimite < ($now - $conf->facture->warning_delay)) { $late = img_warning(sprintf($l_due_date,dol_print_date($datelimite,'day')));}

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $picto,
                    'url' => DOL_URL_ROOT."/compta/facture.php?facid=".$objp->facid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->facnumber,
                    'text2'=> $late,
                    'url' => DOL_URL_ROOT."/compta/facture.php?facid=".$objp->facid);

					$this->info_box_contents[$i][2] = array('td' => 'align="left"',
                    'text' => $objp->nom,
                    'maxlength'=>40,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][3] = array('td' => 'align="right"',
                    'text' => dol_print_date($datec,'day'),
					);

					$this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"',
                    'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3));

					$i++;
				}

				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedInvoices"));
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}

		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
