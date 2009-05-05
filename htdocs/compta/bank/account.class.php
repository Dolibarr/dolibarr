<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/compta/bank/account.class.php
 *	\ingroup    banque
 *	\brief      Fichier de la classe des comptes bancaires
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**
 *	\class      Account
 *	\brief      Class to manage bank accounts
 */
class Account extends CommonObject
{
	var $db;
	var $error;
	var $element='bank_account';
	var $table_element='bank_account';

	var $rowid;
	var $ref;
	var $label;
	var $type;		// 'payment', 'company', 'member', 'banktransfert', 'payment_supplier', 'sc', 'payment_vat', ...
	var $bank;
	var $clos;
	var $rappro;
	var $url;
	//! BBAN field for French Code banque
	var $code_banque;
	//! BBAN field for French Code guichet
	var $code_guichet;
	//! BBAN main account number
	var $number;
	//! BBAN field for French Cle de controle
	var $cle_rib;
	//! BIC/SWIFT number
	var $bic;
	//! IBAN number (International Bank Account Number)
	var $iban_prefix;
	var $proprio;
	var $adresse_proprio;
	var $type_lib=array();

	var $account_number;

	var $currency_code;
	var $min_allowed;
	var $min_desired;
	var $comment;


	/**
	 *  Constructeur
	 */
	function Account($DB, $rowid=0)
	{
		global $langs;

		$this->db = $DB;
		$this->rowid = $rowid;

		$this->clos = 0;
		$this->solde = 0;

		$this->type_lib[0]=$langs->trans("BankType0");
		$this->type_lib[1]=$langs->trans("BankType1");
		$this->type_lib[2]=$langs->trans("BankType2");

		$this->status[0]=$langs->trans("StatusAccountOpened");
		$this->status[1]=$langs->trans("StatusAccountClosed");

		return 1;
	}


