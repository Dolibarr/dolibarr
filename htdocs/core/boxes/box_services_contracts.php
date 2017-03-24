<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/core/boxes/box_services_contracts.php
 *		\ingroup    produits,services
 *      \brief      Module de generation de l'affichage de la box services_vendus
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last services lines
 */
class box_services_contracts extends ModeleBoxes
{
	var $boxcode="lastproductsincontract";
	var $boximg="object_product";
	var $boxlabel="BoxLastProductsInContract";
	var $depends = array("service","contrat");

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
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

		$this->info_box_head = array('text' => $langs->trans("BoxLastProductsInContract",$max));

		if ($user->rights->service->lire && $user->rights->contrat->lire)
		{
		    $contractstatic=new Contrat($db);
		    $contratlignestatic=new ContratLigne($db);
		    $thirdpartytmp = new Societe($db);

			$sql = "SELECT s.nom as name, s.rowid as socid,";
			$sql.= " c.rowid, c.ref, c.statut as contract_status,";
			$sql.= " cd.rowid as cdid, cd.tms as datem, cd.statut, cd.label, cd.description, cd.product_type as type,";
			$sql.= " p.rowid as product_id, p.ref as product_ref";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contrat as c ON s.rowid = c.fk_soc";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= "INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			$sql.= ")";
			$sql.= " WHERE c.entity = ".$conf->entity;
			if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= $db->order("c.tms","DESC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=dol_now();

				$i = 0;

				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$datem=$db->jdate($objp->datem);

					$contratlignestatic->id=$objp->cdid;
					$contratlignestatic->fk_contrat=$objp->rowid;
					$contratlignestatic->label=$objp->label;
					$contratlignestatic->description=$objp->description;
					$contratlignestatic->type=$objp->type;
					$contratlignestatic->product_id=$objp->product_id;
					$contratlignestatic->product_ref=$objp->product_ref;

                    $contractstatic->statut=$objp->contract_status;
					$contractstatic->id=$objp->rowid;
					$contractstatic->ref=$objp->ref;

					$thirdpartytmp->name = $objp->name;
					$thirdpartytmp->id = $objp->socid;

					// Multilangs
					if (! empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
					{
						$sqld = "SELECT label";
						$sqld.= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sqld.= " WHERE fk_product=".$objp->product_id;
						$sqld.= " AND lang='". $langs->getDefaultLang() ."'";
						$sqld.= " LIMIT 1";

						$resultd = $db->query($sqld);
						if ($resultd)
						{
							$objtp = $db->fetch_object($resultd);
							if ($objtp->label != '') $contratlignestatic->label = $objtp->label;
						}
					}

					$this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax100 maxwidth100onsmartphone"',
                    'text' => $contratlignestatic->getNomUrl(1),
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => '',
                    'text' => $contractstatic->getNomUrl(1),
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax100 maxwidth100onsmartphone"',
                    'text' => $thirdpartytmp->getNomUrl(1),
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => '',
                    'text' => dol_print_date($datem,'day'));

					$this->info_box_contents[$i][] = array('td' => 'align="right" width="18"',
                    'text' => $contratlignestatic->LibStatut($objp->statut,3)
					);

					$i++;
				}
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoContractedProducts"));

				$db->free($result);
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
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

