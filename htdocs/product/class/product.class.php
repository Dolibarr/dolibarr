<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2007-2011 Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/product/class/product.class.php
 *	\ingroup    produit
 *	\brief      Fichier de la classe des produits predefinis
 *	\version    $Id: product.class.php,v 1.49 2011/08/04 21:46:51 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 * \class      Product
 * \brief      Class to manage products and services
 */
class Product extends CommonObject
{
	var $db;
	var $error;
	//! Error number
	var $errno = 0;
	var $element='product';
	var $table_element='product';
	var $isnolinkedbythird = 1;     // No field fk_soc
	var $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

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
	var $multiprices_tva_tx=array();
	//! Taux de TVA
	var $tva_tx;
	//! French VAT NPR
    var $tva_npr=0;
	//! Spanish local taxes
	var $localtax1_tx;
	var $localtax2_tx;
	//! Type 0 for regular product, 1 for service (Advanced feature: 2 for assembly kit, 3 for stock kit)
	var $type;
	var $typestring;

	//! Stock
	var $stock_reel;
	//! Average price value for product entry into stock (PMP)
	var $pmp;

	var $seuil_stock_alerte;
	//! Duree de validite du service
	var $duration_value;
	//! Unite de duree
	var $duration_unit;
	// Statut indique si le produit est en vente '1' ou non '0'
	var $status;
	// Status indicate whether the product is available for purchase '1' or not '0'
	var $status_buy;
	// Statut indique si le produit est un produit fini '1' ou une matiere premiere '0'
	var $finished;

	var $customcode;       // Custom code
    var $country_id;       // Country origin id
	var $country_code;     // Country origin code (US, FR, ...)

	// Hidden into combo boxes
	var $hidden;

	//! Unites de mesure
	var $weight;
	var $weight_units;
	var $length;
	var $length_units;
	var $surface;
	var $surface_units;
	var $volume;
	var $volume_units;

	var $accountancy_code_buy;
	var $accountancy_code_sell;

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

	//! Canevas a utiliser si le produit n'est pas un produit generique
	var $canvas;

	//! Id du fournisseur
	var $product_fourn_id;

	//! Product ID already linked to a reference supplier
	var $product_id_already_linked;

	var $nbphoto;

	//! Contains detail of stock of product into each warehouse
	var $stock_warehouse=array();


	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function Product($DB, $id=0)
	{
		global $langs;

		$this->db = $DB;
		$this->id = $id ;
		$this->status = 0;
		$this->status_buy = 0;
		$this->nbphoto = 0;
		$this->stock_reel = 0;
		$this->seuil_stock_alerte = 0;
		$this->canvas = '';

		if ($this->id > 0) $this->fetch($this->id);
	}

	/**
	 *    Check that ref and label are ok
	 *    @return     int         >1 if OK, <=0 if KO
	 */
	function check()
	{
		$this->ref = dol_sanitizeFileName(stripslashes($this->ref));

		$err = 0;
		if (dol_strlen(trim($this->ref)) == 0)
		$err++;

		if (dol_strlen(trim($this->libelle)) == 0)
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
	 *	Insert product into database
	 *	@param    user     		User making insert
	 *  @param	  notrigger		Disable triggers
	 *	@return   int     		Id of product/service if OK or number of error < 0
	 */
	function create($user,$notrigger=0)
	{
		global $conf, $langs;

        $error=0;

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		$this->price_ttc=price2num($this->price_ttc);
		$this->price=price2num($this->price);
		$this->price_min_ttc=price2num($this->price_min_ttc);
		$this->price_min=price2num($this->price_min);
		if (empty($this->tva_tx))    	$this->tva_tx = 0;
		if (empty($this->tva_npr))      $this->tva_npr = 0;
		//Local taxes
		if (empty($this->localtax1_tx)) $this->localtax1_tx = 0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx = 0;

		if (empty($this->price))     	$this->price = 0;
		if (empty($this->price_min)) 	$this->price_min = 0;
		if (empty($this->status))    	$this->status = 0;
		if (empty($this->status_buy))   $this->status_buy = 0;
		if (empty($this->finished))  	$this->finished = 0;
		if (empty($this->hidden))    	$this->hidden = 0;

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
		if (($this->price_min_ttc > 0) && ($this->price_base_type == 'TTC'))
		{
			$price_min_ttc = price2num($this->price_min_ttc,'MU');
			$price_min_ht = price2num($this->price_min_ttc / (1 + ($this->tva_tx / 100)),'MU');
		}
		if (($this->price_min > 0) && ($this->price_base_type != 'TTC'))
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
		if (empty($this->ref))
		{
			$this->error='ErrorWrongParameters';
		    return -2;
		}

		dol_syslog("Product::Create ref=".$this->ref." price=".$this->price." price_ttc=".$this->price_ttc." tva_tx=".$this->tva_tx." price_base_type=".$this->price_base_type." Category : ".$this->catid, LOG_DEBUG);


		$this->db->begin();

		$sql = "SELECT count(*) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE ref = '" .$this->ref."'";
		$sql.= " AND entity = ".$conf->entity;

		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj->nb == 0)
			{
				// Produit non deja existant
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product (";
				$sql.= "datec";
				$sql.= ", entity";
				$sql.= ", ref";
				$sql.= ", price_min";
				$sql.= ", price_min_ttc";
				$sql.= ", label";
				$sql.= ", fk_user_author";
				$sql.= ", fk_product_type";
				$sql.= ", price";
				$sql.= ", price_ttc";
				$sql.= ", price_base_type";
				$sql.= ", tobuy";
				$sql.= ", tosell";
				$sql.= ", canvas";
				$sql.= ", finished";
				$sql.= ", hidden";
				$sql.= ") VALUES (";
				$sql.= $this->db->idate(mktime());
				$sql.= ", ".$conf->entity;
				$sql.= ", '".$this->ref."'";
				$sql.= ", ".price2num($price_min_ht);
				$sql.= ", ".price2num($price_min_ttc);
				$sql.= ", ".($this->libelle?"'".$this->db->escape($this->libelle)."'":"null");
				$sql.= ", ".$user->id;
				$sql.= ", ".$this->type;
				$sql.= ", ".price2num($price_ht);
				$sql.= ", ".price2num($price_ttc);
				$sql.= ", '".$this->price_base_type."'";
				$sql.= ", ".$this->status;
				$sql.= ", ".$this->status_buy;
				$sql.= ", '".$this->canvas."'";
				$sql.= ", ".$this->finished;
				$sql.= ", ".$this->hidden;
				$sql.= ")";

				dol_syslog("Product::Create sql=".$sql);
				$result = $this->db->query($sql);
				if ( $result )
				{
					$id = $this->db->last_insert_id(MAIN_DB_PREFIX."product");

					if ($id > 0)
					{
						$this->id				= $id;
						$this->price			= $price_ht;
						$this->price_ttc		= $price_ttc;
						$this->price_min		= $price_min_ht;
						$this->price_min_ttc	= $price_min_ttc;

						$result = $this->_log_price($user);
						if ($result > 0)
						{
							if ( $this->update($id, $user) > 0)
							{
								if ($this->catid > 0)
								{
									require_once(DOL_DOCUMENT_ROOT ."/categories/class/categorie.class.php");
									$cat = new Categorie($this->db, $this->catid);
									$cat->add_type($this,"product");
								}
							}
							else
							{
							    $error++;
					            $this->error='ErrorFailedToUpdateRecord';
							}
						}
						else
						{
							$error++;
						    $this->error=$this->db->lasterror();
						}
					}
					else
					{
						$error++;
					    $this->error='ErrorFailedToGetInsertedId';
					}
				}
				else
				{
					$error++;
				    $this->error=$this->db->lasterror();
				}
			}
			else
			{
				// Product already exists with this ref
				$langs->trans("Error")." : ".$langs->trans("ErrorProductAlreadyExists",$this->ref);
			}
		}
		else
		{
			$error++;
		    $this->error=$this->db->lasterror();
		}

		if (! $error && ! $notrigger)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('PRODUCT_CREATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -$error;
		}
	}

