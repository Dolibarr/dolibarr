<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016 Ferran Marcet       <fmarcet@2byte.es>
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
 *      \file       htdocs/compta/prelevement/class/bonprelevement.class.php
 *      \ingroup    prelevement
 *      \brief      File of withdrawal receipts class
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';



/**
 *	Class to manage withdrawal receipts
 */
class BonPrelevement extends CommonObject
{
	public $element='widthdraw';
	public $table_element='prelevement_bons';
	public $picto = 'payment';

	var $date_echeance;
	var $raison_sociale;
	var $reference_remise;
	var $emetteur_code_guichet;
	var $emetteur_numero_compte;
	var $emetteur_code_banque;
	var $emetteur_number_key;

	var $emetteur_iban;
	var $emetteur_bic;
	var $emetteur_ics;

	var $total;
	var $_fetched;
	var $statut;    // 0-Wait, 1-Trans, 2-Done
	var $labelstatut=array();

	var $invoice_in_error=array();
	var $thirdparty_in_error=array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      	Database handler
	 *  @param		string		$filename	Filename of withdraw receipt
	 */
	function __construct($db, $filename='')
	{
		global $conf,$langs;

		$error = 0;
		$this->db = $db;

		$this->filename=$filename;

		$this->date_echeance = time();
		$this->raison_sociale = "";
		$this->reference_remise = "";

		$this->emetteur_code_guichet = "";
		$this->emetteur_numero_compte = "";
		$this->emetteur_code_banque = "";
		$this->emetteur_number_key = "";

		$this->emetteur_iban = "";
		$this->emetteur_bic = "";
		$this->emetteur_ics = "";

		$this->factures = array();

		$this->methodes_trans = array();

		$this->methodes_trans[0] = "Internet";

		$this->_fetched = 0;


		$langs->load("withdrawals");
		$this->labelstatut[0]=$langs->trans("StatusWaiting");
		$this->labelstatut[1]=$langs->trans("StatusTrans");
		$this->labelstatut[2]=$langs->trans("StatusCredited");

		return 1;
	}

	/**
	 * Add invoice to withdrawal
	 *
	 * @param	int		$facture_id 	id invoice to add
	 * @param	int		$client_id  	id invoice customer
	 * @param	string	$client_nom 	customer name
	 * @param	int		$amount 		amount of invoice
	 * @param	string	$code_banque 	code of bank withdrawal
	 * @param	string	$code_guichet 	code of bank's office
	 * @param	string	$number bank 	account number
	 * @param	string	$number_key 	number key of account number
	 * @return	int						>0 if OK, <0 if KO
	 */
	function AddFacture($facture_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key)
	{
		$result = 0;
		$line_id = 0;

		$result = $this->addline($line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key);

		if ($result == 0)
		{
			if ($line_id > 0)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_facture (";
				$sql.= "fk_facture";
				$sql.= ",fk_prelevement_lignes";
				$sql.= ") VALUES (";
				$sql.= $facture_id;
				$sql.= ", ".$line_id;
				$sql.= ")";

				if ($this->db->query($sql))
				{
					$result = 0;
				}
				else
				{
					$result = -1;
					dol_syslog(get_class($this)."::AddFacture Erreur $result");
				}
			}
			else
			{
				$result = -2;
				dol_syslog(get_class($this)."::AddFacture Erreur $result");
			}
		}
		else
		{
			$result = -3;
			dol_syslog(get_class($this)."::AddFacture Erreur $result");
		}

		return $result;

	}

	/**
	 *	Add line to withdrawal
	 *
	 *	@param	int		$line_id 		id line to add
	 *	@param	int		$client_id  	id invoice customer
	 *	@param	string	$client_nom 	customer name
	 *	@param	int		$amount 		amount of invoice
	 *	@param	string	$code_banque 	code of bank withdrawal
	 *	@param	string	$code_guichet 	code of bank's office
	 *	@param	string	$number 		bank account number
	 *	@param  string	$number_key 	number key of account number
	 *	@return	int						>0 if OK, <0 if KO
	 */
	function addline(&$line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key)
	{
		$result = -1;
		$concat = 0;

		if ($concat == 1)
		{
			/*
             * We aggregate the lines
             */
			$sql = "SELECT rowid";
			$sql.= " FROM  ".MAIN_DB_PREFIX."prelevement_lignes";
			$sql.= " WHERE fk_prelevement_bons = ".$this->id;
			$sql.= " AND fk_soc =".$client_id;
			$sql.= " AND code_banque ='".$code_banque."'";
			$sql.= " AND code_guichet ='".$code_guichet."'";
			$sql.= " AND number ='".$number."'";

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
			}
			else
			{
				$result = -1;
			}
		}
		else
		{
			/*
             * No aggregate
             */
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_lignes (";
			$sql.= "fk_prelevement_bons";
			$sql.= ", fk_soc";
			$sql.= ", client_nom";
			$sql.= ", amount";
			$sql.= ", code_banque";
			$sql.= ", code_guichet";
			$sql.= ", number";
			$sql.= ", cle_rib";
			$sql.= ") VALUES (";
			$sql.= $this->id;
			$sql.= ", ".$client_id;
			$sql.= ", '".$this->db->escape($client_nom)."'";
			$sql.= ", '".price2num($amount)."'";
			$sql.= ", '".$code_banque."'";
			$sql.= ", '".$code_guichet."'";
			$sql.= ", '".$number."'";
			$sql.= ", '".$number_key."'";
			$sql.= ")";

			if ($this->db->query($sql))
			{
				$line_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_lignes");
				$result = 0;
			}
			else
			{
				dol_syslog(get_class($this)."::addline Error -2");
				$result = -2;
			}

		}

		return $result;
	}

	/**
	 *	Return error string
	 *
	 *  @param	int		$error 		 Id of error
	 *	@return	string               Error string
	 */
	function getErrorString($error)
	{
		global $langs;

		$errors = array();

		$errors[1027] = $langs->trans("DateInvalid");

		return $errors[abs($error)];
	}

