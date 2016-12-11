<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C)      2005 Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2012      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014      Marcos García 		 <marcosgdf@gmail.com>
 * Copyright (C) 2015      Juanjo Menent		 <jmenent@2byte.es>
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
require_once DOL_DOCUMENT_ROOT .'/multicurrency/class/multicurrency.class.php';


/**
 *	Class to manage payments of customer invoices
 */
class Paiement extends CommonObject
{
    public $element='payment';
    public $table_element='paiement';

	var $facid;
	var $datepaye;
	/**
	 * @deprecated
	 * @see amount, amounts
	 */
    var $total;
	/**
	 * @deprecated
	 * @see amount, amounts
	 */
	var $montant;
	var $amount;            // Total amount of payment
	var $amounts=array();   // Array of amounts
	var $multicurrency_amounts=array();   // Array of amounts
	var $author;
	var $paiementid;	// Type de paiement. Stocke dans fk_paiement
	// de llx_paiement qui est lie aux types de
	//paiement de llx_c_paiement
	var $num_paiement;	// Numero du CHQ, VIR, etc...
	var $bank_account;	// Id compte bancaire du paiement
	var $bank_line;     // Id de la ligne d'ecriture bancaire
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement
    var $fk_paiement;    // Type of paiment

    
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
	 *    @param	int		$id			Id of payment to get
	 *    @param	string	$ref		Ref of payment to get (currently ref = id but this may change in future)
	 *    @param	int		$fk_bank	Id of bank line associated to payment
	 *    @return   int		            <0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id, $ref='', $fk_bank='')
	{
		$sql = 'SELECT p.rowid, p.ref, p.datep as dp, p.amount, p.statut, p.fk_bank,';
		$sql.= ' c.code as type_code, c.libelle as type_libelle,';
		$sql.= ' p.num_paiement, p.note,';
		$sql.= ' b.fk_account';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql.= ' WHERE p.fk_paiement = c.id';
		if ($id > 0)
			$sql.= ' AND p.rowid = '.$id;
		else if ($ref)
			$sql.= ' AND p.rowid = '.$ref;
		else if ($fk_bank)
			$sql.= ' AND p.fk_bank = '.$fk_bank;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref?$obj->ref:$obj->rowid;
				$this->date           = $this->db->jdate($obj->dp);
				$this->datepaye       = $this->db->jdate($obj->dp);
				$this->numero         = $obj->num_paiement;
				$this->montant        = $obj->amount;   // deprecated
				$this->amount         = $obj->amount;
				$this->note           = $obj->note;
				$this->type_libelle   = $obj->type_libelle;
				$this->type_code      = $obj->type_code;
				$this->statut         = $obj->statut;

				$this->bank_account   = $obj->fk_account; // deprecated
				$this->fk_account     = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;

				$this->db->free($resql);
				return 1;
			}
			else
			{
				$this->db->free($resql);
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
		$way = $this->getWay();
		
		$now=dol_now();
		
        // Clean parameters
        $totalamount = 0;
		$totalamount_converted = 0;
        $atleastonepaymentnotnull = 0;
		
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
		
		foreach ($amounts as $key => $value)	// How payment is dispatch
		{
			$value_converted = Multicurrency::getAmountConversionFromInvoiceRate($key, $value, $way);
			$totalamount_converted += $value_converted;
			$amounts_to_update[$key] = price2num($value_converted, 'MT');
			
			$newvalue = price2num($value,'MT');
			$amounts[$key] = $newvalue;
			$totalamount += $newvalue;
			if (! empty($newvalue)) $atleastonepaymentnotnull++;
		}
		
		$totalamount = price2num($totalamount);
		$totalamount_converted = price2num($totalamount_converted);
		
		// Check parameters
        if (empty($totalamount) && empty($atleastonepaymentnotnull))	 // We accept negative amounts for withdraw reject but not empty arrays
        {
        	$this->error='TotalAmountEmpty';
        	return -1;
        }

		$this->db->begin();

		$ref = $this->getNextNumRef('');

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

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (entity, ref, datec, datep, amount, multicurrency_amount, fk_paiement, num_paiement, note, fk_user_creat)";
		$sql.= " VALUES (".$conf->entity.", '".$ref."', '". $this->db->idate($now)."', '".$this->db->idate($this->datepaye)."', '".$total."', '".$mtotal."', ".$this->paiementid.", '".$this->num_paiement."', '".$this->db->escape($this->note)."', ".$user->id.")";
		
		dol_syslog(get_class($this)."::Create insert paiement", LOG_DEBUG);
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
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_facture, fk_paiement, amount, multicurrency_amount)';
					$sql .= ' VALUES ('.$facid.', '. $this->id.', \''.$amount.'\', \''.$this->multicurrency_amounts[$key].'\')';
		
					dol_syslog(get_class($this).'::Create Amount line '.$key.' insert paiement_facture', LOG_DEBUG);
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

                            //Invoice types that are eligible for changing status to paid
							$affected_types = array(
								Facture::TYPE_STANDARD,
								Facture::TYPE_REPLACEMENT,
								Facture::TYPE_CREDIT_NOTE,
								Facture::TYPE_DEPOSIT,
								Facture::TYPE_SITUATION
							);

                            if (!in_array($invoice->type, $affected_types)) dol_syslog("Invoice ".$facid." is not a standard, nor replacement invoice, nor credit note, nor deposit invoice, nor situation invoice. We do nothing more.");
                            else if ($remaintopay) dol_syslog("Remain to pay for invoice ".$facid." not null. We do nothing more.");
                            else if ($mustwait) dol_syslog("There is ".$mustwait." differed payment to process, we do nothing more.");
                            else
                            {
                                $result=$invoice->set_paid($user,'','');
                                if ($result<0)
                                {
                                    $this->error=$invoice->error;
                                    $error++;
                                }
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
				// Appel des triggers
				$result=$this->call_trigger('PAYMENT_CUSTOMER_CREATE', $user);
				if ($result < 0) { $error++; }
				// Fin appel triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$error++;
		}

		if (! $error)
		{
		    $this->amount=$total;
		    $this->total=$total;    // deprecated
		    $this->multicurrency_amount=$mtotal;
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
	 *  Delete a payment and generated links into account
	 *  - Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *  - Si le paiement porte sur au moins une facture a "payee", on refuse
	 *
	 *  @param	int		$notrigger		No trigger
	 *  @return int     				<0 si ko, >0 si ok
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

		// Delete bank urls. If payment is on a conciliated line, return error.
		if ($bank_line_id > 0)
		{
			$accline = new AccountLine($this->db);

			$result=$accline->fetch($bank_line_id);
			if ($result == 0) $accline->rowid=$bank_line_id;    // If not found, we set artificially rowid to allow delete of llx_bank_url

            // Delete bank account url lines linked to payment
			$result=$accline->delete_urls($user);
            if ($result < 0)
            {
                $this->error=$accline->error;
				$this->db->rollback();
				return -3;
            }

            // Delete bank account lines linked to payment
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
			// Call triggers
			$result=$this->call_trigger('PAYMENT_CUSTOMER_DELETE', $user);
			if ($result < 0)
			{
			    $this->db->rollback();
			    return -1;
			 }
		    // End call triggers
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
            
            // if dolibarr currency != bank currency then we received an amount in customer currency (currently I don't manage the case : my currency is USD, the customer currency is EUR and he paid me in GBP. Seems no sense for me)
            if (!empty($conf->multicurrency->enabled) && $conf->currency != $acc->currency_code) $totalamount=$this->multicurrency_amount;
			
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
                    if ($mode == 'payment') $url=DOL_URL_ROOT.'/compta/paiement/card.php?id=';
                    if ($mode == 'payment_supplier') $url=DOL_URL_ROOT.'/fourn/paiement/card.php?id=';
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
                                    DOL_URL_ROOT.'/comm/card.php?socid=',
                                    $fac->thirdparty->name,
                                    'company'
                                );
                                if ($result <= 0) dol_syslog(get_class($this).'::addPaymentToBank '.$this->db->lasterror());
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
                                    DOL_URL_ROOT.'/fourn/card.php?socid=',
                                    $fac->thirdparty->name,
                                    'company'
                                );
                                if ($result <= 0) dol_syslog(get_class($this).'::addPaymentToBank '.$this->db->lasterror());
                                $linkaddedforthirdparty[$fac->thirdparty->id]=$fac->thirdparty->id;  // Mark as done for this thirdparty
                            }
                        }
                    }
                }

				// Add link 'WithdrawalPayment' in bank_url
				if (! $error && $label == '(WithdrawalPayment)')
				{
					$result=$acc->add_url_line(
						$bank_line_id,
						$this->id_prelevement,
						DOL_URL_ROOT.'/compta/prelevement/card.php?id=',
						$this->num_paiement,
						'withdraw'
					);
				}

	            if (! $error && ! $notrigger)
				{
					// Appel des triggers
					$result=$this->call_trigger('PAYMENT_ADD_TO_BANK', $user);
				    if ($result < 0) { $error++; }
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

		dol_syslog(get_class($this).'::update_fk_bank', LOG_DEBUG);
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
     *  @param	int	$date   New date
     *  @return int					<0 if KO, 0 if OK
     */
    function update_date($date)
    {
        if (!empty($date) && $this->statut!=1)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET datep = '".$this->db->idate($date)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::update_date", LOG_DEBUG);
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

            dol_syslog(get_class($this)."::update_num", LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->numero = $this->db->escape($num);
                return 0;
            }
            else
            {
                $this->error='Error -1 '.$this->db->error();
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

		dol_syslog(get_class($this).'::valide', LOG_DEBUG);
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

	/**
	 *    Reject payment
	 *
	 *    @return     int     <0 if KO, >0 if OK
	 */
	function reject()
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET statut = 2 WHERE rowid = '.$this->id;

		dol_syslog(get_class($this).'::reject', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this).'::reject '.$this->error);
			return -1;
		}
	}

	/**
	 *    Information sur l'objet
	 *    
	 *    @param   int     $id      id du paiement dont il faut afficher les infos
	 *    @return  void
	 */
	function info($id)
	{
		$sql = 'SELECT p.rowid, p.datec, p.fk_user_creat, p.fk_user_modif, p.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' WHERE p.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
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
			dol_syslog(get_class($this).'::getBillsArray Error '.$this->error.' -', LOG_DEBUG);
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
	function getNextNumRef($soc,$mode='next')
	{
		global $conf, $db, $langs;
		$langs->load("bills");

		// Clean parameters (if not defined or using deprecated value)
		if (empty($conf->global->PAYMENT_ADDON)) $conf->global->PAYMENT_ADDON='mod_payment_cicada';
		else if ($conf->global->PAYMENT_ADDON=='ant') $conf->global->PAYMENT_ADDON='mod_payment_ant';
		else if ($conf->global->PAYMENT_ADDON=='cicada') $conf->global->PAYMENT_ADDON='mod_payment_cicada';

		if (! empty($conf->global->PAYMENT_ADDON))
		{
			$mybool=false;

			$file = $conf->global->PAYMENT_ADDON.".php";
			$classname = $conf->global->PAYMENT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/payment/");

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file))
				{
					$mybool |= include_once $dir . $file;
				}
			}

			// For compatibility
			if (! $mybool)
			{
				$file = $conf->global->PAYMENT_ADDON.".php";
				$classname = "mod_payment_".$conf->global->PAYMENT_ADDON;
				$classname = preg_replace('/\-.*$/','',$classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot)
				{
					$dir = $dirroot."/core/modules/payment/";

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
				dol_print_error($db,"Payment::getNextNumRef ".$obj->error);
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
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			Sur quoi pointe le lien
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label = $langs->trans("ShowPayment").': '.$this->ref;
	$arraybill = $this->getBillsArray();
	if (count($arraybill) >0)
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$facturestatic=new Facture($this->db);
		foreach ($arraybill as $billid)
		{
			$facturestatic->fetch($billid);
			$label .='<br> '.$facturestatic->getNomUrl(1).' '.$facturestatic->getLibStatut(2,1);
		}
	}

        $link = '<a href="'.DOL_URL_ROOT.'/compta/paiement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

        if ($withpicto) $result.=($link.img_object($langs->trans("ShowPayment"), 'payment', 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.($this->ref?$this->ref:$this->id).$linkend;
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
