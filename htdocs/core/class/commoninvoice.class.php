<?php
/* Copyright (C) 2012       Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023		Nick Fragoulis
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/core/class/commoninvoice.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of invoices classes (customer and supplier)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';

/**
 * 	Superclass for invoices classes
 */
abstract class CommonInvoice extends CommonObject
{
	use CommonIncoterm;

	/**
	 * @var string		Label used as ref for template invoices
	 */
	public $title;

	/**
	 * @var int		Type of invoice (See TYPE_XXX constants)
	 */
	public $type = self::TYPE_STANDARD;

	/**
	 * @var int		Sub type of invoice (A subtype code coming from llx_invoice_subtype table. May be used by some countries like Greece)
	 */
	public $subtype;

	/**
	 * @var int Thirdparty ID
	 * @deprecated
	 * @see $socid
	 */
	public $fk_soc;
	/**
	 * @var int Thirdparty ID
	 */
	public $socid;

	public $paye;

	/**
	 * Invoice date (date)
	 *
	 * @var integer
	 */
	public $date;

	/**
	 * @var int Deadline for payment
	 */
	public $date_lim_reglement;

	public $cond_reglement_id; // Id in llx_c_paiement
	public $cond_reglement_code; // Code in llx_c_paiement
	public $cond_reglement_label;
	public $cond_reglement_doc; // Code in llx_c_paiement

	public $mode_reglement_id;
	public $mode_reglement_code; // Code in llx_c_paiement

	/**
	 * @var string
	 */
	public $mode_reglement;

	/**
	 * @var double
	 */
	public $revenuestamp;

	public $totalpaid;			// duplicate with sumpayed
	public $totaldeposits;		// duplicate with sumdeposit
	public $totalcreditnotes;	// duplicate with sumcreditnote

	public $sumpayed;
	public $sumpayed_multicurrency;
	public $sumdeposit;
	public $sumdeposit_multicurrency;
	public $sumcreditnote;
	public $sumcreditnote_multicurrency;
	public $remaintopay;

	/**
	 * @var int
	 */
	public $stripechargedone;

	/**
	 * @var int
	 */
	public $stripechargeerror;

	/**
	 * Payment description
	 * @var string
	 */
	public $description;

	/**
	 * @var string
	 * @deprecated
	 * @see $ref_customer
	 */
	public $ref_client;

	/**
	 * @var int Situation cycle reference number
	 */
	public $situation_cycle_ref;

	/**
	 * ! Closing after partial payment: discount_vat, badsupplier, abandon
	 * ! Closing when no payment: replaced, abandoned
	 * @var string Close code
	 */
	public $close_code;

	/**
	 * ! Comment if paid without full payment
	 * @var string Close note
	 */
	public $close_note;


	/**
	 * ! Populated by payment modules like Stripe
	 * @var string[] 	Messages returned by an online payment module
	 */
	public $postactionmessages;


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
	 * @deprecated Remove this. A "proforma invoice" is an order with a look of invoice, not an invoice !
	 */
	const TYPE_PROFORMA = 4;

	/**
	 * Situation invoice
	 */
	const TYPE_SITUATION = 5;

	/**
	 * Draft status
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
	 * If paid completely, this->close_code will be null
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
	 *	@return		float					Remain of amount to pay
	 */
	public function getRemainToPay($multicurrency = 0)
	{
		$alreadypaid = 0.0;
		$alreadypaid += $this->getSommePaiement($multicurrency);
		$alreadypaid += $this->getSumDepositsUsed($multicurrency);
		$alreadypaid += $this->getSumCreditNotesUsed($multicurrency);

		$remaintopay = price2num($this->total_ttc - $alreadypaid, 'MT');
		if ($this->statut == self::STATUS_CLOSED && $this->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
			$remaintopay = 0.0;
		}
		return $remaintopay;
	}

	/**
	 * 	Return amount of payments already done. This must include ONLY the record into the payment table.
	 *  Payments dones using discounts, credit notes, etc are not included.
	 *
	 *  @param 		int 			$multicurrency 		Return multicurrency_amount instead of amount. -1=Return both.
	 *	@return		float|int|array						Amount of payment already done, <0 and set ->error if KO
	 *  @see getSumDepositsUsed(), getSumCreditNotesUsed()
	 */
	public function getSommePaiement($multicurrency = 0)
	{
		$table = 'paiement_facture';
		$field = 'fk_facture';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') {
			$table = 'paiementfourn_facturefourn';
			$field = 'fk_facturefourn';
		}

		$sql = "SELECT sum(amount) as amount, sum(multicurrency_amount) as multicurrency_amount";
		$sql .= " FROM ".$this->db->prefix().$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			if ($obj) {
				if ($multicurrency < 0) {
					$this->sumpayed = $obj->amount;
					$this->sumpayed_multicurrency = $obj->multicurrency_amount;
					return array('alreadypaid' => (float) $obj->amount, 'alreadypaid_multicurrency' => (float) $obj->multicurrency_amount);
				} elseif ($multicurrency) {
					$this->sumpayed_multicurrency = $obj->multicurrency_amount;
					return (float) $obj->multicurrency_amount;
				} else {
					$this->sumpayed = $obj->amount;
					return (float) $obj->amount;
				}
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Return amount (with tax) of all deposits invoices used by invoice.
	 *  Should always be empty, except if option FACTURE_DEPOSITS_ARE_JUST_PAYMENTS is on for sale invoices (not recommended),
	 *  of FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS is on for purchase invoices (not recommended).
	 *
	 * 	@param 		int 	$multicurrency 		Return multicurrency_amount instead of amount
	 *	@return		float						Return integer <0 and set ->error if KO, Sum of deposits amount otherwise
	 *	@see getSommePaiement(), getSumCreditNotesUsed()
	 */
	public function getSumDepositsUsed($multicurrency = 0)
	{
		/*if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') {
			// FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS was never supported for purchase invoice, so we can return 0 with no need of SQL for this case.
			return 0.0;
		}*/

		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic = new DiscountAbsolute($this->db);
		$result = $discountstatic->getSumDepositsUsed($this, $multicurrency);

		if ($result >= 0) {
			if ($multicurrency) {
				$this->sumdeposit_multicurrency = $result;
			} else {
				$this->sumdeposit = $result;
			}

			return $result;
		} else {
			$this->error = $discountstatic->error;
			return -1;
		}
	}

	/**
	 *  Return amount (with tax) of all credit notes invoices + excess received used by invoice
	 *
	 * 	@param 		int 	$multicurrency 		Return multicurrency_amount instead of amount
	 *	@return		float						Return integer <0 and set ->error if KO, Sum of credit notes and deposits amount otherwise
	 *	@see getSommePaiement(), getSumDepositsUsed()
	 */
	public function getSumCreditNotesUsed($multicurrency = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic = new DiscountAbsolute($this->db);
		$result = $discountstatic->getSumCreditNotesUsed($this, $multicurrency);
		if ($result >= 0) {
			if ($multicurrency) {
				$this->sumcreditnote_multicurrency = $result;
			} else {
				$this->sumcreditnote = $result;
			}

			return $result;
		} else {
			$this->error = $discountstatic->error;
			return -1;
		}
	}

	/**
	 *    	Return amount (with tax) of all converted amount for this credit note
	 *
	 * 		@param 		int 	$multicurrency 	Return multicurrency_amount instead of amount
	 *		@return		float						Return integer <0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	public function getSumFromThisCreditNotesNotUsed($multicurrency = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic = new DiscountAbsolute($this->db);
		$result = $discountstatic->getSumFromThisCreditNotesNotUsed($this, $multicurrency);
		if ($result >= 0) {
			return $result;
		} else {
			$this->error = $discountstatic->error;
			return -1;
		}
	}

	/**
	 *	Returns array of credit note ids from the invoice
	 *
	 *	@return		array		Array of credit note ids
	 */
	public function getListIdAvoirFromInvoice()
	{
		$idarray = array();

		$sql = "SELECT rowid";
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE fk_facture_source = ".((int) $this->id);
		$sql .= " AND type = 2";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$idarray[] = $row[0];
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}
		return $idarray;
	}

