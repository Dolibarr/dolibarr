<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles  <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
		\file       htdocs/fourn/fournisseur.facture.class.php
		\ingroup    fournisseur,facture
		\brief      Fichier de la classe des factures fournisseurs
		\version    $Revision$
*/


/**
		\class      FactureFournisseur
		\brief      Classe permettant la gestion des factures fournisseurs
*/

class FactureFournisseur
{
	var $id;
	var $db;
	var $socidp;
	var $statut;
	var $paye;
	var $author;
	var $libelle;
	var $date;
	var $date_echeance;
	var $ref;
	var $amount;
	var $remise;
	var $tva;
	var $total_ht;
	var $total_tva;
	var $total_ttc;
	var $note;
	var $propalid;
	var $lignes;
	var $fournisseur;

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler accès base de données
	 *    \param  soc_idp     id societe ('' par defaut)
	 *    \param  facid       id facture ('' par defaut)
	 */
	function FactureFournisseur($DB, $soc_idp='', $facid='')
	{
		$this->db = $DB ;
		$this->socidp = $soc_idp;
		$this->products = array();
		$this->amount = 0;
		$this->remise = 0;
		$this->tva = 0;
		$this->total_ht = 0;
		$this->total_tva = 0;
		$this->total_ttc = 0;
		$this->propalid = 0;
		$this->id = $facid;

		$this->lignes = array();
	}