	/**
	 *	Update a record into database
	 *	@param      id          Id of product
	 *	@param      user        Object user making update
	 *	@return     int         1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($id, $user)
	{
		global $langs, $conf;

		// Verification parametres
		if (! $this->libelle) $this->libelle = 'LIBELLE MANQUANT';

		// Clean parameters
		$this->ref = dol_string_nospecial(trim($this->ref));
		$this->libelle = trim($this->libelle);
		$this->description = trim($this->description);
		$this->note = trim($this->note);
		$this->weight = price2num($this->weight);
		$this->weight_units = trim($this->weight_units);
		$this->length = price2num($this->length);
		$this->length_units = trim($this->length_units);
		$this->surface = price2num($this->surface);
		$this->surface_units = trim($this->surface_units);
		$this->volume = price2num($this->volume);
		$this->volume_units = trim($this->volume_units);
		if (empty($this->tva_tx))    			$this->tva_tx = 0;
		if (empty($this->tva_npr))    			$this->tva_npr = 0;
		//Local taxes
		if (empty($this->localtax1_tx))			$this->localtax1_tx = 0;
		if (empty($this->localtax2_tx))			$this->localtax2_tx = 0;

		if (empty($this->finished))  			$this->finished = 0;
		if (empty($this->hidden))   			$this->hidden = 0;
        if (empty($this->country_id))           $this->country_id = 0;

		$this->accountancy_code_buy = trim($this->accountancy_code_buy);
		$this->accountancy_code_sell= trim($this->accountancy_code_sell);

		$sql = "UPDATE ".MAIN_DB_PREFIX."product";
		$sql.= " SET label = '" . $this->db->escape($this->libelle) ."'";
		$sql.= ",ref = '" . $this->ref ."'";
		$sql.= ",tva_tx = " . $this->tva_tx;
		$sql.= ",recuperableonly = " . $this->tva_npr;

		//Local taxes
		$sql.= ",localtax1_tx = " . $this->localtax1_tx;
		$sql.= ",localtax2_tx = " . $this->localtax2_tx;

		$sql.= ",tosell = " . $this->status;
		$sql.= ",tobuy = " . $this->status_buy;
		$sql.= ",finished = " . ($this->finished<0 ? "null" : $this->finished);
		$sql.= ",hidden = " . ($this->hidden<0 ? "null" : $this->hidden);
		$sql.= ",weight = " . ($this->weight!='' ? "'".$this->weight."'" : 'null');
		$sql.= ",weight_units = " . ($this->weight_units!='' ? "'".$this->weight_units."'": 'null');
		$sql.= ",length = " . ($this->length!='' ? "'".$this->length."'" : 'null');
		$sql.= ",length_units = " . ($this->length_units!='' ? "'".$this->length_units."'" : 'null');
		$sql.= ",surface = " . ($this->surface!='' ? "'".$this->surface."'" : 'null');
		$sql.= ",surface_units = " . ($this->surface_units!='' ? "'".$this->surface_units."'" : 'null');
		$sql.= ",volume = " . ($this->volume!='' ? "'".$this->volume."'" : 'null');
		$sql.= ",volume_units = " . ($this->volume_units!='' ? "'".$this->volume_units."'" : 'null');
		$sql.= ",seuil_stock_alerte = " . ((isset($this->seuil_stock_alerte) && $this->seuil_stock_alerte != '') ? "'".$this->seuil_stock_alerte."'" : "null");
		$sql.= ",description = '" . $this->db->escape($this->description) ."'";
        $sql.= ",customcode = '" .        $this->db->escape($this->customcode) ."'";
        $sql.= ",fk_country = " . ($this->country_id > 0 ? $this->country_id : 'null');
        $sql.= ",note = '" .        $this->db->escape($this->note) ."'";
		$sql.= ",duration = '" . $this->duration_value . $this->duration_unit ."'";
		$sql.= ",accountancy_code_buy = '" . $this->accountancy_code_buy."'";
		$sql.= ",accountancy_code_sell= '" . $this->accountancy_code_sell."'";
		$sql.= " WHERE rowid = " . $id;

		dol_syslog("Product::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $id;

			// Multilangs
			if($conf->global->MAIN_MULTILANGS)
			{
				if ( $this->setMultiLangs() < 0)
				{
					$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
					return -2;
				}
			}

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('PRODUCT_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

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
	 *  Verification de l'utilisation du produit en base
	 *  @param      id          id du produit
	 */
	function verif_prod_use($id)
	{
		$sqr = 0;

		$elements = array('propaldet','commandedet','facturedet','contratdet','product_fournisseur');

		foreach($elements as $table)
		{
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX.$table;
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
	 *  Delete a product from database (if not used)
	 *	@param      id          Product id
	 * 	@return		int			< 0 if KO, >= 0 if OK
	 */
	function delete($id)
	{
		global $conf,$user,$langs;

		$error=0;

		if ($user->rights->produit->supprimer)
		{
			$prod_use = $this->verif_prod_use($id);
			if ($prod_use == 0)
			{
				// TODO possibility to add external module constraint
				$elements = array('product_price','product_lang','categorie_product');

				foreach($elements as $table)
				{
					$sql = "DELETE FROM ".MAIN_DB_PREFIX.$table;
					$sql.= " WHERE fk_product = ".$id;
					$result = $this->db->query($sql);
				}

				$sqlz = "DELETE FROM ".MAIN_DB_PREFIX."product";
				$sqlz.= " WHERE rowid = ".$id;
				$resultz = $this->db->query($sqlz);

				if ( ! $resultz )
				{
					dol_syslog('Product::delete error sqlz='.$sqlz, LOG_ERR);
					$error++;
				}

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('PRODUCT_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if ($error)
				{
					return -$error;
				}
				else
				{
					return 0;
				}
			}
			else
			{
				$this->error .= "FailedToDeleteProduct. Already used.\n";
				return -1;
			}
		}
	}

	/**
	 *	update ou cree les traductions des infos produits
	 */
	function setMultiLangs()
	{
		global $langs;

		$langs_available = $langs->get_available_languages();
		$current_lang = $langs->getDefaultLang();

		foreach ($langs_available as $key => $value)
		{
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql.= " WHERE fk_product=".$this->id;
			$sql.= " AND lang='".$key."'";

			$result = $this->db->query($sql);

			if ($key == $current_lang)
			{
				if ($this->db->num_rows($result)) // si aucune ligne dans la base
				{
					$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_lang";
					$sql2.= " SET label='".$this->db->escape($this->libelle)."',";
					$sql2.= " description='".$this->db->escape($this->description)."',";
					$sql2.= " note='".$this->db->escape($this->note)."'";
					$sql2.= " WHERE fk_product=".$this->id." AND lang='".$key."'";
				}
				else
				{
					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_lang (fk_product, lang, label, description, note)";
					$sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->libelle);
					$sql2.= "','".$this->db->escape($this->description);
					$sql2.= "','".$this->db->escape($this->note)."')";
				}
				if (!$this->db->query($sql2)) return -1;
			}
			else
			{
				if ($this->db->num_rows($result)) // si aucune ligne dans la base
				{
					$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_lang";
					$sql2.= " SET label='".$this->db->escape($this->multilangs["$key"]["libelle"])."',";
					$sql2.= " description='".$this->db->escape($this->multilangs["$key"]["description"])."',";
					$sql2.= " note='".$this->db->escape($this->multilangs["$key"]["note"])."'";
					$sql2.= " WHERE fk_product=".$this->id." AND lang='".$key."'";
				}
				else
				{
					$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_lang (fk_product, lang, label, description, note)";
					$sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->multilangs["$key"]["libelle"]);
					$sql2.= "','".$this->db->escape($this->multilangs["$key"]["description"]);
					$sql2.= "','".$this->db->escape($this->multilangs["$key"]["note"])."')";
				}

				// on ne sauvegarde pas des champs vides
				if ( $this->multilangs["$key"]["libelle"] || $this->multilangs["$key"]["description"] || $this->multilangs["$key"]["note"] )
				if (!$this->db->query($sql2)) return -1;
			}
		}
		return 1;
	}


	/**
	 *	Load array this->multilangs
	 */
	function getMultiLangs()
	{
		global $langs;

		$current_lang = $langs->getDefaultLang();

		$sql = "SELECT lang, label, description, note";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
		$sql.= " WHERE fk_product=".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			while ( $obj = $this->db->fetch_object($result) )
			{
				//print 'lang='.$obj->lang.' current='.$current_lang.'<br>';
				if( $obj->lang == $current_lang ) // si on a les traduct. dans la langue courante on les charge en infos principales.
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
	 *  Ajoute un changement de prix en base dans l'historique des prix
	 *	@param  	user        Objet utilisateur qui modifie le prix
	 *	@return		int			<0 si KO, >0 si OK
	 */
	function _log_price($user,$level=0)
	{
		$now=dol_now();

		// Add new price
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price(price_level,date_price,fk_product,fk_user_author,price,price_ttc,price_base_type,tosell,tva_tx,recuperableonly,";
		$sql.= " localtax1_tx, localtax2_tx, price_min,price_min_ttc) ";
		$sql.= " VALUES(".($level?$level:1).", ".$this->db->idate($now).",".$this->id.",".$user->id.",".$this->price.",".$this->price_ttc.",'".$this->price_base_type."',".$this->status.",".$this->tva_tx.",".$this->tva_npr.",";
		$sql.= " ".$this->localtax1_tx.",".$this->localtax2_tx.",".$this->price_min.",".$this->price_min_ttc;
		$sql.= ")";

		dol_syslog("Product::_log_price sql=".$sql);
		$resql=$this->db->query($sql);
		if(! $resql)
		{
			$this->error=$this->db->error();
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			return 1;
		}
	}


	/**
	 *  Delete a price line
	 * 	@param		user	Object user
	 * 	@param		rowid	Line id to delete
	 * 	@return		int		<0 if KO, >0 if OK
	 */
	function log_price_delete($user,$rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price";
		$sql.= " WHERE rowid=".$rowid;

		dol_syslog("Product::log_price_delete sql=".$sql, LOG_DEBUG);
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
	 *	Lit le prix pratique par un fournisseur
	 *				On renseigne le couple prodfournprice/qty ou le triplet qty/product_id/fourn_ref)
	 *  @param     	prodfournprice      Id du tarif = rowid table product_fournisseur_price
	 *  @param     	qty                 Quantity asked
	 *	@param		product_id			Filter on a particular product id
	 * 	@param		fourn_ref			Filter on a supplier ref
	 *  @return    	int 				<-1 if KO, -1 if qty not enough, 0 si ok mais rien trouve, id_product si ok et trouve
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

		dol_syslog("Product::get_buyprice sql=".$sql);
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

				dol_syslog("Product::get_buyprice sql=".$sql);
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
						return -1;	// Ce produit existe chez ce fournisseur mais qte insuffisante
					}
				}
				else
				{
					$this->error=$this->db->error();
					dol_syslog("Product:get_buyprice ".$this->error, LOG_ERR);
					return -3;
				}
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Product:get_buyprice ".$this->error, LOG_ERR);
			return -2;
		}
		return $result;
	}


