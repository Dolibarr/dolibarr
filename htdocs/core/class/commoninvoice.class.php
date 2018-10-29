<?php
/* Copyright (C) 2012       Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
     * Standard invoice
     */
    const TYPE_STANDARD = 0;

    /**
     * Replacement invoice
     */
    const TYPE_REPLACEMENT = 1;

    /**
     * Credit note invoice
     */
    const TYPE_CREDIT_NOTE = 2;

    /**
     * Deposit invoice
     */
    const TYPE_DEPOSIT = 3;

    /**
     * Proforma invoice.
     * @deprectad Remove this. A "proforma invoice" is an order with a look of invoice, not an invoice !
     */
    const TYPE_PROFORMA = 4;

    /**
     * Situation invoice
     */
    const TYPE_SITUATION = 5;

	/**
	 * Draft
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated (need to be paid)
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Classified paid.
	 * If paid partially, $this->close_code can be:
	 * - CLOSECODE_DISCOUNTVAT
	 * - CLOSECODE_BADDEBT
	 * If paid completelly, this->close_code will be null
	 */
	const STATUS_CLOSED = 2;

	/**
	 * Classified abandoned and no payment done.
	 * $this->close_code can be:
	 * - CLOSECODE_BADDEBT
	 * - CLOSECODE_ABANDONED
	 * - CLOSECODE_REPLACED
	 */
	const STATUS_ABANDONED = 3;


	/**
	 * 	Return remain amount to pay. Property ->id and ->total_ttc must be set.
	 *  This does not include open direct debit requests.
	 *
	 *  @param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *	@return		double						Remain of amount to pay
	 */
	function getRemainToPay($multicurrency=0)
	{
	    $alreadypaid=0;
	    $alreadypaid+=$this->getSommePaiement($multicurrency);
	    $alreadypaid+=$this->getSumDepositsUsed($multicurrency);
	    $alreadypaid+=$this->getSumCreditNotesUsed($multicurrency);
    	return $this->total_ttc - $alreadypaid;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *  @param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *	@return		int						Amount of payment already done, <0 if KO
	 */
	function getSommePaiement($multicurrency=0)
	{
		$table='paiement_facture';
		$field='fk_facture';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
		{
			$table='paiementfourn_facturefourn';
			$field='fk_facturefourn';
		}

		$sql = 'SELECT sum(amount) as amount, sum(multicurrency_amount) as multicurrency_amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql.= ' WHERE '.$field.' = '.$this->id;

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			if ($multicurrency) return $obj->multicurrency_amount;
			else return $obj->amount;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    	Return amount (with tax) of all deposits invoices used by invoice.
     *      Should always be empty, except if option FACTURE_DEPOSITS_ARE_JUST_PAYMENTS is on (not recommended).
	 *
	 * 		@param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *		@return		int						<0 if KO, Sum of deposits amount otherwise
	 */
	function getSumDepositsUsed($multicurrency=0)
	{
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
	    {
	        // TODO
	       return 0;
	    }

	    require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

	    $discountstatic=new DiscountAbsolute($this->db);
	    $result=$discountstatic->getSumDepositsUsed($this, $multicurrency);
	    if ($result >= 0)
	    {
	        return $result;
	    }
	    else
	    {
	        $this->error=$discountstatic->error;
	        return -1;
	    }
	}

	/**
	 *    	Return amount (with tax) of all credit notes invoices + excess received used by invoice
	 *
	 * 		@param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *		@return		int						<0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	function getSumCreditNotesUsed($multicurrency=0)
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

	    $discountstatic=new DiscountAbsolute($this->db);
	    $result=$discountstatic->getSumCreditNotesUsed($this, $multicurrency);
	    if ($result >= 0)
	    {
	        return $result;
	    }
	    else
	    {
	        $this->error=$discountstatic->error;
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
	 *  Return list of payments
	 *
	 *	@param		string	$filtertype		1 to filter on type of payment == 'PRE'
	 *  @return     array					Array with list of payments
	 */
	function getListOfPayments($filtertype='')
	{
		$retarray=array();

		$table='paiement_facture';
		$table2='paiement';
		$field='fk_facture';
		$field2='fk_paiement';
		$sharedentity='facture';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
		{
			$table='paiementfourn_facturefourn';
			$table2='paiementfourn';
			$field='fk_facturefourn';
			$field2='fk_paiementfourn';
			$sharedentity='facture_fourn';
		}

		$sql = 'SELECT p.ref, pf.amount, pf.multicurrency_amount, p.fk_paiement, p.datep, p.num_paiement as num, t.code';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as pf, '.MAIN_DB_PREFIX.$table2.' as p, '.MAIN_DB_PREFIX.'c_paiement as t';
		$sql.= ' WHERE pf.'.$field.' = '.$this->id;
		//$sql.= ' WHERE pf.'.$field.' = 1';
		$sql.= ' AND pf.'.$field2.' = p.rowid';
		$sql.= ' AND p.fk_paiement = t.id';
		$sql.= ' AND p.entity IN (' . getEntity($sharedentity).')';
		if ($filtertype) $sql.=" AND t.code='PRE'";

		dol_syslog(get_class($this)."::getListOfPayments", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$retarray[]=array('amount'=>$obj->amount,'type'=>$obj->code, 'date'=>$obj->datep, 'num'=>$obj->num, 'ref'=>$obj->ref);
				$i++;
			}
			$this->db->free($resql);
			return $retarray;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_print_error($this->db);
			return array();
		}
	}


	/**
	 *  Return if an invoice can be deleted
	 *	Rule is:
	 *  If invoice is draft and has a temporary ref -> yes (1)
	 *  If hidden option INVOICE_CAN_NEVER_BE_REMOVED is on -> no (0)
	 *  If invoice is dispatched in bookkeeping -> no (-1)
	 *  If invoice has a definitive ref, is not last and INVOICE_CAN_ALWAYS_BE_REMOVED off -> no (-2)
	 *  If invoice not last in a cycle -> no (-3)
	 *  If there is payment -> no (-4)
	 *  Otherwise -> yes (2)
	 *
	 *  @return    int         <=0 if no, >0 if yes
	 */
	function is_erasable()
	{
		global $conf;

		// We check if invoice is a temporary number (PROVxxxx)
		$tmppart = substr($this->ref, 1, 4);

		if ($this->statut == self::STATUS_DRAFT && $tmppart === 'PROV') // If draft invoice and ref not yet defined
		{
			return 1;
		}

		if (! empty($conf->global->INVOICE_CAN_NEVER_BE_REMOVED)) return 0;

		// If not a draft invoice and not temporary invoice
		if ($tmppart !== 'PROV')
		{
			$ventilExportCompta = $this->getVentilExportCompta();
			if ($ventilExportCompta != 0) return -1;

			// Get last number of validated invoice
			if ($this->element != 'invoice_supplier')
			{
				if (empty($this->thirdparty)) $this->fetch_thirdparty();	// We need to have this->thirdparty defined, in case of numbering rule use tags that depend on thirdparty (like {t} tag).
				$maxfacnumber = $this->getNextNumRef($this->thirdparty,'last');

				// If there is no invoice into the reset range and not already dispatched, we can delete
				// If invoice to delete is last one and not already dispatched, we can delete
				if (empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED) && $maxfacnumber != '' && $maxfacnumber != $this->ref) return -2;

				// TODO If there is payment in bookkeeping, check payment is not dispatched in accounting
				// ...

				if ($this->situation_cycle_ref && method_exists($this, 'is_last_in_cycle'))
				{
					$last = $this->is_last_in_cycle();
					if (! $last) return -3;
				}
			}
		}

		// Test if there is at least one payment. If yes, refuse to delete.
		if (empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED) && $this->getSommePaiement() > 0) return -4;

		return 2;
	}

	/**
	 *	Return if an invoice was dispatched into bookkeeping
	 *
	 *	@return     int         <0 if KO, 0=no, 1=yes
	 */
	public function getVentilExportCompta()
	{
		$alreadydispatched = 0;

		$type = 'customer_invoice';
		if ($this->element == 'invoice_supplier') $type = 'supplier_invoice';

		$sql = " SELECT COUNT(ab.rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab WHERE ab.doc_type='".$type."' AND ab.fk_doc = ".$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$alreadydispatched = $obj->nb;
			}
		}
		else
		{
			$this->error = $this->db->lasterror();
			return -1;
		}

		if ($alreadydispatched)
		{
			return 1;
		}
		return 0;
	}


	/**
	 *	Return label of type of invoice
	 *
	 *	@return     string        Label of type of invoice
	 */
	function getLibType()
	{
		global $langs;
        if ($this->type == CommonInvoice::TYPE_STANDARD) return $langs->trans("InvoiceStandard");
        if ($this->type == CommonInvoice::TYPE_REPLACEMENT) return $langs->trans("InvoiceReplacement");
        if ($this->type == CommonInvoice::TYPE_CREDIT_NOTE) return $langs->trans("InvoiceAvoir");
        if ($this->type == CommonInvoice::TYPE_DEPOSIT) return $langs->trans("InvoiceDeposit");
        if ($this->type == CommonInvoice::TYPE_PROFORMA) return $langs->trans("InvoiceProForma");           // Not used.
        if ($this->type == CommonInvoice::TYPE_SITUATION) return $langs->trans("InvoiceSituation");
		return $langs->trans("Unknown");
	}

	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param      integer	$alreadypaid    0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
	 *  @return     string			        Label of status
	 */
	function getLibStatut($mode=0, $alreadypaid=-1)
	{
		return $this->LibStatut($this->paye, $this->statut, $mode, $alreadypaid, $this->type);
	}

	/**
	 *	Return label of a status
	 *
	 *	@param    	int  	$paye          	Status field paye
	 *	@param      int		$status        	Id status
	 *	@param      int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=long label + picto
	 *	@param		integer	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, -1 otherwise)
	 *	@param		int		$type			Type invoice
	 *	@return     string        			Label of status
	 */
	function LibStatut($paye, $status, $mode=0, $alreadypaid=-1, $type=0)
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
				if ($type == self::TYPE_CREDIT_NOTE) return $langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');       // credit note
				elseif ($type == self::TYPE_DEPOSIT) return $langs->trans('Bill'.$prefix.'StatusConverted');             // deposit invoice
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
				if ($type == self::TYPE_CREDIT_NOTE) return $langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');
				elseif ($type == self::TYPE_DEPOSIT) return $langs->trans('Bill'.$prefix.'StatusConverted');
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
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut9').' '.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPaid');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				if ($type == self::TYPE_CREDIT_NOTE) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted');
				elseif ($type == self::TYPE_DEPOSIT) return img_picto($langs->trans('BillStatusConverted'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusConverted');
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
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut9');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1');
				return img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == self::TYPE_CREDIT_NOTE) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6');
				elseif ($type == self::TYPE_DEPOSIT) return img_picto($langs->trans('BillStatusConverted'),'statut6');
				else return img_picto($langs->trans('BillStatusPaid'),'statut6');
			}
		}
		if ($mode == 4)
		{
			$prefix='';
			if (! $paye)
			{
				if ($status == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut9').' '.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially');
				if ($alreadypaid <= 0) return img_picto($langs->trans('BillStatusNotPaid'),'statut1').' '.$langs->trans('BillStatusNotPaid');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('BillStatusStarted');
			}
			else
			{
				if ($type == self::TYPE_CREDIT_NOTE) return img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6').' '.$langs->trans('BillStatusPaidBackOrConverted');
				elseif ($type == self::TYPE_DEPOSIT) return img_picto($langs->trans('BillStatusConverted'),'statut6').' '.$langs->trans('BillStatusConverted');
				else return img_picto($langs->trans('BillStatusPaid'),'statut6').' '.$langs->trans('BillStatusPaid');
			}
		}
		if ($mode == 5 || $mode == 6)
		{
			$prefix='';
			if ($mode == 5) $prefix='Short';
			if (! $paye)
			{
				if ($status == 0) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusDraft').' </span>'.img_picto($langs->trans('BillStatusDraft'),'statut0');
				if (($status == 3 || $status == 2) && $alreadypaid <= 0) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusCanceled').' </span>'.img_picto($langs->trans('BillStatusCanceled'),'statut5');
				if (($status == 3 || $status == 2) && $alreadypaid > 0) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusClosedPaidPartially').' </span>'.img_picto($langs->trans('BillStatusClosedPaidPartially'),'statut9');
				if ($alreadypaid <= 0)
				{
				    if ($type == self::TYPE_CREDIT_NOTE) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusNotRefunded').' </span>'.img_picto($langs->trans('StatusNotRefunded'),'statut1');
				    return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusNotPaid').' </span>'.img_picto($langs->trans('BillStatusNotPaid'),'statut1');
				}
				return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusStarted').' </span>'.img_picto($langs->trans('BillStatusStarted'),'statut3');
			}
			else
			{
				if ($type == self::TYPE_CREDIT_NOTE) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusPaidBackOrConverted').' </span>'.img_picto($langs->trans('BillStatusPaidBackOrConverted'),'statut6');
				elseif ($type == self::TYPE_DEPOSIT) return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusConverted').' </span>'.img_picto($langs->trans('BillStatusConverted'),'statut6');
				else return '<span class="xhideonsmartphone">'.$langs->trans('Bill'.$prefix.'StatusPaid').' </span>'.img_picto($langs->trans('BillStatusPaid'),'statut6');
			}
		}
	}

	/**
	 *	Renvoi une date limite de reglement de facture en fonction des
	 *	conditions de reglements de la facture et date de facturation
	 *
	 *	@param      integer	$cond_reglement   	Condition of payment (code or id) to use. If 0, we use current condition.
	 *	@return     date     			       	Date limite de reglement si ok, <0 si ko
	 */
	function calculate_date_lim_reglement($cond_reglement=0)
	{
		if (! $cond_reglement) $cond_reglement=$this->cond_reglement_code;
		if (! $cond_reglement) $cond_reglement=$this->cond_reglement_id;

		$cdr_nbjour=0; $cdr_type=0; $cdr_decalage=0;

		$sqltemp = 'SELECT c.type_cdr,c.nbjour,c.decalage';
		$sqltemp.= ' FROM '.MAIN_DB_PREFIX.'c_payment_term as c';
		if (is_numeric($cond_reglement)) $sqltemp.= " WHERE c.rowid=".$cond_reglement;
		else {
			$sqltemp.= " WHERE c.entity IN (".getEntity('c_payment_term').")";
			$sqltemp.= " AND c.code='".$this->db->escape($cond_reglement)."'";
		}

		dol_syslog(get_class($this).'::calculate_date_lim_reglement', LOG_DEBUG);
		$resqltemp=$this->db->query($sqltemp);
		if ($resqltemp)
		{
			if ($this->db->num_rows($resqltemp))
			{
				$obj = $this->db->fetch_object($resqltemp);
				$cdr_nbjour = $obj->nbjour;
				$cdr_type = $obj->type_cdr;
				$cdr_decalage = $obj->decalage;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
		$this->db->free($resqltemp);

		/* Definition de la date limite */

		// 1 : ajout du nombre de jours
		$datelim = $this->date + ($cdr_nbjour * 3600 * 24);

		// 2 : application de la regle "fin de mois"
		if ($cdr_type == 1)
		{
			$mois=date('m', $datelim);
			$annee=date('Y', $datelim);
			if ($mois == 12)
			{
				$mois = 1;
				$annee += 1;
			}
			else
			{
				$mois += 1;
			}
			// On se deplace au debut du mois suivant, et on retire un jour
			$datelim=dol_mktime(12,0,0,$mois,1,$annee);
			$datelim -= (3600 * 24);
		}
		elseif($cdr_type == 2 && !empty($cdr_nbjour)) // Application de la règle, le N du mois courant ou suivant
		{

			$date_piece = dol_mktime(0,0,0,date('m', $this->date),date('d', $this->date),date('Y', $this->date)); // Sans les heures minutes et secondes
			$date_lim_current = dol_mktime(0,0,0,date('m', $this->date),$cdr_nbjour,date('Y', $this->date)); // Sans les heures minutes et secondes
			$date_lim_next = strtotime(date('Y-m-d', $date_lim_current).' +1month');

			$diff = $date_piece - $date_lim_current;

			if($diff < 0) $datelim = $date_lim_current;
			else $datelim = $date_lim_next;

		}

		// 3 : application du decalage
		$datelim += ($cdr_decalage * 3600 * 24);

		return $datelim;
	}
}



require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';

/**
 *	Parent class of all other business classes for details of elements (invoices, contracts, proposals, orders, ...)
 */
abstract class CommonInvoiceLine extends CommonObjectLine
{
	/**
	 * Quantity
	 * @var double
	 */
	public $qty;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * Type of the product. 0 for product 1 for service
	 * @var int
	 */
	public $product_type = 0;

	/**
	 * Id of corresponding product
	 * @var int
	 */
	public $fk_product;

	/**
	 * VAT code
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * VAT %
	 * @var float
	 */
	public $tva_tx;

	/**
	 * Local tax 1 %
	 * @var float
	 */
	public $localtax1_tx;

	/**
	 * Local tax 2 %
	 * @var float
	 */
	public $localtax2_tx;

	/**
	 * Percent of discount
	 * @var float
	 */
	public $remise_percent;

	/**
	 * Total amount before taxes
	 * @var float
	 */
	public $total_ht;

	/**
	 * Total VAT amount
	 * @var float
	 */
	public $total_tva;

	/**
	 * Total local tax 1 amount
	 * @var float
	 */
	public $total_localtax1;

	/**
	 * Total local tax 2 amount
	 * @var float
	 */
	public $total_localtax2;

	/**
	 * Total amount with taxes
	 * @var float
	 */
	public $total_ttc;

	/**
	 * Liste d'options cumulables:
	 * Bit 0:	0 si TVA normal - 1 si TVA NPR
	 * Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
	 * @var int
	 */
	public $info_bits = 0;

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}

