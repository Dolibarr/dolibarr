<?php
/* Copyright (C) 2003      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 \file       htdocs/livraison/livraison.class.php
 \ingroup    livraison
 \brief      Fichier de la classe de gestion des bons de livraison
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/mouvementstock.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


/**
 \class      Livraison
 \brief      Classe de gestion des bons de livraison
 */
class Livraison extends CommonObject
{
	var $db;
	var $id;

	var $brouillon;
	var $origin;
	var $origin_id;
	var $socid;

	var $date_livraison;
	var $date_creation;
	var $date_valid;


	/**
	 * Initialisation
	 *
	 */
	function Livraison($DB)
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
	 *    \brief      Create delivery receipt in database
	 *    \param      user        Objet du user qui cree
	 *    \return     int         <0 si erreur, id livraison cree si ok
	 */
	function create($user)
	{
		global $conf;

		dolibarr_syslog("Livraison::create");

		$error = 0;

		/* On positionne en mode brouillon le bon de livraison */
		$this->brouillon = 1;

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraison (ref, fk_soc, date_creation, fk_user_author,";
		$sql.= " fk_adresse_livraison";
		if ($this->expedition_id) $sql.= ", fk_expedition";
		$sql.= ")";
		$sql.= " VALUES ('(PROV)', ".$this->socid.", ".$this->db->idate(mktime()).", $user->id,";
		$sql.= " ".($this->adresse_livraison_id > 0?$this->adresse_livraison_id:"null");
		if ($this->expedition_id) $sql.= ", $this->expedition_id";
		$sql.= ")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."livraison");

			$numref="(PROV".$this->id.")";
			$sql = "UPDATE ".MAIN_DB_PREFIX."livraison ";
			$sql.= "SET ref='".addslashes($numref)."' WHERE rowid=".$this->id;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				if (! $conf->expedition->enabled)
				{
					$commande = new Commande($this->db);
					$commande->id = $this->commande_id;
					$this->lignes = $commande->fetch_lines();
				}


				/*
				 *  Insertion des produits dans la base
				 */
				for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
				{
					$origin_id=$this->lignes[$i]->origin_line_id;
					if (! $origin_id) $origin_id=$this->lignes[$i]->commande_ligne_id;	// For backward compatibility
						
					if (! $this->create_line(0, $origin_id, $this->lignes[$i]->qty, $this->lignes[$i]->fk_product))
					{
						$error++;
					}
				}

				/*
				if ($conf->livraison->enabled && $this->origin_id)
				{
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'co_liv (fk_livraison, fk_commande) VALUES ('.$this->id.','.$this->origin_id.')';
					if (!$this->db->query($sql))
					{
						$error++;
					}

				}
				*/
					
				if (! $conf->expedition->enabled)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
					$sql.= " SET fk_statut = 2";
					$sql.= " WHERE rowid=".$this->commande_id;
					$resql=$this->db->query($sql);
					if (! $resql)
					{
						$error++;
					}
				}

				if ($error==0)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
					dolibarr_syslog("Livraison::create Error -3 ".$this->error);
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
				dolibarr_syslog("Livraison::create Error -2 ".$this->error);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
			dolibarr_syslog("Livraison::create Error -1 ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *
	 *
	 */
	function create_line($transaction, $commande_ligne_id, $qty, $fk_product=0)
	{
		$error = 0;
		$idprod = $fk_product;
		$j = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraisondet (fk_livraison, fk_origin_line,";
		if ($idprod) $sql.=" fk_product,";
		$sql.= " qty)";
		$sql.= " VALUES (".$this->id.",".$commande_ligne_id.",";
		if ($idprod) $sql.=" ".$idprod.",";
		$sql.= $qty.")";

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
	 * Lit un bon de livraison
	 *
	 */
	function fetch($id)
	{
		global $conf;

		$sql = "SELECT l.rowid, l.fk_soc, l.date_creation, l.date_valid, l.ref, l.ref_client, l.fk_user_author,";
		$sql.=" l.total_ht, l.fk_statut, l.fk_expedition, l.fk_user_valid, l.note, l.note_public";
		$sql.= ", ".$this->db->pdate("l.date_livraison")." as date_livraison, l.fk_adresse_livraison, l.model_pdf";
		if ($conf->commande->enabled)
		{
			$sql.= ", cl.fk_commande as origin_id";
		}
		else
		{
			$sql.= ", pl.fk_propal as origin_id";
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."livraison as l";
		if ($conf->commande->enabled)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_liv as cl ON cl.fk_livraison = l.rowid";
		}
		else
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."pr_liv as pl ON pl.fk_livraison = l.rowid";
		}
		$sql.= " WHERE l.rowid = ".$id;

		dolibarr_syslog("Livraison::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->date_livraison       = $obj->date_livraison;
				$this->date_creation        = $obj->date_creation;
				$this->date_valid           = $obj->date_valid;
				$this->ref                  = $obj->ref;
				$this->ref_client           = $obj->ref_client;
				$this->socid                = $obj->fk_soc;
				$this->statut               = $obj->fk_statut;
				$this->origin_id            = $obj->origin_id;
				$this->expedition_id        = $obj->fk_expedition;
				$this->user_author_id       = $obj->fk_user_author;
				$this->user_valid_id        = $obj->fk_user_valid;
				$this->adresse_livraison_id = $obj->fk_adresse_livraison;
				$this->note                 = $obj->note;
				$this->note_public          = $obj->note_public;
				$this->modelpdf             = $obj->model_pdf;

				$this->db->free($result);

				if ($this->origin_id)
				{
					if ($conf->commande->enabled)
					{
						$this->origin = "commande";
					}
					else
					{
						$this->origin = "propal";
					}
				}
					
				if ($this->statut == 0) $this->brouillon = 1;
					
				$file = $conf->livraison->dir_output . "/" .get_exdir($livraison->id,2) . "/" . $this->id.".pdf";
				$this->pdf_filename = $file;
					
				/*
				 * Lignes
				 */
				$result=$this->fetch_lignes();
				if ($result < 0)
				{
					return -3;
				}
					
				return 1;
			}
			else
			{
				$this->error='Delivery with id '.$rowid.' not found sql='.$sql;
				dolibarr_syslog('Livraison::Fetch Error '.$this->error);
				return -2;
			}
		}
		else
		{
			dolibarr_syslog('Livraison::Fetch Error '.$this->error);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *        \brief      Valide l'expedition, et met a jour le stock si stock g�r
	 *        \param      user        Objet de l'utilisateur qui valide
	 *        \return     int
	 */
	function valid($user)
	{
		global $conf;

		dolibarr_syslog("livraison.class.php::valid begin");

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
					$modName=LIVRAISON_ADDON;

					// Recuperation de la nouvelle reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->socid);

					// on v�rifie si le bon de livraison est en num�rotation provisoire
					$livref = substr($this->ref, 1, 4);
					if ($livref == 'PROV')
					{
						$this->ref = $objMod->livraison_get_num($soc,$this);
					}

					// Tester si non dej� au statut valid�. Si oui, on arrete afin d'�viter
					// de d�cr�menter 2 fois le stock.
					$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."livraison where ref='".$this->ref."' AND fk_statut <> 0";
					$resql=$this->db->query($sql);
					if ($resql)
					{
						$num = $this->db->num_rows($resql);
						if ($num > 0)
						{
							return 0;
						}
					}

					$sql = "UPDATE ".MAIN_DB_PREFIX."livraison ";
					$sql.= " SET ref='".addslashes($this->ref)."', fk_statut = 1, date_valid = ".$this->db->idate(mktime()).", fk_user_valid = ".$user->id;
					$sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
					$resql=$this->db->query($sql);
					if ($resql)
					{
						// Si module stock g�r� et que expedition faite depuis un entrepot
						if (!$conf->expedition->enabled && $conf->stock->enabled && $this->entrepot_id && $conf->global->STOCK_CALCULATE_ON_SHIPMENT == 1)
						{

							//Enregistrement d'un mouvement de stock pour chaque produit de l'expedition


							dolibarr_syslog("livraison.class.php::valid enregistrement des mouvements");

							$sql = "SELECT cd.fk_product, ld.qty ";
							$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."livraisondet as ld";
							$sql.= " WHERE ld.fk_livraison = $this->id AND cd.rowid = ld.fk_commande_ligne";

							$resql=$this->db->query($sql);
							if ($resql)
							{
								$num = $this->db->num_rows($resql);
								$i=0;
								while($i < $num)
								{
									dolibarr_syslog("livraison.class.php::valid movment $i");

									$obj = $this->db->fetch_object($resql);

									$mouvS = new MouvementStock($this->db);
									$result=$mouvS->livraison($user, $obj->fk_product, $this->entrepot_id, $obj->qty);
									if ($result < 0)
									{
										$this->db->rollback();
										$this->error=$this->db->error()." - sql=$sql";
										dolibarr_syslog("livraison.class.php::valid ".$this->error);
										return -3;
									}
									$i++;
								}

							}
							else
							{
								$this->db->rollback();
								$this->error=$this->db->error()." - sql=$sql";
								dolibarr_syslog("livraison.class.php::valid ".$this->error);
								return -2;

							}
						}

						// On efface le r�pertoire de pdf provisoire
						$livraisonref = sanitize_string($this->ref);
						if ($conf->expedition->dir_output)
						{
							$dir = $conf->livraison->dir_output . "/" . $livraisonref ;
							$file = $dir . "/" . $livraisonref . ".pdf";
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

						dolibarr_syslog("livraison.class.php::valid ok");
					}
					else
					{
						$this->db->rollback();
						$this->error=$this->db->error()." - sql=$sql";
						dolibarr_syslog("livraison.class.php::valid ".$this->error);
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
		dolibarr_syslog("livraison.class.php::valid commit");
		return 1;
	}

	/**     \brief      Cr�� le bon de livraison depuis une exp�dition existante
	 \param      user            Utilisateur qui cr�e
	 \param      sending_id      Id de l'exp�dition qui sert de mod�le
	 */
	function create_from_sending($user, $sending_id)
	{
		$expedition = new Expedition($this->db);
		$result=$expedition->fetch($sending_id);

		$this->lignes = array();
		$this->date_livraison = time();
		$this->expedition_id = $sending_id;

		for ($i = 0 ; $i < sizeof($expedition->lignes) ; $i++)
		{
			$LivraisonLigne = new LivraisonLigne($this->db);
			$LivraisonLigne->origin_line_id    = $expedition->lignes[$i]->origin_line_id;
			$LivraisonLigne->libelle           = $expedition->lignes[$i]->libelle;
			$LivraisonLigne->description       = $expedition->lignes[$i]->description;
			$LivraisonLigne->qty               = $expedition->lignes[$i]->qty_shipped;
			$LivraisonLigne->fk_product        = $expedition->lignes[$i]->fk_product;
			$LivraisonLigne->ref               = $expedition->lignes[$i]->ref;
			$this->lignes[$i] = $LivraisonLigne;
		}

		$this->origin               = $expedition->origin;
		$this->origin_id            = $expedition->origin_id;
		$this->note                 = $expedition->note;
		$this->projetid             = $expedition->projetidp;
		$this->date_livraison       = $expedition->date_livraison;
		$this->adresse_livraison_id = $expedition->adresse_livraison_id;
		$this->socid                = $expedition->socid;

		return $this->create($user);
	}


	/**
	 * Ajoute une ligne
	 *
	 */
	function addline( $id, $qty )
	{
		$num = sizeof($this->lignes);
		$ligne = new livraisonLigne($this->db);

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

				// On efface le r�pertoire de pdf provisoire
				$livref = sanitize_string($this->ref);
				if ($conf->livraison->dir_output)
				{
					$dir = $conf->livraison->dir_output . "/" . $livref ;
					$file = $conf->livraison->dir_output . "/" . $livref . "/" . $livref . ".pdf";
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
	 * Lit le document associe
	 *
	 */
	function fetch_object()
	{
		$object = $this->origin;
		$class = ucfirst($this->origin);
		$this->$object = & new $class($this->db);
		$this->$object->fetch($this->origin_id);
	}

	/**
	 *
	 *
	 */
	function fetch_adresse_livraison($id)
	{
		$idadresse = $id;
		$adresse = new Societe($this->db);
		$adresse->fetch_adresse_livraison($idadresse);
		$this->adresse = $adresse;
	}

	/**
	 *
	 *
	 */
	function fetch_lignes()
	{
		$this->lignes = array();

		$sql = "SELECT p.label, p.ref,";
		$sql.= " l.description, l.fk_product, l.subprice, l.total_ht, l.qty as qty_shipped";
		//$sql.= ", c.qty as qty_asked";
		$sql.= " FROM ".MAIN_DB_PREFIX."livraisondet as l";
		//$sql .= " , ".MAIN_DB_PREFIX."commandedet as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on p.rowid = l.fk_product";
		$sql.= " WHERE l.fk_livraison = ".$this->id;
		//$sql .= " AND l.fk_commande_ligne = c.rowid";

		dolibarr_syslog("Livraison::fetch_lignes sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$ligne = new LivraisonLigne($this->db);

				$obj = $this->db->fetch_object($resql);

				$ligne->fk_product     = $obj->fk_product;
				$ligne->qty_asked      = $obj->qty_asked;
				$ligne->qty_shipped    = $obj->qty_shipped;
				$ligne->ref            = $obj->ref;
				$ligne->label          = $obj->label;
				$ligne->description    = $obj->description;
				$ligne->price          = $obj->price;
				$ligne->total_ht       = $obj->total_ht;

				$this->lignes[$i] = $ligne;
				$i++;
			}
			$this->db->free($resql);
		}

		return $this->lignes;
	}


	/**
	 *    \brief      Retourne le libell� du statut d'une expedition
	 *    \return     string      Libell�
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *		\brief      Renvoi le libell� d'un statut donn�
	 *    	\param      statut      Id statut
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libell�
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode==0)
		{
			if ($statut==-1) return $this->statuts[$statut];
			if ($statut==0) return $this->statuts[$statut];
			if ($statut==1) return $this->statuts[$statut];
		}
		if ($mode==1)
		{
			if ($statut==-1) return $this->statuts[$statut];
			if ($statut==0) return $this->statuts[$statut];
			if ($statut==1) return $this->statuts[$statut];
		}
		if ($mode == 4)
		{
			if ($statut==-1) return img_picto($langs->trans('StatusSendingCanceled'),'statut5').' '.$langs->trans('StatusSendingDraft');
			if ($statut==0) return img_picto($langs->trans('StatusSendingDraft'),'statut0').' '.$langs->trans('StatusSendingDraft');
			if ($statut==1) return img_picto($langs->trans('StatusSendingValidated'),'statut4').' '.$langs->trans('StatusSendingValidated');
		}
	}


	/**
	 *		\brief		Initialise object with default value to be used as example
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Charge tableau des id de soci�t� socids
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

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$socid = rand(1, $num_socs);
		$this->socid = $socids[$socid];
		$this->date_livraison = time();
		$this->note_public='SPECIMEN';
	}
	 
}



/**
 \class      LivraisonLigne
 \brief      Classe de gestion des lignes de bons de livraison
 */
class LivraisonLigne
{
	var $db;

	// From llx_expeditiondet
	var $qty;
	var $qty_asked;
	var $qty_shipped;
	var $price;
	var $fk_product;
	var $commande_ligne_id;
	var $label;       // Label produit
	var $description;  // Description produit
	var $ref;

	function LivraisonLigne($DB)
	{
		$this->db=$DB;
	}

}

?>