	/**
	 *      Add a link between bank line record and its source
	 *      @param      line_id     Id ecriture bancaire
	 *      @param      url_id      Id parametre url
	 *      @param      url         Url
	 *      @param      label       Link label
	 *      @param      type        Type of link ('payment', 'company', 'member', ...)
	 *      @return     int         <0 if KO, id line if OK
	 */
	function add_url_line($line_id, $url_id, $url, $label, $type)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (";
		$sql.= "fk_bank";
		$sql.= ", url_id";
		$sql.= ", url";
		$sql.= ", label";
		$sql.= ", type";
		$sql.= ") VALUES (";
		$sql.= "'".$line_id."'";
		$sql.= ", '".$url_id."'";
		$sql.= ", '".$url."'";
		$sql.= ", '".addslashes($label)."'";
		$sql.= ", '".$type."'";
		$sql.= ")";

		dol_syslog("Account::add_url_line sql=".$sql);
		if ($this->db->query($sql))
		{
			$rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_url");
			return $rowid;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *      \brief      Renvoi tableau des liens
	 *      \param      line_id         Id ligne �criture
	 *      \retuen     array           Tableau des liens
	 */
	function get_url($line_id)
	{
		$lines = array();
		
		$sql = "SELECT url_id, url, label, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_url";
		$sql.= " WHERE fk_bank = ".$line_id;
		$sql.= " ORDER BY type, label";

		$result = $this->db->query($sql);
		if ($result)
		{
			$i = 0;
			$num = $this->db->num_rows($result);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				// Anciens liens (pour compatibilit�)
				$lines[$i][0] = $obj->url;
				$lines[$i][1] = $obj->url_id;
				$lines[$i][2] = $obj->label;
				$lines[$i][3] = $obj->type;
				// Nouveaux liens
				$lines[$i]['url'] = $obj->url;
				$lines[$i]['url_id'] = $obj->url_id;
				$lines[$i]['label'] = $obj->label;
				$lines[$i]['type'] = $obj->type;
				$i++;
			}
			return $lines;
		}
	}

	/**
		\brief     	Ajoute une entree dans la table ".MAIN_DB_PREFIX."bank
		\param		$date			Date operation
		\param		$oper			1,2,3,4... or TYP,VIR,PRE,LIQ,VAD,CB,CHQ...
		\param		$label			Descripton
		\param		$amount			Montant
		\param		$num_chq		Numero cheque ou virement
		\param		$categorie		Categorie optionnelle
		\param		$user			User that create
		\param		$emetteur		Nom emetteur
		\param		$banque			Banque emettrice
		\return		int				Rowid of added entry, <0 si erreur
		*/
	function addline($date, $oper, $label, $amount, $num_chq='', $categorie='', $user, $emetteur='',$banque='')
	{
		// Clean parameters
		$emetteur=trim($emetteur);
		$banque=trim($banque);
		switch ($oper)
		{
			case 1:
				$oper = 'TIP';
				break;
			case 2:
				$oper = 'VIR';
				break;
			case 3:
				$oper = 'PRE';
				break;
			case 4:
				$oper = 'LIQ';
				break;
			case 5:
				$oper = 'VAD';
				break;
			case 6:
				$oper = 'CB';
				break;
			case 7:
				$oper = 'CHQ';
				break;
		}

		// Check parameters
		if (! $oper)
		{
			$this->error="Account::addline oper not defined";
			return -1;
		}
		if (! $this->rowid)
		{
			$this->error="Account::addline this->rowid not defined";
			return -2;
		}
		if ($this->courant == 2 && $oper != 'LIQ')
		{
			$this->error="ErrorCashAccountAcceptsOnlyCashMoney";
			return -3;
		}


		$this->db->begin();

		$datev = $date;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (";
		$sql.= "datec";
		$sql.= ", dateo";
		$sql.= ", datev";
		$sql.= ", label";
		$sql.= ", amount";
		$sql.= ", fk_user_author";
		$sql.= ", num_chq";
		$sql.= ", fk_account";
		$sql.= ", fk_type";
		$sql.= ",emetteur,banque";
		$sql.= ") VALUES (";
		$sql.= $this->db->idate(mktime());
		$sql.= ", '".$this->db->idate($date)."'";
		$sql.= ", '".$this->db->idate($datev)."'";
		$sql.= ", '".addslashes($label)."'";
		$sql.= ", ".price2num($amount);
		$sql.= ", '".$user->id."'";
		$sql.= ", ".($num_chq?"'".$num_chq."'":"null");
		$sql.= ", '".$this->rowid."'";
		$sql.= ", '".$oper."'";
		$sql.= ", ".($emetteur?"'".addslashes($emetteur)."'":"null");
		$sql.= ", ".($banque?"'".addslashes($banque)."'":"null");
		$sql.= ")";

		dol_syslog("Account::addline sql=".$sql);
		if ($this->db->query($sql))
		{
			$rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank");
			if ($categorie)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
				$sql.= "lineid";
				$sql.= ", fk_categ";
				$sql.= ") VALUES (";
				$sql.= "'".$rowid."'";
				$sql.= ", '".$categorie."'";
				$sql.= ")";
				
				$result = $this->db->query($sql);
				if (! $result)
				{
					$this->db->rollback();
					$this->error=$this->db->error();
					return -3;
				}
			}
			$this->db->commit();
			return $rowid;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Account::addline ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *      \brief          Creation du compte bancaire en base
	 *      \return         int     < 0 si erreur, > 0 si ok
	 */
	function create()
	{
		global $langs,$conf;

		// Check parameters
		if (! $this->min_allowed) $this->min_allowed=0;
		if (! $this->min_desired) $this->min_desired=0;

		// Chargement librairie pour acces fonction controle RIB
		require_once DOL_DOCUMENT_ROOT.'/lib/bank.lib.php';

		if (! $this->ref)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_account (";
		$sql.= "datec";
		$sql.= ", ref";
		$sql.= ", label";
		$sql.= ", entity";
		$sql.= ", account_number";
		$sql.= ", currency_code";
		$sql.= ", rappro";
		$sql.= ", min_allowed";
		$sql.= ", min_desired";
		$sql.= ", comment";
		$sql.= ") VALUES (";
		$sql.= $this->db->idate(mktime());
		$sql.= ", '".addslashes($this->ref)."'";
		$sql.= ", '".addslashes($this->label)."'";
		$sql.= ", ".$conf->entity;
		$sql.= ", '".addslashes($this->account_number)."'";
		$sql.= ", '".$this->currency_code."'";
		$sql.= ", ".$this->rappro;
		$sql.= ", ".price2num($this->min_allowed);
		$sql.= ", ".price2num($this->min_desired);
		$sql.= ", '".addslashes($this->comment)."'";
		$sql.= ")";

		dol_syslog("Account::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->affected_rows($resql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_account");
				if ( $this->update() )
				{
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (";
					$sql.= "datec";
					$sql.= ", label";
					$sql.= ", amount";
					$sql.= ", fk_account";
					$sql.= ", datev";
					$sql.= ", dateo";
					$sql.= ", fk_type";
					$sql.= ", rappro";
					$sql.= ") VALUES (";
					$sql.= $this->db->idate(mktime());
					$sql.= ", '(".$langs->trans("InitialBankBalance").")'";
					$sql.= ", ".price2num($this->solde);
					$sql.= ", '".$this->id."'";
					$sql.= ", '".$this->db->idate($this->date_solde)."'";
					$sql.= ", '".$this->db->idate($this->date_solde)."'";
					$sql.= ", 'SOLD'";
					$sql.= ", 1";
					$sql.= ")";
					
					$this->db->query($sql);
				}
				return $this->id;
			}
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$langs->trans("ErrorBankLabelAlreadyExists");
				dol_syslog($this->error, LOG_ERR);
				return -1;
			}
			else {
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog($this->error, LOG_ERR);
				return -2;
			}
		}
	}

	/**
	 *    	\brief      Mise a jour compte, partie generale
	 *    	\param      user        Object utilisateur qui modifie
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function update($user='')
	{
		global $langs,$conf;

		// Check parameters
		if (! $this->min_allowed) $this->min_allowed=0;
		if (! $this->min_desired) $this->min_desired=0;

		if (! $this->ref)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Ref"));
			dol_syslog("Account::update ".$this->error, LOG_ERR);
			return -1;
		}
		if (! $this->label) $this->label = "???";

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";

		$sql.= " ref   = '".addslashes($this->ref)."'";
		$sql.= ",label = '".addslashes($this->label)."'";

		$sql.= ",courant = ".$this->courant;
		$sql.= ",clos = ".$this->clos;
		$sql.= ",rappro = ".$this->rappro;
		$sql.= ",url = ".($this->url?"'".$this->url."'":"null");
		$sql.= ",account_number = '".$this->account_number."'";

		$sql.= ",currency_code = '".$this->currency_code."'";

		$sql.= ",min_allowed = '".price2num($this->min_allowed)."'";
		$sql.= ",min_desired = '".price2num($this->min_desired)."'";
		$sql.= ",comment     = '".addslashes($this->comment)."'";

		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Account::update sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db.' sql='.$sql;
			dol_print_error($this->error, LOG_ERR);
			return -1;
		}
	}


	/*
	 *    	\brief      Update BBAN (RIB) account fields
	 *    	\param      user        Object utilisateur qui modifie
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function update_bban($user='')
	{
		global $langs;

		// Chargement librairie pour acces fonction controle RIB
		require_once(DOL_DOCUMENT_ROOT.'/lib/bank.lib.php');

		dol_syslog("Account::update_bban $this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban");

		// Check parameters
		if (! $this->ref)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Ref"));
			return -2;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";
		$sql.= " bank  = '".addslashes($this->bank)."'";
		$sql.= ",code_banque='".$this->code_banque."'";
		$sql.= ",code_guichet='".$this->code_guichet."'";
		$sql.= ",number='".$this->number."'";
		$sql.= ",cle_rib='".$this->cle_rib."'";
		$sql.= ",bic='".$this->bic."'";
		$sql.= ",iban_prefix = '".$this->iban."'";
		$sql.= ",domiciliation='".addslashes($this->domiciliation)."'";
		$sql.= ",proprio = '".addslashes($this->proprio)."'";
		$sql.= ",adresse_proprio = '".addslashes($this->adresse_proprio)."'";
		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Account::update_bban sql=$sql");

		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db.' sql='.$sql;
			dol_print_error($this->error, LOG_ERR);
			return -1;
		}
	}


	/*
	 *      \brief      Charge un compte en memoire depuis la base
	 *      \param      id      Id du compte a recuperer
	 *      \param      ref     Ref du compte a recuperer
	 */
	function fetch($id,$ref='')
	{
		global $conf;
		
		$sql = "SELECT rowid, ref, label, bank, number, courant, clos, rappro, url,";
		$sql.= " code_banque, code_guichet, cle_rib, bic, iban_prefix as iban,";
		$sql.= " domiciliation, proprio, adresse_proprio,";
		$sql.= " account_number, currency_code,";
		$sql.= " min_allowed, min_desired, comment";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		if ($id)  $sql.= " AND rowid  = ".$id;
		if ($ref) $sql.= " AND ref = '".addslashes($ref)."'";

		dol_syslog("Account::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id            = $obj->rowid;		// deprecated
				$this->rowid         = $obj->rowid;
				$this->ref           = $obj->ref;
				$this->label         = $obj->label;
				$this->type          = $obj->courant;
				$this->courant       = $obj->courant;
				$this->bank          = $obj->bank;
				$this->clos          = $obj->clos;
				$this->rappro        = $obj->rappro;
				$this->url           = $obj->url;

				$this->code_banque   = $obj->code_banque;
				$this->code_guichet  = $obj->code_guichet;
				$this->number        = $obj->number;
				$this->cle_rib       = $obj->cle_rib;
				$this->bic           = $obj->bic;
				$this->iban          = $obj->iban;
				$this->iban_prefix   = $obj->iban;	// deprecated
				$this->domiciliation = $obj->domiciliation;
				$this->proprio       = $obj->proprio;
				$this->adresse_proprio = $obj->adresse_proprio;

				$this->account_number = $obj->account_number;

				$this->currency_code  = $obj->currency_code;
				$this->min_allowed    = $obj->min_allowed;
				$this->min_desired    = $obj->min_desired;
				$this->comment        = $obj->comment;
				return 1;
			}
			else
			{
				return 0;
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/*
	 *    \brief      Efface le compte
	 */
	function delete()
	{
		global $conf;
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE rowid  = ".$this->rowid;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Account::delete sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		}
		else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    \brief      Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *    \param      mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->clos,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string        	Libelle du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('banks');

		if ($mode == 0)
		{
			if ($statut==0) return $langs->trans("StatusAccountOpened");
			if ($statut==1) return $langs->trans("StatusAccountClosed");
		}
		if ($mode == 1)
		{
			if ($statut==0) return $langs->trans("StatusAccountOpened");
			if ($statut==1) return $langs->trans("StatusAccountClosed");
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4').' '.$langs->trans("StatusAccountOpened");
			if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5').' '.$langs->trans("StatusAccountClosed");
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4');
			if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4').' '.$langs->trans("StatusAccountOpened");
			if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5').' '.$langs->trans("StatusAccountClosed");
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans("StatusAccountOpened").' '.img_picto($langs->trans("StatusAccountOpened"),'statut4');
			if ($statut==1) return $langs->trans("StatusAccountClosed").' '.img_picto($langs->trans("StatusAccountClosed"),'statut5');
		}
	}


	/*
	 *    \brief      Renvoi si un compte peut etre supprimer ou non (sans mouvements)
	 *    \return     boolean     vrai si peut etre supprime, faux sinon
	 */
	function can_be_deleted()
	{
		$can_be_deleted=false;

		$sql = "SELECT COUNT(rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE fk_account=".$this->id;
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj=$this->db->fetch_object($resql);
			if ($obj->nb <= 1) $can_be_deleted=true;    // Juste le solde
		}
		else {
			dol_print_error($this->db);
		}
		return $can_be_deleted;
	}


	/*
	 *
	 */
	function error()
	{
		return $this->error;
	}

	/**
	 * 	\brief		Return current sold
	 * 	\param		option		1=Exclude future operation date (this is to exclude input made in advance and have real account sold)
	 *	\return		int			Current sold (value date <= today)
	 */
	function solde($option=0)
	{
		$sql = "SELECT sum(amount) as amount";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE fk_account = ".$this->id;
		if ($option == 1) $sql.= " AND dateo <= ".$this->db->idate(time());

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj=$this->db->fetch_object($resql);
				$solde = $obj->amount;
			}
			$this->db->free($resql);
			return $solde;
		}
	}

	/*
	 *
	 */
	function datev_next($rowid)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";
		$sql.= " datev = adddate(datev, interval 1 day)";
		$sql.= " WHERE rowid = ".$rowid;

		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->affected_rows())
			{
				return 1;
			}
		}
		else
		{
			dol_print_error($this->db);
			return 0;
		}
	}

	/*
	 *
	 */
	function datev_previous($rowid)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";
		$sql.= " datev = adddate(datev, interval -1 day)";
		$sql.= " WHERE rowid = ".$rowid;

		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->affected_rows())
			{
				return 1;
			}
		}
		else
		{
			dol_print_error($this->db);
			return 0;
		}
	}


	/**
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        		Objet user
	 *		\param		filteraccountid		To get info for a particular account id
	 *      \return     int         		<0 si ko, >0 si ok
	 */
	function load_board($user,$filteraccountid=0)
	{
		global $conf;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$now=gmmktime();

		$this->nbtodo=$this->nbtodolate=0;
		
		$sql = "SELECT b.rowid, b.datev as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.rappro=0";
		$sql.= " AND b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND (ba.rappro = 1 AND ba.courant != 2)";	// Compte rapprochable
		if ($filteraccountid) $sql.=" AND ba.rowid = ".$filteraccountid;

		//print $sql;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->bank->rappro->warning_delay)) $this->nbtodolate++;
			}
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowAccount"),'account').$lienfin.' ');
		$result.=$lien.$this->label.$lienfin;
		return $result;
	}

	/**
	 * 	\brief		Return account country code
	 *	\return		String		country code
	 */
	function getCountryCode()
	{
		global $mysoc;

		if (! empty($this->iban))
		{
			// If IBAN defined, we can know country of account from it
			if (eregi("^([a-zA-Z][a-zA-Z])",$this->iban,$reg)) return $reg[1];
		}

		// We return country code
		if (! empty($mysoc->pays_code)) return $mysoc->pays_code;

		return '';
	}

	/**
	 * 	\brief		Return if a bank account is defined with detailed information (bank code, desk code, number and key)
	 * 	\return		boolean		true or false
	 */
	function useDetailedBBAN()
	{
		$country_code=$this->getCountryCode();

		if ($country_code == 'FR') return true;
		if ($country_code == 'ES') return true;

		return false;
	}

}


