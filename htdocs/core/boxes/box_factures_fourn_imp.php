<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
<<<<<<< HEAD
	var $boxcode = "oldestunpaidsupplierbills";
	var $boximg = "object_bill";
	var $boxlabel = "BoxOldestUnpaidSupplierBills";
	var $depends = array("facture","fournisseur");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();
=======
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

	    $this->hidden=! ($user->rights->fournisseur->facture->lire);
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
		global $conf, $user, $langs, $db;

		$this->max=$max;

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		$facturestatic=new FactureFournisseur($db);
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
		$thirdpartytmp=new Fournisseur($db);

<<<<<<< HEAD
		$this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpaidSupplierBills",$max));
=======
		$this->info_box_head = array('text' => $langs->trans("BoxTitleOldestUnpaidSupplierBills", $max));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND f.entity = ".$conf->entity;
			$sql.= " AND f.paye=0";
			$sql.= " AND fk_statut = 1";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= " ORDER BY datelimite DESC, f.ref_supplier DESC ";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.$langs->trans('DateDue').': %s)';

                $facturestatic = new FactureFournisseur($db);

				while ($line < $num)
				{
					$objp = $db->fetch_object($result);
					$datelimite=$db->jdate($objp->datelimite);
					$date=$db->jdate($objp->df);
					$datem=$db->jdate($objp->tms);
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
<<<<<<< HEAD
                        $late=img_warning(sprintf($l_due_date,dol_print_date($datelimite,'day')));
=======
                        $late=img_warning(sprintf($l_due_date, dol_print_date($datelimite, 'day')));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    }

                    $tooltip = $langs->trans('SupplierInvoice') . ': ' . ($objp->ref?$objp->ref:$objp->facid) . '<br>' . $langs->trans('RefSupplier') . ': ' . $objp->ref_supplier;

                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => '',
=======
                        'td' => 'class="nowraponall"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text' => $facturestatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis' => 1
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $thirdpartytmp->getNomUrl(1, '', 40),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => 'class="right"',
=======
                        'td' => 'class="nowrap right"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
<<<<<<< HEAD
                        'text' => dol_print_date($datelimite,'day'),
=======
                        'text' => dol_print_date($datelimite, 'day'),
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    );

					$fac = new FactureFournisseur($db);
					$fac->fetch($objp->facid);
					$alreadypaid=$fac->getSommePaiement();
                    $this->info_box_contents[$line][] = array(
<<<<<<< HEAD
                        'td' => 'align="right" width="18"',
                        'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3,$alreadypaid,$objp->type),
=======
                        'td' => 'class="right" width="18"',
                        'text' => $facturestatic->LibStatut($objp->paye, $objp->fk_statut, 3, $alreadypaid, $objp->type),
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
<<<<<<< HEAD
                        'td' => 'align="center"',
=======
                        'td' => 'class="center"',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        'text'=>$langs->trans("NoUnpaidSupplierBills"),
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
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
<<<<<<< HEAD
	 *	@param	array	$head       Array with properties of box title
=======
	 *	@param  array	$head       Array with properties of box title
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