	/**
	 *	Returns the id of the invoice that replaces it
	 *
	 *	@param		string	$option		status filter ('', 'validated', ...)
	 *	@return		int					Return integer <0 si KO, 0 if no invoice replaces it, id of invoice otherwise
	 */
	public function getIdReplacingInvoice($option = '')
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE fk_facture_source = ".((int) $this->id);
		$sql .= " AND type < 2";
		if ($option == 'validated') {
			$sql .= ' AND fk_statut = 1';
		}
		// PROTECTION BAD DATA
		// In case the database is corrupted and there is a valid replectement invoice
		// and another no, priority is given to the valid one.
		// Should not happen (unless concurrent access and 2 people have created a
		// replacement invoice for the same invoice at the same time)
		$sql .= " ORDER BY fk_statut DESC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				// If there is any
				return $obj->rowid;
			} else {
				// If no invoice replaces it
				return 0;
			}
		} else {
			return -1;
		}
	}

	/**
	 *  Return list of payments
	 *
	 *  @see $error Empty string '' if no error.
	 *
	 *	@param		string	$filtertype		1 to filter on type of payment == 'PRE'
	 *  @param      int     $multicurrency  Return multicurrency_amount instead of amount
	 *  @return     array<array{amount:int|float,date:int,num:string,ref:string,ref_ext?:string,fk_bank_line?:int,type:string}>		 Array with list of payments
	 */
	public function getListOfPayments($filtertype = '', $multicurrency = 0)
	{
		$retarray = array();
		// By default no error, list can be empty.
		$this->error = '';

		$table = 'paiement_facture';
		$table2 = 'paiement';
		$field = 'fk_facture';
		$field2 = 'fk_paiement';
		$field3 = ', p.ref_ext';
		$field4 = ', p.fk_bank'; // Bank line id
		$sharedentity = 'facture';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') {
			$table = 'paiementfourn_facturefourn';
			$table2 = 'paiementfourn';
			$field = 'fk_facturefourn';
			$field2 = 'fk_paiementfourn';
			$field3 = '';
			$sharedentity = 'facture_fourn';
		}

		$sql = "SELECT p.ref, pf.amount, pf.multicurrency_amount, p.fk_paiement, p.datep, p.num_paiement as num, t.code".$field3 . $field4;
		$sql .= " FROM ".$this->db->prefix().$table." as pf, ".$this->db->prefix().$table2." as p, ".$this->db->prefix()."c_paiement as t";
		$sql .= " WHERE pf.".$field." = ".((int) $this->id);
		$sql .= " AND pf.".$field2." = p.rowid";
		$sql .= ' AND p.fk_paiement = t.id';
		$sql .= ' AND p.entity IN ('.getEntity($sharedentity).')';
		if ($filtertype) {
			$sql .= " AND t.code='PRE'";
		}

		dol_syslog(get_class($this)."::getListOfPayments", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tmp = array('amount' => $obj->amount, 'type' => $obj->code, 'date' => $obj->datep, 'num' => $obj->num, 'ref' => $obj->ref);
				if (!empty($field3)) {
					$tmp['ref_ext'] = $obj->ref_ext;
				}
				if (!empty($field4)) {
					$tmp['fk_bank_line'] = $obj->fk_bank;
				}
				$retarray[] = $tmp;
				$i++;
			}
			$this->db->free($resql);

			//look for credit notes and discounts and deposits
			$sql = '';
			if ($this->element == 'facture' || $this->element == 'invoice') {
				$sql = "SELECT rc.amount_ttc as amount, rc.multicurrency_amount_ttc as multicurrency_amount, rc.datec as date, f.ref as ref, rc.description as type";
				$sql .= ' FROM '.$this->db->prefix().'societe_remise_except as rc, '.$this->db->prefix().'facture as f';
				$sql .= ' WHERE rc.fk_facture_source=f.rowid AND rc.fk_facture = '.((int) $this->id);
				$sql .= ' AND (f.type = 2 OR f.type = 0 OR f.type = 3)'; // Find discount coming from credit note or excess received or deposits (payments from deposits are always null except if FACTURE_DEPOSITS_ARE_JUST_PAYMENTS is set)
			} elseif ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') {
				$sql = "SELECT rc.amount_ttc as amount, rc.multicurrency_amount_ttc as multicurrency_amount, rc.datec as date, f.ref as ref, rc.description as type";
				$sql .= ' FROM '.$this->db->prefix().'societe_remise_except as rc, '.$this->db->prefix().'facture_fourn as f';
				$sql .= ' WHERE rc.fk_invoice_supplier_source=f.rowid AND rc.fk_invoice_supplier = '.((int) $this->id);
				$sql .= ' AND (f.type = 2 OR f.type = 0 OR f.type = 3)'; // Find discount coming from credit note or excess received or deposits (payments from deposits are always null except if FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS is set)
			}

			if ($sql) {
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($multicurrency) {
							$retarray[] = array('amount' => $obj->multicurrency_amount, 'type' => $obj->type, 'date' => $obj->date, 'num' => '0', 'ref' => $obj->ref);
						} else {
							$retarray[] = array('amount' => $obj->amount, 'type' => $obj->type, 'date' => $obj->date, 'num' => '', 'ref' => $obj->ref);
						}
						$i++;
					}
				} else {
					$this->error = $this->db->lasterror();
					dol_print_error($this->db);
					return array();
				}
				$this->db->free($resql);
			}

			return $retarray;
		} else {
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
			return array();
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
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
	 *  @return    int         Return integer <=0 if no, >0 if yes
	 */
	public function is_erasable()
	{
		// phpcs:enable

		// We check if invoice is a temporary number (PROVxxxx)
		$tmppart = substr($this->ref, 1, 4);

		if ($this->statut == self::STATUS_DRAFT && $tmppart === 'PROV') { // If draft invoice and ref not yet defined
			return 1;
		}

		if (getDolGlobalString('INVOICE_CAN_NEVER_BE_REMOVED')) {
			return 0;
		}

		// If not a draft invoice and not temporary invoice
		if ($tmppart !== 'PROV') {
			$ventilExportCompta = $this->getVentilExportCompta();
			if ($ventilExportCompta != 0) {
				return -1;
			}

			// Get last number of validated invoice
			if ($this->element != 'invoice_supplier') {
				if (empty($this->thirdparty)) {
					$this->fetch_thirdparty(); // We need to have this->thirdparty defined, in case of numbering rule use tags that depend on thirdparty (like {t} tag).
				}
				$maxref = $this->getNextNumRef($this->thirdparty, 'last');

				// If there is no invoice into the reset range and not already dispatched, we can delete
				// If invoice to delete is last one and not already dispatched, we can delete
				if (!getDolGlobalString('INVOICE_CAN_ALWAYS_BE_REMOVED') && $maxref != '' && $maxref != $this->ref) {
					return -2;
				}

				// TODO If there is payment in bookkeeping, check payment is not dispatched in accounting
				// ...

				if ($this->situation_cycle_ref && method_exists($this, 'is_last_in_cycle')) {
					$last = $this->is_last_in_cycle();
					if (!$last) {
						return -3;
					}
				}
			}
		}

		// Test if there is at least one payment. If yes, refuse to delete.
		if (!getDolGlobalString('INVOICE_CAN_ALWAYS_BE_REMOVED') && $this->getSommePaiement() > 0) {
			return -4;
		}

		return 2;
	}

	/**
	 *	Return if an invoice was dispatched into bookkeeping
	 *
	 *	@return     int         Return integer <0 if KO, 0=no, 1=yes
	 */
	public function getVentilExportCompta()
	{
		$alreadydispatched = 0;

		$type = 'customer_invoice';
		if ($this->element == 'invoice_supplier') {
			$type = 'supplier_invoice';
		}

		$sql = " SELECT COUNT(ab.rowid) as nb FROM ".$this->db->prefix()."accounting_bookkeeping as ab WHERE ab.doc_type='".$this->db->escape($type)."' AND ab.fk_doc = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$alreadydispatched = $obj->nb;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		if ($alreadydispatched) {
			return 1;
		}
		return 0;
	}

	/**
	 * Return next reference of invoice not already used (or last reference)
	 *
	 * @param	 Societe	$soc		Thirdparty object
	 * @param    string		$mode		'next' for next value or 'last' for last value
	 * @return   string					free ref or last ref
	 */
	public function getNextNumRef($soc, $mode = 'next')
	{
		// TODO Must be implemented into main class
		return '';
	}

	/**
	 *	Return label of type of invoice
	 *
	 *	@param		int			$withbadge		1=Add span for badge css, 2=Add span and show short label
	 *	@return     string        				Label of type of invoice
	 */
	public function getLibType($withbadge = 0)
	{
		global $langs;

		$labellong = "Unknown";
		if ($this->type == CommonInvoice::TYPE_STANDARD) {
			$labellong = "InvoiceStandard";
			$labelshort = "InvoiceStandardShort";
		} elseif ($this->type == CommonInvoice::TYPE_REPLACEMENT) {
			$labellong = "InvoiceReplacement";
			$labelshort = "InvoiceReplacementShort";
		} elseif ($this->type == CommonInvoice::TYPE_CREDIT_NOTE) {
			$labellong = "InvoiceAvoir";
			$labelshort = "CreditNote";
		} elseif ($this->type == CommonInvoice::TYPE_DEPOSIT) {
			$labellong = "InvoiceDeposit";
			$labelshort = "Deposit";
		} elseif ($this->type == CommonInvoice::TYPE_PROFORMA) {
			$labellong = "InvoiceProForma"; // Not used.
			$labelshort = "ProForma";
		} elseif ($this->type == CommonInvoice::TYPE_SITUATION) {
			$labellong = "InvoiceSituation";
			$labelshort = "Situation";
		}

		$out = '';
		if ($withbadge) {
			$out .= '<span class="badgeneutral" title="'.dol_escape_htmltag($langs->trans($labellong)).'">';
		}
		$out .= $langs->trans($withbadge == 2 ? $labelshort : $labellong);
		if ($withbadge) {
			$out .= '</span>';
		}
		return $out;
	}

	/**
	 *	Return label of invoice subtype
	 *
	 *  @param		string		$table          table of invoice
	 *	@return     string|int     				Label of invoice subtype or -1 if error
	 */
	public function getSubtypeLabel($table = '')
	{
		$subtypeLabel = '';
		if ($table === 'facture' || $table === 'facture_fourn') {
			$sql = "SELECT s.label FROM " . $this->db->prefix() . $table . " AS f";
			$sql .= " INNER JOIN " . $this->db->prefix() . "c_invoice_subtype AS s ON f.subtype = s.rowid";
			$sql .= " WHERE f.ref = '".$this->db->escape($this->ref)."'";
		} elseif ($table === 'facture_rec' || $table === 'facture_fourn_rec') {
			$sql = "SELECT s.label FROM " . $this->db->prefix() . $table . " AS f";
			$sql .= " INNER JOIN " . $this->db->prefix() . "c_invoice_subtype AS s ON f.subtype = s.rowid";
			$sql .= " WHERE f.titre = '".$this->db->escape($this->title)."'";
		} else {
			return -1;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$subtypeLabel = $obj->label;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}

		return $subtypeLabel;
	}

	/**
	 *    	Retrieve a list of invoice subtype labels or codes.
	 *
	 *		@param	int		$mode		0=Return id+label, 1=Return code+id
	 *    	@return array      			Array of subtypes
	 */
	public function getArrayOfInvoiceSubtypes($mode = 0)
	{
		global $mysoc;

		$effs = array();

		$sql = "SELECT rowid, code, label as label";
		$sql .= " FROM " . MAIN_DB_PREFIX . 'c_invoice_subtype';
		$sql .= " WHERE active = 1 AND fk_country = ".((int) $mysoc->country_id)." AND entity IN(".getEntity('c_invoice_subtype').")";
		$sql .= " ORDER by rowid, code";

		dol_syslog(get_class($this) . '::getArrayOfInvoiceSubtypes', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				if (!$mode) {
					$key = $objp->rowid;
					$effs[$key] = $objp->label;
				} else {
					$key = $objp->code;
					$effs[$key] = $objp->rowid;
				}

				$i++;
			}
			$this->db->free($resql);
		}

		return $effs;
	}

	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param      integer	$alreadypaid    0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 *  @return     string			        Label of status
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $this->statut, $mode, $alreadypaid, $this->type);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of a status
	 *
	 *	@param    	int  	$paye          	Status field paye
	 *	@param      int		$status        	Id status
	 *	@param      int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=long label + picto
	 *	@param		integer	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, -1 otherwise)
	 *	@param		int		$type			Type invoice. If -1, we use $this->type
	 *	@return     string        			Label of status
	 */
	public function LibStatut($paye, $status, $mode = 0, $alreadypaid = -1, $type = -1)
	{
		// phpcs:enable
		global $langs, $hookmanager;
		$langs->load('bills');

		if ($type == -1) {
			$type = $this->type;
		}

		$statusType = 'status0';
		$prefix = 'Short';
		if (!$paye) {
			if ($status == 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusDraft');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusDraft');
			} elseif (($status == 3 || $status == 2) && $alreadypaid <= 0) {
				if ($status == 3) {
					$labelStatus = $langs->transnoentitiesnoconv('BillStatusCanceled');
					$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusCanceled');
				} else {
					$labelStatus = $langs->transnoentitiesnoconv('BillStatusClosedUnpaid');
					$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusClosedUnpaid');
				}
				$statusType = 'status5';
			} elseif (($status == 3 || $status == 2) && $alreadypaid > 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusClosedPaidPartially');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusClosedPaidPartially');
				$statusType = 'status9';
			} elseif ($alreadypaid == 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusNotPaid');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusNotPaid');
				$statusType = 'status1';
			} else {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusStarted');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusStarted');
				$statusType = 'status3';
			}
		} else {
			$statusType = 'status6';

			if ($type == self::TYPE_CREDIT_NOTE) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusPaidBackOrConverted'); // credit note
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusPaidBackOrConverted'); // credit note
			} elseif ($type == self::TYPE_DEPOSIT) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusConverted'); // deposit invoice
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusConverted'); // deposit invoice
			} else {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusPaid');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusPaid');
			}
		}

		$parameters = array(
			'status'      => $status,
			'mode'        => $mode,
			'paye'        => $paye,
			'alreadypaid' => $alreadypaid,
			'type'        => $type
		);

		$reshook = $hookmanager->executeHooks('LibStatut', $parameters, $this); // Note that $action and $object may have been modified by hook

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}



		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Returns an invoice payment deadline based on the invoice settlement
	 *  conditions and billing date.
	 *
	 *	@param      int			$cond_reglement   	Condition of payment (code or id) to use. If 0, we use current condition.
	 *  @return     int|string    			       	Date limit of payment if OK, <0 or string if KO
	 */
	public function calculate_date_lim_reglement($cond_reglement = 0)
	{
		// phpcs:enable
		if (!$cond_reglement) {
			$cond_reglement = $this->cond_reglement_code;
		}
		if (!$cond_reglement) {
			$cond_reglement = $this->cond_reglement_id;
		}
		if (!$cond_reglement) {
			return $this->date;
		}

		$cdr_nbjour = 0;
		$cdr_type = 0;
		$cdr_decalage = 0;

		$sqltemp = "SELECT c.type_cdr, c.nbjour, c.decalage";
		$sqltemp .= " FROM ".$this->db->prefix()."c_payment_term as c";
		if (is_numeric($cond_reglement)) {
			$sqltemp .= " WHERE c.rowid=".((int) $cond_reglement);
		} else {
			$sqltemp .= " WHERE c.entity IN (".getEntity('c_payment_term').")";
			$sqltemp .= " AND c.code = '".$this->db->escape($cond_reglement)."'";
		}

		dol_syslog(get_class($this).'::calculate_date_lim_reglement', LOG_DEBUG);
		$resqltemp = $this->db->query($sqltemp);
		if ($resqltemp) {
			if ($this->db->num_rows($resqltemp)) {
				$obj = $this->db->fetch_object($resqltemp);
				$cdr_nbjour = $obj->nbjour;
				$cdr_type = $obj->type_cdr;
				$cdr_decalage = $obj->decalage;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
		$this->db->free($resqltemp);

		/* Definition de la date limit */

		// 0 : adding the number of days
		if ($cdr_type == 0) {
			$datelim = $this->date + ($cdr_nbjour * 3600 * 24);

			$datelim += ($cdr_decalage * 3600 * 24);
		} elseif ($cdr_type == 1) {
			// 1 : application of the "end of the month" rule
			$datelim = $this->date + ($cdr_nbjour * 3600 * 24);

			$mois = date('m', $datelim);
			$annee = date('Y', $datelim);
			if ($mois == 12) {
				$mois = 1;
				$annee += 1;
			} else {
				$mois += 1;
			}
			// We move at the beginning of the next month, and we take a day off
			$datelim = dol_mktime(12, 0, 0, $mois, 1, $annee);
			$datelim -= (3600 * 24);

			$datelim += ($cdr_decalage * 3600 * 24);
		} elseif ($cdr_type == 2 && !empty($cdr_decalage)) {
			// 2 : application of the rule, the N of the current or next month
			include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$datelim = $this->date + ($cdr_nbjour * 3600 * 24);

			$date_piece = dol_mktime(0, 0, 0, date('m', $datelim), date('d', $datelim), date('Y', $datelim)); // Sans les heures minutes et secondes
			$date_lim_current = dol_mktime(0, 0, 0, date('m', $datelim), $cdr_decalage, date('Y', $datelim)); // Sans les heures minutes et secondes
			$date_lim_next = dol_time_plus_duree($date_lim_current, 1, 'm'); // Add 1 month

			$diff = $date_piece - $date_lim_current;

			if ($diff < 0) {
				$datelim = $date_lim_current;
			} else {
				$datelim = $date_lim_next;
			}
		} else {
			return 'Bad value for type_cdr in database for record cond_reglement = '.$cond_reglement;
		}

		return $datelim;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create a withdrawal request for a direct debit order or a credit transfer order.
	 *  Use the remain to pay excluding all existing open direct debit requests.
	 *
	 *	@param      User	$fuser      				User asking the direct debit transfer
	 *  @param		float	$amount						Amount we request direct debit for
	 *  @param		string	$type						'direct-debit' or 'bank-transfer'
	 *  @param		string	$sourcetype					Source ('facture' or 'supplier_invoice')
	 *  @param		int		$checkduplicateamongall		0=Default (check among open requests only to find if request already exists). 1=Check also among requests completely processed and cancel if at least 1 request exists whatever is its status.
	 *	@return     int         						Return integer <0 if KO, 0 if a request already exists, >0 if OK
	 */
	public function demande_prelevement($fuser, $amount = 0, $type = 'direct-debit', $sourcetype = 'facture', $checkduplicateamongall = 0)
	{
		// phpcs:enable
		global $conf;

		$error = 0;

		dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);

		if ($this->status > self::STATUS_DRAFT && $this->paye == 0) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$bac = new CompanyBankAccount($this->db);
			$bac->fetch(0, '', $this->socid);

			$sql = "SELECT count(rowid) as nb";
			$sql .= " FROM ".$this->db->prefix()."prelevement_demande";
			if ($type == 'bank-transfer') {
				$sql .= " WHERE fk_facture_fourn = ".((int) $this->id);
			} else {
				$sql .= " WHERE fk_facture = ".((int) $this->id);
			}
			$sql .= " AND type = 'ban'"; // To exclude record done for some online payments
			if (empty($checkduplicateamongall)) {
				$sql .= " AND traite = 0";
			}

			dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj && $obj->nb == 0) {	// If no request found yet
					$now = dol_now();

					$totalpaid = $this->getSommePaiement();
					$totalcreditnotes = $this->getSumCreditNotesUsed();
					$totaldeposits = $this->getSumDepositsUsed();
					//print "totalpaid=".$totalpaid." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

					// We can also use bcadd to avoid pb with floating points
					// For example print 239.2 - 229.3 - 9.9; does not return 0.
					//$resteapayer=bcadd($this->total_ttc,$totalpaid,$conf->global->MAIN_MAX_DECIMALS_TOT);
					//$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
					if (empty($amount)) {
						$amount = price2num($this->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
					}

					if (is_numeric($amount) && $amount != 0) {
						$sql = 'INSERT INTO '.$this->db->prefix().'prelevement_demande(';
						if ($type == 'bank-transfer') {
							$sql .= 'fk_facture_fourn, ';
						} else {
							$sql .= 'fk_facture, ';
						}
						$sql .= ' amount, date_demande, fk_user_demande, code_banque, code_guichet, number, cle_rib, sourcetype, type, entity)';
						$sql .= " VALUES (".((int) $this->id);
						$sql .= ", ".((float) price2num($amount));
						$sql .= ", '".$this->db->idate($now)."'";
						$sql .= ", ".((int) $fuser->id);
						$sql .= ", '".$this->db->escape($bac->code_banque)."'";
						$sql .= ", '".$this->db->escape($bac->code_guichet)."'";
						$sql .= ", '".$this->db->escape($bac->number)."'";
						$sql .= ", '".$this->db->escape($bac->cle_rib)."'";
						$sql .= ", '".$this->db->escape($sourcetype)."'";
						$sql .= ", 'ban'";
						$sql .= ", ".((int) $conf->entity);
						$sql .= ")";

						dol_syslog(get_class($this)."::demande_prelevement", LOG_DEBUG);
						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = $this->db->lasterror();
							dol_syslog(get_class($this).'::demandeprelevement Erreur');
							$error++;
						}
					} else {
						$this->error = 'WithdrawRequestErrorNilAmount';
						dol_syslog(get_class($this).'::demandeprelevement WithdrawRequestErrorNilAmount');
						$error++;
					}

					if (!$error) {
						// Force payment mode of invoice to withdraw
						$payment_mode_id = dol_getIdFromCode($this->db, ($type == 'bank-transfer' ? 'VIR' : 'PRE'), 'c_paiement', 'code', 'id', 1);
						if ($payment_mode_id > 0) {
							$result = $this->setPaymentMethods($payment_mode_id);
						}
					}

					if ($error) {
						return -1;
					}
					return 1;
				} else {
					$this->error = "A request already exists";
					dol_syslog(get_class($this).'::demandeprelevement Can t create a request to generate a direct debit, a request already exists.');
					return 0;
				}
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this).'::demandeprelevement Error -2');
				return -2;
			}
		} else {
			$this->error = "Status of invoice does not allow this";
			dol_syslog(get_class($this)."::demandeprelevement ".$this->error." $this->status, $this->paye, $this->mode_reglement_id");
			return -3;
		}
	}


	/**
	 *  Create a payment with Stripe card
	 *  Must take amount using Stripe and record an event into llx_actioncomm
	 *  Record bank payment
	 *  Send email to customer ?
	 *
	 *	@param      User	$fuser      	User asking the direct debit transfer
	 *  @param		int		$id				Invoice ID with remain to pay
	 *  @param		string	$sourcetype		Source ('facture' or 'supplier_invoice')
	 *	@return     int         			Return integer <0 if KO, >0 if OK
	 */
	public function makeStripeCardRequest($fuser, $id, $sourcetype = 'facture')
	{
		// TODO See in sellyoursaas
		return 0;
	}

	/**
	 *  Create a direct debit order into prelevement_bons for a given prelevement_request, then
	 *  Send the payment order to the service (for a direct debit order or a credit transfer order) and record an event in llx_actioncomm.
	 *
	 *	@param      User	$fuser      	User asking the direct debit transfer
	 *  @param		int		$did			ID of unitary payment request to pay
	 *  @param		string	$type			'direct-debit' or 'bank-transfer'
	 *  @param		string	$sourcetype		Source ('facture' or 'supplier_invoice')
	 *  @param		string	$service		'StripeTest', 'StripeLive', ...
	 *  @param		string	$forcestripe	To force another stripe env: 'cus_account@pk_...:sk_...'
	 *	@return     int         			Return integer <0 if KO, >0 if OK
	 */
	public function makeStripeSepaRequest($fuser, $did, $type = 'direct-debit', $sourcetype = 'facture', $service = '', $forcestripe = '')
	{
		global $conf, $user, $langs;

		if ($type != 'bank-transfer' && $type != 'credit-transfer' && !getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
			return 0;
		}
		if ($type != 'direct-debit' && !getDolGlobalString('STRIPE_SEPA_CREDIT_TRANSFER')) {
			return 0;
		}
		// Set a default value for service if not provided
		if (empty($service)) {
			$service = 'StripeTest';
			if (getDolGlobalString('STRIPE_LIVE') && !GETPOST('forcesandbox', 'alpha')) {
				$service = 'StripeLive';
			}
		}

		$error = 0;

		dol_syslog(get_class($this)."::makeStripeSepaRequest start did=".$did." type=".$type." service=".$service." sourcetype=".$sourcetype." forcestripe=".$forcestripe, LOG_DEBUG);

		if ($this->status > self::STATUS_DRAFT && $this->paye == 0) {
			// Get the default payment mode for BAN payment of the third party
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$bac = new CompanyBankAccount($this->db);	// Table societe_rib
			$result = $bac->fetch(0, '', $this->socid, 1, 'ban');
			if ($result <= 0 || empty($bac->id)) {
				$this->error = $langs->trans("ThirdpartyHasNoDefaultBanAccount");
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::makeStripeSepaRequest ".$this->error);
				return -1;
			}

			// Load the pending payment request to process (with rowid=$did)
			$sql = "SELECT rowid, date_demande, amount, fk_facture, fk_facture_fourn, fk_salary, fk_prelevement_bons";
			$sql .= " FROM ".$this->db->prefix()."prelevement_demande";
			$sql .= " WHERE rowid = ".((int) $did);
			if ($type != 'bank-transfer' && $type != 'credit-transfer') {
				$sql .= " AND fk_facture = ".((int) $this->id);				// Add a protection to not pay another invoice than current one
			}
			if ($type != 'direct-debit') {
				if ($sourcetype == 'salary') {
					$sql .= " AND fk_salary = ".((int) $this->id);			// Add a protection to not pay another salary than current one
				} else {
					$sql .= " AND fk_facture_fourn = ".((int) $this->id);	// Add a protection to not pay another invoice than current one
				}
			}
			$sql .= " AND traite = 0";	// To not process payment request that were already converted into a direct debit or credit transfer order (Note: fk_prelevement_bons is also empty when traite = 0)

			dol_syslog(get_class($this)."::makeStripeSepaRequest load requests to process", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if (!$obj) {
					dol_print_error($this->db, 'CantFindRequestWithId');
					return -2;
				}

				// amount to pay
				$amount = $obj->amount;

				if (is_numeric($amount) && $amount != 0) {
					require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
					$companypaymentmode = new CompanyPaymentMode($this->db);	// table societe_rib
					$companypaymentmode->fetch($bac->id);

					$this->stripechargedone = 0;
					$this->stripechargeerror = 0;

					$now = dol_now();

					$currency = $conf->currency;

					$errorforinvoice = 0;     // We reset the $errorforinvoice at each invoice loop

					$this->fetch_thirdparty();

					dol_syslog("makeStripeSepaRequest Process payment request amount=".$amount." thirdparty_id=" . $this->thirdparty->id . ", thirdparty_name=" . $this->thirdparty->name . " ban id=" . $bac->id, LOG_DEBUG);

					//$alreadypayed = $this->getSommePaiement();
					//$amount_credit_notes_included = $this->getSumCreditNotesUsed();
					//$amounttopay = $this->total_ttc - $alreadypayed - $amount_credit_notes_included;
					$amounttopay = $amount;

					// Correct the amount according to unit of currency
					// See https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
					$arrayzerounitcurrency = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
					$amountstripe = $amounttopay;
					if (!in_array($currency, $arrayzerounitcurrency)) {
						$amountstripe = $amountstripe * 100;
					}

					$fk_bank_account = getDolGlobalInt('STRIPE_BANK_ACCOUNT_FOR_PAYMENTS');		// Bank account used for SEPA direct debit or credit transfer. Must be the Stripe account in Dolibarr.
					if (!($fk_bank_account > 0)) {
						$error++;
						$errorforinvoice++;
						dol_syslog("makeStripeSepaRequest Error no bank account defined for Stripe payments", LOG_ERR);
						$this->errors[] = "Error bank account for Stripe payments not defined into Stripe module";
					}

					$this->db->begin();

					// Create a prelevement_bon
					require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
					$bon = new BonPrelevement($this->db);
					if (!$error) {
						if (empty($obj->fk_prelevement_bons)) {
							// This creates a record into llx_prelevement_bons and updates link with llx_prelevement_demande
							$nbinvoices = $bon->create(0, 0, 'real', 'ALL', '', 0, $type, $did, $fk_bank_account);
							if ($nbinvoices <= 0) {
								$error++;
								$errorforinvoice++;
								dol_syslog("makeStripeSepaRequest Error on BonPrelevement creation", LOG_ERR);
								$this->errors[] = "Error on BonPrelevement creation";
							}
							/*
							if (!$error) {
								// Update the direct debit payment request of the processed request to save the id of the prelevement_bon
								$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_demande SET";
								$sql .= " fk_prelevement_bons = ".((int) $bon->id);
								$sql .= " WHERE rowid = ".((int) $did);

								$result = $this->db->query($sql);
								if ($result < 0) {
									$error++;
									$this->errors[] = "Error on updating fk_prelevement_bons to ".$bon->id;
								}
							}
							*/
						} else {
							$error++;
							$errorforinvoice++;
							dol_syslog("makeStripeSepaRequest Error Line already part of a bank payment order", LOG_ERR);
							$this->errors[] = "The line is already included into a bank payment order. Delete the bank payment order first.";
						}
					}

					if (!$error) {
						if ($amountstripe > 0) {
							try {
								global $savstripearrayofkeysbyenv;
								global $stripearrayofkeysbyenv;
								$servicestatus = 0;
								if ($service == 'StripeLive') {
									$servicestatus = 1;
								}

								//var_dump($companypaymentmode);
								dol_syslog("makeStripeSepaRequest We will try to pay with companypaymentmodeid=" . $companypaymentmode->id . " stripe_card_ref=" . $companypaymentmode->stripe_card_ref . " mode=" . $companypaymentmode->status, LOG_DEBUG);

								$thirdparty = new Societe($this->db);
								$resultthirdparty = $thirdparty->fetch($this->socid);

								include_once DOL_DOCUMENT_ROOT . '/stripe/class/stripe.class.php';        // This include the include of htdocs/stripe/config.php
								// So it inits or erases the $stripearrayofkeysbyenv
								$stripe = new Stripe($this->db);

								if (empty($savstripearrayofkeysbyenv)) {
									$savstripearrayofkeysbyenv = $stripearrayofkeysbyenv;
								}
								dol_syslog("makeStripeSepaRequest Current Stripe environment is " . $stripearrayofkeysbyenv[$servicestatus]['publishable_key']);
								dol_syslog("makeStripeSepaRequest Current Saved Stripe environment is ".$savstripearrayofkeysbyenv[$servicestatus]['publishable_key']);

								$foundalternativestripeaccount = '';

								// Force stripe to another value (by default this value is empty)
								if (! empty($forcestripe)) {
									dol_syslog("makeStripeSepaRequest A dedicated stripe account was forced, so we switch to it.");

									$tmparray = explode('@', $forcestripe);
									if (! empty($tmparray[1])) {
										$tmparray2 = explode(':', $tmparray[1]);
										if (! empty($tmparray2[1])) {
											$stripearrayofkeysbyenv[$servicestatus]["publishable_key"] = $tmparray2[0];
											$stripearrayofkeysbyenv[$servicestatus]["secret_key"] = $tmparray2[1];

											$stripearrayofkeys = $stripearrayofkeysbyenv[$servicestatus];
											\Stripe\Stripe::setApiKey($stripearrayofkeys['secret_key']);

											$foundalternativestripeaccount = $tmparray[0];    // Store the customer id

											dol_syslog("makeStripeSepaRequest We use now customer=".$foundalternativestripeaccount." publishable_key=".$stripearrayofkeys['publishable_key'], LOG_DEBUG);
										}
									}

									if (! $foundalternativestripeaccount) {
										$stripearrayofkeysbyenv = $savstripearrayofkeysbyenv;

										$stripearrayofkeys = $savstripearrayofkeysbyenv[$servicestatus];
										\Stripe\Stripe::setApiKey($stripearrayofkeys['secret_key']);
										dol_syslog("makeStripeSepaRequest We found a bad value for Stripe Account for thirdparty id=".$thirdparty->id.", so we ignore it and keep using the global one, so ".$stripearrayofkeys['publishable_key'], LOG_WARNING);
									}
								} else {
									$stripearrayofkeysbyenv = $savstripearrayofkeysbyenv;

									$stripearrayofkeys = $savstripearrayofkeysbyenv[$servicestatus];
									\Stripe\Stripe::setApiKey($stripearrayofkeys['secret_key']);
									dol_syslog("makeStripeSepaRequest No dedicated Stripe Account requested, so we use global one, so ".$stripearrayofkeys['publishable_key'], LOG_DEBUG);
								}

								$stripeacc = $stripe->getStripeAccount($service, $this->socid);								// Get Stripe OAuth connect account if it exists (no network access here)

								if ($foundalternativestripeaccount) {
									if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
										$customer = \Stripe\Customer::retrieve(array('id' => "$foundalternativestripeaccount", 'expand[]' => 'sources'));
									} else {
										$customer = \Stripe\Customer::retrieve(array('id' => "$foundalternativestripeaccount", 'expand[]' => 'sources'), array("stripe_account" => $stripeacc));
									}
								} else {
									$customer = $stripe->customerStripe($thirdparty, $stripeacc, $servicestatus, 0);
									if (empty($customer) && ! empty($stripe->error)) {
										$this->errors[] = $stripe->error;
									}
									/*if (!empty($customer) && empty($customer->sources)) {
									 $customer = null;
									 $this->errors[] = '\Stripe\Customer::retrieve did not returned the sources';
									 }*/
								}

								// $nbhoursbetweentries = (empty($conf->global->SELLYOURSAAS_NBHOURSBETWEENTRIES) ? 49 : $conf->global->SELLYOURSAAS_NBHOURSBETWEENTRIES);				// Must have more that 48 hours + 1 between each try (so 1 try every 3 daily batch)
								// $nbdaysbeforeendoftries = (empty($conf->global->SELLYOURSAAS_NBDAYSBEFOREENDOFTRIES) ? 35 : $conf->global->SELLYOURSAAS_NBDAYSBEFOREENDOFTRIES);
								$postactionmessages = [];

								if ($resultthirdparty > 0 && !empty($customer)) {
									if (!$error) {	// Payment was not canceled
										$stripecard = null;
										if ($companypaymentmode->type == 'ban') {
											// Check into societe_rib if a payment mode for Stripe and ban payment exists
											// To make a Stripe SEPA payment request, we must have the payment mode source already saved into societe_rib and retrieved with ->sepaStripe
											// The payment mode source is created when we create the bank account on Stripe with paymentmodes.php?action=create
											$stripecard = $stripe->sepaStripe($customer, $companypaymentmode, $stripeacc, $servicestatus, 0);
										} else {
											$error++;
											$this->error = 'The payment mode type is not "ban"';
										}

										if ($stripecard) {  // Can be src_... (for sepa) or pm_... (new card mode). Note that card_... (old card mode) should not happen here.
											$FULLTAG = 'DID='.$did.'-INV=' . $this->id . '-CUS=' . $thirdparty->id;
											$description = 'Stripe payment from makeStripeSepaRequest: ' . $FULLTAG . ' did='.$did.' ref=' . $this->ref;

											$stripefailurecode = '';
											$stripefailuremessage = '';
											$stripefailuredeclinecode = '';

											// Using new SCA method
											dol_syslog("* Create payment on SEPA " . $stripecard->id . ", amounttopay=" . $amounttopay . ", amountstripe=" . $amountstripe . ", FULLTAG=" . $FULLTAG, LOG_DEBUG);

											// Create payment intent and charge payment (confirmnow = true)
											$paymentintent = $stripe->getPaymentIntent($amounttopay, $currency, $FULLTAG, $description, $this, $customer->id, $stripeacc, $servicestatus, 0, 'automatic', true, $stripecard->id, 1, 1, $did);

											$charge = new stdClass();

											if ($paymentintent->status === 'succeeded' || $paymentintent->status === 'processing') {
												$charge->status = 'ok';
												$charge->id = $paymentintent->id;
												$charge->customer = $customer->id;
											} elseif ($paymentintent->status === 'requires_action') {
												//paymentintent->status may be => 'requires_action' (no error in such a case)
												dol_syslog(var_export($paymentintent, true), LOG_DEBUG);

												$charge->status = 'failed';
												$charge->customer = $customer->id;
												$charge->failure_code = $stripe->code;
												$charge->failure_message = $stripe->error;
												$charge->failure_declinecode = $stripe->declinecode;
												$stripefailurecode = $stripe->code;
												$stripefailuremessage = 'Action required. Contact the support at ';// . $conf->global->SELLYOURSAAS_MAIN_EMAIL;
												$stripefailuredeclinecode = $stripe->declinecode;
											} else {
												dol_syslog(var_export($paymentintent, true), LOG_DEBUG);

												$charge->status = 'failed';
												$charge->customer = $customer->id;
												$charge->failure_code = $stripe->code;
												$charge->failure_message = $stripe->error;
												$charge->failure_declinecode = $stripe->declinecode;
												$stripefailurecode = $stripe->code;
												$stripefailuremessage = $stripe->error;
												$stripefailuredeclinecode = $stripe->declinecode;
											}

											//var_dump("stripefailurecode=".$stripefailurecode." stripefailuremessage=".$stripefailuremessage." stripefailuredeclinecode=".$stripefailuredeclinecode);
											//exit;


											// Return $charge = array('id'=>'ch_XXXX', 'status'=>'succeeded|pending|failed', 'failure_code'=>, 'failure_message'=>...)
											if (empty($charge) || $charge->status == 'failed') {
												dol_syslog('Failed to charge payment mode ' . $stripecard->id . ' stripefailurecode=' . $stripefailurecode . ' stripefailuremessage=' . $stripefailuremessage . ' stripefailuredeclinecode=' . $stripefailuredeclinecode, LOG_WARNING);

												// Save a stripe payment was in error
												$this->stripechargeerror++;

												$error++;
												$errorforinvoice++;
												$errmsg = $langs->trans("FailedToChargeCard");
												if (!empty($charge)) {
													if ($stripefailuredeclinecode == 'authentication_required') {
														$errauthenticationmessage = $langs->trans("ErrSCAAuthentication");
														$errmsg = $errauthenticationmessage;
													} elseif (in_array($stripefailuredeclinecode, ['insufficient_funds', 'generic_decline'])) {
														$errmsg .= ': ' . $charge->failure_code;
														$errmsg .= ($charge->failure_message ? ' - ' : '') . ' ' . $charge->failure_message;
														if (empty($stripefailurecode)) {
															$stripefailurecode = $charge->failure_code;
														}
														if (empty($stripefailuremessage)) {
															$stripefailuremessage = $charge->failure_message;
														}
													} else {
														$errmsg .= ': failure_code=' . $charge->failure_code;
														$errmsg .= ($charge->failure_message ? ' - ' : '') . ' failure_message=' . $charge->failure_message;
														if (empty($stripefailurecode)) {
															$stripefailurecode = $charge->failure_code;
														}
														if (empty($stripefailuremessage)) {
															$stripefailuremessage = $charge->failure_message;
														}
													}
												} else {
													$errmsg .= ': ' . $stripefailurecode . ' - ' . $stripefailuremessage;
													$errmsg .= ($stripefailuredeclinecode ? ' - ' . $stripefailuredeclinecode : '');
												}

												$description = 'Stripe payment ERROR from makeStripeSepaRequest: ' . $FULLTAG;
												$postactionmessages[] = $errmsg . ' (' . $stripearrayofkeys['publishable_key'] . ')';
												$this->errors[] = $errmsg;
											} else {
												dol_syslog('Successfuly request '.$type.' '.$stripecard->id);

												$postactionmessages[] = 'Success to request '.$type.' (' . $charge->id . ' with ' . $stripearrayofkeys['publishable_key'] . ')';

												// Save a stripe payment was done in real life so later we will be able to force a commit on recorded payments
												// even if in batch mode (method doTakePaymentStripe), we will always make all action in one transaction with a forced commit.
												$this->stripechargedone++;

												// Default description used for label of event. Will be overwrite by another value later.
												$description = 'Stripe payment request OK (' . $charge->id . ') from makeStripeSepaRequest: ' . $FULLTAG;
											}

											$object = $this;

											// Track an event
											if (empty($charge) || $charge->status == 'failed') {
												$actioncode = 'PAYMENT_STRIPE_KO';
												$extraparams = $stripefailurecode;
												$extraparams .= (($extraparams && $stripefailuremessage) ? ' - ' : '') . $stripefailuremessage;
												$extraparams .= (($extraparams && $stripefailuredeclinecode) ? ' - ' : '') . $stripefailuredeclinecode;
											} else {
												$actioncode = 'PAYMENT_STRIPE_OK';
												$extraparams = '';
											}
										} else {
											$error++;
											$errorforinvoice++;
											dol_syslog("No ban payment method found for this stripe customer " . $customer->id, LOG_WARNING);
											$this->errors[] = 'Failed to get direct debit payment method for stripe customer = ' . $customer->id;

											$description = 'Failed to find or use the payment mode - no ban defined for the thirdparty account';
											$stripefailurecode = 'BADPAYMENTMODE';
											$stripefailuremessage = 'Failed to find or use the payment mode - no ban defined for the thirdparty account';
											$postactionmessages[] = $description . ' (' . $stripearrayofkeys['publishable_key'] . ')';

											$object = $this;

											$actioncode = 'PAYMENT_STRIPE_KO';
											$extraparams = '';
										}
									} else {
										// If error because payment was canceled for a logical reason, we do nothing (no event added)
										$description = '';
										$stripefailurecode = '';
										$stripefailuremessage = '';

										$object = $this;

										$actioncode = '';
										$extraparams = '';
									}
								} else {	// Else of the   if ($resultthirdparty > 0 && ! empty($customer)) {
									if ($resultthirdparty <= 0) {
										dol_syslog('SellYourSaasUtils Failed to load customer for thirdparty_id = ' . $thirdparty->id, LOG_WARNING);
										$this->errors[] = 'Failed to load Stripe account for thirdparty_id = ' . $thirdparty->id;
									} else { // $customer stripe not found
										dol_syslog('SellYourSaasUtils Failed to get Stripe customer id for thirdparty_id = ' . $thirdparty->id . " in mode " . $servicestatus . " in Stripe env " . $stripearrayofkeysbyenv[$servicestatus]['publishable_key'], LOG_WARNING);
										$this->errors[] = 'Failed to get Stripe account id for thirdparty_id = ' . $thirdparty->id . " in mode " . $servicestatus . " in Stripe env " . $stripearrayofkeysbyenv[$servicestatus]['publishable_key'];
									}
									$error++;
									$errorforinvoice++;

									$description = 'Failed to find or use your payment mode (no payment mode for this customer id)';
									$stripefailurecode = 'BADPAYMENTMODE';
									$stripefailuremessage = 'Failed to find or use your payment mode (no payment mode for this customer id)';
									$postactionmessages = [];

									$object = $this;

									$actioncode = 'PAYMENT_STRIPE_KO';
									$extraparams = '';
								}

								if ($description) {
									dol_syslog("* Record event for credit transfer or direct debit request result - " . $description);
									require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

									// Insert record of payment (success or error)
									$actioncomm = new ActionComm($this->db);

									$actioncomm->type_code = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
									$actioncomm->code = 'AC_' . $actioncode;
									$actioncomm->label = $description;
									$actioncomm->note_private = implode(",\n", $postactionmessages);
									$actioncomm->fk_project = $this->fk_project;
									$actioncomm->datep = $now;
									$actioncomm->datef = $now;
									$actioncomm->percentage = -1;   // Not applicable
									$actioncomm->socid = $thirdparty->id;
									$actioncomm->contactid = 0;
									$actioncomm->authorid = $user->id;   // User saving action
									$actioncomm->userownerid = $user->id;	// Owner of action
									// Fields when action is a real email (content is already into note)
									/*$actioncomm->email_msgid = $object->email_msgid;
									 $actioncomm->email_from  = $object->email_from;
									 $actioncomm->email_sender= $object->email_sender;
									 $actioncomm->email_to    = $object->email_to;
									 $actioncomm->email_tocc  = $object->email_tocc;
									 $actioncomm->email_tobcc = $object->email_tobcc;
									 $actioncomm->email_subject = $object->email_subject;
									 $actioncomm->errors_to   = $object->errors_to;*/
									$actioncomm->fk_element = $this->id;
									$actioncomm->elementtype = $this->element;
									$actioncomm->extraparams = dol_trunc($extraparams, 250);

									$actioncomm->create($user);
								}

								$this->description = $description;
								$this->postactionmessages = $postactionmessages;
							} catch (Exception $e) {
								$error++;
								$errorforinvoice++;
								dol_syslog('Error ' . $e->getMessage(), LOG_ERR);
								$this->errors[] = 'Error ' . $e->getMessage();
							}
						} else {	// If remain to pay is null
							$error++;
							$errorforinvoice++;
							dol_syslog("Remain to pay is null for the invoice " . $this->id . " " . $this->ref . ". Why is the invoice not classified 'Paid' ?", LOG_WARNING);
							$this->errors[] = "Remain to pay is null for the invoice " . $this->id . " " . $this->ref . ". Why is the invoice not classified 'Paid' ?";
						}
					}

					// Set status of the order to "Transferred" with method 'api'
					if (!$error && !$errorforinvoice) {
						$result = $bon->set_infotrans($user, $now, 3);
						if ($result < 0) {
							$error++;
							$errorforinvoice++;
							dol_syslog("Error on BonPrelevement creation", LOG_ERR);
							$this->errors[] = "Error on BonPrelevement creation";
						}
					}

					if (!$error && !$errorforinvoice) {
						// Update the direct debit payment request of the processed invoice to save the id of the prelevement_bon
						$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_demande SET";
						$sql .= " ext_payment_id = '".$this->db->escape($paymentintent->id)."',";
						$sql .= " ext_payment_site = '".$this->db->escape($service)."'";
						$sql .= " WHERE rowid = ".((int) $did);

						dol_syslog(get_class($this)."::makeStripeSepaRequest update to save stripe paymentintent ids", LOG_DEBUG);
						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = $this->db->lasterror();
							dol_syslog(get_class($this).'::makeStripeSepaRequest Erreur');
							$error++;
						}
					}

					if (!$error && !$errorforinvoice) {
						$this->db->commit();
					} else {
						$this->db->rollback();
					}
				} else {
					$this->error = 'WithdrawRequestErrorNilAmount';
					dol_syslog(get_class($this).'::makeStripeSepaRequest WithdrawRequestErrorNilAmount');
					$error++;
				}

				/*
				if (!$error) {
					// Force payment mode of the invoice to withdraw
					$payment_mode_id = dol_getIdFromCode($this->db, ($type == 'bank-transfer' ? 'VIR' : 'PRE'), 'c_paiement', 'code', 'id', 1);
					if ($payment_mode_id > 0) {
						$result = $this->setPaymentMethods($payment_mode_id);
					}
				}*/

				if ($error) {
					return -1;
				}
				return 1;
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this).'::makeStripeSepaRequest Erreur -2');
				return -2;
			}
		} else {
			$this->error = "Status of invoice does not allow this";
			dol_syslog(get_class($this)."::makeStripeSepaRequest ".$this->error." ".$this->status." ,".$this->paye.", ".$this->mode_reglement_id, LOG_WARNING);
			return -3;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a direct debit request or a credit transfer request
	 *
	 *  @param  User	$fuser      User making delete
	 *  @param  int		$did        ID of request to delete
	 *  @return	int					Return integer <0 if OK, >0 if KO
	 */
	public function demande_prelevement_delete($fuser, $did)
	{
		// phpcs:enable
		$sql = 'DELETE FROM '.$this->db->prefix().'prelevement_demande';
		$sql .= ' WHERE rowid = '.((int) $did);
		$sql .= ' AND traite = 0';
		if ($this->db->query($sql)) {
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this).'::demande_prelevement_delete Error '.$this->error);
			return -1;
		}
	}

	/**
	 * Build string for EPC QR Code
	 *
	 * @return	string			String for EPC QR Code
	 */
	public function buildEPCQrCodeString()
	{
		global $mysoc;

		// Convert total_ttc to a string with 2 decimal places
		$totalTTCString = number_format($this->total_ttc, 2, '.', '');

		// Initialize an array to hold the lines of the QR code
		$lines = array();

		// Add the standard elements to the QR code
		$lines = [
			'BCD',  // Service Tag (optional)
			'002',  // Version (optional)
			'1',	// Character set (optional)
			'SCT',  // Identification (optional)
		];

		// Add the bank account information
		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$bankAccount = new Account($this->db);
		if ($this->fk_account > 0) {
			$bankAccount->fetch($this->fk_account);
			$lines[] = $bankAccount->bic; //BIC (required)
			$lines[] = $mysoc->name; //Name (required)
			$lines[] = $bankAccount->iban; //IBAN (required)
		} else {
			$lines[] = ""; //BIC (required)
			$lines[] = $mysoc->name; //Name (required)
			$lines[] = ""; //IBAN (required)
		}

		// Add the amount and reference
		$lines[] = 'EUR' . $totalTTCString; // Amount (optional)
		$lines[] = ''; // Payment reference (optional)
		$lines[] = $this->ref; // Remittance Information (optional)

		// Join the lines with newline characters and return the result
		return implode("\n", $lines);
	}
	/**
	 * Build string for ZATCA QR Code (Arabi Saudia)
	 *
	 * @return	string			String for ZATCA QR Code
	 */
	public function buildZATCAQRString()
	{
		global $conf, $mysoc;

		$tmplang = new Translate('', $conf);
		$tmplang->setDefaultLang('en_US');
		$tmplang->load("main");

		$datestring = dol_print_date($this->date, 'dayhourrfc');
		//$pricewithtaxstring = price($this->total_ttc, 0, $tmplang, 0, -1, 2);
		//$pricetaxstring = price($this->total_tva, 0, $tmplang, 0, -1, 2);
		$pricewithtaxstring = price2num($this->total_ttc, 2, 1);
		$pricetaxstring = price2num($this->total_tva, 2, 1);

		/*
		$name = implode(unpack("H*", $this->thirdparty->name));
		$vatnumber = implode(unpack("H*", $this->thirdparty->tva_intra));
		$date = implode(unpack("H*", $datestring));
		$pricewithtax = implode(unpack("H*", price2num($pricewithtaxstring, 2)));
		$pricetax = implode(unpack("H*", $pricetaxstring));

		//var_dump(strlen($this->thirdparty->name));
		//var_dump(str_pad(dechex('9'), 2, '0', STR_PAD_LEFT));
		//var_dump($this->thirdparty->name);
		//var_dump(implode(unpack("H*", $this->thirdparty->name)));
		//var_dump(price($this->total_tva, 0, $tmplang, 0, -1, 2));

		$s = '01'.str_pad(dechex(strlen($this->thirdparty->name)), 2, '0', STR_PAD_LEFT).$name;
		$s .= '02'.str_pad(dechex(strlen($this->thirdparty->tva_intra)), 2, '0', STR_PAD_LEFT).$vatnumber;
		$s .= '03'.str_pad(dechex(strlen($datestring)), 2, '0', STR_PAD_LEFT).$date;
		$s .= '04'.str_pad(dechex(strlen($pricewithtaxstring)), 2, '0', STR_PAD_LEFT).$pricewithtax;
		$s .= '05'.str_pad(dechex(strlen($pricetaxstring)), 2, '0', STR_PAD_LEFT).$pricetax;
		$s .= '';					// Hash of xml invoice
		$s .= '';					// ecda signature
		$s .= '';					// ecda public key
		$s .= '';					// ecda signature of public key stamp
		*/

		// Using TLV format
		$s = pack('C1', 1).pack('C1', strlen($mysoc->name)).$mysoc->name;
		$s .= pack('C1', 2).pack('C1', strlen($mysoc->tva_intra)).$mysoc->tva_intra;
		$s .= pack('C1', 3).pack('C1', strlen($datestring)).$datestring;
		$s .= pack('C1', 4).pack('C1', strlen($pricewithtaxstring)).$pricewithtaxstring;
		$s .= pack('C1', 5).pack('C1', strlen($pricetaxstring)).$pricetaxstring;
		$s .= '';					// Hash of xml invoice
		$s .= '';					// ecda signature
		$s .= '';					// ecda public key
		$s .= '';					// ecda signature of public key stamp

		$s = base64_encode($s);

		return $s;
	}


	/**
	 * Build string for QR-Bill (Switzerland)
	 *
	 * @return	string			String for Switzerland QR Code if QR-Bill
	 */
	public function buildSwitzerlandQRString()
	{
		global $conf, $mysoc;

		$tmplang = new Translate('', $conf);
		$tmplang->setDefaultLang('en_US');
		$tmplang->load("main");

		$pricewithtaxstring = price2num($this->total_ttc, 2, 1);
		$pricetaxstring = price2num($this->total_tva, 2, 1);

		$complementaryinfo = '';
		/*
		 Example: //S1/10/10201409/11/190512/20/1400.000-53/30/106017086/31/180508/32/7.7/40/2:10;0:30
		 /10/ Numéro de facture – 10201409
		 /11/ Date de facture – 12.05.2019
		 /20/ Référence client – 1400.000-53
		 /30/ Numéro IDE pour la TVA – CHE-106.017.086 TVA
		 /31/ Date de la prestation pour la comptabilisation de la TVA – 08.05.2018
		 /32/ Taux de TVA sur le montant total de la facture – 7.7%
		 /40/ Conditions – 2% d’escompte à 10 jours, paiement net à 30 jours
		 */
		$datestring = dol_print_date($this->date, '%y%m%d');
		//$pricewithtaxstring = price($this->total_ttc, 0, $tmplang, 0, -1, 2);
		//$pricetaxstring = price($this->total_tva, 0, $tmplang, 0, -1, 2);
		$complementaryinfo = '//S1/10/'.str_replace('/', '', $this->ref).'/11/'.$datestring;
		if ($this->ref_client) {
			$complementaryinfo .= '/20/'.$this->ref_client;
		}
		if ($this->thirdparty->tva_intra) {
			$complementaryinfo .= '/30/'.$this->thirdparty->tva_intra;
		}

		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$bankaccount = new Account($this->db);

		// Header
		$s = '';
		$s .= "SPC\n";
		$s .= "0200\n";
		$s .= "1\n";
		// Info Seller ("Compte / Payable à")
		if ($this->fk_account > 0) {
			// Bank BAN if country is LI or CH.  TODO Add a test to check than IBAN start with CH or LI
			$bankaccount->fetch($this->fk_account);
			$s .= $bankaccount->iban."\n";
		} else {
			$s .= "\n";
		}
		if ($bankaccount->id > 0 && getDolGlobalString('PDF_SWISS_QRCODE_USE_OWNER_OF_ACCOUNT_AS_CREDITOR')) {
			// If a bank account is provided and we ask to use it as creditor, we use the bank address
			// TODO In a future, we may always use this address, and if name/address/zip/town/country differs from $mysoc, we can use the address of $mysoc into the final seller field ?
			$s .= "S\n";
			$s .= dol_trunc($bankaccount->proprio, 70, 'right', 'UTF-8', 1)."\n";
			$addresslinearray = explode("\n", $bankaccount->owner_address);
			$s .= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";		// address line 1
			$s .= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";		// address line 2
			/*$s .= dol_trunc($mysoc->zip, 16, 'right', 'UTF-8', 1)."\n";
			$s .= dol_trunc($mysoc->town, 35, 'right', 'UTF-8', 1)."\n";
			$s .= dol_trunc($mysoc->country_code, 2, 'right', 'UTF-8', 1)."\n";*/
		} else {
			$s .= "S\n";
			$s .= dol_trunc($mysoc->name, 70, 'right', 'UTF-8', 1)."\n";
			$addresslinearray = explode("\n", $mysoc->address);
			$s .= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";		// address line 1
			$s .= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";		// address line 2
			$s .= dol_trunc($mysoc->zip, 16, 'right', 'UTF-8', 1)."\n";
			$s .= dol_trunc($mysoc->town, 35, 'right', 'UTF-8', 1)."\n";
			$s .= dol_trunc($mysoc->country_code, 2, 'right', 'UTF-8', 1)."\n";
		}
		// Final seller (Ultimate seller) ("Créancier final" = "En faveur de")
		$s .= "\n";
		$s .= "\n";
		$s .= "\n";
		$s .= "\n";
		$s .= "\n";
		$s .= "\n";
		$s .= "\n";
		// Amount of payment (to do?)
		$s .= price($pricewithtaxstring, 0, 'none', 0, 0, 2)."\n";
		$s .= ($this->multicurrency_code ? $this->multicurrency_code : $conf->currency)."\n";
		// Buyer
		$s .= "S\n";
		$s .= dol_trunc($this->thirdparty->name, 70, 'right', 'UTF-8', 1)."\n";
		$addresslinearray = explode("\n", $this->thirdparty->address);
		$s .= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";		// address line 1
		$s .= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";		// address line 2
		$s .= dol_trunc($this->thirdparty->zip, 16, 'right', 'UTF-8', 1)."\n";
		$s .= dol_trunc($this->thirdparty->town, 35, 'right', 'UTF-8', 1)."\n";
		$s .= dol_trunc($this->thirdparty->country_code, 2, 'right', 'UTF-8', 1)."\n";
		// ID of payment
		$s .= "NON\n";			// NON or QRR
		$s .= "\n";				// QR Code reference if previous field is QRR
		// Free text
		if ($complementaryinfo) {
			$s .= $complementaryinfo."\n";
		} else {
			$s .= "\n";
		}
		$s .= "EPD\n";
		// More text, complementary info
		if ($complementaryinfo) {
			$s .= $complementaryinfo."\n";
		}
		$s .= "\n";
		//var_dump($s);exit;
		return $s;
	}
}



