<?php
/* Copyright (C) 2003      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2014 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2011-2018 Philippe Grand	     <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry	     <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 Marcos García         <marcosgdf@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/delivery/class/delivery.class.php
 *  \ingroup    delivery
 *  \brief      Delivery Order Management Class File
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
if (!empty($conf->propal->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (!empty($conf->commande->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}


/**
 *  Class to manage receptions
 */
class Delivery extends CommonObject
{
	use CommonIncoterm;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = "delivery";

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = "fk_delivery";

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = "delivery";

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = "deliverydet";

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'sending';

	/**
	 * @var int draft status
	 */
	public $draft;

	/**
	 * @var int thirdparty id
	 */
	public $socid;

	/**
	 * @var string ref custome
	 */
	public $ref_customer;

	/**
	 * @var integer|string Date really received
	 */
	public $date_delivery;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_valid
	 */
	public $date_valid;

	/**
	 * @var string model pdf
	 */
	public $model_pdf;

	public $lines = array();


	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// List of short language codes for status
		$this->statuts[-1] = 'StatusDeliveryCanceled';
		$this->statuts[0]  = 'StatusDeliveryDraft';
		$this->statuts[1]  = 'StatusDeliveryValidated';
	}

	/**
	 *  Create delivery receipt in database
	 *
	 *  @param 	User	$user       Objet du user qui cree
	 *  @return int         		<0 si erreur, id delivery cree si ok
	 */
	public function create($user)
	{
		global $conf;

		dol_syslog("Delivery::create");

		if (empty($this->model_pdf)) {
			$this->model_pdf = $conf->global->DELIVERY_ADDON_PDF;
		}

		$error = 0;

		$now = dol_now();

		/* Delivery note as draft On positionne en mode draft le bon de livraison */
		$this->draft = 1;

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."delivery (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", fk_soc";
		$sql .= ", ref_customer";
		$sql .= ", date_creation";
		$sql .= ", fk_user_author";
		$sql .= ", date_delivery";
		$sql .= ", fk_address";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", model_pdf";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ") VALUES (";
		$sql .= "'(PROV)'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((int) $this->socid);
		$sql .= ", '".$this->db->escape($this->ref_customer)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".($this->date_delivery ? "'".$this->db->idate($this->date_delivery)."'" : "null");
		$sql .= ", ".($this->fk_delivery_address > 0 ? $this->fk_delivery_address : "null");
		$sql .= ", ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ")";

		dol_syslog("Delivery::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."delivery");

			$numref = "(PROV".$this->id.")";

			$sql = "UPDATE ".MAIN_DB_PREFIX."delivery ";
			$sql .= "SET ref = '".$this->db->escape($numref)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog("Delivery::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$conf->expedition_bon->enabled) {
					$commande = new Commande($this->db);
					$commande->id = $this->commande_id;
					$commande->fetch_lines();
				}


				/*
				 *  Inserting products into the database
				 */
				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					$origin_id = $this->lines[$i]->origin_line_id;
					if (!$origin_id) {
						$origin_id = $this->lines[$i]->commande_ligne_id; // For backward compatibility
					}

					if (!$this->create_line($origin_id, $this->lines[$i]->qty, $this->lines[$i]->fk_product, $this->lines[$i]->description, $this->lines[$i]->array_options)) {
						$error++;
					}
				}

				if (!$error && $this->id && $this->origin_id) {
					$ret = $this->add_object_linked();
					if (!$ret) {
						$error++;
					}

					if (!$conf->expedition_bon->enabled) {
						// TODO standardize status uniformiser les statuts
						$ret = $this->setStatut(2, $this->origin_id, $this->origin);
						if (!$ret) {
							$error++;
						}
					}
				}

				if (!$error) {
					$this->db->commit();
					return $this->id;
				} else {
					$error++;
					$this->error = $this->db->lasterror()." - sql=".$this->db->lastqueryerror;
					$this->db->rollback();
					return -3;
				}
			} else {
				$error++;
				$this->error = $this->db->lasterror()." - sql=".$this->db->lastqueryerror;
				$this->db->rollback();
				return -2;
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror()." - sql=".$this->db->lastqueryerror;
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create a line
	 *
	 *	@param	string	$origin_id				Id of order
	 *	@param	string	$qty					Quantity
	 *	@param	string	$fk_product				Id of predefined product
	 *	@param	string	$description			Description
	 *  @param	array	$array_options			Array options
	 *	@return	int								<0 if KO, >0 if OK
	 */
	public function create_line($origin_id, $qty, $fk_product, $description, $array_options = null)
	{
		// phpcs:enable
		$error = 0;
		$idprod = $fk_product;
		$j = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deliverydet (fk_delivery, fk_origin_line,";
		$sql .= " fk_product, description, qty)";
		$sql .= " VALUES (".$this->id.",".((int) $origin_id).",";
		$sql .= " ".($idprod > 0 ? ((int) $idprod) : "null").",";
		$sql .= " ".($description ? "'".$this->db->escape($description)."'" : "null").",";
		$sql .= (price2num($qty, 'MS')).")";

		dol_syslog(get_class($this)."::create_line", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$error++;
		}

		$id = $this->db->last_insert_id(MAIN_DB_PREFIX."deliverydet");

		if (is_array($array_options) && count($array_options) > 0) {
			$line = new DeliveryLine($this->db);
			$line->id = $id;
			$line->array_options = $array_options;
			$result = $line->insertExtraFields();
		}

		if ($error == 0) {
			return 1;
		}
	}

	/**
	 * 	Load a delivery receipt
	 *
	 * 	@param	int		$id			Id of object to load
	 * 	@return	integer
	 */
	public function fetch($id)
	{
		global $conf;

		$sql = "SELECT l.rowid, l.fk_soc, l.date_creation, l.date_valid, l.ref, l.ref_customer, l.fk_user_author,";
		$sql .= " l.total_ht, l.fk_statut, l.fk_user_valid, l.note_private, l.note_public";
		$sql .= ", l.date_delivery, l.fk_address, l.model_pdf";
		$sql .= ", el.fk_source as origin_id, el.sourcetype as origin";
		$sql .= ', l.fk_incoterms, l.location_incoterms';
		$sql .= ", i.libelle as label_incoterms";
		$sql .= " FROM ".MAIN_DB_PREFIX."delivery as l";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = l.rowid AND el.targettype = '".$this->db->escape($this->element)."'";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON l.fk_incoterms = i.rowid';
		$sql .= " WHERE l.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
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
				$this->model_pdf            = $obj->model_pdf;
				$this->modelpdf             = $obj->model_pdf; // deprecated
				$this->origin               = $obj->origin; // May be 'shipping'
				$this->origin_id            = $obj->origin_id; // May be id of shipping

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;
				$this->db->free($result);

				if ($this->statut == 0) {
					$this->draft = 1;
				}

				// Retrieve all extrafields
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				// Load lines
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}

				return 1;
			} else {
				$this->error = 'Delivery with id '.$id.' not found sql='.$sql;
				dol_syslog(get_class($this).'::fetch Error '.$this->error, LOG_ERR);
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Validate object and update stock if option enabled
	 *
	 *  @param  User    $user       Object user that validate
	 *  @param  int     $notrigger  1=Does not execute triggers, 0= execute triggers
	 *  @return int
	 */
	public function valid($user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::valid begin");

		$this->db->begin();

		$error = 0;

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->expedition->delivery->creer))
			|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->expedition->delivery_advance->validate))) {
			if (!empty($conf->global->DELIVERY_ADDON_NUMBER)) {
				// Setting the command numbering module name
				$modName = $conf->global->DELIVERY_ADDON_NUMBER;

				if (is_readable(DOL_DOCUMENT_ROOT.'/core/modules/delivery/'.$modName.'.php')) {
					require_once DOL_DOCUMENT_ROOT.'/core/modules/delivery/'.$modName.'.php';

					$now = dol_now();

					// Retrieving the new reference
					$objMod = new $modName($this->db);
					$soc = new Societe($this->db);
					$soc->fetch($this->socid);

					if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
						$numref = $objMod->delivery_get_num($soc, $this);
					} else {
						$numref = $this->ref;
					}
					$this->newref = dol_sanitizeFileName($numref);

					// Test if is not already in valid status. If so, we stop to avoid decrementing the stock twice.
					$sql = "SELECT ref";
					$sql .= " FROM ".MAIN_DB_PREFIX."delivery";
					$sql .= " WHERE ref = '".$this->db->escape($numref)."'";
					$sql .= " AND fk_statut <> 0";
					$sql .= " AND entity = ".((int) $conf->entity);

					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						if ($num > 0) {
							return 0;
						}
					}

					$sql = "UPDATE ".MAIN_DB_PREFIX."delivery SET";
					$sql .= " ref='".$this->db->escape($numref)."'";
					$sql .= ", fk_statut = 1";
					$sql .= ", date_valid = '".$this->db->idate($now)."'";
					$sql .= ", fk_user_valid = ".$user->id;
					$sql .= " WHERE rowid = ".((int) $this->id);
					$sql .= " AND fk_statut = 0";

					$resql = $this->db->query($sql);
					if (!$resql) {
						dol_print_error($this->db);
						$this->error = $this->db->lasterror();
						$error++;
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('DELIVERY_VALIDATE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}

					if (!$error) {
						$this->oldref = $this->ref;

						// Rename directory if dir was a temporary ref
						if (preg_match('/^[\(]?PROV/i', $this->ref)) {
							// Now we rename also files into index
							$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'expedition/receipt/".$this->db->escape($this->newref)."'";
							$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'expedition/receipt/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
							$resql = $this->db->query($sql);
							if (!$resql) {
								$error++; $this->error = $this->db->lasterror();
							}

							// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
							$oldref = dol_sanitizeFileName($this->ref);
							$newref = dol_sanitizeFileName($numref);
							$dirsource = $conf->expedition->dir_output.'/receipt/'.$oldref;
							$dirdest = $conf->expedition->dir_output.'/receipt/'.$newref;
							if (!$error && file_exists($dirsource)) {
								dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

								if (@rename($dirsource, $dirdest)) {
									dol_syslog("Rename ok");
									// Rename docs starting with $oldref with $newref
									$listoffiles = dol_dir_list($conf->expedition->dir_output.'/receipt/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
									foreach ($listoffiles as $fileentry) {
										$dirsource = $fileentry['name'];
										$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
										$dirsource = $fileentry['path'].'/'.$dirsource;
										$dirdest = $fileentry['path'].'/'.$dirdest;
										@rename($dirsource, $dirdest);
									}
								}
							}
						}

						// Set new ref and current status
						if (!$error) {
							$this->ref = $numref;
							$this->statut = 1;
						}

						dol_syslog(get_class($this)."::valid ok");
					}

					if (!$error) {
						$this->db->commit();
						return 1;
					} else {
						$this->db->rollback();
						return -1;
					}
				}
			}
		} else {
			$this->error = "Non autorise";
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Creating the delivery slip from an existing shipment
	 *
	 *	@param	User	$user            User who creates
	 *	@param  int		$sending_id      Id of the expedition that serves as a model
	 *	@return	integer
	 */
	public function create_from_sending($user, $sending_id)
	{
		// phpcs:enable
		$expedition = new Expedition($this->db);
		$result = $expedition->fetch($sending_id);

		$this->lines = array();

		$num = count($expedition->lines);
		for ($i = 0; $i < $num; $i++) {
			$line = new DeliveryLine($this->db);
			$line->origin_line_id    = $expedition->lines[$i]->origin_line_id;
			$line->label             = $expedition->lines[$i]->label;
			$line->description       = $expedition->lines[$i]->description;
			$line->qty               = $expedition->lines[$i]->qty_shipped;
			$line->fk_product        = $expedition->lines[$i]->fk_product;
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($expedition->lines[$i]->array_options) && count($expedition->lines[$i]->array_options) > 0) { // For avoid conflicts if trigger used
				$line->array_options = $expedition->lines[$i]->array_options;
			}
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
		$this->ref_customer         = $expedition->ref_customer;

		//Incoterms
		$this->fk_incoterms = $expedition->fk_incoterms;
		$this->location_incoterms = $expedition->location_incoterms;

		return $this->create($user);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Update a livraison line (only extrafields)
	 *
	 * @param 	int		$id					Id of line (livraison line)
	 * @param	array		$array_options		extrafields array
	 * @return	int							<0 if KO, >0 if OK
	 */
	public function update_line($id, $array_options = 0)
	{
		// phpcs:enable
		global $conf;
		$error = 0;

		if ($id > 0 && !$error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options) > 0) { // For avoid conflicts if trigger used
			$line = new DeliveryLine($this->db);
			$line->array_options = $array_options;
			$line->id = $id;
			$result = $line->insertExtraFields();

			if ($result < 0) {
				$this->error[] = $line->error;
				$error++;
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}


	/**
	 * 	Add line
	 *
	 *	@param	int		$origin_id				Origin id
	 *	@param	int		$qty					Qty
	 *  @param	array	$array_options			Array options
	 *	@return	void
	 */
	public function addline($origin_id, $qty, $array_options = null)
	{
		global $conf;

		$num = count($this->lines);
		$line = new DeliveryLine($this->db);

		$line->origin_id = $origin_id;
		$line->qty = $qty;
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options) > 0) { // For avoid conflicts if trigger used
			$line->array_options = $array_options;
		}
		$this->lines[$num] = $line;
	}

	/**
	 *	Delete line
	 *
	 *	@param	int		$lineid		Line id
	 *	@return	integer|null
	 */
	public function deleteline($lineid)
	{
		if ($this->statut == 0) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
			$sql .= " WHERE rowid = ".((int) $lineid);

			if ($this->db->query($sql)) {
				$this->update_price();

				return 1;
			} else {
				return 0;
			}
		}
	}

	/**
	 * Delete object
	 *
	 * @return	integer
	 */
	public function delete()
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$this->db->begin();

		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."deliverydet";
		$sql .= " WHERE fk_delivery = ".((int) $this->id);
		if ($this->db->query($sql)) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}

			if (!$error) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."delivery";
				$sql .= " WHERE rowid = ".((int) $this->id);
				if ($this->db->query($sql)) {
					$this->db->commit();

					// Deleting pdf folder's draft On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if (!empty($conf->expedition->dir_output)) {
						$dir = $conf->expedition->dir_output.'/receipt/'.$ref;
						$file = $dir.'/'.$ref.'.pdf';
						if (file_exists($file)) {
							if (!dol_delete_file($file)) {
								return 0;
							}
						}
						if (file_exists($dir)) {
							if (!dol_delete_dir($dir)) {
								$this->error = $langs->trans("ErrorCanNotDeleteDir", $dir);
								return 0;
							}
						}
					}

					// Call trigger
					$result = $this->call_trigger('DELIVERY_DELETE', $user);
					if ($result < 0) {
						$this->db->rollback();
						return -4;
					}
					// End call triggers

					return 1;
				} else {
					$this->error = $this->db->lasterror()." - sql=$sql";
					$this->db->rollback();
					return -3;
				}
			} else {
				$this->error = $this->db->lasterror()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param  int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $save_lastsearch_value = -1)
	{
		global $langs;

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("ShowReceiving").'</u>:<br>';
		$label .= '<b>'.$langs->trans("Status").'</b>: '.$this->ref;

		$url = DOL_URL_ROOT.'/delivery/card.php?id='.$this->id;

		//if ($option !== 'nolink')
		//{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}
		//}


		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		if ($withpicto) {
			$result .= ($linkstart.img_object($label, $this->picto, 'class="classfortooltip"').$linkend);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ' ';
		}
		$result .= $linkstart.$this->ref.$linkend;
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load lines
	 *
	 *	@return	void
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		$this->lines = array();

		$sql = "SELECT ld.rowid, ld.fk_product, ld.description, ld.subprice, ld.total_ht, ld.qty as qty_shipped, ld.fk_origin_line, ";
		$sql .= " cd.qty as qty_asked, cd.label as custom_label, cd.fk_unit,";
		$sql .= " p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc,";
		$sql .= " p.weight, p.weight_units,  p.width, p.width_units, p.length, p.length_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units, p.tobatch as product_tobatch";
		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."deliverydet as ld";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on p.rowid = ld.fk_product";
		$sql .= " WHERE ld.fk_origin_line = cd.rowid";
		$sql .= " AND ld.fk_delivery = ".((int) $this->id);

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$line = new DeliveryLine($this->db);

				$obj = $this->db->fetch_object($resql);

				$line->id = $obj->rowid;
				$line->label = $obj->custom_label;
				$line->description		= $obj->description;
				$line->fk_product = $obj->fk_product;
				$line->qty_asked = $obj->qty_asked;
				$line->qty_shipped		= $obj->qty_shipped;

				$line->ref = $obj->product_ref; // deprecated
				$line->libelle = $obj->product_label; // deprecated
				$line->product_label	= $obj->product_label; // Product label
				$line->product_ref = $obj->product_ref; // Product ref
				$line->product_desc		= $obj->product_desc; // Product description
				$line->product_type		= $obj->fk_product_type;
				$line->fk_origin_line = $obj->fk_origin_line;

				$line->price = $obj->price;
				$line->total_ht = $obj->total_ht;

				// units
				$line->weight         	= $obj->weight;
				$line->weight_units   	= $obj->weight_units;
				$line->width         	= $obj->width;
				$line->width_units   	= $obj->width_units;
				$line->height         	= $obj->height;
				$line->height_units   	= $obj->height_units;
				$line->length         	= $obj->length;
				$line->length_units   	= $obj->length_units;
				$line->surface        	= $obj->surface;
				$line->surface_units = $obj->surface_units;
				$line->volume         	= $obj->volume;
				$line->volume_units   	= $obj->volume_units;

				$line->fk_unit = $obj->fk_unit;
				$line->fetch_optionals();

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
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status     	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string					Label
	 */
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[-1] = $langs->transnoentitiesnoconv('StatusDeliveryCanceled');
			$this->labelStatus[0] = $langs->transnoentitiesnoconv('StatusDeliveryDraft');
			$this->labelStatus[1] = $langs->transnoentitiesnoconv('StatusDeliveryValidated');
			$this->labelStatusShort[-1] = $langs->transnoentitiesnoconv('StatusDeliveryCanceled');
			$this->labelStatusShort[0] = $langs->transnoentitiesnoconv('StatusDeliveryDraft');
			$this->labelStatusShort[1] = $langs->transnoentitiesnoconv('StatusDeliveryValidated');
		}

		$statusType = 'status0';
		if ($status == -1) {
			$statusType = 'status5';
		}
		if ($status == 1) {
			$statusType = 'status4';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	public function initAsSpecimen()
	{
		global $user, $langs, $conf;

		$now = dol_now();

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= " AND tosell = 1";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date_delivery = $now;
		$this->note_public = 'Public note';
		$this->note_private = 'Private note';

		$i = 0;
		$line = new DeliveryLine($this->db);
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
	public function getRemainingDelivered()
	{
		global $langs;

		// Get the linked object
		$this->fetchObjectLinked('', '', $this->id, $this->element);
		//var_dump($this->linkedObjectsIds);
		// Get the product ref and qty in source
		$sqlSourceLine = "SELECT st.rowid, st.description, st.qty";
		$sqlSourceLine .= ", p.ref, p.label";
		$sqlSourceLine .= " FROM ".MAIN_DB_PREFIX.$this->linkedObjectsIds[0]['type']."det as st";
		$sqlSourceLine .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON st.fk_product = p.rowid";
		$sqlSourceLine .= " WHERE fk_".$this->linked_object[0]['type']." = ".((int) $this->linked_object[0]['linkid']);

		$resultSourceLine = $this->db->query($sqlSourceLine);
		if ($resultSourceLine) {
			$num_lines = $this->db->num_rows($resultSourceLine);
			$i = 0;
			$resultArray = array();
			while ($i < $num_lines) {
				$objSourceLine = $this->db->fetch_object($resultSourceLine);

				// Get lines of sources alread delivered
				$sql = "SELECT ld.fk_origin_line, sum(ld.qty) as qty";
				$sql .= " FROM ".MAIN_DB_PREFIX."deliverydet as ld, ".MAIN_DB_PREFIX."delivery as l,";
				$sql .= " ".MAIN_DB_PREFIX.$this->linked_object[0]['type']." as c";
				$sql .= ", ".MAIN_DB_PREFIX.$this->linked_object[0]['type']."det as cd";
				$sql .= " WHERE ld.fk_delivery = l.rowid";
				$sql .= " AND ld.fk_origin_line = cd.rowid";
				$sql .= " AND cd.fk_".$this->linked_object[0]['type']." = c.rowid";
				$sql .= " AND cd.fk_".$this->linked_object[0]['type']." = ".((int) $this->linked_object[0]['linkid']);
				$sql .= " AND ld.fk_origin_line = ".((int) $objSourceLine->rowid);
				$sql .= " GROUP BY ld.fk_origin_line";

				$result = $this->db->query($sql);
				$row = $this->db->fetch_row($result);

				if ($objSourceLine->qty - $row[1] > 0) {
					if ($row[0] == $objSourceLine->rowid) {
						$array[$i]['qty'] = $objSourceLine->qty - $row[1];
					} else {
						$array[$i]['qty'] = $objSourceLine->qty;
					}

					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label ? $objSourceLine->label : $objSourceLine->description;
				} elseif ($objSourceLine->qty - $row[1] < 0) {
					$array[$i]['qty'] = $objSourceLine->qty - $row[1]." Erreur livraison !";
					$array[$i]['ref'] = $objSourceLine->ref;
					$array[$i]['label'] = $objSourceLine->label ? $objSourceLine->label : $objSourceLine->description;
				}

				$i++;
			}
			return $array;
		} else {
			$this->error = $this->db->error()." - sql=$sqlSourceLine";
			return -1;
		}
	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User			$user        		Objet utilisateur qui modifie
	 *	@param      integer 		$delivery_date     Delivery date
	 *	@return     int         						<0 if KO, >0 if OK
	 */
	public function setDeliveryDate($user, $delivery_date)
	{
		if ($user->rights->expedition->creer) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."delivery";
			$sql .= " SET date_delivery = ".($delivery_date ? "'".$this->db->idate($delivery_date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setDeliveryDate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->date_delivery = $delivery_date;
				return 1;
			} else {
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			return -2;
		}
	}

	/**
	 *	Create object on disk
	 *
	 *	@param     string		$modele			force le modele a utiliser ('' to not force)
	 * 	@param     Translate	$outputlangs	Object langs to use for output
	 *  @param     int			$hidedetails    Hide details of lines
	 *  @param     int			$hidedesc       Hide description
	 *  @param     int			$hideref        Hide ref
	 *  @return    int             				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $user, $langs;

		$langs->load("deliveries");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'typhon';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->DELIVERY_ADDON_PDF)) {
				$modele = $conf->global->DELIVERY_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/delivery/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'delivery'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}



/**
 *  Management class of delivery note lines
 */
class DeliveryLine extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'deliverydet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'deliverydet';

	// From llx_expeditiondet
	public $qty;
	public $qty_asked;
	public $qty_shipped;
	public $price;
	public $fk_product;
	public $origin_id;

	/**
	 * @var string delivery note lines label
	 */
	public $label;

	/**
	 * @var string product description
	 */
	public $description;

	/**
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref;
	/**
	 * @deprecated
	 * @see product_label;
	 */
	public $libelle;

	public $origin_line_id;

	public $product_ref;
	public $product_label;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
}
