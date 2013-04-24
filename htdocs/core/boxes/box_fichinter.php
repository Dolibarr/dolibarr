<?php
/* Copyright (C) 2012-2013	Charles-François BENKE <charles.fr@benke.fr>
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
 *	\file       htdocs/core/boxes/box_fichinter.php
 *	\ingroup    intervention
 *	\brief      Module to show FichInter activity of the current Year
 *	\version	$Id: box_fichinter.php,v 1.1 2012/09/11 Charles-François BENKE
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

class box_fichinter extends ModeleBoxes {

	var $boxcode="fichinter";
	var $boximg="object_intervention";
	var $boxlabel;
	//var $depends = array("projet");
	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function __construct()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("interventions");

		$this->boxlabel="Intervention";
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max=$max;
		
		$totalMnt = 0;
		$totalnb = 0;
		$totalnbTask=0;
		include_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
		require_once(DOL_DOCUMENT_ROOT."/core/lib/fichinter.lib.php");
		$fichinterstatic=new Fichinter($db);
		


		$textHead = $langs->trans("Interventions")."&nbsp;".date("Y");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the orders
		if ($user->rights->ficheinter->lire)
		{
			
			$sql = "SELECT fi.fk_statut, count(fi.rowid) as nb";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."fichinter as fi";
			$sql.= ")";
			$sql.= " WHERE fi.fk_soc = s.rowid";
			$sql.= " AND s.entity = ".$conf->entity;
			$sql.= " AND DATE_FORMAT(fi.datec,'%Y') = ".date("Y")." ";
			$sql.= " GROUP BY fi.fk_statut ";
			$sql.= " ORDER BY fi.fk_statut DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"','logo' => 'object_intervention');

					$objp = $db->fetch_object($result);
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' =>$langs->trans("Intervention")."&nbsp;".$fichinterstatic->LibStatut($objp->fk_statut,0)
					);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => $objp->nb."&nbsp;".$langs->trans("Interventions"),
					'url' => DOL_URL_ROOT."/fichinter/list.php?mainmenu=commercial&leftmenu=fichinter&viewstatut=".$objp->fk_statut
					);
					$totalnb += $objp->nb;
					
					$sql = "SELECT sum(fid.total_ht) as Mnttot, sum(fid.duree) as nbHrs";
					$sql.= " FROM ".MAIN_DB_PREFIX."fichinterdet as fid, ".MAIN_DB_PREFIX."fichinter as fi";
					$sql.= " WHERE fid.fk_fichinter = fi.rowid";
					$sql.= " AND fi.entity = ".$conf->entity;
					$sql.= " AND (DATE_FORMAT(fi.datec,'%Y') = ".date("Y").")";
					$sql.= " AND fi.fk_statut=".$objp->fk_statut;
					$resultdet = $db->query($sql);
					if ($resultdet)
					{
						$objdet = $db->fetch_object($resultdet);
						$this->info_box_contents[$i][3] = array('td' => 'align="right"', 'text' => ConvertSecondToTime($objdet->nbHrs,'all',25200,5));						
						$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => number_format($objdet->Mnttot, 0, ',', ' ')."&nbsp;".$langs->trans("Currency".$conf->currency));

						$totalMnt += $objdet->Mnttot;
						$totalnbHrs += $objdet->nbHrs;
					}
					else
					{
						$this->info_box_contents[$i][3] = array('td' => 'align="right"', 'text' => number_format(0 , 0, ',', ' '));
						$this->info_box_contents[$i][4] = array('td' => 'align="right"', 'text' => dol_trunc(number_format(0 , 0, ',', ' '),40)."&nbsp;".$langs->trans("Currency".$conf->currency));
					}
					

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"', 'text' => $fichinterstatic->LibStatut($objp->fk_statut,3));
					$i++;
				}
			}
		}


		// Add the sum to the bottom of the boxes
		$this->info_box_contents[$i][0] = array('tr' => 'class="liste_total"', 'td' => 'colspan=2 align="left" class="liste_total"', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$i][1] = array('td' => 'align="right" class="liste_total"', 'text' => number_format($totalnb, 0, ',', ' ')."&nbsp;".$langs->trans("Interventions"));
		$this->info_box_contents[$i][2] = array('td' => 'align="right" class="liste_total"', 'text' => ConvertSecondToTime($totalnbHrs,'all',25200,5));
		$this->info_box_contents[$i][3] = array('td' => 'align="right" class="liste_total"', 'text' => number_format($totalMnt, 0, ',', ' ')."&nbsp;".$langs->trans("Currency".$conf->currency));
		$this->info_box_contents[$i][4] = array('td' => 'colspan=2 class="liste_total"' , 'text' => "");	
		
	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
?>