	/**
	 *    \brief      Création de la facture en base
	 *    \param      user        object utilisateur qui crée
	 *    \return     int         id facture si ok, < 0 si erreur
	 */
	function create($user)
	{
		global $langs;

		$socidp = $this->socidp;
		$number = $this->ref;
		$amount = $this->amount;
		$remise = $this->remise;

		$this->db->begin();

		if (! $remise) $remise = 0 ;
		$totalht = ($amount - $remise);

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, fk_user_author, date_lim_reglement) ';
		$sql .= " VALUES ('".addslashes($number)."','".addslashes($this->libelle)."',";
		$sql .= $this->socidp.", now(),'".$this->db->idate($this->date)."','".addslashes($this->note)."', ".$user->id.",'".$this->db->idate($this->date_echeance)."');";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');
			for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
			{
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
				$sql .= ' VALUES ('.$this->id.');';
				$resql_insert=$this->db->query($sql);
				if ($resql_insert)
				{
					$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');
					$this->updateline($idligne,
					$this->lignes[$i][0],
					$this->lignes[$i][1],
					$this->lignes[$i][2],
					$this->lignes[$i][3]);
				}
			}
			// Mise à jour prix
			if ($this->updateprice($this->id) > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$langs->trans('FailedToUpdatePrice');
				$this->db->rollback();
				return -3;
			}
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$langs->trans('ErrorBillRefAlreadyExists');
				$this->db->rollback();
				return -1;
			}
			else
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
		}
	}

	/**
	 *    \brief      Recupére l'objet facture et ses lignes de factures
	 *    \param      rowid       id de la facture a récupérer
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT libelle, facnumber, amount, remise, '.$this->db->pdate(datef).'as df';
		$sql .= ', total_ht, total_tva, total_ttc, fk_user_author';
		$sql .= ', fk_statut, paye, f.note,'.$this->db->pdate('date_lim_reglement').'as de';
		$sql .= ', s.nom as socnom, s.idp as socidp';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql .= ' WHERE f.rowid='.$rowid.' AND f.fk_soc = s.idp ;';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object();

				$this->id            = $rowid;
				$this->datep         = $obj->df;
				$this->date_echeance = $obj->de;
				$this->ref           = $obj->facnumber;
				$this->libelle       = $obj->libelle;

				$this->remise        = $obj->remise;
				$this->socidp        = $obj->socidp;

				$this->total_ht  = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_ttc = $obj->total_ttc;

				$this->author    = $obj->fk_user_author;

				$this->statut = $obj->fk_statut;
				$this->paye   = $obj->paye;

				$this->socnom = $obj->socnom;
				$this->note = $obj->note;
				$this->db->free($resql);

				/*
				* Lignes
				*/
				$sql = 'SELECT rowid,description, pu_ht, qty, tva_taux, tva, total_ht, total_ttc';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det';
				$sql .= ' WHERE fk_facture_fourn='.$this->id;
				$resql_rows = $this->db->query($sql);
				if ($resql_rows)
				{
					$num_rows = $this->db->num_rows($resql_rows);
					$i = 0;
					if ($num_rows)
					{
						while ($i < $num_rows)
						{
							$obj = $this->db->fetch_object();
							$this->lignes[$i][0] = stripslashes($obj->description);
							$this->lignes[$i][1] = $obj->pu_ht;
							$this->lignes[$i][2] = $obj->tva_taux;
							$this->lignes[$i][3] = $obj->qty;
							$this->lignes[$i][4] = $obj->total_ht;
							$this->lignes[$i][5] = $obj->tva;
							$this->lignes[$i][6] = $obj->total_ttc;
							$this->lignes[$i][7] = $obj->rowid;
							$i++;
						}
					}
					$this->db->free($resql_rows);
				}
				else
				{
					dolibarr_print_error($this->db);
				}
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

	/**
	 * \brief     Recupére l'objet fournisseur lié à la facture
	 *
	 */
	function fetch_fournisseur()
	{
		$fournisseur = new Fournisseur($this->db);
		$fournisseur->fetch($this->socidp);
		$this->fournisseur = $fournisseur;
	}

	/**
	 * \brief     Supprime la facture
	 * \param     rowid      id de la facture à supprimer
	 */
	function delete($rowid)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.$rowid.' AND fk_statut = 0;';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->affected_rows($resql);
			if ($num)
			{
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.$rowid.';';
				$resql2 = $this->db->query($sql);
				if ($resql2)
				{
					return 1;
				}
				else
				{
					dolibarr_print_error($this->db);
				}
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *      \brief      Tag la facture comme payée complètement
	 *      \param      user        Objet utilisateur qui modifie l'état
     *      \return     int         <0 si ko, >0 si ok
	 */
    function set_payed($user)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
		$sql.= ' SET paye = 1';
		$sql.= ' WHERE rowid = '.$this->id;
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->error();
			dolibarr_print_error($this->db);
            return -1;
		}
        return 1;
	}


	/**
	 *      \brief      Tag la facture comme validée
	 *      \param      user        Objet utilisateur qui valide la facture
     *      \return     int         <0 si ko, >0 si ok
	 */
	function set_valid($user)
	{
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET fk_statut = 1, fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->error();
			dolibarr_print_error($this->db);
            return -1;
		}
        return 1;
	}


	/**
	 * \brief     Ajoute une ligne de facture (associé à aucun produit/service prédéfini)
	 * \param     desc            description de la ligne
	 * \param     pu              prix unitaire
	 * \param     tauxtva         taux de tva
	 * \param     qty             quantité
	 */
	function addline($desc, $pu, $tauxtva, $qty)
	{
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
		$sql .= ' VALUES ('.$this->id.');';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');
			$this->updateline($idligne, $desc, $pu, $tauxtva, $qty);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
		// Mise a jour prix facture
		$this->updateprice($this->id);
	}

	/**
	 * \brief     Mets à jour une ligne de facture
	 * \param     id              id de la ligne de facture
	 * \param     label           description de la ligne
	 * \param     puht            prix unitaire
	 * \param     tauxtva         taux tva
	 * \param     qty             quantité
	 * \return    int             <0 si ko, >0 si ok
	 */
	function updateline($id, $label, $puht, $tauxtva, $qty=1)
	{
		$puht = price2num($puht);
		$qty  = price2num($qty);

		if (is_numeric($puht) && is_numeric($qty))
		{
			$totalht  = ($puht * $qty);
			$tva      = ($totalht * $tauxtva /  100);
			$totalttc = $totalht + $tva;

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn_det ';
			$sql .= 'SET ';
			$sql .= 'description =\''.addslashes($label).'\'';
			$sql .= ', pu_ht = '  .$puht;
			$sql .= ', qty ='     .$qty;
			$sql .= ', total_ht=' .price2num($totalht);
			$sql .= ', tva='      .price2num($tva);
			$sql .= ', tva_taux=' .price2num($tauxtva);
			$sql .= ', total_ttc='.price2num($totalttc);
			$sql .= ' WHERE rowid = '.$id.';';

			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Mise a jour prix facture
				return $this->updateprice($this->id);
			}
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
	}

	/**
	 * \brief     Supprime une ligne facture de la base
	 * \param     rowid      id de la ligne de facture a supprimer
	 */
	function deleteline($rowid)
	{
		// Supprime ligne
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det ';
		$sql .= ' WHERE rowid = '.$rowid.';';
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			dolibarr_print_error($this->db);
		}
		// Mise a jour prix facture
		$this->updateprice($this->id);
		return 1;
	}

	/**
	 *    \brief      Mise à jour des sommes de la facture
	 *    \param      facid       id de la facture a modifier
	 *    \return     int         <0 si ko, >0 si ok
	 */
	function updateprice($facid)
	{
		$total_ht  = 0;
		$total_tva = 0;
		$total_ttc = 0;

		$sql = 'SELECT sum(total_ht), sum(tva), sum(total_ttc) FROM '.MAIN_DB_PREFIX.'facture_fourn_det';
		$sql .= ' WHERE fk_facture_fourn = '.$facid.';';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$row = $this->db->fetch_row();
				$total_ht  = $row[0];
				$total_tva = $row[1];
				$total_ttc = $row[2];
			}
			$this->db->free($resql);

			$total_ht  = $total_ht  != '' ? $total_ht  : 0;
			$total_tva = $total_tva != '' ? $total_tva : 0;
			$total_ttc = $total_ttc != '' ? $total_ttc : 0;

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn SET';
			$sql .= ' total_ht = '. price2num($total_ht);
			$sql .= ',total_tva = '.price2num($total_tva);
			$sql .= ',total_ttc = '.price2num($total_ttc);
			$sql .= ' WHERE rowid = '.$facid.';';
			$resql2 = $this->db->query($sql);
			if ($resql2)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				return -2;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('datec').' as datec';
		$sql .= ', fk_user_author, fk_user_valid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as c';
		$sql .= ' WHERE c.rowid = '.$id;

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
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}
				$this->date_creation     = $obj->datec;
				//$this->date_validation   = $obj->datev; \todo La date de validation n'est pas encore gérée
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}
	
	
	/**
	 *    \brief      Retourne le libellé du statut d'une facture (brouillon, validée, abandonnée, payée)
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->paye,$this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libellé d'un statut donné
	 *    	\param      paye          	Etat paye
	 *		\param      statut        	Id statut
	 *    	\param      mode          	0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string			Libellé du statut
	 */
	function LibStatut($paye,$statut,$mode=0)
	{
		global $langs;
		$langs->load('bills');
	
		if ($mode == 0)
		{
			$prefix='';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed');
				return $langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 2)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0').' '.$langs->trans('Bill'.$prefix.'StatusDraft');
				if ($statut == 3) return img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5').' '.$langs->trans('Bill'.$prefix.'StatusCanceled');
				if ($alreadypayed <= 0) return img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1').' '.$langs->trans('Bill'.$prefix.'StatusNotPayed');
				return img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3').' '.$langs->trans('Bill'.$prefix.'StatusStarted');
			}
			else
			{
				return img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6').' '.$langs->trans('Bill'.$prefix.'StatusPayed');
			}
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0');
				if ($statut == 3) return img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5');
				if ($alreadypayed <= 0) return img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1');
				return img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3');
			}
			else
			{
				return img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6');
			}
		}
		if ($mode == 4)
		{
			if (! $paye)
			{
				if ($statut == 0) return img_picto($langs->trans('BillStatusDraft'),'statut0').' '.$langs->trans('BillStatusDraft');
				if ($statut == 3) return img_picto($langs->trans('BillStatusCanceled'),'statut5').' '.$langs->trans('BillStatusCanceled');
				if ($alreadypayed <= 0) return img_picto($langs->trans('BillStatusNotPayed'),'statut1').' '.$langs->trans('BillStatusNotPayed');
				return img_picto($langs->trans('BillStatusStarted'),'statut3').' '.$langs->trans('BillStatusStarted');
			}
			else
			{
				return img_picto($langs->trans('BillStatusPayed'),'statut6').' '.$langs->trans('BillStatusPayed');
			}
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if (! $paye)
			{
				if ($statut == 0) return $langs->trans('Bill'.$prefix.'StatusDraft').' '.img_picto($langs->trans('Bill'.$prefix.'StatusDraft'),'statut0');
				if ($statut == 3) return $langs->trans('Bill'.$prefix.'StatusCanceled').' '.img_picto($langs->trans('Bill'.$prefix.'StatusCanceled'),'statut5');
				if ($alreadypayed <= 0) return $langs->trans('Bill'.$prefix.'StatusNotPayed').' '.img_picto($langs->trans('Bill'.$prefix.'StatusNotPayed'),'statut1');
				return $langs->trans('Bill'.$prefix.'StatusStarted').' '.img_picto($langs->trans('Bill'.$prefix.'StatusStarted'),'statut3');
			}
			else
			{
				return $langs->trans('Bill'.$prefix.'StatusPayed').' '.img_picto($langs->trans('Bill'.$prefix.'StatusPayed'),'statut6');
			}
		}
	}

	/**
	 *    \brief      Renvoi le libellé court d'un statut donné
	 *    \param      paye        etat paye
	 *    \param      statut      id statut
	 *    \param      amount      amount already payed
	 *    \return     string      Libellé court du statut
	 */
	function PayedLibStatut($paye,$statut,$amount=0)
	{
		global $langs;
		$langs->load('bills');
		if (! $paye)
		{
			if ($statut == 0) return $langs->trans('BillShortStatusDraft');
			if ($statut == 3) return $langs->trans('BillStatusCanceled');
			if ($amount) return $langs->trans('BillStatusStarted');
			return $langs->trans('BillStatusNotPayed');
		}
		else
		{
			return $langs->trans('BillStatusPayed');
		}
	}


	/**
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user)
	{
		global $conf;

		$this->nbtodo=$this->nbtodolate=0;
		$sql = 'SELECT ff.rowid,'.$this->db->pdate('ff.date_lim_reglement').' as datefin';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as ff';
		$sql.= ' WHERE ff.paye=0';
		if ($user->societe_id) $sql.=' AND fk_soc = '.$user->societe_id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($obj->datefin < (time() - $conf->facture->fournisseur->warning_delay)) $this->nbtodolate++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

}
?>
