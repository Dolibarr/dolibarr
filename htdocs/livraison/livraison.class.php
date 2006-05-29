<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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

/**
        \file       htdocs/livraison/livraison.class.php
        \ingroup    livraison
        \brief      Fichier de la classe de gestion des bons de livraison
        \version    $Revision$
*/


/** 
        \class      Livraison
		\brief      Classe de gestion des bons de livraison
*/
class Livraison 
{
  var $db ;
  var $id ;
  var $brouillon;
  var $entrepot_id;

  /**
   * Initialisation
   *
   */
  function Livraison($DB)
    {
      $this->db = $DB;
      $this->lignes = array();

      $this->statuts[-1] = "Annulée";
      $this->statuts[0] = "Brouillon";
      $this->statuts[1] = "Validée";

      $this->products = array();
    }

  /**
   *    \brief      Créé bon de livraison en base
   *    \param      user        Objet du user qui crée
   *    \return     int         <0 si erreur, id livraison créée si ok
   */
  function create($user)
    {
        global $conf;
        require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";
        $error = 0;
        
        /* On positionne en mode brouillon le bon de livraison */
        $this->brouillon = 1;
    
        $this->user = $user;
    
        $this->db->begin();
    
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."livraison (fk_soc, fk_soc_contact, date_creation, fk_user_author, fk_commande";
        if ($this->commande_id) $sql.= ", fk_commande";
        if ($this->expedition_id) $sql.= ", fk_expedition";
        $sql.= ")";
        $sql.= " VALUES ($this->soc_id, $this->contactid, now(), $user->id, $this->commande_id";
        if ($this->commande_id) $sql.= ", $this->commande_id";
        if ($this->expedition_id) $sql.= ", $this->expedition_id";
        $sql.= ")";
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."livraison");
    
            $sql = "UPDATE ".MAIN_DB_PREFIX."livraison SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
            if ($this->db->query($sql))
            {
    
                if ($conf->expedition->enabled)
                {
                	$this->expedition = new Expedition($this->db);
                  $this->expedition->id = $this->expedition_id;
                  $this->expedition->fetch_lignes();
                }
                else
                {
                	$this->commande = new Commande($this->db);
                	$this->commande->id = $this->commande_id;
                	$this->commande->fetch_lignes();
                }
    
                /*
                *  Insertion des produits dans la base
                */
                for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
                {
                    //TODO
                    if (! $this->create_line(0, $this->lignes[$i]->commande_ligne_id, $this->lignes[$i]->qty))
                    {
                        $error++;
                    }
                }

                /*
                 *
                 */
                $sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 2 WHERE rowid=".$this->commande_id;
                if (! $this->db->query($sql))
                {
                    $error++;
                }
        
                if ($error==0)
                {
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                    $error++;
                    $this->error=$this->db->error()." - sql=$sql";
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                $error++;
                $this->error=$this->db->error()." - sql=$sql";
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $error++;
            $this->error=$this->db->error()." - sql=$sql";
            $this->db->rollback();
            return -1;
        }
    }

  /**
   *
   *
   */
  function create_line($transaction, $commande_ligne_id, $qty)
  {
    $error = 0;

    $idprod = 0;
    $j = 0;
    while (($j < sizeof($this->commande->lignes)) && idprod == 0)
      {
	if ($this->commande->lignes[$j]->id == $commande_ligne_id)
	  {
	    $idprod = $this->commande->lignes[$j]->product_id;
	  }
	$j++;
      }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."livraisondet (fk_livraison, fk_commande_ligne, qty)";
    $sql .= " VALUES ($this->id,".$commande_ligne_id.",".$qty.")";
    
    if (! $this->db->query($sql) )
      {
	$error++;
      }

    if ($error == 0 )
      {
	return 1;
      }
  }
  /** 
   *
   * Lit un bon de livraison
   *
   */
    function fetch ($id)
    {
        global $conf;
    
        $sql = "SELECT l.rowid, l.fk_soc, l.fk_soc_contact, l.date_creation, l.ref, l.fk_user_author,";
        $sql .=" l.fk_statut, l.fk_commande, l.fk_expedition, l.fk_user_valid, l.note, l.note_public";
        $sql .= ", ".$this->db->pdate("l.date_livraison")." as date_livraison, fk_adresse_livraison, model_pdf";
        $sql .= " FROM ".MAIN_DB_PREFIX."livraison as l";
        $sql .= " WHERE l.rowid = $id";
    
        $result = $this->db->query($sql) ;
    
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);
    
