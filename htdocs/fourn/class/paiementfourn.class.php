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

/**
 *	Class to manage payments for supplier invoices
 */
class PaiementFourn extends Paiement
{
    public $element='payment_supplier';
    public $table_element='paiementfourn';

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
	 *	@param	int		$id     Id if payment to get
	 *	@return int     		<0 if ko, >0 if ok
	 */
	function fetch($id)
	{
		$sql = 'SELECT p.rowid, p.datep as dp, p.amount, p.statut, p.fk_bank,';
		$sql.= ' c.code as paiement_code, c.libelle as paiement_type,';
		$sql.= ' p.num_paiement, p.note, b.fk_account';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiementfourn as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql.= ' WHERE p.fk_paiement = c.id';
		$sql.= ' AND p.rowid = '.$id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 0)
			{
				$obj = $this->db->fetch_object($resql);
				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
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
				$error = -2;
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
	function create($user,$closepaidinvoices=0)
	{
		global $langs,$conf;

		$error = 0;

		// Clean parameters
		$this->total = 0;
		foreach ($this->amounts as $key => $value)
		{
			$value = price2num($value);
			$val = round($value, 2);
			$this->amounts[$key] = $val;
			$this->total += $val;
		}
		$this->total = price2num($this->total);


		$this->db->begin();

		if ($this->total <> 0) // On accepte les montants negatifs
		{
			$now=dol_now();

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn (';
			$sql.= 'datec, datep, amount, fk_paiement, num_paiement, note, fk_user_author, fk_bank)';
			$sql.= " VALUES ('".$this->db->idate($now)."',";
			$sql.= " '".$this->db->idate($this->datepaye)."', '".$this->total."', ".$this->paiementid.", '".$this->num_paiement."', '".$this->db->escape($this->note)."', ".$user->id.", 0)";

			dol_syslog("PaiementFourn::create", LOG_DEBUG);
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
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn (fk_facturefourn, fk_paiementfourn, amount)';
						$sql .= ' VALUES ('.$facid.','. $this->id.',\''.$amount.'\')';
						$resql=$this->db->query($sql);
						if ($resql)
						{
							// If we want to closed payed invoices
						    if ($closepaidinvoices)
						    {
						        $invoice=new FactureFournisseur($this->db);
						        $invoice->fetch($facid);
	                            $paiement = $invoice->getSommePaiement();
	                            //$creditnotes=$invoice->getSumCreditNotesUsed();
	                            $creditnotes=0;
	                            //$deposits=$invoice->getSumDepositsUsed();
	                            $deposits=0;
	                            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
	                            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
	                            if ($remaintopay == 0)
	                            {
	    					        $result=$invoice->set_paid($user,'','');
	                            }
	                            else dol_syslog("Remain to pay for invoice ".$facid." not null. We do nothing.");
							}
						}
						else
						{
							dol_syslog('Paiement::Create Erreur INSERT dans paiement_facture '.$facid);
							$error++;
						}

					}
					else
					{
						dol_syslog('PaiementFourn::Create Montant non numerique',LOG_ERR);
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

		if ($this->total <> 0 && $error == 0) // On accepte les montants negatifs
		{
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
			$accline = new AccountLine($this->db,$bank_line_id);
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
    				$result=$accline->delete();
    			}
    			if ($result < 0)
    			{
                    $this->error=$accline->error;
                    $this->db->rollback();
    	    		return -4;
    		    }
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


	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option			Sur quoi pointe le lien
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
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

        $link = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';


        if ($withpicto) $result.=($link.img_object($langs->trans("ShowPayment"), 'payment', 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$text.$linkend;
		return $result;
	}
}
