<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/core/boxes/box_factures_fourn_imp.php
 *      \ingroup    fournisseur
 *      \brief      Fichier de gestion d'une box des factures fournisseurs impayees
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show not payed suppliers invoices
 */
class box_factures_fourn_imp extends ModeleBoxes
{
    public $boxcode = "oldestunpaidsupplierbills";
    public $boximg = "object_bill";
    public $boxlabel = "BoxOldestUnpaidSupplierBills";
    public $depends = array("facture","fournisseur");

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

	    $this->hidden = ! ($user->rights->fournisseur->facture->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		$facturestatic=new FactureFournisseur($this->db);
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
		$thirdpartytmp=new Fournisseur($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpaidSupplierBills", $max));

		if ($user->rights->fournisseur->facture->lire)
		{
			$sql = "SELECT s.nom as name, s.rowid as socid,";
			$sql.= " f.rowid as facid, f.ref, f.ref_supplier, f.date_lim_reglement as datelimite,";
			$sql.= " f.amount, f.datef as df,";
            $sql.= " f.total_ht as total_ht,";
            $sql.= " f.tva as total_tva,";
            $sql.= " f.total_ttc,";
			$sql.= " f.paye, f.fk_statut, f.type";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql.= ",".MAIN_DB_PREFIX."facture_fourn as f";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND f.entity = ".$conf->entity;
			$sql.= " AND f.paye=0";
			$sql.= " AND fk_statut = 1";
			if (!$user->rights->societe->client->voir && !$user->socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->socid) $sql.= " AND s.rowid = ".$user->socid;
			$sql.= " ORDER BY datelimite DESC, f.ref_supplier DESC ";
			$sql.= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.$langs->trans('DateDue').': %s)';

                $facturestatic = new FactureFournisseur($this->db);

				while ($line < $num)
				{
					$objp = $this->db->fetch_object($result);
					$datelimite=$this->db->jdate($objp->datelimite);
					$date=$this->db->jdate($objp->df);
					$datem=$this->db->jdate($objp->tms);
					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->ref;
					$facturestatic->total_ht = $objp->total_ht;
					$facturestatic->total_tva = $objp->total_tva;
					$facturestatic->total_ttc = $objp->total_ttc;
					$facturestatic->date_echeance = $datelimite;
					$facturestatic->statut = $objp->fk_statut;
					$thirdpartytmp->id = $objp->socid;
					$thirdpartytmp->name = $objp->name;
					$thirdpartytmp->fournisseur = 1;
                    $thirdpartytmp->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartytmp->logo = $objp->logo;

					$late='';
					if ($facturestatic->hasDelay()) {
                        $late=img_warning(sprintf($l_due_date, dol_print_date($datelimite, 'day')));
                    }

                    $tooltip = $langs->trans('SupplierInvoice') . ': ' . ($objp->ref?$objp->ref:$objp->facid) . '<br>' . $langs->trans('RefSupplier') . ': ' . $objp->ref_supplier;

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="nowraponall"',
                        'text' => $facturestatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis' => 1
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $thirdpartytmp->getNomUrl(1, '', 40),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="nowraponall right"',
                        'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($datelimite, 'day'),
                    );

					$fac = new FactureFournisseur($this->db);
					$fac->fetch($objp->facid);
					$alreadypaid=$fac->getSommePaiement();
                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $facturestatic->LibStatut($objp->paye, $objp->fk_statut, 3, $alreadypaid, $objp->type),
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'class="center"',
                        'text'=>$langs->trans("NoUnpaidSupplierBills"),
                    );

                $this->db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($this->db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }
    }

	/**
	 *	Method to show box
	 *
	 *	@param  array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