            $this->id                   = $obj->rowid;
            $this->socid                = $obj->fk_soc;
            $this->contact_id           = $obj->fk_soc_contact;
            $this->ref                  = $obj->ref;
            $this->statut               = $obj->fk_statut;
            $this->commande_id          = $obj->fk_commande;
            $this->expedition_id        = $obj->fk_expedition;
            $this->user_author_id       = $obj->fk_user_author;
            $this->user_valid_id        = $obj->fk_user_valid;
            $this->date                 = $obj->date_livraison;
            $this->adresse_livraison_id = $obj->fk_entrepot;
            $this->note                 = $obj->note;
            $this->note_public          = $obj->note_public;
            $this->modelpdf            = $obj->model_pdf;
            $this->db->free();
    
            if ($this->statut == 0) $this->brouillon = 1;
    
            $file = $conf->livraison->dir_output . "/" .get_exdir($livraison->id) . "/" . $this->id.".pdf";
            $this->pdf_filename = $file;
    
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

  /**
   *        \brief      Valide l'expedition, et met a jour le stock si stock géré
   *        \param      user        Objet de l'utilisateur qui valide
   *        \return     int
   */
    function valid($user)
    {
        global $conf;
        
        require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";
    
        dolibarr_syslog("expedition.class.php::valid");

        $this->db->begin();
        
        $error = 0;
        
        if ($user->rights->expedition->livraison->valider)
        {
        	if (defined('LIVRAISON_ADDON'))
        	{
        		if (is_readable(DOL_DOCUMENT_ROOT .'/livraison/mods/'.LIVRAISON_ADDON.'.php'))
        		{
        			require_once DOL_DOCUMENT_ROOT .'/livraison/mods/'.LIVRAISON_ADDON.'.php';
        			
        			// Definition du nom de module de numerotation de commande
        			$modName=COMMANDE_ADDON;

					    // Recuperation de la nouvelle reference
					    $objMod = new $modName($this->db);
					    $soc = new Societe($this->db);
					    $soc->fetch($this->soc_id);
					    
					    // on vérifie si le bon de livraison est en numérotation provisoire
					    $comref = substr($this->ref, 1, 4);
					    if ($comref == PROV)
					    {
						    $num = $objMod->commande_get_num($soc);
					    }
					    else
					    {
						    $num = $this->ref;
					    }
        		
            // \todo Tester si non dejà au statut validé. Si oui, on arrete afin d'éviter
            //       de décrémenter 2 fois le stock.

            $sql = "UPDATE ".MAIN_DB_PREFIX."livraison SET ref='$num', fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
            $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
    
            if ($this->db->query($sql) )
            {
/*
                
                // Si module stock géré et que expedition faite depuis un entrepot
                if ($conf->stock->enabled && $this->entrepot_id)
                {
                    
                     //Enregistrement d'un mouvement de stock pour chaque produit de l'expedition
                     

                    dolibarr_syslog("expedition.class.php::valid enregistrement des mouvements");

                    $sql = "SELECT cd.fk_product, ed.qty ";
                    $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."expeditiondet as ed";
                    $sql.= " WHERE ed.fk_expedition = $this->id AND cd.rowid = ed.fk_commande_ligne";
        
                    $resql=$this->db->query($sql);
                    if ($resql)
                    {
                        $num = $this->db->num_rows($resql);
                        $i=0;
                        while($i < $num)
                        {
                            dolibarr_syslog("expedition.class.php::valid movment $i");

                            $obj = $this->db->fetch_object($resql);

                            $mouvS = new MouvementStock($this->db);
                            $result=$mouvS->livraison($user, $obj->fk_product, $this->entrepot_id, $obj->qty);
                            if ($result < 0)
                            {
                                $this->db->rollback();
                                $this->error=$this->db->error()." - sql=$sql";
                                dolibarr_syslog("expedition.class.php::valid ".$this->error);
                                return -3;
                            }
                            $i++;
                        }
                        
                    }
                    else
                    {
                        $this->db->rollback();
                        $this->error=$this->db->error()." - sql=$sql";
                        dolibarr_syslog("expedition.class.php::valid ".$this->error);
                        return -2;
                        
                    }
                }
*/
                return 1;
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->error()." - sql=$sql";
                dolibarr_syslog("expedition.class.php::valid ".$this->error);
                return -1;
            }
          }
         }
        }
        else
        {
            $this->error="Non autorise";
            dolibarr_syslog("livraison.class.php::valid ".$this->error);
            return -1;
        }

        $this->db->commit();
        //dolibarr_syslog("expedition.class.php::valid commit");
        return 1;
    }


