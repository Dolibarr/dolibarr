<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/*!
	    \file       htdocs/facturefourn.class.php
        \ingroup    facture
		\brief      Fichier de la classe des factures fournisseurs
		\version    $Revision$
*/


/*! \class FactureFourn
		\brief Classe permettant la gestion des factures fournisseurs
*/

class FactureFourn
{
  var $id;
  var $db;
  var $socid;
  var $number;
  var $author;
  var $libelle;
  var $date;
  var $ref;
  var $amount;
  var $remise;
  var $tva;
  var $total_ht;
  var $total_tva;
  var $total_ttc;
  var $note;
  var $db_table;
  var $propalid;
  var $lignes;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  soc_idp     id societe ("" par defaut)
   *    \param  facid       id facture ("" par defaut)
   */
  function FactureFourn($DB, $soc_idp="", $facid="")
  {
    $this->db = $DB ;
    $this->socidp = $soc_idp;
    $this->products = array();
    $this->db_table = MAIN_DB_PREFIX."facture";
    $this->amount = 0;
    $this->remise = 0;
    $this->tva = 0;
    $this->total = 0;
    $this->propalid = 0;
    $this->id = $facid;

    $this->lignes = array();
  }

  /**
   *    \brief      Création de la facture en base
   *    \param      user       object utilisateur qui crée
   *
   */
  function create($user)
  {

    /*
     *  Insertion dans la base
     */
    $socid = $this->socidp;
    $number = $this->number;
    $amount = $this->amount;
    $remise = $this->remise;

    if (! $remise)
      {
	$remise = 0 ;
      }

    $totalht = ($amount - $remise);
    $tva = tva($totalht);
    $total = $totalht + $tva;
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, fk_user_author) ";
    $sql .= " VALUES ('".$this->number."','".$this->libelle."',". $this->socid.", now(),".$this->date.",'".$this->note."', ".$user->id.");";

    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id();

	for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
	  {	 

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_det (fk_facture_fourn)";
	    $sql .= " VALUES ($this->id);";
	    if ($this->db->query($sql) ) 
	      {
		$idligne = $this->db->last_insert_id();

		$this->updateline($idligne,
				    $this->lignes[$i][0],
				    $this->lignes[$i][1],
				    $this->lignes[$i][2],
				    $this->lignes[$i][3]);
	      }
	  }
	  
        /*
         * Mise à jour prix
         */
        
        $this->updateprice($this->id);
        