	/**
	 *	Modify price of a product/Service
	 *	@param  	id          	Id of product/service to change
	 *	@param  	newprice		New price
	 *	@param  	newpricebase	HT or TTC
	 *	@param  	user        	Object user that make change
	 *	@param  	newvat			New VAT Rate
	 *  @param		newminprice		New price min
	 *  @param		level			0=standard, >0 = level if multilevel prices
	 *  @param     newnpr          0=Standard vat rate, 1=Special vat rate for French NPR VAT
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function update_price($id, $newprice, $newpricebase, $user, $newvat='',$newminprice='', $level=0, $newnpr=0)
	{
		global $conf,$langs;

		dol_syslog("Product::update_price id=".$id." newprice=".$newprice." newpricebase=".$newpricebase." newminprice=".$newminprice." level=".$level." npr=".$newnpr);

		// Clean parameters
		if (empty($this->tva_tx))  $this->tva_tx=0;
        if (empty($newnpr)) $newnpr=0;

		// Check parameters
		if ($newvat == '') $newvat=$this->tva_tx;

		if ($newprice!='' || $newprice==0)
		{
			if ($newpricebase == 'TTC')
			{
				$price_ttc = price2num($newprice,'MU');
				$price = price2num($newprice) / (1 + ($newvat / 100));
				$price = price2num($price,'MU');

				if ($newminprice!='' || $newminprice==0)
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

				if ($newminprice!='' || $newminprice==0)
				{
					$price_min = price2num($newminprice,'MU');
					$price_min_ttc = price2num($newminprice) * (1 + ($newvat / 100));
					$price_min_ttc = price2num($price_min_ttc,'MU');
					//print 'X'.$newminprice.'-'.$price_min;
				}
				else
				{
					$price_min=0;
					$price_min_ttc=0;
				}
			}
			//print 'x'.$id.'-'.$newprice.'-'.$newpricebase.'-'.$price.'-'.$price_ttc.'-'.$price_min.'-'.$price_min_ttc;

			//Local taxes
			$localtax1=get_localtax($newvat,1);
			$localtax2=get_localtax($newvat,2);
			if (empty($localtax1)) $localtax1=0;	// If = '' then = 0
			if (empty($localtax2)) $localtax2=0;	// If = '' then = 0

			// Ne pas mettre de quote sur les numeriques decimaux.
			// Ceci provoque des stockages avec arrondis en base au lieu des valeurs exactes.
			$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
			$sql.= " price_base_type='".$newpricebase."',";
			$sql.= " price=".$price.",";
			$sql.= " price_ttc=".$price_ttc.",";
			$sql.= " price_min=".$price_min.",";
			$sql.= " price_min_ttc=".$price_min_ttc.",";
			$sql.= " localtax1_tx=".($localtax1>=0?$localtax1:'NULL').",";
			$sql.= " localtax2_tx=".($localtax2>=0?$localtax2:'NULL').",";
			$sql.= " tva_tx='".price2num($newvat)."',";
            $sql.= " recuperableonly='".$newnpr."'";
			$sql.= " WHERE rowid = ".$id;

			dol_syslog("Product::update_price sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->price = $price;
				$this->price_ttc = $price_ttc;
				$this->price_min = $price_min;
				$this->price_min_ttc = $price_min_ttc;
				$this->price_base_type = $newpricebase;
				$this->tva_tx = $newvat;
				$this->tva_npr = $newnpr;
				//Local taxes
				$this->localtax1_tx = $localtax1;
				$this->localtax2_tx = $localtax2;

				$this->_log_price($user,$level);
			}
			else
			{
				dol_print_error($this->db);
			}
		}

		return 1;
	}


	/**
	 *  Load a product in memory from database
	 *  @param      id      Id of product/service to load
	 *  @param      ref     Ref of product/service to load
	 *  @return     int     <0 if KO, >0 if OK
	 */
	function fetch($id='',$ref='')
	{
	    include_once(DOL_DOCUMENT_ROOT.'/lib/company.lib.php');

		global $langs;
		global $conf;

		dol_syslog("Product::fetch id=$id ref=$ref");

		// Check parameters
		if (! $id && ! $ref)
		{
			$this->error=$langs->trans('ErrorWrongParameters');
			dol_print_error("Product::fetch ".$this->error, LOG_ERR);
			return -1;
		}

		$sql = "SELECT rowid, ref, label, description, note, customcode, fk_country, price, price_ttc,";
		$sql.= " price_min, price_min_ttc, price_base_type, tva_tx, recuperableonly as tva_npr, localtax1_tx, localtax2_tx, tosell,";
		$sql.= " tobuy, fk_product_type, duration, seuil_stock_alerte, canvas,";
		$sql.= " weight, weight_units, length, length_units, surface, surface_units, volume, volume_units, barcode, fk_barcode_type, finished, hidden,";
		$sql.= " accountancy_code_buy, accountancy_code_sell, stock, pmp,";
		$sql.= " import_key";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		if ($id) $sql.= " WHERE rowid = '".$id."'";
		else if ($ref) $sql.= " WHERE ref = '".$this->db->escape($ref)."'";

		dol_syslog("Product::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$object = $this->db->fetch_object($resql);

				$this->id					= $object->rowid;
				$this->ref					= $object->ref;
				$this->libelle				= $object->label;		// TODO deprecated
				$this->label				= $object->label;
				$this->description			= $object->description;
				$this->note					= $object->note;
	            $this->customcode			= $object->customcode;
	            $this->country_id			= $object->fk_country;
	            $this->country_code			= getCountry($this->country_id,2,$this->db);
	            $this->price				= $object->price;
				$this->price_ttc			= $object->price_ttc;
				$this->price_min			= $object->price_min;
				$this->price_min_ttc		= $object->price_min_ttc;
				$this->price_base_type		= $object->price_base_type;
				$this->tva_tx				= $object->tva_tx;
				//! French VAT NPR
				$this->tva_npr				= $object->tva_npr;
				//! Spanish local taxes
				$this->localtax1_tx			= $object->localtax1_tx;
				$this->localtax2_tx			= $object->localtax2_tx;

				$this->type					= $object->fk_product_type;
				$this->status				= $object->tosell;
				$this->status_buy			= $object->tobuy;
				$this->finished				= $object->finished;
				$this->hidden				= $object->hidden;
				$this->duration				= $object->duration;
				$this->duration_value		= substr($object->duration,0,dol_strlen($object->duration)-1);
				$this->duration_unit		= substr($object->duration,-1);
				$this->seuil_stock_alerte	= $object->seuil_stock_alerte;
				$this->canvas				= $object->canvas;
				$this->weight				= $object->weight;
				$this->weight_units			= $object->weight_units;
				$this->length				= $object->length;
				$this->length_units			= $object->length_units;
				$this->surface				= $object->surface;
				$this->surface_units		= $object->surface_units;
				$this->volume				= $object->volume;
				$this->volume_units			= $object->volume_units;
				$this->barcode				= $object->barcode;
				$this->barcode_type			= $object->fk_barcode_type;

				$this->accountancy_code_buy = $object->accountancy_code_buy;
				$this->accountancy_code_sell= $object->accountancy_code_sell;

				$this->stock_reel         = $object->stock;
				$this->pmp                = $object->pmp;

				$this->import_key         = $object->import_key;

				$this->db->free($resql);

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
						$resql = $this->db->query($sql);
						if ($resql)
						{
							$result = $this->db->fetch_array($resql);
							$this->barcode_type_code = $result["code"];
							$this->barcode_type_label = $result["libelle"];
							$this->barcode_type_coder = $result["coder"];
						}
						else
						{
							dol_print_error($this->db);
							return -1;
						}
					}
				}

