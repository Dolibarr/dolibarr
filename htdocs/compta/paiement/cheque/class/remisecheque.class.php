<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/compta/paiement/cheque/class/remisecheque.class.php
 *	\ingroup    compta
 *	\brief      File with class to manage cheque delivery receipts
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


/**
 *	Class to manage cheque delivery receipts
 */
class RemiseCheque extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='chequereceipt';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='bordereau_cheque';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	public $num;
	public $intitule;
	//! Numero d'erreur Plage 1024-1279
	public $errno;

	public $amount;
	public $date_bordereau;
	public $account_id;
	public $account_label;
	public $author_id;
	public $nbcheque;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    public function __construct($db)
    {
		$this->db = $db;
		$this->next_id = 0;
		$this->previous_id = 0;
    }

	/**
	 *	Load record
	 *
	 *	@param	int		$id 			Id record
	 *	@param 	string	$ref		 	Ref record
	 * 	@return	int						<0 if KO, > 0 if OK
	 */
    public function fetch($id, $ref = '')
    {
		global $conf;

		$sql = "SELECT bc.rowid, bc.datec, bc.fk_user_author, bc.fk_bank_account, bc.amount, bc.ref, bc.statut, bc.nbcheque, bc.ref_ext";
		$sql.= ", bc.date_bordereau as date_bordereau";
		$sql.= ", ba.label as account_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON bc.fk_bank_account = ba.rowid";
		$sql.= " WHERE bc.entity = ".$conf->entity;
		if ($id)  $sql.= " AND bc.rowid = ".$id;
		if ($ref) $sql.= " AND bc.ref = '".$this->db->escape($ref)."'";

		dol_syslog("RemiseCheque::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($obj = $this->db->fetch_object($resql) )
			{
				$this->id             = $obj->rowid;
				$this->amount         = $obj->amount;
				$this->date_bordereau = $this->db->jdate($obj->date_bordereau);
				$this->account_id     = $obj->fk_bank_account;
				$this->account_label  = $obj->account_label;
				$this->author_id      = $obj->fk_user_author;
				$this->nbcheque       = $obj->nbcheque;
				$this->statut         = $obj->statut;
				$this->ref_ext        = $obj->ref_ext;

				if ($this->statut == 0)
				{
					$this->ref         = "(PROV".$this->id.")";
				}
				else
				{
					$this->ref         = $obj->ref;
				}
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
		    $this->error=$this->db->lasterror();
			return -1;
		}
    }

	/**
	 *	Create a receipt to send cheques
	 *
	 *	@param	User	$user 			User making creation
	 *	@param  int		$account_id 	Bank account for cheque receipt
	 *  @param  int		$limit          Limit ref of cheque to this
	 *  @param	array	$toRemise		array with cheques to remise
	 *	@return	int						<0 if KO, >0 if OK
	 */
    public function create($user, $account_id, $limit, $toRemise)
    {
		global $conf;

		$this->errno = 0;
		$this->id = 0;

		$now=dol_now();

		dol_syslog("RemiseCheque::Create start", LOG_DEBUG);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bordereau_cheque (";
		$sql.= "datec";
		$sql.= ", date_bordereau";
		$sql.= ", fk_user_author";
		$sql.= ", fk_bank_account";
		$sql.= ", statut";
		$sql.= ", amount";
		$sql.= ", ref";
		$sql.= ", entity";
		$sql.= ", nbcheque";
		$sql.= ", ref_ext";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($now)."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", ".$account_id;
		$sql.= ", 0";
		$sql.= ", 0";
		$sql.= ", 0";
		$sql.= ", ".$conf->entity;
		$sql.= ", 0";
		$sql.= ", ''";
		$sql.= ")";

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bordereau_cheque");
			if ($this->id == 0)
			{
				$this->errno = -1024;
				dol_syslog("Remisecheque::Create Error read id ".$this->errno, LOG_ERR);
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
				$sql.= " SET ref='(PROV".$this->id.")'";
				$sql.= " WHERE rowid=".$this->id."";

				$resql = $this->db->query($sql);
				if (! $resql)
				{
					$this->errno = -1025;
					dol_syslog("RemiseCheque::Create Error update ".$this->errno, LOG_ERR);
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				$lines = array();
				$sql = "SELECT b.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
				$sql.= " WHERE b.fk_type = 'CHQ'";
				$sql.= " AND b.amount > 0";
				$sql.= " AND b.fk_bordereau = 0";
				$sql.= " AND b.fk_account='".$account_id."'";
				if ($limit) $sql.= $this->db->plimit($limit);

				dol_syslog("RemiseCheque::Create", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					while ($row = $this->db->fetch_row($resql) )
					{
						array_push($lines, $row[0]);
					}
					$this->db->free($resql);
				}
				else
				{
					$this->errno = -1026;
					dol_syslog("RemiseCheque::Create Error ".$this->errno, LOG_ERR);
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				foreach ($lines as $lineid)
				{
					$checkremise=false;
					foreach ($toRemise as $linetoremise)
					{
						if($linetoremise==$lineid) $checkremise=true;
					}

					if ($checkremise)
					{
						$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
						$sql.= " SET fk_bordereau = ".$this->id;
						$sql.= " WHERE rowid = ".$lineid;

						$resql = $this->db->query($sql);
						if (!$resql)
						{
							$this->errno = -18;
							dol_syslog("RemiseCheque::Create Error update bank ".$this->errno, LOG_ERR);
						}
					}
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				if ($this->updateAmount() <> 0)
				{
					$this->errno = -1027;
					dol_syslog("RemiseCheque::Create Error update amount ".$this->errno, LOG_ERR);
				}
			}
		}
		else
		{
			$this->errno = -1;
			$this->error=$this->db->lasterror();
			$this->errno=$this->db->lasterrno();
		}

	    if (! $this->errno && ! empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res=$this->validate($user);
            //if ($res < 0) $error++;
        }

        if (! $this->errno)
        {
            $this->db->commit();
            dol_syslog("RemiseCheque::Create end", LOG_DEBUG);
            return $this->id;
        }
        else
        {
            $this->db->rollback();
            dol_syslog("RemiseCheque::Create end", LOG_DEBUG);
            return $this->errno;
        }
	}

	/**
	 *	Supprime la remise en base
	 *
	 *	@param  User	$user 		Utilisateur qui effectue l'operation
	 *	@return	int
	 */
    public function delete($user = '')
    {
		global $conf;

		$this->errno = 0;
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$num = $this->db->affected_rows($resql);

			if ($num <> 1) {
				$this->errno = -2;
				dol_syslog("Remisecheque::Delete Erreur Lecture ID ($this->errno)");
			}

			if ( $this->errno === 0) {
			    $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
			    $sql.= " SET fk_bordereau = 0";
			    $sql.= " WHERE fk_bordereau = ".$this->id;

			    $resql = $this->db->query($sql);
			    if (!$resql)
			    {
			        $this->errno = -1028;
				    dol_syslog("RemiseCheque::Delete ERREUR UPDATE ($this->errno)");
				}
			}
		}

		if ($this->errno === 0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Delete ROLLBACK ($this->errno)");
		}

		return $this->errno;
	}

	/**
	 *  Validate a receipt
	 *
	 *  @param	User	$user 		User
	 *  @return int      			<0 if KO, >0 if OK
	 */
    public function validate($user)
    {
		global $langs,$conf;

		$this->errno = 0;

		$this->db->begin();

		$numref = $this->getNextNumRef();

		if ($this->errno == 0 && $numref)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
			$sql.= " SET statut = 1, ref = '".$numref."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND statut = 0";

			dol_syslog("RemiseCheque::Validate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ( $resql )
			{
				$num = $this->db->affected_rows($resql);

				if ($num == 1)
				{
				    $this->ref = $numref;
					$this->statut = 1;
				}
				else
				{
					$this->errno = -1029;
					dol_syslog("Remisecheque::Validate Error ".$this->errno, LOG_ERR);
				}
			}
			else
			{
				$this->errno = -1033;
				dol_syslog("Remisecheque::Validate Error ".$this->errno, LOG_ERR);
			}
		}

		// Commit/Rollback
		if ($this->errno == 0)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Validate ".$this->errno, LOG_ERR);
            return $this->errno;
		}
	}

	/**
	 *      Return next reference of cheque receipts not already used (or last reference)
	 *      according to numbering module defined into constant FACTURE_ADDON
	 *
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
    public function getNextNumRef($mode = 'next')
    {
		global $conf, $db, $langs, $mysoc;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->CHEQUERECEIPTS_ADDON)) $conf->global->CHEQUERECEIPTS_ADDON='mod_chequereceipt_mint';
		elseif ($conf->global->CHEQUERECEIPTS_ADDON=='thyme') $conf->global->CHEQUERECEIPTS_ADDON='mod_chequereceipt_thyme';
		elseif ($conf->global->CHEQUERECEIPTS_ADDON=='mint') $conf->global->CHEQUERECEIPTS_ADDON='mod_chequereceipt_mint';

		if (! empty($conf->global->CHEQUERECEIPTS_ADDON))
		{
			$mybool=false;

			$file = $conf->global->CHEQUERECEIPTS_ADDON.".php";
			$classname = $conf->global->CHEQUERECEIPTS_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/cheque/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file))
				{
					$mybool |= include_once $dir . $file;
				}
			}

			// For compatibility
			if (! $mybool)
			{
				$file = $conf->global->CHEQUERECEIPTS_ADDON.".php";
				$classname = "mod_chequereceipt_".$conf->global->CHEQUERECEIPTS_ADDON;
				$classname = preg_replace('/\-.*$/', '', $classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot)
				{
					$dir = $dirroot."/core/modules/cheque/";

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool |= include_once $dir . $file;
					}
				}
			}

			if (! $mybool)
			{
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($mysoc, $this);

			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				dol_print_error($db, "ChequeReceipts::getNextNumRef ".$obj->error);
				return "";
			}

			return $numref;
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Bank"));
			return "";
		}
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param      User	$user       Objet user
	 *      @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
	 */
    public function load_board($user)
    {
        // phpcs:enable
		global $conf, $langs;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$sql = "SELECT b.rowid, b.datev as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql.= " AND b.fk_type = 'CHQ'";
		$sql.= " AND b.fk_bordereau = 0";
		$sql.= " AND b.amount > 0";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$langs->load("banks");
			$now=dol_now();

			$response = new WorkboardResponse();
			$response->warning_delay=$conf->bank->cheque->warning_delay/60/60/24;
			$response->label=$langs->trans("BankChecksToReceipt");
			$response->url=DOL_URL_ROOT.'/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank';
			$response->img=img_object('', "payment");

			while ($obj=$this->db->fetch_object($resql))
			{
				$response->nbtodo++;

				if ($this->db->jdate($obj->datefin) < ($now - $conf->bank->cheque->warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Charge indicateurs this->nb de tableau de bord
	 *
	 *      @return     int         <0 if ko, >0 if ok
	 */
    public function load_state_board()
    {
        // phpcs:enable
		global $user;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$sql = "SELECT count(b.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql.= " AND b.fk_type = 'CHQ'";
		$sql.= " AND b.amount > 0";

		$resql=$this->db->query($sql);
		if ($resql)
		{

			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["cheques"]=$obj->nb;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
    }


	/**
	 *	Build document
	 *
	 *	@param	string		$model 			Model name
	 *	@param 	Translate	$outputlangs	Object langs
	 * 	@return int        					<0 if KO, >0 if OK
	 */
    public function generatePdf($model, $outputlangs)
    {
		global $langs,$conf;

		if (empty($model)) $model='blochet';

		dol_syslog("RemiseCheque::generatePdf model=".$model." id=".$this->id, LOG_DEBUG);

		$dir=DOL_DOCUMENT_ROOT ."/core/modules/cheque/doc/";

		// Charge le modele
		$file = "pdf_".$model.".class.php";
		if (file_exists($dir.$file))
		{
			include_once DOL_DOCUMENT_ROOT .'/compta/bank/class/account.class.php';
			include_once $dir.$file;

			$classname='BordereauCheque'.ucfirst($model);
			$docmodel = new $classname($this->db);

			$sql = "SELECT b.banque, b.emetteur, b.amount, b.num_chq";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
			$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
			$sql.= ", ".MAIN_DB_PREFIX."bordereau_cheque as bc";
			$sql.= " WHERE b.fk_account = ba.rowid";
			$sql.= " AND b.fk_bordereau = bc.rowid";
			$sql.= " AND bc.rowid = ".$this->id;
			$sql.= " AND bc.entity = ".$conf->entity;
			$sql.= " ORDER BY b.dateo ASC, b.rowid ASC";

			dol_syslog("RemiseCheque::generatePdf", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$i = 0;
				while ($objp = $this->db->fetch_object($result))
				{
					$docmodel->lines[$i] = new stdClass();
					$docmodel->lines[$i]->bank_chq = $objp->banque;
					$docmodel->lines[$i]->emetteur_chq = $objp->emetteur;
					$docmodel->lines[$i]->amount_chq = $objp->amount;
					$docmodel->lines[$i]->num_chq = $objp->num_chq;
					$i++;
				}
			}
			$docmodel->nbcheque = $this->nbcheque;
			$docmodel->ref = $this->ref;
			$docmodel->amount = $this->amount;
			$docmodel->date   = $this->date_bordereau;

			$account = new Account($this->db);
			$account->fetch($this->account_id);

			$docmodel->account = &$account;

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charseSupprimert_output=$outputlangs->charset_output;
			$result=$docmodel->write_file($this, $conf->bank->dir_output.'/checkdeposits', $this->ref, $outputlangs);
			if ($result > 0)
			{
				//$outputlangs->charset_output=$sav_charset_output;
				return 1;
			}
			else
			{
				//$outputlangs->charset_output=$sav_charset_output;
				dol_syslog("Error");
				dol_print_error($this->db, $docmodel->error);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorFileDoesNotExists", $dir.$file);
			return -1;
		}
    }

	/**
	 *	Mets a jour le montant total
	 *
	 *	@return 	int		0 en cas de succes
	 */
    public function updateAmount()
    {
		global $conf;

		$this->errno = 0;
		$this->db->begin();
		$total = 0;
		$nb = 0;
		$sql = "SELECT amount ";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE fk_bordereau = ".$this->id;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			while ( $row = $this->db->fetch_row($resql) )
			{
				$total += $row[0];
				$nb++;
			}

			$this->db->free($resql);

			$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
			$sql.= " SET amount = '".price2num($total)."'";
			$sql.= ", nbcheque = ".$nb;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$this->errno = -1030;
				dol_syslog("RemiseCheque::updateAmount ERREUR UPDATE ($this->errno)");
			}
		}
		else
		{
			$this->errno = -1031;
			dol_syslog("RemiseCheque::updateAmount ERREUR SELECT ($this->errno)");
		}

		if ($this->errno === 0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::updateAmount ROLLBACK ($this->errno)");
		}

		return $this->errno;
    }

	/**
	 *	Insere la remise en base
	 *
	 *	@param	int		$account_id 		Compte bancaire concerne
	 * 	@return	int
	 */
    public function removeCheck($account_id)
    {
		$this->errno = 0;

		if ($this->id > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
			$sql.= " SET fk_bordereau = 0";
			$sql.= " WHERE rowid = '".$account_id."'";
			$sql.= " AND fk_bordereau = ".$this->id;

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$this->updateAmount();
			}
			else
			{
				$this->errno = -1032;
				dol_syslog("RemiseCheque::removeCheck ERREUR UPDATE ($this->errno)");
			}
		}
		return 0;
    }

	/**
	 *	Check return management
	 *	Reopen linked invoices and create a new negative payment.
	 *
	 *	@param	int		$bank_id 		   Id of bank transaction line concerned
	 *	@param	integer	$rejection_date    Date to use on the negative payment
	 * 	@return	int                        Id of negative payment line created
	 */
    public function rejectCheck($bank_id, $rejection_date)
    {
		global $db, $user;

		$payment = new Paiement($db);
		$payment->fetch(0, 0, $bank_id);

		$bankline = new AccountLine($db);
		$bankline->fetch($bank_id);

		/* Conciliation is allowed because when check is returned, a new line is created onto bank transaction log.
		if ($bankline->rappro)
		{
            $this->error='ActionRefusedLineAlreadyConciliated';
		    return -1;
		}*/

		$this->db->begin();

		// Not conciliated, we can delete it
		//$bankline->delete($user);    // We delete

		$bankaccount = $payment->fk_account;

		// Get invoices list to reopen them
		$sql = 'SELECT pf.fk_facture, pf.amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
		$sql.= ' WHERE pf.fk_paiement = '.$payment->id;

		$resql=$db->query($sql);
		if ($resql)
		{
			$rejectedPayment = new Paiement($db);
			$rejectedPayment->amounts = array();
			$rejectedPayment->datepaye = $rejection_date;
			$rejectedPayment->paiementid = dol_getIdFromCode($this->db, 'CHQ', 'c_paiement', 'code', 'id', 1);
			$rejectedPayment->num_paiement = $payment->numero;

			while($obj = $db->fetch_object($resql))
			{
				$invoice = new Facture($db);
				$invoice->fetch($obj->fk_facture);
				$invoice->set_unpaid($user);

				$rejectedPayment->amounts[$obj->fk_facture] = price2num($obj->amount) * -1;
			}

			$result = $rejectedPayment->create($user);
			if ($result > 0)
			{
                // We created a negative payment, we also add the line as bank transaction
			    $result=$rejectedPayment->addPaymentToBank($user, 'payment', '(CheckRejected)', $bankaccount, '', '');
				if ($result > 0)
				{
				    $result = $payment->reject();
					if ($result > 0)
					{
    					$this->db->commit();
    					return $rejectedPayment->id;
					}
					else
					{
                        $this->db->rollback();
					    return -1;
					}
				}
				else
				{
				    $this->error = $rejectedPayment->error;
				    $this->errors = $rejectedPayment->errors;
				    $this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error = $rejectedPayment->error;
				$this->errors = $rejectedPayment->errors;
			    $this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Charge les proprietes ref_previous et ref_next
	 *
	 *  @return     int   <0 if KO, 0 if OK
	 */
    public function load_previous_next_id()
    {
        // phpcs:enable
		global $conf;

		$this->errno = 0;

		$sql = "SELECT MAX(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid < ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->errno = -1035;
		}
		$row = $this->db->fetch_row($result);
		$this->previous_id = $row[0];

		$sql = "SELECT MIN(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid > ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$result = $this->db->query($sql);
		if (! $result)
		{
			$this->errno = -1035;
		}
		$row = $this->db->fetch_row($result);
		$this->next_id = $row[0];

		return $this->errno;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *      Set the creation date
     *
     *      @param	User		$user           Object user
     *      @param  int   $date           Date creation
     *      @return int                 		<0 if KO, >0 if OK
     */
    public function set_date($user, $date)
    {
        // phpcs:enable
        if ($user->rights->banque->cheque)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
            $sql.= " SET date_bordereau = ".($date ? "'".$this->db->idate($date)."'" : 'null');
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("RemiseCheque::set_date", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date_bordereau = $date;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Set the ref of bordereau
	 *
	 *      @param	User		$user           Object user
	 *      @param  int   $ref         ref of bordereau
	 *      @return int                 		<0 if KO, >0 if OK
	 */
    public function set_number($user, $ref)
    {
        // phpcs:enable
		if ($user->rights->banque->cheque)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
			$sql.= " SET ref = '".$ref."'" ;
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog("RemiseCheque::set_number", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	void
	 */
    public function initAsSpecimen($option = '')
    {
		global $user,$langs,$conf;

		$now=dol_now();
		$arraynow=dol_getdate($now);
		$nownotime=dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Initialize parameters
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->date_bordereau = $nownotime;
    }

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option						Sur quoi pointe le lien
     *  @param	int  	$notooltip					1=Disable tooltip
     *  @param  string  $morecss            		Add more css on link
     *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
		global $conf, $langs;

		$result='';

		$label = '<u>'.$langs->trans("ShowCheckReceipt").'</u>';
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = DOL_URL_ROOT.'/compta/paiement/cheque/card.php?id='.$this->id;

        if ($option != 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
        	if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        	{
        		$label=$langs->trans("ShowCheckReceipt");
        		$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
        	}
        	$linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
        	$linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;

		return $result;
    }

	/**
	 *  Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string				Libelle
	 */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a status
	 *
	 *  @param	int		$status     Statut
	 *  @param  int		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @return string      		Libelle du statut
	 */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
		global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage
		$langs->load('compta');
		if ($mode == 0)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			elseif ($status == 1) return $langs->trans('Validated');
		}
		elseif ($mode == 1)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			elseif ($status == 1) return $langs->trans('Validated');
		}
		elseif ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'), 'statut0').' '.$langs->trans('ToValidate');
			elseif ($status == 1) return img_picto($langs->trans('Validated'), 'statut4').' '.$langs->trans('Validated');
		}
		elseif ($mode == 3)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'), 'statut0');
			elseif ($status == 1) return img_picto($langs->trans('Validated'), 'statut4');
		}
		elseif ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'), 'statut0').' '.$langs->trans('ToValidate');
			elseif ($status == 1) return img_picto($langs->trans('Validated'), 'statut4').' '.$langs->trans('Validated');
		}
		elseif ($mode == 5)
		{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'), 'statut0');
			elseif ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'), 'statut4');
		}
		elseif ($mode == 6)
		{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'), 'statut0');
			elseif ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'), 'statut4');
		}
		return $langs->trans('Unknown');
    }
}
