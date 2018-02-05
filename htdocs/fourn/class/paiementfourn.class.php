<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent          <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
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
	public $element='payment_supplier';
	public $table_element='paiementfourn';
	public $picto = 'payment';

	var $statut;        //Status of payment. 0 = unvalidated; 1 = validated
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement

	/**
	 * Label of payment type
	 * @var string
	 */
	public $type_libelle;

	/**
	 * Code of Payment type
	 * @var string
	 */
	public $type_code;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Load payment object
	 *
	 *	@param	int		$id         Id if payment to get
	 *  @param	string	$ref		Ref of payment to get (currently ref = id but this may change in future)
	 *  @param	int		$fk_bank	Id of bank line associated to payment
	 *  @return int		            <0 if KO, -2 if not found, >0 if OK
	 */
	function fetch($id, $ref='', $fk_bank='')
	{
		$error=0;

		$sql = 'SELECT p.rowid, p.ref, p.entity, p.datep as dp, p.amount, p.statut, p.fk_bank,';
		$sql.= ' c.code as paiement_code, c.libelle as paiement_type,';
		$sql.= ' p.num_paiement, p.note, b.fk_account';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id AND c.entity IN ('.getEntity('c_paiement').')';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql.= ' WHERE p.entity IN ('.getEntity('facture_fourn').')';
		if ($id > 0)
			$sql.= ' AND p.rowid = '.$id;
		else if ($ref)
			$sql.= ' AND p.rowid = '.$ref;
		else if ($fk_bank)
			$sql.= ' AND p.fk_bank = '.$fk_bank;
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 0)
			{
				$obj = $this->db->fetch_object($resql);
				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref;
				$this->entity         = $obj->entity;
				$this->date           = $this->db->jdate($obj->dp);
				$this->numero         = $obj->num_paiement;
				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
				$this->montant        = $obj->amount;
				$this->note           = $obj->note;
				$this->type_code      = $obj->paiement_code;
				$this->type_libelle   = $obj->paiement_type;
				$this->statut         = $obj->statut;
				$error = 1;
			}
			else
			{
				$error = -2;    // TODO Use 0 instead
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
			$error = -1;
		}
		return $error;
	}

	/**
	 *	Create payment in database
	 *
	 *	@param		User	$user        			Object of creating user
	 *	@param		int		$closepaidinvoices   	1=Also close payed invoices to paid, 0=Do nothing more
	 *	@return     int         					id of created payment, < 0 if error
	 */
	function create($user, $closepaidinvoices=0)
	{
		global $langs,$conf;

		$error = 0;
		$way = $this->getWay();

		// Clean parameters
		$totalamount = 0;
		$totalamount_converted = 0;

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		if ($way == 'dolibarr')
		{
			$amounts = &$this->amounts;
			$amounts_to_update = &$this->multicurrency_amounts;
		}
		else
		{
			$amounts = &$this->multicurrency_amounts;
			$amounts_to_update = &$this->amounts;
		}

		foreach ($amounts as $key => $value)
		{
			$value_converted = Multicurrency::getAmountConversionFromInvoiceRate($key, $value, $way, 'facture_fourn');
			$totalamount_converted += $value_converted;
			$amounts_to_update[$key] = price2num($value_converted, 'MT');

			$newvalue = price2num($value,'MT');
			$amounts[$key] = $newvalue;
			$totalamount += $newvalue;
		}
		$totalamount = price2num($totalamount);
		$totalamount_converted = price2num($totalamount_converted);

		$this->db->begin();

		if ($totalamount <> 0) // On accepte les montants negatifs
		{
			$ref = $this->getNextNumRef('');
			$now=dol_now();

			if ($way == 'dolibarr')
			{
				$total = $totalamount;
				$mtotal = $totalamount_converted; // Maybe use price2num with MT for the converted value
			}
			else
			{
				$total = $totalamount_converted; // Maybe use price2num with MT for the converted value
				$mtotal = $totalamount;
			}

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn (';
			$sql.= 'ref, entity, datec, datep, amount, multicurrency_amount, fk_paiement, num_paiement, note, fk_user_author, fk_bank)';
			$sql.= " VALUES ('".$this->db->escape($ref)."', ".$conf->entity.", '".$this->db->idate($now)."',";
			$sql.= " '".$this->db->idate($this->datepaye)."', '".$total."', '".$mtotal."', ".$this->paiementid.", '".$this->num_paiement."', '".$this->db->escape($this->note)."', ".$user->id.", 0)";

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiementfourn');

				// Insere tableau des montants / factures
				foreach ($this->amounts as $key => $amount)
				{
					$facid = $key;
					if (is_numeric($amount) && $amount <> 0)
					{
						$amount = price2num($amount);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn (fk_facturefourn, fk_paiementfourn, amount, multicurrency_amount)';
						$sql .= ' VALUES ('.$facid.','. $this->id.',\''.$amount.'\', \''.$this->multicurrency_amounts[$key].'\')';
						$resql=$this->db->query($sql);
						if ($resql)
						{
							$invoice=new FactureFournisseur($this->db);
							$invoice->fetch($facid);

							// If we want to closed payed invoices
							if ($closepaidinvoices)
							{
								$paiement = $invoice->getSommePaiement();
								//$creditnotes=$invoice->getSumCreditNotesUsed();
								$creditnotes=0;
								//$deposits=$invoice->getSumDepositsUsed();
								$deposits=0;
								$alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
								$remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
								if ($remaintopay == 0)
								{
									$result=$invoice->set_paid($user, '', '');
								}
								else dol_syslog("Remain to pay for invoice ".$facid." not null. We do nothing.");
							}

							// Regenerate documents of invoices
							if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
							{
								$outputlangs = $langs;
								if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $invoice->thirdparty->default_lang;
								if (! empty($newlang)) {
									$outputlangs = new Translate("", $conf);
									$outputlangs->setDefaultLang($newlang);
								}
								$ret = $invoice->fetch($facid); // Reload to get new records
								$result = $invoice->generateDocument($invoice->modelpdf, $outputlangs);
								if ($result < 0) {
									setEventMessages($invoice->error, $invoice->errors, 'errors');
									$error++;
								}
							}
						}
						else
						{
							$this->error=$this->db->lasterror();
							$error++;
						}

					}
					else
					{
						dol_syslog(get_class($this).'::Create Amount line '.$key.' not a number. We discard it.');
					}
				}

				if (! $error)
				{
					// Call trigger
					$result=$this->call_trigger('PAYMENT_SUPPLIER_CREATE',$user);
					if ($result < 0) $error++;
					// End call triggers
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		else
		{
			$this->error="ErrorTotalIsNull";
			dol_syslog('PaiementFourn::Create Error '.$this->error, LOG_ERR);
			$error++;
		}

		if ($totalamount <> 0 && $error == 0) // On accepte les montants negatifs
		{
			$this->amount=$total;
			$this->total=$total;
			$this->multicurrency_amount=$mtotal;
			$this->db->commit();
			dol_syslog('PaiementFourn::Create Ok Total = '.$this->total);
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Supprime un paiement ainsi que les lignes qu'il a genere dans comptes
	 *	Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *	Si le paiement porte sur au moins une facture a "payee", on refuse
	 *
	 *	@param		int		$notrigger		No trigger
	 *	@return     int     <0 si ko, >0 si ok
	 */
	function delete($notrigger=0)
	{
		global $conf, $user, $langs;

		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture a l'etat payee
		// Si c'est le cas, on refuse la suppression
		$billsarray=$this->getBillsArray('paye=1');
		if (is_array($billsarray))
		{
			if (count($billsarray))
			{
				$this->error="ErrorCantDeletePaymentSharedWithPayedInvoice";
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -2;
		}

		// Verifier si paiement ne porte pas sur ecriture bancaire rapprochee
		// Si c'est le cas, on refuse le delete
		if ($bank_line_id)
		{
			$accline = new AccountLine($this->db);
			$accline->fetch($bank_line_id);
			if ($accline->rappro)
			{
				$this->error="ErrorCantDeletePaymentReconciliated";
				$this->db->rollback();
				return -3;
			}
		}

		// Efface la ligne de paiement (dans paiement_facture et paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
		$sql.= ' WHERE fk_paiementfourn = '.$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiementfourn';
			$sql.= ' WHERE rowid = '.$this->id;
			$result = $this->db->query($sql);
			if (! $result)
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -3;
			}

			// Supprimer l'ecriture bancaire si paiement lie a ecriture
			if ($bank_line_id)
			{
				$accline = new AccountLine($this->db);
				$result=$accline->fetch($bank_line_id);
				if ($result > 0) // If result = 0, record not found, we don't try to delete
				{
					$result=$accline->delete($user);
				}
				if ($result < 0)
				{
					$this->error=$accline->error;
					$this->db->rollback();
					return -4;
				}
			}

			if (! $notrigger)
			{
				// Appel des triggers
				$result=$this->call_trigger('PAYMENT_SUPPLIER_DELETE', $user);
				if ($result < 0)
				{
					$this->db->rollback();
					return -1;
				}
				// Fin appel triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error;
			$this->db->rollback();
			return -5;
		}
	}

	/**
	 *	Information on object
	 *
	 *	@param	int		$id      Id du paiement dont il faut afficher les infos
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, datec, fk_user_author as fk_user_creat, tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;

				if ($obj->fk_user_creat)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Return list of supplier invoices the payment point to
	 *
	 *	@param      string	$filter         SQL filter
	 *	@return     array           		Array of supplier invoice id
	 */
	function getBillsArray($filter='')
	{
		$sql = 'SELECT fk_facturefourn';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql.= ' WHERE pf.fk_facturefourn = f.rowid AND fk_paiementfourn = '.$this->id;
		if ($filter) $sql.= ' AND '.$filter;

		dol_syslog(get_class($this).'::getBillsArray', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num=$this->db->num_rows($resql);
			$billsarray=array();

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$billsarray[$i]=$obj->fk_facturefourn;
				$i++;
			}

			return $billsarray;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error);
			return -1;
		}
	}

	/**
	 *	Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *	@return     string				Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *	@param      int		$status     Statut
	 *	@param      int		$mode      0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *	@return     string      		Libelle du statut
	 */
	function LibStatut($status,$mode=0)
	{
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
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option			Sur quoi pointe le lien
	 *  @param		string  $mode           'withlistofinvoices'=Include list of invoices into tooltip
     *  @param		int  	$notooltip		1=Disable tooltip
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0, $option='', $mode='withlistofinvoices', $notooltip=0)
	{
		global $langs;

		$result='';
		$text=$this->ref;   // Sometimes ref contains label
		if (preg_match('/^\((.*)\)$/i',$text,$reg)) {
			// Label generique car entre parentheses. On l'affiche en le traduisant
			if ($reg[1]=='paiement') $reg[1]='Payment';
			$text=$langs->trans($reg[1]);
		}
		$label = $langs->trans("ShowPayment").': '.$text;

		$linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	void
	 */
	function initAsSpecimen($option='')
	{
		global $user,$langs,$conf;

		$now=dol_now();
		$arraynow=dol_getdate($now);
		$nownotime=dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Initialize parameters
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->facid = 1;
		$this->datepaye = $nownotime;
	}

	/**
	 *      Return next reference of supplier invoice not already used (or last reference)
	 *      according to numbering module defined into constant SUPPLIER_PAYMENT_ADDON
	 *
	 *      @param	   Societe		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
	function getNextNumRef($soc,$mode='next')
	{
		global $conf, $db, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->SUPPLIER_PAYMENT_ADDON)) $conf->global->SUPPLIER_PAYMENT_ADDON='mod_supplier_payment_bronan';
		else if ($conf->global->SUPPLIER_PAYMENT_ADDON=='brodator') $conf->global->SUPPLIER_PAYMENT_ADDON='mod_supplier_payment_brodator';
		else if ($conf->global->SUPPLIER_PAYMENT_ADDON=='bronan') $conf->global->SUPPLIER_PAYMENT_ADDON='mod_supplier_payment_bronan';

		if (! empty($conf->global->SUPPLIER_PAYMENT_ADDON))
		{
			$mybool=false;

			$file = $conf->global->SUPPLIER_PAYMENT_ADDON.".php";
			$classname = $conf->global->SUPPLIER_PAYMENT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/supplier_payment/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file))
				{
					$mybool |= include_once $dir . $file;
				}
			}

			// For compatibility
			if (! $mybool)
			{
				$file = $conf->global->SUPPLIER_PAYMENT_ADDON.".php";
				$classname = "mod_supplier_payment_".$conf->global->SUPPLIER_PAYMENT_ADDON;
				$classname = preg_replace('/\-.*$/','',$classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot)
				{
					$dir = $dirroot."/core/modules/supplier_payment/";

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool |= include_once $dir . $file;
					}
				}
			}

			if (! $mybool)
			{
				dol_print_error('',"Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc,$this);

			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				dol_print_error($db,"SupplierPayment::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete");
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
	 *  @return     int         				<0 if KO, 0 if nothing done, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf, $user, $langs;

		$langs->load("suppliers");

		// Set the model on the model name to use
		if (empty($modele))
		{
			if (! empty($conf->global->SUPPLIER_PAYMENT_ADDON_PDF))
			{
				$modele = $conf->global->SUPPLIER_PAYMENT_ADDON_PDF;
			}
			else
			{
				$modele = '';       // No default value. For supplier invoice, we allow to disable all PDF generation
			}
		}

		if (empty($modele))
		{
			return 0;
		}
		else
		{
			$modelpath = "core/modules/supplier_payment/doc/";

			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}



	/**
	 * 	get the right way of payment
	 *
	 * 	@return 	string 	'dolibarr' if standard comportment or paid in dolibarr currency, 'customer' if payment received from multicurrency inputs
	 */
	function getWay()
	{
		global $conf;

		$way = 'dolibarr';
		if (!empty($conf->multicurrency->enabled))
		{
			foreach ($this->multicurrency_amounts as $value)
			{
				if (!empty($value)) // one value found then payment is in invoice currency
				{
					$way = 'customer';
					break;
				}
			}
		}

		return $way;
	}


	/**
	 *    	Load the third party of object, from id into this->thirdparty
	 *
	 *		@param		int		$force_thirdparty_id	Force thirdparty id
	 *		@return		int								<0 if KO, >0 if OK
	 */
	function fetch_thirdparty($force_thirdparty_id=0)
	{
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

		if (empty($force_thirdparty_id))
		{
			$billsarray = $this->getBillsArray(); // From payment, the fk_soc isn't available, we should load the first supplier invoice to get him
			if (!empty($billsarray))
			{
				$supplier_invoice = new FactureFournisseur($this->db);
				if ($supplier_invoice->fetch($billsarray[0]) > 0)
				{
					$force_thirdparty_id = $supplier_invoice->fk_soc;
				}
			}
		}

		return parent::fetch_thirdparty($force_thirdparty_id);
	}

}
