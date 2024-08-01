<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2012       Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Marcos García 		 <marcosgdf@gmail.com>
 * Copyright (C) 2015       Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet		 <fmarcet@2byte.es>
 * Copyright (C) 2018       Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Andreu Bisquerra Gaya 	<jove@bisquerra.com>
 * Copyright (C) 2021       OpenDsi					<support@open-dsi.fr>
 * Copyright (C) 2023       Joachim Kueter			<git-jk@bloxera.com>
 * Copyright (C) 2023       Sylvain Legrand			<technique@infras.fr>
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
 *	\file       htdocs/compta/paiement/class/paiement.class.php
 *	\ingroup    invoice
 *	\brief      File of class to manage payments of customers invoices
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';


/**
 *	Class to manage payments of customer invoices
 */
class Paiement extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'payment';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'paiement';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * @var int							Invoice ID
	 */
	public $facid;

	/**
	 * @var int							Company ID
	 */
	public $socid;

	/**
	 * @var int|string
	 */
	public $datepaye;

	/**
	 * @var int|string					same than $datepaye
	 */
	public $date;

	/**
	 * @deprecated
	 * @see $amount, $amounts
	 */
	public $total;

	/**
	 * @deprecated
	 * @see $amount, $amounts
	 */
	public $montant;

	/**
	 * @var float							Total amount of payment (in the main currency)
	 */
	public $amount;

	/**
	 * @var float							Total amount of payment (in the currency of the bank account)
	 */
	public $multicurrency_amount;

	/**
	 * @var float[] array: invoice ID => amount for that invoice (in the main currency)
	 */
	public $amounts = array();

	/**
	 * @var float[] array: invoice ID => amount for that invoice (in the invoice's currency)
	 */
	public $multicurrency_amounts = array();

	/**
	 * @var float[] Multicurrency rate (array: invoice ID => currency rate ("taux" in French) for that invoice)
	 */
	public $multicurrency_tx = array();

	/**
	 * @var string[] Multicurrency code (array: invoice ID => currency code for that invoice)
	 */
	public $multicurrency_code = array();

	/**
	 * @var float							Excess received in TakePOS cash payment
	 */
	public $pos_change = 0.0;

	public $author;

	/**
	 * @var int								ID of mode of payment. Is saved into fields fk_paiement on llx_paiement = id of llx_c_paiement. Can get value from code using ...
	 */
	public $paiementid;

	/**
	 * @var string							Code of mode of payment.
	 */
	public $paiementcode;

	/**
	 * @var string							Type of payment label
	 */
	public $type_label;

	/**
	 * @var string							Type of payment code (seems duplicate with $paiementcode);
	 */
	public $type_code;

	/**
	 * @var string
	 * @deprecated
	 * @see $num_payment
	 */
	public $num_paiement;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var string Id of external payment mode
	 */
	public $ext_payment_id;

	/**
	 * @var string Id of prelevement
	 */
	public $id_prelevement;

	/**
	 * @var string num_prelevement
	 */
	public $num_prelevement;

	/**
	 * @var string Name of external payment mode
	 */
	public $ext_payment_site;

	/**
	 * @var int bank account id of payment
	 * @deprecated
	 * @see $fk_account
	 */
	public $bank_account;

	/**
	 * @var int bank account id of payment
	 */
	public $fk_account;

	/**
	 * @var int id of payment line in bank account
	 */
	public $bank_line;

	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement
	/**
	 * @var int payment id
	 */
	public $fk_paiement; // Type of payment

	/**
	 * @var string payment external reference
	 */
	public $ref_ext;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *    Load payment from database
	 *
	 *    @param	int		$id			Id of payment to get
	 *    @param	string	$ref		Ref of payment to get (currently ref = id but this may change in future)
	 *    @param	int		$fk_bank	Id of bank line associated to payment
	 *    @return   int		            Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '', $fk_bank = 0)
	{
		$sql = 'SELECT p.rowid, p.ref, p.ref_ext, p.datep as dp, p.amount, p.statut, p.ext_payment_id, p.ext_payment_site, p.fk_bank, p.multicurrency_amount,';
		$sql .= ' c.code as type_code, c.libelle as type_label,';
		$sql .= ' p.num_paiement as num_payment, p.note,';
		$sql .= ' b.fk_account';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' WHERE p.entity IN ('.getEntity('invoice').')';
		if ($id > 0) {
			$sql .= ' AND p.rowid = '.((int) $id);
		} elseif ($ref) {
			$sql .= " AND p.ref = '".$this->db->escape($ref)."'";
		} elseif ($fk_bank) {
			$sql .= ' AND p.fk_bank = '.((int) $fk_bank);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref ? $obj->ref : $obj->rowid;
				$this->ref_ext        = $obj->ref_ext;
				$this->date           = $this->db->jdate($obj->dp);
				$this->datepaye       = $this->db->jdate($obj->dp);
				$this->num_payment    = $obj->num_payment;
				$this->montant        = $obj->amount; // deprecated
				$this->amount         = $obj->amount;
				$this->multicurrency_amount = $obj->multicurrency_amount;
				$this->note           = $obj->note;
				$this->note_private   = $obj->note;
				$this->type_label = $obj->type_label;
				$this->type_code      = $obj->type_code;
				$this->statut         = $obj->statut;
				$this->ext_payment_id = $obj->ext_payment_id;
				$this->ext_payment_site = $obj->ext_payment_site;

				$this->bank_account   = $obj->fk_account; // deprecated
				$this->fk_account     = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;

				$this->db->free($resql);
				return 1;
			} else {
				$this->db->free($resql);
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Create payment of invoices into database.
	 *  Use this->amounts to have list of invoices for the payment.
	 *  For payment of a customer invoice, amounts are positive, for payment of credit note, amounts are negative
	 *
	 *  @param	User	  $user                	Object user
	 *  @param  int		  $closepaidinvoices   	1=Also close paid invoices to paid, 0=Do nothing more
	 *  @param  Societe   $thirdparty           Thirdparty
	 *  @return int                 			id of created payment, < 0 if error
	 */
	public function create($user, $closepaidinvoices = 0, $thirdparty = null)
	{
		global $conf, $langs;

		$error = 0;
		$way = $this->getWay();	// 'dolibarr' to use amount, 'customer' to use foreign multicurrency amount

		$now = dol_now();

		// Clean parameters
		$totalamount = 0;
		$totalamount_converted = 0;
		$atleastonepaymentnotnull = 0;

		if ($way == 'dolibarr') {	// Payments were entered into the column of main currency
			$amounts = &$this->amounts;
			$amounts_to_update = &$this->multicurrency_amounts;
		} else {					// Payments were entered into the column of foreign currency
			$amounts = &$this->multicurrency_amounts;
			$amounts_to_update = &$this->amounts;
		}

		$currencyofpayment = '';
		$currencytxofpayment = '';

		foreach ($amounts as $key => $value) {	// How payment is dispatched
			if (empty($value)) {
				continue;
			}
			// $key is id of invoice, $value is amount, $way is a 'dolibarr' if amount is in main currency, 'customer' if in foreign currency
			$value_converted = MultiCurrency::getAmountConversionFromInvoiceRate($key, $value, $way);
			// Add controls of input validity
			if ($value_converted === false) {
				// We failed to find the conversion for one invoice
				$this->error = $langs->trans('FailedToFoundTheConversionRateForInvoice');
				return -1;
			}
			if (empty($currencyofpayment)) {
				$currencyofpayment = isset($this->multicurrency_code[$key]) ? $this->multicurrency_code[$key] : "";
			} elseif ($currencyofpayment != $this->multicurrency_code[$key]) {
				// If we have invoices with different currencies in the payment, we stop here
				$this->error = 'ErrorYouTryToPayInvoicesWithDifferentCurrenciesInSamePayment';
				return -1;
			}
			if (empty($currencytxofpayment)) {
				$currencytxofpayment = isset($this->multicurrency_tx[$key]) ? $this->multicurrency_tx[$key] : "";
			}

			$totalamount_converted += $value_converted;
			$amounts_to_update[$key] = price2num($value_converted, 'MT');

			$newvalue = price2num($value, 'MT');
			$amounts[$key] = $newvalue;
			$totalamount += $newvalue;
			if (!empty($newvalue)) {
				$atleastonepaymentnotnull++;
			}
		}

		if (empty($currencyofpayment)) {	// Should not happen. For the case the multicurrency_code was not saved into invoices
			$currencyofpayment = $conf->currency;
		}

		if (!empty($currencyofpayment)) {
			// We must check that the currency of invoices is the same than the currency of the bank
			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$bankaccount = new Account($this->db);
			$bankaccount->fetch($this->fk_account);
			$bankcurrencycode = empty($bankaccount->currency_code) ? $conf->currency : $bankaccount->currency_code;
			if ($currencyofpayment != $bankcurrencycode && $currencyofpayment != $conf->currency && $bankcurrencycode != $conf->currency) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorYouTryToPayInvoicesInACurrencyFromBankWithAnotherCurrency', $currencyofpayment, $bankcurrencycode);
				return -1;
			}
		}


		$totalamount = (float) price2num($totalamount);
		$totalamount_converted = (float) price2num($totalamount_converted);

		// Check parameters
		if (empty($totalamount) && empty($atleastonepaymentnotnull)) {	 // We accept negative amounts for withdraw reject but not empty arrays
			$this->errors[] = 'TotalAmountEmpty';
			$this->error = $langs->trans('TotalAmountEmpty');
			return -1;
		}

		dol_syslog(get_class($this)."::create insert paiement (closepaidinvoices = ".$closepaidinvoices.")", LOG_DEBUG);

		$this->db->begin();

		$this->ref = $this->getNextNumRef(is_object($thirdparty) ? $thirdparty : '');

		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
		}

		if ($way == 'dolibarr') {
			$total = $totalamount;
			$mtotal = $totalamount_converted;
		} else {
			$total = $totalamount_converted;
			$mtotal = $totalamount;
		}

		$num_payment = $this->num_payment;
		$note = ($this->note_private ? $this->note_private : $this->note);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (entity, ref, ref_ext, datec, datep, amount, multicurrency_amount, fk_paiement, num_paiement, note, ext_payment_id, ext_payment_site, fk_user_creat, pos_change)";
		$sql .= " VALUES (".((int) $conf->entity).", '".$this->db->escape($this->ref)."', '".$this->db->escape($this->ref_ext)."', '".$this->db->idate($now)."', '".$this->db->idate($this->datepaye)."', ".((float) $total).", ".((float) $mtotal).", ".((int) $this->paiementid).", ";
		$sql .= "'".$this->db->escape($num_payment)."', '".$this->db->escape($note)."', ".($this->ext_payment_id ? "'".$this->db->escape($this->ext_payment_id)."'" : "null").", ".($this->ext_payment_site ? "'".$this->db->escape($this->ext_payment_site)."'" : "null").", ".((int) $user->id).", ".((float) $this->pos_change).")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiement');

			// Insert links amount / invoices
			foreach ($this->amounts as $key => $amount) {
				$facid = $key;
				if (is_numeric($amount) && $amount != 0) {
					$amount = price2num($amount);
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount, multicurrency_amount, multicurrency_code, multicurrency_tx)";
					$sql .= " VALUES (".((int) $facid).", ".((int) $this->id).", ".((float) $amount).", ".((float) $this->multicurrency_amounts[$key]).", ".($currencyofpayment ? "'".$this->db->escape($currencyofpayment)."'" : 'NULL').", ".(!empty($this->multicurrency_tx) ? (float) $currencytxofpayment : 1).")";

					dol_syslog(get_class($this).'::create Amount line '.$key.' insert paiement_facture', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$invoice = new Facture($this->db);
						$invoice->fetch($facid);

						// If we want to closed paid invoices
						if ($closepaidinvoices) {
							$paiement = $invoice->getSommePaiement();
							$creditnotes = $invoice->getSumCreditNotesUsed();
							$deposits = $invoice->getSumDepositsUsed();
							$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
							$remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

							//var_dump($invoice->total_ttc.' - '.$paiement.' -'.$creditnotes.' - '.$deposits.' - '.$remaintopay);exit;

							//Invoice types that are eligible for changing status to paid
							$affected_types = array(
								Facture::TYPE_STANDARD,
								Facture::TYPE_REPLACEMENT,
								Facture::TYPE_CREDIT_NOTE,
								Facture::TYPE_DEPOSIT,
								Facture::TYPE_SITUATION
							);

							if (!in_array($invoice->type, $affected_types)) {
								dol_syslog("Invoice ".$facid." is not a standard, nor replacement invoice, nor credit note, nor deposit invoice, nor situation invoice. We do nothing more.");
							} elseif ($remaintopay) {
								// hook to have an option to automatically close a closable invoice with less payment than the total amount (e.g. agreed cash discount terms)
								global $hookmanager;
								$hookmanager->initHooks(array('paymentdao'));
								$parameters = array('facid' => $facid, 'invoice' => $invoice, 'remaintopay' => $remaintopay);
								$action = 'CLOSEPAIDINVOICE';
								$reshook = $hookmanager->executeHooks('createPayment', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
								if ($reshook < 0) {
									$this->errors[] = $hookmanager->error;
									$this->error = $hookmanager->error;
									$error++;
								} elseif ($reshook == 0) {
									dol_syslog("Remain to pay for invoice " . $facid . " not null. We do nothing more.");
								}
								// } else if ($mustwait) dol_syslog("There is ".$mustwait." differed payment to process, we do nothing more.");
							} else {
								// If invoice is a down payment, we also convert down payment to discount
								if ($invoice->type == Facture::TYPE_DEPOSIT) {
									$amount_ht = $amount_tva = $amount_ttc = array();
									$multicurrency_amount_ht = $multicurrency_amount_tva = $multicurrency_amount_ttc = array();

									// Insert one discount by VAT rate category
									$discount = new DiscountAbsolute($this->db);
									$discount->fetch(0, $invoice->id);
									if (empty($discount->id)) {	// If the invoice was not yet converted into a discount (this may have been done manually before we come here)
										$discount->description = '(DEPOSIT)';
										$discount->fk_soc = $invoice->socid;
										$discount->socid = $invoice->socid;
										$discount->fk_facture_source = $invoice->id;

										// Loop on each vat rate
										$i = 0;
										foreach ($invoice->lines as $line) {
											if ($line->total_ht != 0) {    // no need to create discount if amount is null
												$amount_ht[$line->tva_tx] += $line->total_ht;
												$amount_tva[$line->tva_tx] += $line->total_tva;
												$amount_ttc[$line->tva_tx] += $line->total_ttc;
												$multicurrency_amount_ht[$line->tva_tx] += $line->multicurrency_total_ht;
												$multicurrency_amount_tva[$line->tva_tx] += $line->multicurrency_total_tva;
												$multicurrency_amount_ttc[$line->tva_tx] += $line->multicurrency_total_ttc;
												$i++;
											}
										}

										foreach ($amount_ht as $tva_tx => $xxx) {
											$discount->amount_ht = abs($amount_ht[$tva_tx]);
											$discount->amount_tva = abs($amount_tva[$tva_tx]);
											$discount->amount_ttc = abs($amount_ttc[$tva_tx]);
											$discount->multicurrency_amount_ht = abs($multicurrency_amount_ht[$tva_tx]);
											$discount->multicurrency_amount_tva = abs($multicurrency_amount_tva[$tva_tx]);
											$discount->multicurrency_amount_ttc = abs($multicurrency_amount_ttc[$tva_tx]);
											$discount->tva_tx = abs($tva_tx);

											$result = $discount->create($user);
											if ($result < 0) {
												$error++;
												break;
											}
										}
									}

									if ($error) {
										$this->error = $discount->error;
										$this->errors = $discount->errors;
										$error++;
									}
								}

								// Set invoice to paid
								if (!$error) {
									$invoice->context['actionmsgmore'] = 'Invoice set to paid by the payment->create() of payment '.$this->ref.' because the remain to pay is 0';

									$result = $invoice->setPaid($user, '', '');
									if ($result < 0) {
										$this->error = $invoice->error;
										$this->errors = $invoice->errors;
										$error++;
									}
								}
							}
						}

						// Regenerate documents of invoices
						if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
							dol_syslog(get_class($this).'::create Regenerate the document after inserting payment for thirdparty default_lang='.(is_object($invoice->thirdparty) ? $invoice->thirdparty->default_lang : 'null'), LOG_DEBUG);

							$newlang = '';
							$outputlangs = $langs;
							if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
								$invoice->fetch_thirdparty();
								$newlang = $invoice->thirdparty->default_lang;
							}
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}

							$hidedetails = getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0;
							$hidedesc = getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0;
							$hideref = getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0;

							$ret = $invoice->fetch($facid); // Reload to get new records

							$result = $invoice->generateDocument($invoice->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

							dol_syslog(get_class($this).'::create Regenerate end result='.$result, LOG_DEBUG);

							if ($result < 0) {
								$this->error = $invoice->error;
								$this->errors = $invoice->errors;
								$error++;
							}
						}
					} else {
						$this->error = $this->db->lasterror();
						$error++;
					}
				} else {
					dol_syslog(get_class($this).'::Create Amount line '.$key.' not a number. We discard it.');
				}
			}

			dol_syslog(get_class($this).'::create Now we call the triggers if no error (error = '.$error.')', LOG_DEBUG);

			if (!$error) {    // All payments into $this->amounts were recorded without errors
				// Appel des triggers
				$result = $this->call_trigger('PAYMENT_CUSTOMER_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// Fin appel triggers
			}
		} else {
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			$this->amount = $total;
			$this->total = $total; // deprecated
			$this->multicurrency_amount = $mtotal;
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * Delete a payment and generated links into account
	 *  - Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *  - Si le paiement porte sur au moins une facture a "payee", on refuse
	 *
	 * @param	User	$user			User making the deletion
	 * @param	int		$notrigger		No trigger
	 * @return 	int     				Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture classee
		// Si c'est le cas, on refuse la suppression
		$billsarray = $this->getBillsArray('f.fk_statut > 1');
		if (is_array($billsarray)) {
			if (count($billsarray)) {
				$this->error = "ErrorDeletePaymentLinkedToAClosedInvoiceNotPossible";
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			return -2;
		}

		// Delete bank urls. If payment is on a conciliated line, return error.
		if ($bank_line_id > 0) {
			$accline = new AccountLine($this->db);

			$result = $accline->fetch($bank_line_id);
			if ($result == 0) {
				$accline->id = $accline->rowid = $bank_line_id; // If not found, we set artificially rowid to allow delete of llx_bank_url
			}

			// Delete bank account url lines linked to payment
			$result = $accline->delete_urls($user);
			if ($result < 0) {
				$this->error = $accline->error;
				$this->db->rollback();
				return -3;
			}

			// Delete bank account lines linked to payment
			$result = $accline->delete($user);
			if ($result < 0) {
				$this->error = $accline->error;
				$this->db->rollback();
				return -4;
			}
		}

		if (!$notrigger) {
			// Call triggers
			$result = $this->call_trigger('PAYMENT_CUSTOMER_DELETE', $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
			// End call triggers
		}

		// Delete payment (into paiement_facture and paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement_facture';
		$sql .= ' WHERE fk_paiement = '.((int) $this->id);
		dol_syslog($sql);
		$result = $this->db->query($sql);
		if ($result) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement';
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog($sql);
			$result = $this->db->query($sql);
			if (!$result) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -3;
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error;
			$this->db->rollback();
			return -5;
		}
	}


	/**
	 *      Add a record into bank for payment + links between this bank record and sources of payment.
	 *      All payment properties (this->amount, this->amounts, ...) must have been set first like after a call to create().
	 *
	 *      @param	User	$user               Object of user making payment
	 *      @param  string	$mode               'payment', 'payment_supplier'
	 *      @param  string	$label              Label to use in bank record
	 *      @param  int		$accountid          Id of bank account to do link with
	 *      @param  string	$emetteur_nom       Name of transmitter
	 *      @param  string	$emetteur_banque    Name of bank
	 *      @param	int		$notrigger			No trigger
	 *  	@param	string	$accountancycode	When we record a free bank entry, we must provide accounting account if accountancy module is on.
	 *      @param	string	$addbankurl			'direct-debit' or 'credit-transfer': Add another entry into bank_url.
	 *      @return int                 		Return integer <0 if KO, bank_line_id if OK
	 */
	public function addPaymentToBank($user, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque, $notrigger = 0, $accountancycode = '', $addbankurl = '')
	{
		global $conf, $user;

		$error = 0;
		$bank_line_id = 0;

		if (isModEnabled("bank")) {
			if ($accountid <= 0) {
				$this->error = 'Bad value for parameter accountid='.$accountid;
				dol_syslog(get_class($this).'::addPaymentToBank '.$this->error, LOG_ERR);
				return -1;
			}

			$this->fk_account = $accountid;

			dol_syslog("addPaymentToBank ".$user->id.", ".$mode.", ".$label.", ".$this->fk_account.", ".$emetteur_nom.", ".$emetteur_banque);

			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$acc = new Account($this->db);
			$result = $acc->fetch($this->fk_account);
			if ($result < 0) {
				$this->error = $acc->error;
				$this->errors = $acc->errors;
				$error++;
				return -1;
			}

			$this->db->begin();

			$totalamount = $this->amount;
			$totalamount_main_currency = null;
			if (empty($totalamount)) {
				$totalamount = $this->total; // For backward compatibility
			}

			// if dolibarr currency != bank currency then we received an amount in customer currency (currently I don't manage the case : my currency is USD, the customer currency is EUR and he paid me in GBP. Seems no sense for me)
			if (isModEnabled('multicurrency') && $conf->currency != $acc->currency_code) {
				$totalamount = $this->multicurrency_amount;		// We will insert into llx_bank.amount in foreign currency
				$totalamount_main_currency = $this->amount;		// We will also save the amount in main currency into column llx_bank.amount_main_currency
			}

			if ($mode == 'payment_supplier') {
				$totalamount = -$totalamount;
				if (isset($totalamount_main_currency)) {
					$totalamount_main_currency = -$totalamount_main_currency;
				}
			}

			// Insert payment into llx_bank
			$bank_line_id = $acc->addline(
				$this->datepaye,
				$this->paiementcode ? $this->paiementcode : $this->paiementid, // Payment mode code ('CB', 'CHQ' or 'VIR' for example). Use payment id if not defined for backward compatibility.
				$label,
				$totalamount, // Sign must be positive when we receive money (customer payment), negative when you give money (supplier invoice or credit note)
				$this->num_payment,
				'',
				$user,
				$emetteur_nom,
				$emetteur_banque,
				$accountancycode,
				null,
				'',
				$totalamount_main_currency
			);

			// Mise a jour fk_bank dans llx_paiement
			// On connait ainsi le paiement qui a genere l'ecriture bancaire
			if ($bank_line_id > 0) {
				$result = $this->update_fk_bank($bank_line_id);
				if ($result <= 0) {
					$error++;
					dol_print_error($this->db);
				}

				// Add link 'payment', 'payment_supplier' in bank_url between payment and bank transaction
				if (!$error) {
					$url = '';
					if ($mode == 'payment') {
						$url = DOL_URL_ROOT.'/compta/paiement/card.php?id=';
					}
					if ($mode == 'payment_supplier') {
						$url = DOL_URL_ROOT.'/fourn/paiement/card.php?id=';
					}
					if ($url) {
						$result = $acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
						if ($result <= 0) {
							$error++;
							dol_print_error($this->db);
						}
					}
				}

				// Add link 'company' in bank_url between invoice and bank transaction (for each invoice concerned by payment)
				if (!$error) {
					$linkaddedforthirdparty = array();
					foreach ($this->amounts as $key => $value) {  // We should have invoices always for same third party but we loop in case of.
						if ($mode == 'payment') {
							$fac = new Facture($this->db);
							$fac->fetch($key);
							$fac->fetch_thirdparty();
							if (!in_array($fac->thirdparty->id, $linkaddedforthirdparty)) { // Not yet done for this thirdparty
								$result = $acc->add_url_line(
									$bank_line_id,
									$fac->thirdparty->id,
									DOL_URL_ROOT.'/comm/card.php?socid=',
									$fac->thirdparty->name,
									'company'
								);
								if ($result <= 0) {
									dol_syslog(get_class($this).'::addPaymentToBank '.$this->db->lasterror());
								}
								$linkaddedforthirdparty[$fac->thirdparty->id] = $fac->thirdparty->id; // Mark as done for this thirdparty
							}
						}
						if ($mode == 'payment_supplier') {
							$fac = new FactureFournisseur($this->db);
							$fac->fetch($key);
							$fac->fetch_thirdparty();
							if (!in_array($fac->thirdparty->id, $linkaddedforthirdparty)) { // Not yet done for this thirdparty
								$result = $acc->add_url_line(
									$bank_line_id,
									$fac->thirdparty->id,
									DOL_URL_ROOT.'/fourn/card.php?socid=',
									$fac->thirdparty->name,
									'company'
								);
								if ($result <= 0) {
									dol_syslog(get_class($this).'::addPaymentToBank '.$this->db->lasterror());
								}
								$linkaddedforthirdparty[$fac->thirdparty->id] = $fac->thirdparty->id; // Mark as done for this thirdparty
							}
						}
					}
				}

				// Add a link to the Direct Debit ('direct-debit') or Credit transfer ('credit-transfer') file in bank_url
				if (!$error && $addbankurl && in_array($addbankurl, array('direct-debit', 'credit-transfer'))) {
					$result = $acc->add_url_line(
						$bank_line_id,
						$this->id_prelevement,
						DOL_URL_ROOT.'/compta/prelevement/card.php?id=',
						$this->num_payment,
						$addbankurl
					);
				}

				// Add link to the Direct Debit if invoice refused ('InvoiceRefused') in bank_url
				if (!$error && $label == '(InvoiceRefused)') {
					$result = $acc->add_url_line(
						$bank_line_id,
						$this->id_prelevement,
						DOL_URL_ROOT.'/compta/prelevement/card.php?id=',
						$this->num_prelevement,
						'withdraw'
					);
				}

				if (!$error && !$notrigger) {
					// Appel des triggers
					$result = $this->call_trigger('PAYMENT_ADD_TO_BANK', $user);
					if ($result < 0) {
						$error++;
					}
					// Fin appel triggers
				}
			} else {
				$this->error = $acc->error;
				$this->errors = $acc->errors;
				$error++;
			}

			if (!$error) {
				$this->db->commit();
			} else {
				$this->db->rollback();
			}
		}

		if (!$error) {
			return $bank_line_id;
		} else {
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Mise a jour du lien entre le paiement et la ligne generee dans llx_bank
	 *
	 *      @param	int		$id_bank    Id compte bancaire
	 *      @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' set fk_bank = '.((int) $id_bank);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this).'::update_fk_bank', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this).'::update_fk_bank '.$this->error);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Updates the payment date
	 *
	 *  @param	int	$date   New date
	 *  @return int					Return integer <0 if KO, 0 if OK
	 */
	public function update_date($date)
	{
		// phpcs:enable
		$error = 0;

		if (!empty($date) && $this->statut != 1) {
			$this->db->begin();

			dol_syslog(get_class($this)."::update_date with date = ".$date, LOG_DEBUG);

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET datep = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = 'Error -1 '.$this->db->error();
			}

			$type = $this->element;

			$sql = "UPDATE ".MAIN_DB_PREFIX.'bank';
			$sql .= " SET dateo = '".$this->db->idate($date)."', datev = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid IN (SELECT fk_bank FROM ".MAIN_DB_PREFIX."bank_url WHERE type = '".$this->db->escape($type)."' AND url_id = ".((int) $this->id).")";
			$sql .= " AND rappro = 0";

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = 'Error -1 '.$this->db->error();
			}

			if (!$error) {
			}

			if (!$error) {
				$this->datepaye = $date;
				$this->date = $date;

				$this->db->commit();
				return 0;
			} else {
				$this->db->rollback();
				return -2;
			}
		}
		return -1; //no date given or already validated
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Updates the payment number
	 *
	 *  @param	string	$num_payment		New num
	 *  @return int							Return integer <0 if KO, 0 if OK
	 */
	public function update_num($num_payment)
	{
		// phpcs:enable
		if (!empty($num_payment) && $this->statut != 1) {
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET num_paiement = '".$this->db->escape($num_payment)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::update_num", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$this->num_payment = $this->db->escape($num_payment);
				return 0;
			} else {
				$this->error = 'Error -1 '.$this->db->error();
				return -2;
			}
		}
		return -1; //no num given or already validated
	}

	/**
	 * Validate payment
	 *
	 * @param	User|null	$user		User making validation
	 * @return	int     				Return integer <0 if KO, >0 if OK
	 * @deprecated
	 */
	public function valide(User $user = null)
	{
		return $this->validate($user);
	}

	/**
	 * Validate payment
	 *
	 * @param	User|null	$user		User making validation
	 * @return	int     				Return integer <0 if KO, >0 if OK
	 */
	public function validate(User $user = null)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 1 WHERE rowid = '.((int) $this->id);

		dol_syslog(get_class($this).'::valide', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this).'::valide '.$this->error);
			return -1;
		}
	}

	/**
	 * Reject payment
	 *
	 * @param	User|null	$user		User making reject
	 * @return  int     				Return integer <0 if KO, >0 if OK
	 */
	public function reject(User $user = null)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 2 WHERE rowid = '.((int) $this->id);

		dol_syslog(get_class($this).'::reject', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this).'::reject '.$this->error);
			return -1;
		}
	}

	/**
	 * Information sur l'objet
	 *
	 * @param   int     $id      id du paiement don't il faut afficher les infos
	 * @return  void
	 */
	public function info($id)
	{
		$sql = 'SELECT p.rowid, p.datec, p.fk_user_creat, p.fk_user_modif, p.tms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p';
		$sql .= ' WHERE p.rowid = '.((int) $id);

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return list of invoices the payment is related to.
	 *
	 *  @param	string		$filter         Filter
	 *  @return int|array					Return integer <0 if KO or array of invoice id
	 *  @see getAmountsArray()
	 */
	public function getBillsArray($filter = '')
	{
		$sql = 'SELECT pf.fk_facture';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'facture as f'; // We keep link on invoice to allow use of some filters on invoice
		$sql .= ' WHERE pf.fk_facture = f.rowid AND pf.fk_paiement = '.((int) $this->id);
		if ($filter) {
			$sql .= ' AND '.$filter;
		}
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $this->db->num_rows($resql);
			$billsarray = array();

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$billsarray[$i] = $obj->fk_facture;
				$i++;
			}

			return $billsarray;
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error.' -', LOG_DEBUG);
			return -1;
		}
	}

	/**
	 *  Return list of amounts of payments.
	 *
	 *  @return int|array					Array of amount of payments
	 *  @see getBillsArray()
	 */
	public function getAmountsArray()
	{
		$sql = 'SELECT pf.fk_facture, pf.amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
		$sql .= ' WHERE pf.fk_paiement = '.((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $this->db->num_rows($resql);
			$amounts = array();

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$amounts[$obj->fk_facture] = $obj->amount;
				$i++;
			}

			return $amounts;
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this).'::getAmountsArray Error '.$this->error.' -', LOG_DEBUG);
			return -1;
		}
	}

	/**
	 *      Return next reference of customer invoice not already used (or last reference)
	 *      according to numbering module defined into constant FACTURE_ADDON
	 *
	 *      @param	   Societe		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
	public function getNextNumRef($soc, $mode = 'next')
	{
		global $conf, $db, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (!getDolGlobalString('PAYMENT_ADDON')) {
			$conf->global->PAYMENT_ADDON = 'mod_payment_cicada';
		} elseif (getDolGlobalString('PAYMENT_ADDON') == 'ant') {
			$conf->global->PAYMENT_ADDON = 'mod_payment_ant';
		} elseif (getDolGlobalString('PAYMENT_ADDON') == 'cicada') {
			$conf->global->PAYMENT_ADDON = 'mod_payment_cicada';
		}

		if (getDolGlobalString('PAYMENT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('PAYMENT_ADDON') . ".php";
			$classname = getDolGlobalString('PAYMENT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/payment/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file)) {
					$mybool = (include_once $dir.$file) || $mybool;
				}
			}

			// For compatibility
			if (!$mybool) {
				$file = getDolGlobalString('PAYMENT_ADDON') . ".php";
				$classname = "mod_payment_" . getDolGlobalString('PAYMENT_ADDON');
				$classname = preg_replace('/\-.*$/', '', $classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot) {
					$dir = $dirroot."/core/modules/payment/";

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool = (include_once $dir.$file) || $mybool;
					}
				}
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc, $this);

			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				dol_print_error($db, "Payment::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Invoice"));
			return "";
		}
	}

	/**
	 * 	get the right way of payment
	 *
	 * 	@return 	string 	'dolibarr' if standard comportment or paid in main currency, 'customer' if payment received from multicurrency inputs
	 */
	public function getWay()
	{
		global $conf;

		$way = 'dolibarr';
		if (isModEnabled('multicurrency')) {
			foreach ($this->multicurrency_amounts as $value) {
				if (!empty($value)) { // one value found then payment is in invoice currency
					$way = 'customer';
					break;
				}
			}
		}

		return $way;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	int
	 */
	public function initAsSpecimen($option = '')
	{
		global $user, $langs, $conf;

		$now = dol_now();
		$arraynow = dol_getdate($now);
		$nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Initialize parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->facid = 1;
		$this->datepaye = $nownotime;

		return 1;
	}


	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			Sur quoi pointe le lien
	 *  @param  string  $mode           'withlistofinvoices'=Include list of invoices into tooltip
	 *  @param	int  	$notooltip		1=Disable tooltip
	 *  @param	string	$morecss		Add more CSS
	 *	@return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $mode = 'withlistofinvoices', $notooltip = 0, $morecss = '')
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Payment").'</u><br>';
		$label .= '<strong>'.$langs->trans("Ref").':</strong> '.$this->ref;
		$dateofpayment = ($this->datepaye ? $this->datepaye : $this->date);
		if ($dateofpayment) {
			$label .= '<br><strong>'.$langs->trans("Date").':</strong> ';
			$tmparray = dol_getdate($dateofpayment);
			if ($tmparray['seconds'] == 0 && $tmparray['minutes'] == 0 && ($tmparray['hours'] == 0 || $tmparray['hours'] == 12)) {	// We set hours to 0:00 or 12:00 because we don't know it
				$label .= dol_print_date($dateofpayment, 'day');
			} else {	// Hours was set to real date of payment (special case for POS for example)
				$label .= dol_print_date($dateofpayment, 'dayhour', 'tzuser');
			}
		}
		if ($this->amount) {
			$label .= '<br><strong>'.$langs->trans("Amount").':</strong> '.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency);
		}
		if ($mode == 'withlistofinvoices') {
			$arraybill = $this->getBillsArray();
			if (is_array($arraybill) && count($arraybill) > 0) {
				include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$facturestatic = new Facture($this->db);
				foreach ($arraybill as $billid) {
					$facturestatic->fetch($billid);
					$label .= '<br> '.$facturestatic->getNomUrl(1, '', 0, 0, '', 1).' '.$facturestatic->getLibStatut(2, 1);
				}
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("Payment");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$url = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$this->id;

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ($this->ref ? $this->ref : $this->id);
		}
		$result .= $linkend;
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs; // TODO Renvoyer le libelle anglais et faire traduction a affichage

		$langs->load('compta');
		/*if ($mode == 0)
		 {
		 if ($status == 0) return $langs->trans('ToValidate');
		 if ($status == 1) return $langs->trans('Validated');
		 }
		 if ($mode == 1)
		 {
		 if ($status == 0) return $langs->trans('ToValidate');
		 if ($status == 1) return $langs->trans('Validated');
		 }
		 if ($mode == 2)
		 {
		 if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
		 if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		 }
		 if ($mode == 3)
		 {
		 if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
		 if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
		 }
		 if ($mode == 4)
		 {
		 if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
		 if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		 }
		 if ($mode == 5)
		 {
		 if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
		 if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
		 }
		 if ($mode == 6)
		 {
		 if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
		 if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
		 }*/
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load the third party of object, from id into this->thirdparty.
	 *  For payments, take the thirdparty linked to the first invoice found. This is enough because payments are done on invoices of the same thirdparty.
	 *
	 *	@param		int		$force_thirdparty_id	Force thirdparty id
	 *	@return		int								Return integer <0 if KO, >0 if OK
	 */
	public function fetch_thirdparty($force_thirdparty_id = 0)
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

		if (empty($force_thirdparty_id)) {
			$billsarray = $this->getBillsArray(); // From payment, the fk_soc isn't available, we should load the first supplier invoice to get him
			if (!empty($billsarray)) {
				$invoice = new Facture($this->db);
				if ($invoice->fetch($billsarray[0]) > 0) {
					$force_thirdparty_id = $invoice->socid;
				}
			}
		}

		return parent::fetch_thirdparty($force_thirdparty_id);
	}


	/**
	 *  Return if payment is reconciled
	 *
	 *  @return     boolean     True if payment is reconciled
	 */
	public function isReconciled()
	{
		$accountline = new AccountLine($this->db);
		$accountline->fetch($this->bank_line);
		return $accountline->rappro ? true : false;
	}
}