        return $this->id;
    }
    else
    {
        if ($this->db->errno() == $this->db->ERROR_DUPLICATE) {
            print "Erreur : Une facture possédant cet id existe déjà";
        }
        else {
    	  dolibarr_print_error($this->db);
        }
    
        return 0;
    }
   }

  /**
   *    \brief      Recupére l'objet facture et ses lignes de factures
   *    \param      rowid       id de la facture a récupérer
   */
  function fetch($rowid)
    {
      $sql = "SELECT fk_soc,libelle,facnumber,amount,remise,".$this->db->pdate(datef)."as df";
      $sql .= ", total_ht, total_tva, total_ttc, fk_user_author";
      $sql .= ", fk_statut, paye";
      $sql .= ", s.nom as socnom, s.idp as socidp";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f,".MAIN_DB_PREFIX."societe as s";
      $sql .= " WHERE f.rowid=$rowid AND f.fk_soc = s.idp ;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id      = $rowid;
	      $this->datep   = $obj->df;
	      $this->ref     = $obj->facnumber;
	      $this->libelle = $obj->libelle;

	      $this->remise = $obj->remise;
	      $this->socidp = $obj->fk_soc;

	      $this->total_ht  = $obj->total_ht;
	      $this->total_tva = $obj->total_tva;
	      $this->total_ttc = $obj->total_ttc;

	      $this->author    = $obj->fk_user_author;

	      $this->statut = $obj->fk_statut;
	      $this->paye   = $obj->paye;

	      $this->socidp = $obj->socidp;
	      $this->socnom = $obj->socnom;

	      $this->db->free();

	      /* 
	       * Lignes
	       */
	      $sql = "SELECT rowid,description, pu_ht, qty, tva_taux, tva, total_ht, total_ttc FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE fk_facture_fourn=".$this->id;
      
	      if ($this->db->query($sql) )
		{
		  $num = $this->db->num_rows();
		  $i = 0;
		  if ($num)
		    {
		      while ($i < $num)
			{
			  $obj = $this->db->fetch_object($i);
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
   * \brief     Supprime la facture
   * \param     rowid      id de la facture à supprimer
   */
  function delete($rowid)
    {

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn WHERE rowid = $rowid AND fk_statut = 0";

      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->affected_rows() )
	    {
	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE fk_facture_fourn = $rowid;";

	      if ($this->db->query( $sql) )
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
   * \brief     Tag la facture comme payée complètement
   * \param     userid        utilisateur qui modifie l'état
   */
  function set_payed($userid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set paye = 1 WHERE rowid = ".$this->id;
      $result = $this->db->query( $sql);
      if (! $result) {
    	  dolibarr_print_error($this->db);
      }
    }

  /**
   * \brief     Tag la facture comme validée et valide la facture
   * \param     userid        utilisateur qui valide la facture
   */
  function set_valid($userid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set fk_statut = 1, fk_user_valid = $userid WHERE rowid = ".$this->id;
      $result = $this->db->query( $sql);
      if (! $result) {
    	  dolibarr_print_error($this->db);
      }
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
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_det (fk_facture_fourn)";
    $sql .= " VALUES ($this->id);";
    if ($this->db->query($sql) ) 
      {
	$idligne = $this->db->last_insert_id();
	
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
   * \return    int     0 si erreur
   */
  function updateline($id, $label, $puht, $tauxtva, $qty=1)
  {
    if (is_numeric($puht) && is_numeric($qty))
      {
	$totalht  = ($puht * $qty);
	$tva      = ($totalht * $tauxtva /  100);
	$totalttc = $totalht + $tva;
		
	$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det ";
	$sql .= "SET description ='".$label."'";
	$sql .= ", pu_ht = "  .ereg_replace(",",".",$puht);
	$sql .= ", qty ="     .ereg_replace(",",".",$qty);
	$sql .= ", total_ht=" .ereg_replace(",",".",$totalht);
	$sql .= ", tva="      .ereg_replace(",",".",$tva);
	$sql .= ", tva_taux=" .ereg_replace(",",".",$tauxtva);
	$sql .= ", total_ttc=".ereg_replace(",",".",$totalttc);
	
	$sql .= " WHERE rowid = $id";
	
	if (! $this->db->query($sql) )
	  {
    	  dolibarr_print_error($this->db);
	  }

	// Mise a jour prix facture
	$this->updateprice($this->id);
      }
  }

  /**
   * \brief     Supprime une ligne facture de la base
   * \param     rowid      id de la ligne de facture a supprimer
   */
  function deleteline($rowid)
  {
    // Supprime ligne
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det ";
    $sql .= " WHERE rowid = $rowid";
    
    if (! $this->db->query($sql) ) 
      {
    	  dolibarr_print_error($this->db);
      }
    
    // Mise a jour prix facture
    $this->updateprice($this->id);
    
    return 1;
  }

  /**
   * \brief     Mise à jour des sommes de la facture
   * \param     facid      id de la facture a modifier
   */
  function updateprice($facid)
  {    
    $total_ht  = 0;
    $total_tva = 0;
    $total_ttc = 0;

    $sql = "SELECT sum(total_ht), sum(tva), sum(total_ttc) FROM ".MAIN_DB_PREFIX."facture_fourn_det";
    $sql .= " WHERE fk_facture_fourn = $facid;";
    
    $result = $this->db->query($sql);
    
    if ($result)
      {
	if ($this->db->num_rows() )
	  {
	    $row = $this->db->fetch_row();
	    $total_ht  = $row[0];
	    $total_tva = $row[1];
	    $total_ttc = $row[2];

	    if ($total_ht == '')
	      $total_ht = 0;

	    if ($total_tva == '')
	      $total_tva = 0;

	    if ($total_ttc == '')
	      $total_ttc = 0;

	  }
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn SET";
	$sql .= " total_ht = ". ereg_replace(",",".",$total_ht);
	$sql .= ",total_tva = ".ereg_replace(",",".",$total_tva);
	$sql .= ",total_ttc = ".ereg_replace(",",".",$total_ttc);
	$sql .= " WHERE rowid = $facid ;";

	$result = $this->db->query($sql);	
      }
    else 
      {
    	  dolibarr_print_error($this->db);
      }
  }
  
  /**
   * \todo RODO
   *
   */
  function pdf()
    {

    }

  /**
   * \brief     Renvoi un libellé du statut
   * \param     paye        etat paye
   * \param     statut      id statut
   */
    function LibStatut($paye,$statut)
    {
        global $langs;
        $langs->load("bills");
        if (! $paye)
        {
            if ($statut == 0) return $langs->trans("BillStatusDraft");
            if ($statut == 3) return $langs->trans("BillStatusCanceled");
            return $langs->trans("BillStatusValidated");
        }
        else
        {
            return $langs->trans("BillStatusPayed");
        }
    }
  
}
?>
