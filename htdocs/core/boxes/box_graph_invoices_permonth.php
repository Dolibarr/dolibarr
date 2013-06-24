<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/boxes/box_invoice_permonth.php
 *	\ingroup    factures
 *	\brief      Box to show graph of invoices per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last invoices
 */
class box_graph_invoices_permonth extends ModeleBoxes
{
	var $boxcode="invoicespermonth";
	var $boximg="object_bill";
	var $boxlabel="BoxInvoicesPerMonth";
	var $depends = array("facture");

	var $db;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db,$param)
	{
		global $conf;

		$this->db=$db;
		$this->enabled=$conf->global->MAIN_FEATURES_LEVEL;
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$facturestatic=new Facture($db);

		$text = $langs->trans("BoxInvoicesPerMonth",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1
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
			$sql.= " AND f.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id)	$sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY f.tms DESC";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=dol_now();

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
					if ($objp->paye == 0 && ($objp->fk_statut != 2 && $objp->fk_statut != 3) && $datelimite < ($now - $conf->facture->client->warning_delay)) { $late = img_warning(sprintf($l_due_date,dol_print_date($datelimite,'day')));}

					$i++;
				}

				$this->info_box_contents[0][0] = array('td' => 'align="center"','text2'=>'xxxxxxx');
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
