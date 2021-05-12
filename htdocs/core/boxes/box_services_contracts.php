<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2017 	   Nicolas Zabouri      <info@inovea-conseil.com>
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
 *      \brief      Widget of sells products
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last contracted products/services lines
 */
class box_services_contracts extends ModeleBoxes
{
<<<<<<< HEAD
	var $boxcode="lastproductsincontract";
	var $boximg="object_product";
	var $boxlabel="BoxLastProductsInContract";
	var $depends = array("service","contrat");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();
=======
    public $boxcode="lastproductsincontract";
    public $boximg="object_product";
    public $boxlabel="BoxLastProductsInContract";
    public $depends = array("service","contrat");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;

    public $info_box_head = array();
    public $info_box_contents = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
<<<<<<< HEAD
	function __construct($db,$param)
=======
	public function __construct($db, $param)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    global $user;

	    $this->db=$db;

	    $this->hidden=! ($user->rights->service->lire && $user->rights->contrat->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
<<<<<<< HEAD
	function loadBox($max=5)
=======
	public function loadBox($max = 5)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

		$form = new Form($db);

<<<<<<< HEAD
		$this->info_box_head = array('text' => $langs->trans("BoxLastProductsInContract",$max));
=======
		$this->info_box_head = array('text' => $langs->trans("BoxLastProductsInContract", $max));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		if ($user->rights->service->lire && $user->rights->contrat->lire)
		{
		    $contractstatic=new Contrat($db);
<<<<<<< HEAD
		    $contratlignestatic=new ContratLigne($db);
		    $thirdpartytmp = new Societe($db);
		    $productstatic = new Product($db);

			$sql = "SELECT s.nom as name, s.rowid as socid,";
			$sql.= " c.rowid, c.ref, c.statut as contract_status,";
=======
		    $contractlinestatic=new ContratLigne($db);
		    $thirdpartytmp = new Societe($db);
		    $productstatic = new Product($db);

			$sql = "SELECT s.nom as name, s.rowid as socid, s.email, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,";
			$sql.= " c.rowid, c.ref, c.statut as contract_status, c.ref_customer, c.ref_supplier,";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$sql.= " cd.rowid as cdid, cd.label, cd.description, cd.tms as datem, cd.statut, cd.product_type as type,";
			$sql.= " p.rowid as product_id, p.ref as product_ref, p.label as plabel, p.fk_product_type as ptype, p.entity";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contrat as c ON s.rowid = c.fk_soc";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			$sql.= ")";
			$sql.= " WHERE c.entity = ".$conf->entity;
			if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
<<<<<<< HEAD
			$sql.= $db->order("c.tms","DESC");
=======
			$sql.= $db->order("c.tms", "DESC");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
					$contratlignestatic->id=$objp->cdid;
					$contratlignestatic->fk_contrat=$objp->rowid;
					$contratlignestatic->label=$objp->label;
					$contratlignestatic->description=$objp->description;
					$contratlignestatic->type=$objp->type;
					$contratlignestatic->product_id=$objp->product_id;
					$contratlignestatic->product_ref=$objp->product_ref;
=======
					$contractlinestatic->id=$objp->cdid;
					$contractlinestatic->fk_contrat=$objp->rowid;
					$contractlinestatic->label=$objp->label;
					$contractlinestatic->description=$objp->description;
					$contractlinestatic->type=$objp->type;
					$contractlinestatic->product_id=$objp->product_id;
					$contractlinestatic->product_ref=$objp->product_ref;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

                    $contractstatic->statut=$objp->contract_status;
					$contractstatic->id=$objp->rowid;
					$contractstatic->ref=$objp->ref;
<<<<<<< HEAD

					$thirdpartytmp->name = $objp->name;
					$thirdpartytmp->id = $objp->socid;
=======
					$contractstatic->ref_customer=$objp->ref_customer;
					$contractstatic->ref_supplier=$objp->ref_supplier;

					$thirdpartytmp->name = $objp->name;
					$thirdpartytmp->id = $objp->socid;
					$thirdpartytmp->email = $objp->email;
					$thirdpartytmp->client = $objp->client;
					$thirdpartytmp->fournisseur = $objp->fournisseur;
					$thirdpartytmp->code_client = $objp->code_client;
					$thirdpartytmp->code_fournisseur = $objp->code_fournisseur;
					$thirdpartytmp->code_compta = $objp->code_compta;
					$thirdpartytmp->code_compta_fournisseur = $objp->code_compta_fournisseur;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

					// Multilangs
					if (! empty($conf->global->MAIN_MULTILANGS) && $objp->product_id > 0) // if option multilang is on
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
<<<<<<< HEAD
							if ($objtp->label != '') $contratlignestatic->label = $objtp->label;
=======
							if ($objtp->label != '') $contractlinestatic->label = $objtp->label;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						}
					}

					// Label
					if ($objp->product_id > 0)
					{
						$productstatic->id=$objp->product_id;
						$productstatic->type=$objp->ptype;
						$productstatic->ref=$objp->product_ref;
						$productstatic->entity=$objp->pentity;
						$productstatic->label=$objp->plabel;
<<<<<<< HEAD
						$text = $productstatic->getNomUrl(1,'',20);
=======
						$text = $productstatic->getNomUrl(1, '', 20);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						if ($objp->plabel)
						{
							$text .= ' - ';
							//$productstatic->ref=$objp->label;
							//$text .= $productstatic->getNomUrl(0,'',16);
							$text .= $objp->plabel;
						}
						$description = $objp->description;

						// Add description in form
						if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
						{
							//$text .= (! empty($objp->description) && $objp->description!=$objp->plabel)?'<br>'.dol_htmlentitiesbr($objp->description):'';
							$description = '';	// Already added into main visible desc
						}

<<<<<<< HEAD
						$s = $form->textwithtooltip($text,$description,3,'','',$cursorline,0,(!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));
=======
						$s = $form->textwithtooltip($text, $description, 3, '', '', $cursorline, 0, (!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					}
					else
					{
						$s = img_object($langs->trans("ShowProductOrService"), ($objp->product_type ? 'service' : 'product')).' '.dol_htmlentitiesbr($objp->description);
					}


<<<<<<< HEAD
					$this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                    'text' => $s,
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => '',
                    'text' => $contractstatic->getNomUrl(1),
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                    'text' => $thirdpartytmp->getNomUrl(1),
					'asis' => 1
                    );

					$this->info_box_contents[$i][] = array('td' => '',
                    'text' => dol_print_date($datem,'day'));

					$this->info_box_contents[$i][] = array('td' => 'align="right" width="18"',
                    'text' => $contratlignestatic->LibStatut($objp->statut,3)
=======
					$this->info_box_contents[$i][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $s,
                        'asis' => 1
                    );

					$this->info_box_contents[$i][] = array(
                        'td' => 'class="nowraponall"',
                        'text' => $contractstatic->getNomUrl(1),
                        'asis' => 1
                    );

					$this->info_box_contents[$i][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $thirdpartytmp->getNomUrl(1),
                        'asis' => 1
                    );

					$this->info_box_contents[$i][] = array(
                        'td' => '',
                        'text' => dol_print_date($datem, 'day'),
                    );

					$this->info_box_contents[$i][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $contractlinestatic->LibStatut($objp->statut, 3)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					);

					$i++;
				}
<<<<<<< HEAD
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoContractedProducts"));
=======
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'class="center"','text'=>$langs->trans("NoContractedProducts"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

				$db->free($result);
			}
			else
			{
<<<<<<< HEAD
				$this->info_box_contents[0][0] = array(	'td' => '',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
=======
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength' => 500,
                    'text' => ($db->error().' sql='.$sql),
                );
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}
		}
		else {
			$this->info_box_contents[0][0] = array(
<<<<<<< HEAD
			    'td' => 'align="left" class="nohover opacitymedium"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}

=======
			    'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
<<<<<<< HEAD
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

=======
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