/**
 *	\class      AccountLine
 *	\brief      Classe permettant la gestion des lignes de transactions bancaires
 */
class AccountLine
{
	var $error;
	var $db;

	var $id;
	var $ref;
	var $datec;
	var $dateo;
	var $datev;
	var $amount;
	var $label;
	var $fk_account;
	var $note;
	var $fk_user_author;
	var $fk_user_rappro;
	var $num_releve;
	var $num_chq;
	var $rappro;

	var $bank_account_label;


	/**
	 *  Constructeur
	 */
	function AccountLine($DB, $rowid=0)
	{
		global $langs;

		$this->db = $DB;
		$this->rowid = $rowid;

		return 1;
	}

	/**
	 *      \brief      Charge en memoire depuis la base, une ecriture sur le compte
	 *      \param      id      Id de la ligne ecriture a recuperer
	 *		\return		int		<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;
		
		$sql = "SELECT b.datec, b.datev, b.dateo, b.amount, b.label as label, b.fk_account,";
		$sql.= " b.fk_user_author, b.fk_user_rappro,";
		$sql.= " b.fk_type, b.num_releve, b.num_chq, b.rappro, b.note,";
		$sql.= " ba.label as bank_account_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.rowid  = ".$rowid;

		dol_syslog("AccountLine::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id            = $rowid;
				$this->rowid         = $rowid;
				$this->ref           = $rowid;

				$this->datec         = $obj->datec;
				$this->datev         = $obj->datev;
				$this->dateo         = $obj->dateo;
				$this->amount        = $obj->amount;
				$this->label         = $obj->label;
				$this->fk_account    = $obj->fk_account;
				$this->note          = $obj->note;

				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_rappro = $obj->fk_user_rappro;

				$this->fk_type        = $obj->fk_type;
				$this->num_releve     = $obj->num_releve;
				$this->num_chq        = $obj->num_chq;

				$this->rappro        = $obj->rappro;

				$this->bank_account_label = $obj->bank_account_label;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      \brief      Efface ligne bancaire
	 *		\param		user	User object that delete
	 *      \return		int 	<0 si KO, >0 si OK
	 */
	function delete($user=0)
	{
		$nbko=0;

		if ($this->rappro)
		{
			// Protection pour eviter tout suppression d'une ligne consolid�e
			$this->error="DeleteNotPossibleLineIsConsolidated";
			return -1;
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=".$this->rowid;
		dol_syslog("AccountLine::delete sql=".$sql);
		$result = $this->db->query($sql);
		if (! $result) $nbko++;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank=".$this->rowid;
		dol_syslog("AccountLine::delete sql=".$sql);
		$result = $this->db->query($sql);
		if (! $result) $nbko++;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".$this->rowid;
		dol_syslog("AccountLine::delete sql=".$sql);
		$result = $this->db->query($sql);
		if (! $result) $nbko++;

		if (! $nbko)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -$nbko;
		}
	}