				// Load multiprices array
				if ($conf->global->PRODUIT_MULTIPRICES)
				{
					for ($i=1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
					{
						$sql = "SELECT price, price_ttc, price_min, price_min_ttc,";
						$sql.= " price_base_type, tva_tx, tosell";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_price";
						$sql.= " where price_level=".$i." and";
						$sql.= " fk_product = '".$this->id."'";
						$sql.= " ORDER BY date_price DESC";
						$sql.= " LIMIT 1";
						$resql = $this->db->query($sql) ;
						if ($resql)
						{
							$result = $this->db->fetch_array($resql);

							$this->multiprices[$i]=$result["price"];
							$this->multiprices_ttc[$i]=$result["price_ttc"];
							$this->multiprices_min[$i]=$result["price_min"];
							$this->multiprices_min_ttc[$i]=$result["price_min_ttc"];
							$this->multiprices_base_type[$i]=$result["price_base_type"];
							$this->multiprices_tva_tx[$i]=$result["tva_tx"];
						}
						else
						{
							dol_print_error($this->db);
							return -1;
						}
					}
				}

				$res=$this->load_stock();

				return $res;
			}
			else
			{
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
	 *  Charge tableau des stats propale pour le produit/service
	 *  @param    socid       Id societe
	 *  @return   array       Tableau des stats
	 */
	function load_stats_propale($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT p.fk_soc) as nb_customers, COUNT(DISTINCT p.rowid) as nb,";
		$sql.= " COUNT(pd.rowid) as nb_rows, SUM(pd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd";
		$sql.= ", ".MAIN_DB_PREFIX."propal as p";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE p.rowid = pd.fk_propal";
		$sql.= " AND p.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND pd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND pr.fk_statut != 0";
		if ($socid > 0)	$sql.= " AND p.fk_soc = ".$socid;

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
	 *  Charge tableau des stats commande client pour le produit/service
	 *  @param    socid       	Id societe pour filtrer sur une societe
	 *  @param    filtrestatut  Id statut pour filtrer sur un statut
	 *  @return   array       	Tableau des stats
	 */
	function load_stats_commande($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."commande as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;
		if ($filtrestatut <> '') $sql.= " AND c.fk_statut in (".$filtrestatut.")";

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
	 *  Charge tableau des stats commande fournisseur pour le produit/service
	 *  @param    socid       	Id societe pour filtrer sur une societe
	 *  @param    filtrestatut  Id des statuts pour filtrer sur des statuts
	 *  @return   array       	Tableau des stats
	 */
	function load_stats_commande_fournisseur($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_suppliers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_commande";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0) $sql.= " AND c.fk_soc = ".$socid;
		if ($filtrestatut != '') $sql.= " AND c.fk_statut in (".$filtrestatut.")"; // Peut valoir 0

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
	 *  Charge tableau des stats expedition client pour le produit/service
	 *  @param    socid       	Id societe pour filtrer sur une societe
	 *  @param    filtrestatut  Id statut pour filtrer sur un statut
	 *  @return   array       	Tableau des stats
	 */
	function load_stats_sending($socid=0,$filtrestatut='')
	{
		global $conf,$user;

		$sql = "SELECT COUNT(DISTINCT e.fk_soc) as nb_customers, COUNT(DISTINCT e.rowid) as nb,";
		$sql.= " COUNT(ed.rowid) as nb_rows, SUM(ed.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
		$sql.= ", ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."expedition as e";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE e.rowid = ed.fk_expedition";
		$sql.= " AND e.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND ed.fk_origin_line = cd.rowid";
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND e.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND e.fk_soc = ".$socid;
		if ($filtrestatut <> '') $sql.= " AND e.fk_statut in (".$filtrestatut.")";

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
	 *  Charge tableau des stats contrat pour le produit/service
	 *  @param    socid       Id societe
	 *  @return   array       Tableau des stats
	 */
	function load_stats_contrat($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
		$sql.= " COUNT(cd.rowid) as nb_rows, SUM(cd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = cd.fk_contrat";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND c.statut != 0";
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;

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
	 *  Charge tableau des stats facture pour le produit/service
	 *  @param    socid       Id societe
	 *  @return   array       Tableau des stats
	 */
	function load_stats_facture($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_customers, COUNT(DISTINCT f.rowid) as nb,";
		$sql.= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
		$sql.= ", ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = fd.fk_facture";
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND fd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND f.fk_statut != 0";
		if ($socid > 0)	$sql .= " AND f.fk_soc = ".$socid;

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
	 *  Charge tableau des stats facture pour le produit/service
	 *  @param    socid       Id societe
	 *  @return   array       Tableau des stats
	 */
	function load_stats_facture_fournisseur($socid=0)
	{
		global $conf;
		global $user;

		$sql = "SELECT COUNT(DISTINCT f.fk_soc) as nb_suppliers, COUNT(DISTINCT f.rowid) as nb,";
		$sql.= " COUNT(fd.rowid) as nb_rows, SUM(fd.qty) as qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as fd";
		$sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = fd.fk_facture_fourn";
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND fd.fk_product = ".$this->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		//$sql.= " AND f.fk_statut != 0";
		if ($socid > 0)	$sql .= " AND f.fk_soc = ".$socid;

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
	 *  Return an array formated for showing graphs
	 *  @param		sql         Request to execute
	 *  @param		mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 *  @return   	array       <0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function _get_stats($sql,$mode)
	{
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$arr = $this->db->fetch_array($resql);
				if ($mode == 'byunit')   $tab[$arr[1]] = $arr[0];	// 1st field
				if ($mode == 'bynumber') $tab[$arr[1]] = $arr[2];	// 3rd field
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
			$idx=ucfirst(substr(dol_print_date(dol_mktime(12,0,0,$month,1,$year),"%b"),0,3) );
			$monthnum=sprintf("%02s",$month);

			$result[$j] = array($idx,isset($tab[$year.$month])?$tab[$year.$month]:0);
			//            $result[$j] = array($monthnum,isset($tab[$year.$month])?$tab[$year.$month]:0);

			$month = "0".($month - 1);
			if (dol_strlen($month) == 3)
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
	 *  Return nb of units or customers invoices in which product is included
	 *  @param  	socid       Limit count on a particular third party id
	 *  @param		mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       <0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_vente($socid=0,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT f.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as d, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = d.fk_facture";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND f.fk_soc = $socid";
		$sql.= " GROUP BY date_format(f.datef,'%Y%m')";
		$sql.= " ORDER BY date_format(f.datef,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}


	/**
	 *  Return nb of units or supplier invoices in which product is included
	 *  @param  	socid       Limit count on a particular third party id
	 * 	@param		mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       <0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_achat($socid=0,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(f.datef, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT f.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as d, ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.rowid = d.fk_facture_fourn";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND f.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND f.fk_soc = $socid";
		$sql.= " GROUP BY date_format(f.datef,'%Y%m')";
		$sql.= " ORDER BY date_format(f.datef,'%Y%m') DESC";

		$resarray=$this->_get_stats($sql,$mode);
		return $resarray;
	}

	/**
	 *  Return nb of units or proposals in which product is included
	 *  @param  	socid       Limit count on a particular third party id
	 * 	@param		mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       <0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_propal($socid=0,$mode)
	{
		global $conf;
		global $user;

		$sql = "SELECT sum(d.qty), date_format(p.datep, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT p.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as d, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE p.rowid = d.fk_propal";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND p.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND p.fk_soc = ".$socid;
		$sql.= " GROUP BY date_format(p.datep,'%Y%m')";
		$sql.= " ORDER BY date_format(p.datep,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}

	/**
	 *  Return nb of units or orders in which product is included
	 *  @param  	socid       Limit count on a particular third party id
	 *  @param		mode		'byunit'=number of unit, 'bynumber'=nb of entities
	 * 	@return   	array       <0 if KO, result[month]=array(valuex,valuey) where month is 0 to 11
	 */
	function get_nb_order($socid=0,$mode)
	{
		global $conf, $user;

		$sql = "SELECT sum(d.qty), date_format(c.date_commande, '%Y%m')";
		if ($mode == 'bynumber') $sql.= ", count(DISTINCT c.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as d, ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.rowid = d.fk_commande";
		$sql.= " AND d.fk_product =".$this->id;
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid > 0)	$sql.= " AND c.fk_soc = ".$socid;
		$sql.= " GROUP BY date_format(c.date_commande,'%Y%m')";
		$sql.= " ORDER BY date_format(c.date_commande,'%Y%m') DESC";

		return $this->_get_stats($sql,$mode);
	}

	/**
	 *  Lie un produit associe au produit/service
	 *  @param      id_pere    Id du produit auquel sera lie le produit a lier
	 *  @param      id_fils    Id du produit a lier
	 *  @return     int        < 0 if KO, > 0 if OK
	 */
	function add_sousproduit($id_pere, $id_fils,$qty)
	{
		$sql = 'delete from '.MAIN_DB_PREFIX.'product_association';
		$sql .= ' WHERE fk_product_pere  = "'.$id_pere.'" and fk_product_fils = "'.$id_fils.'"';
		if (! $this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			$sql = 'select fk_product_pere from '.MAIN_DB_PREFIX.'product_association';
			$sql .= ' WHERE fk_product_pere  = "'.$id_fils.'" and fk_product_fils = "'.$id_pere.'"';
			if (! $this->db->query($sql))
			{
				dol_print_error($this->db);
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
							dol_print_error($this->db);
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
	 *  Retire le lien entre un sousproduit et un produit/service
	 *  @param      fk_parent		Id du produit auquel ne sera plus lie le produit lie
	 *  @param      fk_child		Id du produit a ne plus lie
	 *  @return     int			    < 0 si erreur, > 0 si ok
	 */
	function del_sousproduit($fk_parent, $fk_child)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere  = '".$fk_parent."'";
		$sql.= " AND fk_product_fils = '".$fk_child."'";

		if (! $this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		return 1;
	}

	/**
	 *  Verifie si c'est un sous-produit
	 *  @param      fk_parent		Id du produit auquel le produit est lie
	 *  @param      fk_child		Id du produit lie
	 *  @return     int			    < 0 si erreur, > 0 si ok
	 */
	function is_sousproduit($fk_parent, $fk_child)
	{
		$sql = "SELECT fk_product_pere, qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere  = '".$fk_parent."'";
		$sql.= " AND fk_product_fils = '".$fk_child."'";

		$result = $this->db->query($sql);
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
			{
				return false;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Remplit le tableau des sous-produits
	 *  @return     int        < 0 si erreur, > 0 si ok
	 *  @remark		Not used. Used by module droitpret only.
	 */
	/* Deprecated. Is not used
	function load_subproduct()
	{
		$this->subproducts_id = array();
		$i = 0;

		$sql = "SELECT fk_product_subproduct";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_subproduct";
		$sql.= " WHERE fk_product = ".$this->id;

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
    */

	/**
	 *  Lie un sous produit au produit/service
	 *  @param      id_sub     Id du produit a lier
	 *  @return     int        < 0 si erreur, > 0 si ok
	 *  @remark		Not used. Used by module droitpret only.
	 */
    /* Deprecated. Is not used
	function add_subproduct($id_sub)
	{
		if ($id_sub)
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_subproduct(fk_product,fk_product_subproduct)';
			$sql .= ' VALUES ("'.$this->id.'","'.$id_sub.'")';
			if (! $this->db->query($sql))
			{
				dol_print_error($this->db);
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
    */

	/**
	 *  Add a supplier reference for the product
	 *  @param      user        User that make link
	 *  @param      id_fourn    Supplier id
	 *  @param      ref_fourn   Supplier ref
	 *  @return     int         < 0 if KO, 0 if link already exists for this product, > 0 if OK
	 */
	function add_fournisseur($user, $id_fourn, $ref_fourn)
	{
		global $conf;

		$sql = "SELECT rowid, fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE fk_soc = ".$id_fourn;
		$sql.= " AND ref_fourn = '".$ref_fourn."'";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Product::add_fournisseur sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);

			// The reference supplier does not exist, we create it for this product.
			if (! $obj)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur (";
				$sql.= "datec";
				$sql.= ", entity";
				$sql.= ", fk_product";
				$sql.= ", fk_soc";
				$sql.= ", ref_fourn";
				$sql.= ", fk_user_author";
				$sql.= ") VALUES (";
				$sql.= $this->db->idate(mktime());
				$sql.= ", ".$conf->entity;
				$sql.= ", ".$this->id;
				$sql.= ", ".$id_fourn;
				$sql.= ", '".$ref_fourn."'";
				$sql.= ", ".$user->id;
				$sql.= ")";

				dol_syslog("Product::add_fournisseur sql=".$sql);
				if ($this->db->query($sql))
				{
					$this->product_fourn_id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur");
					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					dol_syslog("Product::add_fournisseur ".$this->error, LOG_ERR);
					return -1;
				}
			}
			// If the supplier ref already exists for this product
			else if ($obj->fk_product == $this->id)
			{
				$this->product_fourn_id = $obj->rowid;

				return 0;
			}
			// If the supplier ref already exists but for another product
			else
			{
				$this->product_id_already_linked = $obj->fk_product;

				return -3;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -2;
		}
	}


	/**
	 *  Renvoie la liste des fournisseurs du produit/service
	 *  @return 	array		Tableau des id de fournisseur
	 */
	function list_suppliers()
	{
		global $conf;

		$list = array();

		$sql = "SELECT fk_soc";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur as p";
		$sql.= " WHERE p.fk_product = ".$this->id;
		$sql.= " AND p.entity = ".$conf->entity;

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
	 *  Saisie une commande fournisseur
	 *	@param		user		Objet user de celui qui demande
	 *	@return		int			<0 si ko, >0 si ok
	 */
	function fastappro($user)
	{
		include_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php";

		$list = $this->list_suppliers();
		if (sizeof($list) > 0)
		{
			dol_syslog("Product::fastappro");
			$fournisseur = new Fournisseur($this->db);
			$fournisseur->fetch($this->fourn_appro_open);

			$fournisseur->ProductCommande($user, $this->id);
		}
		return 1;
	}

	/**
	 *  Recopie les prix d'un produit/service sur un autre
	 *  @param    fromId      Id produit source
	 *  @param    toId        Id produit cible
	 *  @return   int         < 0 si erreur, > 0 si ok
	 */
	function clone_price($fromId, $toId)
	{
		$this->db->begin();

		// les prix
		$sql = "INSERT ".MAIN_DB_PREFIX."product_price ("
		. " fk_product, date_price, price, tva_tx, localtax1_tx, localtax2_tx, fk_user_author, tosell )"
		. " SELECT ".$toId . ", date_price, price, tva_tx, localtax1_tx, localtax2_tx, fk_user_author, tosell "
		. " FROM ".MAIN_DB_PREFIX."product_price "
		. " WHERE fk_product = ". $fromId;

		if ( ! $this->db->query($sql ) )
		{
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();
		return 1;
	}

	/**
	 *  Recopie les fournisseurs et prix fournisseurs d'un produit/service sur un autre
	 *  @param    fromId      Id produit source
	 *  @param    toId        Id produit cible
	 *  @return   int         < 0 si erreur, > 0 si ok
	 */
	function clone_fournisseurs($fromId, $toId)
	{
		$this->db->begin();

		// les fournisseurs
		$sql = "INSERT ".MAIN_DB_PREFIX."product_fournisseur ("
		. " datec, fk_product, fk_soc, ref_fourn, fk_user_author )"
		. " SELECT '".$this->db->idate(mktime())."', ".$toId.", fk_soc, ref_fourn, fk_user_author"
		. " FROM ".MAIN_DB_PREFIX."product_fournisseur"
		. " WHERE fk_product = ".$fromId;

		if ( ! $this->db->query($sql ) )
		{
			$this->db->rollback();
			return -1;
		}

		// les prix de fournisseurs.
		$sql = "INSERT ".MAIN_DB_PREFIX."product_fournisseur_price ("
		. " datec, fk_product, fk_soc, price, quantity, fk_user )"
		. " SELECT '".$this->db->idate(mktime())."', ".$toId. ", fk_soc, price, quantity, fk_user"
		. " FROM ".MAIN_DB_PREFIX."product_fournisseur_price"
		. " WHERE fk_product = ".$fromId;

		if ( ! $this->db->query($sql ) )
		{
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();
		return 1;
	}

	/**
	 *  Fonction recursive uniquement utilisee par get_arbo_each_prod, recompose l'arborescence des sousproduits
	 * 	@remarks	Define value of this->res
	 *	@param		multiply	Because each sublevel must be multiplicated by parent nb
	 *  @return 	void
	 */
	function fetch_prod_arbo($prod, $compl_path="", $multiply=1, $level=1)
	{
		global $conf,$langs;

		$product = new Product($this->db);
		foreach($prod as $nom_pere => $desc_pere)
		{
			if (is_array($desc_pere))	// If this parent desc is an array, this is an array of childs
			{
				if($multiply)
				{
					//print "XXX ".$desc_pere[1]." multiply=".$multiply;
					$img="";
					$this->fetch($desc_pere[0]);
					$this->load_stock();
					if ($this->stock_warehouse[1]->real < $this->seuil_stock_alerte)
					{
						$img=img_warning($langs->trans("StockTooLow"));
					}
					$this->res[]= array(
/*								"<tr><td>&nbsp; &nbsp; &nbsp; ->
                                <a href=\"".DOL_URL_ROOT."/product/fiche.php?id=".$desc_pere[0]."\">".$compl_path.stripslashes($nom_pere)."
                                </a> (".$desc_pere[1].")</td><td align=\"center\"> ".($desc_pere[1]*$multiply)."</td><td>&nbsp</td><td>&nbsp</td>
                                <td align=\"center\">".$this->stock_entrepot[1]." ".$img."</td></tr>",
								$desc_pere[0],							// Id product
*/								'id'=>$desc_pere[0],					// Id product
								'nb'=>$desc_pere[1],					// Nb of units that compose parent product
								'nb_total'=>$desc_pere[1]*$multiply,	// Nb of units for all nb of product
								'stock'=>$this->stock_warehouse[1]->real,		// Stock
								'stock_alert'=>$this->seuil_stock_alerte,	// Stock alert
								'fullpath' => $compl_path.$nom_pere,	// Label
								'type'=>$desc_pere[2]					// Nb of units that compose parent product
								);
				}
				else
				{
					$this->fetch($desc_pere[0]);
					$this->load_stock();
					$this->res[]= array(
/*					$compl_path.$nom_pere." (".$desc_pere[1].")",
					$desc_pere[0],							// Id product
*/					'id'=>$desc_pere[0],					// Id product
					'nb'=>$desc_pere[1],					// Nb of units that compose parent product
					'nb_total'=>$desc_pere[1],				// Nb of units for all nb of product
					'stock'=>$this->stock_warehouse[1]->real,		// Stock
					'stock_alert'=>$this->seuil_stock_alerte,	// Stock alert
					'fullpath' => $compl_path.$nom_pere,	// Label
					'type'=>$desc_pere[2]					// Nb of units that compose parent product
					);
				}
			}
			else if($nom_pere != "0" && $nom_pere != "1")
			{
				$this->res[]= array($compl_path.$nom_pere,$desc_pere);
			}

			// Recursive call
			if (is_array($desc_pere))
			{
				$this ->fetch_prod_arbo($desc_pere, $nom_pere." -> ", $desc_pere[1]*$multiply, $level+1);
			}
		}
	}

	/**
	 *  fonction recursive uniquement utilisee par get_each_prod, ajoute chaque sousproduits dans le tableau res
	 *  @return void
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
	 *  reconstruit l'arborescence des categories sous la forme d'un tableau
	 *  @return 	array 	$this->res
	 */
	function get_arbo_each_prod($multiply=1)
	{
		$this->res = array();
		if (is_array($this -> sousprods))
		{
			foreach($this -> sousprods as $nom_pere => $desc_pere)
			{
				if (is_array($desc_pere)) $this->fetch_prod_arbo($desc_pere,"",$multiply);
			}
			//			dol_sort($this->res,);
		}
		return $this->res;
	}

	/**
	 *  renvoie tous les sousproduits dans le tableau res, chaque ligne de res contient : id -> qty
	 *  @return array $this->res
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
	 *  Return all parent products fo current product
	 *  @return 	array prod
	 */
	function getParent()
	{

		$sql = "SELECT p.label as label,p.rowid,pa.fk_product_pere as id,p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association as pa,";
		$sql.= " ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.rowid = pa.fk_product_pere";
		$sql.= " AND p.rowid = ".$this->id;

		$res = $this->db->query($sql);
		if ($res)
		{
			$prods = array ();
			while ($record = $this->db->fetch_array ($res))
			{
				$prods[$this->db->escape($record['label'])] = array(0=>$record['id']);
			}
			return $prods;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 *  Return childs of prodcut with if fk_parent
	 * 	@param		fk_parent	Id of product to search childs of
	 *  @return     array       Prod
	 */
	function getChildsArbo($fk_parent)
	{
		$sql = "SELECT p.rowid, p.label as label, pa.qty as qty, pa.fk_product_fils as id, p.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ", ".MAIN_DB_PREFIX."product_association as pa";
		$sql.= " WHERE p.rowid = pa.fk_product_fils";
		$sql.= " AND pa.fk_product_pere = ".$fk_parent;

		$res  = $this->db->query($sql);
		if ($res)
		{
			$prods = array();
			while ($rec = $this->db->fetch_array($res))
			{
				//$prods[$this->db->escape($rec['label'])]= array(0=>$rec['id'],1=>$rec['qty'],2=>$rec['fk_product_type']);
				$prods[$this->db->escape($rec['label'])]= array(0=>$rec['id'],1=>$rec['qty']);
				$listofchilds=$this->getChildsArbo($rec['id']);
				foreach($listofchilds as $keyChild => $valueChild)
				{
					$prods[$this->db->escape($rec['label'])][$keyChild] = $valueChild;
				}
			}

			return $prods;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * 	Return tree of all subproducts for product. Tree contains id, name and quantity.
	 * 	@remarks	Set this->sousprods
	 *  @return    	void
	 */
	function get_sousproduits_arbo()
	{
		$parent = $this->getParent();
		foreach($parent as $key => $value)
		{
			foreach($this->getChildsArbo($value[0]) as $keyChild => $valueChild)
			{
				$parent[$key][$keyChild] = $valueChild;
			}
		}
		foreach($parent as $key => $value)
		{
			$this->sousprods[$key] = $value;
		}
	}

	/**
	 *    	Return clicable link of object (with eventually picto)
	 *		@param		withpicto		Add picto into link
	 *		@param		option			Where point the link
	 *		@param		maxlength		Maxlength of ref
	 *		@return		string			String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		if ($option == 'supplier')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$this->id.'">';
			$lienfin='</a>';
		}
        else if ($option == 'stock')
        {
            $lien = '<a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$this->id.'">';
            $lienfin='</a>';
        }
		else
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$this->id.'">';
			$lienfin='</a>';
		}
		$newref=$this->ref;
		if ($maxlength) $newref=dol_trunc($newref,$maxlength,'middle');

		if ($withpicto) {
			if ($this->type == 0) $result.=($lien.img_object($langs->trans("ShowProduct").' '.$this->ref,'product').$lienfin.' ');
			if ($this->type == 1) $result.=($lien.img_object($langs->trans("ShowService").' '.$this->ref,'service').$lienfin.' ');
		}
		$result.=$lien.$newref.$lienfin;
		return $result;
	}

	/**
	 *    	Return label of status of object
	 *    	@param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *      @param      type        0=Shell, 1=Buy
	 *    	@return     string      Label of status
	 */
	function getLibStatut($mode=0, $type=0)
	{
		if($type==0)
			return $this->LibStatut($this->status,$mode,$type);
		else
			return $this->LibStatut($this->status_buy,$mode,$type);
	}

	/**
	 *     Return label of a given status
	 *     @param      status      Statut
	 *     @param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *     @param      type        0=Status "to sell", 1=Status "to buy"
	 *     @return     string      Label of status
	 */
	function LibStatut($status,$mode=0,$type=0)
	{
		global $langs;
		$langs->load('products');

		if ($mode == 0)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 1)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort'));
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto(($type==0 ? $langs->trans('ProductStatusNotOnSell') : $langs->trans('ProductStatusNotOnBuy')),'statut5');
			if ($status == 1) return img_picto(($type==0 ? $langs->trans('ProductStatusOnSell') : $langs->trans('ProductStatusOnBuy')),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ProductStatusNotOnSell'),'statut5').' '.($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy'));
			if ($status == 1) return img_picto($langs->trans('ProductStatusOnSell'),'statut4').' '.($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy'));
		}
		if ($mode == 5)
		{
			if ($status == 0) return ($type==0 ? $langs->trans('ProductStatusNotOnSellShort'):$langs->trans('ProductStatusNotOnBuyShort')).' '.img_picto(($type==0 ? $langs->trans('ProductStatusNotOnSell'):$langs->trans('ProductStatusNotOnBuy')),'statut5');
			if ($status == 1) return ($type==0 ? $langs->trans('ProductStatusOnSellShort'):$langs->trans('ProductStatusOnBuyShort')).' '.img_picto(($type==0 ? $langs->trans('ProductStatusOnSell'):$langs->trans('ProductStatusOnBuy')),'statut4');
		}
		return $langs->trans('Unknown');
	}


	/**
	 *  Retourne le libelle du finished du produit
	 *  @return     string		Libelle
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
	 *  Adjust stock in a warehouse for product
	 *  @param  	user            user asking change
	 *  @param  	id_entrepot     id of warehouse
	 *  @param  	nbpiece         nb of units
	 *  @param  	movement       	0 = add, 1 = remove
	 * 	@param		label			Label of stock movement
	 * 	@param		price			Price to use for stock eval
	 * 	@return     int     		<0 if KO, >0 if OK
	 */
	function correct_stock($user, $id_entrepot, $nbpiece, $movement, $label='', $price=0)
	{
		if ($id_entrepot)
		{
			$this->db->begin();

			require_once(DOL_DOCUMENT_ROOT ."/product/stock/class/mouvementstock.class.php");

			$op[0] = "+".trim($nbpiece);
			$op[1] = "-".trim($nbpiece);

			$movementstock=new MouvementStock($this->db);
			$result=$movementstock->_create($user,$this->id,$id_entrepot,$op[$movement],$movement,$price,$label);

			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				dol_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *    Load information about stock of a product into stock_warehouse[] and stock_reel
	 *    @return     int             < 0 si erreur, > 0 si ok
	 */
	function load_stock()
	{
		$this->stock_reel = 0;

		$sql = "SELECT reel, fk_entrepot, pmp";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock";
		$sql.= " WHERE fk_product = '".$this->id."'";

		dol_syslog("Product::load_stock sql=".$sql);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i=0;
			if ($num > 0)
			{
				while ($i < $num)
				{
					$row = $this->db->fetch_object($result);
					$this->stock_warehouse[$row->fk_entrepot]->real = $row->reel;
					$this->stock_warehouse[$row->fk_entrepot]->pmp = $row->pmp;
					$this->stock_reel+=$row->reel;
					$i++;
				}
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
	 *  Deplace fichier uploade sous le nom $files dans le repertoire sdir
	 *  @param      sdir        Repertoire destination finale
	 *  @param      $file       Nom du fichier uploade
	 *  @param      maxWidth    Largeur maximum que dois faire la miniature (160 par defaut)
	 *  @param      maxHeight   Hauteur maximum que dois faire la miniature (120 par defaut)
	 */
	function add_photo($sdir, $file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/photos";

		create_exdir($dir);

		$dir_osencoded=$dir;
		if (is_dir($dir_osencoded))
		{
			$originImage = $dir . '/' . $file['name'];

			// Cree fichier en taille origine
			$result=dol_move_uploaded_file($file['tmp_name'], $originImage, 1);

			if (file_exists(dol_osencode($originImage)))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage,$maxWidth,$maxHeight);
			}
		}
	}

	/**
	 *  Build thumb
	 *  @param      sdir           Repertoire destination finale
	 *  @param      file           Chemin du fichier d'origine
	 *  @param      maxWidth       Largeur maximum que dois faire la miniature (160 par defaut)
	 *  @param      maxHeight      Hauteur maximum que dois faire la miniature (120 par defaut)
	 */
	function add_thumb($file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once(DOL_DOCUMENT_ROOT ."/lib/images.lib.php");

		$file_osencoded=dol_osencode($file);
		if (file_exists($file_osencoded))
		{
			vignette($file,$maxWidth,$maxHeight);
		}
	}

	/**
	 *  Deplace fichier recupere sur internet (utilise pour interface avec OSC)
	 *  @param      sdir        		Repertoire destination finale
	 *  @param      $files      		url de l'image
	 *	@author	  Jean Heimburger	june 2007
	 */
	function add_photo_web($sdir, $file)
	{
		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/";
		$dir .= "photos/";

		$dir_osencoded=dol_osencode($dir);
		if (! file_exists($dir_osencoded))
		{
			dol_syslog("Product Create ".$dir);
			create_exdir($dir);
		}

		if (file_exists($dir_osencoded))
		{
			// Cree fichier en taille vignette
			// \todo A faire

			// Cree fichier en taille origine
			$content = @file_get_contents($file);
			if( $content)
			{
				$nom = basename($file);
				$im = fopen(dol_osencode($dir.$nom),'wb');
				fwrite($im, $content);
				fclose($im);
			}
		}
	}

	/**
	 *  Affiche la premiere photo du produit
	 *  @param      sdir        Repertoire a scanner
	 *  @return     boolean     true si photo dispo, false sinon
	 */
	function is_photo_available($sdir)
	{
		include_once(DOL_DOCUMENT_ROOT ."/lib/files.lib.php");

		$pdir = get_exdir($this->id,2) . $this->id ."/photos/";
		$dir = $sdir . '/'. $pdir;

		$nbphoto=0;

		$dir_osencoded=dol_osencode($dir);
		if (file_exists($dir_osencoded))
		{
			$handle=opendir($dir_osencoded);
			if (is_resource($handle))
			{
			    while (($file = readdir($handle)) != false)
    			{
    				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure data is stored in UTF8 in memory
    				if (dol_is_file($dir.$file)) return true;
    			}
			}
		}
		return false;
	}


	/**
	 *    	Show photos of a product (nbmax maximum)
	 *    	@param      sdir        	Directory to scan
	 *    	@param      size        	0=original size, 1 use thumbnail if possible
	 *    	@param      nbmax       	Nombre maximum de photos (0=pas de max)
	 *    	@param      nbbyrow     	Nombre vignettes par ligne (si mode vignette)
	 * 		@param		showfilename	1=Show filename
	 * 		@param		showaction		1=Show icon with action links (resize, delete)
	 * 		@param		maxheight		Max height of image when size=1
	 *    	@return     string			Html code to show photo. Number of photos shown is saved in this->nbphoto
	 *		TODO Move this into html.formproduct.class.php
	 */
	function show_photos($sdir,$size=0,$nbmax=0,$nbbyrow=5,$showfilename=0,$showaction=0,$maxheight=120)
	{
		global $conf,$user,$langs;

		include_once(DOL_DOCUMENT_ROOT ."/lib/files.lib.php");
		include_once(DOL_DOCUMENT_ROOT ."/lib/images.lib.php");

		$pdir = get_exdir($this->id,2) . $this->id ."/photos/";
		$dir = $sdir . '/'. $pdir;
		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';


		$return ='<!-- Photo -->'."\n";
        /*$return.="<script type=\"text/javascript\">
        jQuery(function() {
            jQuery('a.lightbox').lightBox({
                overlayBgColor: '#888',
                overlayOpacity: 0.6,
                imageLoading: '".DOL_URL_ROOT."/theme/eldy/img/working.gif',
                imageBtnClose: '".DOL_URL_ROOT."/theme/eldy/img/previous.png',
                imageBtnPrev: '".DOL_URL_ROOT."/theme/eldy/img/1leftarrow.png',
                imageBtnNext: '".DOL_URL_ROOT."/theme/eldy/img/1rightarrow.png',
                containerResizeSpeed: 350,
                txtImage: '".$langs->trans("Image")."',
                txtOf: '".$langs->trans("on")."',
                fixedNavigation:false
            	});
            });
        </script>\n";
        */
		$nbphoto=0;

		$dir_osencoded=dol_osencode($dir);
		if (file_exists($dir_osencoded))
		{
			$handle=opendir($dir_osencoded);
            if (is_resource($handle))
            {
    			while (($file = readdir($handle)) != false)
    			{
    				$photo='';

    				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure file is stored in UTF8 in memory

    				if (dol_is_file($dir.$file) && preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$dir.$file))
    				{
    					$nbphoto++;
    					$photo = $file;
    					$viewfilename = $file;

    					if ($size == 1) {   // Format vignette
    						// On determine nom du fichier vignette
    						$photo_vignette='';
    						if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$photo,$regs)) {
    							$photo_vignette=preg_replace('/'.$regs[0].'/i','',$photo)."_small".$regs[0];
    							if (! dol_is_file($dirthumb.$photo_vignette)) $photo_vignette='';
    						}

    						// Get filesize of original file
    						$imgarray=dol_getImageSize($dir.$photo);

    						if ($nbbyrow && $nbphoto == 1) $return.= '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

    						if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) $return.= '<tr align=center valign=middle border=1>';
    						if ($nbbyrow) $return.= '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

    						$return.= "\n";
    						$return.= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'" class="lightbox" target="_blank">';

    						// Show image (width height=$maxheight)
    						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
    						$alt=$langs->transnoentitiesnoconv('File').': '.$pdir.$photo;
    						$alt.=' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
    						if ($photo_vignette && $imgarray['height'] > $maxheight) {
    							$return.= '<!-- Show thumb -->';
    							$return.= '<img class="photo" border="0" height="'.$maxheight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
    						}
    						else {
    							$return.= '<!-- Show original file -->';
    							$return.= '<img class="photo" border="0" height="'.$maxheight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
    						}

    						$return.= '</a>'."\n";

    						if ($showfilename) $return.= '<br>'.$viewfilename;
    						if ($showaction)
    						{
    							$return.= '<br>';
    							// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
    							if ($photo_vignette && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i',$photo) && ($product->imgWidth > $maxWidth || $product->imgHeight > $maxHeight))
    							{
    								$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_refresh($langs->trans('GenerateThumb')).'&nbsp;&nbsp;</a>';
    							}
    							if ($user->rights->produit->creer || $user->rights->service->creer)
    							{
    								// Link to resize
    			               		$return.= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$_GET["id"].'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"),DOL_URL_ROOT.'/theme/common/transform-crop-and-resize','',1).'</a> &nbsp; ';

    			               		// Link to delete
    								$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
    								$return.= img_delete().'</a>';
    							}
    						}
    						$return.= "\n";

    						if ($nbbyrow) $return.= '</td>';
    						if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) $return.= '</tr>';

    					}

    					if ($size == 0) {     // Format origine
    						$return.= '<img class="photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$photo).'">';

    						if ($showfilename) $return.= '<br>'.$viewfilename;
    						if ($showaction)
    						{
    							if ($user->rights->produit->creer || $user->rights->service->creer)
    							{
    								// Link to resize
    			               		$return.= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$_GET["id"].'&amp;file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"),DOL_URL_ROOT.'/theme/common/transform-crop-and-resize','',1).'</a> &nbsp; ';

    			               		// Link to delete
    			               		$return.= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
    								$return.= img_delete().'</a>';
    							}
    						}
    					}

    					// On continue ou on arrete de boucler ?
    					if ($nbmax && $nbphoto >= $nbmax) break;
    				}
    			}
            }

			if ($nbbyrow && $size==1)
			{
				// Ferme tableau
				while ($nbphoto % $nbbyrow)
				{
					$return.= '<td width="'.ceil(100/$nbbyrow).'%">&nbsp;</td>';
					$nbphoto++;
				}

				if ($nbphoto) $return.= '</table>';
			}

			closedir($handle);
		}

		$this->nbphoto = $nbphoto;

		return $return;
	}

	/**
	 *  Retourne tableau de toutes les photos du produit
	 *  @param      dir         Repertoire a scanner
	 *  @param      nbmax       Nombre maximum de photos (0=pas de max)
	 *  @return     array       Tableau de photos
	 */
	function liste_photos($dir,$nbmax=0)
	{
		include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

		$nbphoto=0;
		$tabobj=array();

		$dir_osencoded=dol_osencode($dir);
		$handle=@opendir($dir_osencoded);
		if (is_resource($handle))
		{
			while (($file = readdir($handle)) != false)
			{
				if (! utf8_check($file)) $file=utf8_encode($file);	// readdir returns ISO
				if (dol_is_file($dir.$file))
				{
					$nbphoto++;

					// On determine nom du fichier vignette
					$photo=$file;
					$photo_vignette='';
					if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$photo,$regs))
					{
						$photo_vignette=preg_replace('/'.$regs[0].'/i','',$photo).'_small'.$regs[0];
					}

					$dirthumb = $dir.'thumbs/';

					// Objet
					$obj=array();
					$obj['photo']=$photo;
					if ($photo_vignette && dol_is_file($dirthumb.$photo_vignette)) $obj['photo_vignette']=$photo_vignette;
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
	 *  Efface la photo du produit et sa vignette
	 *  @param      file        Chemin de l'image
	 */
	function delete_photo($file)
	{
        require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

        $dir = dirname($file).'/'; // Chemin du dossier contenant l'image d'origine
		$dirthumb = $dir.'/thumbs/'; // Chemin du dossier contenant la vignette
		$filename = preg_replace('/'.preg_quote($dir,'/').'/i','',$file); // Nom du fichier

		// On efface l'image d'origine
		dol_delete_file($file);

		// Si elle existe, on efface la vignette
		if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$filename,$regs))
		{
			$photo_vignette=preg_replace('/'.$regs[0].'/i','',$filename).'_small'.$regs[0];
			if (file_exists(dol_osencode($dirthumb.$photo_vignette)))
			{
				dol_delete_file($dirthumb.$photo_vignette);
			}
		}
	}

	/**
	 *  Load size of image file
	 *  @param      file        Path to file
	 */
	function get_image_size($file)
	{
		$file_osencoded=dol_osencode($file);
		$infoImg = getimagesize($file_osencoded); // Get information on image
		$this->imgWidth = $infoImg[0]; // Largeur de l'image
		$this->imgHeight = $infoImg[1]; // Hauteur de l'image
	}

	/**
	 *  Charge indicateurs this->nb de tableau de bord
	 *  @return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE p.fk_product_type <> 1";
		$sql.= ' AND p.entity IN (0,'.(! empty($conf->entities['product']) ? $conf->entities['product'] : $conf->entity).')';

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
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Mise a jour du code barre
	 *  @param      user    Utilisateur qui fait la modification
	 */
	function update_barcode($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."product";
		$sql.= " SET barcode = '".$this->barcode."'";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Product::update_barcode sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Mise a jour du type de code barre
	 *  @param      user     Utilisateur qui fait la modification
	 */
	function update_barcode_type($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."product";
		$sql.= " SET fk_barcode_type = '".$this->barcode_type."'";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Product::update_barcode_type sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}



	/**
	 *  Affecte les valeurs communes
	 */
	function assign_values($action='')
	{
		global $conf,$langs;
		global $html;

		// canvas
		$this->tpl['canvas'] = $this->canvas;

		// id
		$this->tpl['id'] = $this->id;

		// Ref
		$this->tpl['ref'] = $this->ref;

		// Label
		$this->tpl['label'] = $this->libelle;

		// Description
		$this->tpl['description'] = nl2br($this->description);

		// Statut
		$this->tpl['status'] = $this->getLibStatut(2);

		// Note
		$this->tpl['note'] = nl2br($this->note);

		// Hidden
		if ($this->user->rights->produit->hidden)
		{
			$this->tpl['hidden'] = yn($this->hidden);
		}
		else
		{
			$this->tpl['hidden'] = yn("No");
		}

		if ($action == 'create')
		{
			// Price
			$this->tpl['price'] = $this->price;
			$this->tpl['price_min'] = $this->price_min;
			$this->tpl['price_base_type'] = $html->load_PriceBaseType($this->price_base_type, "price_base_type");

			// VAT
			$this->tpl['tva_tx'] = $html->load_tva("tva_tx",-1,$mysoc,'');
		}

		if ($action == 'create' || $action == 'edit')
		{
			// Status
			$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			$this->tpl['status'] = $html->selectarray('statut',$statutarray,$this->status);

			//To Buy
			$statutarray=array('1' => $langs->trans("Yes"), '0' => $langs->trans("No"));
			$this->tpl['tobuy'] = $html->selectarray('tobuy',$statutarray,$this->status_buy);

			// Hidden
			if ($this->user->rights->produit->hidden)
			{
				$this->tpl['hidden'] = $html->selectyesno('hidden',$this->hidden);
			}

            $this->tpl['description'] = $this->description;
            $this->tpl['note'] = $this->note;
		}

		if ($action == 'view')
		{
			// Ref
			$this->tpl['ref'] = $html->showrefnav($this,'ref','',1,'ref');

			// Accountancy buy code
			$this->tpl['accountancyBuyCodeKey'] = $html->editfieldkey("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,'id',$this->id,$user->rights->produit->creer);
			$this->tpl['accountancyBuyCodeVal'] = $html->editfieldval("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,'id',$this->id,$user->rights->produit->creer);

			// Accountancy sell code
			$this->tpl['accountancySellCodeKey'] = $html->editfieldkey("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,'id',$this->id,$user->rights->produit->creer);
			$this->tpl['accountancySellCodeVal'] = $html->editfieldval("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,'id',$this->id,$user->rights->produit->creer);
		}
	}

    /**
     * Return if object is a product
     * @return  boolean     True if it's a product
     */
	function isproduct()
	{
		if ($this->type != 1)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

    /**
     * Return if object is a product
     * @return  boolean     True if it's a service
     */
	function isservice()
	{
		if ($this->type==1)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

}
?>