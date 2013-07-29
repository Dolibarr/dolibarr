<?php
/* Copyright (C) 2003      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2011-2012 Philippe Grand	     <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/livraison/class/livraison.class.php
 *  \ingroup    delivery
 *  \brief      Fichier de la classe de gestion des bons de livraison
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
if (! empty($conf->propal->enabled))   require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


/**
 *  \class      Livraison
 *  \brief      Classe de gestion des bons de livraison
 */
class Livraison extends CommonObject
{
	public $element="delivery";
	public $fk_element="fk_livraison";
	public $table_element="livraison";

	var $id;
	var $brouillon;
	var $origin;
	var $origin_id;
	var $socid;
	var $ref_customer;
	var $statut;
	
	var $note_public;
	var $note_private;

	var $expedition_id;

	var $date_delivery;    // Date really received
	var $date_creation;
	var $date_valid;
	var $model_pdf;


	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->lines = array();
		$this->products = array();

		// List of short language codes for status
		$this->statuts[-1] = 'StatusSendingCanceled';
		$this->statuts[0]  = 'StatusSendingDraft';
		$this->statuts[1]  = 'StatusSendingValidated';
	}

	/**
	 *  Create delivery receipt in database
	 *
	 *  @param 	User	$user       Objet du user qui cree
	 *  @return int         		<0 si erreur, id livraison cree si ok
	 */
	function create($user)
	{
		global $conf;

		dol_syslog("Livraison::create");
		
		if (empty($this->model_pdf)) $this->model_pdf=$conf->global->LIVRAISON_ADDON_PDF;

		$error = 0;

        $now=dol_now();

		/* On positionne en mode brouillon le bon de livraison */
		$this->brouillon = 1;

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraison (";
		$sql.= "ref";
		$sql.= ", entity";
		$sql.= ", fk_soc";
		$sql.= ", ref_customer";
		$sql.= ", date_creation";
		$sql.= ", fk_user_author";
		$sql.= ", date_delivery";
		$sql.= ", fk_address";
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ", model_pdf";
		$sql.= ") VALUES (";
		$sql.= "'(PROV)'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$this->socid;
		$sql.= ", '".$this->db->escape($this->ref_customer)."'";
		$sql.= ", ".$this->db->idate($now);
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_delivery?"'".$this->db->idate($this->date_delivery)."'":"null");
		$sql.= ", ".($this->fk_delivery_address > 0 ? $this->fk_delivery_address : "null");
		$sql.= ", ".(!empty($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", ".(!empty($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", ".(!empty($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null");
		$sql.= ")";

		dol_syslog("Livraison::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."livraison");

			$numref = "(PROV".$this->id.")";

			$sql = "UPDATE ".MAIN_DB_PREFIX."livraison ";
			$sql.= "SET ref = '".$this->db->escape($numref)."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog("Livraison::create sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				if (! $conf->expedition_bon->enabled)
				{
					$commande = new Commande($this->db);
					$commande->id = $this->commande_id;
					$this->lines = $commande->fetch_lines();
				}


				/*
				 *  Insertion des produits dans la base
				 */
				$num=count($this->lines);
				for ($i = 0; $i < $num; $i++)
				{
					$origin_id=$this->lines[$i]->origin_line_id;
					if (! $origin_id) $origin_id=$this->lines[$i]->commande_ligne_id;	// For backward compatibility

					if (! $this->create_line($origin_id, $this->lines[$i]->qty, $this->lines[$i]->fk_product, $this->lines[$i]->description))
					{
						$error++;
					}
				}

				if (! $error && $this->id && $this->origin_id)
				{
					$ret = $this->add_object_linked();
					if (!$ret)
					{
						$error++;
					}

					if (! $conf->expedition_bon->enabled)
					{
						// TODO uniformiser les statuts
						$ret = $this->setStatut(2,$this->origin_id,$this->origin);
						if (! $ret)
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
					$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
					dol_syslog("Livraison::create Error -3 ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
				dol_syslog("Livraison::create Error -2 ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->lasterror()." - sql=".$this->db->lastqueryerror;
			dol_syslog("Livraison::create Error -1 ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Create a line
	 *
	 *	@param	string	$origin_id				Id of order
	 *	@param	string	$qty					Quantity
	 *	@param	string	$fk_product				Id of predefined product
	 *	@param	string	$description			Description
	 *	@return	int								<0 if KO, >0 if OK
	 */
	function create_line($origin_id, $qty, $fk_product, $description)
	{
		$error = 0;
		$idprod = $fk_product;
		$j = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."livraisondet (fk_livraison, fk_origin_line,";
		$sql.= " fk_product, description, qty)";
		$sql.= " VALUES (".$this->id.",".$origin_id.",";
		$sql.= " ".($idprod>0?$idprod:"null").",";
		$sql.= " ".($description?"'".$this->db->escape($description)."'":"null").",";
		$sql.= $qty.")";

		dol_syslog(get_class($this)."::create_line sql=".$sql, LOG_DEBUG);
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
	 * 	Load a delivery receipt
	 *
	 * 	@param	int		$id			Id of object to load
	 * 	@return	void
	 */
	function fetch($id)
	{
		global $conf;

		$sql = "SELECT l.rowid, l.fk_soc, l.date_creation, l.date_valid, l.ref, l.ref_customer, l.fk_user_author,";
		$sql.=" l.total_ht, l.fk_statut, l.fk_user_valid, l.note_private, l.note_public";
		$sql.= ", l.date_delivery, l.fk_address, l.model_pdf";
		$sql.= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql.= " FROM ".MAIN_DB_PREFIX."livraison as l";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = l.rowid AND el.targettype = '".$this->element."'";
		$sql.= " WHERE l.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                   = $obj->rowid;
				$this->date_delivery        = $this->db->jdate($obj->date_delivery);
				$this->date_creation        = $this->db->jdate($obj->date_creation);
				$this->date_valid           = $this->db->jdate($obj->date_valid);
				$this->ref                  = $obj->ref;
				$this->ref_customer         = $obj->ref_customer;
				$this->socid                = $obj->fk_soc;
				$this->statut               = $obj->fk_statut;
				$this->user_author_id       = $obj->fk_user_author;
				$this->user_valid_id        = $obj->fk_user_valid;
				$this->fk_delivery_address  = $obj->fk_address;
				$this->note                 = $obj->note_private; //TODO deprecated
				$this->note_private         = $obj->note_private;
				$this->note_public          = $obj->note_public;
				$this->modelpdf             = $obj->model_pdf;
				$this->origin               = $obj->origin;		// May be 'shipping'
				$this->origin_id            = $obj->origin_id;	// May be id of shipping

				$this->db->free($result);

				if ($this->statut == 0) $this->brouillon = 1;

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
				$this->error='Delivery with id '.$id.' not found sql='.$sql;
				dol_syslog(get_class($this).'::fetch Error '.$this->error, LOG_ERR);
				return -2;
			}
		}
		else
		{
			dol_syslog(get_class($this).'::fetch Error '.$this->error, LOG_ERR);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *        Validate object and update stock if option enabled
	 *
     *        @param 	User	$user        Object user that validate
     *        @return   int
	 */
	function valid($user)
	{
		global $conf, $langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::valid begin");

		$this->db->begin();

		$error = 0;

		if ($user->rights->expedition->livraison->valider)
		{
			if (! empty($conf->global->LIVRAISON_ADDON_NUMBER))
			{
				// Definition du nom de module de numerotation de commande
				$modName = $conf->global->LIVRAISON_ADDON_NUMBER;

				if (is_readable(DOL_DOCUMENT_ROOT .'/core/modules/livraison/'.$modName.'.php'))
				{
					require_once DOL_DOCUMENT_ROOT .'/core/modules/livraison/'.$modName.'.php';

					$now=dol_now();

					// Recuperation de la nouvelle reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->socid);

					// on verifie si le bon de livraison est en numerotation provisoire
					$livref = substr($this->ref, 1, 4);
					if ($livref == 'PROV')
					{
						$numref = $objMod->livraison_get_num($soc,$this);
					}

					// Tester si non deja au statut valide. Si oui, on arrete afin d'eviter
					// de decrementer 2 fois le stock.
					$sql = "SELECT ref";
					$sql.= " FROM ".MAIN_DB_PREFIX."livraison";
					$sql.= " WHERE ref = '".$numref."'";
					$sql.= " AND fk_statut <> 0";
					$sql.= " AND entity = ".$conf->entity;

					$resql=$this->db->query($sql);
					if ($resql)
					{
						$num = $this->db->num_rows($resql);
						if ($num > 0)
						{
							return 0;
						}
					}

					$sql = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
					$sql.= " ref='".$this->db->escape($numref)."'";
					$sql.= ", fk_statut = 1";
					$sql.= ", date_valid = ".$this->db->idate($now);
					$sql.= ", fk_user_valid = ".$user->id;
					$sql.= " WHERE rowid = ".$this->id;
					$sql.= " AND fk_statut = 0";

					$resql=$this->db->query($sql);
					if ($resql)
					{

						$this->oldref='';

						// Rename directory if dir was a temporary ref
						if (preg_match('/^[\(]?PROV/i', $this->ref))
						{
							// On renomme repertoire ($this->ref = ancienne ref, $numfa = nouvelle ref)
							// afin de ne pas perdre les fichiers attaches
							$oldref = dol_sanitizeFileName($this->ref);
							$newref = dol_sanitizeFileName($numref);
							$dirsource = $conf->expedition->dir_output.'/receipt/'.$oldref;
							$dirdest = $conf->expedition->dir_output.'/receipt/'.$newref;
							if (file_exists($dirsource))
							{
								dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

								if (@rename($dirsource, $dirdest))
								{
									$this->oldref = $oldref;

									dol_syslog("Rename ok");
									// Suppression ancien fichier PDF dans nouveau rep
									dol_delete_file($dirdest.'/'.$oldref.'*.*');
								}
							}
						}

						// Set new ref and current status
						if (! $error)
						{
							$this->ref = $numref;
							$this->statut = 1;
						}

						dol_syslog(get_class($this)."::valid ok");
					}
					else
					{
						$this->db->rollback();
						$this->error=$this->db->error()." - sql=$sql";
						dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
						return -1;
					}
				}
			}
		}
		else
		{
			$this->error="Non autorise";
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		// Appel des triggers
		include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
		$interface = new Interfaces($this->db);
		$result = $interface->run_triggers('DELIVERY_VALIDATE', $this, $user, $langs, $conf);
		// Fin appel triggers
		if ($result < 0)
		{
			$this->db->rollback();
			$this->error = $interface->errors;
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * 	Cree le bon de livraison depuis une expedition existante
	 *
	 *	@param	User	$user            Utilisateur qui cree
	 *	@param  int		$sending_id      Id de l'expedition qui sert de modele
	 *	@return	void
	 */
	function create_from_sending($user, $sending_id)
	{
		$expedition = new Expedition($this->db);
		$result=$expedition->fetch($sending_id);

		$this->lines = array();

		$num=count($expedition->lines);
		for ($i = 0; $i < $num; $i++)
		{
			$line = new LivraisonLigne($this->db);
			$line->origin_line_id    = $expedition->lines[$i]->origin_line_id;
			$line->libelle           = $expedition->lines[$i]->libelle;
			$line->description       = $expedition->lines[$i]->description;
			$line->qty               = $expedition->lines[$i]->qty_shipped;
			$line->fk_product        = $expedition->lines[$i]->fk_product;
			$line->ref               = $expedition->lines[$i]->ref;

			$this->lines[$i] = $line;
		}

		$this->origin               = $expedition->element;
		$this->origin_id            = $expedition->id;
		$this->note_private         = $expedition->note_private;
		$this->note_public          = $expedition->note_public;
		$this->fk_project           = $expedition->fk_project;
		$this->date_delivery        = $expedition->date_delivery;
		$this->fk_delivery_address  = $expedition->fk_delivery_address;
		$this->socid                = $expedition->socid;
		$this->ref_customer			= $expedition->ref_customer;

		return $this->create($user);
	}


	/**
	 * 	Add line
	 *
	 *	@param	int		$origin_id		Origin id
	 *	@param	int		$qty			Qty
	 *	@return	void
	 */
	function addline($origin_id, $qty)
	{
		$num = count($this->lines);
		$line = new LivraisonLigne($this->db);

		$line->origin_id = $origin_id;
		$line->qty = $qty;

		$this->lines[$num] = $line;
	}

	/**
	 *	Delete line
	 *
	 *	@param	int		$lineid		Line id
	 *	@return	void
	 */
	function deleteline($lineid)
	{
		if ($this->statut == 0)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
			$sql.= " WHERE rowid = ".$lineid;

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
	 * Delete object
	 *
	 * @return	void
	 */
	function delete()
	{
		global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$this->db->begin();

		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."livraisondet";
		$sql.= " WHERE fk_livraison = ".$this->id;
		if ($this->db->query($sql))
		{
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) $error++;

			if (! $error)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."livraison";
				$sql.= " WHERE rowid = ".$this->id;
				if ($this->db->query($sql))
				{
					$this->db->commit();

					// On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if (! empty($conf->expedition->dir_output))
					{
						$dir = $conf->expedition->dir_output . '/receipt/' . $ref ;
						$file = $dir . '/' . $ref . '.pdf';
						if (file_exists($file))
						{
							if (!dol_delete_file($file))
							{
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

					// Call triggers
					include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('DELIVERY_DELETE',$this,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// End call triggers

					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error=$this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Renvoie nom clicable (avec eventuellement le picto)
	 *
	 *	@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';
		$urlOption='';


		$lien = '<a href="'.DOL_URL_ROOT.'/livraison/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='sending';
		$label=$langs->trans("ShowReceiving").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	/**
	 *	Load lines
	 *
	 *	@return	void
	 */
	function fetch_lines()
	{
		$this->lines = array();

		$sql = "SELECT ld.rowid, ld.fk_product, ld.description, ld.subprice, ld.total_ht, ld.qty as qty_shipped,";
		$sql.= " cd.qty as qty_asked, cd.label as custom_label,";
		$sql.= " p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."livraisondet as ld";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on p.rowid = ld.fk_product";
		$sql.= " WHERE ld.fk_origin_line = cd.rowid";
		$sql.= " AND ld.fk_livraison = ".$this->id;

		dol_syslog(get_class($this)."::fetch_lines sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$line = new LivraisonLigne($this->db);

				$obj = $this->db->fetch_object($resql);

				$line->label			= $obj->custom_label;
				$line->description		= $obj->description;
				$line->fk_product		= $obj->fk_product;
				$line->qty_asked		= $obj->qty_asked;
				$line->qty_shipped		= $obj->qty_shipped;

				$line->ref				= $obj->product_ref;		// deprecated
				$line->libelle			= $obj->product_label;		// deprecated
				$line->product_label	= $obj->product_label;		// Product label
				$line->product_ref		= $obj->product_ref;		// Product ref
				$line->product_desc		= $obj->product_desc;		// Product description
				$line->product_type		= $obj->fk_product_type;

				$line->price			= $obj->price;
				$line->total_ht			= $obj->total_ht;

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($resql);
		}

		return $this->lines;
	}


	/**
	 *  Retourne le libelle du statut d'une expedition
	 *
	 *  @param	int			$mode		Mode
	 *  @return string      			Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *  @param	int			$statut     Id statut
	 *  @param  int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string					Label
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode==0)
		{
			if ($statut==-1) return $langs->trans('StatusSendingCanceled');
			if ($statut==0)  return $langs->trans('StatusSendingDraft');
			if ($statut==1)  return $langs->trans('StatusSendingValidated');
		}
		if ($mode==1)
		{
			if ($statut==-1) return $langs->trans($this->statuts[$statut]);
			if ($statut==0)  return $langs->trans($this->statuts[$statut]);
			if ($statut==1)  return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 4)
		{
			if ($statut==-1) return img_picto($langs->trans('StatusSendingCanceled'),'statut5').' '.$langs->trans('StatusSendingCanceled');
			if ($statut==0)  return img_picto($langs->trans('StatusSendingDraft'),'statut0').' '.$langs->trans('StatusSendingDraft');
			if ($statut==1)  return img_picto($langs->trans('StatusSendingValidated'),'statut4').' '.$langs->trans('StatusSendingValidated');
		}
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		$now=dol_now();

		// Charge tableau des produits prodids
		$prodids = array();
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE entity IN (".getEntity('product', 1).")";
		$sql.= " AND tosell = 1";
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
		$this->socid = 1;
		$this->date_delivery = $now;
		$this->note_public='Pulbic note';
		$this->note_private='Private note';

		$i=0;
		$line=new LivraisonLigne($this->db);
		$line->fk_product     = $prodids[0];
		$line->qty_asked      = 10;
		$line->qty_shipped    = 9;
		$line->ref            = 'REFPROD';
		$line->label          = 'Specimen';
		$line->description    = 'Description';
		$line->price          = 100;
		$line->total_ht       = 100;

		$this->lines[$i] = $line;
	}

	/**
	 *  Renvoie la quantite de produit restante a livrer pour une commande
	 *
	 *  @return     array		Product remaining to be delivered
	 *  TODO use new function
	 */
	function getRemainingDelivered()
	{
		global $langs;

		// Get the linked object
		$this->fetchObjectLinked('','',$this->id,$this->element);
		//var_dump($this->linkedObjectIds);
		// Get the product ref and qty in source
		$sqlSourceLine = "SELECT st.rowid, st.description, st.qty";
		$sqlSourceLine.= ", p.ref, p.label";
		$sqlSourceLine.= " FROM ".MAIN_DB_PREFIX.$this->linkedObjectIds[0]['type']."det as st";
		$sqlSourceLine.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON st.fk_product = p.rowid";
		$sqlSourceLine.= " WHERE fk_".$this->linked_object[0]['type']." = ".$this->linked_object[0]['linkid'];

		$resultSourceLine = $this->db->query($sqlSourceLine);
		if ($resultSourceLine)
		{
			$num_lines = $this->db->num_rows($resultSourceLine);
			$i = 0;
			$resultArray = array();
			while ($i < $num_lines)
			{
				$objSourceLine = $this->db->fetch_object($resultSourceLine);

				// Recupere les lignes de la source deja livrees
				$sql = "SELECT ld.fk_origin_line, sum(ld.qty) as qty";
				$sql.= " FROM ".MAIN_DB_PREFIX."livraisondet as ld, ".MAIN_DB_PREFIX."livraison as l,";
				$sql.= " ".MAIN_DB_PREFIX.$this->linked_object[0]['type']." as c";
				$sql.= ", ".MAIN_DB_PREFIX.$this->linked_object[0]['type']."det as cd";
				$sql.= " WHERE ld.fk_livraison = l.rowid";
				$sql.= " AND ld.fk_origin_line = cd.rowid";
				$sql.= " AND cd.fk_".$this->linked_object[0]['type']." = c.rowid";
				$sql.= " AND cd.fk_".$this->linked_object[0]['type']." = ".$this->linked_object[0]['linkid'];
				$sql.= " AND ld.fk_origin_line = ".$objSourceLine->rowid;
				$sql.= " GROUP BY ld.fk_origin_line";

				$result = $this->db->query($sql);
				$row = $this->db->fetch_row($result);

				if ($objSourceLine->qty - $row[1] > 0)
				{
					if ($row[0] == $objSourceLine->rowid)
					{
						$array[$i]['qty'] = $objSourceLine->qty - $row[1];
					}
					else
					{
						$array[$i]['qty'] = $objSourceLine->qty;
					}

					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label?$objSourceLine->label:$objSourceLine->description;
				}
				elseif($objSourceLine->qty - $row[1] < 0)
				{
					$array[$i]['qty'] = $objSourceLine->qty - $row[1]. " Erreur livraison !";
					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label?$objSourceLine->label:$objSourceLine->description;
				}

					$i++;
			}
			return $array;
		}
		else
		{
			$this->error=$this->db->error()." - sql=$sqlSourceLine";
			dol_syslog(get_class($this)."::getRemainingDelivered ".$this->error, LOG_ERR);
			return -1;
		}
	}

}



/**
 *  Classe de gestion des lignes de bons de livraison
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
	var $origin_id;
	var $label;       // Label produit
	var $description;  // Description produit
	var $ref;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}

}

?>