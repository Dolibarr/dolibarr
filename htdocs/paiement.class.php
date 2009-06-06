<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C)      2005 Marc Barilley / Ocebo <marc@ocebo.com>
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
        \file       htdocs/paiement.class.php
        \ingroup    facture
        \brief      Fichier de la classe des paiement de factures clients
        \remarks	Cette classe est presque identique a paiementfourn.class.php
        \version    $Id$
*/


/**     \class      Paiement
  	    \brief      Classe permettant la gestion des paiements des factures clients
*/

class Paiement
{
	var $id;
	var $ref;
	var $facid;
	var $datepaye;
	var $amount;
	var $total;
	var $author;
	var $paiementid;	// Type de paiement. Stocke dans fk_paiement
						// de llx_paiement qui est lie aux types de
						//paiement de llx_c_paiement
	var $num_paiement;	// Numero du CHQ, VIR, etc...
	var $bank_account;	// Id compte bancaire du paiement
	var $bank_line;     // Id de la ligne d'ecriture bancaire
	var $note;
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement

	var $db;


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acces base de donnees
	 */
	function Paiement($DB)
	{
		$this->db = $DB ;
	}

    /**
     *    \brief      Recupere l'objet paiement
     *    \param      id      id du paiement a recuperer
     *    \return     int     <0 si ko, 0 si non trouve, >0 si ok
     */
    function fetch($id)
	{
		$sql = 'SELECT p.rowid,'.$this->db->pdate('p.datep').' as dp, p.amount, p.statut, p.fk_bank';
		$sql.= ', c.code as type_code, c.libelle as type_libelle';
		$sql.= ', p.num_paiement, p.note, b.fk_account';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement as p';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql.= ' WHERE p.fk_paiement = c.id';
		$sql.= ' AND p.rowid = '.$id;

		dol_syslog("Paiement::fetch sql=".$sql);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->date           = $obj->dp;
				$this->numero         = $obj->num_paiement;
				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
				$this->montant        = $obj->amount;
				$this->note           = $obj->note;
				$this->type_libelle   = $obj->type_libelle;
				$this->type_code      = $obj->type_code;
				$this->statut         = $obj->statut;

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
 	 *    \brief      Creation du paiement en base
	 *    \param      user        object utilisateur qui cree
	 *    \return     int         id du paiement cree, < 0 si erreur
	 */
	function create($user)
	{
		global $langs,$conf;

		$error = 0;

		// Nettoyage parametres
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

		if ($this->total <> 0) // On accepte les montants negatifs pour les rejets de prelevement
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement (datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)';
			$sql.= ' VALUES ('.$this->db->idate(mktime()).', '.$this->db->idate($this->datepaye).', \''.$this->total.'\', '.$this->paiementid.', \''.$this->num_paiement.'\', \''.addslashes($this->note).'\', '.$user->id.')';
			$resql = $this->db->query($sql);

			dol_syslog("Paiement::Create insert paiement sql=".$sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiement');

				// Insere liens montants / factures
				foreach ($this->amounts as $key => $amount)
				{
					$facid = $key;
					if (is_numeric($amount) && $amount <> 0)
					{
						$amount = price2num($amount);
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_facture, fk_paiement, amount)';
						$sql .= ' VALUES ('.$facid.','. $this->id.',\''.$amount.'\')';

						dol_syslog("Paiement::Create insert paiement_facture sql=".$sql);
						$resql=$this->db->query($sql);
						if (! $resql)
						{
							$this->error=$this->db->error();
							dol_syslog('Paiement::Create insert paiement_facture error='.$this->error, LOG_ERR);
							$error++;
						}
					}
					else
					{
						dol_syslog('Paiement::Create Montant non numérique');
					}
				}

				if (! $error)
				{
		            // Appel des triggers
		            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		            $interface=new Interfaces($this->db);
		            $result=$interface->run_triggers('PAYMENT_CUSTOMER_CREATE',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
		            // Fin appel triggers
				}
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog('Paiement::Create insert paiement error='.$this->error, LOG_ERR);
				$error++;
			}
		}

		if ($this->total <> 0 && ! $error) // On accepte les montants negatifs
		{
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
     *      \brief      Supprime un paiement ainsi que les lignes qu'il a genere dans comptes
     *                  Si le paiement porte sur un ecriture compte qui est rapprochee, on refuse
     *                  Si le paiement porte sur au moins une facture a "payee", on refuse
     *      \return     int     <0 si ko, >0 si ok
     */
	function delete()
	{
      	$bank_line_id = $this->bank_line;

		$this->db->begin();

        // Verifier si paiement porte pas sur une facture classee
        // Si c'est le cas, on refuse la suppression
        $billsarray=$this->getBillsArray('fk_statut > 1');
        if (is_array($billsarray))
        {
            if (sizeof($billsarray))
            {
                $this->error="Impossible de supprimer un paiement portant sur au moins une facture fermee";
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
      	// Si c'est le cas, on refuse le paiement
		if ($bank_line_id)
		{
			$accline = new AccountLine($this->db,$bank_line_id);
            $accline->fetch($bank_line_id);
            if ($accline->rappro)
            {
                $this->error="Impossible de supprimer un paiement qui a genere une ecriture qui a ete rapprochee";
                $this->db->rollback();
                return -3;
            }
        }

        // Efface la ligne de paiement (dans paiement_facture et paiement)
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement_facture';
		$sql.= ' WHERE fk_paiement = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'paiement';
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

    /**
     *      \brief      Mise a jour du lien entre le paiement et la ligne generee dans llx_bank
     *      \param      id_bank     Id compte bancaire
     */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE llx_paiement set fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return 0;
		}
	}

    /**
     *    \brief      Valide le paiement
     *    \return     int     <0 si ko, >0 si ok
     */
	function valide()
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'paiement SET statut = 1 WHERE rowid = '.$this->id;

		dol_syslog('Paiement::valide sql='.$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_syslog('Paiement::valide Error -1');
			return -1;
		}
	}

    /*
     *    \brief      Information sur l'objet
     *    \param      id      id du paiement dont il faut afficher les infos
     */
    function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec, fk_user_creat, fk_user_modif';
		$sql .= ', '.$this->db->pdate('tms').' as tms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as c';
		$sql .= ' WHERE c.rowid = '.$id;

		dol_syslog('Paiement::info sql='.$sql);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_creat)
				{
					$cuser = new User($this->db, $obj->fk_user_creat);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db, $obj->fk_user_modif);
					$muser->fetch();
					$this->user_modification = $muser;
				}
				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->tms;
			}
			$this->db->free($result);
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
			dol_syslog('Paiement::getBillsArray Error '.$this->error.' - sql='.$sql);
			return -1;
		}
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

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowPayment"),'payment').$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
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