	/**
	 *		\brief 		Met a jour en base la ligne
	 *		\param 		user			Objet user qui met a jour
	 *		\param 		notrigger		0=Desactive les triggers
	 *		\param		int				<0 if KO, >0 if OK
	 */
	function update($user,$notrigger=0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
		$sql.= " amount = ".price2num($this->amount).",";
		$sql.= " datev='".$this->db->idate($this->datev)."',";
		$sql.= " dateo='".$this->db->idate($this->dateo)."'";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("AccountLine::update sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dol_syslog("AccountLine::update ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *		\brief 		Update conciliation field
	 *		\param 		user			Objet user making update
	 *		\param 		cat				Category id
	 *		\param		int				<0 if KO, >0 if OK
	 */
	function update_conciliation($user,$cat)
	{
		$this->db->begin();

	    $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
        $sql.= " rappro = 1";
        $sql.= ", num_releve = '".$this->num_releve."'";
        $sql.= ", fk_user_rappro = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("AccountLine::update_conciliation sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	if (! empty($cat))
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
                $sql.= "lineid";
                $sql.= ", fk_categ";
                $sql.= ") VALUES (";
                $sql.= $this->id;
                $sql.= ", ".$cat;
                $sql.= ")";

                dol_syslog("AccountLine::update_conciliation sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // No error check. Can fail if category already affected
            }

        	$bankline->rappro=1;

        	$this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
	}

	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       Id de la facture a charger
	 */
	function info($rowid)
	{
		$sql = 'SELECT b.rowid, '.$this->db->pdate('b.datec').' as datec,';
		$sql.= ' b.fk_user_author, b.fk_user_rappro';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'bank as b';
		$sql.= ' WHERE b.rowid = '.$rowid;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_rappro)
				{
					$ruser = new User($this->db, $obj->fk_user_rappro);
					$ruser->fetch();
					$this->user_rappro = $ruser;
				}

				$this->date_creation     = $obj->datec;
				//$this->date_rappro       = $obj->daterappro;    // \todo pas encore g�r�e
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		maxlen			Longueur max libelle
	 *		\param		option			Option ('showall')
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlen=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$this->rowid.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowTransaction"),'account').$lienfin.' ');
		$result.=$lien.$this->rowid.$lienfin;

		if ($option == 'showall')
		{
			$result.=' (';
			$result.=$langs->trans("BankAccount").': ';
			$accountstatic=new Account($this->db);
			$accountstatic->id=$this->fk_account;
			$accountstatic->label=$this->bank_account_label;
			$result.=$accountstatic->getNomUrl(0).', ';
			$result.=$langs->trans("BankLineConciliated").': ';
	    	$result.=yn($this->rappro);
	    	$result.=')';
		}

		return $result;
	}

}

?>
