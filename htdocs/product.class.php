<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2007      Jean Heimburger      <jean@tiaris.info>
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
 *	\file       htdocs/product.class.php
 *	\ingroup    produit
 *	\brief      Fichier de la classe des produits pr�d�finis
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**
 * \class      Product
 * \brief      Class to manage products and services
 */
class Product extends CommonObject
{
	var $db;
	var $error;
	var $element='product';
	var $table_element='product';

	//! Identifiant unique
	var $id ;
	//! Ref
	var $ref;
	var $libelle;
	var $description;
	//! Prix de vente
	var $price;				// Price without tax
	var $price_ttc;			// Price with tax
	var $price_min;
	var $price_min_ttc;
	//! Base de prix (ttc ou ht)
	var $price_base_type;
	//! Tableau des prix multiples
	var $multiprices=array();
	var $multiprices_ttc=array();
	var $multiprices_base_type=array();
	//! Taux de TVA
	var $tva_tx;
	//! Type 0 for regural product, 1 for service, 2 for assembly kit, 3 for stock kit
	var $type;
	var $typestring;
	var $stock_reel;
	var $seuil_stock_alerte;
	//! Duree de validite du service
	var $duration_value;
	//! Unite de duree
	var $duration_unit;
	// Statut indique si le produit est en vente '1' ou non '0'
	var $status;
	 // Statut indique si le produit est un produit finis '1' ou une matiere premi�re '0'
  	var $finished; 

	//! Unites de mesure
	var $weight;
	var $weight_units;
	var $volume;
	var $volume_units;

	//! Codes barres
	var $barcode;
	var $barcode_type;
	var $barcode_type_code;
	var $barcode_type_label;
	var $barcode_type_coder;

	var $stats_propale=array();
	var $stats_commande=array();
	var $stats_contrat=array();
	var $stats_facture=array();
	var $multilangs=array();

	//! Taille de l'image
	var $imgWidth;
	var $imgHeight;

	//! Numero de l'erreur
	//! Numero d'erreur Plage 0256-0511
	var $errno = 0;
	//! Canevas a utiliser si le produit n'est pas un produit generique
	var $canvas;
	//! Nombre de piece en commande, non expedie
	var $stock_in_command;

	//! Id du fournisseur
	var $product_fourn_id;

	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acc�s base de donn�es
	 *    \param      id          Id produit (0 par defaut)
	 */
	function Product($DB, $id=0)
	{
		global $langs;

		$this->db = $DB;
		$this->id   = $id ;
		$this->status = 0;
		$this->stock_reel = 0;
		$this->seuil_stock_alerte = 0;

		$this->canvas = '';
		if ($id>0) $this->fetch($id);
	}

	/**
	 *    \brief      Check that ref and label are ok
	 *    \return     int         >1 if OK, <=0 if KO
	 */
	function check()
	{
		$this->ref = sanitizeFileName(stripslashes($this->ref));

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
	 *	\brief    Insert product in database
	 *	\param    user     	Utilisateur qui effectue l'insertion
	 *	\return   int     	id du produit ou numero d'erreur < 0
	 */
	function create($user)
	{
		global $conf ;
		$this->errno = 0;

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		if (empty($this->tva_tx))    $this->tva_tx = 0;
		if (empty($this->price))     $this->price = 0;
		if (empty($this->price_min)) $this->price_min = 0;
		if (empty($this->status))    $this->status = 0;
		if (empty($this->finished))  $this->finished = 0;
		
		$price_ht=0;
		$price_ttc=0;
		$price_min_ht=0;
		$price_min_ttc=0;
		if ($this->price_base_type == 'TTC' && $this->price_ttc > 0)
		{
			$price_ttc = price2num($this->price_ttc,'MU');
			$price_ht = price2num($this->price_ttc / (1 + ($this->tva_tx / 100)),'MU');
		}
		if ($this->price_base_type != 'TTC' && $this->price > 0)
		{
			$price_ht = price2num($this->price,'MU');
			$price_ttc = price2num($this->price * (1 + ($this->tva_tx / 100)),'MU');
		}

		if (($this->price_min_ttc > 0)&&($this->price_base_type == 'TTC'))
		{
			$price_min_ttc = price2num($this->price_min_ttc,'MU');
			$price_min_ht = price2num($this->price_min_ttc / (1 + ($this->tva_tx / 100)),'MU');
		}
		if (($this->price_min > 0)&&($this->price_base_type != 'TTC'))
		{
			$price_min_ht = price2num($this->price_min,'MU');
			$price_min_ttc = price2num($this->price_min * (1 + ($this->tva_tx / 100)),'MU');
		}

		// Check parameters
		if (empty($this->libelle))
		{
			$this->error='ErrorWrongParameters';
			return -1;
		}

		dolibarr_syslog("Product::Create ref=".$this->ref." price=".$this->price." price_ttc=".$this->price_ttc." tva_tx=".$this->tva_tx." price_base_type=".$this->price_base_type." Categorie : ".$this->catid);

		if ($this->ref)
		{
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
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."product";
					$sql.= " (datec, ";
					if ($this->ref) $sql.= "ref, ";
					$sql.= "price_min, price_min_ttc, ";
					$sql.= "label, ";
					$sql.= "fk_user_author, fk_product_type, price, price_ttc, price_base_type, canvas, finished)";
					$sql.= " VALUES (".$this->db->idate(mktime()).", ";
					if ($this->ref) $sql.= "'".$this->ref."',";
					$sql.= price2num($price_min_ht).",";
					$sql.= price2num($price_min_ttc).",";
					$sql.= " ".($this->libelle?"'".addslashes($this->libelle)."'":"null").",";
					$sql.= $user->id.",";
					$sql.= " ".$this->type.",";
					$sql.= price2num($price_ht).",";
					$sql.= price2num($price_ttc).",";
					$sql.= "'".$this->price_base_type."',";
					$sql.= "'".$this->canvas."',";
					$sql.= " ".$this->finished.")";

					dolibarr_syslog("Product::Create sql=".$sql);
					$result = $this->db->query($sql);
					if ( $result )
					{
						$id = $this->db->last_insert_id(MAIN_DB_PREFIX."product");

						if ($id > 0)
						{
							$this->id = $id;
							$this->price     = $price_ht;
							$this->price_ttc = $price_ttc;
							$this->price_min     = $price_min_ht;
							$this->price_min_ttc = $price_min_ttc;

							$result = $this->_log_price($user);
							if ($result > 0)
							{
								if ( $this->update($id, $user) > 0)
								{
									if ($this->catid > 0)
									{
										require_once(DOL_DOCUMENT_ROOT ."/categories/categorie.class.php");
										$cat = new Categorie ($this->db, $this->catid);
										$cat->add_type($this,"product");
									}
								}
								else
								{
									$this->_setErrNo("Create",260,$this->error);
								}
							}
							else
							{
								$this->error=$this->db->error();
								$this->_setErrNo("Create",264,$this->error);
							}
						}
						else
						{
							$this->_setErrNo("Create",259);
						}
					}
					else
					{
						$this->error=$this->db->error();
						$this->_setErrNo("Create",258,$this->error);
					}
				}
				else
				{
					// Le produit existe deja
					$this->error='ErrorProductAlreadyExists';
				}
			}
			else
			{
				$this->_setErrNo("Create",263);
			}

			/*
			 * END COMMIT
			 */

			if ($this->errno === 0)
			{
				$this->db->commit();
				return $id;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->_setErrNo("Create",262);

			return -2;
		}

		return -1;
	}

