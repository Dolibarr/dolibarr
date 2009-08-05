<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2006-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 \file       htdocs/expedition/expedition.class.php
 \ingroup    expedition
 \brief      Fichier de la classe de gestion des expeditions
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


/**
 *	\class      Expedition
 *	\brief      Classe de gestion des expeditions
 */
class Expedition extends CommonObject
{
	var $db;
	var $error;
	var $element="expedition";
	var $fk_element="fk_expedition";
	var $table_element="expedition";

	var $id;
	var $socid;
	var $brouillon;
	var $entrepot_id;
	var $modelpdf;
	var $origin;
	var $origin_id;
	var $lignes;
	var $meths;

	/**
	 * Initialisation
	 *
	 */
	function Expedition($DB)
	{
		global $langs;

		$this->db = $DB;
		$this->lignes = array();

		$this->statuts[-1] = $langs->trans("Canceled");
		$this->statuts[0]  = $langs->trans("Draft");
		$this->statuts[1]  = $langs->trans("Validated");

		$this->products = array();
	}

	/**
	 *    \brief      Créé expédition en base
	 *    \param      user        Objet du user qui cré
	 *    \return     int         <0 si erreur, id expédition créée si ok
	 */
	function create($user)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";
		$error = 0;

		// Clean parameters
		$this->brouillon = 1;
		$this->tracking_number = dol_sanitizeFileName($this->tracking_number);