	/**
	 *	Get object and lines from database
	 *
	 *	@param	int		$rowid		Id of object to load
	 *  @param	string	$ref		Ref of direct debit
	 *	@return	int					>0 if OK, <0 if KO
	 */
	function fetch($rowid, $ref='')
	{
		global $conf;

		$sql = "SELECT p.rowid, p.ref, p.amount, p.note";
		$sql.= ", p.datec as dc";
		$sql.= ", p.date_trans as date_trans";
		$sql.= ", p.method_trans, p.fk_user_trans";
		$sql.= ", p.date_credit as date_credit";
		$sql.= ", p.fk_user_credit";
		$sql.= ", p.statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
		$sql.= " WHERE p.entity = ".$conf->entity;
		if ($rowid > 0) $sql.= " AND p.rowid = ".$rowid;
		else $sql.= " AND p.ref = '".$this->db->escape($ref)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                 = $obj->rowid;
				$this->ref                = $obj->ref;
				$this->amount             = $obj->amount;
				$this->note               = $obj->note;
				$this->datec              = $this->db->jdate($obj->dc);

				$this->date_trans         = $this->db->jdate($obj->date_trans);
				$this->method_trans       = $obj->method_trans;
				$this->user_trans         = $obj->fk_user_trans;

				$this->date_credit        = $this->db->jdate($obj->date_credit);
				$this->user_credit        = $obj->fk_user_credit;

				$this->statut             = $obj->statut;

				$this->_fetched = 1;

				return 1;
			}
			else
			{
				dol_syslog(get_class($this)."::Fetch Erreur aucune ligne retournee");
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	/**
	 * Set credite and set status of linked invoices. Still used ??
	 *
	 * @return		int		<0 if KO, >=0 if OK
	 */
	function set_credite()
	{
		global $user,$conf;

		$error = 0;

		if ($this->db->begin())
		{
			$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
			$sql.= " SET statut = 1";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if (! $result)
			{
				dol_syslog(get_class($this)."::set_credite Erreur 1");
				$error++;
			}

			if (! $error)
			{
				$facs = array();
				$facs = $this->getListInvoices();

				$num=count($facs);
				for ($i = 0; $i < $num; $i++)
				{
					/* Tag invoice as payed */
					dol_syslog(get_class($this)."::set_credite set_paid fac ".$facs[$i]);
					$fac = new Facture($this->db);
					$fac->fetch($facs[$i]);
					$result = $fac->set_paid($user);
				}
			}

			if (! $error)
			{
				$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
				$sql.= " SET statut = 2";
				$sql.= " WHERE fk_prelevement_bons = ".$this->id;

				if (! $this->db->query($sql))
				{
					dol_syslog(get_class($this)."::set_credite Erreur 1");
					$error++;
				}
			}

			/*
             * End of procedure
             */
			if (! $error)
			{
				$this->db->commit();
				return 0;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::set_credite ROLLBACK ");

				return -1;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::set_credite Ouverture transaction SQL impossible ");
			return -2;
		}
	}

	/**
	 *	Set direct debit order to "credited" status.
	 *
	 *	@param	User	$user			Id of user
	 *	@param 	int		$date			date of action
	 *	@return	int						>0 if OK, <0 if KO
	 */
	function set_infocredit($user, $date)
	{
		global $conf,$langs;

		$error = 0;

		if ($this->_fetched == 1)
		{
			if ($date >= $this->date_trans)
			{
				if ($this->db->begin())
				{
					$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
					$sql.= " SET fk_user_credit = ".$user->id;
					$sql.= ", statut = 2";
					$sql.= ", date_credit = '".$this->db->idate($date)."'";
					$sql.= " WHERE rowid=".$this->id;
					$sql.= " AND entity = ".$conf->entity;
					$sql.= " AND statut = 1";

					if ($this->db->query($sql))
					{

						$langs->load('withdrawals');
						$subject = $langs->trans("InfoCreditSubject", $this->ref);
						$message = $langs->trans("InfoCreditMessage", $this->ref, dol_print_date($date,'dayhour'));

						//Add payment of withdrawal into bank
						$bankaccount = $conf->global->PRELEVEMENT_ID_BANKACCOUNT;
						$facs = array();
						$amounts = array();
						$amountsperthirdparty = array();

						$facs = $this->getListInvoices(1);

						// Loop on each invoice. $facs=array(0=>id, 1=>amount requested)
						$num=count($facs);
						for ($i = 0; $i < $num; $i++)
						{
							$fac = new Facture($this->db);
							$fac->fetch($facs[$i][0]);
							$amounts[$fac->id] = $facs[$i][1];
							$amountsperthirdparty[$fac->socid][$fac->id] = $facs[$i][1];

							$totalpaye  = $fac->getSommePaiement();
							$totalcreditnotes = $fac->getSumCreditNotesUsed();
							$totaldeposits = $fac->getSumDepositsUsed();
							$alreadypayed = $totalpaye + $totalcreditnotes + $totaldeposits;

							if (price2num($alreadypayed + $facs[$i][1], 'MT') == $fac->total_ttc) {
								$result = $fac->set_paid($user);
							}
						}

						// Make one payment per customer
						foreach ($amountsperthirdparty as $thirdpartyid => $cursoramounts)
						{
							$paiement = new Paiement($this->db);
							$paiement->datepaye     = $date;
							$paiement->amounts      = $cursoramounts;		// Array with detail of dispatching of payments for each invoice
							$paiement->paiementid   = 3; 					//
							$paiement->num_paiement = $this->ref;			// Set ref of direct debit note
							$paiement->id_prelevement = $this->id;

							$paiement_id = $paiement->create($user);
							if ($paiement_id < 0)
							{
								dol_syslog(get_class($this)."::set_infocredit AddPayment Error");
								$error++;
							}
							else
							{
								$result=$paiement->addPaymentToBank($user,'payment','(WithdrawalPayment)',$bankaccount,'','');
								if ($result < 0)
								{
									dol_syslog(get_class($this)."::set_infocredit AddPaymentToBank Error");
									$error++;
								}
							}
							//var_dump($paiement->amounts);
							//var_dump($thirdpartyid);
							//var_dump($cursoramounts);
						}

						// Update withdrawal line
						// TODO: Translate to ligneprelevement.class.php
						$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
						$sql.= " SET statut = 2";
						$sql.= " WHERE fk_prelevement_bons = ".$this->id;

						if (! $this->db->query($sql))
						{
							dol_syslog(get_class($this)."::set_infocredit Update lines Error");
							$error++;
						}

					}
					else
					{
						dol_syslog(get_class($this)."::set_infocredit Update Bons Error");
						$error++;
					}

					/*
                     * End of procedure
                     */
					if ($error == 0)
					{
						$this->date_credit = $date;
						$this->statut = 1;

						$this->db->commit();
						return 0;
					}
					else
					{
						$this->db->rollback();
						dol_syslog("bon-prelevment::set_infocredit ROLLBACK ");
						return -1;
					}
				}
				else
				{
					dol_syslog(get_class($this)."::set_infocredit 1025 Open SQL transaction impossible ");
					return -1025;
				}
			}
			else
			{
				dol_syslog("bon-prelevment::set_infocredit 1027 Date de credit < Date de trans ");
				return -1027;
			}
		}
		else
		{
			return -1026;
		}
	}

	/**
	 *	Set withdrawal to transmited status
	 *
	 *	@param	User		$user		id of user
	 *	@param 	int	$date		date of action
	 *	@param	string		$method		method of transmision to bank
	 *	@return	int						>0 if OK, <0 if KO
	 */
	function set_infotrans($user, $date, $method)
	{
		global $conf,$langs;

		$error = 0;

		dol_syslog(get_class($this)."::set_infotrans Start",LOG_INFO);
		if ($this->db->begin())
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
			$sql.= " SET fk_user_trans = ".$user->id;
			$sql.= " , date_trans = '".$this->db->idate($date)."'";
			$sql.= " , method_trans = ".$method;
			$sql.= " , statut = 1";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND statut = 0";

			if ($this->db->query($sql))
			{
				$this->method_trans = $method;
				$langs->load('withdrawals');
				$subject = $langs->trans("InfoTransSubject", $this->ref);
				$message = $langs->trans("InfoTransMessage", $this->ref, dolGetFirstLastname($user->firstname, $user->lastname));
				$message .=$langs->trans("InfoTransData", price($this->amount), $this->methodes_trans[$this->method_trans], dol_print_date($date,'day'));

				// TODO Call trigger to create a notification using notification module
			}
			else
			{
				$error++;
			}

			if ($error == 0)
			{
				$this->date_trans = $date;
				$this->statut = 1;
				$this->db->commit();

				return 0;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::set_infotrans ROLLBACK", LOG_ERR);

				return -1;
			}
		}
		else
		{

			dol_syslog(get_class($this)."::set_infotrans Ouverture transaction SQL impossible", LOG_CRIT);
			return -2;
		}
	}

	/**
	 *	Get invoice list
	 *
	 *  @param 	int		$amounts 	If you want to get the amount of the order for each invoice
	 *	@return	array 				Id of invoices
	 */
	private function getListInvoices($amounts=0)
	{
		global $conf;

		$arr = array();

		/*
         * Returns all invoices presented
         * within a withdrawal receipt
         */
		$sql = "SELECT fk_facture";
		if ($amounts) $sql .= ", SUM(pl.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
		$sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
		$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture as pf";
		$sql.= " WHERE pf.fk_prelevement_lignes = pl.rowid";
		$sql.= " AND pl.fk_prelevement_bons = p.rowid";
		$sql.= " AND p.rowid = ".$this->id;
		$sql.= " AND p.entity = ".$conf->entity;
		if ($amounts) $sql.= " GROUP BY fk_facture";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					if (!$amounts) $arr[$i] = $row[0];
					else
					{
						$arr[$i] = array(
							$row[0],
							$row[1]
						);
					}
					$i++;
				}
			}
			$this->db->free($resql);
		}
		else
		{
			dol_syslog(get_class($this)."::getListInvoices Erreur");
		}

		return $arr;
	}

	/**
	 *	Returns amount of withdrawal
	 *
	 *	@return		double	 	Total amount
	 */
	function SommeAPrelever()
	{
		global $conf;

		$sql = "SELECT sum(pfd.amount) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
		$sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
		//$sql.= " ,".MAIN_DB_PREFIX."c_paiement as cp";
		$sql.= " WHERE f.fk_statut = 1";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.rowid = pfd.fk_facture";
		$sql.= " AND f.paye = 0";
		$sql.= " AND pfd.traite = 0";
		$sql.= " AND f.total_ttc > 0";

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			return $obj->nb;
		}
		else
		{
			$error = 1;
			dol_syslog(get_class($this)."::SommeAPrelever Erreur -1");
			dol_syslog($this->db->error());
		}
	}

	/**
	 *	Get number of invoices to withdrawal
	 *	TODO delete params banque and agence when not necesary
	 *
	 *	@param	int		$banque		dolibarr mysoc bank
	 *	@param	int		$agence		dolibarr mysoc agence
	 *	@return	int					<O if KO, number of invoices if OK
	 */
	function NbFactureAPrelever($banque=0,$agence=0)
	{
		global $conf;

		$sql = "SELECT count(f.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
		//if ($banque || $agence) $sql.=", ".MAIN_DB_PREFIX."societe_rib as sr";
		$sql.= " WHERE f.fk_statut = 1";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.rowid = pfd.fk_facture";
		$sql.= " AND f.paye = 0";
		$sql.= " AND pfd.traite = 0";
		$sql.= " AND f.total_ttc > 0";
		//if ($banque || $agence) $sql.= " AND f.fk_soc = sr.rowid";
		//if ($banque) $sql.= " AND sr.code_banque = '".$conf->global->PRELEVEMENT_CODE_BANQUE."'";
		//if ($agence) $sql.= " AND sr.code_guichet = '".$conf->global->PRELEVEMENT_CODE_GUICHET."'";

		dol_syslog(get_class($this)."::SommeAPrelever");
		$resql = $this->db->query($sql);

		if ( $resql )
		{
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			return $obj->nb;
		}
		else
		{
			$this->error=get_class($this)."::SommeAPrelever Erreur -1 sql=".$this->db->error();
			return -1;
		}
	}


	/**
	 *	Create a withdraw
	 *  TODO delete params banque and agence when not necesary
	 *
	 *	@param 	int		$banque		dolibarr mysoc bank
	 *	@param	int		$agence		dolibarr mysoc bank office (guichet)
	 *	@param	string	$mode		real=do action, simu=test only
	 *  @param	string	$format		FRST, RCUR or ALL
	 *	@return	int					<0 if KO, nbre of invoice withdrawed if OK
	 */
	function Create($banque=0, $agence=0, $mode='real', $format='ALL')
	{
		global $conf,$langs;

		dol_syslog(__METHOD__."::Bank=".$banque." Office=".$agence." mode=".$mode." format=".$format, LOG_DEBUG);

		require_once (DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		require_once (DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

		if (empty($format)) return 'ErrorBadParametersForDirectDebitFileCreate';

		$error = 0;

		$datetimeprev = time();

		$month = strftime("%m", $datetimeprev);
		$year = strftime("%Y", $datetimeprev);

		$puser = new User($this->db, $conf->global->PRELEVEMENT_USER);

		$this->invoice_in_error = array();
		$this->thirdparty_in_error = array();

		// Read invoices
		$factures = array();
		$factures_prev = array();
		$factures_result = array();
		$factures_prev_id=array();
		$factures_errors=array();

		if (! $error)
		{
			$sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
			$sql.= ", pfd.code_banque, pfd.code_guichet, pfd.number, pfd.cle_rib";
			$sql.= ", pfd.amount";
			$sql.= ", s.nom as name";
			$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
			$sql.= ", ".MAIN_DB_PREFIX."societe as s";
			$sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
			//if ($banque || $agence) $sql.= ", ".MAIN_DB_PREFIX."societe_rib as sr";
			$sql.= " WHERE f.rowid = pfd.fk_facture";
			$sql.= " AND f.entity = ".$conf->entity;
			$sql.= " AND s.rowid = f.fk_soc";
			//if ($banque || $agence) $sql.= " AND s.rowid = sr.fk_soc";
			$sql.= " AND f.fk_statut = 1";
			$sql.= " AND f.paye = 0";
			$sql.= " AND pfd.traite = 0";
			$sql.= " AND f.total_ttc > 0";
			//if ($banque) $sql.= " AND sr.code_banque = '".$conf->global->PRELEVEMENT_CODE_BANQUE."'";
			//if ($agence) $sql.= " AND sr.code_guichet = '".$conf->global->PRELEVEMENT_CODE_GUICHET."'";

			dol_syslog(__METHOD__."::Read invoices, sql=".$sql, LOG_DEBUG);

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;

				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$factures[$i] = $row;	// All fields
					$i++;
				}
				$this->db->free($resql);
				dol_syslog(__METHOD__."::Read invoices, ".$i." invoices to withdraw", LOG_DEBUG);
			}
			else
			{
				$error++;
				dol_syslog(__METHOD__."::Read invoices error ".$this->db->error(), LOG_ERR);
			}
		}

		if (! $error)
		{
			require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
			$soc = new Societe($this->db);

			// Check RIB
			$i = 0;
			dol_syslog(__METHOD__."::Check RIB", LOG_DEBUG);

			if (count($factures) > 0)
			{
				foreach ($factures as $key => $fac)
				{
					$fact = new Facture($this->db);
					$resfetch = $fact->fetch($fac[0]);
					if ($resfetch >= 0)		// Field 0 of $fac is rowid of invoice
					{
						if ($soc->fetch($fact->socid) >= 0)
						{
							$bac = new CompanyBankAccount($this->db);
							$bac->fetch(0, $soc->id);

							if ($format == 'FRST' && $bac->frstrecur != 'FRST')
							{
								continue;
							}
							if ($format == 'RCUR' && ($bac->frstrecur != 'RCUR' && $bac->frstrecur != 'RECUR'))
							{
								continue;
							}

							if ($bac->verif() >= 1)
							{
								$factures_prev[$i] = $fac;
								/* second tableau necessaire pour BonPrelevement */
								$factures_prev_id[$i] = $fac[0];
								$i++;
								//dol_syslog(__METHOD__."::RIB is ok", LOG_DEBUG);
							}
							else
							{
								dol_syslog(__METHOD__."::Check RIB Error on default bank number IBAN/BIC for thirdparty reported by verif() ".$fact->socid." ".$soc->name, LOG_WARNING);
								$this->invoice_in_error[$fac[0]]="Error on default bank number IBAN/BIC for invoice ".$fact->getNomUrl(0)." for thirdparty ".$soc->getNomUrl(0);
								$this->thirdparty_in_error[$soc->id]="Error on default bank number IBAN/BIC for invoice ".$fact->getNomUrl(0)." for thirdparty ".$soc->getNomUrl(0);
							}
						}
						else
						{
							dol_syslog(__METHOD__."::Check RIB Failed to read company", LOG_WARNING);
						}
					}
					else
					{
						dol_syslog(__METHOD__."::Check RIB Failed to read invoice", LOG_WARNING);
					}
				}
			}
			else
			{
				dol_syslog(__METHOD__."::Check RIB No invoice to process", LOG_WARNING);
			}
		}

		$ok=0;

		// Withdraw invoices in factures_prev array
		$out=count($factures_prev)." invoices will be withdrawn.";
		//print $out."\n";
		dol_syslog($out);

		// Return warning
		/*$i=0;
        foreach ($this->thirdparty_in_error as $key => $val)
        {
        	if ($i < 10) setEventMessages($val, null, 'warnings');
        	else setEventMessages('More error were discarded...', null, 'warnings');
        	$i++;
        }*/

		if (count($factures_prev) > 0)
		{
			if ($mode=='real')
			{
				$ok=1;
			}
			else
			{
				print $langs->trans("ModeWarning"); //"Option for real mode was not set, we stop after this simulation\n";
			}
		}


		if ($ok)
		{
			/*
             * We are in real mode.
             * We create withdraw receipt and build withdraw into disk
             */
			$this->db->begin();

			$now=dol_now();

			/*
             * Traitements
             */
			if (!$error)
			{
				$ref = substr($year,-2).$month;

				$sql = "SELECT substring(ref from char_length(ref) - 1)";
				$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons";
				$sql.= " WHERE ref LIKE '%".$this->db->escape($ref)."%'";
				$sql.= " AND entity = ".$conf->entity;
				$sql.= " ORDER BY ref DESC LIMIT 1";

				dol_syslog(get_class($this)."::Create sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);

				if ($resql)
				{
					$row = $this->db->fetch_row($resql);
					$ref = "T".$ref.str_pad(dol_substr("00".intval($row[0])+1,0,2),2,"0",STR_PAD_LEFT);

					$dir=$conf->prelevement->dir_output.'/receipts';
					if (! is_dir($dir)) dol_mkdir($dir);

					$this->filename = $dir.'/'.$ref.'.xml';

					// Create withdraw receipt in database
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_bons (";
					$sql.= " ref, entity, datec";
					$sql.= ") VALUES (";
					$sql.= "'".$this->db->escape($ref)."'";
					$sql.= ", ".$conf->entity;
					$sql.= ", '".$this->db->idate($now)."'";
					$sql.= ")";

					$resql = $this->db->query($sql);
					if ($resql)
					{
						$prev_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_bons");
						$this->id = $prev_id;
						$this->ref = $ref;
					}
					else
					{
						$error++;
						dol_syslog(__METHOD__."::Create withdraw receipt ".$this->db->lasterror(), LOG_ERR);
					}
				}
				else
				{
					$error++;
					dol_syslog(__METHOD__."::Get last withdraw receipt ".$this->db->lasterror(), LOG_ERR);
				}
			}

			if (!$error)
			{
				/*
	             * Create withdrawal receipt in database
	             */
				if (count($factures_prev) > 0)
				{
					foreach ($factures_prev as $fac)	// Add a link in database for each invoice
					{
						// Fetch invoice
						$fact = new Facture($this->db);
						$fact->fetch($fac[0]);
						/*
                         * Add standing order
                         *
                         *
                         * $fac[3] : banque
                         * $fac[4] : guichet
                         * $fac[5] : number
                         * $fac[6] : cle rib
                         * $fac[7] : amount
                         * $fac[8] : client nom
                         * $fac[2] : client id
                         */
						$ri = $this->AddFacture($fac[0], $fac[2], $fac[8], $fac[7], $fac[3], $fac[4], $fac[5], $fac[6]);
						if ($ri <> 0)
						{
							$error++;
						}

						// Update invoice requests as done
						$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_facture_demande";
						$sql.= " SET traite = 1";
						$sql.= ", date_traite = '".$this->db->idate($now)."'";
						$sql.= ", fk_prelevement_bons = ".$this->id;
						$sql.= " WHERE rowid = ".$fac[1];

						dol_syslog(__METHOD__."::Update Orders::Sql=".$sql, LOG_DEBUG);
						$resql=$this->db->query($sql);

						if (! $resql)
						{
							$error++;
							dol_syslog(__METHOD__."::Update Orders::Error=".$this->db->error(), LOG_ERR);
						}

					}
				}

			}

			if (!$error)
			{
				/*
                 * Create direct debit order in a XML file
                 */

				dol_syslog(__METHOD__."::Init withdraw receipt for ".count($factures_prev)." invoices", LOG_DEBUG);


				if (count($factures_prev) > 0)
				{
					$this->date_echeance = $datetimeprev;
					$this->reference_remise = $ref;

					$id=$conf->global->PRELEVEMENT_ID_BANKACCOUNT;
					$account = new Account($this->db);
					if ($account->fetch($id)>0)
					{
						$this->emetteur_code_banque 	   = $account->code_banque;
						$this->emetteur_code_guichet       = $account->code_guichet;
						$this->emetteur_numero_compte      = $account->number;
						$this->emetteur_number_key		   = $account->cle_rib;
						$this->emetteur_iban               = $account->iban;
						$this->emetteur_bic                = $account->bic;

						$this->emetteur_ics                = $conf->global->PRELEVEMENT_ICS;		// Ex: PRELEVEMENT_ICS = "FR78ZZZ123456";

						$this->raison_sociale              = $account->proprio;
					}

					$this->factures = $factures_prev_id;

					// Generation of SEPA file $this->filename
					$this->generate($format);
				}
				dol_syslog(__METHOD__."::End withdraw receipt, file ".$this->filename, LOG_DEBUG);
			}
			//var_dump($factures_prev);exit;

			/*
             * Update total
             */
			$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
			$sql.= " SET amount = ".price2num($this->total);
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$error++;
				dol_syslog(__METHOD__."::Error update total: ".$this->db->error(), LOG_ERR);
			}

			if (!$error)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
			}

			return count($factures_prev);
		}
		else
		{
			return 0;
		}
	}


	/**
	 *	Get object and lines from database
	 *
	 *  @param	User	$user		Object user that delete
	 *	@return	int					>0 if OK, <0 if KO
	 */
	function delete($user=null)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_facture WHERE fk_prelevement_lignes IN (SELECT rowid FROM ".MAIN_DB_PREFIX."prelevement_lignes WHERE fk_prelevement_bons = ".$this->id.")";
		$resql1=$this->db->query($sql);
		if (! $resql1) dol_print_error($this->db);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_lignes WHERE fk_prelevement_bons = ".$this->id;
		$resql2=$this->db->query($sql);
		if (! $resql2) dol_print_error($this->db);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_bons WHERE rowid = ".$this->id;
		$resql3=$this->db->query($sql);
		if (! $resql3) dol_print_error($this->db);

		$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_facture_demande SET fk_prelevement_bons = NULL, traite = 0 WHERE fk_prelevement_bons = ".$this->id;
		$resql4=$this->db->query($sql);
		if (! $resql4) dol_print_error($this->db);

		if ($resql1 && $resql2 && $resql3)
		{
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
	 *	Returns clickable name (with picto)
	 *
	 *	@param	int		$withpicto	link with picto
	 *	@param	string	$option		link target
	 *	@return	string				URL of target
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
		$label = $langs->trans("ShowWithdraw").': '.$this->ref;

		$link = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		if ($option == 'xxx')
		{
			$link = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend='</a>';
		}

		if ($withpicto) $result.=($link.img_object($label, 'payment', 'class="classfortooltip"').$linkend.' ');
		$result.=$link.$this->ref.$linkend;
		return $result;
	}


	/**
	 *	Delete a notification def by id
	 *
	 *	@param	int		$rowid		id of notification
	 *	@return	int					0 if OK, <0 if KO
	 */
	function DeleteNotificationById($rowid)
	{
		$result = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql.= " WHERE rowid = '".$rowid."'";

		if ($this->db->query($sql))
		{
			return 0;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Delete a notification
	 *
	 *	@param	int	$user		notification user
	 *	@param	string	$action		notification action
	 *	@return	int					>0 if OK, <0 if KO
	 */
	function DeleteNotification($user, $action)
	{
		$result = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql .= " WHERE fk_user=".$user." AND fk_action='".$action."'";

		if ($this->db->query($sql))
		{
			return 0;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Add a notification
	 *
	 *	@param	DoliDB	$db			database handler
	 *	@param	int	$user		notification user
	 *	@param	string	$action		notification action
	 *	@return	int					0 if OK, <0 if KO
	 */
	function AddNotification($db, $user, $action)
	{
		$result = 0;

		if ($this->DeleteNotification($user, $action) == 0)
		{
			$now=dol_now();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_user, fk_soc, fk_contact, fk_action)";
			$sql .= " VALUES (".$db->idate($now).",".$user.", 'NULL', 'NULL', '".$action."')";

			dol_syslog("adnotiff: ".$sql);
			if ($this->db->query($sql))
			{
				$result = 0;
			}
			else
			{
				$result = -1;
				dol_syslog(get_class($this)."::AddNotification Error $result");
			}
		}

		return $result;
	}


	/**
	 * Generate a withdrawal file.
	 * Generation Formats:
	 * - Europe: SEPA (France: CFONB no more supported, Spain: AEB19 if external module EsAEB is enabled)
	 * - Others countries: Warning message
	 * File is generated with name this->filename
	 *
	 *  @param		string	$format		FRST, RCUR or ALL
	 *	@return		int					0 if OK, <0 if KO
	 */
	function generate($format='ALL')
	{
		global $conf,$langs,$mysoc;

		//TODO: Optimize code to read lines in a single function

		$result = 0;

		dol_syslog(get_class($this)."::generate build file ".$this->filename);

		$this->file = fopen($this->filename,"w");
		if (empty($this->file))
		{
			$this->error=$langs->trans('ErrorFailedToOpenFile', $this->filename);
			return -1;
		}

		$found=0;

		// Build file for European countries
		if ($mysoc->isInEEC())
		{
			$found++;

			/**
			 * SECTION CREATION FICHIER SEPA
			 */
			// SEPA Initialisation
			$CrLf = "\n";
			$date_actu = dol_now();
			$dateTime_YMD  = dol_print_date($date_actu, '%Y%m%d');
			$dateTime_YMDHMS = dol_print_date($date_actu, '%Y%m%d%H%M%S');
			$dateTime_ECMA = dol_print_date($date_actu, '%Y-%m-%dT%H:%M:%S');
			$fileDebiteurSection = '';
			$fileEmetteurSection = '';
			$i = 0;
			$this->total = 0;

			/*
			 * section Debiteur (sepa Debiteurs bloc lines)
			 */

			$sql = "SELECT soc.code_client as code, soc.address, soc.zip, soc.town, c.code as country_code,";
			$sql.= " pl.client_nom as nom, pl.code_banque as cb, pl.code_guichet as cg, pl.number as cc, pl.amount as somme,";
			$sql.= " f.facnumber as fac, pf.fk_facture as idfac, rib.datec, rib.iban_prefix as iban, rib.bic as bic, rib.rowid as drum";
			$sql.= " FROM";
			$sql.= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
			$sql.= " ".MAIN_DB_PREFIX."facture as f,";
			$sql.= " ".MAIN_DB_PREFIX."prelevement_facture as pf,";
			$sql.= " ".MAIN_DB_PREFIX."societe as soc,";
			$sql.= " ".MAIN_DB_PREFIX."c_country as c,";
			$sql.= " ".MAIN_DB_PREFIX."societe_rib as rib";
			$sql.= " WHERE pl.fk_prelevement_bons = ".$this->id;
			$sql.= " AND pl.rowid = pf.fk_prelevement_lignes";
			$sql.= " AND pf.fk_facture = f.rowid";
			$sql.= " AND soc.fk_pays = c.rowid";
			$sql.= " AND soc.rowid = f.fk_soc";
			$sql.= " AND rib.fk_soc = f.fk_soc";
			$sql.= " AND rib.default_rib = 1";
			//print $sql;

			// Define $fileDebiteurSection. One section DrctDbtTxInf per invoice.
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$fileDebiteurSection .= $this->EnregDestinataireSEPA($obj->code, $obj->nom, $obj->address, $obj->zip, $obj->town, $obj->country_code, $obj->cb, $obj->cg, $obj->cc, $obj->somme, $obj->fac, $obj->idfac, $obj->iban, $obj->bic, $this->db->jdate($obj->datec), $obj->drum);
					$this->total = $this->total + $obj->somme;
					$i++;
				}
				$nbtotalDrctDbtTxInf = $i;
			}
			else
			{
				fputs($this->file, 'ERROR DEBITOR '.$sql.$CrLf);		// DEBITOR = Customers
				$result = -2;
			}

			// Define $fileEmetteurSection. Start of bloc PmtInf. Will contains all DrctDbtTxInf
			if ($result != -2)
			{
				$fileEmetteurSection .= $this->EnregEmetteurSEPA($conf, $date_actu, $nbtotalDrctDbtTxInf, $this->total, $CrLf, $format);
			}
			else
			{
				fputs($this->file, 'ERROR CREDITOR'.$CrLf);		// CREDITOR = My company
			}

			/**
			 * SECTION CREATION SEPA FILE
			 */
			// SEPA File Header
			fputs($this->file, '<'.'?xml version="1.0" encoding="UTF-8" standalone="yes"?'.'>'.$CrLf);
			fputs($this->file, '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$CrLf);
			fputs($this->file, '	<CstmrDrctDbtInitn>'.$CrLf);
			// SEPA Group header
			fputs($this->file, '		<GrpHdr>'.$CrLf);
			fputs($this->file, '			<MsgId>'.('PREL'.$dateTime_YMD.'/REF'.$this->id).'</MsgId>'.$CrLf);
			fputs($this->file, '			<CreDtTm>'.$dateTime_ECMA.'</CreDtTm>'.$CrLf);
			fputs($this->file, '			<NbOfTxs>'.$i.'</NbOfTxs>'.$CrLf);
			fputs($this->file, '			<CtrlSum>'.$this->total.'</CtrlSum>'.$CrLf);
			fputs($this->file, '			<InitgPty>'.$CrLf);
			fputs($this->file, '				<Nm>'.strtoupper(dol_string_unaccent($this->raison_sociale)).'</Nm>'.$CrLf);
			fputs($this->file, '				<Id>'.$CrLf);
			fputs($this->file, '				    <PrvtId>'.$CrLf);
			fputs($this->file, '					<Othr>'.$CrLf);
			fputs($this->file, '						<Id>'.$conf->global->PRELEVEMENT_ICS.'</Id>'.$CrLf);
			fputs($this->file, '					</Othr>'.$CrLf);
			fputs($this->file, '				    </PrvtId>'.$CrLf);
			fputs($this->file, '				</Id>'.$CrLf);
			fputs($this->file, '			</InitgPty>'.$CrLf);
			fputs($this->file, '		</GrpHdr>'.$CrLf);
			// SEPA File Emetteur
			if ($result != -2)
			{	fputs($this-> file, $fileEmetteurSection);}
			// SEPA File Debiteurs
			if ($result != -2)
			{	fputs($this-> file, $fileDebiteurSection);}
			// SEPA FILE FOOTER
			fputs($this->file, '		</PmtInf>'.$CrLf);
			fputs($this->file, '	</CstmrDrctDbtInitn>'.$CrLf);
			fputs($this->file, '</Document>'.$CrLf);
		}

		// Build file for Other Countries with unknow format
		if (! $found)
		{
			$this->total = 0;
			$sql = "SELECT pl.amount";
			$sql.= " FROM";
			$sql.= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
			$sql.= " ".MAIN_DB_PREFIX."facture as f,";
			$sql.= " ".MAIN_DB_PREFIX."prelevement_facture as pf";
			$sql.= " WHERE pl.fk_prelevement_bons = ".$this->id;
			$sql.= " AND pl.rowid = pf.fk_prelevement_lignes";
			$sql.= " AND pf.fk_facture = f.rowid";

			//Lines
			$i = 0;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);

				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$this->total = $this->total + $obj->amount;
					$i++;
				}
			}
			else
			{
				$result = -2;
			}

			$langs->load('withdrawals');

			// TODO Add here code to generate a generic file
			fputs($this->file, $langs->trans('WithdrawalFileNotCapable', $mysoc->country_code));
		}

		fclose($this->file);
		if (! empty($conf->global->MAIN_UMASK))
		@chmod($this->file, octdec($conf->global->MAIN_UMASK));
		return $result;

	}


	/**
	 *	Write recipient of request (customer)
	 *
	 *	@param	int		$rowid			id of line
	 *	@param	string	$client_nom		name of customer
	 *	@param	string	$rib_banque		code of bank
	 *	@param	string	$rib_guichet 	code of bank office
	 *	@param	string	$rib_number		bank account
	 *	@param	float	$amount			amount
	 *	@param	string	$facnumber		ref of invoice
	 *	@param	int		$facid			id of invoice
	 *  @param	string	$rib_dom		rib domiciliation
	 *	@return	void
	 */
	function EnregDestinataire($rowid, $client_nom, $rib_banque, $rib_guichet, $rib_number, $amount, $facnumber, $facid, $rib_dom='')
	{
		fputs($this->file, "06");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Date d'echeance C1

		fputs($this->file, "       ");
		fputs($this->file, strftime("%d%m", $this->date_echeance));
		fputs($this->file, substr(strftime("%y", $this->date_echeance),1));

		// Raison Sociale Destinataire C2

		fputs($this->file, substr(strtoupper($client_nom)."                         ",0,24));

		// Domiciliation facultative D1
		$domiciliation = strtr($rib_dom, array(" " => "-", CHR(13) => " ", CHR(10) => ""));
		fputs($this->file, substr($domiciliation."                         ",0,24));

		// Zone Reservee D2

		fputs($this->file, substr("                             ",0,8));

		// Code Guichet  D3

		fputs($this->file, $rib_guichet);

		// Numero de compte D4

		fputs($this->file, substr("000000000000000".$rib_number, -11));

		// Zone E Montant

		$montant = (round($amount,2) * 100);

		fputs($this->file, substr("000000000000000".$montant, -16));

		// Libelle F

		fputs($this->file, substr("*_".$facnumber."_RDVnet".$rowid."                               ", 0, 31));

		// Code etablissement G1

		fputs($this->file, $rib_banque);

		// Zone Reservee G2

		fputs($this->file, substr("                                        ", 0, 5));

		fputs($this->file, "\n");
	}


	/**
	 * Build RUM number for a customer bank account
	 *
	 * @param	string		$row_code_client	Customer code (soc.code_client)
	 * @param	int			$row_datec			Creation date of bank account (rib.datec)
	 * @param	string		$row_drum			Id of customer bank account (rib.rowid)
	 * @return 	string		RUM number
	 */
	static function buildRumNumber($row_code_client, $row_datec, $row_drum)
	{
		global $langs;
		$pre = $langs->trans('RUM').'-';
		return $pre.$row_code_client.'-'.$row_drum.'-'.date('U', $row_datec);
	}

	/**
	 *	Write recipient of request (customer)
	 *
	 *	@param	string		$row_code_client	soc.code_client as code,
	 *	@param	string		$row_nom			pl.client_nom AS name,
	 *	@param	string		$row_address		soc.address AS adr,
	 *	@param	string		$row_zip			soc.zip
	 *  @param	string		$row_town			soc.town
	 *	@param	string		$row_country_code	c.code AS country,
	 *	@param	string		$row_cb				pl.code_banque AS cb,		Not used for SEPA
	 *	@param	string		$row_cg				pl.code_guichet AS cg,		Not used for SEPA
	 *	@param	string		$row_cc				pl.number AS cc,			Not used for SEPA
	 *	@param	string		$row_somme			pl.amount AS somme,
	 *	@param	string		$row_facnumber		f.facnumber
	 *	@param	string		$row_idfac			pf.fk_facture AS idfac,
	 *	@param	string		$row_iban			rib.iban_prefix AS iban,
	 *	@param	string		$row_bic			rib.bic AS bic,
	 *	@param	string		$row_datec			rib.datec,
	 *	@param	string		$row_drum			rib.rowid used to generate rum
	 *	@return	string							Return string with SEPA part DrctDbtTxInf
	 */
	function EnregDestinataireSEPA($row_code_client, $row_nom, $row_address, $row_zip, $row_town, $row_country_code, $row_cb, $row_cg, $row_cc, $row_somme, $row_facnumber, $row_idfac, $row_iban, $row_bic, $row_datec, $row_drum)
	{
		$CrLf = "\n";
		$Rowing = sprintf("%06d", $row_idfac);

		// Define value for RUM
		// Example:  RUMCustomerCode-CustomerBankAccountId-01424448606	(note: Date is date of creation of CustomerBankAccountId)
		$Rum = $this->buildRumNumber($row_code_client, $row_datec, $row_drum);

		// Define date of RUM signature
		$DtOfSgntr = dol_print_date($row_datec, '%Y-%m-%d');

		$XML_DEBITOR ='';
		$XML_DEBITOR .='			<DrctDbtTxInf>'.$CrLf;
		$XML_DEBITOR .='				<PmtId>'.$CrLf;
		$XML_DEBITOR .='					<EndToEndId>'.('AS-'.dol_trunc($row_facnumber,20).'-'.$Rowing).'</EndToEndId>'.$CrLf;          // ISO20022 states that EndToEndId has a MaxLength of 35 characters
		$XML_DEBITOR .='				</PmtId>'.$CrLf;
		$XML_DEBITOR .='				<InstdAmt Ccy="EUR">'.round($row_somme, 2).'</InstdAmt>'.$CrLf;
		$XML_DEBITOR .='				<DrctDbtTx>'.$CrLf;
		$XML_DEBITOR .='					<MndtRltdInf>'.$CrLf;
		$XML_DEBITOR .='						<MndtId>'.$Rum.'</MndtId>'.$CrLf;
		$XML_DEBITOR .='						<DtOfSgntr>'.$DtOfSgntr.'</DtOfSgntr>'.$CrLf;
		$XML_DEBITOR .='						<AmdmntInd>false</AmdmntInd>'.$CrLf;
		$XML_DEBITOR .='					</MndtRltdInf>'.$CrLf;
		$XML_DEBITOR .='				</DrctDbtTx>'.$CrLf;
		$XML_DEBITOR .='				<DbtrAgt>'.$CrLf;
		$XML_DEBITOR .='					<FinInstnId>'.$CrLf;
		$XML_DEBITOR .='						<BIC>'.$row_bic.'</BIC>'.$CrLf;
		$XML_DEBITOR .='					</FinInstnId>'.$CrLf;
		$XML_DEBITOR .='				</DbtrAgt>'.$CrLf;
		$XML_DEBITOR .='				<Dbtr>'.$CrLf;
		$XML_DEBITOR .='					<Nm>'.dolEscapeXML(strtoupper(dol_string_unaccent($row_nom))).'</Nm>'.$CrLf;
		$XML_DEBITOR .='					<PstlAdr>'.$CrLf;
		$XML_DEBITOR .='						<Ctry>'.$row_country_code.'</Ctry>'.$CrLf;
		$addressline1 = dol_string_unaccent(strtr($row_address, array(CHR(13) => ", ", CHR(10) => "")));
		$addressline2 = dol_string_unaccent(strtr($row_zip.(($row_zip && $row_town)?' ':''.$row_town), array(CHR(13) => ", ", CHR(10) => "")));
		if (trim($addressline1)) 	$XML_DEBITOR .='						<AdrLine>'.dolEscapeXML(dol_trunc($addressline1,70,'right','UTF-8',true)).'</AdrLine>'.$CrLf;
		if (trim($addressline2))	$XML_DEBITOR .='						<AdrLine>'.dolEscapeXML(dol_trunc($addressline2,70,'right','UTF-8',true)).'</AdrLine>'.$CrLf;
		$XML_DEBITOR .='					</PstlAdr>'.$CrLf;
		$XML_DEBITOR .='				</Dbtr>'.$CrLf;
		$XML_DEBITOR .='				<DbtrAcct>'.$CrLf;
		$XML_DEBITOR .='					<Id>'.$CrLf;
		$XML_DEBITOR .='						<IBAN>'.preg_replace('/\s/', '', $row_iban).'</IBAN>'.$CrLf;
		$XML_DEBITOR .='					</Id>'.$CrLf;
		$XML_DEBITOR .='				</DbtrAcct>'.$CrLf;
		$XML_DEBITOR .='				<RmtInf>'.$CrLf;
	//	$XML_DEBITOR .='					<Ustrd>'.($row_facnumber.'/'.$Rowing.'/'.$Rum).'</Ustrd>'.$CrLf;
		$XML_DEBITOR .='					<Ustrd>'.dol_trunc($row_facnumber, 135).'</Ustrd>'.$CrLf;        // 140 max
		$XML_DEBITOR .='				</RmtInf>'.$CrLf;
		$XML_DEBITOR .='			</DrctDbtTxInf>'.$CrLf;
		return $XML_DEBITOR;
	}


	/**
	 *	Write sender of request (me)
	 *
	 *	@return	void
	 */
	function EnregEmetteur()
	{
		fputs($this->file, "03");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Date d'echeance C1

		fputs($this->file, "       ");
		fputs($this->file, strftime("%d%m", $this->date_echeance));
		fputs($this->file, substr(strftime("%y", $this->date_echeance),1));

		// Raison Sociale C2

		fputs($this->file, substr($this->raison_sociale. "                           ",0,24));

		// Reference de la remise creancier D1 sur 7 caracteres

		fputs($this->file, substr($this->reference_remise. "                           ",0,7));

		// Zone Reservee D1-2

		fputs($this->file, substr("                                    ",0,17));

		// Zone Reservee D2

		fputs($this->file, substr("                             ",0,2));
		fputs($this->file, "E");
		fputs($this->file, substr("                             ",0,5));

		// Code Guichet  D3

		fputs($this->file, $this->emetteur_code_guichet);

		// Numero de compte D4

		fputs($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

		// Zone Reservee E

		fputs($this->file, substr("                                        ",0,16));

		// Zone Reservee F

		fputs($this->file, substr("                                        ",0,31));

		// Code etablissement

		fputs($this->file, $this->emetteur_code_banque);

		// Zone Reservee G

		fputs($this->file, substr("                                        ",0,5));

		fputs($this->file, "\n");

	}

	/**
	 *	Write sender of request (me).
	 *  Note: The tag PmtInf is opened here but closed into caller
	 *
	 *	@param	string	$configuration	conf
	 *	@param	int	$ladate			Date
	 *	@param	int		$nombre			0 or 1
	 *	@param	float	$total			Total
	 *	@param	string	$CrLf			End of line character
	 *  @param	string	$format			FRST or RCUR or ALL
	 *	@return	string					String with SEPA Sender
	 */
	function EnregEmetteurSEPA($configuration, $ladate, $nombre, $total, $CrLf='\n', $format='FRST')
	{
		// SEPA INITIALISATION
		global $conf;

		$dateTime_YMD = dol_print_date($ladate, '%Y%m%d');
		$dateTime_ETAD = dol_print_date($ladate, '%Y-%m-%d');
		$dateTime_YMDHMS = dol_print_date($ladate, '%Y-%m-%dT%H:%M:%S');

		// Get data of bank account
		$id=$configuration->global->PRELEVEMENT_ID_BANKACCOUNT;
		$account = new Account($this->db);
		if ($account->fetch($id)>0)
		{
			$this->emetteur_code_banque 	   = $account->code_banque;
			$this->emetteur_code_guichet       = $account->code_guichet;
			$this->emetteur_numero_compte      = $account->number;
			$this->emetteur_number_key		   = $account->cle_rib;
			$this->emetteur_iban               = $account->iban;
			$this->emetteur_bic                = $account->bic;

			$this->emetteur_ics                = $conf->global->PRELEVEMENT_ICS;		// Ex: PRELEVEMENT_ICS = "FR78ZZZ123456";

			$this->raison_sociale              = $account->proprio;
		}

		// Récupération info demandeur
		$sql = "SELECT rowid, ref";
		$sql.= " FROM";
		$sql.= " ".MAIN_DB_PREFIX."prelevement_bons as pb";
		$sql.= " WHERE pb.rowid = ".$this->id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);

			// DONNEES BRUTES : par la suite Rows['XXX'] de la requete au dessus
			$country = explode(':', $configuration->global->MAIN_INFO_SOCIETE_COUNTRY);
			$IdBon  = sprintf("%05d", $obj->rowid);
			$RefBon = $obj->ref;

			// SEPA Paiement Information
			$XML_SEPA_INFO = '';
			$XML_SEPA_INFO .= '		<PmtInf>'.$CrLf;
			$XML_SEPA_INFO .= '			<PmtInfId>'.('PREL'.$dateTime_YMD.'/ID'.$IdBon.'-'.$RefBon).'</PmtInfId>'.$CrLf;
			$XML_SEPA_INFO .= '			<PmtMtd>DD</PmtMtd>'.$CrLf;
			$XML_SEPA_INFO .= '			<NbOfTxs>'.$nombre.'</NbOfTxs>'.$CrLf;
			$XML_SEPA_INFO .= '			<CtrlSum>'.$total.'</CtrlSum>'.$CrLf;
			$XML_SEPA_INFO .= '			<PmtTpInf>'.$CrLf;
			$XML_SEPA_INFO .= '				<SvcLvl>'.$CrLf;
			$XML_SEPA_INFO .= '					<Cd>SEPA</Cd>'.$CrLf;
			$XML_SEPA_INFO .= '				</SvcLvl>'.$CrLf;
			$XML_SEPA_INFO .= '				<LclInstrm>'.$CrLf;
			$XML_SEPA_INFO .= '					<Cd>CORE</Cd>'.$CrLf;
			$XML_SEPA_INFO .= '				</LclInstrm>'.$CrLf;
			$XML_SEPA_INFO .= '				<SeqTp>'.$format.'</SeqTp>'.$CrLf;
			$XML_SEPA_INFO .= '			</PmtTpInf>'.$CrLf;
			$XML_SEPA_INFO .= '			<ReqdColltnDt>'.$dateTime_ETAD.'</ReqdColltnDt>'.$CrLf;
			$XML_SEPA_INFO .= '			<Cdtr>'.$CrLf;
			$XML_SEPA_INFO .= '				<Nm>'.strtoupper(dol_string_unaccent($this->raison_sociale)).'</Nm>'.$CrLf;
			$XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
			$XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
			$addressline1 = dol_string_unaccent(strtr($configuration->global->MAIN_INFO_SOCIETE_ADDRESS, array(CHR(13) => ", ", CHR(10) => "")));
			$addressline2 = dol_string_unaccent(strtr($configuration->global->MAIN_INFO_SOCIETE_ZIP.(($configuration->global->MAIN_INFO_SOCIETE_ZIP || ' '.$configuration->global->MAIN_INFO_SOCIETE_TOWN)?' ':'').$configuration->global->MAIN_INFO_SOCIETE_TOWN, array(CHR(13) => ", ", CHR(10) => "")));
			if ($addressline1)		$XML_SEPA_INFO .= '					<AdrLine>'.$addressline1.'</AdrLine>'.$CrLf;
			if ($addressline2)		$XML_SEPA_INFO .= '					<AdrLine>'.$addressline2.'</AdrLine>'.$CrLf;
			$XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
			$XML_SEPA_INFO .= '			</Cdtr>'.$CrLf;
			$XML_SEPA_INFO .= '			<CdtrAcct>'.$CrLf;
			$XML_SEPA_INFO .= '				<Id>'.$CrLf;
			$XML_SEPA_INFO .= '					<IBAN>'.preg_replace('/\s/', '', $this->emetteur_iban).'</IBAN>'.$CrLf;
			$XML_SEPA_INFO .= '				</Id>'.$CrLf;
			$XML_SEPA_INFO .= '			</CdtrAcct>'.$CrLf;
			$XML_SEPA_INFO .= '			<CdtrAgt>'.$CrLf;
			$XML_SEPA_INFO .= '				<FinInstnId>'.$CrLf;
			$XML_SEPA_INFO .= '					<BIC>'.$this->emetteur_bic.'</BIC>'.$CrLf;
			$XML_SEPA_INFO .= '				</FinInstnId>'.$CrLf;
			$XML_SEPA_INFO .= '			</CdtrAgt>'.$CrLf;
/*			$XML_SEPA_INFO .= '			<UltmtCdtr>'.$CrLf;
			$XML_SEPA_INFO .= '				<Nm>'.$this->raison_sociale.'</Nm>'.$CrLf;
			$XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
			$XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
			$XML_SEPA_INFO .= '					<AdrLine>'.$conf->global->MAIN_INFO_SOCIETE_ADDRESS.'</AdrLine>'.$CrLf;
			$XML_SEPA_INFO .= '					<AdrLine>'.$conf->global->MAIN_INFO_SOCIETE_ZIP.' '.$conf->global->MAIN_INFO_SOCIETE_TOWN.'</AdrLine>'.$CrLf;
			$XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
			$XML_SEPA_INFO .= '			</UltmtCdtr>'.$CrLf;
*/			$XML_SEPA_INFO .= '			<ChrgBr>SLEV</ChrgBr>'.$CrLf;
			$XML_SEPA_INFO .= '			<CdtrSchmeId>'.$CrLf;
			$XML_SEPA_INFO .= '				<Id>'.$CrLf;
			$XML_SEPA_INFO .= '					<PrvtId>'.$CrLf;
			$XML_SEPA_INFO .= '						<Othr>'.$CrLf;
			$XML_SEPA_INFO .= '							<Id>'.$this->emetteur_ics.'</Id>'.$CrLf;
			$XML_SEPA_INFO .= '							<SchmeNm>'.$CrLf;
			$XML_SEPA_INFO .= '								<Prtry>SEPA</Prtry>'.$CrLf;
			$XML_SEPA_INFO .= '							</SchmeNm>'.$CrLf;
			$XML_SEPA_INFO .= '						</Othr>'.$CrLf;
			$XML_SEPA_INFO .= '					</PrvtId>'.$CrLf;
			$XML_SEPA_INFO .= '				</Id>'.$CrLf;
			$XML_SEPA_INFO .= '			</CdtrSchmeId>'.$CrLf;
		}
		else
		{
			fputs($this->file, 'INCORRECT EMETTEUR '.$XML_SEPA_INFO.$CrLf);
			$result = -2;
		}
		return $XML_SEPA_INFO;
	}

	/**
	 *	Write end
	 *
	 *	@param	int		$total	total amount
	 *	@return	void
	 */
	function EnregTotal($total)
	{
		fputs($this->file, "08");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Reserve C1

		fputs($this->file, substr("                           ",0,12));


		// Raison Sociale C2

		fputs($this->file, substr("                           ",0,24));

		// D1

		fputs($this->file, substr("                                    ",0,24));

		// Zone Reservee D2

		fputs($this->file, substr("                             ",0,8));

		// Code Guichet  D3

		fputs($this->file, substr("                             ",0,5));

		// Numero de compte D4

		fputs($this->file, substr("                             ",0,11));

		// Zone E Montant

		$montant = ($total * 100);

		fputs($this->file, substr("000000000000000".$montant, -16));

		// Zone Reservee F

		fputs($this->file, substr("                                        ",0,31));

		// Code etablissement

		fputs($this->file, substr("                                        ",0,5));

		// Zone Reservee F

		fputs($this->file, substr("                                        ",0,5));

		fputs($this->file, "\n");
	}

	/**
	 *    Return status label of object
	 *
	 *    @param    int		$mode   0=Label, 1=Picto + label, 2=Picto, 3=Label + Picto
	 * 	  @return	string     		Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Return status label for a status
	 *
	 *  @param	int		$statut     id statut
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return	string  		    Label
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->labelstatut[$statut]);
		}

		if ($mode == 1)
		{
			if ($statut==0) return img_picto($langs->trans($this->labelstatut[$statut]),'statut1').' '.$langs->trans($this->labelstatut[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->labelstatut[$statut]),'statut3').' '.$langs->trans($this->labelstatut[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->labelstatut[$statut]),'statut6').' '.$langs->trans($this->labelstatut[$statut]);
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans($this->labelstatut[$statut]),'statut1');
			if ($statut==1) return img_picto($langs->trans($this->labelstatut[$statut]),'statut3');
			if ($statut==2) return img_picto($langs->trans($this->labelstatut[$statut]),'statut6');
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->labelstatut[$statut]),'statut1');
			if ($statut==1) return img_picto($langs->trans($this->labelstatut[$statut]),'statut3');
			if ($statut==2) return img_picto($langs->trans($this->labelstatut[$statut]),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->labelstatut[$statut]),'statut1').' '.$langs->trans($this->labelstatut[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->labelstatut[$statut]),'statut3').' '.$langs->trans($this->labelstatut[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->labelstatut[$statut]),'statut6').' '.$langs->trans($this->labelstatut[$statut]);
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut1');
			if ($statut==1) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut3');
			if ($statut==2) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut6');
		}
		if ($mode == 6)
		{
			if ($statut==0) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut1');
			if ($statut==1) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut3');
			if ($statut==2) return $langs->trans($this->labelstatut[$statut]).' '.img_picto($langs->trans($this->labelstatut[$statut]),'statut6');
		}
	}

}