	/**
	 \brief      Positionne le numero d'erreur
	 \param      func Nom de la fonction
	 \param      num Numero de l'erreur
	 \param		error string
	 */
	function _setErrNo($func, $num, $error='')
	{
		$this->errno = $num;
		dolibarr_syslog(get_class($this)."::".$func." - ERRNO(".$this->errno.")".($error?' - '.$error:''), LOG_ERR);
	}

	/**
	 \brief      Retourne le texte de l'erreur
	 */
	function error()
	{
		$errs[257] = "ErrorProductAlreadyExists";
		$errs[262] = "ErrorProductBadRefOrLabel";

		return $errs[$this->errno];
	}


	/**
		\brief      Mise a jour du produit en base
		\param      id          id du produit
		\param      user        utilisateur qui effectue l'insertion
		\return     int         1 si ok, -1 si ref deja existante, -2 autre erreur
		*/
	function update($id, $user)
	{
		global $langs, $conf;

		// Verification parametres
		if (! $this->libelle) $this->libelle = 'LIBELLE MANQUANT';

		// Nettoyage parametres
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		$this->description = trim($this->description);
		$this->note = trim($this->note);
		$this->stock_loc = trim($this->stock_loc);
		$this->weight = price2num($this->weight);
		$this->weight_units = trim($this->weight_units);
		$this->volume = price2num($this->volume);
		$this->volume_units = trim($this->volume_units);
		if (empty($this->tva_tx))    			$this->tva_tx = 0;
		if (empty($this->finished))  			$this->finished = 0;
		if (empty($this->seuil_stock_alerte))  	$this->seuil_stock_alerte = 0;
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
		$sql .= " SET label = '" . addslashes($this->libelle) ."'";
		if ($this->ref) $sql .= ",ref = '" . $this->ref ."'";
		$sql .= ",tva_tx = " . $this->tva_tx;
		$sql .= ",envente = " . $this->status;
		$sql .= ",finished = " . ($this->finished<0 ? "null" : $this->finished);
		$sql .= ",weight = " . ($this->weight!='' ? "'".$this->weight."'" : 'null');
		$sql .= ",weight_units = " . ($this->weight_units!='' ? "'".$this->weight_units."'": 'null');
		$sql .= ",volume = " . ($this->volume!='' ? "'".$this->volume."'" : 'null');
		$sql .= ",volume_units = " . ($this->volume_units!='' ? "'".$this->volume_units."'" : 'null');
		$sql .= ",seuil_stock_alerte = " . ($this->seuil_stock_alerte!='' ? $this->seuil_stock_alerte : "null");
		$sql .= ",description = '" . addslashes($this->description) ."'";
		$sql .= ",stock_loc   = '" . addslashes($this->stock_loc) ."'";
		$sql .= ",note = '" .        addslashes($this->note) ."'";
		$sql .= ",duration = '" . $this->duration_value . $this->duration_unit ."'";
		$sql .= " WHERE rowid = " . $id;

		dolibarr_syslog("Product::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Multilangs
			if($conf->global->MAIN_MULTILANGS)
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
	 *    \brief      Verification de l'utilisation du produit en base
	 *    \param      id          id du produit
	 */
	function verif_prod_use($id)
	{
		$sqr = 0;

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet";
		$sql.= " WHERE fk_product = ".$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num != 0)
	  {
	  	$sqr++;
	  }
		}

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet";
		$sql.= " WHERE fk_product = ".$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num != 0)
	  {
	  	$sqr++;
	  }
		}


		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet";
		$sql.= " WHERE fk_product = ".$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num != 0)
	  {
	  	$sqr++;
	  }
		}


		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet";
		$sql.= " WHERE fk_product = ".$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num != 0)
	  {
	  	$sqr++;
	  }
		}

		if ($sqr == 0)
		{
			return 0;
		}
		else
		{
			return -1;
		}
	}


	/**
	 *  \brief      Delete a product from database (if not used)
	 *	\param      id          Product id
	 * 	\return		int			< 0 if KO, >= 0 if OK
	 */
	function delete($id)
	{
		global $conf,$user;

		if ($user->rights->produit->supprimer)
		{
			$prod_use = $this->verif_prod_use($id);
			if ($prod_use == 0)
			{
				$sqla = "DELETE from ".MAIN_DB_PREFIX."product";
				$sqla.= " WHERE rowid = ".$id;
				$resulta = $this->db->query($sqla);

				$sqlb = "DELETE from ".MAIN_DB_PREFIX."product_price";
				$sqlb.= " WHERE fk_product = ".$id;
				$resultb = $this->db->query($sqlb);

				$sqlb = "DELETE from ".MAIN_DB_PREFIX."product_price_min";
				$sqlb.= " WHERE fk_product = ".$id;
				$resultb = $this->db->query($sqlb);

				$sqlc = "DELETE from ".MAIN_DB_PREFIX."product_det";
				$sqlc.= " WHERE fk_product = ".$id;
				$resultc = $this->db->query($sqlc);

				$sqld = "DELETE from ".MAIN_DB_PREFIX."categorie_product";
				$sqld.= " WHERE fk_product = ".$id;
				$resultd = $this->db->query($sqld);

				return 0;
			}
			else
			{
				$this->error .= "Impossible de supprimer le produit.\n";
				return -1;
			}
		}
	}

	/**
	 *		\brief   update ou cr�e les traductions des infos produits
	 */
	function setMultiLangs()
	{
		global $langs;
		$langs_available = $langs->get_available_languages();
		$current_lang = $langs->getDefaultLang();

		foreach ($langs_available as $value)
		{
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_det";
			$sql.= " WHERE fk_product=".$this->id." AND lang='".$value."'";

			$result = $this->db->query($sql);

	  if ($value == $current_lang)
	  {
	  	if ($this->db->num_rows($result)) // si aucune ligne dans la base
	  	{
	  		$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_det";
	  		$sql2.= " SET label='".addslashes($this->libelle)."',";
	  		$sql2.= " description='".addslashes($this->description)."',";
	  		$sql2.= " note='".addslashes($this->note)."'";
	  		$sql2.= " WHERE fk_product=".$this->id." AND lang='".$value."'";
	  	}
	  	else
	  	{
	  		$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_det (fk_product, lang, label, description, note)";
	  		$sql2.= " VALUES(".$this->id.",'".$value."','". addslashes($this->libelle);
	  		$sql2.= "','".addslashes($this->description);
	  		$sql2.= "','".addslashes($this->note)."')";
	  	}
	  	if (!$this->db->query($sql2)) return -1;
	  }
	  else
	  {
	  	if ($this->db->num_rows($result)) // si aucune ligne dans la base
	  	{
	  		$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_det";
	  		$sql2.= " SET label='".addslashes($this->multilangs["$value"]["libelle"])."',";
	  		$sql2.= " description='".addslashes($this->multilangs["$value"]["description"])."',";
	  		$sql2.= " note='".addslashes($this->multilangs["$value"]["note"])."'";
	  		$sql2.= " WHERE fk_product=".$this->id." AND lang='".$value."'";
	  	}
	  	else
	  	{
	  		$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_det (fk_product, lang, label, description, note)";
	  		$sql2.= " VALUES(".$this->id.",'".$value."','". addslashes($this->multilangs["$value"]["libelle"]);
	  		$sql2.= "','".addslashes($this->multilangs["$value"]["description"]);
	  		$sql2.= "','".addslashes($this->multilangs["$value"]["note"])."')";
	  	}

	  	// on ne sauvegarde pas des champs vides
	  	if ( $this->multilangs["$value"]["libelle"] || $this->multilangs["$value"]["description"] || $this->multilangs["$value"]["note"] )
	  	if (!$this->db->query($sql2)) return -1;
	  }
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
	  	if( $obj->lang == $current_lang ) // si on a les traduct. dans la langue courant on les charge en infos principales.
	  	{
	  		$this->libelle		= $obj->label;
	  		$this->description	= $obj->description;
	  		$this->note			= $obj->note;
	  	}
	  	$this->multilangs["$obj->lang"]["libelle"]		= $obj->label;
	  	$this->multilangs["$obj->lang"]["description"]	= $obj->description;
	  	$this->multilangs["$obj->lang"]["note"]			= $obj->note;

	  }
		}
		else
		{
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			return -1;
		}
	}



	/**
	 *   \brief  	Ajoute un changement de prix en base dans l'historique des prix
	 *	\param  	user        Objet utilisateur qui modifie le prix
	 *	\return		int			<0 si KO, >0 si OK
	 */
	function _log_price($user)
	{
		// MultiPrix : si activ�, on g�re tout ici, m�me le prix standard
		global $conf;

		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			$queryError = false;
			for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
			{
				if($this->multiprices["$i"] != "")
				{
					// Prise en compte du type
					if ($this->multiprices_base_type["$i"] == 'TTC')
					{
						$multiprice_ttc = price2num($this->multiprices["$i"],'MU');
						$multiprice_ht = price2num($this->multiprices["$i"] / (1 + ($this->tva_tx / 100)),'MU');
					}
					else
					{
						$multiprice_ht = price2num($this->multiprices["$i"],'MU');
						$multiprice_ttc = price2num($this->multiprices["$i"] * (1 + ($this->tva_tx / 100)),'MU');
					}

					// On ajoute nouveau tarif
					$sql_multiprix = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price_level,price,price_ttc,price_base_type,tva_tx) ";
					$sql_multiprix.= " VALUES(".$this->db->idate(mktime()).",".$this->id.",".$user->id.",".$i.",".price2num($multiprice_ht).",'".price2num($multiprice_ttc)."','".$this->multiprices_base_type["$i"]."',".$this->tva_tx;
					$sql_multiprix.= ")";
					if (! $this->db->query($sql_multiprix) )
					{
						$queryError = true;
					}
				}
			}
			if (strlen(trim($this->price)) > 0 )
			{
				// On ajoute nouveau tarif
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,envente,tva_tx,price_min,price_min_ttc) ";
				$sql.= " VALUES(".$this->db->idate(mktime()).",".$this->id.",".$user->id.",".$this->price.",".$this->price_ttc.",'".$this->price_base_type."',".$this->status.",".$this->tva_tx;
				$sql.= ",".$this->price_min.",".$this->price_min_ttc;
				$sql.= ")";
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
			$queryError = false;

			// On ajoute nouveau tarif
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,envente,tva_tx,price_min,price_min_ttc) ";
			$sql.= " VALUES(".$this->db->idate(mktime()).",".$this->id.",".$user->id.",".$this->price.",".$this->price_ttc.",'".$this->price_base_type."',".$this->status.",".$this->tva_tx;
			$sql.= ",".$this->price_min.",".$this->price_min_ttc;
			$sql.= ")";

			dolibarr_syslog("Product::_log_price sql=".$sql);
			$resql=$this->db->query($sql);
			if (!$resql)
			$queryError = true;

			if($queryError)
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
	 *   	\brief  	Delete a price line
	 * 		\param		user	Object user
	 * 		\param		rowid	Line id to delete
	 * 		\return		int		<0 if KO, >0 if OK
	 */
	function log_price_delete($user,$rowid)
	{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price";
			$sql.= " WHERE rowid=".$rowid;

			dolibarr_syslog("Product::log_price_delete sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -1;
			}
		
	}

	
	/**
	 *    \brief		Lit le prix pratique par un fournisseur
	 *				On renseigne le couple prodfournprice/qty ou le triplet qty/product_id/fourn_ref)
	 *    \param     prodfournprice      Id du tarif = rowid table product_fournisseur_price
	 *    \param     qty                 Quantit� du produit
	 *    \return    int 				<0 si ko, 0 si ok mais rien trouv�, id_product si ok et trouv�
	 */
	function get_buyprice($prodfournprice,$qty,$product_id=0,$fourn_ref=0)
	{
		$result = 0;
		$sql = "SELECT pfp.rowid, pfp.price as price, pfp.quantity as quantity,";
		$sql.= " pf.fk_product, pf.ref_fourn";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product_fournisseur as pf";
		$sql.= " WHERE pf.rowid = pfp.fk_product_fournisseur";
		$sql.= " AND pfp.rowid = ".$prodfournprice;
		$sql.= " AND pfp.quantity <= ".$qty;

		dolibarr_syslog("Product::get_buyprice sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj && $obj->quantity > 0)
			{
				$this->buyprice = $obj->price;                      // \deprecated
				$this->fourn_pu = $obj->price / $obj->quantity;     // Prix unitaire du produit pour le fournisseur $fourn_id
				$this->ref_fourn = $obj->ref_fourn;
				$result=$obj->fk_product;
				return $result;
			}
			else
			{
				// On refait le meme select sur la ref et l'id du produit
				$sql = "SELECT pfp.price as price, pfp.quantity as quantity, pf.fk_soc,";
				$sql.= " pf.fk_product, pf.ref_fourn";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product_fournisseur as pf";
				$sql.= " WHERE pf.rowid = pfp.fk_product_fournisseur";
				$sql.= " AND pf.ref_fourn = '".$fourn_ref."'";
				$sql.= " AND pf.fk_product = ".$product_id;
				$sql.= " AND quantity <= ".$qty;
				$sql.= " ORDER BY pfp.quantity DESC";
				$sql.= " LIMIT 1";

				dolibarr_syslog("Product::get_buyprice sql=".$sql);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj && $obj->quantity > 0)
					{
						$this->buyprice = $obj->price;                      // \deprecated
						$this->fourn_pu = $obj->price / $obj->quantity;     // Prix unitaire du produit pour le fournisseur $fourn_id
						$this->ref_fourn = $obj->ref_fourn;
						$result=$obj->fk_product;
						return $result;
					}
					else
					{
						return -1;	// Ce produit existe chez ce fournisseur mais qt� insuffisante
					}
				}
				else
				{
					$this->error=$this->db->error();
					return -3;
				}
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -2;
		}
		return $result;
	}


	/**
	 *	\brief  	Modifie le prix d'un produit/service
	 *	\param  	id          	Id du produit/service a modifier
	 *	\param  	newprice		Nouveau prix
	 *	\param  	newpricebase	HT ou TTC
	 *	\param  	user        	Objet utilisateur qui modifie le prix
	 *	\param  	newvat			New VAT Rate
	 * 	\return		int				<0 if KO, >0 if OK
	 */
	function update_price($id, $newprice, $newpricebase, $user, $newvat='',$newminprice='')
	{
		//multiprix
		global $conf,$langs;
		dolibarr_syslog("Product::update_price id=".$id." newprice=".$newprice." newpricebase=".$newpricebase, LOG_DEBUG);

		if ($newvat == '') $newvat=$this->tva_tx;

		if ($newprice)
		{
			if ($newpricebase == 'TTC')
			{
				$price_ttc = price2num($newprice,'MU');
				$price = price2num($newprice) / (1 + ($newvat / 100));
				$price = price2num($price,'MU');

				if($newminprice!='')
				{
					$price_min_ttc = price2num($newminprice,'MU');
					$price_min = price2num($newminprice) / (1 + ($newvat / 100));
					$price_min = price2num($price_min,'MU');
				}
				else 
				{
					$price_min=0;
					$price_min_ttc=0;
				}
			}
			else
			{
				$price = price2num($newprice,'MU');
				$price_ttc = price2num($newprice) * (1 + ($newvat / 100));
				$price_ttc = price2num($price_ttc,'MU');

				if ($newminprice!='')
				{
					$price_min = price2num($newminprice,'MU');
					$price_min_ttc = price2num($newminprice) * (1 + ($newvat / 100));
					$price_min_ttc = price2num($price_min_ttc,'MU');
				}
				else 
				{
					$price_min=0;
					$price_min_ttc=0;
				}
			}
			//print 'x'.$id.'-'.$newprice.'-'.$newpricebase.'-'.$price.'-'.$price_ttc.'-'.$price_min.'-'.$price_min_ttc;
			
			// Ne pas mettre de quote sur le numeriques decimaux.
			// Ceci provoque des stockage avec arrondis en base au lieu des valeurs exactes.
			$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
			$sql.= " price_base_type='".$newpricebase."',";
			$sql.= " price=".$price.",";
			$sql.= " price_ttc=".$price_ttc.",";
			$sql.= " price_min=".$price_min.",";
			$sql.= " price_min_ttc=".$price_min_ttc.",";
			$sql.= " tva_tx='".price2num($newvat)."'";
			$sql.= " WHERE rowid = " . $id;

			dolibarr_syslog("Product::update_price sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->price = $price;
				$this->price_ttc = $price_ttc;
				$this->price_min = $price_min;
				$this->price_min_ttc = $price_min_ttc;
				$this->price_base_type = $newpricebase;
				$this->tva_tx = $newvat;

				$this->_log_price($user);
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				return -1;
			}
		}
		else if(($conf->global->PRODUIT_MULTIPRICES) && (count($this->multiprices) > 0))
		{
			$this->_log_price($user);
			return 1;
		}
		else
		{
			$this->error = $langs->trans("ErrorBadParameter");
			return -2;
		}
	}


	/**
	 *      \brief      Charge le produit/service en m�moire
	 *      \param      id      Id du produit/service � charger
	 *      \param      ref     Ref du produit/service � charger
	 *      \return     int     <0 si ko, >0 si ok
	 */
	function fetch($id='',$ref='')
	{
		global $langs;
		global $conf;

		dolibarr_syslog("Product::fetch id=$id ref=$ref");

		// Verification parametres
		if (! $id && ! $ref)
		{
			$this->error=$langs->trans('ErrorWrongParameters');
			dolibarr_print_error("Product::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT rowid, ref, label, description, note, price, price_ttc, price_min, price_min_ttc, price_base_type, tva_tx, envente,";
		$sql.= " fk_product_type, duration, seuil_stock_alerte,canvas,";
		$sql.= " stock_commande, stock_loc, weight, weight_units, volume, volume_units, barcode, fk_barcode_type, finished";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		if ($id) $sql.= " WHERE rowid = '".$id."'";
		if ($ref) $sql.= " WHERE ref = '".addslashes($ref)."'";

		dolibarr_syslog("Product::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$result = $this->db->fetch_array();

			$this->id                 = $result["rowid"];
			$this->ref                = $result["ref"];
			$this->libelle            = $result["label"];
			$this->description        = $result["description"];
			$this->note               = $result["note"];
			$this->price              = $result["price"];
			$this->price_ttc          = $result["price_ttc"];
			$this->price_min          = $result["price_min"];
			$this->price_min_ttc      = $result["price_min_ttc"];
			$this->price_base_type    = $result["price_base_type"];
			$this->tva_tx             = $result["tva_tx"];
			$this->type               = $result["fk_product_type"];
			$this->status             = $result["envente"];
			$this->finished           = $result["finished"];
			$this->duration           = $result["duration"];
			$this->duration_value     = substr($result["duration"],0,strlen($result["duration"])-1);
			$this->duration_unit      = substr($result["duration"],-1);
			$this->seuil_stock_alerte = $result["seuil_stock_alerte"];
			$this->canvas             = $result["canvas"];
			$this->stock_loc          = $result["stock_loc"];
			$this->weight             = $result["weight"];
			$this->weight_units       = $result["weight_units"];
			$this->volume             = $result["volume"];
			$this->volume_units       = $result["volume_units"];
			$this->barcode            = $result["barcode"];
			$this->barcode_type       = $result["fk_barcode_type"];

			$this->stock_in_command   = $result["stock_commande"];

			$this->label_url = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">'.$this->libelle.'</a>';

			$this->db->free();
			// multilangs
			if ($conf->global->MAIN_MULTILANGS) $this->getMultiLangs();

			// Barcode
			if ($conf->global->MAIN_MODULE_BARCODE)
			{
				if ($this->barcode_type == 0)
				{
					$this->barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
				}

				if ($this->barcode_type > 0)
				{
					$sql = "SELECT code, libelle, coder";
					$sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
					$sql.= " WHERE rowid = ".$this->barcode_type;
					$result = $this->db->query($sql);
					if ($result)
					{
						$result = $this->db->fetch_array();
						$this->barcode_type_code = $result["code"];
						$this->barcode_type_label = $result["libelle"];
						$this->barcode_type_coder = $result["coder"];
					}
					else
					{
						dolibarr_print_error($this->db);
						return -1;
					}
				}
			}

			// multiprix
			if ($conf->global->PRODUIT_MULTIPRICES)
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
					
				$this->multiprices[1] = $this->price;
				$this->multiprices_ttc[1] = $this->price_ttc;
				$this->multiprices_base_type[1] = $this->price_base_type;

				for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
				{
					$sql= "SELECT price, price_ttc, price_base_type, tva_tx, envente ";
					$sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
					$sql.= "where price_level=".$i." and ";
					if ($id) $sql.= "fk_product = '".$id."' ";
					if ($ref) $sql.= "fk_product = '".$prodid."' ";
					$sql.= "order by date_price DESC limit 1";
					$result = $this->db->query($sql) ;
					if ( $result )
					{
						$result = $this->db->fetch_array();
							
						if ($result["price"] != "" && $result["price"] != "0.00")
						{
							$this->multiprices[$i]=$result["price"];
							$this->multiprices_ttc[$i]=$result["price_ttc"];
							$this->multiprices_base_type[$i] = $result["price_base_type"];
						}
						else
						{
							$this->multiprices[$i]=$this->price;
							$this->multiprices_ttc[$i]=$this->price_ttc;
							$this->multiprices_base_type[$i] = $this->price_base_type;
						}
					}
					else
					{
						dolibarr_print_error($this->db);
						return -1;
					}
				}
					
			}

			$res=$this->load_stock();

			return $res;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
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
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."propal as pr";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE pr.rowid = pd.fk_propal AND pd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND pr.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	 *    \brief    Charge tableau des stats commande client pour le produit/service
	 *    \param    socid       	Id societe pour filtrer sur une societe
	 *    \param    filtrestatut    Id statut pour filtrer sur un statut
	 *    \return   array       	Tableau des stats
	 */
	function load_stats_commande($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd,";
		$sql.= " ".MAIN_DB_PREFIX."commande as c";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql.= " AND c.fk_soc = ".$socid;
		}

		if ($filtrestatut <> '')
		{
			$sql.= " AND c.fk_statut in (".$filtrestatut.")";
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
	 *    \brief    Charge tableau des stats commande fournisseur pour le produit/service
	 *    \param    socid       	Id societe pour filtrer sur une soci�t�
	 *    \param    filtrestatut    Id des statuts pour filtrer sur des statuts
	 *    \return   array       	Tableau des stats
	 */
	function load_stats_commande_fournisseur($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_suppliers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd,";
		$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as c";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql.= " AND c.fk_soc = ".$socid;
		}
		if ($filtrestatut != '')	// Peut valoir 0
		{
			$sql.= " AND c.fk_statut in (".$filtrestatut.")";
		}

		$result = $this->db->query($sql) ;
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_commande_fournisseur['suppliers']=$obj->nb_suppliers;
			$this->stats_commande_fournisseur['nb']=$obj->nb;
			$this->stats_commande_fournisseur['rows']=$obj->nb_rows;
			$this->stats_commande_fournisseur['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *    \brief    Charge tableau des stats expedition client pour le produit/service
	 *    \param    socid       	Id societe pour filtrer sur une societe
	 *    \param    filtrestatut    Id statut pour filtrer sur un statut
	 *    \return   array       	Tableau des stats
	 */
	function load_stats_sending($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(ed.rowid) as nb_rows, SUM(ed.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."commandedet as cd,";
		$sql.= " ".MAIN_DB_PREFIX."expedition as c";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = ed.fk_expedition AND ed.fk_origin_line = cd.rowid AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql.= " AND c.fk_soc = ".$socid;
		}

		if ($filtrestatut <> '')
		{
			$sql.= " AND c.fk_statut in (".$filtrestatut.")";
		}

		$result = $this->db->query($sql) ;
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_expedition['customers']=$obj->nb_customers;
			$this->stats_expedition['nb']=$obj->nb;
			$this->stats_expedition['rows']=$obj->nb_rows;
			$this->stats_expedition['qty']=$obj->qty?$obj->qty:0;
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
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd,";
		$sql.= " ".MAIN_DB_PREFIX."contrat as c";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_contrat AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
			$this->error=$this->db->error().' sql='.$sql;
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
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as pd,";
		$sql.= " ".MAIN_DB_PREFIX."facture as f";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = pd.fk_facture AND pd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	 *    \brief    Charge tableau des stats facture pour le produit/service
	 *    \param    socid       Id societe
	 *    \return   array       Tableau des stats
	 */
	function load_stats_facture_fournisseur($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_suppliers, COUNT(DISTINCT f.rowid) as nb,";
		$sql.= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as pd,";
		$sql.= " ".MAIN_DB_PREFIX."facture_fourn as f";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = pd.fk_facture_fourn AND pd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND f.fk_statut != 0";
		if ($socid > 0)
		{
			$sql .= " AND f.fk_soc = $socid";
		}

		$result = $this->db->query($sql) ;
		if ( $result )
		{
			$obj=$this->db->fetch_object($result);
			$this->stats_facture_fournisseur['suppliers']=$obj->nb_suppliers;
			$this->stats_facture_fournisseur['nb']=$obj->nb;
			$this->stats_facture_fournisseur['rows']=$obj->nb_rows;
			$this->stats_facture_fournisseur['qty']=$obj->qty?$obj->qty:0;
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *    \brief    Return an array formated for showing graphs
	 *    \param    sql         Request to execute
	 *    \return   array       <0 if KO, result[i]=array(valuex,valuey);
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
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}

		$year = strftime('%Y',time());
		$month = strftime('%m',time());
		$result = array();

		for ($j = 0 ; $j < 12 ; $j++)
		{
			$idx=ucfirst(substr(dolibarr_print_date(dolibarr_mktime(12,0,0,$month,1,$year),"%b") ,0,3) );
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
	 *    \brief  Renvoie le nombre de factures clients du produit/service par mois
	 *    \param  socid       id societe
	 *    \return array       nombre de vente par mois
	 */
	function get_nb_vente($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
		$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE f.rowid = d.fk_facture and d.fk_product =".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql .= " AND f.fk_soc = $socid";
		}
		$sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

		return $this->_get_stats($sql);
	}


	/**
	 *    \brief  Renvoie le nombre de factures fournisseurs du produit/service par mois
	 *    \param  socid       id societe
	 *    \return array       nombre d'achat par mois
	 */
	function get_nb_achat($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m') ";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as d, ".MAIN_DB_PREFIX."facture_fourn as f";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE f.rowid = d.fk_facture_fourn and d.fk_product =".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql .= " AND f.fk_soc = $socid";
		}
		$sql .= " GROUP BY date_format(f.datef,'%Y%m') DESC ;";

		$resarray=$this->_get_stats($sql);
		return $resarray;
	}

	/**
	 *    \brief  Renvoie le nombre de propales dans lesquelles figure le produit par mois
	 *    \param  socid       id societe
	 *    \return array       nombre de propales par mois
	 */
	function get_nb_propal($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(p.datep, '%Y%m') ";
		$sql .= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."propal as p";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE p.rowid = d.fk_propal and d.fk_product =".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql .= " AND p.fk_soc = $socid";
		}
		$sql .= " GROUP BY date_format(p.datep,'%Y%m') DESC ;";

		return $this->_get_stats($sql);
	}

	/**
	 *    \brief  Renvoie le nombre de commandes dans lesquelles figure le produit par mois
	 *    \param  socid       id societe
	 *    \return array       nombre de commandes par mois
	 */
	function get_nb_order($socid=0)
	{
		global $conf, $user;

		$sql = "SELECT sum(d.qty), date_format(p.date_commande, '%Y%m') ";
		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as d, ".MAIN_DB_PREFIX."commande as p";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE p.rowid = d.fk_commande and d.fk_product =".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)
		{
			$sql .= " AND p.fk_soc = $socid";
		}
		$sql .= " GROUP BY date_format(p.date_commande,'%Y%m') DESC ;";

		return $this->_get_stats($sql);
	}

	/**
	 *    \brief      Lie un produit associe au produit/service
	 *    \param      id_pere    Id du produit auquel sera li� le produit � lier
	 *    \param      id_fils    Id du produit � lier
	 *    \return     int        < 0 si erreur, > 0 si ok
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
			$sql = 'select fk_product_pere from '.MAIN_DB_PREFIX.'product_association';
			$sql .= ' WHERE fk_product_pere  = "'.$id_fils.'" and fk_product_fils = "'.$id_pere.'"';
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
		    $this->error="isFatherOfThis";
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
	  }

		}
	}

	/**
	 *    \brief      retire le lien entre un sousproduit et un produit/service
	 *    \param      id_pere    Id du produit auquel ne sera plus li� le produit li
	 *    \param      id_fils  Id du produit � ne plus li
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
	 *    \param      id_pere    Id du produit auquel ne sera plus li� le produit li
	 *    \param      id_fils  Id du produit � ne plus li
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
	 *    \brief      Remplit le tableau des sous-produits
	 *    \return     int        < 0 si erreur, > 0 si ok
	 */
	function load_subproduct()
	{
		$this->subproducts_id = array();
		$i = 0;

		$sql = "SELECT fk_product_subproduct FROM ".MAIN_DB_PREFIX."product_subproduct";
		$sql .= " WHERE fk_product=$this->id;";

		if ($result = $this->db->query($sql))
		{
	  while ($row = $this->db->fetch_row($result) )
	  {
	  	$this->subproducts_id[$i] = $row[0];
	  	$i++;
	  }
	  $this->db->free($result);
	  return 0;
		}
		else
		{
	  return -1;
		}
	}


	/**
	 *    \brief      Lie un sous produit au produit/service
	 *    \param      id_sub     Id du produit � lier
	 *    \return     int        < 0 si erreur, > 0 si ok
	 */
	function add_subproduct($id_sub)
	{
		if ($id_sub)
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_subproduct(fk_product,fk_product_subproduct)';
			$sql .= ' VALUES ("'.$this->id.'","'.$id_sub.'")';
			if (! $this->db->query($sql))
	  {
	  	dolibarr_print_error($this->db);
	  	return -1;
	  }
	  else
	  {
	  	return 0;
	  }
		}
		else
		{
			return -2;
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
		$sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn." AND ref_fourn = '".$ref_fourn."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj->nb == 0)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur ";
				$sql .= " (datec, fk_product, fk_soc, ref_fourn, fk_user_author)";
				$sql .= " VALUES (".$this->db->idate(mktime()).", ".$this->id.", ".$id_fourn.", '".$ref_fourn."', ".$user->id.")";
					
				if ($this->db->query($sql))
				{
					$this->product_fourn_id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur");
					return 1;
				}
				else
				{
					$this->error=$this->db->error();
					return -1;
				}
			}
			else
			{
				$sql = "SELECT rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
				$sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn." AND ref_fourn = '".$ref_fourn."'";

				$resql=$this->db->query($sql);
				if ($resql)
				{
					$obj = $this->db->fetch_object($resql);
					$this->product_fourn_id = $obj->rowid;
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
	 *    \brief  	Renvoie la liste des fournisseurs du produit/service
	 *    \return 	array		Tableau des id de fournisseur
	 */
	function list_suppliers()
	{
		$list = array();

		$sql = "SELECT fk_soc";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur as p";
		$sql.= " WHERE p.fk_product = ".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i=0;
			while ($i < $num)
	  {
	  	$obj = $this->db->fetch_object($result);
	  	$list[$i] = $obj->fk_soc;
	  	$i++;
	  }
		}

		return $list;
	}

	/**
	 *		\brief		Saisie une commande fournisseur
	 *		\param		user		Objet user de celui qui demande
	 *		\return		int			<0 si ko, >0 si ok
	 */
	function fastappro($user)
	{
		include_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php";

		$list = $this->list_suppliers();
		if (sizeof($list) > 0)
		{
			dolibarr_syslog("Product::fastappro");
			$fournisseur = new Fournisseur($this->db);
			$fournisseur->fetch($this->fourn_appro_open);

			$fournisseur->ProductCommande($user, $this->id);
		}
		return 1;
	}


	/**
	 *    \brief  Supprime un tarif fournisseur
	 *    \param  user        utilisateur qui d�fait le lien
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
		. " select '".$this->db->idate(mktime())."', ".$toId.", fk_soc, ref_fourn, fk_user_author"
		. " from ".MAIN_DB_PREFIX."product_fournisseur "
		. " where fk_product = ".$fromId .";" ;
		if ( ! $db->query($sql ) ) {
			$db->rollback();
			return -1;
		}
		// les prix de fournisseurs.
		$sql = "insert ".MAIN_DB_PREFIX."product_fournisseur_price ("
		. " datec, fk_product, fk_soc, price, quantity, fk_user )"
		. " select '".$this->db->idate(mktime())."', ".$toId. ", fk_soc, price, quantity, fk_user"
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
	 *   \brief fonction recursive uniquement utilisee par get_arbo_each_prod, recompose l'arborescence des sousproduits
	 *   \return void
	 */
	function fetch_prod_arbo($prod,$compl_path="",$multiply="")
	{
		global $langs;

		$this->res;
		$this->pere_encours;
		foreach($prod as $nom_pere => $desc_pere)
		{
			// on est dans une sous-categorie
			if(is_array($desc_pere))
			{
				if($multiply)
				{
					$img="";
					$trueValue=$desc_pere[1]*$multiply;
					$product = new Product($this->db);
					$this->fetch($desc_pere[0]);
					$this->load_stock();
					if ($this->stock_entrepot[1] < $this->seuil_stock_alerte)
					{
						$img=img_warning($langs->trans("StockTooLow"));
					}
					$this->res[]= array("<tr><td>&nbsp; &nbsp; &nbsp;
                                <a href=\"".DOL_URL_ROOT."/product/fiche.php?id=".$desc_pere[0]."\">".$compl_path.stripslashes($nom_pere)."
                                </a></td><td align=\"center\"> ".$trueValue."</td><td>&nbsp</td></td><td>&nbsp</td>
                                </td><td align=\"center\">".$this->stock_entrepot[1]." ".$img."</td></tr>",
					$desc_pere[0]);
				}
				else
				{
					$this->res[]= array($compl_path.$nom_pere." (".$desc_pere[1].")",$desc_pere[0]);
				}
			}
			else if($nom_pere != "0" && $nom_pere != "1")
			$this->res[]= array($compl_path.$nom_pere,$desc_pere);
			if(sizeof($desc_pere) >1)
			{
				$this ->fetch_prod_arbo($desc_pere,$nom_pere." -> ");
			}
		}
	}

	/**
	 *   \brief fonction recursive uniquement utilisee par get_each_prod, ajoute chaque sousproduits dans le tableau res
	 *   \return void
	 */
	function fetch_prods($prod)
	{
		$this->res;
		foreach($prod as $nom_pere => $desc_pere)
		{
			// on est dans une sous-categorie
			if(is_array($desc_pere))
			$this->res[]= array($desc_pere[1],$desc_pere[0]);
			if(sizeof($desc_pere) >1)
			{
				$this ->fetch_prods($desc_pere);
			}
		}
	}

	/**
	 *   \brief reconstruit l'arborescence des categorie sous la forme d'un tableau
	 *   \return array $this->res
	 */
	function get_arbo_each_prod($multiply="")
	{
		$this->res = array();
		if(is_array($this -> sousprods))
		{
			foreach($this -> sousprods as $nom_pere => $desc_pere)
			{
				if(sizeof($desc_pere) >1)
				$this ->fetch_prod_arbo($desc_pere,"",$multiply);
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
	 *   \brief Retourne les cat�gories p�res
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
	 *  \brief Retourne les fils de la cat�gorie structur�s pour l'arbo
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
	 * \brief compose l'arborescence des sousproduits, id, nom et quantit� sous la forme d'un tableau associatif
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
		// on concat�ne tout �a
		foreach($peres as $k=>$v)
		{
			$this -> sousprods[$k]=$v;
		}
	}

	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\param		maxlength		Maxlength of ref
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';
		$newref=$this->ref;
		if ($maxlength) $newref=dolibarr_trunc($newref,$maxlength,'middle');

		if ($withpicto) {
			if ($this->type == 0) $result.=($lien.img_object($langs->trans("ShowProduct").' '.$this->ref,'product').$lienfin.' ');
			if ($this->type == 1) $result.=($lien.img_object($langs->trans("ShowService").' '.$this->ref,'service').$lienfin.' ');
		}
		$result.=$lien.$newref.$lienfin;
		return $result;
	}

	/**
	 *    	\brief      Retourne le libell� du statut d'une facture (brouillon, valid�e, abandonn�e, pay�e)
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *    	\brief      Renvoi le libell� d'un statut donne
	 *    	\param      status      Statut
	 *		\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string      Libell� du statut
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
		if ($mode == 3)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5');
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.$langs->trans('ProductStatusNotOnSell');
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.$langs->trans('ProductStatusOnSell');
		}
		if ($mode == 5)
		{
			if ($status == 0) return $langs->trans('ProductStatusNotOnSell').' '.img_picto($langs->trans('ProductStatusNotOnSell'),'statut5');
			if ($status == 1) return $langs->trans('ProductStatusOnSell').' '.img_picto($langs->trans('ProductStatusOnSell'),'statut4');
		}
		return $langs->trans('Unknown');
	}
	
	
	/**
	*    	\brief      Retourne le libell� du finished du produit
	*    	\return     string		Libelle
	*/
	function getLibFinished()
	{
		global $langs;
		$langs->load('products');
		
		if ($this->finished == '0') return $langs->trans("RowMaterial");
		if ($this->finished == '1') return $langs->trans("Finished");
		return '';
	}

	/**
	 *    \brief  Entre un nombre de piece du produit en stock dans un entrep�t
	 *    \param  id_entrepot     id de l'entrepot
	 *    \param  nbpiece         nombre de pieces
	 */
	function create_stock($id_entrepot, $nbpiece)
	{
		global $user;

		$op[0] = "+".trim($nbpiece);
		$op[1] = "-".trim($nbpiece);
		$mouvement=0;	// We add pieces

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock ";
		$sql .= " (fk_product, fk_entrepot, reel)";
		$sql .= " VALUES ($this->id, $id_entrepot, $nbpiece)";

		dolibarr_syslog("Product::create_stock sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author)";
			$sql .= " VALUES (".$this->db->idate(mktime()).", ".$this->id.", ".$id_entrepot.", ".$nbpiece.", 0, ".$user->id.")";

			dolibarr_syslog("Product::create_stock sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
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
			return -1;
		}
	}


	/**
	 *    \brief  Ajuste le stock d'un entrepot pour le produit a une valeure donnee
	 *    \param  user            utilisateur qui demande l'ajustement
	 *    \param  id_entrepot     id de l'entrepot
	 *    \param  nbpiece         nombre de pieces
	 *    \param  mouvement       0 = ajout, 1 = suppression
	 */
	function correct_stock($user, $id_entrepot, $nbpiece, $mouvement)
	{
		if ($id_entrepot)
		{
			$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."product_stock";
			$sql .= " WHERE fk_product = ".$this->id." AND fk_entrepot = ".$id_entrepot;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$row = $this->db->fetch_object($resql);
				if ($row->nb > 0)
				{
					// Record already exists, we make an update
					return $this->ajust_stock($user, $id_entrepot, $nbpiece, $mouvement);
				}
				else
				{
					// Record not yet available, we make an insert
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
	 *    \brief  Augmente ou r�duit la valeur de stock pour le produit
	 *    \param  user            utilisateur qui demande l'ajustement
	 *    \param  id_entrepot     id de l'entrepot
	 *    \param  nbpiece         nombre de pieces
	 *    \param  mouvement       0 = ajout, 1 = suppression
	 */
	function ajust_stock($user, $id_entrepot, $nbpiece, $mouvement)
	{
		$op[0] = "+".trim($nbpiece);
		$op[1] = "-".trim($nbpiece);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_stock";
		$sql.= " SET reel = reel ".$op[$mouvement];
		$sql.= " WHERE fk_product = ".$this->id." AND fk_entrepot = ".$id_entrepot;

		dolibarr_syslog("Product::ajust_stock sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author)";
			$sql .= " VALUES (".$this->db->idate(mktime()).", ".$this->id.", ".$id_entrepot.", ".$op[$mouvement].", 0, ".$user->id.")";

			dolibarr_syslog("Product::ajust_stock sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
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

	/**
	 *    \brief  Augmente ou r�duit le nombre de piece en commande a expedier
	 *    \param  nbpiece         nombre de pieces
	 *    \param  mouvement       0 = ajout, 1 = suppression
	 *    \return     int             < 0 si erreur, > 0 si ok
	 */
	function ajust_stock_commande($nbpiece, $mouvement)
	{
		$op[0] = "+" . trim($nbpiece);
		$op[1] = "-" . trim($nbpiece);

		if ($this->db->begin())
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."product ";
			$sql .= " SET stock_commande = stock_commande ".$op[$mouvement];
			$sql .= " WHERE rowid = '".$this->id ."';";

			if ($this->db->query($sql) )
	  {

	  	$this->load_subproduct();

	  	for ($i = 0 ; $i < sizeof($this->subproducts_id) ; $i++)
	  	{
	  		$product = new Product($this->db);
	  		$product->id = $this->subproducts_id[$i];
	  		$product->ajust_stock_commande($nbpiece, $mouvement);
	  	}

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
			return -3;
		}
	}


	/**
	 *    \brief      Charge les informations en stock du produit
	 *    \return     int             < 0 si erreur, > 0 si ok
	 */
	function load_stock()
	{
		$this->stock_reel = 0;

		$sql = "SELECT reel, fk_entrepot";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock";
		$sql.= " WHERE fk_product = '".$this->id."'";
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
			return -1;
		}
	}


	/**
	 *    \brief      D�place fichier upload� sous le nom $files dans le r�pertoire sdir
	 *    \param      sdir        R�pertoire destination finale
	 *    \param      $file       Nom du fichier upload�
	 *    \param      maxWidth    Largeur maximum que dois faire la miniature (160 par d�faut)
	 *    \param      maxHeight   Hauteur maximum que dois faire la miniature (120 par d�faut)
	 */
	function add_photo($sdir, $file, $maxWidth = 160, $maxHeight = 120)
	{
		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/";
		$dir .= "photos/";

		if (! file_exists($dir))
		{
			dolibarr_syslog("Product Create $dir");
			create_exdir($dir);
		}

		if (file_exists($dir))
		{
			$originImage = $dir . $file['name'];

			// Cree fichier en taille origine
			$result=dol_move_uploaded_file($file['tmp_name'], $originImage, 1);

			if (file_exists($originImage))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage,$maxWidth,$maxHeight);
			}
		}
	}

	/**
	 *    \brief      Build thumb
	 *    \param      sdir           Repertoire destination finale
	 *    \param      file           Chemin du fichier d'origine
	 *    \param      maxWidth       Largeur maximum que dois faire la miniature (160 par defaut)
	 *    \param      maxHeight      Hauteur maximum que dois faire la miniature (120 par defaut)
	 */
	function add_thumb($file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once(DOL_DOCUMENT_ROOT ."/lib/images.lib.php");

		if (file_exists($file))
		{
			vignette($file,$maxWidth,$maxHeight);
		}
	}

	/**
	 *    \brief      Deplace fichier recupere sur internet (utilise pour interface avec OSC)
	 *    \param      sdir        		Repertoire destination finale
	 *    \param      $files      		url de l'image
	 *	\author		Jean Heimburger		juin 2007
	 */
	function add_photo_web($sdir, $files)
	{
		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/";
		$dir .= "photos/";

		if (! file_exists($dir))
		{
			dolibarr_syslog("Product Create $dir");
			create_exdir($dir);
		}

		if (file_exists($dir))
		{
			// Cree fichier en taille vignette
			// \todo A faire

			// Cree fichier en taille origine
			$content = file_get_contents($files);

			$nom = basename($files);
			$im = fopen($dir.$nom,'wb');
			fwrite($im, $content);
			fclose($im);
			//		}
		}
	}

	/**
	 *    \brief      Affiche la premi�re photo du produit
	 *    \param      sdir        R�pertoire � scanner
	 *    \return     boolean     true si photo dispo, false sinon
	 */
	function is_photo_available($sdir)
	{
		$pdir = get_exdir($this->id,2) . $this->id ."/photos/";
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
	 *    \brief      Affiche toutes les photos du produit (nbmax maximum)
	 *    \param      sdir        R�pertoire � scanner
	 *    \param      size        0=taille origine, 1 taille vignette
	 *    \param      nbmax       Nombre maximum de photos (0=pas de max)
	 *    \param      nbbyrow     Nombre vignettes par ligne (si mode vignette)
	 *    \return     int         Nombre de photos affich�es
	 */
	function show_photos($sdir,$size=0,$nbmax=0,$nbbyrow=5)
	{
		$pdir = get_exdir($this->id,2) . $this->id ."/photos/";
		$dir = $sdir . '/'. $pdir;
		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';

		$nbphoto=0;
		if (file_exists($dir))
		{
			$handle=opendir($dir);

			while (($file = readdir($handle)) != false)
			{
				$photo='';

				if (is_file($dir.$file))
				{
					$nbphoto++;
					$photo = $file;

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
						if ($photo_vignette && is_file($dirthumb.$photo_vignette)) {
							print '<img border="0" height="120" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdirthumb.$photo_vignette).'">';
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
	 *    \param      dir         R�pertoire � scanner
	 *    \param      nbmax       Nombre maximum de photos (0=pas de max)
	 *    \return     array       Tableau de photos
	 */
	function liste_photos($dir,$nbmax=0)
	{
		$nbphoto=0;
		$tabobj=array();

		$dirthumb = $dir.'thumbs/';

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
					if (eregi('(\.jpg|\.bmp|\.gif|\.png|\.tiff)$',$photo,$regs))
					{
						$photo_vignette=eregi_replace($regs[0],'',$photo).'_small'.$regs[0];
					}

					// Objet
					$obj=array();
					$obj['photo']=$photo;
					if ($photo_vignette && is_file($dirthumb.$photo_vignette)) $obj['photo_vignette']=$photo_vignette;
					else $obj['photo_vignette']="";

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
	 *    \brief      Efface la photo du produit et sa vignette
	 *    \param      file        Chemin de l'image
	 */
	function delete_photo($file)
	{
		$dir = dirname($file).'/'; // Chemin du dossier contenant l'image d'origine
		$dirthumb = $dir.'/thumbs/'; // Chemin du dossier contenant la vignette
		$filename = eregi_replace($dir,'',$file); // Nom du fichier
			
		// On efface l'image d'origine
		unlink($file);
			
		// Si elle existe, on efface la vignette
		if (eregi('(\.jpg|\.bmp|\.gif|\.png|\.tiff)$',$filename,$regs))
		{
			$photo_vignette=eregi_replace($regs[0],'',$filename).'_small'.$regs[0];
			if (file_exists($dirthumb.$photo_vignette))
			{
				unlink($dirthumb.$photo_vignette);
			}
		}
	}

	/**
	 *    \brief      R�cup�re la taille de l'image
	 *    \param      file        Chemin de l'image
	 */
	function get_image_size($file)
	{
		$infoImg = getimagesize($file); // R�cup�ration des infos de l'image
		$this->imgWidth = $infoImg[0]; // Largeur de l'image
		$this->imgHeight = $infoImg[1]; // Hauteur de l'image
	}

	/**
	 *      \brief      Charge indicateurs this->nb de tableau de bord
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
		}
		$sql.= " WHERE p.fk_product_type <> 1";
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= " AND IFNULL(c.visible,1)=1";
		}
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

	/**
	 *    \brief      Mise � jour du code barre
	 *    \param      user        Utilisateur qui fait la modification
	 */
	function update_barcode($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."product";
		$sql .= " SET barcode = '".$this->barcode."'";
		$sql .= " WHERE rowid = ".$this->id;

		dolibarr_syslog("Product::update_barcode sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
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
	 *    \brief      Mise � jour du type de code barre
	 *    \param      user        Utilisateur qui fait la modification
	 */
	function update_barcode_type($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."product";
		$sql .= " SET fk_barcode_type = '".$this->barcode_type."'";
		$sql .= " WHERE rowid = ".$this->id;

		dolibarr_syslog("Product::update_barcode_type sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
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
	 \brief Affecte les valeurs smarty
	 \remarks Rodolphe : pour l'instant la fonction est vide mais necessaire pour compatibilite
	 avec les canvas A terme la fiche produit utilisera aussi smarty
	 */
	function assign_smarty_values(&$smarty)
	{

	}

	function isproduct() {
		if ($this->type != 1) {
			return 1;
		} else {
			return 0;
		}
	}

	function isservice() {
		if ($this->type==1) {
			return 1;
		} else {
			return 0;
		}
	}

}
?>
