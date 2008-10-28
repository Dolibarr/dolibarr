<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Régis Houssin         <regis@dolibarr.fr>
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
 \class      Expedition
 \brief      Classe de gestion des expeditions
 */
class Expedition extends CommonObject
{
	var $db;
	var $error;
	var $element="expedition";
	var $table_element="expedition";

	var $id;
	var $socid;
	var $brouillon;
	var $entrepot_id;
	var $modelpdf;
	var $origin;
	var $origin_id;

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
		/* On positionne en mode brouillon l'expedition */
		$this->brouillon = 1;

		$this->user = $user;

		$this->expedition_method = sanitizeFileName($this->expedition_method);
		$this->tracking_number = sanitizeFileName($this->tracking_number);

		$this->db->begin();

  		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expedition (ref, date_creation, fk_user_author, date_expedition,";
 		$sql.= " fk_soc, fk_expedition_methode, tracking_number, weight, size, width, height, weight_units, size_units";
  		$sql.= ")";
  		$sql.= " VALUES ('(PROV)', now(), $user->id, ".$this->db->idate($this->date_expedition);
 		$sql.= ", ".$this->socid.",'". $this->expedition_method_id."','". $this->tracking_number."',".$this->weight.",".$this->sizeS.",".$this->sizeW.",".$this->sizeH.",".$this->weight_units.",".$this->size_units;
  		$sql.= ")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."expedition");

			$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
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

		    //Todo: definir un statut
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
		$sql.= ", weight, weight_units, size, size_units, width, height";
		$sql.= ", ".$this->db->pdate("e.date_expedition")." as date_expedition, e.model_pdf, e.fk_adresse_livraison";
		$sql.= ", e.fk_expedition_methode, e.tracking_number";
		if ($conf->commande->enabled)
		{
			$sql.=", ce.fk_commande as origin_id";
		}
		else
		{
			$sql.=", pe.fk_propal as origin_id";
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

		$result = $this->db->query($sql) ;

		if ($result)
		{
			if ($this->db->num_rows($result))
	  {
	  	$obj = $this->db->fetch_object($result);
	  	 
	  	$this->id                   = $obj->rowid;
	  	$this->ref                  = $obj->ref;
	  	$this->socid                = $obj->socid;
	  	$this->statut               = $obj->fk_statut;
	  	$this->origin_id            = $obj->origin_id;
	  	$this->livraison_id         = $obj->livraison_id;
	  	$this->user_author_id       = $obj->fk_user_author;
	  	$this->date                 = $obj->date_expedition;
	  	$this->adresse_livraison_id = $obj->fk_adresse_livraison;
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
	  	dolibarr_syslog('Expedition::Fetch Error rowid='.$rowid.' numrows=0 sql='.$sql);
	  	$this->error='Delivery with id '.$rowid.' not found sql='.$sql;
	  	return -2;
	  }
		}
		else
		{
			dolibarr_syslog('Expedition::Fetch Error rowid='.$rowid.' Erreur dans fetch de l\'expedition');
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

		dolibarr_syslog("Expedition::valid");

		$this->db->begin();

		$error = 0;
		$provref = $this->ref;

		if ($user->rights->expedition->valider)
		{
			$this->ref = "EXP".$this->id;

			// Tester si non dejà au statut validé. Si oui, on arrete afin d'éviter
			// de décrémenter 2 fois le stock.
			$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."expedition where ref='".$this->ref."' AND fk_statut <> '0'";
			$resql=$this->db->query($sql);
			if ($resql)
	  {
	  	$num = $this->db->num_rows($resql);
	  	if ($num > 0)
	  	{
	  		dolibarr_syslog("Expedition::valid already validated", LOG_WARNING);
	  		$this->db->rollback();
	  		return 0;
	  	}
	  }

	  $sql = "UPDATE ".MAIN_DB_PREFIX."expedition";
	  $sql.= " SET ref='".$this->ref."', fk_statut = 1, date_valid = ".$this->db->idate(mktime()).", fk_user_valid = ".$user->id;
	  $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

	  dolibarr_syslog("Expedition::valid update expedition sql=".$sql);
	  $resql=$this->db->query($sql);
	  if ($resql)
	  {
	  	// If stock increment is done on sending (recommanded choice)
	  	if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SHIPMENT)
	  	{
	  		/*
	  		 * Enregistrement d'un mouvement de stock pour chaque produit de l'expedition
	  		 */
	  		$sql = "SELECT cd.fk_product, ed.qty, ed.fk_entrepot";
	  		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."expeditiondet as ed";
	  		$sql.= " WHERE ed.fk_expedition = $this->id AND cd.rowid = ed.fk_origin_line";

	  		dolibarr_syslog("Expedition::valid select details sql=".$sql);
	  		$resql=$this->db->query($sql);
	  		if ($resql)
	  		{
		    $num = $this->db->num_rows($resql);
		    $i=0;
		    while($i < $num)
		    {
		    	dolibarr_syslog("Expedition::valid movment nb ".$i);

		    	$obj = $this->db->fetch_object($resql);

		    	$mouvS = new MouvementStock($this->db);
		    	$result=$mouvS->livraison($user, $obj->fk_product, $obj->fk_entrepot, $obj->qty);
		    	if ($result < 0)
		    	{
		    		$this->db->rollback();
		    		$this->error=$this->db->error()." - sql=$sql";
		    		dolibarr_syslog("Expedition::valid ".$this->error);
		    		return -3;
		    	}

		    	$i++;
		    }

	  		}
	  		else
	  		{
		    $this->db->rollback();
		    $this->error=$this->db->error()." - sql=$sql";
		    dolibarr_syslog("Expedition::valid ".$this->error);
		    return -2;
	  		}
	  	}
	  	 
	  	// On efface le répertoire de pdf provisoire
	  	$expeditionref = sanitizeFileName($provref);
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
	  else
	  {
	  	$this->db->rollback();
	  	$this->error=$this->db->error();
	  	dolibarr_syslog("Expedition::valid ".$this->error);
	  	return -1;
	  }
		}
		else
		{
			$this->db->rollback();
			$this->error="Non autorise";
			dolibarr_syslog("Expedition::valid ".$this->error);
			return -1;
		}

		$this->db->commit();
		//dolibarr_syslog("Expedition::valid commit");
		return 1;
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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition = ".$this->id;
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."expedition WHERE rowid = ".$this->id;
			if ( $this->db->query($sql) )
			{
				$this->db->commit();
				 
				// On efface le répertoire de pdf provisoire
				$expref = sanitizeFileName($this->ref);
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
		$sql.= ", p.ref, p.label, p.weight, p.weight_units, p.volume, p.volume_units";
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
				$ligne->ref            = $obj->ref;
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
			dolibarr_syslog('Expedition::fetch_lines: Error '.$this->error);
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
		global $user,$langs;

		dolibarr_syslog("Expedition::initAsSpecimen");

		// Charge tableau des id de société socids
		$socids = array();
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1 LIMIT 10";
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
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
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
		$this->adresse_livraison_id = 0;
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
			$prodid = rand(1, $num_prods);
			$ligne->fk_product=$prodids[$prodid];
			$xnbp++;
		}
	}
	/*
	 Fetch deliveries method and return an array
	 */
	function fetch_delivery_methods()
	{
		$meths = array();

		$sql = "SELECT em.rowid, em.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."expedition_methode as em";
		$sql.= " WHERE em.statut = 1 ORDER BY em.libelle ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
	  {
	  	$this->meths[$obj->rowid] = $obj->libelle;
	  }
		}
	}
	/*
	 Get tracking url status
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
			$this->tracking_url = sprintf('<a target="_blank" href="%s">url</a>',$url,$url);
		} else {
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
