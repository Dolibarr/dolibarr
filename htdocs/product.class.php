<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file       htdocs/product.class.php
		\ingroup    produit
		\brief      Fichier de la classe des produits prédéfinis
		\version    $Revision$
*/


/*! \class Product
		\brief Classe permettant la gestion des produits prédéfinis
*/

class Product
{
  var $db ;

  var $id ;
  var $ref;
  var $libelle;
  var $description;
  var $price;
  var $tva_tx;
  var $type;
  var $seuil_stock_alerte;
  var $duration_value;
  var $duration_unit;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  id          id produit (0 par defaut)
   */
	 
  function Product($DB, $id=0)
    {
      $this->db = $DB;
      $this->id   = $id ;
      $this->envente = 0;
    }  

  /**
   *    \brief  Vérifie que la référence produit est non null
   *    \return int         1 si ok, 0 sinon
   */
	 
  function check()
    {
    $this->ref = ereg_replace("'","",stripslashes($this->ref));
    $this->ref = ereg_replace("\"","",stripslashes($this->ref));

      $err = 0;
      if (strlen(trim($this->ref)) == 0)
	$err++;
 
      if (strlen(trim($this->libelle)) == 0)
	$err++;
      
      if ($err > 0)
	{
	  return 0;
	}
      else
	{
	  return 1;
	}      
    }

  /**
   *    \brief  Insère le produit en base
   *    \param  user        utilisateur qui effectue l'insertion
   */
	 