require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 *	Parent class of all other business classes for details of elements (invoices, contracts, proposals, orders, ...)
 */
abstract class CommonInvoiceLine extends CommonObjectLine
{
	/**
	 * Custom label of line. Not used by default.
	 * @deprecated
	 */
	public $label;

	/**
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref; // Product ref (deprecated)
	/**
	 * @deprecated
	 * @see $product_label
	 */
	public $libelle; // Product label (deprecated)

	/**
	 * Type of the product. 0 for product 1 for service
	 * @var int
	 */
	public $product_type = 0;

	/**
	 * Product ref
	 * @var string
	 */
	public $product_ref;

	/**
	 * Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

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
	 * Unit price before taxes
	 * @var float
	 * @deprecated
	 */
	public $price;

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
	 * VAT %  Vat rate can be like "21.30 (CODE)"
	 * @var string|float
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
	 * Local tax 1 type
	 * @var int<0,6>		From 1 to 6, or 0 if not found
	 * @see getLocalTaxesFromRate()
	 */
	public $localtax1_type;

	/**
	 * Local tax 2 type
	 * @var int<0,6>		From 1 to 6, or 0 if not found
	 * @see getLocalTaxesFromRate()
	 */
	public $localtax2_type;

	/**
	 * Percent of discount
	 * @var float
	 */
	public $remise_percent;

	/**
	 * Fixed discount
	 * @var float
	 * @deprecated
	 */
	public $remise;

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

	public $date_start_fill; // If set to 1, when invoice is created from a template invoice, it will also auto set the field date_start at creation
	public $date_end_fill; // If set to 1, when invoice is created from a template invoice, it will also auto set the field date_end at creation

	public $buy_price_ht;
	public $buyprice; // For backward compatibility
	public $pa_ht; // For backward compatibility

	public $marge_tx;
	public $marque_tx;

	/**
	 * List of cumulative options:
	 * Bit 0:	0 for common VAT - 1 if VAT french NPR
	 * Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
	 * @var int
	 */
	public $info_bits = 0;

	/**
	 * List of special options to define line:
	 * 1: shipment cost lines
	 * 2: ecotaxe
	 * 3: ??
	 * id of module: a meaning for the module
	 *  @var int
	 */
	public $special_code = 0;

	/**
	 * @deprecated	Use user_creation_id
	 */
	public $fk_user_author;

	/**
	 * @deprecated	Use user_modification_id
	 */
	public $fk_user_modif;

	public $fk_accounting_account;
}