		$this->user = $user;


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", date_expedition";
		$sql.= ", fk_soc";
		$sql.= ", fk_adresse_livraison";
		$sql.= ", fk_expedition_methode";
		$sql.= ", tracking_number";
		$sql.= ", weight";
		$sql.= ", size";
		$sql.= ", width";
		$sql.= ", height";
		$sql.= ", weight_units";
		$sql.= ", size_units";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$this->db->idate(gmmktime());
		$sql.= ", ".$user->id;
		$sql.= ", ".$this->db->idate($this->date_expedition);
		$sql.= ", ".$this->socid;
		$sql.= ", ".($this->fk_delivery_address>0?$this->fk_delivery_address:"null");
		$sql.= ", ".($this->expedition_method_id>0?$this->expedition_method_id:"null");
		$sql.= ", '". $this->tracking_number."'";
		$sql.= ", ".$this->weight;
		$sql.= ", ".$this->sizeS;
		$sql.= ", ".$this->sizeW;
		$sql.= ", ".$this->sizeH;
		$sql.= ", ".$this->weight_units;
		$sql.= ", ".$this->size_units;
		$sql.= ")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expedition");

			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
			$sql.= " SET ref = '(PROV".$this->id.")'";
			$sql.= " WHERE rowid = ".$this->id;
			if ($this->db->query($sql))
	  {
	  	// Insertion des lignes
	  	for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
	  	{
	  		if (! $this->create_line($this->lignes[$i]->entrepot_id, $this->lignes[$i]->origin_line_id, $this->lignes[$i]->qty) > 0)
	  		{
	  			$error++;
	  		}
	  	}

	  	if (! $error && $this->id && $this->origin_id)
	  	{
	  		if ($conf->commande->enabled)
	  		{
	  			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_exp (fk_expedition, fk_commande) VALUES ('.$this->id.','.$this->origin_id.')';
	  			if (!$this->db->query($sql))
	  			{
	  				$error++;
	  			}

	  			$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = 2 WHERE rowid=".$this->origin_id;
	  			if (! $this->db->query($sql))
	  			{
	  				$error++;
	  			}
	  		}
	  		else
	  		{
	  			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'pr_exp (fk_expedition, fk_propal) VALUES ('.$this->id.','.$this->origin_id.')';
	  			if (!$this->db->query($sql))
	  			{
	  				$error++;
	  			}

	  			// TODO definir un statut
	  			$sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_statut = 9 WHERE rowid=".$this->origin_id;
	  			if (! $this->db->query($sql))
	  			{
	  				$error++;
	  			}
	  		}
	  	}

	  	if (! $error)
	  	{
	  		$this->db->commit();
	  		return $this->id;
	  	}
	  	else
	  	{
	  		$error++;
	  		$this->error=$this->db->lasterror()." - sql=$sql";
	  		$this->db->rollback();
	  		return -3;
	  	}
	  }
	  else
	  {
	  	$error++;
	  	$this->error=$this->db->lasterror()." - sql=$sql";
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
	function create_line($entrepot_id, $origin_line_id, $qty)
	{
		$error = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet (fk_expedition, fk_entrepot, fk_origin_line, qty)";
		$sql .= " VALUES (".$this->id.", ".($entrepot_id?$entrepot_id:'null').", ".$origin_line_id.", ".$qty.")";
		//print 'x'.$sql;
		if (! $this->db->query($sql))
		{
			$error++;
		}

		if (! $error) return 1;
		else return -1;
	}

	/**
	 *		\brief		Lit une expedition
	 *		\param		id
	 */
	function fetch ($id)
	{
		global $conf;

		$sql = "SELECT e.rowid, e.fk_soc as socid, e.date_creation, e.ref, e.fk_user_author, e.fk_statut";
		$sql.= ", e.weight, e.weight_units, e.size, e.size_units, e.width, e.height";
		$sql.= ", ".$this->db->pdate("e.date_expedition")." as date_expedition, e.model_pdf, e.fk_adresse_livraison";
		$sql.= ", e.fk_expedition_methode, e.tracking_number";
		if ($conf->commande->enabled)
		{
			$sql.= ", ce.fk_commande as origin_id";
			$sql.= ", c.ref_client";
		}
		else
		{
			$sql.= ", pe.fk_propal as origin_id";
			$sql.= ", p.ref_client"; 
		}
		if ($conf->livraison_bon->enabled) $sql.=", l.rowid as livraison_id";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
		if ($conf->commande->enabled)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON ce.fk_commande = c.rowid";
		}
		else
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."pr_exp as pe ON e.rowid = pe.fk_expedition";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON pe.fk_propal = p.rowid";
		}
		if ($conf->livraison_bon->enabled) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON e.rowid = l.fk_expedition";
		$sql.= " WHERE e.rowid = ".$id;

		dol_syslog("Expedition::fetch sql=".$sql);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->ref                  = $obj->ref;
				$this->socid                = $obj->socid;
				$this->ref_client			= $obj->ref_client;
				$this->statut               = $obj->fk_statut;
				$this->origin_id            = $obj->origin_id;
				$this->livraison_id         = $obj->livraison_id;
				$this->user_author_id       = $obj->fk_user_author;
				$this->date                 = $obj->date_expedition;
				$this->adresse_livraison_id = $obj->fk_adresse_livraison; // TODO obsolete
				$this->fk_delivery_address  = $obj->fk_adresse_livraison;
				$this->modelpdf             = $obj->model_pdf;
				$this->expedition_method_id = $obj->fk_expedition_methode;
				$this->tracking_number      = $obj->tracking_number;

				$this->trueWeight           = $obj->weight;
				$this->weight_units         = $obj->weight_units;

				$this->trueWidth            = $obj->width;
				$this->width_units          = $obj->size_units;
				$this->trueHeight           = $obj->height;
				$this->height_units         = $obj->size_units;
				$this->trueDepth            = $obj->size;
				$this->depth_units          = $obj->size_units;

				// A denormalized value
				$this->trueSize           	= $obj->size."x".$obj->width."x".$obj->height;
				$this->size_units           = $obj->size_units;

				if ($conf->commande->enabled)
				{
					$this->origin = "commande";
				}
				else
				{
					$this->origin = "propal";
				}

				$this->db->free($result);

				if ($this->statut == 0) $this->brouillon = 1;

				$this->lignes = array();

				$file = $conf->expedition->dir_output . "/" .get_exdir($expedition->id,2) . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;

				/*
				 * Lignes
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					return -3;
				}

				return 1;
			}
			else
			{
				dol_syslog('Expedition::Fetch Error rowid='.$rowid.' numrows=0 sql='.$sql);
				$this->error='Delivery with id '.$rowid.' not found sql='.$sql;
				return -2;
			}
		}
		else
		{
			dol_syslog('Expedition::Fetch Error rowid='.$rowid.' Erreur dans fetch de l\'expedition');
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *        \brief      Validate object and update stock if option enabled
	 *        \param      user        Objet de l'utilisateur qui valide
	 *        \return     int
	 */
	function valid($user)
	{
		global $conf;

		dol_syslog("Expedition::valid");

		// Protection
		if ($this->statut)
		{
			dol_syslog("Expedition::valid no draft status", LOG_WARNING);
			return 0;
		}

		if (! $user->rights->expedition->valider)
		{
			$this->error='Permission denied';
			dol_syslog("Expedition::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		// Define new ref
		$num = "EXP".$this->id;

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
		$sql.= " SET ref='".$num."'";
		$sql.= ", fk_statut = 1";
		$sql.= ", date_valid = ".$this->db->idate(mktime());
		$sql.= ", fk_user_valid = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Expedition::valid update expedition sql=".$sql);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			dol_syslog("Expedition::valid() Echec update - 10 - sql=".$sql, LOG_ERR);
			dol_print_error($this->db);
			$error++;
		}

		if (! $error)
		{
			// If stock increment is done on sending (recommanded choice)
			if ($result >= 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SHIPMENT)
			{
				require_once DOL_DOCUMENT_ROOT ."/product/stock/mouvementstock.class.php";

				// Loop on each product line to add a stock movement
				$sql = "SELECT cd.fk_product, cd.subprice, ed.qty, ed.fk_entrepot";
				$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
				$sql.= ", ".MAIN_DB_PREFIX."expeditiondet as ed";
				$sql.= " WHERE ed.fk_expedition = ".$this->id;
				$sql.= " AND cd.rowid = ed.fk_origin_line";
				dol_syslog("Expedition::valid select details sql=".$sql);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i=0;
					while($i < $num)
					{
						dol_syslog("Expedition::valid movment index ".$i);
						$obj = $this->db->fetch_object($resql);

						if ($this->lignes[$i]->fk_product > 0 && $this->lignes[$i]->product_type == 0)
						{
							$mouvS = new MouvementStock($this->db);
							// We decrement stock of product (and sub-products)
							$entrepot_id = "1"; // TODO ajouter possibilité de choisir l'entrepot
							$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty, $obj->subprice);
							if ($result < 0) { $error++; }
						}

						$i++;
					}
				}
				else
				{
					$this->db->rollback();
					$this->error=$this->db->error()." - sql=$sql";
					dol_syslog("Expedition::valid ".$this->error, LOG_ERR);
					return -2;
				}
			}
		}

		if (! $error)
		{
			// On efface le répertoire de pdf provisoire
			$expeditionref = dol_sanitizeFileName($this->ref);
			if ($conf->expedition->dir_output)
			{
				$dir = $conf->expedition->dir_output . "/" . $expeditionref;
				$file = $dir . "/" . $expeditionref . ".pdf";
				if (file_exists($file))
				{
					if (!dol_delete_file($file))
					{
						$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
					}
				}
				if (file_exists($dir))
				{
					if (!dol_delete_dir($dir))
					{
						$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
					}
				}
			}
		}

		// Set new ref
		if (! $error)
		{
			$this->ref = $num;
		}

		if (! $error)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('ORDER_SHIPPING',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *      \brief      Crée un bon de livraison à partir de l'expédition
	 *      \param      user        Utilisateur
	 *      \return     int         <0 si ko, >=0 si ok
	 */
	function create_delivery($user)
	{
		global $conf;

		if ($conf->livraison_bon->enabled)
		{
			if ($this->statut == 1)
			{
				// Expédition validée
				include_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
				$livraison = new Livraison($this->db);
				$result=$livraison->create_from_sending($user, $this->id);
				if ($result > 0)
				{
					return $result;
				}
				else
				{
					$this->error=$livraison->error;
					return $result;
				}
			}
			else return 0;
		}
		else return 0;
	}

	/**
	 * Ajoute une ligne
	 *
	 */
	function addline( $entrepot_id, $id, $qty )
	{
		$num = sizeof($this->lignes);
		$ligne = new ExpeditionLigne($this->db);

		$ligne->entrepot_id = $entrepot_id;
		$ligne->origin_line_id = $id;
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
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
			$sql.= " WHERE rowid = ".$idligne;

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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition = ".$this->id;
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."co_exp WHERE rowid = ".$this->id;
			if ( $this->db->query($sql) )
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."expedition WHERE rowid = ".$this->id;
				if ( $this->db->query($sql) )
				{
					$this->db->commit();

					// On efface le répertoire de pdf provisoire
					$expref = dol_sanitizeFileName($this->ref);
					if ($conf->expedition->dir_output)
					{
						$dir = $conf->expedition->dir_output . "/" . $expref ;
						$file = $conf->expedition->dir_output . "/" . $expref . "/" . $expref . ".pdf";
						if (file_exists($file))
						{
							if (!dol_delete_file($file))
							{
								$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
								return 0;
							}
						}
						if (file_exists($dir))
						{
							if (!dol_delete_dir($dir))
							{
								$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
								return 0;
							}
						}
					}
					// TODO il faut incrementer le stock si on supprime une expedition validee
					return 1;
				}
				else
				{
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
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/*
	 * Lit le document associé
	 *
	 */
	function fetch_object()
	{
		$object = $this->origin;
		$class = ucfirst($object);
		$this->$object = & new $class($this->db);
		$this->$object->fetch($this->origin_id);
	}


	function fetch_lines()
	{
		//Todo: récupérer les champs du document associé a part

		$sql = "SELECT cd.rowid, cd.fk_product, cd.description, cd.qty as qty_asked";
		$sql.= ", ed.qty as qty_shipped, ed.fk_origin_line, ed.fk_entrepot";
		$sql.= ", p.ref, p.fk_product_type, p.label, p.weight, p.weight_units, p.volume, p.volume_units";
		$sql.= " FROM (".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= ", ".MAIN_DB_PREFIX."expeditiondet as ed)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON (p.rowid = cd.fk_product)";
		$sql.= " WHERE ed.fk_expedition = ".$this->id;
		$sql.= " AND ed.fk_origin_line = cd.rowid";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$ligne = new ExpeditionLigne($this->db);
				$obj = $this->db->fetch_object($resql);

				$ligne->origin_line_id = $obj->fk_origin_line;
				$ligne->entrepot_id    = $obj->fk_entrepot;
				$ligne->fk_product     = $obj->fk_product;
				$ligne->fk_product_type= $obj->fk_product_type;
				$ligne->ref            = $obj->ref;
				$ligne->label          = $obj->label;		// deprecated
				$ligne->libelle        = $obj->label;
				$ligne->description    = $obj->description;
				$ligne->qty_asked      = $obj->qty_asked;
				$ligne->qty_shipped    = $obj->qty_shipped;
				$ligne->weight         = $obj->weight;
				$ligne->weight_units   = $obj->weight_units;
				$ligne->volume         = $obj->volume;
				$ligne->volume_units   = $obj->volume_units;

				$this->lignes[$i] = $ligne;
				$i++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('Expedition::fetch_lines: Error '.$this->error, LOG_ERR);
			return -3;
		}
	}

	/**
	 *    \brief      Retourne le libellé du statut d'une expedition
	 *    \return     string      Libellé
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *		\brief      Renvoi le libellé d'un statut donné
	 *    	\param      statut      Id statut
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Pict
	 *    	\return     string		Libellé
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode==0)
		{
			if ($statut==0) return $this->statuts[$statut];
			if ($statut==1) return $this->statuts[$statut];
		}
		if ($mode==1)
		{
			if ($statut==0) return $this->statuts[$statut];
			if ($statut==1) return $this->statuts[$statut];
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans('StatusSendingDraft'),'statut0').' '.$langs->trans('StatusSendingDraft');
			if ($statut==1) return img_picto($langs->trans('StatusSendingValidated'),'statut4').' '.$langs->trans('StatusSendingValidated');
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans('StatusSendingDraftShort').' '.img_picto($langs->trans('StatusSendingDraft'),'statut0');
			if ($statut==1) return $langs->trans('StatusSendingValidatedShort').' '.img_picto($langs->trans('StatusSendingValidated'),'statut4');
		}
	}

	/**
	 *		\brief		Initialise la facture avec valeurs fictives aléatoire
	 *					Sert à générer une facture pour l'aperu des modèles ou dem
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		dol_syslog("Expedition::initAsSpecimen");

		// Charge tableau des id de société socids
		$socids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE client = 1";
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " LIMIT 10";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs)
			{
				$i++;

				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];
			}
		}

		// Charge tableau des produits prodids
		$prodids = array();

		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE envente = 1";
		$sql.= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods)
			{
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		$order=new Commande($this->db);
		$order->initAsSpecimen();

		// Initialise paramètres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->statut               = 1;
		if ($conf->livraison_bon->enabled)
		{
			$this->livraison_id     = 0;
		}
		$this->date                 = time();
		$this->entrepot_id          = 0;
		$this->adresse_livraison_id = 0; // TODO obsolete
		$this->fk_delivery_address  = 0;
		$this->socid = $socids[$socid];

		$this->commande_id          = 0;
		$this->commande             = $order;

		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$ligne=new ExpeditionLigne($this->db);
			$ligne->desc=$langs->trans("Description")." ".$xnbp;
			$ligne->libelle=$langs->trans("Description")." ".$xnbp;
			$ligne->qty=10;
			$ligne->qty_asked=5;
			$ligne->qty_shipped=4;
			$ligne->fk_product=$this->commande->lignes[$xnbp]->fk_product;

			$this->lignes[]=$ligne;
			$xnbp++;
		}

	}

	/**
	 *	\brief	Fetch deliveries method and return an array. Load array this->meths(rowid=>label).
	 */
	function fetch_delivery_methods()
	{
		global $langs;
		$meths = array();

		$sql = "SELECT em.rowid, em.code, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition_methode as em";
		$sql.= " WHERE em.active = 1";
		$sql.= " ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$label=$langs->trans('SendingMethod'.$obj->code);
				$this->meths[$obj->rowid] = ($label != 'SendingMethod'.$obj->code?$label:$obj->libelle);
			}
		}
	}

	/**
	 *	Get tracking url status
	 */
	function GetUrlTrackingStatus()
	{
		$sql = "SELECT em.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition_methode as em";
		$sql.= " WHERE em.rowid = ".$this->expedition_method_id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($obj = $this->db->fetch_object($resql))
			{
				$code = $obj->code;
			}
		}

		if ($code) {
			$classe = "methode_expedition_".strtolower($code);
			require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/methode_expedition_".strtolower($code).".modules.php");
			$obj = new $classe;
			$url = $obj->provider_url_status($this->tracking_number);
			if ($url)
			{
				$this->tracking_url = sprintf('<a target="_blank" href="%s">url</a>',$url,$url);
			}
			else
			{
				$this->tracking_url = '';
			}
		}
		else
		{
			$this->tracking_url = '';
		}
	}
}


/**
 \class      ExpeditionLigne
 \brief      Classe de gestion des lignes de bons d'expedition
 */
class ExpeditionLigne
{
	var $db;

	// From llx_expeditiondet
	var $qty;
	var $qty_shipped;
	var $fk_product;

	// From llx_commandedet or llx_propaldet
	var $qty_asked;
	var $libelle;       // Label produit
	var $product_desc;  // Description produit
	var $ref;


	function ExpeditionLigne($DB)
	{
		$this->db=$DB;
	}

}

?>
