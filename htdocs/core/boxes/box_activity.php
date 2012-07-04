<?php
/* Copyright (C) 2012 Charles-François BENKE <charles.fr@benke.fr>
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
 *	\file       htdocs/core/boxes/box_activity.php
 *	\ingroup    societes
 *	\brief      Module to show box of bills, orders & propal of the current year
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");

class box_activity extends ModeleBoxes
{
	var $boxcode="activity";
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
	function box_activity()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("bills");
		$langs->load("projects");
		$langs->trans("orders");

		$this->boxlabel=$langs->transnoentitiesnoconv("BoxGlobalActivity");
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox()
	{
		global $conf, $user, $langs, $db;

		$totalMnt = 0;
		$totalnb = 0;

		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		$facturestatic=new Facture($db);
		$propalstatic=new Propal($db);
		$commandestatic=new Commande($db);

		$textHead = $langs->trans("Activity")."&nbsp;".date("Y");
		$this->info_box_head = array('text' => $textHead, 'limit'=> dol_strlen($textHead));

		// list the summary of the bills
		if ($conf->facture->enabled && $user->rights->facture->lire)
		{
			$sql = "SELECT f.paye, f.fk_statut, sum(f.total_ttc) as Mnttot, count(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			$sql.= " WHERE f.entity = ".$conf->entity;
			$sql.= " AND f.fk_soc = s.rowid";
			$sql.= " AND (DATE_FORMAT(f.datef,'%Y') = ".date("Y")." or paye=0)";
			$sql.= " GROUP BY f.paye, f.fk_statut ";
			$sql.= " ORDER BY f.fk_statut DESC";

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=gmmktime();
				$i = 0;

				while ($i < $num)
				{
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => 'bill');
					$objp = $db->fetch_object($result);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut($objp->paye,$objp->fk_statut,0));

					if($objp->fk_statut==0)
					{	// draft
						$billurl="viewstatut=0&paye=0";
					} elseif($objp->fk_statut==1)
					{	// unpaid
						$billurl="viewstatut=1&paye=0";
					} else
					{	// paid for current year
						$billurl="viewstatut=2&paye=1";
					}
					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
                    'text' => $objp->nb, 'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&mainmenu=accountancy&leftmenu=customers_bills"
					);
					$totalnb += $objp->nb;
					$this->info_box_contents[$i][3] = array('td' => 'align="right"',
					'text' => dol_trunc(number_format($objp->Mnttot, 0, ',', ' '),40)."&nbsp;".getCurrencySymbol($conf->currency)
					);
					$totalMnt += $objp->Mnttot;
					$this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"',
					'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3)
					);
					$i++;
				}
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedInvoices"));
			} else {
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"', 'maxlength'=>500, 'text' => ($db->error().' sql='.$sql));
			}
		}

		// list the summary of the orders
		if ($conf->commande->enabled && $user->rights->commande->lire)
		{
			$sql = "SELECT c.fk_statut,c.facture, sum(c.total_ttc) as Mnttot, count(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
			$sql.= " WHERE c.entity = ".$conf->entity;
			$sql.= " AND c.fk_soc = s.rowid";
			$sql.= " AND c.facture=0";
			$sql.= " GROUP BY c.fk_statut";
			$sql.= " ORDER BY c.fk_statut DESC";

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result)+$i;
				while ($i < $num)
				{
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"','logo' => 'object_order');

					$objp = $db->fetch_object($result);
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' =>$langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($objp->fk_statut,$objp->facture,0)
					);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => $objp->nb,
					'url' => DOL_URL_ROOT."/commande/liste.php?mainmenu=commercial&leftmenu=orders&viewstatut=".$objp->fk_statut
					);
					$totalnb += $objp->nb;

					$this->info_box_contents[$i][3] = array('td' => 'align="right"',
					'text' => dol_trunc(number_format($objp->Mnttot, 0, ',', ' '),40)."&nbsp;".getCurrencySymbol($conf->currency)
					);
					$totalMnt += $objp->Mnttot;
					$this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"', 'text' => $commandestatic->LibStatut($objp->fk_statut,$objp->facture,3));

					$i++;
				}
			}
		}

		// list the summary of the propals
		if ($conf->propal->enabled && $user->rights->propal->lire)
		{
			$sql = "SELECT p.fk_statut, sum(p.total) as Mnttot, count(*) as nb";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
			$sql.= " WHERE p.entity = ".$conf->entity;
			$sql.= " AND p.fk_soc = s.rowid";
			$sql.= " AND DATE_FORMAT(p.datep,'%Y') = ".date("Y");
			$sql.= " AND p.date_cloture IS NULL "; // just unclosed
			$sql.= " GROUP BY p.fk_statut";
			$sql.= " ORDER BY p.fk_statut DESC";

			$result = $db->query($sql);

			if ($result)
			{
				$num = $db->num_rows($result)+$i;
				while ($i < $num)
				{
					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"','logo' => 'object_propal');

					$objp = $db->fetch_object($result);
					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
					'text' =>$langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($objp->fk_statut,0)
					);

					$this->info_box_contents[$i][2] = array('td' => 'align="right"',
					'text' => $objp->nb,
					'url' => DOL_URL_ROOT."/comm/propal/list.php?mainmenu=commercial&leftmenu=propals&viewstatut=".$objp->fk_statut
					);
					$totalnb += $objp->nb;

					$this->info_box_contents[$i][3] = array('td' => 'align="right"',
					'text' => dol_trunc(number_format($objp->Mnttot, 0, ',', ' '),40)."&nbsp;".getCurrencySymbol($conf->currency)
					);
					$totalMnt += $objp->Mnttot;
					$this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"', 'text' => $propalstatic->LibStatut($objp->fk_statut,3));

					$i++;
				}
			}
		}

		// Add the sum in the bottom of the boxes
		$this->info_box_contents[$i][1] = array('td' => 'align="left" ', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$i][2] = array('td' => 'align="right" ', 'text' => number_format($totalnb, 0, ',', ' '));
		$this->info_box_contents[$i][3] = array('td' => 'align="right" ', 'text' => number_format($totalMnt, 0, ',', ' ')."&nbsp;".getCurrencySymbol($conf->currency));
		$this->info_box_contents[$i][4] = array('td' => 'align="right" ', 'text' => "");
		$this->info_box_contents[$i][5] = array('td' => 'align="right"', 'text' => "");
	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
?>
