<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
        \file       htdocs/product.class.php
        \ingroup    produit
        \brief      Fichier de la classe des produits prédéfinis
        \version    $Revision$
*/


/**
        \class      Product
        \brief      Classe permettant la gestion des produits prédéfinis
*/

class Product
{
    var $db ;
    
    var $id ;
    var $ref;
    var $libelle;
    var $description;
    var $price;
	var $multiprices=array();
    var $tva_tx;
    var $type;
    var $seuil_stock_alerte;
    var $duration_value;
    var $duration_unit;
    var $status;

    var $stats_propale=array();
    var $stats_commande=array();
    var $stats_contrat=array();
    var $stats_facture=array();
    var $multilangs=array();

    var $typeprodserv;
    var $error;
    
    
    /**
     *    \brief      Constructeur de la classe
     *    \param      DB          Handler accès base de données
     *    \param      id          Id produit (0 par defaut)
     */
    function Product($DB, $id=0)
    {
        global $langs;
        
        $this->db = $DB;
        $this->id   = $id ;
        $this->status = 0;
        $this->seuil_stock_alerte = 0;
        
        $this->typeprodser[0]=$langs->trans("Product");
        $this->typeprodser[1]=$langs->trans("Service");
    }  


  /**
   *    \brief      Vérifie que la référence et libellé du produit est non null
   *    \return     int         1 si ok, 0 sinon
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
     *    \brief    Insère le produit en base
     *    \param    user        Utilisateur qui effectue l'insertion
     */
    function create($user)
    {
    
        $this->ref = trim(sanitize_string($this->ref));

        if ($this->tva_tx=='') $this->tva_tx = 0;
        if ($this->price=='')  $this->price = 0;
        if ($this->status=='') $this->status = 0;
        $this->price = price2num($this->price);

        dolibarr_syslog("Product::Create ref=".$this->ref." Categorie : ".$this->catid);
    
        $this->db->begin();
    
        $sql = "SELECT count(*)";
        $sql .= " FROM ".MAIN_DB_PREFIX."product WHERE ref = '" .$this->ref."'";

        $result = $this->db->query($sql) ;
        if ($result)
        {
            $row = $this->db->fetch_array($result);
            if ($row[0] == 0)
            {
                // Produit non deja existant
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."product ";
                $sql.= " (datec, ";
                if ($this->ref) $sql.= "ref, ";
                $sql.= "fk_user_author, fk_product_type, price)";
                $sql.= " VALUES (now(), ";
                if ($this->ref) $sql.= "'".$this->ref."', ";
                $sql.= $user->id.", ".$this->type.", '" . $this->price . "')";
                $result = $this->db->query($sql);
                if ( $result )
                {
                    $id = $this->db->last_insert_id(MAIN_DB_PREFIX."product");
    
                    if ($id > 0)
                    {
                        $this->id = $id;
                        $this->_log_price($user);
                        if ( $this->update($id, $user) > 0)
                        {
                            if ($this->catid > 0)
                            {
                                $cat = new Categorie ($this->db, $this->catid);
                                $cat->add_product($this);
                            }
                            $this->db->commit();
                            return $id;
                        }
                        else {
                            $this->db->rollback();
                            return -5;
                        }
                    }
                    else
                    {
                        $this->db->rollback();
                        return -4;
                    }
                }
                else
                {
                    $this->error=$this->db->error()." - ".$sql;
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }

        $this->error=$this->db->error();
        $this->db->rollback();
        return -1;
    }


    /**
     *    \brief      Mise à jour du produit en base
     *    \param      id          id du produit
     *    \param      user        utilisateur qui effectue l'insertion
     *    \return     int         1 si ok, -1 si ref deja existante, -2 autre erreur
     */
    function update($id, $user)
    {
        global $langs, $conf;
        $langs->load("main");
        $langs->load("products");
    
        if (! $this->libelle) $this->libelle = 'LIBELLE MANQUANT';
    
        $this->ref = trim(sanitize_string($this->ref));
        $this->libelle = trim($this->libelle);
        $this->description = trim($this->description);
        $this->note = trim($this->note);
    
        $sql = "UPDATE ".MAIN_DB_PREFIX."product ";
        $sql .= " SET label = '" . addslashes($this->libelle) ."'";
        if ($this->ref) $sql .= ",ref = '" . $this->ref ."'";
        $sql .= ",tva_tx = " . $this->tva_tx ;
        $sql .= ",envente = " . $this->envente ;
        $sql .= ",seuil_stock_alerte = " . $this->seuil_stock_alerte ;
        $sql .= ",description = '" . addslashes($this->description) ."'";
        $sql .= ",note = '" . addslashes($this->note) ."'";
        $sql .= ",duration = '" . $this->duration_value . $this->duration_unit ."'";
        $sql .= " WHERE rowid = " . $id;
    
        if ( $this->db->query($sql) )
        {
        	// Multilangs
			    if($conf->global->PRODUIT_MULTILANGS == 1)
				     if ( $this->setMultiLangs() < 0)
				     {
					     $this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
					     return -2;
				     }
           return 1;
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error=$langs->trans("Error")." : ".$langs->trans("ErrorProductAlreadyExists",$this->ref);
                return -1;
            }
            else
            {
                $this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
                return -2;
            }
        }
    }

	/**
	 *		\brief : update ou crée les traductions des infos produits
	 */
	function setMultiLangs()
	{
		global $langs;
        $langs_available = $langs->get_available_languages();
		$current_lang = $langs->getDefaultLang();

		foreach ($langs_available as $value)
		{
			if ($value == $current_lang)
			{
				$sqlU = "UPDATE ".MAIN_DB_PREFIX."product_det";
				$sqlU.= " SET label='".$this->libelle."'";
				$sqlU.= " description='".$this->description."'";
				$sqlU.= " note='".$this->note."'";
				$sqlU.= " WHERE fk_product=".$this->id." AND lang='".$value."'";
				
				$sqlI = "INSERT INTO ".MAIN_DB_PREFIX."product_det (fk_product, lang, label, description, note)";
				$sqlI.= " VALUES(".$this->id.",'".$value."','". $this->libelle;
				$sqlI.= "','".$this->description;
				$sqlI.= "','".$this->note."')";

			}
			else
			{
				$sqlU = "UPDATE ".MAIN_DB_PREFIX."product_det";
				$sqlU.= " SET label='".$this->multilangs["$value"]["libelle"]."'";
				$sqlU.= " description='".$this->multilangs["$value"]["libelle"]."'";
				$sqlU.= " note='".$this->multilangs["$value"]["note"]."'";
				$sqlU.= " WHERE fk_product=".$this->id." AND lang='".$value."'";

				$sqlI = "INSERT INTO ".MAIN_DB_PREFIX."product_det (fk_product, lang, label, description, note)";
				$sqlI.= " VALUES(".$this->id.",'".$value."','". $this->multilangs["$value"]["libelle"];
				$sqlI.= "','".$this->multilangs["$value"]["description"];
				$sqlI.= "','".$this->multilangs["$value"]["note"]."')";

			}

    // on ne sauvegarde pas des champs vides
    if ( $this->multilangs["$value"]["libelle"] || $this->multilangs["$value"]["description"] || $this->multilangs["$value"]["note"] ) 
			if (!$this->db->query($sqlU)) // si aucun champ n'est mis a jour
				if (!$this->db->query($sqlI)) return -1;
		}
		return 1;
	}
	 

	/**
	 *		\ brief Charge toutes les traductions du produit
	 */
	function getMultiLangs($langue='')
	{
		global $langs;
        $langs_available = $langs->get_available_languages(); 
		
		if ( $langue != '')
			foreach ($langs_available as $value)
				if ( $value == $langue ) $current_lang = $value; // si $langue est une valeur correcte
		
		if ( !$current_lang )
			$current_lang = $langs->getDefaultLang(); // sinon on choisi la langue par defaut
		
		$sql = "SELECT lang, label, description, note";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_det";
		$sql.= " WHERE fk_product=".$this->id;
		
		$result = $this->db->query($sql);
		if ($result)
		{
			while ( $obj = $this->db->fetch_object($result) )
			{
				$this->multilangs["$obj->lang"]["libelle"]		= $obj->label;
				$this->multilangs["$obj->lang"]["description"]	= $obj->description;
				$this->multilangs["$obj->lang"]["note"]			= $obj->note;
				
				if( $obj->lang == $current_lang ) // si on a les traduct. dans la langue courant on les charge en infos principales.
				{
					$this->libelle		= $obj->label;
					$this->description	= $obj->description;
					$this->note			= $obj->note;
				}
			 }
			}
		else
		{
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			return -1;
		}
	}



    /**
     *    \brief  Ajoute un changement de prix en base dans l'historique des prix
     *    \param  user        utilisateur qui modifie le prix
     */
    function _log_price($user) 
    {
        // MultiPrix : si activé, on gère tout ici, même le prix standard
		global $conf;
		if($conf->global->PRODUIT_MULTIPRICES == 1)
		{
			$queryError = false;
			for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
			{
						if($this->multiprices["$i"] != "")
						{
								// On supprimme ligne existante au cas ou
								$sql_multiprix = "DELETE FROM ".MAIN_DB_PREFIX."product_price ";
								$sql_multiprix .= "WHERE date_price = now()";
								$sql_multiprix .= " and fk_product = ".$this->id;
								$sql_multiprix .= " and fk_user_author = ".$user->id;
								$sql_multiprix .= " and price = ".price2num($this->multiprices["$i"]);
						
								$this->db->query($sql_multiprix);
								
								// On ajoute nouveau tarif
								$sql_multiprix = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price_level,price) ";
								$sql_multiprix .= " VALUES(now(),".$this->id.",".$user->id.",".$i.",".price2num($this->multiprices["$i"]);
								$sql_multiprix .= ")";
								if (! $this->db->query($sql_multiprix) )
									$queryError = true;
						}
				}
				if (strlen(trim($this->price)) > 0 )
				{
					// On supprimme ligne existante au cas ou
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price ";
					$sql .= "WHERE date_price = now()";
					$sql .= " and fk_product = ".$this->id;
					$sql .= " and fk_user_author = ".$user->id;
					$sql .= " and price = ".price2num($this->price);
					$sql .= " and envente = ".$this->envente;
					$sql .= " and tva_tx = ".$this->tva_tx;
			
					$this->db->query($sql);
					
					// On ajoute nouveau tarif
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price,envente,tva_tx) ";
					$sql .= " VALUES(now(),".$this->id.",".$user->id.",".price2num($this->price).",".$this->envente.",".$this->tva_tx;
					$sql .= ")";
					if (! $this->db->query($sql) )
						$queryError = true;
				}
				if($queryError)
				{
					dolibarr_print_error($this->db);
					return 0;
				}
				else
				  return 1;
		}
		else
		{
				// On supprimme ligne existante au cas ou
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price ";
				$sql .= "WHERE date_price = now()";
				$sql .= " and fk_product = ".$this->id;
				$sql .= " and fk_user_author = ".$user->id;
				$sql .= " and price = ".price2num($this->price);
				$sql .= " and envente = ".$this->envente;
				$sql .= " and tva_tx = ".$this->tva_tx;
		
				$this->db->query($sql);
				
				// On ajoute nouveau tarif
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price,envente,tva_tx) ";
				$sql .= " VALUES(now(),".$this->id.",".$user->id.",".price2num($this->price).",".$this->envente.",".$this->tva_tx;
				$sql .= ")";
			   
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
		
    }


    /**
     *    \brief      Lit le prix pratiqué par un fournisseur
     *    \param      fourn_id        Id du fournisseur
     *    \param      qty             Quantite pour lequel le prix est valide
     *    \return     int             <0 si ko, 0 si ok mais rien trouvé, 1 si ok et trouvé
     */
    function get_buyprice($fourn_id, $qty) 
    {
        $result = 0;
        $sql = "SELECT pf.price as price, pf.quantity as quantity";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pf";
        $sql.= " WHERE pf.fk_soc = ".$fourn_id;
        $sql.= " AND pf.fk_product =" .$this->id;
        $sql.= " AND quantity <= ".$qty;
        $sql.= " ORDER BY quantity DESC";
        $sql.= " LIMIT 1";
        
        dolibarr_syslog("Product::get_buyprice $fourn_id,$qty sql=$sql");
        
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj && $obj->quantity > 0)
            {
                $this->buyprice = $obj->price;                      // \deprecated
                $this->fourn_pu = $obj->price / $obj->quantity;     // Prix unitaire du produit pour le fournisseur $fourn_id
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return -1;
        }
        return $result;
    }


  /**
   *    \brief  Modifie le prix d'achat pour un fournisseur
   *    \param  id_fourn        Id du fournisseur
   *    \param  qty             Quantite pour lequel le prix est valide
   *    \param  buyprice        Prix d'achat pour la quantité
   *    \param  user            Objet user de l'utilisateur qui modifie
   */
  function update_buyprice($id_fourn, $qty, $buyprice, $user) 
    {
        $error=0;
        $this->db->begin();
        
        // Supprime prix courant du fournisseur pour cette quantité
        $sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price ";
        $sql .= " WHERE ";
        $sql .= " fk_product = ".$this->id;
        $sql .= " AND fk_soc = ".$id_fourn;
        $sql .= " AND quantity = ".$qty;
    
        if ($this->db->query($sql))
        {
            // Ajoute prix courant du fournisseur pour cette quantité
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price ";
            $sql .= " SET datec = now()";
            $sql .= " ,fk_product = ".$this->id;
            $sql .= " ,fk_soc = ".$id_fourn;
            $sql .= " ,fk_user = ".$user->id;
            $sql .= " ,price = ".price2num($buyprice);
            $sql .= " ,quantity = ".$qty;
    
            if (! $this->db->query($sql))
            {
                $error++;
            }
    
            if (! $error) {
                // Ajoute modif dans table log
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log ";
                $sql .= " SET datec = now()";
                $sql .= " ,fk_product = ".$this->id;
                $sql .= " ,fk_soc = ".$id_fourn;
                $sql .= " ,fk_user = ".$user->id;
                $sql .= " ,price = ".price2num($buyprice);
                $sql .= " ,quantity = ".$qty;

                if (! $this->db->query($sql))
                {
                    $error++;
                }
            }

            if (! $error)
            {
                $this->db->commit();
                return 0;
            }
            else
            {
                $this->error=$this->db->error()." ($sql)";
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error()." ($sql)";
            $this->db->rollback();
            return -1;
        }
    }

  
    /**
     *    \brief  Modifie le prix d'un produit/service
     *    \param  id          id du produit/service à modifier
     *    \param  user        utilisateur qui modifie le prix
     */
    function update_price($id, $user)
    {
		//multiprix
		global $conf;
		if($conf->global->PRODUIT_MULTIPRICES == 1)
		{
				if (strlen(trim($this->price)) > 0 )
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
					$sql .= " SET price = " . price2num($this->price);
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
				else if(count($this->multiprices) > 0)
				{
					$this->_log_price($user);
					return 1;
				}
				else
				{
					$this->error = "Prix saisi invalide.";
				    return -2;
				}
		}
		else
		{
			if (strlen(trim($this->price)) > 0 )
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
				$sql .= " SET price = " . price2num($this->price);
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
				$this->error = "Prix saisi invalide.";
				return -2;
			}
		}
		
		
		
	}


    /**
     *      \brief      Charge le produit/service en mémoire
     *      \param      id      Id du produit/service à charger
     *      \param      ref     Ref du produit/service à charger
     *      \return     int     <0 si ko, >0 si ok
     */
    function fetch($id='',$ref='')
    {
        global $langs;
		global $conf;

		dolibarr_syslog("Product::fecth $id=$id ref=$ref");

        // Verification parametres
        if (! $id && ! $ref)
        {
            $this->error=$langs->trans('ErrorWrongParameters');
            return -1;
        }

        $sql = "SELECT rowid, ref, label, description, note, price, tva_tx, envente,";
        $sql.= " nbvente, fk_product_type, duration, seuil_stock_alerte";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        if ($id) $sql.= " WHERE rowid = '".$id."'";
        if ($ref) $sql.= " WHERE ref = '".addslashes($ref)."'";
    
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $result = $this->db->fetch_array();
    
            $this->id                 = $result["rowid"];
            $this->ref                = $result["ref"];
            $this->libelle            = stripslashes($result["label"]);
            $this->description        = stripslashes($result["description"]);
            $this->note               = stripslashes($result["note"]);
            $this->price              = $result["price"];
            $this->tva_tx             = $result["tva_tx"];
            $this->type               = $result["fk_product_type"];
            $this->nbvente            = $result["nbvente"];
            $this->status             = $result["envente"];
            $this->duration           = $result["duration"];
            $this->duration_value     = substr($result["duration"],0,strlen($result["duration"])-1);
            $this->duration_unit      = substr($result["duration"],-1);
            $this->seuil_stock_alerte = $result["seuil_stock_alerte"];
    
            $this->label_url = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">'.$this->libelle.'</a>';
    
            if ($this->type == 0)
            {
                $this->isproduct = 1;
                $this->isservice = 0;
            }
            else
            {
                $this->isproduct = 0;
                $this->isservice = 1;
            }
    
            $this->db->free();
			// multilangs
			if( $conf->global->PRODUIT_MULTILANGS == 1 ) $this->getMultiLangs();
			

			// multiprix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
			{
					if ($ref) 
					{
						 $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product ";
						 $sql.=  "WHERE ref = '".addslashes($ref)."'";
						 $result = $this->db->query($sql) ;
						 if ($result)
						{
								$result = $this->db->fetch_array();
								$prodid = $result["rowid"];
						}
						else
						 {
							dolibarr_print_error($this->db);
							return -1;
						}
					}
					$this -> multiprices[1] = $this->price;
					for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
					{
						$sql= "SELECT price, tva_tx, envente ";
						$sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
						$sql.= "where price_level=".$i." and ";
						if ($id) $sql.= "fk_product = '".$id."' ";
						if ($ref) $sql.= "fk_product = '".$prodid."' ";
						$sql.= "order by date_price DESC limit 1";
					    $result = $this->db->query($sql) ;
						if ( $result )
						{
							$result = $this->db->fetch_array();
							if($result["price"] != "" && $result["price"] != "0.00")
							 	$this -> multiprices[$i]=$result["price"];
							 else
							 	$this -> multiprices[$i]=$this->price;
						}
						else
						 {
							dolibarr_print_error($this->db);
							return -1;
						}
					}
					
			  }
		
            $sql = "SELECT reel, fk_entrepot";
            $sql .= " FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product = '".$this->id."'";
            $result = $this->db->query($sql) ;
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i=0;
                if ($num > 0)
                {
                    while ($i < $num )
                    {
                        $row = $this->db->fetch_row($result);
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
                $this->db->free($result);
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
     *      \brief      Charge les propriétés ref_previous et ref_next
     *      \param      filter      filtre
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_previous_next_ref($filtre='')
    {
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE ref < '".addslashes($this->ref)."'";
        if ($filter) $sql.=" AND ".$filter;
        $result = $this->db->query($sql) ;
        if (! $result)
        {
            $this->error=$this->db->error();
            return -1;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_previous = $row[0];

        $sql = "SELECT MIN(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE ref > '".addslashes($this->ref)."'";
        if ($filter) $sql.=" AND ".$filter;
        $result = $this->db->query($sql) ;
        if (! $result)
        {
            $this->error=$this->db->error();
            return -2;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_next = $row[0];
        
        return 1;
    }
    
    
    /**
     *    \brief    Charge tableau des stats propale pour le produit/service
     *    \param    socid       Id societe
     *    \return   array       Tableau des stats
     */
    function load_stats_propale($socid=0)
    {
    	  global $conf;
    	  global $user;
    	
        $sql = "SELECT COUNT(DISTINCT pr.fk_soc) as nb_customers, COUNT(DISTINCT pr.rowid) as nb,";
        $sql.= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."propal as pr";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE p.rowid = pd.fk_product AND pd.fk_propal = pr.rowid AND p.rowid = ".$this->id;
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND pr.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        //$sql.= " AND pr.fk_statut != 0";
        if ($socid > 0)
        {
            $sql .= " AND pr.fk_soc = $socid";
        }
    
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj=$this->db->fetch_object($result);
            $this->stats_propale['customers']=$obj->nb_customers;
            $this->stats_propale['nb']=$obj->nb;
            $this->stats_propale['rows']=$obj->nb_rows;
            $this->stats_propale['qty']=$obj->qty?$obj->qty:0;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *    \brief    Charge tableau des stats commande pour le produit/service
     *    \param    socid       Id societe
     *    \return   array       Tableau des stats
     */
    function load_stats_commande($socid=0)
    {
    	  global $conf;
    	  global $user;
    	
        $sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
        $sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."product as p,";
        $sql.= " ".MAIN_DB_PREFIX."commande as c";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE c.rowid = cd.fk_commande AND p.rowid = cd.fk_product AND p.rowid = ".$this->id;
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        //$sql.= " AND c.fk_statut != 0";
        if ($socid > 0)
        {
            $sql .= " AND c.fk_soc = $socid";
        }
    
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj=$this->db->fetch_object($result);
            $this->stats_commande['customers']=$obj->nb_customers;
            $this->stats_commande['nb']=$obj->nb;
            $this->stats_commande['rows']=$obj->nb_rows;
            $this->stats_commande['qty']=$obj->qty?$obj->qty:0;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    
    /**
     *    \brief    Charge tableau des stats contrat pour le produit/service
     *    \param    socid       Id societe
     *    \return   array       Tableau des stats
     */
    function load_stats_contrat($socid=0)
    {
    	  global $conf;
    	  global $user;
    	
        $sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
        $sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."product as p,";
        $sql.= " ".MAIN_DB_PREFIX."contrat as c";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE c.rowid = cd.fk_contrat AND p.rowid = cd.fk_product AND p.rowid = ".$this->id;
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        //$sql.= " AND c.statut != 0";
        if ($socid > 0)
        {
            $sql .= " AND c.fk_soc = $socid";
        }
    
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj=$this->db->fetch_object($result);
            $this->stats_contrat['customers']=$obj->nb_customers;
            $this->stats_contrat['nb']=$obj->nb;
            $this->stats_contrat['rows']=$obj->nb_rows;
            $this->stats_contrat['qty']=$obj->qty?$obj->qty:0;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    
    /**
     *    \brief    Charge tableau des stats facture pour le produit/service
     *    \param    socid       Id societe
     *    \return   array       Tableau des stats
     */
    function load_stats_facture($socid=0)
    {
    	  global $conf;
    	  global $user;
    	
        $sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_customers, COUNT(DISTINCT f.rowid) as nb,";
        $sql.= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."facturedet as pd, ".MAIN_DB_PREFIX."product as p";
        $sql.= ", ".MAIN_DB_PREFIX."facture as f";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE f.rowid = pd.fk_facture AND p.rowid = pd.fk_product AND p.rowid = ".$this->id;
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        //$sql.= " AND f.fk_statut != 0";
        if ($socid > 0)
        {
            $sql .= " AND f.fk_soc = $socid";
        }
    
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj=$this->db->fetch_object($result);
            $this->stats_facture['customers']=$obj->nb_customers;
            $this->stats_facture['nb']=$obj->nb;
            $this->stats_facture['rows']=$obj->nb_rows;
            $this->stats_facture['qty']=$obj->qty?$obj->qty:0;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *    \brief    Renvoie tableau des stats pour une requete donnée
     *    \param    sql         Requete a exécuter
     *    \return   array       Tableau de stats
     */
    function _get_stats($sql)
    {
        $result = $this->db->query($sql) ;
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $arr = $this->db->fetch_array($result);
                $tab[$arr[1]] = $arr[0];
                $i++;
            }
        }
    
        $year = strftime('%Y',time());
        $month = strftime('%m',time());
        $result = array();
    
        for ($j = 0 ; $j < 12 ; $j++)
        {
            $idx=ucfirst(substr( strftime("%b",mktime(12,0,0,$month,1,$year)) ,0,3) );
            $monthnum=sprintf("%02s",$month);
            
            $result[$j] = array($idx,isset($tab[$year.$month])?$tab[$year.$month]:0);
//            $result[$j] = array($monthnum,isset($tab[$year.$month])?$tab[$year.$month]:0);
    
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
    	global $conf;
    	global $user;
    	
      $sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f";
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
      $sql .= " WHERE f.rowid = d.fk_facture and d.fk_product =".$this->id;
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
    	global $conf;
    	global $user;
    	
      $sql = "SELECT count(*), date_format(f.datef, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f";
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
      $sql .= " WHERE f.rowid = d.fk_facture AND d.fk_product =".$this->id;
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
  	  global $conf;
    	global $user;
    	
      $sql = "SELECT count(*), date_format(p.datep, '%Y%m') ";
      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."propal as p";
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
      $sql .= " WHERE p.rowid = d.fk_propal and d.fk_product =".$this->id;
      if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
      if ($socid > 0)
	{
	  $sql .= " AND p.fk_soc = $socid";
	}
      $sql .= " GROUP BY date_format(p.datep,'%Y%m') DESC ;";

      return $this->_get_stats($sql);
    }
/**
   *    \brief      Lie un sousproduit au produit/service
   *    \param      id_pere    Id du produit auquel sera lié le produit à lier
   *    \param      id_fils  Id du produit à lier
   *    \return     int         < 0 si erreur, > 0 si ok
   */
	 
  function add_sousproduit($id_pere, $id_fils,$qty) 
    {
        $sql = 'delete from '.MAIN_DB_PREFIX.'product_association';
		$sql .= ' WHERE fk_product_pere  = "'.$id_pere.'" and fk_product_fils = "'.$id_fils.'"';
		if (! $this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			 return -1;
		}
		else
		{
			$sql = 'insert into '.MAIN_DB_PREFIX.'product_association(fk_product_pere,fk_product_fils,qty)';
			$sql .= ' VALUES ("'.$id_pere.'","'.$id_fils.'","'.$qty.'")';
			if (! $this->db->query($sql))
			{
				dolibarr_print_error($this->db);
				 return -1;
			}
			else
			{
				 return 1;
			}
		}
    }
/**
   *    \brief      retire le lien entre un sousproduit et un produit/service
   *    \param      id_pere    Id du produit auquel ne sera plus lié le produit lié
   *    \param      id_fils  Id du produit à ne plus lié
   *    \return     int         < 0 si erreur, > 0 si ok
   */
	 
  function del_sousproduit($id_pere, $id_fils) 
    {
        $sql = 'delete from '.MAIN_DB_PREFIX.'product_association';
		$sql .= ' WHERE fk_product_pere  = "'.$id_pere.'" and fk_product_fils = "'.$id_fils.'"';
		if (! $this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			 return -1;
		}
		else
			return 1;
    }
/**
   *    \brief      retire le lien entre un sousproduit et un produit/service
   *    \param      id_pere    Id du produit auquel ne sera plus lié le produit lié
   *    \param      id_fils  Id du produit à ne plus lié
   *    \return     int         < 0 si erreur, > 0 si ok
   */
	 
  function is_sousproduit($id_pere, $id_fils) 
    {
        $sql = 'select fk_product_pere,qty from '.MAIN_DB_PREFIX.'product_association';
		$sql .= ' WHERE fk_product_pere  = "'.$id_pere.'" and fk_product_fils = "'.$id_fils.'"';
		if (! $this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			 return -1;
		}
		else
		{
			$result = $this->db->query($sql) ;
       		 if ($result)
        	{
            	$num = $this->db->num_rows($result);
				if($num > 0)
				{
					$obj = $this->db->fetch_object($result);
               		$this->is_sousproduit_qty = $obj->qty;          
					
					return true;
				}
				else
					return false;
			}
		}
    }
  /**
   *    \brief      Lie un fournisseur au produit/service
   *    \param      user        Utilisateur qui fait le lien
   *    \param      id_fourn    Id du fournisseur
   *    \param      ref_fourn   Reference chez le fournisseur
   *    \return     int         < 0 si erreur, > 0 si ok
   */
	 
  function add_fournisseur($user, $id_fourn, $ref_fourn) 
    {
        $sql = "SELECT count(*) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
        $sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;
        $sql.= " AND ref_fourn = '".$ref_fourn."'";
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj->nb == 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur ";
                $sql .= " (datec, fk_product, fk_soc, ref_fourn, fk_user_author)";
                $sql .= " VALUES (now(), $this->id, $id_fourn, '$ref_fourn', $user->id)";
    
                if ($this->db->query($sql))
                {
                    return 1;
                }
                else
                {
                    $this->error=$this->db->error();
                    return -1;
                }
            }
            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->error();
            return -2;
        }
    }


  /**
   *    \brief  Renvoie le nombre de fournisseurs
   *    \return int         nombre de fournisseur
   */
	 
  function count_fournisseur()
    {
      $sql = "SELECT fk_soc";
      $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur as p";
      $sql .= " WHERE p.fk_product = ".$this->id;

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $num = $this->db->num_rows();

	  if ($num == 1)
	    {
	      $row = $this->db->fetch_row();
	      $this->fourn_appro_open = $row[0];
	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
      else
	{
	  return 0;
	}
    }


  /**
   *
   *
   */
  function fastappro($user)
  {
    include_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php";

    $nbf = $this->count_fournisseur();
    if ($nbf == 1)
      {
	dolibarr_syslog("Product::fastappro");
	$fournisseur = new Fournisseur($this->db);
	$fournisseur->fetch($this->fourn_appro_open);

	$fournisseur->ProductCommande($user, $this->id);
      }

  }


    /**
     *    \brief  Supprime un tarif fournisseur
     *    \param  user        utilisateur qui défait le lien
     *    \param  id_fourn    id du fournisseur
     *    \param  qty         quantit
     *    \return int         < 0 si erreur, > 0 si ok
     */
    function remove_price($user, $id_fourn, $qty)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " WHERE fk_product = $this->id AND fk_soc = $id_fourn and quantity = '".$qty."';";
    
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
     *    \brief    Délie un fournisseur au produit/service
     *    \param    user        utilisateur qui défait le lien
     *    \param    id_fourn    id du fournisseur
     *    \return   int         < 0 si erreur, > 0 si ok
     */
    function remove_fournisseur($user, $id_fourn)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur ";
        $sql.= " WHERE fk_product = $this->id AND fk_soc = $id_fourn;";
    
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
     *    \brief    Recopie les prix d'un produit/service sur un autre
     *    \param    fromId      Id produit source
     *    \param    toId        Id produit cible
     *    \return   int         < 0 si erreur, > 0 si ok
     */
    function clone_price($fromId, $toId)
    {
        global $db;
    
        $db->begin();
    
        // les prix
        $sql = "insert "	.MAIN_DB_PREFIX."product_price ("
        . " fk_product, date_price, price, tva_tx, fk_user_author, envente )"
        . " select ".$toId . ", date_price, price, tva_tx, fk_user_author, envente "
        . " from ".MAIN_DB_PREFIX."product_price "
        . " where fk_product = ". $fromId . ";" ;
        if ( ! $db->query($sql ) ) {
            $db->rollback();
            return -1;
        }
        $db->commit();
        return 1;
    }
    
    /**
     *    \brief    Recopie les fournisseurs et prix fournisseurs d'un produit/service sur un autre
     *    \param    fromId      Id produit source
     *    \param    toId        Id produit cible
     *    \return   int         < 0 si erreur, > 0 si ok
     */
    function clone_fournisseurs($fromId, $toId)
    {
        global $db;
    
        $db->begin();
    
        // les fournisseurs
        $sql = "insert ".MAIN_DB_PREFIX."product_fournisseur ("
        . " datec, fk_product, fk_soc, ref_fourn, fk_user_author )"
        . " select now(), ".$toId.", fk_soc, ref_fourn, fk_user_author"
        . " from ".MAIN_DB_PREFIX."product_fournisseur "
        . " where fk_product = ".$fromId .";" ;
        if ( ! $db->query($sql ) ) {
            $db->rollback();
            return -1;
        }
        // les prix de fournisseurs.
        $sql = "insert ".MAIN_DB_PREFIX."product_fournisseur_price ("
        . " datec, fk_product, fk_soc, price, quantity, fk_user )"
        . " select now(), ".$toId. ", fk_soc, price, quantity, fk_user"
        . " from ".MAIN_DB_PREFIX."product_fournisseur_price"
        . " where fk_product = ".$fromId.";";
        if ( ! $db->query($sql ) ) {
            $db->rollback();
            return -1;
        }
        $db->commit();
        return 1;
    }
/**
   *   \brief fonction récursive uniquement utilisée par get_arbo_each_prod, recompose l'arborescence des sousproduits
   *   \return void
   */
  function fetch_prod_arbo($prod,$compl_path="")
{
			$this->res;
			$this->pere_encours;
			foreach($prod as $nom_pere => $desc_pere)
			{
						// on est dans une sous-catégorie
						if(is_array($desc_pere))
							$this->res[]= array($compl_path.stripslashes($nom_pere)." (".$desc_pere[1].")",$desc_pere[0]);
						else if($nom_pere != "0" && $nom_pere != "1")
							$this->res[]= array($compl_path.stripslashes($nom_pere),$desc_pere);
						if(sizeof($desc_pere) >1)
						{
							$this ->fetch_prod_arbo($desc_pere,stripslashes($nom_pere)." -> ");
						}
			}
	}	
/**
   *   \brief fonction récursive uniquement utilisée par get_each_prod, ajoute chaque sousproduits dans le tableau res
   *   \return void
   */
  function fetch_prods($prod)
{
			$this->res;
			foreach($prod as $nom_pere => $desc_pere)
			{
						// on est dans une sous-catégorie
						if(is_array($desc_pere))
							$this->res[]= array($desc_pere[1],$desc_pere[0]);
						if(sizeof($desc_pere) >1)
						{
							$this ->fetch_prods($desc_pere);
						}
			}
	}	
	/**
   *   \brief reconstruit l'arborescence des catégorie sous la forme d'un tableau
   *   \return array $this->res
   */
function get_arbo_each_prod()
{
		$this->res = array();
		if(is_array($this -> sousprods))
		{
			foreach($this -> sousprods as $nom_pere => $desc_pere)
			{
				if(sizeof($desc_pere) >1)
					$this ->fetch_prod_arbo($desc_pere);
					
			}
			sort($this->res);
		}
		return $this->res;
}
	/**
   *   \brief renvoie tous les sousproduits dans le tableau res, chaque ligne de res contient : id -> qty
   *   \return array $this->res
   */
function get_each_prod()
{
		$this->res = array();
		if(is_array($this -> sousprods))
		{
			foreach($this -> sousprods as $nom_pere => $desc_pere)
			{
				if(sizeof($desc_pere) >1)
					$this ->fetch_prods($desc_pere);
					
			}
			sort($this->res);
		}
		return $this->res;
}
	
/**
   *   \brief Retourne les catégories pères
   *   \return array prod
   */
  function get_pere()
  {
   
	$sql  = "SELECT p.label as label,p.rowid,pa.fk_product_pere as id FROM ";
	$sql  .= MAIN_DB_PREFIX."product_association as pa,";
	$sql  .= MAIN_DB_PREFIX."product as p";
	$sql  .= " where p.rowid=pa.fk_product_pere and p.rowid = '".$this->id."'";
    $res = $this->db->query ($sql);
    if ($res)
      {
	$prods = array ();
	while ($record = $this->db->fetch_array ($res))
	 {
			$prods[addslashes($record['label'])] = array(0=>$record['id']);
	 }
		return $prods;
      }
    else
      {
	dolibarr_print_error ($this->db);
	return -1;
      }
  }

/**
   *  \brief Retourne les fils de la catégorie structurés pour l'arbo
   *   \return     array        prod
   */
  function get_fils_arbo ($id_pere)
  {
	$sql  = "SELECT p.rowid, p.label as label,pa.qty as qty,pa.fk_product_fils as id FROM ";
	$sql .= MAIN_DB_PREFIX."product as p,".MAIN_DB_PREFIX."product_association as pa";
    $sql .= " WHERE p.rowid = pa.fk_product_fils and pa.fk_product_pere = '".$id_pere."'";
    $res  = $this->db->query ($sql);

    if ($res)
     {
			$prods = array();
			while ($rec = $this->db->fetch_array ($res))
			 {
					$prods[addslashes($rec['label'])]= array(0=>$rec['id'],1=>$rec['qty']);
					foreach($this -> get_fils_arbo($rec['id']) as $kf=>$vf)
							$prods[addslashes($rec['label'])][$kf] = $vf;
			 }
			 return $prods;
      }
    else
    {
				dolibarr_print_error ($this->db);
				return -1;
     }
  }
/**
   * \brief compose l'arborescence des sousproduits, id, nom et quantité sous la forme d'un tableau associatif 
   *    \return    void
   */
  function get_sousproduits_arbo ()
  {
		
		$peres = $this -> get_pere();
		foreach($peres as $k=>$v)
		{
			foreach($this -> get_fils_arbo($v[0]) as $kf=>$vf)
				$peres[$k][$kf] = $vf;
		}
		// on concatène tout ça
		foreach($peres as $k=>$v)
		{
			$this -> sousprods[$k]=$v;
		}
	}

	/**
	 *    \brief      Retourne le libellé du statut d'une facture (brouillon, validée, abandonnée, payée)
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *    \brief      Renvoi le libellé d'un statut donne
	 *    \param      status        Statut
	 *    \param      mode          0=libellé long, 1=libellé court, 2=Picto + Libellé court
	 *    \return     string        Libellé du statut
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;
		$langs->load('products');
		if ($mode == 0)
		{
			if ($status == 0) return $langs->trans('ProductStatusNotOnSellShort');
			if ($status == 1) return $langs->trans('ProductStatusOnSellShort');
		}
		if ($mode == 1)
		{
			if ($status == 0) return $langs->trans('ProductStatusNotOnSell');
			if ($status == 1) return $langs->trans('ProductStatusOnSell');
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.$langs->trans('ProductStatusNotOnSell');
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.$langs->trans('ProductStatusOnSell');
		}
		return $langs->trans('Unknown');
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
    if ($id_entrepot)
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


  /**
   *    \brief      Charge les informations relatives à un fournisseur
   *    \param      fournid         id du fournisseur
   *    \return     int             < 0 si erreur, > 0 si ok
   */
  function fetch_fourn_data($fournid)
    {    
        $sql = "SELECT rowid, ref_fourn";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur ";
        $sql.= " WHERE fk_product = ".$this->id;
        $sql.= " AND fk_soc = ".$fournid;
        $result = $this->db->query($sql) ;
        
        if ($result)
        {
            $result = $this->db->fetch_array();
            $this->ref_fourn = $result["ref_fourn"];
            return 1;
        }
        else {
            return -1;
        }
    }


  /**
   *    \brief      Déplace fichier uploadé sous le nom $files dans le répertoire sdir
   *    \param      sdir        Répertoire destination finale
   *    \param      $files      Nom du fichier uploadé
   */
  function add_photo($sdir, $files)
  {
    $dir = $sdir .'/'. get_exdir($this->id) . $this->id ."/";
    $dir .= "photos/";
    
    if (! file_exists($dir))
    {
        dolibarr_syslog("Product Create $dir");
        create_exdir($dir);
    }
    
    if (file_exists($dir))
    {
        // Crée fichier en taille vignette
        // \todo A faire

        // Crée fichier en taille origine
        doliMoveFileUpload($files['tmp_name'], $dir . $files['name']);
    }
  }


  /**
   *    \brief      Affiche la première photo du produit
   *    \param      sdir        Répertoire à scanner
   *    \return     boolean     true si photo dispo, flase sinon
   */
  function is_photo_available($sdir)
  {
    $pdir = get_exdir($this->id) . $this->id ."/photos/";
    $dir = $sdir . '/'. $pdir;
    
    $nbphoto=0;
    if (file_exists($dir))
    {
        $handle=opendir($dir);
    
        while (($file = readdir($handle)) != false)
        {
            if (is_file($dir.$file)) return true;
        }
     }
     return false;
  }
  

  /**
   *    \brief      Affiche la première photo du produit
   *    \param      sdir    Répertoire à scanner
   *    \param      size    0=taille origine, 1 taille vignette
   *    \return     int     Nombre de photos affichées
   */
  function show_photo($sdir,$size=0)
  {
    return $this->show_photos($sdir,$size,1,0);
  }


  /**
   *    \brief      Affiche toutes les photos du produit (nbmax maximum)
   *    \param      sdir        Répertoire à scanner
   *    \param      size        0=taille origine, 1 taille vignette
   *    \param      nbmax       Nombre maximum de photos (0=pas de max)
   *    \param      nbbyrow     Nombre vignettes par ligne (si mode vignette)
   *    \return     int         Nombre de photos affichées
   *    \todo   A virer, seule la methode avec size=0 sert encore.
   */
  function show_photos($sdir,$size=0,$nbmax=0,$nbbyrow=5)
  {
    $pdir = get_exdir($this->id) . $this->id ."/photos/";
    $dir = $sdir . '/'. $pdir;
    
    $nbphoto=0;
    if (file_exists($dir))
    {
        $handle=opendir($dir);
    
        while (($file = readdir($handle)) != false)
        {
            $photo='';
            if (is_file($dir.$file)) $photo = $file;
    
            if ($photo)
            {
                $nbphoto++;

                if ($size == 1) {   // Format vignette
                    
                    // On determine nom du fichier vignette
                    $photo_vignette='';
                    if (eregi('(\.jpg|\.bmp|\.gif|\.png|\.tiff)$',$photo,$regs)) {
                        $photo_vignette=eregi_replace($regs[0],'',$photo)."_small".$regs[0];
                    }


                    if ($nbbyrow && $nbphoto == 1) print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

                    if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
                    if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';
                    
                    print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'" alt="Taille origine" target="_blank">';

                    // Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
                    if ($photo_vignette && is_file($photo_vignette)) {
                        print '<img border="0" height="120" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo_vignette).'">';
                    }
                    else {
                        print '<img border="0" height="120" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'">';
                    }

                    print '</a>';

                    if ($nbbyrow) print '</td>';
                    if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';

                }
    
                if ($size == 0)     // Format origine
                    print '<img border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'">';
    
                // On continue ou on arrete de boucler ?
                if ($nbmax && $nbphoto >= $nbmax) break;
            }
        }
        
        if ($nbbyrow && $size==1)
        {
            // Ferme tableau
            while ($nbphoto % $nbbyrow) {
                print '<td width="'.ceil(100/$nbbyrow).'%">&nbsp;</td>';
                $nbphoto++;
            }
            
            if ($nbphoto) print '</table>';
        }
        
        closedir($handle);
    }
    
    return $nbphoto;
  }
  
  /**
   *    \brief      Retourne tableau de toutes les photos du produit
   *    \param      dir         Répertoire à scanner
   *    \param      nbmax       Nombre maximum de photos (0=pas de max)
   *    \return     array       Tableau de photos
   */
  function liste_photos($dir,$nbmax=0)
  {
    $nbphoto=0;
    $tabobj=array();
    
    if (file_exists($dir))
    {
        $handle=opendir($dir);
    
        while (($file = readdir($handle)) != false)
        {
            if (is_file($dir.$file))
            {
                $nbphoto++;
                $photo = $file;

                // On determine nom du fichier vignette
                $photo_vignette='';
                if (eregi('(\.jpg|\.bmp|\.gif|\.png|\.tiff)$',$photo,$regs)) {
                    $photo_vignette=eregi_replace($regs[0],'',$photo)."_small".$regs[0];
                }
 
                // Objet
                $obj->photo=$photo;
                if ($photo_vignette && is_file($photo_vignette)) $obj->photo_vignette=$photo_vignette;
                else $obj->photo_vignette="";
                $tabobj[$nbphoto-1]=$obj;

                // On continue ou on arrete de boucler ?
                if ($nbmax && $nbphoto >= $nbmax) break;
            }
        }
        
        closedir($handle);
    }
    
    return $tabobj;
  }


    /**
     *      \brief      Charge indicateurs this->nb de tableau de bord
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        global $conf;
        
        $this->nb=array();

        $sql = "SELECT count(p.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " WHERE p.fk_product_type = 0";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["products"]=$obj->nb;
            }
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
