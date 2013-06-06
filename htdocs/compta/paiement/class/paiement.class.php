<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C)      2005 Marc Barilley / Ocebo <marc@ocebo.com>
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
 *	\file       htdocs/compta/paiement/class/paiement.class.php
 *	\ingroup    facture
 *	\brief      File of class to manage payments of customers invoices
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**     \class      Paiement
 *		\brief      Classe permettant la gestion des paiements des factures clients
 */
class Paiement extends CommonObject
{
    public $element='payment';
    public $table_element='paiement';

    var $id;
	var $ref;
	var $facid;
	var $datepaye;
    var $total;             // deprecated
	var $amount;            // Total amount of payment
	var $amounts=array();   // Array of amounts
	var $author;
	var $paiementid;	// Type de paiement. Stocke dans fk_paiement
	// de llx_paiement qui est lie aux types de
	//paiement de llx_c_paiement
	var $num_paiement;	// Numero du CHQ, VIR, etc...
	var $bank_account;	// Id compte bancaire du paiement
	var $bank_line;     // Id de la ligne d'ecriture bancaire
	var $fk_account;	// Id of bank account
	var $note;
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement


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
	 *    Load payment from database
	 *
	 *    @param	int		$id     Id of payment to get
	 *    @return   int     		<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id)
	{
		$sql = 'SELECT p.rowid, p.datep as dp, p.amount, p.statut, p.fk_bank,';
		$sql.= ' c.code as type_code, c.libelle as type_libelle,';
		$sql.= ' p.num_paiement, p.note,';
		$sql.= ' b.fk_account';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql.= ' WHERE p.fk_paiement = c.id';
		$sql.= ' AND p.rowid = '.$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->date           = $this->db->jdate($obj->dp);
				$this->datepaye       = $this->db->jdate($obj->dp);
				$this->numero         = $obj->num_paiement;
				$this->montant        = $obj->amount;   // deprecated
				$this->amount         = $obj->amount;
				$this->note           = $obj->note;
				$this->type_libelle   = $obj->type_libelle;
				$this->type_code      = $obj->type_code;
				$this->statut         = $obj->statut;

				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;

				$this->db->free($result);
				return 1;
			}
			else
			{
				$this->db->free($result);
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    Create payment of invoices into database.
	 *    Use this->amounts to have list of invoices for the payment.
	 *    For payment of a customer invoice, amounts are postive, for payment of credit note, amounts are negative
	 *
	 *    @param	User	$user                	Object user
	 *    @param    int		$closepaidinvoices   	1=Also close payed invoices to paid, 0=Do nothing more
	 *    @return   int                 			id of created payment, < 0 if error
	 */
	function create($user,$closepaidinvoices=0)
	{
		global $conf, $langs;

		$error = 0;

        $now=dol_now();

        // Clean parameters
        $totalamount = 0;
        $atleastonepaymentnotnull = 0;
		foreach ($this->amounts as $key => $value)	// How payment is dispatch
		{
			$newvalue = price2num($value,'MT');
			$this->amounts[$key] = $newvalue;
			$totalamount += $newvalue;
			if (! empty($newvalue)) $atleastonepaymentnotnull++;
		}
		$totalamount = price2num($totalamount);

		// Check parameters
        if (empty($totalamount) && empty($atleastonepaymentnotnull))	 // We accept negative amounts for withdraw reject but not empty arrays
        {
        	$this->error='TotalAmountEmpty';
        	return -1;
        }

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (entity, datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)";
		$sql.= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', '".$this->db->idate($this->datepaye)."', '".$totalamount."', ".$this->paiementid.", '".$this->num_paiement."', '".$this->db->escape($this->note)."', ".$user->id.")";

		dol_syslog(get_class($this)."::Create insert paiement sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiement');

			// Insert links amount / invoices
			foreach ($this->amounts as $key => $amount)
			{
				$facid = $key;
				if (is_numeric($amount) && $amount <> 0)
				{
					$amount = price2num($amount);
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_facture, fk_paiement, amount)';
					$sql .= ' VALUES ('.$facid.', '. $this->id.', \''.$amount.'\')';

					dol_syslog(get_class($this).'::Create Amount line '.$key.' insert paiement_facture sql='.$sql);
					$resql=$this->db->query($sql);
					if ($resql)
					{
						// If we want to closed payed invoices
					    if ($closepaidinvoices)
					    {
					        $invoice=new Facture($this->db);
					        $invoice->fetch($facid);
                            $paiement = $invoice->getSommePaiement();
                            $creditnotes=$invoice->getSumCreditNotesUsed();
                            $deposits=$invoice->getSumDepositsUsed();
                            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
                            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');

							//var_dump($invoice->total_ttc.' - '.$paiement.' -'.$creditnotes.' - '.$deposits.' - '.$remaintopay);exit;

                            // If there is withdrawals request to do and not done yet, we wait before closing.
                            $mustwait=0;
                            $listofpayments=$invoice->getListOfPayments();
                            foreach($listofpayments as $paym)
                            {
                                // This payment might be this one or a previous one
                                if ($paym['type']=='PRE')
                                {
                                    if (! empty($conf->prelevement->enabled))
                                    {
                                        // TODO Check if this payment has a withdraw request
                                        // if not, $mustwait++;      // This will disable automatic close on invoice to allow to process
                                    }
                                }
                            }

                            if ($invoice->type != 0 && $invoice->type != 1 && $invoice->type != 2) dol_syslog("Invoice ".$facid." is not a standard, nor replacement invoice, nor credit note. We do nothing more.");
                            else if ($remaintopay) dol_syslog("Remain to pay for invoice ".$facid." not null. We do nothing more.");
                            else if ($mustwait) dol_syslog("There is ".$mustwait." differed payment to process, we do nothing more.");
                            else $result=$invoice->set_paid($user,'','');
					    }
					}
					else
					{
						$this->error=$this->db->lasterror();
						dol_syslog(get_class($this).'::Create insert paiement_facture error='.$this->error, LOG_ERR);
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
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('PAYMENT_CUSTOMER_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::Create insert paiement error='.$this->error, LOG_ERR);
			$error++;
		}

		if (! $error)
		{
		    $this->amount=$totalamount;
		    $this->total=$totalamount;    // deprecated
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      Supprime un paiement ainsi que les lignes qu'il a genere dans comptes
	 *      Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *      Si le paiement porte sur au moins une facture a "payee", on refuse
	 *
	 *      @param	int		$notrigger		No trigger
	 *      @return int     				<0 si ko, >0 si ok
	 */
	function delete($notrigger=0)
	{
		global $conf, $user, $langs;

		$error=0;

		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture classee
		// Si c'est le cas, on refuse la suppression
		$billsarray=$this->getBillsArray('fk_statut > 1');
		if (is_array($billsarray))
		{
			if (count($billsarray))
			{
				$this->error="ErrorDeletePaymentLinkedToAClosedInvoiceNotPossible";
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -2;
		}

		$accline = new AccountLine($this->db);

		// Delete bank urls. If payment is on a conciliated line, return error.
		if ($bank_line_id)
		{
			$result=$accline->fetch($bank_line_id);
			if ($result == 0) $accline->rowid=$bank_line_id;    // If not found, we set artificially rowid to allow delete of llx_bank_url

            $result=$accline->delete_urls($user);
            if ($result < 0)
            {
                $this->error=$accline->error;
				$this->db->rollback();
				return -3;
            }
		}

		// Delete payment (into paiement_facture and paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement_facture';
		$sql.= ' WHERE fk_paiement = '.$this->id;
		dol_syslog($sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement';
			$sql.= ' WHERE rowid = '.$this->id;
		    dol_syslog($sql);
			$result = $this->db->query($sql);
			if (! $result)
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -3;
			}

			// Supprimer l'ecriture bancaire si paiement lie a ecriture
			if ($bank_line_id)
			{
				$result=$accline->delete($user);
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
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('PAYMENT_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
     *      Add a record into bank for payment with links between this bank record and invoices of payment.
     *      All payment properties (this->amount, this->amounts, ...) must have been set first like after a call to create().
     *
     *      @param	User	$user               Object of user making payment
     *      @param  string	$mode               'payment', 'payment_supplier'
     *      @param  string	$label              Label to use in bank record
     *      @param  int		$accountid          Id of bank account to do link with
     *      @param  string	$emetteur_nom       Name of transmitter
     *      @param  string	$emetteur_banque    Name of bank
     *      @param	int		$notrigger			No trigger
     *      @return int                 		<0 if KO, bank_line_id if OK
     */
    function addPaymentToBank($user,$mode,$label,$accountid,$emetteur_nom,$emetteur_banque,$notrigger=0)
    {
        global $conf,$langs,$user;

        $error=0;
        $bank_line_id=0;

        if (! empty($conf->banque->enabled))
        {
        	if ($accountid <= 0)
        	{
        		$this->error='Bad value for parameter accountid';
        		dol_syslog(get_class($this).'::addPaymentToBank '.$this->error, LOG_ERR);
        		return -1;
        	}

        	$this->db->begin();

        	$this->fk_account=$accountid;

        	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

            dol_syslog("$user->id,$mode,$label,$this->fk_account,$emetteur_nom,$emetteur_banque");

            $acc = new Account($this->db);
            $result=$acc->fetch($this->fk_account);

            $totalamount=$this->amount;
            if (empty($totalamount)) $totalamount=$this->total; // For backward compatibility
            if ($mode == 'payment') $totalamount=$totalamount;
            if ($mode == 'payment_supplier') $totalamount=-$totalamount;

            // Insert payment into llx_bank
            $bank_line_id = $acc->addline(
                $this->datepaye,
                $this->paiementid,  // Payment mode id or code ("CHQ or VIR for example")
                $label,
                $totalamount,		// Sign must be positive when we receive money (customer payment), negative when you give money (supplier invoice or credit note)
                $this->num_paiement,
                '',
                $user,
                $emetteur_nom,
                $emetteur_banque
            );

            // Mise a jour fk_bank dans llx_paiement
            // On connait ainsi le paiement qui a genere l'ecriture bancaire
            if ($bank_line_id > 0)
            {
                $result=$this->update_fk_bank($bank_line_id);
                if ($result <= 0)
                {
                    $error++;
                    dol_print_error($this->db);
                }

                // Add link 'payment', 'payment_supplier' in bank_url between payment and bank transaction
                if ( ! $error)
                {
                    $url='';
                    if ($mode == 'payment') $url=DOL_URL_ROOT.'/compta/paiement/fiche.php?id=';
                    if ($mode == 'payment_supplier') $url=DOL_URL_ROOT.'/fourn/paiement/fiche.php?id=';
                    if ($url)
                    {
                        $result=$acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
                        if ($result <= 0)
                        {
                            $error++;
                            dol_print_error($this->db);
                        }
                    }
                }

                // Add link 'company' in bank_url between invoice and bank transaction (for each invoice concerned by payment)
                if (! $error  && $label != '(WithdrawalPayment)')
                {
                    $linkaddedforthirdparty=array();
                    foreach ($this->amounts as $key => $value)  // We should have always same third party but we loop in case of.
                    {
                        if ($mode == 'payment')
                        {
                            $fac = new Facture($this->db);
                            $fac->fetch($key);
                            $fac->fetch_thirdparty();
                            if (! in_array($fac->thirdparty->id,$linkaddedforthirdparty)) // Not yet done for this thirdparty
                            {
                                $result=$acc->add_url_line(
                                    $bank_line_id,
                                    $fac->thirdparty->id,
                                    DOL_URL_ROOT.'/comm/fiche.php?socid=',
                                    $fac->thirdparty->nom,
                                    'company'
                                );
                                if ($result <= 0) dol_print_error($this->db);
                                $linkaddedforthirdparty[$fac->thirdparty->id]=$fac->thirdparty->id;  // Mark as done for this thirdparty
                            }
                        }
                        if ($mode == 'payment_supplier')
                        {
                            $fac = new FactureFournisseur($this->db);
                            $fac->fetch($key);
                            $fac->fetch_thirdparty();
                            if (! in_array($fac->thirdparty->id,$linkaddedforthirdparty)) // Not yet done for this thirdparty
                            {
                                $result=$acc->add_url_line(
                                    $bank_line_id,
                                    $fac->thirdparty->id,
                                    DOL_URL_ROOT.'/fourn/fiche.php?socid=',
                                    $fac->thirdparty->nom,
                                    'company'
                                );
                                if ($result <= 0) dol_print_error($this->db);
                                $linkaddedforthirdparty[$fac->thirdparty->id]=$fac->thirdparty->id;  // Mark as done for this thirdparty
                            }
                        }
                    }
                }

	            if (! $error && ! $notrigger)
				{
					// Appel des triggers
					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('PAYMENT_ADD_TO_BANK',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}
            }
            else
			{
                $this->error=$acc->error;
                $error++;
            }

            if (! $error)
            {
            	$this->db->commit();
            }
            else
			{
            	$this->db->rollback();
            }
        }

        if (! $error)
        {
            return $bank_line_id;
        }
        else
        {
            return -1;
        }
    }


	/**
	 *      Mise a jour du lien entre le paiement et la ligne generee dans llx_bank
	 *
	 *      @param	int		$id_bank    Id compte bancaire
	 *      @return	int					<0 if KO, >0 if OK
	 */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' set fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;

		dol_syslog(get_class($this).'::update_fk_bank sql='.$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this).'::update_fk_bank '.$this->error);
			return -1;
		}
	}

    /**
     *	Updates the payment date
     *
     *  @param	timestamp	$date   New date
     *  @return int					<0 if KO, 0 if OK
     */
    function update_date($date)
    {
        if (!empty($date) && $this->statut!=1)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET datep = ".$this->db->idate($date);
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::update_date sql=".$sql);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->datepaye = $date;
                $this->date = $date;
                return 0;
            }
            else
            {
                $this->error='Error -1 '.$this->db->error();
                dol_syslog(get_class($this)."::update_date ".$this->error, LOG_ERR);
                return -2;
            }
        }
        return -1; //no date given or already validated
    }

    /**
     *  Updates the payment number
     *
     *  @param	string	$num		New num
     *  @return int					<0 if KO, 0 if OK
     */
    function update_num($num)
    {
    	if(!empty($num) && $this->statut!=1)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET num_paiement = '".$this->db->escape($num)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::update_num sql=".$sql);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->numero = $this->db->escape($num);
                return 0;
            }
            else
            {
                $this->error='Error -1 '.$this->db->error();
                dol_syslog(get_class($this)."::update_num ".$this->error, LOG_ERR);
                return -2;
            }
        }
        return -1; //no num given or already validated
    }

	/**
	 *    Validate payment
	 *
	 *    @return     int     <0 if KO, >0 if OK
	 */
	function valide()
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 1 WHERE rowid = '.$this->id;

		dol_syslog(get_class($this).'::valide sql='.$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::valide '.$this->error);
			return -1;
		}
	}

	/*
	 *    \brief      Information sur l'objet
	 *    \param      id      id du paiement dont il faut afficher les infos
	 */
	function info($id)
	{
		$sql = 'SELECT p.rowid, p.datec, p.fk_user_creat, p.fk_user_modif, p.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' WHERE p.rowid = '.$id;

		dol_syslog(get_class($this).'::info sql='.$sql);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_creat)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation     = $cuser;
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
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Retourne la liste des factures sur lesquels porte le paiement
	 *
	 *  @param	string	$filter         Critere de filtre
	 *  @return array					Tableau des id de factures
	 */
	function getBillsArray($filter='')
	{
		$sql = 'SELECT fk_facture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' WHERE pf.fk_facture = f.rowid AND fk_paiement = '.$this->id;
		if ($filter) $sql.= ' AND '.$filter;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num=$this->db->num_rows($resql);
			$billsarray=array();

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$billsarray[$i]=$obj->fk_facture;
				$i++;
			}

			return $billsarray;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error.' - sql='.$sql);
			return -1;
		}
	}


	/**
	 *  Renvoie nom clicable (avec eventuellement le picto)
	 *
	 *	@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	@param	string	$option			Sur quoi pointe le lien
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowPayment"),'payment').$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	/**
	 * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 * @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return  string				Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param   int		$status     Statut
	 * @param   int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return	string  		    Libelle du statut
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage

		$langs->load('compta');
		if ($mode == 0)
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
		return $langs->trans('Unknown');
	}

}
?>
