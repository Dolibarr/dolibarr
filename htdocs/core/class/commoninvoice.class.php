<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/core/class/commoninvoice.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of invoices classes (customer and supplier)
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

/**
 * 	Superclass for invoices classes
 */
abstract class CommonInvoice extends CommonObject
{
	/**
	 * 	Return amount of payments already done
	 *
	 *	@return		int		Amount of payment already done, <0 if KO
	 */
	function getSommePaiement()
	{
		$table='paiement_facture';
		$field='fk_facture';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
		{
			$table='paiementfourn_facturefourn';
			$field='fk_facturefourn';
		}

		$sql = 'SELECT sum(amount) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql.= ' WHERE '.$field.' = '.$this->id;

		dol_syslog(get_class($this)."::getSommePaiement sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return $obj->amount;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Renvoie tableau des ids de facture avoir issus de la facture
	 *
	 *	@return		array		Tableau d'id de factures avoirs
	 */
	function getListIdAvoirFromInvoice()
	{
		$idarray=array();

		$sql = 'SELECT rowid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE fk_facture_source = '.$this->id;
		$sql.= ' AND type = 2';
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$idarray[]=$row[0];
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $idarray;
	}

	/**
	 *	Renvoie l'id de la facture qui la remplace
	 *
	 *	@param		string	$option		filtre sur statut ('', 'validated', ...)
	 *	@return		int					<0 si KO, 0 si aucune facture ne remplace, id facture sinon
	 */
	function getIdReplacingInvoice($option='')
	{
		$sql = 'SELECT rowid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE fk_facture_source = '.$this->id;
		$sql.= ' AND type < 2';
		if ($option == 'validated') $sql.= ' AND fk_statut = 1';
		// PROTECTION BAD DATA
		// Au cas ou base corrompue et qu'il y a une facture de remplacement validee
		// et une autre non, on donne priorite a la validee.
		// Ne devrait pas arriver (sauf si acces concurrentiel et que 2 personnes
		// ont cree en meme temps une facture de remplacement pour la meme facture)
		$sql.= ' ORDER BY fk_statut DESC';

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				// Si il y en a
				return $obj->rowid;
			}
			else
			{
				// Si aucune facture ne remplace
				return 0;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Retourne le libelle du type de facture
	 *
	 *	@return     string        Libelle
	 */
	function getLibType()
	{
		global $langs;
		if ($this->type == 0) return $langs->trans("InvoiceStandard");
		if ($this->type == 1) return $langs->trans("InvoiceReplacement");
		if ($this->type == 2) return $langs->trans("InvoiceAvoir");
		if ($this->type == 3) return $langs->trans("InvoiceDeposit");
		if ($this->type == 4) return $langs->trans("InvoiceProForma");
		return $langs->trans("Unknown");
	}

	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode            0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
	 *  @param      double	$alreadypaid     0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
	 *  @return     string			         Label
	 */
	function getLibStatut($mode=0,$alreadypaid=-1)
	{
		return $this->LibStatut($this->paye,$this->statut,$mode,$alreadypaid,$this->type);
	}

	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *	@param    	int  	$paye          	Status field paye
	 *	@param      int		$status        	Id status
	 *	@param      int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
	 *	@param		double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
	 *	@param		int		$type			Type facture
	 *	@return     string        			Libelle du statut
	 */
	function LibStatut($paye,$status,$mode=0,$alreadypaid=-1,$type=0)
	{
		global $langs;
		$langs->load('bills');

		//print "$paye,$status,$mode,$alreadypaid,$type";
		if ($mode == 0)
		{
			$prefix='';
			if (! $paye)
			{
				if ($status == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return $langs->trans('Bill'.$prefix.'StatusClosedUnpaid');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPaid');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return $langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');
				elseif ($type == 3) return $langs->trans('Bill'.$prefix.'StatusConverted');
				else return $langs->trans('Bill'.$prefix.'StatusPaid');
			}
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($status == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return $langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPaid');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return $langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');
				elseif ($type == 3) return $langs->trans('Bill'.$prefix.'StatusConverted');
				else return $langs->trans('Bill'.$prefix.'StatusPaid');
			}
		}
		if ($mode == 2)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($status == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('Bill'.$prefix.'StatusDraft');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return img_picto($langs->trans('StatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPaid');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');
				elseif ($type == 3) return img_picto($langs->trans('BillStatusConverted'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusConverted');
				else return img_picto($langs->trans('BillStatusPaid'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPaid');
			}
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($status == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut7');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1');
				return img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6');
				elseif ($type == 3) return img_picto($langs->trans('BillStatusConverted'),'statut6');
				else return img_picto($langs->trans('BillStatusPaid'),'statut6');
			}
		}
		if ($mode == 4)
		{
			if (! $paye)
			{
				if ($status == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut7').' '.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1').' '.$langs->trans('BillStatusNotPaid');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('BillStatusStarted');
			}
			else
			{
				if ($type == 2) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6').' '.$langs->trans('BillStatusPaidBackOrConverted');
				elseif ($type == 3) return img_picto($langs->trans('BillStatusConverted'),'statut6').' '.$langs->trans('BillStatusConverted');
				else return img_picto($langs->trans('BillStatusPaid'),'statut6').' '.$langs->trans('BillStatusPaid');
			}
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($status == 0) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusDraft').' </span>'.img_picto($langs->trans('BillStatusDraft'),'statut0');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusCanceled').' </span>'.img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially').' </span>'.img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut7');
				if ($alreadypaid <= 0) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusNotPaid').' </span>'.img_picto($langs->trans('BillStatusNotPaid'),'statut1');
				return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusStarted').' </span>'.img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == 2) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted').' </span>'.img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6');
				elseif ($type == 3) return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusConverted').' </span>'.img_picto($langs->trans('BillStatusConverted'),'statut6');
				else return '<span class="hideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusPaid').' </span>'.img_picto($langs->trans('BillStatusPaid'),'statut6');
			}
		}
	}
}

/**
 *	Parent class of all other business classes for details of elements (invoices, contracts, proposals, orders, ...)
 */
abstract class CommonInvoiceLine extends CommonObject
{
}

?>
