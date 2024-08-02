<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin          <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent          <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2018      Nicolas ZABOURI	  <info@inovea-conseil.com>
 * Copyright (C) 2018       Frédéric France         <frederic.francenetlogic.fr>
 * Copyright (C) 2023      Joachim Kueter		  <git-jk@bloxera.com>
 * Copyright (C) 2023      Sylvain Legrand		  <technique@infras.fr>
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
 *		\file       htdocs/fourn/class/paiementfourn.class.php
 *		\ingroup    fournisseur, facture
 *		\brief      File of class to manage payments of suppliers invoices
 */
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage payments for supplier invoices
 */
class PaiementFourn extends Paiement
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'payment_supplier';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'paiementfourn';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	public $statut; //Status of payment. 0 = unvalidated; 1 = validated
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement

	/**
	 * Label of payment type
	 * @var string
	 */
	public $type_label;

	/**
	 * Code of Payment type
	 * @var string
	 */
	public $type_code;

	/**
	 * @var string Id of prelevement
	 */
	public $id_prelevement;

	/**
	 * @var string num_prelevement
	 */
	public $num_prelevement;


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
	 *	Load payment object
	 *
	 *	@param	int		$id         Id if payment to get
	 *  @param	string	$ref		Ref of payment to get
	 *  @param	int		$fk_bank	Id of bank line associated to payment
	 *  @return int		            Return integer <0 if KO, -2 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '', $fk_bank = 0)
	{
		$error = 0;

		$sql = 'SELECT p.rowid, p.ref, p.entity, p.datep as dp, p.amount, p.statut, p.fk_bank, p.multicurrency_amount,';
		$sql .= ' c.code as payment_code, c.libelle as payment_type,';
		$sql .= ' p.num_paiement as num_payment, p.note, b.fk_account, p.fk_paiement';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' WHERE p.entity IN ('.getEntity('facture_fourn').')';
		if ($id > 0) {
			$sql .= ' AND p.rowid = '.((int) $id);
		} elseif ($ref) {
			$sql .= " AND p.ref = '".$this->db->escape($ref)."'";
		} elseif ($fk_bank > 0) {
			$sql .= ' AND p.fk_bank = '.((int) $fk_bank);
		}
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref;
				$this->entity         = $obj->entity;
				$this->date           = $this->db->jdate($obj->dp);
				$this->datepaye       = $this->db->jdate($obj->dp);
				$this->num_payment    = $obj->num_payment;
				$this->bank_account   = $obj->fk_account;
				$this->fk_account     = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
				$this->montant        = $obj->amount; // deprecated
				$this->amount         = $obj->amount;
				$this->multicurrency_amount = $obj->multicurrency_amount;
				$this->note                 = $obj->note;
				$this->note_private         = $obj->note;
				$this->type_code            = $obj->payment_code;
				$this->type_label           = $obj->payment_type;
				$this->fk_paiement           = $obj->fk_paiement;
				$this->statut               = $obj->statut;

				$error = 1;
			} else {
				$error = -2; // TODO Use 0 instead
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
			$error = -1;
		}
		return $error;
	}

	/**
	 *	Create payment in database
	 *
	 *	@param		User	   $user        		Object of creating user
	 *	@param		int		   $closepaidinvoices   1=Also close paid invoices to paid, 0=Do nothing more
	 *  @param      Societe    $thirdparty          Thirdparty
	 *	@return     int         					id of created payment, < 0 if error
	 */
	public function create($user, $closepaidinvoices = 0, $thirdparty = null)
	{
		global $langs, $conf;

		$error = 0;
		$way = $this->getWay();

		$now = dol_now();

		// Clean parameters
		$totalamount = 0;
		$totalamount_converted = 0;
		$atleastonepaymentnotnull = 0;

		if ($way == 'dolibarr') {
			$amounts = &$this->amounts;
			$amounts_to_update = &$this->multicurrency_amounts;
		} else {
			$amounts = &$this->multicurrency_amounts;
			$amounts_to_update = &$this->amounts;
		}

		$currencyofpayment = '';
		$currencytxofpayment = '';

		foreach ($amounts as $key => $value) {
			if (empty($value)) {
				continue;
			}
			// $key is id of invoice, $value is amount, $way is a 'dolibarr' if amount is in main currency, 'customer' if in foreign currency
			$value_converted = MultiCurrency::getAmountConversionFromInvoiceRate($key, $value ? $value : 0, $way, 'facture_fourn');
			// Add controls of input validity
			if ($value_converted === false) {
				// We failed to find the conversion for one invoice
				$this->error = $langs->trans('FailedToFoundTheConversionRateForInvoice');
				return -1;
			}
			if (empty($currencyofpayment)) {
				$currencyofpayment = $this->multicurrency_code[$key];
			}
			if ($currencyofpayment != $this->multicurrency_code[$key]) {
				// If we have invoices with different currencies in the payment, we stop here
				$this->error = 'ErrorYouTryToPayInvoicesWithDifferentCurrenciesInSamePayment';
				return -1;
			}
			if (empty($currencytxofpayment)) {
				$currencytxofpayment = $this->multicurrency_tx[$key];
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

		if (!empty($currencyofpayment)) {
			// We must check that the currency of invoices is the same than the currency of the bank
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

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$this->db->begin();

		if ($totalamount != 0) { // On accepte les montants negatifs
			$ref = $this->getNextNumRef(is_object($thirdparty) ? $thirdparty : '');

			if ($way == 'dolibarr') {
				$total = $totalamount;
				$mtotal = $totalamount_converted; // Maybe use price2num with MT for the converted value
			} else {
				$total = $totalamount_converted; // Maybe use price2num with MT for the converted value
				$mtotal = $totalamount;
			}

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn (';
			$sql .= 'ref, entity, datec, datep, amount, multicurrency_amount, fk_paiement, num_paiement, note, fk_user_author, fk_bank)';
			$sql .= " VALUES ('".$this->db->escape($ref)."', ".((int) $conf->entity).", '".$this->db->idate($now)."',";
			$sql .= " '".$this->db->idate($this->datepaye)."', ".((float) $total).", ".((float) $mtotal).", ".((int) $this->paiementid).", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note_private)."', ".((int) $user->id).", 0)";

			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiementfourn');

				// Insere tableau des montants / factures
				foreach ($this->amounts as $key => $amount) {
					$facid = $key;
					if (is_numeric($amount) && $amount != 0) {
						$amount = price2num($amount);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn (fk_facturefourn, fk_paiementfourn, amount, multicurrency_amount, multicurrency_code, multicurrency_tx)';
						$sql .= " VALUES (".((int) $facid).", ".((int) $this->id).", ".((float) $amount).', '.((float) $this->multicurrency_amounts[$key]).', '.($currencyofpayment ? "'".$this->db->escape($currencyofpayment)."'" : 'NULL').', '.(!empty($currencytxofpayment) ? (float) $currencytxofpayment : 1).')';
						$resql = $this->db->query($sql);
						if ($resql) {
							$invoice = new FactureFournisseur($this->db);
							$invoice->fetch($facid);

							// If we want to closed paid invoices
							if ($closepaidinvoices) {
								$paiement = $invoice->getSommePaiement();
								$creditnotes = $invoice->getSumCreditNotesUsed();
								//$creditnotes = 0;
								$deposits = $invoice->getSumDepositsUsed();
								//$deposits = 0;
								$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
								$remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');
								if ($remaintopay == 0) {
									// If invoice is a down payment, we also convert down payment to discount
									if ($invoice->type == FactureFournisseur::TYPE_DEPOSIT) {
										$amount_ht = $amount_tva = $amount_ttc = array();
										$multicurrency_amount_ht = $multicurrency_amount_tva = $multicurrency_amount_ttc = array();

										// Insert one discount by VAT rate category
										require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
										$discount = new DiscountAbsolute($this->db);
										$discount->fetch('', 0, $invoice->id);
										if (empty($discount->id)) {    // If the invoice was not yet converted into a discount (this may have been done manually before we come here)
											$discount->discount_type = 1; // Supplier discount
											$discount->description = '(DEPOSIT)';
											$discount->fk_soc = $invoice->socid;
											$discount->socid = $invoice->socid;
											$discount->fk_invoice_supplier_source = $invoice->id;

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
											setEventMessages($discount->error, $discount->errors, 'errors');
											$error++;
										}
									}

									// Set invoice to paid
									if (!$error) {
										$result = $invoice->setPaid($user, '', '');
										if ($result < 0) {
											$this->error = $invoice->error;
											$error++;
										}
									}
								} else {
									// hook to have an option to automatically close a closable invoice with less payment than the total amount (e.g. agreed cash discount terms)
									global $hookmanager;
									$hookmanager->initHooks(array('payment_supplierdao'));
									$parameters = array('facid' => $facid, 'invoice' => $invoice, 'remaintopay' => $remaintopay);
									$action = 'CLOSEPAIDSUPPLIERINVOICE';
									$reshook = $hookmanager->executeHooks('createPayment', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
									if ($reshook < 0) {
										$this->error = $hookmanager->error;
										$error++;
									} elseif ($reshook == 0) {
										dol_syslog("Remain to pay for invoice " . $facid . " not null. We do nothing more.");
									}
								}
							}

							// Regenerate documents of invoices
							if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
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
								$ret = $invoice->fetch($facid); // Reload to get new records
								$result = $invoice->generateDocument($invoice->model_pdf, $outputlangs);
								if ($result < 0) {
									setEventMessages($invoice->error, $invoice->errors, 'errors');
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

				if (!$error) {
					// Call trigger
					$result = $this->call_trigger('PAYMENT_SUPPLIER_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			} else {
				$this->error = $this->db->lasterror();
				$error++;
			}
		} else {
			$this->error = "ErrorTotalIsNull";
			dol_syslog('PaiementFourn::Create Error '.$this->error, LOG_ERR);
			$error++;
		}

		if ($totalamount != 0 && $error == 0) { // On accepte les montants negatifs
			$this->amount = $total;
			$this->total = $total;
			$this->multicurrency_amount = $mtotal;
			$this->db->commit();
			dol_syslog('PaiementFourn::Create Ok Total = '.$this->amount.', Total currency = '.$this->multicurrency_amount);
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Delete a payment and lines generated into accounts
	 *	Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *	Si le paiement porte sur au moins une facture a "payee", on refuse
	 *	@TODO Add User $user as first param
	 *  @param		User	$user			User making the deletion
	 *	@param		int		$notrigger		No trigger
	 *	@return     int     				Return integer <0 si ko, >0 si ok
	 */
	public function delete($user = null, $notrigger = 0)
	{
		if (empty($user)) {
			global $user;
		}

		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture a l'etat payee
		// Si c'est le cas, on refuse la suppression
		$billsarray = $this->getBillsArray('paye=1');
		if (is_array($billsarray)) {
			if (count($billsarray)) {
				$this->error = "ErrorCantDeletePaymentSharedWithPayedInvoice";
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			return -2;
		}

		// Verifier si paiement ne porte pas sur ecriture bancaire rapprochee
		// Si c'est le cas, on refuse le delete
		if ($bank_line_id) {
			$accline = new AccountLine($this->db);
			$accline->fetch($bank_line_id);
			if ($accline->rappro) {
				$this->error = "ErrorCantDeletePaymentReconciliated";
				$this->db->rollback();
				return -3;
			}
		}

		// Efface la ligne de paiement (dans paiement_facture et paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
		$sql .= ' WHERE fk_paiementfourn = '.((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiementfourn';
			$sql .= " WHERE rowid = ".((int) $this->id);
			$result = $this->db->query($sql);
			if (!$result) {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -3;
			}

			// Supprimer l'ecriture bancaire si paiement lie a ecriture
			if ($bank_line_id) {
				$accline = new AccountLine($this->db);
				$result = $accline->fetch($bank_line_id);
				if ($result > 0) { // If result = 0, record not found, we don't try to delete
					$result = $accline->delete($user);
				}
				if ($result < 0) {
					$this->error = $accline->error;
					$this->db->rollback();
					return -4;
				}
			}

			if (!$notrigger) {
				// Appel des triggers
				$result = $this->call_trigger('PAYMENT_SUPPLIER_DELETE', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// Fin appel triggers
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
	 *	Information on object
	 *
	 *	@param	int		$id      Id du paiement don't il faut afficher les infos
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, datec, fk_user_author as fk_user_creat, tms as fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Return list of supplier invoices the payment point to
	 *
	 *	@param      string	$filter         SQL filter. Warning: This value must not come from a user input.
	 *	@return     array|int           		Array of supplier invoice id | <0 si ko
	 */
	public function getBillsArray($filter = '')
	{
		$sql = 'SELECT fk_facturefourn';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql .= ' WHERE pf.fk_facturefourn = f.rowid AND fk_paiementfourn = '.((int) $this->id);
		if ($filter) {
			$sql .= " AND ".$filter;
		}

		dol_syslog(get_class($this).'::getBillsArray', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $this->db->num_rows($resql);
			$billsarray = array();

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$billsarray[$i] = $obj->fk_facturefourn;
				$i++;
			}

			return $billsarray;
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error);
			return -1;
		}
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
		global $langs;

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


	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option			What is the link pointing to
	 *  @param		string  $mode           'withlistofinvoices'=Include list of invoices into tooltip
	 *  @param		int  	$notooltip		1=Disable tooltip
	 *  @param		string	$morecss		Add more CSS
	 *	@return		string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $mode = 'withlistofinvoices', $notooltip = 0, $morecss = '')
	{
		global $langs, $conf, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$text = $this->ref; // Sometimes ref contains label
		$reg = array();
		if (preg_match('/^\((.*)\)$/i', $text, $reg)) {
			// Label generique car entre parentheses. On l'affiche en le traduisant
			if ($reg[1] == 'paiement') {
				$reg[1] = 'Payment';
			}
			$text = $langs->trans($reg[1]);
		}

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Payment").'</u><br>';
		$label .= '<strong>'.$langs->trans("Ref").':</strong> '.$text;
		$dateofpayment = ($this->datepaye ? $this->datepaye : $this->date);
		if ($dateofpayment) {
			$label .= '<br><strong>'.$langs->trans("Date").':</strong> '.dol_print_date($dateofpayment, 'dayhour', 'tzuser');
		}
		if ($this->amount) {
			$label .= '<br><strong>'.$langs->trans("Amount").':</strong> '.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency);
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

		$linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$this->id.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
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
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return int
	 */
	public function initAsSpecimen($option = '')
	{
		$now = dol_now();
		$arraynow = dol_getdate($now);
		$nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Initialize parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->facid = 1;
		$this->socid = 1;
		$this->datepaye = $nownotime;

		return 1;
	}

	/**
	 *      Return next reference of supplier invoice not already used (or last reference)
	 *      according to numbering module defined into constant SUPPLIER_PAYMENT_ADDON
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
		if (!getDolGlobalString('SUPPLIER_PAYMENT_ADDON')) {
			$conf->global->SUPPLIER_PAYMENT_ADDON = 'mod_supplier_payment_bronan';
		} elseif (getDolGlobalString('SUPPLIER_PAYMENT_ADDON') == 'brodator') {
			$conf->global->SUPPLIER_PAYMENT_ADDON = 'mod_supplier_payment_brodator';
		} elseif (getDolGlobalString('SUPPLIER_PAYMENT_ADDON') == 'bronan') {
			$conf->global->SUPPLIER_PAYMENT_ADDON = 'mod_supplier_payment_bronan';
		}

		if (getDolGlobalString('SUPPLIER_PAYMENT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('SUPPLIER_PAYMENT_ADDON') . ".php";
			$classname = getDolGlobalString('SUPPLIER_PAYMENT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/supplier_payment/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file)) {
					$mybool = ((bool) @include_once $dir.$file) || $mybool;
				}
			}

			// For compatibility
			if (!$mybool) {
				$file = getDolGlobalString('SUPPLIER_PAYMENT_ADDON') . ".php";
				$classname = "mod_supplier_payment_" . getDolGlobalString('SUPPLIER_PAYMENT_ADDON');
				$classname = preg_replace('/\-.*$/', '', $classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot) {
					$dir = $dirroot."/core/modules/supplier_payment/";

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool = ((bool) @include_once $dir.$file) || $mybool;
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
				dol_print_error($db, "SupplierPayment::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Supplier"));
			return "";
		}
	}

	/**
	 *	Create a document onto disk according to template model.
	 *
	 *	@param	    string		$modele			Force template to use ('' to not force)
	 *	@param		Translate	$outputlangs	Object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 *  @return     int         				Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $user, $langs;

		$langs->load("suppliers");

		// Set the model on the model name to use
		if (empty($modele)) {
			if (getDolGlobalString('SUPPLIER_PAYMENT_ADDON_PDF')) {
				$modele = getDolGlobalString('SUPPLIER_PAYMENT_ADDON_PDF');
			} else {
				$modele = ''; // No default value. For supplier invoice, we allow to disable all PDF generation
			}
		}

		if (empty($modele)) {
			return 0;
		} else {
			$modelpath = "core/modules/supplier_payment/doc/";

			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}
	}



	/**
	 * 	get the right way of payment
	 *
	 * 	@return 	string 	'dolibarr' if standard comportment or paid in dolibarr currency, 'customer' if payment received from multicurrency inputs
	 */
	public function getWay()
	{
		global $conf;

		$way = 'dolibarr';
		if (isModEnabled("multicurrency")) {
			foreach ($this->multicurrency_amounts as $value) {
				if (!empty($value)) { // one value found then payment is in invoice currency
					$way = 'customer';
					break;
				}
			}
		}

		return $way;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load the third party of object, from id into this->thirdparty
	 *
	 *	@param		int		$force_thirdparty_id	Force thirdparty id
	 *	@return		int								Return integer <0 if KO, >0 if OK
	 */
	public function fetch_thirdparty($force_thirdparty_id = 0)
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

		if (empty($force_thirdparty_id)) {
			$billsarray = $this->getBillsArray(); // From payment, the fk_soc isn't available, we should load the first supplier invoice to get him
			if (!empty($billsarray)) {
				$supplier_invoice = new FactureFournisseur($this->db);
				if ($supplier_invoice->fetch($billsarray[0]) > 0) {
					$force_thirdparty_id = $supplier_invoice->socid;
				}
			}
		}

		return parent::fetch_thirdparty($force_thirdparty_id);
	}
}
