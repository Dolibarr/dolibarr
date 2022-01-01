<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
    public $boxcode = "lastproductsincontract";
    public $boximg = "object_product";
    public $boxlabel = "BoxLastProductsInContract";
    public $depends = array("service", "contrat");

	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;

    public $info_box_head = array();
    public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
	    global $user;

	    $this->db = $db;

	    $this->hidden = !($user->rights->service->lire && $user->rights->contrat->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

		$form = new Form($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxLastProductsInContract", $max));

		if ($user->rights->service->lire && $user->rights->contrat->lire)
		{
		    $contractstatic = new Contrat($this->db);
		    $contractlinestatic = new ContratLigne($this->db);
		    $thirdpartytmp = new Societe($this->db);
		    $productstatic = new Product($this->db);

			$sql = "SELECT s.nom as name, s.rowid as socid, s.email, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,";
			$sql .= " c.rowid, c.ref, c.statut as contract_status, c.ref_customer, c.ref_supplier,";
			$sql .= " cd.rowid as cdid, cd.label, cd.description, cd.tms as datem, cd.statut, cd.product_type as type, cd.date_fin_validite as date_line,";
			$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as product_type, p.entity, p.tobuy, p.tosell";
			$sql .= " FROM (".MAIN_DB_PREFIX."societe as s";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."contrat as c ON s.rowid = c.fk_soc";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			$sql .= ")";
			$sql .= " WHERE c.entity = ".$conf->entity;
			if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
			$sql .= $this->db->order("c.tms", "DESC");
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$now = dol_now();

				$i = 0;

				while ($i < $num)
				{
					$late = '';

					$objp = $this->db->fetch_object($result);
					$datem = $this->db->jdate($objp->datem);

					$contractlinestatic->id = $objp->cdid;
					$contractlinestatic->fk_contrat = $objp->rowid;
					$contractlinestatic->label = $objp->label;
					$contractlinestatic->description = $objp->description;
					$contractlinestatic->type = $objp->type;
					$contractlinestatic->product_id = $objp->product_id;
					$contractlinestatic->product_ref = $objp->product_ref;
					$contractlinestatic->product_type = $objp->product_type;

					$contractstatic->id = $objp->rowid;
					$contractstatic->ref = $objp->ref;
					$contractstatic->ref_customer = $objp->ref_customer;
					$contractstatic->ref_supplier = $objp->ref_supplier;
					$contractstatic->statut = $objp->contract_status;

					$thirdpartytmp->name = $objp->name;
					$thirdpartytmp->id = $objp->socid;
					$thirdpartytmp->email = $objp->email;
					$thirdpartytmp->client = $objp->client;
					$thirdpartytmp->fournisseur = $objp->fournisseur;
					$thirdpartytmp->code_client = $objp->code_client;
					$thirdpartytmp->code_fournisseur = $objp->code_fournisseur;
					$thirdpartytmp->code_compta = $objp->code_compta;
					$thirdpartytmp->code_compta_fournisseur = $objp->code_compta_fournisseur;

					$dateline = $this->db->jdate($objp->date_line);
					if ($contractstatic->statut == Contrat::STATUS_VALIDATED && $objp->statut == ContratLigne::STATUS_OPEN && !empty($dateline) && ($dateline + $conf->contrat->services->expires->warning_delay) < $now) $late = img_warning($langs->trans("Late"));

					// Multilangs
					if (!empty($conf->global->MAIN_MULTILANGS) && $objp->product_id > 0) // if option multilang is on
					{
						$sqld = "SELECT label";
						$sqld .= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sqld .= " WHERE fk_product=".$objp->product_id;
						$sqld .= " AND lang='".$langs->getDefaultLang()."'";
						$sqld .= " LIMIT 1";

						$resultd = $this->db->query($sqld);
						if ($resultd)
						{
							$objtp = $this->db->fetch_object($resultd);
							if ($objtp->label != '') $contractlinestatic->label = $objtp->label;
						}
					}

					// Label
					if ($objp->product_id > 0)
					{
						$productstatic->id = $objp->product_id;
						$productstatic->type = $objp->product_type;
						$productstatic->ref = $objp->product_ref;
						$productstatic->entity = $objp->pentity;
						$productstatic->label = $objp->product_label;
						$productstatic->status = $objp->tosell;
						$productstatic->status_buy = $objp->tobuy;

						$text = $productstatic->getNomUrl(1, '', 20);
						if ($objp->product_label)
						{
							$text .= ' - ';
							//$productstatic->ref=$objp->label;
							//$text .= $productstatic->getNomUrl(0,'',16);
							$text .= $objp->product_label;
						}
						$description = $objp->description;

						// Add description in form
						if (!empty($conf->global->PRODUIT_DESC_IN_FORM))
						{
							//$text .= (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
							$description = ''; // Already added into main visible desc
						}

						$s = $form->textwithtooltip($text, $description, 3, '', '', $cursorline, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
					}
					else
					{
						$s = img_object($langs->trans("ShowProductOrService"), ($objp->product_type ? 'service' : 'product')).' '.dol_htmlentitiesbr($objp->description);
					}


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
						'text2'=> $late,
                    );

					$this->info_box_contents[$i][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $contractlinestatic->LibStatut($objp->statut, 3)
					);

					$i++;
				}
				if ($num == 0) $this->info_box_contents[$i][0] = array(
					'td' => 'class="center opacitymedium"',
					'text'=>$langs->trans("NoContractedProducts")
				);

				$this->db->free($result);
			}
			else
			{
				$this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength' => 500,
                    'text' => ($this->db->error().' sql='.$sql),
                );
			}
		}
		else {
			$this->info_box_contents[0][0] = array(
			    'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
