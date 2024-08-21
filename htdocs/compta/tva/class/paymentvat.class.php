<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *      \file       htdocs/compta/sociales/class/paymentsocialcontribution.class.php
 *		\ingroup    invoice
 *		\brief      File of class to manage payment of social contributions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';


/**
 *	Class to manage payments of social contributions
 */
class PaymentVAT extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'payment_vat';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'payment_vat';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * @var int ID
	 */
	public $fk_tva;

	public $datec = '';

	public $datep = '';

	/**
	 * @deprecated Use $amount instead
	 * @see $amount
	 * @var float|int
	 */
	public $total;

	/**
	 * @var float|int
	 */
	public $amount; // Total amount of payment

	/**
	 * @var array<float|int>
	 */
	public $amounts = array(); // Array of amounts

	/**
	 * @var int ID
	 */
	public $fk_typepaiement;

	/**
	 * @var string
	 * @deprecated Use $num_payment instead
	 * @see $num_payment
	 */
	public $num_paiement;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var int ID
	 */
	public $fk_bank;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var int ID
	 */
	public $chid;

	/**
	 * @var string lib
	 * @deprecated
	 * @see $label
	 */
	public $lib;

	/**
	 * @var int|string datepaye
	 */
	public $datepaye;

	/**
	 * @var string
	 */
	public $type_code;

	/**
	 * @var string
	 */
	public $type_label;

	/**
	 * @var int
	 */
	public $bank_account;

	/**
	 * @var int
	 */
	public $bank_line;

	/**
	 * @var int|string paiementtype
	 */
	public $paiementtype;

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
	 *  Create payment of vat into database.
	 *  Use this->amounts to have list of lines for the payment
	 *
	 *  @param      User	$user   				User making payment
	 *	@param		int		$closepaidvat			1=Also close paid contributions to paid, 0=Do nothing more
	 *  @return     int     						Return integer <0 if KO, id of payment if OK
	 */
	public function create($user, $closepaidvat = 0)
	{
		$error = 0;

		$now = dol_now();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		// Validate parameters
		if (!$this->datepaye) {
			$this->error = 'ErrorBadValueForParameterCreatePaymentVAT';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_tva)) {
			$this->fk_tva = (int) $this->fk_tva;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepaiement)) {
			$this->fk_typepaiement = (int) $this->fk_typepaiement;
		}
		if (isset($this->num_paiement)) {
			$this->num_paiement = trim($this->num_paiement); // deprecated
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		$totalamount = 0;
		foreach ($this->amounts as $key => $value) {  // How payment is dispatch
			$newvalue = (float) price2num($value, 'MT');
			$this->amounts[$key] = $newvalue;
			$totalamount += $newvalue;
		}
		// $totalamount = price2num($totalamount);

		// Check parameters
		if ($totalamount == 0) {
			return -1; // We accept negative amounts for chargebacks, but not null amounts.
		}


		$this->db->begin();

		if ($totalamount != 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_vat (fk_tva, datec, datep, amount,";
			$sql .= " fk_typepaiement, num_paiement, note, fk_user_creat, fk_bank)";
			$sql .= " VALUES ($this->chid, '".$this->db->idate($now)."',";
			$sql .= " '".$this->db->idate($this->datepaye)."',";
			$sql .= " ".((float) $totalamount).",";
			$sql .= " ".((int) $this->paiementtype).", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note)."', ".$user->id.",";
			$sql .= " 0)";

			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_vat");

				// Insert table of amounts / invoices
				foreach ($this->amounts as $key => $amount) {
					$contribid = $key;
					if (is_numeric($amount) && $amount != 0) {
						$amount = (float) price2num($amount);

						// If we want to closed paid invoices
						if ($closepaidvat) {
							$contrib = new Tva($this->db);
							$contrib->fetch($contribid);
							$paiement = $contrib->getSommePaiement();
							//$creditnotes=$contrib->getSumCreditNotesUsed();
							$creditnotes = 0;
							//$deposits=$contrib->getSumDepositsUsed();
							$deposits = 0;
							$alreadypayed = (float) price2num($paiement + $creditnotes + $deposits, 'MT');
							$remaintopay = (float) price2num($contrib->amount - $paiement - $creditnotes - $deposits, 'MT');
							if ($remaintopay == 0) {
								$result = $contrib->setPaid($user);
							} else {
								dol_syslog("Remain to pay for conrib ".$contribid." not null. We do nothing.");
							}
						}
					}
				}
			} else {
				$error++;
			}
		}

		$result = $this->call_trigger('PAYMENTVAT_CREATE', $user);
		if ($result < 0) {
			$error++;
		}

		if ($totalamount != 0 && !$error) {
			$this->amount = $totalamount;
			$this->total = $totalamount; // deprecated
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_tva,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.datep,";
		$sql .= " t.amount,";
		$sql .= " t.fk_typepaiement,";
		$sql .= " t.num_paiement as num_payment,";
		$sql .= " t.note as note_private,";
		$sql .= " t.fk_bank,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " pt.code as type_code, pt.libelle as type_label,";
		$sql .= ' b.fk_account';
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_vat as t LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepaiement = pt.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql .= " WHERE t.rowid = ".((int) $id);
		// TODO link on entity of tax;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->fk_tva = $obj->fk_tva;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->datep = $this->db->jdate($obj->datep);
				$this->amount = $obj->amount;
				$this->fk_typepaiement = $obj->fk_typepaiement;
				$this->num_paiement = $obj->num_payment;
				$this->num_payment = $obj->num_payment;
				$this->note = $obj->note_private;
				$this->note_private = $obj->note_private;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;

				$this->type_code = $obj->type_code;
				$this->type_label = $obj->type_label;

				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_tva)) {
			$this->fk_tva = (int) $this->fk_tva;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepaiement)) {
			$this->fk_typepaiement = (int) $this->fk_typepaiement;
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_vat SET";
		$sql .= " fk_tva=".(isset($this->fk_tva) ? ((int) $this->fk_tva) : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " tms=".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql .= " datep=".(dol_strlen($this->datep) != 0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql .= " amount=".(isset($this->amount) ? (float) price2num($this->amount) : "null").",";
		$sql .= " fk_typepaiement=".(isset($this->fk_typepaiement) ? ((int) $this->fk_typepaiement) : "null").",";
		$sql .= " num_paiement=".(isset($this->num_payment) ? "'".$this->db->escape($this->num_payment)."'" : "null").",";
		$sql .= " note=".(isset($this->note) ? "'".$this->db->escape($this->note)."'" : "null").",";
		$sql .= " fk_bank=".(isset($this->fk_bank) ? ((int) $this->fk_bank) : "null").",";
		$sql .= " fk_user_creat=".(isset($this->fk_user_creat) ? ((int) $this->fk_user_creat) : "null").",";
		$sql .= " fk_user_modif=".(isset($this->fk_user_modif) ? ((int) $this->fk_user_modif) : "null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *  @param	User	$user        	User that delete
	 *  @param  int		$notrigger		0=launch triggers after, 1=disable triggers
	 *  @return int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::delete");

		$this->db->begin();

		if ($this->bank_line > 0) {
			$accline = new AccountLine($this->db);
			$accline->fetch($this->bank_line);
			$result = $accline->delete($user);
			if ($result < 0) {
				$this->errors[] = $accline->error;
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_vat";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		    User making the clone
	 *	@param	int		$fromid     	Id of object to clone
	 * 	@return	int						New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error = 0;

		$object = new PaymentVAT($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->fk_tva = 0;
		$this->datec = dol_now();
		$this->tms = dol_now();
		$this->datep = dol_now();
		$this->amount = 100;
		$this->fk_typepaiement = 0;
		$this->num_payment = '123456';
		$this->note_private = 'Private note';
		$this->note_public = 'Public note';
		$this->fk_bank = 0;
		$this->fk_user_creat = 0;
		$this->fk_user_modif = 0;

		return 1;
	}


	/**
	 *      Add record into bank for payment with links between this bank record and invoices of payment.
	 *      All payment properties must have been set first like after a call to create().
	 *
	 *      @param	User	$user               Object of user making payment
	 *      @param  string	$mode               'payment_sc'
	 *      @param  string	$label              Label to use in bank record
	 *      @param  int		$accountid          Id of bank account to do link with
	 *      @param  string	$emetteur_nom       Name of transmitter
	 *      @param  string	$emetteur_banque    Name of bank
	 *      @return int                 		Return integer <0 if KO, >0 if OK
	 */
	public function addPaymentToBank($user, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque)
	{
		// Clean data
		$this->num_payment = trim($this->num_payment ? $this->num_payment : $this->num_paiement);

		$error = 0;

		if (isModEnabled("bank")) {
			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acc = new Account($this->db);
			$acc->fetch($accountid);

			$total = $this->amount;
			if ($mode == 'payment_vat') {
				$total = -$total;
			}

			// Insert payment into llx_bank
			$bank_line_id = $acc->addline(
				$this->datepaye,
				$this->paiementtype, // Payment mode id or code ("CHQ or VIR for example")
				$label,
				$total,
				$this->num_payment,
				'',
				$user,
				$emetteur_nom,
				$emetteur_banque
			);

			// Update fk_bank in llx_paiement.
			// We thus know the payment that generated the bank entry
			if ($bank_line_id > 0) {
				$result = $this->update_fk_bank($bank_line_id);
				if ($result <= 0) {
					$error++;
					dol_print_error($this->db);
				}

				// Add link 'payment', 'payment_supplier', 'payment_sc' in bank_url between payment and bank transaction
				$url = '';
				if ($mode == 'payment_vat') {
					$url = DOL_URL_ROOT.'/compta/payment_vat/card.php?id=';
				}
				if ($url) {
					$result = $acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
					if ($result <= 0) {
						$error++;
						dol_print_error($this->db);
					}
				}

				// Add link 'company' in bank_url between invoice and bank transaction (for each invoice concerned by payment)
				$linkaddedforthirdparty = array();
				foreach ($this->amounts as $key => $value) {
					if ($mode == 'payment_vat') {
						$tva = new Tva($this->db);
						$tva->fetch($key);
						$result = $acc->add_url_line($bank_line_id, $tva->id, DOL_URL_ROOT.'/compta/tva/card.php?id=', '('.$tva->label.')', 'vat');
						if ($result <= 0) {
							dol_print_error($this->db);
						}
					}
				}
			} else {
				$this->error = $acc->error;
				$error++;
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update link between vat payment and line in llx_bank generated
	 *
	 *  @param	int		$id_bank         Id if bank
	 *  @return	int			             >0 if OK, <=0 if KO
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_vat SET fk_bank = ".((int) $id_bank)." WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_fk_bank", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error();
			return 0;
		}
	}


	/**
	 * Return the label of the status
	 *
	 * @param	int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return  string				Libelle
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
	 *  Return clickable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * 	@param	int		$maxlen			Longueur max libelle
	 *	@return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0)
	{
		global $langs;

		$result = '';

		if (empty($this->ref)) {
			$this->ref = $this->lib;
		}

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("VATPayment").'</u>';
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (!empty($this->label)) {
			$labeltoshow = $this->label;
			$reg = array();
			if (preg_match('/^\((.*)\)$/i', $this->label, $reg)) {
				// Label generique car entre parentheses. On l'affiche en le traduisant
				if ($reg[1] == 'paiement') {
					$reg[1] = 'Payment';
				}
				$labeltoshow = $langs->trans($reg[1]);
			}
			$label .= '<br><b>'.$langs->trans('Label').':</b> '.$labeltoshow;
		}
		if ($this->datep) {
			$label .= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->datep, 'day');
		}

		if (!empty($this->id)) {
			$link = '<a href="'.DOL_URL_ROOT.'/compta/payment_vat/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend = '</a>';

			if ($withpicto) {
				$result .= ($link.img_object($label, 'payment', 'class="classfortooltip"').$linkend.' ');
			}
			if ($withpicto && $withpicto != 2) {
				$result .= ' ';
			}
			if ($withpicto != 2) {
				$result .= $link.($maxlen ? dol_trunc($this->ref, $maxlen) : $this->ref).$linkend;
			}
		}

		return $result;
	}
}