  function create($user) 
    {
      $this->ref = ereg_replace("'","",stripslashes($this->ref));
      $this->ref = ereg_replace("\"","",stripslashes($this->ref));

      $sql = "SELECT count(*)";
      $sql .= " FROM ".MAIN_DB_PREFIX."product WHERE ref = '" .trim($this->ref)."'";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $row = $this->db->fetch_array();
	  if ($row[0] == 0)
	    {

	      if (strlen($this->price)==0)
		{
		  $this->price = 0;
		}
	      $this->price = round($this->price, 2);
	      
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."product (datec, fk_user_author, fk_product_type, price)";
	      $sql .= " VALUES (now(),".$user->id.",$this->type, " . ereg_replace(",",".",$this->price) . ")";
	      $result = $this->db->query($sql);
	      if ( $result )
		{
		  $id = $this->db->last_insert_id();
		  
		  if ($id > 0)
		    {
		      $this->id = $id;
		      $this->_log_price($user);
		      if ( $this->update($id, $user) )
			{
			  return $id;
			}
		    }
		  else
		    {
		      return -2;
		    }
		}
	      else
		{
		  dolibarr_print_error($this->db);
		  return -1;
		}
	    }
	  else
	    {
	      return -3;
	    }
	}
    }

  /**
   *    \brief  Mise à jour du produit en base
   *    \param  id          id du produit
   *    \param  user        utilisateur qui effectue l'insertion
   */
	 
  function update($id, $user)
  {
    $this->ref = ereg_replace("\"","",stripslashes($this->ref));
    $this->ref = ereg_replace("'","",stripslashes($this->ref));

    if (strlen(trim($this->libelle)) == 0)
      {
	$this->libelle = 'LIBELLE MANQUANT';
      }
    
    $sql = "UPDATE ".MAIN_DB_PREFIX."product ";
    $sql .= " SET label = '" . trim($this->libelle) ."'";
    if (strlen(trim($this->ref)))
      {
	$sql .= ",ref = '" . trim($this->ref) ."'";
      }
    $sql .= ",tva_tx = " . $this->tva_tx ;
    $sql .= ",envente = " . $this->envente ;
    $sql .= ",seuil_stock_alerte = " . $this->seuil_stock_alerte ;
    $sql .= ",description = '" . trim($this->description) ."'";
    $sql .= ",duration = '" . $this->duration_value . $this->duration_unit ."'";
    
    $sql .= " WHERE rowid = " . $id;
    
    if ( $this->db->query($sql) )
      {
	return 1;
      }
    else
      {
		  dolibarr_print_error($this->db);
      }
  }

  /**
   *    \brief  Ajoute un changement de prix en base dans l'historique des prix
   *    \param  user        utilisateur qui modifie le prix
   */
	 
  function _log_price($user) 
    {

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price ";
        $sql .= "WHERE date_price = now()";
        $sql .= " and fk_product = ".$this->id;
        $sql .= " and fk_user_author = ".$user->id;
        $sql .= " and price = ".ereg_replace(",",".",$this->price);
        $sql .= " and envente = ".$this->envente;
        $sql .= " and tva_tx = ".$this->tva_tx;
        
        $this->db->query($sql);
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price ";
        $sql .= " SET date_price = now()";
        $sql .= " ,fk_product = ".$this->id;
        $sql .= " ,fk_user_author = ".$user->id;
        $sql .= " ,price = ".ereg_replace(",",".",$this->price);
        $sql .= " ,envente = ".$this->envente;
        $sql .= " ,tva_tx = ".$this->tva_tx;
      
      if ($this->db->query($sql) )
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
   *    \brief  Modifie le prix d'un produit/service
   *    \param  id          id du produit/service à modifier
   *    \param  user        utilisateur qui modifie le prix
   */
	 
  function update_price($id, $user)
  {
    if (strlen(trim($this->price)) > 0 )
      {
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
	$sql .= " SET price = " . ereg_replace(",",".",$this->price);	  
	$sql .= " WHERE rowid = " . $id;
	
	if ( $this->db->query($sql) )
	  {
	    $this->_log_price($user);
	    return 1;
	  }
	else
	  {
        dolibarr_print_error($this->db);
	    return -1;
	  }
      }
    else
      {
	$this->mesg_error = "Prix saisi invalide.";
	return -2;
      }
  }

  /**
   *    \brief  Charge le produit/service en mémoire
   *    \param  id          id du produit/service à charger
   */
	 
  function fetch ($id)
    {    
      $sql = "SELECT rowid, ref, label, description, price, tva_tx, envente, nbvente, fk_product_type, duration, seuil_stock_alerte";
      $sql .= " FROM ".MAIN_DB_PREFIX."product WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id                 = $result["rowid"];
	  $this->ref                = $result["ref"];
	  $this->libelle            = stripslashes($result["label"]);
	  $this->description        = stripslashes($result["description"]);
	  $this->price              = $result["price"];
	  $this->tva_tx             = $result["tva_tx"];
	  $this->type               = $result["fk_product_type"];
	  $this->nbvente            = $result["nbvente"];
	  $this->envente            = $result["envente"];
	  $this->duration           = $result["duration"];
	  $this->duration_value     = substr($result["duration"],0,strlen($result["duration"])-1);
	  $this->duration_unit      = substr($result["duration"],-1);
	  $this->seuil_stock_alerte = $result["seuil_stock_alerte"];

	  $this->label_url = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">'.$this->libelle.'</a>';

	  $this->db->free();

	  $sql = "SELECT reel, fk_entrepot";
	  $sql .= " FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product = $id";
	  $result = $this->db->query($sql) ;
	  if ( $result )
	    {
	      $num = $this->db->num_rows();
	      $i=0;
	      if ($num > 0)
		{
		  while ($i < $num )
		    {
		      $row = $this->db->fetch_row($i);
		      $this->stock_entrepot[$row[1]] = $row[0];

		      $this->stock_reel = $this->stock_reel + $row[0];
		      $i++;
		    }

		  $this->no_stock = 0;
		}
	      else
		{
		  $this->no_stock = 1;
		}
	      $this->db->free();
	    }
	  return 1;
	}
      else
	{
	  dolibarr_print_error($this->db);
	  return -1;
	}
  }

  /**
   *    \brief  Renvoie le nombre de propale incluant le produit/service
   *    \param  socid       id societe
   *    \return int         nombre d'inclusion
   */
	 
  function count_propale($socid=0)
    {
      $sql = "SELECT pd.fk_propal";
      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."propal as pr";
      $sql .= " WHERE pr.rowid = pd.fk_propal AND p.rowid = pd.fk_product AND p.rowid = ".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND pr.fk_soc = $socid";
	}
      $sql .= " GROUP BY pd.fk_propal";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }

  /**
   *    \brief  Renvoie le nombre de client avec propale incluant le produit/service
   *    \param  socid       id societe
   *    \return int         nombre d'inclusion
   */
	 
  function count_propale_client($socid=0)
    {
      $sql = "SELECT pr.fk_soc";
      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."propal as pr";
      $sql .= " WHERE p.rowid = pd.fk_product AND pd.fk_propal = pr.rowid AND p.rowid = ".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND pr.fk_soc = $socid";
	}
      $sql .= " GROUP BY pr.fk_soc";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }

  /**
   *    \brief  Renvoie le nombre de facture incluant le produit/service
   *    \param  socid       id societe
   *    \return int         nombre d'inclusion
   */
	 
  function count_facture($socid=0)
    {
      $sql = "SELECT pd.fk_facture";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as pd, ".MAIN_DB_PREFIX."product as p";
      $sql .= ", ".MAIN_DB_PREFIX."facture as f";
      $sql .= " WHERE f.rowid = pd.fk_facture AND p.rowid = pd.fk_product AND p.rowid = ".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND f.fk_soc = $socid";
	}
      $sql .= " GROUP BY pd.fk_facture";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  return $this->db->num_rows();
	}
      else
	{
	  return 0;
	}
    }

  /**
   *    \brief  Renvoie des stats
   *    \param  sql         requete a exécuter
   *    \return array       tableau ?
   */
	 
  function _get_stats($sql)
    {
      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $arr = $this->db->fetch_array($i);
	      $ventes[$arr[1]] = $arr[0];
	      $i++;
	    }
	}

      $year = strftime('%Y',time());
      $month = strftime('%m',time());
      $result = array();

      for ($j = 0 ; $j < 12 ; $j++)
	{
	  $idx=ucfirst(strftime("%b",mktime(12,0,0,$month,1,$year)));
	  if (isset($ventes[$year . $month]))
	    {
	      $result[$j] = array($idx, $ventes[$year . $month]);
	    }
	  else
	    {
	      $result[$j] = array($idx,0);
	    }

	  $month = "0".($month - 1);
	  if (strlen($month) == 3)
	    {
	      $month = substr($month,1);
	    }
	  if ($month == 0)
	    {
	      $month = 12;
	      $year = $year - 1;
	    }
	}
      return array_reverse($result);

    }

  /**
   *    \brief  Renvoie le nombre de ventes du produit/service par mois
   *    \param  socid       id societe
   *    \return array       nombre de vente par mois
   */
	 
  function get_nb_vente($socid=0)
    {
      $sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f";
      $sql .= " WHERE f.rowid = d.fk_facture and d.fk_product =".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND f.fk_soc = $socid";
	}
      $sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }

  /**
   *    \brief  Renvoie le nombre de factures dans lesquelles figure le produit par mois
   *    \param  socid       id societe
   *    \return array       nombre de factures par mois
   */
	 
  function get_num_vente($socid=0)
    {
      $sql = "SELECT count(*), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f";
      $sql .= " WHERE f.rowid = d.fk_facture AND d.fk_product =".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND f.fk_soc = $socid";
	}
      $sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }

  /**
   *    \brief  Renvoie le nombre de propales dans lesquelles figure le produit par mois
   *    \param  socid       id societe
   *    \return array       nombre de propales par mois
   */
	 
  function get_num_propal($socid=0)
  {
      $sql = "SELECT count(*), date_format(p.datep, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."propal as p";
      $sql .= " WHERE p.rowid = d.fk_propal and d.fk_product =".$this->id;
      if ($socid > 0)
	{
	  $sql .= " AND p.fk_soc = $socid";
	}
      $sql .= " GROUP BY date_format(p.datep,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }

  /**
   *    \brief  Lie un fournisseur au produit/service
   *    \param  user        utilisateur qui fait le lien
   *    \param  id_fourn    id du fournisseur
   *    \param  ref_fourn   reference chez le fournisseur
   */
	 
  function add_fournisseur($user, $id_fourn, $ref_fourn) 
    {
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."product_fournisseur WHERE fk_product = $this->id AND fk_soc = $id_fourn";

      if ($this->db->query($sql) )
	{
	  $row = $this->db->fetch_row(0);
	  $this->db->free();
	  if ($row[0] == 0)
	    {

	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur ";
	      $sql .= " (datec, fk_product, fk_soc, ref_fourn, fk_user_author)";
	      $sql .= " VALUES (now(), $this->id, $id_fourn, '$ref_fourn', $user->id)";
	      
	      if ($this->db->query($sql) )
		{
		  return 1;	      
		}
	      else
		{
    	  dolibarr_print_error($this->db);
		  return -1;
		}
	    }
	  else
	    {
	      return -2;
	    }
	}
      else
	{
	  dolibarr_print_error($this->db);
	  return -3;
	}
    }

  /**
   *    \brief  Délie un fournisseur au produit/service
   *    \param  user        utilisateur qui défait le lien
   *    \param  id_fourn    id du fournisseur
   */
	 
  function remove_fournisseur($user, $id_fourn) 
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur ";
      $sql .= " WHERE fk_product = $this->id AND fk_soc = $id_fourn;";	
      
      if ($this->db->query($sql) )
	{
	  return 1;	      
	}
      else
	{
	  dolibarr_print_error($this->db);
	  return -1;
	}
    }

  /**
   *    \brief  Entre un nombre de piece du produit en stock dans un entrepôt
   *    \param  id_entrepot     id de l'entrepot
   *    \param  nbpiece         nombre de pieces
   */
	 
  function create_stock($id_entrepot, $nbpiece)
  {
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock ";
    $sql .= " (fk_product, fk_entrepot, reel)";
    $sql .= " VALUES ($this->id, $id_entrepot, $nbpiece)";
    
    if ($this->db->query($sql) )
      {
	return 1;	      
      }
    else
      {
    dolibarr_print_error($this->db);
	return -1;
      }    
  }

  /**
   *    \brief  Ajuste le stock d'un entrepôt pour le produit à une valeure donnée
   *    \param  user            utilisateur qui demande l'ajustement
   *    \param  id_entrepot     id de l'entrepot
   *    \param  nbpiece         nombre de pieces
   *    \param  mouvement       0 = ajout, 1 = suppression
   */
	 
  function correct_stock($user, $id_entrepot, $nbpiece, $mouvement)
  {

    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."product_stock ";
    $sql .= " WHERE fk_product = $this->id AND fk_entrepot = $id_entrepot";
    
    if ($this->db->query($sql) )
      {
	$row = $this->db->fetch_row(0);
	if ($row[0] > 0)
	  {
	    return $this->ajust_stock($user, $id_entrepot, $nbpiece, $mouvement);
	  }
	else
	  {
	    return $this->create_stock($id_entrepot, $nbpiece);
	  }
      }
    else
      {
	  dolibarr_print_error($this->db);
  	  $this->db->rollback();
	  return -1;
      }        
  }

  /**
   *    \brief  Augment ou réduit la valeur de stock pour le produit
   *    \param  user            utilisateur qui demande l'ajustement
   *    \param  id_entrepot     id de l'entrepot
   *    \param  nbpiece         nombre de pieces
   *    \param  mouvement       0 = ajout, 1 = suppression
   */
	 
  function ajust_stock($user, $id_entrepot, $nbpiece, $mouvement)
  {
    $op[0] = "+" . trim($nbpiece);
    $op[1] = "-" . trim($nbpiece);

    if ($this->db->begin())
      {

	$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
	$sql .= " SET stock_commande = stock_commande ".$op[$mouvement].", stock_propale = stock_propale ".$op[$mouvement];
	$sql .= " WHERE rowid = $this->id ";
	
	if ($this->db->query($sql) )
	  {	    
	    $sql = "UPDATE ".MAIN_DB_PREFIX."product_stock ";
	    $sql .= " SET reel = reel ".$op[$mouvement];
	    $sql .= " WHERE fk_product = $this->id AND fk_entrepot = $id_entrepot";
	    
	    if ($this->db->query($sql) )
	      {		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author)";
		$sql .= " VALUES (now(), $this->id, $id_entrepot, ".$op[$mouvement].", 0, $user->id)";
		
		if ($this->db->query($sql) )
		  {
		    $this->db->commit();
		    return 1;	      
		  }
		else
		  {
      	    dolibarr_print_error($this->db);
		    $this->db->rollback();
		    return -2;
		  }
	      }
	    else
	      {
  	    dolibarr_print_error($this->db);
		$this->db->rollback();
		return -1;
	      } 
	  }
	else
	  {
 	    dolibarr_print_error($this->db);
	    $this->db->rollback();
	    return -3;
	  }    
      }
  }

}
?>