  /**
   * Ajoute un produit
   *
   */
  function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6, $p_product_id=0, $remise_percent=0)
    {
      if ($this->statut == 0)
	{
	  if (strlen(trim($p_qty)) == 0)
	    {
	      $p_qty = 1;
	    }

	  $p_price = price2num($p_price);

	  $price = $p_price;
	  $subprice = $p_price;
	  if ($remise_percent > 0)
	    {
	      $remise = round(($p_price * $remise_percent / 100), 2);
	      $price = $p_price - $remise;
	    }

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."commandedet (fk_commande, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
	  $sql .= " (".$this->id.", $p_product_id,". $p_qty.",". $price.",".$p_tva_tx.",'". addslashes($p_desc) ."',$remise_percent, $subprice) ; ";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      if ($this->update_price() > 0)
		{	      
		  return 1;
		}
	      else
		{
		  return -1;
		}
	    }
	  else
	    {
	      print $this->db->error();
	      print "<br>".$sql;
	      return -2;
	    }
	}
    }
  /**
   * Ajoute une ligne
   *
   */
  function addline( $id, $qty )
    {
      $num = sizeof($this->lignes);
      $ligne = new ExpeditionLigne();

      $ligne->commande_ligne_id = $id;
      $ligne->qty = $qty;

      $this->lignes[$num] = $ligne;
    }

  /** 
   *
   *
   */
  function delete_line($idligne)
    {
      if ($this->statut == 0)
	{
	  $sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE rowid = $idligne";
	  
	  if ($this->db->query($sql) )
	    {
	      $this->update_price();
	      
	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
    }
  /**
   * Supprime la fiche
   *
   */
  function delete()
  {
    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."livraisondet WHERE fk_livraison = $this->id ;";
    if ( $this->db->query($sql) ) 
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."livraison WHERE rowid = $this->id;";
	if ( $this->db->query($sql) ) 
	  {
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    $this->db->rollback();
	    return -2;
	  }
      }
    else
      {
	$this->db->rollback();
	return -1;
      }
  }


    /**
     * Genere le pdf
     */
    function PdfWrite()
    {
        global $conf;
    
        //LIVRAISON_ADDON_PDF
        if (defined("LIVRAISON_ADDON_PDF") && strlen(LIVRAISON_ADDON_PDF) > 0)
        {
            $module_file_name = DOL_DOCUMENT_ROOT."/livraison/mods/pdf/pdf_".LIVRAISON_ADDON_PDF.".modules.php";
    
            $mod = "pdf_".LIVRAISON_ADDON_PDF;
            $this->fetch_commande();
    
            require_once($module_file_name);
    
            $pdf = new $mod($this->db);
    
            $dir = $conf->livraison->dir_output . "/" .get_exdir($this->id);
    
            if (! file_exists($dir))
            {
                create_exdir($dir);
            }
    
            $file = $dir . $this->id . ".pdf";
    
            if (file_exists($dir))
            {
                $pdf->generate($this, $file);
            }
        }
    }

  /*
   * Lit la commande associée
   *
   */
  function fetch_commande()
  {
    $this->commande =& new Commande($this->db);
    $this->commande->fetch($this->commande_id);
  }


  function fetch_lignes()
  {
    $this->lignes = array();

    $sql = "SELECT c.label, c.description, c.qty as qtycom, l.qty as qtyliv";    
    $sql .= ", c.fk_product, c.price, p.ref";
    $sql .= " FROM ".MAIN_DB_PREFIX."livraisondet as l";
    $sql .= " , ".MAIN_DB_PREFIX."commandedet as c";
    $sql .= " , ".MAIN_DB_PREFIX."product as p";

    $sql .= " WHERE l.fk_livraison = ".$this->id;
    $sql .= " AND l.fk_commande_ligne = c.rowid";
    $sql .= " AND c.fk_product = p.rowid";


    $resql = $this->db->query($sql);
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	  {
	    $ligne = new LivraisonLigne();

	    $obj = $this->db->fetch_object($resql);

	    $ligne->product_id     = $obj->fk_product;
	    $ligne->qty_commande   = $obj->qtycom;
	    $ligne->qty_livre      = $obj->qtyliv;
	    $ligne->ref            = $obj->ref;
	    $ligne->label          = stripslashes($obj->label);
	    $ligne->description    = stripslashes($obj->description);
	    $ligne->price          = $obj->price;

	    $this->lignes[$i] = $ligne;	    
	    $i++;
	  }	      
	$this->db->free($resql);
      }

    return $this->lignes;
  }

}


class LivraisonLigne
{

}

?>
