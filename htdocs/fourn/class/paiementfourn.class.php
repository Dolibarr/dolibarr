<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin          <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/fourn/class/paiementfourn.class.php
 *		\ingroup    fournisseur, facture
 *		\brief      File of class to manage payments of suppliers invoices
 *		\version    $Id: paiementfourn.class.php,v 1.7 2011/07/31 23:57:02 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');

/**
 *	\class      PaiementFourn
 *	\brief      Classe permettant la gestion des paiements des factures fournisseurs
 */
class PaiementFourn extends Paiement
{
    var $db;
    var $error;
    var $element='payment_supplier';
    var $table_element='paiementfourn';

    var $id;
	var $ref;
	var $facid;
	var $datepaye;
	var $total;
    var $amount;            // Total amount of payment
    var $amounts=array();   // Array of amounts
	var $author;
	var $paiementid;	// Type de paiement. Stocke dans fk_paiement
						// de llx_paiement qui est lie aux types de
						//paiement de llx_c_paiement
	var $num_paiement;	// Numero du CHQ, VIR, etc...
	var $bank_account;	// Id compte bancaire du paiement
	var $bank_line;		// Id de la ligne d'ecriture bancaire
	var $note;
    var $statut;        //Status of payment. 0 = unvalidated; 1 = validated
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acces base de donnees
	 */

	function PaiementFourn($DB)
	{
		$this->db = $DB ;
	}

	/**
	 *    \brief      Load payment object
	 *    \param      id      id paiement to get
	 *    \return     int     <0 si ko, >0 si ok
	 */
	function fetch($id)
	{
		$sql = 'SELECT p.rowid, p.datep as dp, p.amount, p.statut, p.fk_bank,';
		$sql.= ' c.libelle as paiement_type,';
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
	 *    Create payment in database
	 *    @param      user        			Object of creating user
	 *    @param       closepaidinvoices   	1=Also close payed invoices to paid, 0=Do nothing more
	 *    @return     int         			id of created payment, < 0 if error
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
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn (';
			$sql.= 'datec, datep, amount, fk_paiement, num_paiement, note, fk_user_author, fk_bank)';
			$sql.= ' VALUES ('.$this->db->idate(mktime()).',';
			$sql.= " ".$this->db->idate($this->datepaye).", '".$this->total."', ".$this->paiementid.", '".$this->num_paiement."', '".$this->db->escape($this->note)."', ".$user->id.", 0)";

			dol_syslog("PaiementFourn::create sql=".$sql);
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
		            // Appel des triggers
		            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		            $interface=new Interfaces($this->db);
		            $result=$interface->run_triggers('PAYMENT_SUPPLIER_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
		            // Fin appel triggers
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog('PaiementFourn::Create Error '.$this->error, LOG_ERR);
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
	 *      \brief      Supprime un paiement ainsi que les lignes qu'il a genere dans comptes
	 *                  Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
	 *                  Si le paiement porte sur au moins une facture a "payee", on refuse
	 *      \return     int     <0 si ko, >0 si ok
	 */
	function delete()
	{
		$bank_line_id = $this->bank_line;

		$this->db->begin();

		// Verifier si paiement porte pas sur une facture a l'etat payee
		// Si c'est le cas, on refuse la suppression
		$billsarray=$this->getBillsArray('paye=1');
		if (is_array($billsarray))
		{
			if (sizeof($billsarray))
			{
				$this->error='Impossible de supprimer un paiement portant sur au moins une facture a l\'etat paye';
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
				$this->error='Impossible de supprimer un paiement qui a genere une ecriture qui a ete rapprochee';
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
    			$accline->fetch($bank_line_id);
				$result=$accline->delete();
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

	/*
	 *    \brief      Information sur l'objet
	 *    \param      id      id du paiement dont il faut afficher les infos
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, datec, fk_user_author, tms';
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
	 *      \brief      Retourne la liste des factures sur lesquels porte le paiement
	 *      \param      filter          Critere de filtre
	 *      \return     array           Tableau des id de factures
	 */
	function getBillsArray($filter='')
	{
		$sql = 'SELECT fk_facturefourn';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql.= ' WHERE pf.fk_facturefourn = f.rowid AND fk_paiementfourn = '.$this->id;
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
				$billsarray[$i]=$obj->fk_facturefourn;
				$i++;
			}

			return $billsarray;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('PaiementFourn::getBillsArray Error '.$this->error.' - sql='.$sql);
			return -1;
		}
	}

	/**
	*    	\brief      Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	*    	\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	*    	\return     string		Libelle
	*/
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	*    	\brief      Renvoi le libelle d'un statut donne
	*    	\param      status      Statut
	*		\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	*    	\return     string      Libelle du statut
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$text=$this->ref;	// Sometimes ref contains label
		if (preg_match('/^\((.*)\)$/i',$text,$reg))
		{
			// Label g诩rique car entre parenth粥s. On l'affiche en le traduisant
			if ($reg[1]=='paiement') $reg[1]='Payment';
			$text=$langs->trans($reg[1]);
		}

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowPayment"),'payment').$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$text.$lienfin;
		return $result;
	}

    /**
     *      \brief      Updates the payment number
     *      \param      string          New num
     *      \return     int             -1 on error, 0 otherwise
     */
    function update_num($num)
    {
    	if(!empty($num) && $this->statut!=1)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."paiementfourn";
            $sql.= " SET num_paiement = '".$this->db->escape($num)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("PaiementFourn::update_num sql=".$sql);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->numero = $this->db->escape($num);
                return 0;
            }
            else
            {
                $this->error='PaiementFourn::update_num Error -1 '.$this->db->error();
                dol_syslog('PaiementFourn::update_num error '.$this->error, LOG_ERR);
                return -1;
            }
        }
        return -1; //no num given or already validated
    }
    /**
     *      \brief      Updates the payment date
     *      \param      string          New date
     *      \return     int             -1 on error, 0 otherwise
     */
    function update_date($date)
    {
        if(!empty($date) && $this->statut!=1)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."paiementfourn";
            $sql.= " SET datep = ".$this->db->idate($date);
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("PaiementFourn::update_date sql=".$sql);
            $result = $this->db->query($sql);
            if ($result)
            {
            	$this->datepaye = $date;
                $this-> date = $date;
                return 0;
            }
            else
            {
                $this->error='PaiementFourn::update_date Error -1 '.$this->db->error();
                dol_syslog('PaiementFourn::update_date error '.$this->error, LOG_ERR);
                return -1;
            }
        }
        return -1; //no date given or already validated
    }
}
?>
