<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/paiement.class.php
        \ingroup    facture
        \brief      Fichier de la classe des paiement de factures clients
        \version    $Revision$
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
	var $author;
	var $paiementid;	// Type de paiement. Stocké dans fk_paiement
						// de llx_paiement qui est lié aux types de
						//paiement de llx_c_paiement
	var $num_paiement;	// Numéro du CHQ, VIR, etc...
	var $bank_account;	// Id compte bancaire du paiement
	var $bank_line;     // Id de la ligne d'écriture bancaire
	var $note;
	// fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
	// fk_paiement dans llx_paiement_facture est le rowid du paiement

	var $db;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  soc_idp     id societe ('' par defaut)
   */

	function Paiement($DB, $soc_idp='')
	{
		$this->db = $DB ;
	}

    /**
     *    \brief      Récupère l'objet paiement
     *    \param      id      id du paiement a récupérer
     *    \return     int     <0 si ko, >0 si ok
     */
    function fetch($id)
	{
		$sql = 'SELECT p.rowid,'.$this->db->pdate('p.datep').' as dp, p.amount, p.statut, p.fk_bank';
		$sql .=', c.libelle as paiement_type';
		$sql .= ', p.num_paiement, p.note, b.fk_account';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid ';
		$sql .= ' WHERE p.fk_paiement = c.id';
		$sql .= ' AND p.rowid = '.$id;

		if ($this->db->query($sql))
		{
			if ($this->db->num_rows())
			{
				$obj = $this->db->fetch_object();
				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->date           = $obj->dp;
				$this->numero         = $obj->num_paiement;
				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
				$this->montant        = $obj->amount;
				$this->note           = $obj->note;
				$this->type_libelle   = $obj->paiement_type;
				$this->statut         = $obj->statut;
				return 1;
			}
			else
			{
				return -2;
			}
			$this->db->free();
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}

  /**
   *    \brief      Création du paiement en base
   *    \param      user        object utilisateur qui crée
   *    \return     int         id du paiement crée, < 0 si erreur
   */

	function create($user)
	{
		$sql_err = 0;

		$this->db->begin();

		$total = 0.0;
		foreach ($this->amounts as $key => $value)
		{
			$val = $value;
			$val = str_replace(' ','',$val);
			$val = str_replace(',','.',$val);
			$val = round($val, 2);
			$val = str_replace(',','.',$val);
			if (is_numeric($val))
			{
				$total += $val;
			}
			$this->amounts[$key] = $val;
		}
		$total = str_replace(',','.',$total);
		if ($total <> 0) /* On accepte les montants négatifs pour les rejets de prélèvement */
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement (datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)';
			$sql .= ' VALUES (now(), '.$this->datepaye.', \''.$total.'\', '.$this->paiementid.', \''.$this->num_paiement.'\', \''.$this->note.'\', '.$user->id.')';
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiement');
				foreach ($this->amounts as $key => $amount)
				{
					$facid = $key;
					if (is_numeric($amount) && $amount <> 0)
					{
						$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_facture, fk_paiement, amount)';
						$sql .= ' VALUES ('.$facid.','. $this->id.',\''.$amount.'\')';
						if (! $this->db->query($sql) )
						{
							dolibarr_syslog('Paiement::Create Erreur INSERT dans paiement_facture '.$facid);
							$sql_err++;
						}
					}
					else
					{
						dolibarr_syslog('Paiement::Create Montant non numérique');
					}
				}
			}
			else
			{
				dolibarr_syslog('Paiement::Create Erreur INSERT dans paiement');
				$sql_err++;
			}
		}

		if ( $total <> 0 && $sql_err == 0 ) // On accepte les montants négatifs
		{
			$this->db->commit();
			dolibarr_syslog('Paiement::Create Ok Total = '.$total);
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			dolibarr_syslog('Paiement::Create Erreur');
			return -1;
		}
	}

  /**
   *    \brief      Affiche la liste des modes de paiement possible
   *    \param      name        nom du champ select
   *    \param      filtre      filtre sur un sens de paiement particulier, norme ISO (CRDT=Mode propre à un crédit, DBIT=mode propre à un débit)
   *    \param      id          ???
   */
	function select($name, $filtre='', $id='')
	{
		$form = new Form($this->db);

		if ($filtre == 'CRDT')
		{
			$sql = 'SELECT id, libelle FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active=1 AND type IN (0,2) ORDER BY libelle';
		}
		elseif ($filtre == 'DBIT')
		{
			$sql = 'SELECT id, libelle FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active=1 AND type IN (1,2) ORDER BY libelle';
		}
		else
		{
			$sql = 'SELECT id, libelle FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active=1 ORDER BY libelle';
		}
		$form->select($name, $sql, $id);
	}

    /**
     *      \brief      Supprime un paiement ainsi que les lignes qu'il a généré dans comptes
     *                  Si le paiement porte sur un écriture compte qui est rapprochée, on refuse
     *                  Si le paiement porte sur au moins une facture à "payée", on refuse
     *      \return     int     <0 si ko, >0 si ok
     */
	function delete()
	{
      	$bank_line_id = $this->bank_line;

		$this->db->begin();

        // Vérifier si paiement porte pas sur une facture à l'état payée
        // Si c'est le cas, on refuse la suppression
        $billsarray=$this->getBillsArray('paye=1');
        if (is_array($billsarray))
        {
            if (sizeof($billsarray))
            {
                $this->error="Impossible de supprimer un paiement portant sur au moins une facture à l'état payé";
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->db->rollback();
            return -2;
        }       

      	// Vérifier si paiement ne porte pas sur ecriture bancaire rapprochée
      	// Si c'est le cas, on refuse le paiement
		if ($bank_line_id)
		{
			$accline = new AccountLine($this->db,$bank_line_id);
            $accline->fetch($bank_line_id);
            if ($accline->rappro)
            {
                $this->error="Impossible de supprimer un paiement qui a généré une écriture qui a été rapprochée";
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

    		// Supprimer l'écriture bancaire si paiement lié à écriture
    		if ($bank_line_id)
    		{
    			$acc = new Account($this->db);
    			$result=$acc->deleteline($bank_line_id);
    			if ($result < 0)
    			{
                    $this->error=$acc->error;
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
     *      \brief      Mise a jour du lien entre le paiement et la ligne générée dans llx_bank
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
			dolibarr_print_error($this->db);
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
		$result = $this->db->query($sql);
		if ($result)
		{
			return 0;
		}
		else
		{
			dolibarr_syslog('Paiement::Valide Error -1');
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

		if ($this->db->query($sql))
		{
			if ($this->db->num_rows())
			{
				$obj = $this->db->fetch_object();
				$this->id = $obj->idp;
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
			$this->db->free();
		}
		else
		{
			dolibarr_print_error($this->db);
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
			dolibarr_syslog('Paiement::getBillsArray Error '.$this->error.' - sql='.$sql);
			return -1;
		}
	}

}
?>
