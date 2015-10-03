<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Destailleur Laurent		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2008		Raphael Bertrand		<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015	Marcos García			<marcosgdf@gmail.com>
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
 *	\file       htdocs/contrat/class/contrat.class.php
 *	\ingroup    contrat
 *	\brief      File of class to manage contracts
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';

/**
 *	Class to manage contracts
 */
class Contrat extends CommonObject
{
	public $element='contrat';
	public $table_element='contrat';
	public $table_element_line='contratdet';
	public $fk_element='fk_contrat';
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * Supplier reference of the contract
	 * @var string
	 */
	var $ref_supplier;

	/**
	 * Client id linked to the contract
	 * @var int
	 */
	var $socid;
	var $societe;		// Objet societe

	/**
	 * Status of the contract
	 * @var int
	 */
	var $statut=0;		// 0=Draft,
	var $product;

	/**
	 * @var int		Id of user author of the contract
	 */
	public $fk_user_author;

	/**
	 * TODO: Which is the correct one?
	 * Author of the contract
	 * @var int
	 */
	public $user_author_id;

	/**
	 * @var User 	Object user that create the contract. Set by the info method.
	 */
	public $user_creation;

	/**
	 * @var User 	Object user that close the contract. Set by the info method.
	 */
	public $user_cloture;

	/**
	 * @var int		Date of creation
	 */
	var $date_creation;

	/**
	 * @var int		Date of last modification. Not filled until you call ->info()
	 */
	public $date_modification;

	/**
	 * @var int		Date of validation
	 */
	var $date_validation;

	/**
	 * @var int		Date when contract was signed
	 */
	var $date_contrat;

	/**
	 * @var int		Date of contract closure
	 * @deprecated we close contract lines, not a contract
	 */
	var $date_cloture;

	var $commercial_signature_id;
	var $commercial_suivi_id;

	/**
	 * @deprecated Use fk_project instead
	 * @see fk_project
	 */
	var $fk_projet;

	var $extraparams=array();

	/**
	 * @var ContratLigne[]		Contract lines
	 */
	var $lines=array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc		Thirdparty object
	 *	@return string					free reference for contract
	 */
	function getNextNumRef($soc)
	{
		global $db, $langs, $conf;
		$langs->load("contracts");

		if (!empty($conf->global->CONTRACT_ADDON))
		{
			$mybool = false;

			$file = $conf->global->CONTRACT_ADDON.".php";
			$classname = $conf->global->CONTRACT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/contract/");

				// Load file with numbering class (if found)
				$mybool|=@include_once $dir.$file;
			}

			if (! $mybool)
			{
				dol_print_error('',"Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($soc,$this);

			if ( $numref != "")
			{
				return $numref;
			}
			else
			{
				$this->error = $obj->error;
				dol_print_error($db,get_class($this)."::getNextValue ".$obj->error);
				return "";
			}
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete");
			return "";
		}
	}

	/**
	 *  Activate a contract line
	 *
	 *  @param	User		$user       Objet User who activate contract
	 *  @param  int			$line_id    Id of line to activate
	 *  @param  int			$date       Date d'ouverture
	 *  @param  int|string	$date_end   Date fin prevue
	 * 	@param	string		$comment	A comment typed by user
	 *  @return int         			<0 if KO, >0 if OK
	 */
	function active_line($user, $line_id, $date, $date_end='', $comment='')
	{
		global $langs,$conf;

		$error=0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 4,";
		$sql.= " date_ouverture = ".(dol_strlen($date)!=0?"'".$this->db->idate($date)."'":"null").",";
		$sql.= " date_fin_validite = ".(dol_strlen($date_end)!=0?"'".$this->db->idate($date_end)."'":"null").",";
		$sql.= " fk_user_ouverture = ".$user->id.",";
		$sql.= " date_cloture = null,";
		$sql.= " commentaire = '".$this->db->escape($comment)."'";
		$sql.= " WHERE rowid = ".$line_id . " AND (statut = 0 OR statut = 3 OR statut = 5)";

		dol_syslog(get_class($this)."::active_line", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
            // Call trigger
            $result=$this->call_trigger('CONTRACT_SERVICE_ACTIVATE',$user);
            if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            // End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Close a contract line
	 *
	 *  @param	User		$user       Objet User who close contract
	 *  @param  int			$line_id    Id of line to close
	 *  @param  int			$date_end	Date end
	 * 	@param	string		$comment	A comment typed by user
	 *  @return int         			<0 if KO, >0 if OK
	 */
	function close_line($user, $line_id, $date_end, $comment='')
	{
		global $langs,$conf;

		$error=0;

		// statut actif : 4

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = 5,";
		$sql.= " date_cloture = '".$this->db->idate($date_end)."',";
		$sql.= " fk_user_cloture = ".$user->id.",";
		$sql.= " commentaire = '".$this->db->escape($comment)."'";
		$sql.= " WHERE rowid = ".$line_id . " AND statut = 4";

		$resql = $this->db->query($sql);
		if ($resql)
		{
            // Call trigger
            $result=$this->call_trigger('CONTRACT_SERVICE_CLOSE',$user);
            if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            // End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Close all lines of a contract
	 *
	 *  @param	User		$user      Object User making action
	 *	@return	void
	 */
	function cloture($user)
	{
		$this->db->begin();

		// Load lines
		$this->fetch_lines();

		$ok=true;
		foreach($this->lines as $contratline)
		{
			// Close line not already closed
	        if ($contratline->statut != 5)
	        {
				$contratline->date_cloture=dol_now();
				$contratline->fk_user_cloture=$user->id;
				$contratline->statut='5';
				$result=$contratline->update($user);
				if ($result < 0)
				{
					$ok=false;
					break;
				}
	        }
		}

		if ($this->statut == 0)
		{
			$result=$this->validate($user);
			if ($result < 0) $ok=false;
		}

        if ($ok)
        {
            $this->db->commit();
        }
        else
        {
            dol_print_error($this->db,'Error in cloture function');
            $this->db->rollback();
        }
	}

	/**
	 * Validate a contract
	 *
	 * @param	User	$user      		Objet User
	 * @param   string	$force_number	Reference to force on contract (not implemented yet)
     * @param	int		$notrigger		1=Does not execute triggers, 0= execuete triggers
	 * @return	int						<0 if KO, >0 if OK
	 */
	function validate($user, $force_number='', $notrigger=0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		global $langs, $conf;

		$now=dol_now();

		$error=0;
		dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number);


		$this->db->begin();

		$this->fetch_thirdparty();

		// A contract is validated so we can move thirdparty to status customer
		$result=$this->thirdparty->set_as_client();

		// Define new ref
		if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
		{
			$num = $this->getNextNumRef($this->thirdparty);
		}
		else
		{
			$num = $this->ref;
		}
        $this->newref = $num;

		if ($num)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET ref = '".$num."', statut = 1";
			//$sql.= ", fk_user_valid = ".$user->id.", date_valid = '".$this->db->idate($now)."'";
			$sql .= " WHERE rowid = ".$this->id . " AND statut = 0";

			dol_syslog(get_class($this)."::validate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql)
			{
				dol_print_error($this->db);
				$error++;
				$this->error=$this->db->lasterror();
			}

			// Trigger calls
			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('CONTRACT_VALIDATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
			}

			if (! $error)
			{
            	$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref))
				{
					// Rename of object directory ($this->ref = old ref, $num = new ref)
					// to  not lose the linked files
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->contract->dir_output.'/'.$oldref;
					$dirdest = $conf->contract->dir_output.'/'.$newref;
					if (file_exists($dirsource))
					{
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest))
						{
							dol_syslog("Rename ok");
						    // Rename docs starting with $oldref with $newref
            				$listoffiles=dol_dir_list($conf->contract->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
            				foreach($listoffiles as $fileentry)
            				{
            					$dirsource=$fileentry['name'];
            					$dirdest=preg_replace('/^'.preg_quote($oldref,'/').'/',$newref, $dirsource);
            					$dirsource=$fileentry['path'].'/'.$dirsource;
            					$dirdest=$fileentry['path'].'/'.$dirdest;
            					@rename($dirsource, $dirdest);
            				}
						}
					}
				}
			}

			// Set new ref and define current statut
			if (! $error)
			{
				$this->ref = $num;
				$this->statut=1;
				$this->brouillon=0;
				$this->date_validation=$now;
			}
		}
		else
		{
			$error++;
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}

	}


	/**
	 *    Load a contract from database
	 *
	 *    @param	int		$id     Id of contract to load
	 *    @param	string	$ref	Ref
	 *    @return   int     		<0 if KO, id of contract if OK
	 */
	function fetch($id,$ref='')
	{
		$sql = "SELECT rowid, statut, ref, fk_soc, mise_en_service as datemise,";
		$sql.= " fk_user_mise_en_service, date_contrat as datecontrat,";
		$sql.= " fk_user_author,";
		$sql.= " fk_projet,";
		$sql.= " fk_commercial_signature, fk_commercial_suivi,";
		$sql.= " note_private, note_public, model_pdf, extraparams";
		$sql.= " ,ref_supplier";
		$sql.= " ,ref_ext";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat";
		if ($ref)
		{
			$sql.= " WHERE ref='".$this->db->escape($ref)."'";
			$sql.= " AND entity IN (".getEntity('contract').")";
		}
		else $sql.= " WHERE rowid=".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$result = $this->db->fetch_array($resql);

			if ($result)
			{
				$this->id						= $result["rowid"];
				$this->ref						= (!isset($result["ref"]) || !$result["ref"]) ? $result["rowid"] : $result["ref"];
				$this->ref_supplier				= $result["ref_supplier"];
				$this->ref_ext					= $result["ref_ext"];
				$this->statut					= $result["statut"];
				$this->mise_en_service			= $this->db->jdate($result["datemise"]);

				$this->date_contrat				= $this->db->jdate($result["datecontrat"]);
				$this->date_creation				= $this->db->jdate($result["datecontrat"]);

				$this->user_author_id			= $result["fk_user_author"];

				$this->commercial_signature_id	= $result["fk_commercial_signature"];
				$this->commercial_suivi_id		= $result["fk_commercial_suivi"];

				$this->note_private				= $result["note_private"];
				$this->note_public				= $result["note_public"];
				$this->modelpdf					= $result["model_pdf"];

				$this->fk_projet				= $result["fk_projet"]; // deprecated
				$this->fk_project				= $result["fk_projet"];

				$this->socid					= $result["fk_soc"];
				$this->fk_soc					= $result["fk_soc"];

				$this->extraparams				= (array) json_decode($result["extraparams"], true);

				$this->db->free($resql);

				// Retreive all extrafield for thirdparty
				// fetch optionals attributes and labels
				require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

				/*
				 * Lines
				*/

				$this->lines  = array();

				$result=$this->fetch_lines();
				if ($result < 0)
				{
					$this->error=$this->db->lasterror();
					return -3;
				}

				return $this->id;
			}
			else
			{
				dol_syslog(get_class($this)."::Fetch Erreur contrat non trouve");
				$this->error="Contract not found";
				return -2;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::Fetch Erreur lecture contrat");
			$this->error=$this->db->error();
			return -1;
		}

	}

	/**
	 *  Load lines array into this->lines
	 *
	 *  @return ContratLigne[]   Return array of contract lines
	 */
	function fetch_lines()
	{
		$this->nbofserviceswait=0;
		$this->nbofservicesopened=0;
		$this->nbofservicesexpired=0;
		$this->nbofservicesclosed=0;

		$total_ttc=0;
		$total_vat=0;
		$total_ht=0;

		$now=dol_now();

		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafieldsline=new ExtraFields($this->db);
		$line = new ContratLigne($this->db);
		$extralabelsline=$extrafieldsline->fetch_name_optionals_label($line->table_element,true);

		$this->lines=array();

		// Selectionne les lignes contrats liees a un produit
		$sql = "SELECT p.label as product_label, p.description as product_desc, p.ref as product_ref,";
		$sql.= " d.rowid, d.fk_contrat, d.statut, d.description, d.price_ht, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.qty, d.remise_percent, d.subprice, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht,";
		$sql.= " d.total_ht,";
		$sql.= " d.total_tva,";
		$sql.= " d.total_localtax1,";
		$sql.= " d.total_localtax2,";
		$sql.= " d.total_ttc,";
		$sql.= " d.info_bits, d.fk_product,";
		$sql.= " d.date_ouverture_prevue, d.date_ouverture,";
		$sql.= " d.date_fin_validite, d.date_cloture,";
		$sql.= " d.fk_user_author,";
		$sql.= " d.fk_user_ouverture,";
		$sql.= " d.fk_user_cloture,";
		$sql.= " d.fk_unit";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d, ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE d.fk_contrat = ".$this->id ." AND d.fk_product = p.rowid";
		$sql.= " ORDER by d.rowid ASC";

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$objp					= $this->db->fetch_object($result);

				$line					= new ContratLigne($this->db);
				$line->id				= $objp->rowid;
				$line->ref				= $objp->rowid;
				$line->fk_contrat		= $objp->fk_contrat;
				$line->desc				= $objp->description;  // Description ligne
				$line->qty				= $objp->qty;
				$line->tva_tx			= $objp->tva_tx;
				$line->localtax1_tx		= $objp->localtax1_tx;
				$line->localtax2_tx		= $objp->localtax2_tx;
				$line->subprice			= $objp->subprice;
				$line->statut			= $objp->statut;
				$line->remise_percent	= $objp->remise_percent;
				$line->price_ht			= $objp->price_ht;
				$line->price			= $objp->price_ht;	// For backward compatibility
				$line->total_ht			= $objp->total_ht;
				$line->total_tva		= $objp->total_tva;
				$line->total_localtax1	= $objp->total_localtax1;
				$line->total_localtax2	= $objp->total_localtax2;
				$line->total_ttc		= $objp->total_ttc;
				$line->fk_product		= $objp->fk_product;
				$line->info_bits		= $objp->info_bits;

				$line->fk_fournprice 	= $objp->fk_fournprice;
				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht 			= $marginInfos[0];

				$line->fk_user_author	= $objp->fk_user_author;
				$line->fk_user_ouverture= $objp->fk_user_ouverture;
				$line->fk_user_cloture  = $objp->fk_user_cloture;
				$line->fk_unit           = $objp->fk_unit;

				$line->ref				= $objp->product_ref;			// deprecated
				$line->label			= $objp->product_label;         // deprecated
				$line->libelle			= $objp->product_label;         // deprecated
				$line->product_ref		= $objp->product_ref;   // Ref product
				$line->product_desc		= $objp->product_desc;  // Description product
				$line->product_label	= $objp->product_label; // Label product

				$line->description		= $objp->description;

				$line->date_ouverture_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$line->date_ouverture        = $this->db->jdate($objp->date_ouverture);
				$line->date_fin_validite     = $this->db->jdate($objp->date_fin_validite);
				$line->date_cloture          = $this->db->jdate($objp->date_cloture);
				// For backward compatibility
				$line->date_debut_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$line->date_debut_reel   = $this->db->jdate($objp->date_ouverture);
				$line->date_fin_prevue   = $this->db->jdate($objp->date_fin_validite);
				$line->date_fin_reel     = $this->db->jdate($objp->date_cloture);

				// Retreive all extrafield for propal
				// fetch optionals attributes and labels
				$line->fetch_optionals($line->id,$extralabelsline);

				$this->lines[]			= $line;

				//dol_syslog("1 ".$line->desc);
				//dol_syslog("2 ".$line->product_desc);

				if ($line->statut == 0) $this->nbofserviceswait++;
				if ($line->statut == 4 && (empty($line->date_fin_prevue) || $line->date_fin_prevue >= $now)) $this->nbofservicesopened++;
				if ($line->statut == 4 && (! empty($line->date_fin_prevue) && $line->date_fin_prevue < $now)) $this->nbofservicesexpired++;
				if ($line->statut == 5) $this->nbofservicesclosed++;

				$total_ttc+=$objp->total_ttc;   // TODO Not saved into database
                $total_vat+=$objp->total_tva;
                $total_ht+=$objp->total_ht;

				$i++;
			}
			$this->db->free($result);
		}
		else
		{
			dol_syslog(get_class($this)."::Fetch Erreur lecture des lignes de contrats liees aux produits");
			return -3;
		}

		// Selectionne les lignes contrat liees a aucun produit
		$sql = "SELECT d.rowid, d.fk_contrat, d.statut, d.qty, d.description, d.price_ht, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.rowid, d.remise_percent, d.subprice,";
		$sql.= " d.total_ht,";
		$sql.= " d.total_tva,";
		$sql.= " d.total_localtax1,";
		$sql.= " d.total_localtax2,";
		$sql.= " d.total_ttc,";
		$sql.= " d.info_bits, d.fk_product,";
		$sql.= " d.date_ouverture_prevue, d.date_ouverture,";
		$sql.= " d.date_fin_validite, d.date_cloture,";
		$sql.= " d.fk_user_author,";
		$sql.= " d.fk_user_ouverture,";
		$sql.= " d.fk_user_cloture,";
		$sql.= " d.fk_unit";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as d";
		$sql.= " WHERE d.fk_contrat = ".$this->id;
		$sql.= " AND (d.fk_product IS NULL OR d.fk_product = 0)";   // fk_product = 0 gardee pour compatibilitee

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$objp                  = $this->db->fetch_object($result);

				$line                 = new ContratLigne($this->db);
				$line->id 			  = $objp->rowid;
				$line->fk_contrat     = $objp->fk_contrat;
				$line->libelle        = $objp->description;
				$line->desc           = $objp->description;
				$line->qty            = $objp->qty;
				$line->statut 		  = $objp->statut;
				$line->ref            = '';
				$line->tva_tx         = $objp->tva_tx;
				$line->localtax1_tx   = $objp->localtax1_tx;
				$line->localtax2_tx   = $objp->localtax2_tx;
				$line->subprice       = $objp->subprice;
				$line->remise_percent = $objp->remise_percent;
				$line->price_ht       = $objp->price_ht;
				$line->price          = (isset($objp->price)?$objp->price:null);	// For backward compatibility
				$line->total_ht       = $objp->total_ht;
				$line->total_tva      = $objp->total_tva;
				$line->total_localtax1= $objp->total_localtax1;
				$line->total_localtax2= $objp->total_localtax2;
				$line->total_ttc      = $objp->total_ttc;
				$line->fk_product     = 0;
				$line->info_bits      = $objp->info_bits;

				$line->fk_user_author   = $objp->fk_user_author;
				$line->fk_user_ouverture= $objp->fk_user_ouverture;
				$line->fk_user_cloture  = $objp->fk_user_cloture;

				$line->description    = $objp->description;

				$line->date_ouverture_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$line->date_ouverture        = $this->db->jdate($objp->date_ouverture);
				$line->date_fin_validite     = $this->db->jdate($objp->date_fin_validite);
				$line->date_cloture          = $this->db->jdate($objp->date_cloture);
				// For backward compatibility
				$line->date_debut_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				$line->date_debut_reel   = $this->db->jdate($objp->date_ouverture);
				$line->date_fin_prevue   = $this->db->jdate($objp->date_fin_validite);
				$line->date_fin_reel     = $this->db->jdate($objp->date_cloture);
				$line->fk_unit        = $objp->fk_unit;

				if ($line->statut == 0) $this->nbofserviceswait++;
				if ($line->statut == 4 && (empty($line->date_fin_prevue) || $line->date_fin_prevue >= $now)) $this->nbofservicesopened++;
				if ($line->statut == 4 && (! empty($line->date_fin_prevue) && $line->date_fin_prevue < $now)) $this->nbofservicesexpired++;
				if ($line->statut == 5) $this->nbofservicesclosed++;


				// Retreive all extrafield for propal
				// fetch optionals attributes and labels

				$line->fetch_optionals($line->id,$extralabelsline);


				$this->lines[]        = $line;

				$total_ttc+=$objp->total_ttc;
                $total_vat+=$objp->total_tva;
                $total_ht+=$objp->total_ht;

                $i++;
			}

			$this->db->free($result);
		}
		else
		{
			dol_syslog(get_class($this)."::Fetch Erreur lecture des lignes de contrat non liees aux produits");
			$this->error=$this->db->error();
			return -2;
		}

		$this->nbofservices=count($this->lines);
        $this->total_ttc = price2num($total_ttc);   // TODO For the moment value is false as value is not stored in database for line linked to products
        $this->total_vat = price2num($total_vat);   // TODO For the moment value is false as value is not stored in database for line linked to products
        $this->total_ht = price2num($total_ht);     // TODO For the moment value is false as value is not stored in database for line linked to products

		return $this->lines;
	}

	/**
	 *  Create a contract into database
	 *
	 *  @param	User	$user       User that create
	 *  @return int  				<0 if KO, id of contract if OK
	 */
	function create($user)
	{
		global $conf,$langs,$mysoc;

		// Check parameters
		$paramsok=1;
		if ($this->commercial_signature_id <= 0)
		{
			$langs->load("commercial");
			$this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeSignature"));
			$paramsok=0;
		}
		if ($this->commercial_suivi_id <= 0)
		{
			$langs->load("commercial");
			$this->error.=($this->error?"<br>":'');
			$this->error.=$langs->trans("ErrorFieldRequired",$langs->trans("SalesRepresentativeFollowUp"));
			$paramsok=0;
		}
		if (! $paramsok) return -1;

		$this->db->begin();

		$now=dol_now();

		// Insert contract
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (datec, fk_soc, fk_user_author, date_contrat,";
		$sql.= " fk_commercial_signature, fk_commercial_suivi, fk_projet,";
		$sql.= " ref, entity, note_private, note_public, ref_supplier, ref_ext)";
		$sql.= " VALUES ('".$this->db->idate($now)."',".$this->socid.",".$user->id;
		$sql.= ", '".$this->db->idate($this->date_contrat)."'";
		$sql.= ",".($this->commercial_signature_id>0?$this->commercial_signature_id:"NULL");
		$sql.= ",".($this->commercial_suivi_id>0?$this->commercial_suivi_id:"NULL");
		$sql.= ",".($this->fk_project>0?$this->fk_project:"NULL");
		$sql.= ", ".(dol_strlen($this->ref)<=0 ? "null" : "'".$this->ref."'");
		$sql.= ", ".$conf->entity;
		$sql.= ", ".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
		$sql.= ", ".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
		$sql.= ", ".(!empty($this->ref_supplier)?("'".$this->db->escape($this->ref_supplier)."'"):"NULL");
		$sql.= ", ".(!empty($this->ref_ext)?("'".$this->db->escape($this->ref_ext)."'"):"NULL");
		$sql.= ")";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$error=0;

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."contrat");


			// Load object modContract
			$module=(! empty($conf->global->CONTRACT_ADDON)?$conf->global->CONTRACT_ADDON:'mod_contract_olive');
			if (substr($module, 0, 13) == 'mod_contract_' && substr($module, -3) == 'php')
			{
				$module = substr($module, 0, dol_strlen($module)-4);
			}
			$result=dol_include_once('/core/modules/contract/'.$module.'.php');
			if ($result > 0)
			{
				$modCodeContract = new $module();
			}

			if (!empty($modCodeContract->code_auto)) {
				// Mise a jour ref
				$sql = 'UPDATE '.MAIN_DB_PREFIX."contrat SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
				if ($this->db->query($sql))
				{
					if ($this->id)
					{
						$this->ref="(PROV".$this->id.")";
					}
				}
			}

			// Insert contacts commerciaux ('SALESREPSIGN','contrat')
			$result=$this->add_contact($this->commercial_signature_id,'SALESREPSIGN','internal');
			if ($result < 0) $error++;

			// Insert contacts commerciaux ('SALESREPFOLL','contrat')
			$result=$this->add_contact($this->commercial_suivi_id,'SALESREPFOLL','internal');
			if ($result < 0) $error++;


			if (! $error)
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$error++;
					}
				}
			}

			if (! $error)
			{
                // Call trigger
                $result=$this->call_trigger('CONTRACT_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers

				if (! $error)
				{
		            // Add linked object
		            if (! $error && $this->origin && $this->origin_id)
		            {
		                $ret = $this->add_object_linked();
		                if (! $ret)	dol_print_error($this->db);
		            }
					$this->db->commit();
					return $this->id;
				}
				else
				{
					dol_syslog(get_class($this)."::create - 30 - ".$this->error, LOG_ERR);

					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error="Failed to add contact";
				dol_syslog(get_class($this)."::create - 20 - ".$this->error, LOG_ERR);

				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$langs->trans("UnknownError: ".$this->db->error()." -", LOG_DEBUG);

			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Supprime l'objet de la base
	 *
	 *  @param	User		$user       Utilisateur qui supprime
	 *  @return int         			< 0 si erreur, > 0 si ok
	 */
	function delete($user)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error=0;

		$this->db->begin();

	    // Call trigger
	    $result=$this->call_trigger('CONTRACT_DELETE',$user);
	    if ($result < 0) { $error++; }
	    // End call triggers

		if (! $error)
		{
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0)
			{
				dol_syslog(get_class($this)."::delete error", LOG_ERR);
				$error++;
			}
		}

		if (! $error)
		{
			// Delete contratdet_log
			/*
			$sql = "DELETE cdl";
			$sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;
			*/
			$sql = "SELECT cdl.rowid as cdlrowid ";
			$sql.= " FROM ".MAIN_DB_PREFIX."contratdet_log as cdl, ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= " WHERE cdl.fk_contratdet=cd.rowid AND cd.fk_contrat=".$this->id;

			dol_syslog(get_class($this)."::delete contratdet_log", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
			$numressql=$this->db->num_rows($resql);
			if (! $error && $numressql )
			{
				$tab_resql=array();
				for($i=0;$i<$numressql;$i++)
				{
					$objresql=$this->db->fetch_object($resql);
					$tab_resql[]= $objresql->cdlrowid;
				}
				$this->db->free($resql);

				$sql= "DELETE FROM ".MAIN_DB_PREFIX."contratdet_log ";
				$sql.= " WHERE ".MAIN_DB_PREFIX."contratdet_log.rowid IN (".implode(",",$tab_resql).")";

				dol_syslog(get_class($this)."::delete contratdet_log", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if (! $resql)
				{
					$this->error=$this->db->error();
					$error++;
				}
			}
		}

		if (! $error)
		{
			// Delete contratdet
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
			$sql.= " WHERE fk_contrat=".$this->id;

			dol_syslog(get_class($this)."::delete contratdet", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
		}

		if (! $error)
		{
			// Delete contrat
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete contrat", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->error();
				$error++;
			}
		}

		if (! $error)
		{
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->contrat->dir_output)
			{
				$dir = $conf->contrat->dir_output . "/" . $ref;
				if (file_exists($dir))
				{
					$res=@dol_delete_dir_recursive($dir);
					if (! $res)
					{
						$this->error='ErrorFailToDeleteDir';
						$error++;
					}
				}
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
		if (isset($this->ref_ext)) $this->ref_ext=trim($this->ref_ext);
		if (isset($this->entity)) $this->entity=trim($this->entity);
		if (isset($this->statut)) $this->statut=(int) $this->statut;
		if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
		if (isset($this->fk_projet)) $this->fk_projet=trim($this->fk_projet);
		if (isset($this->fk_commercial_signature)) $this->fk_commercial_signature=trim($this->fk_commercial_signature);
		if (isset($this->fk_commercial_suivi)) $this->fk_commercial_suivi=trim($this->fk_commercial_suivi);
		if (isset($this->fk_user_mise_en_service)) $this->fk_user_mise_en_service=trim($this->fk_user_mise_en_service);
		if (isset($this->fk_user_cloture)) $this->fk_user_cloture=trim($this->fk_user_cloture);
		if (isset($this->note_private)) $this->note_private=trim($this->note_private);
		if (isset($this->note_public)) $this->note_public=trim($this->note_public);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);
		//if (isset($this->extraparams)) $this->extraparams=trim($this->extraparams);



		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET";

		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_supplier=".(isset($this->ref_supplier)?"'".$this->db->escape($this->ref_supplier)."'":"null").",";
		$sql.= " ref_ext=".(isset($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null").",";
		$sql.= " entity=".$conf->entity.",";
		$sql.= " date_contrat=".(dol_strlen($this->date_contrat)!=0 ? "'".$this->db->idate($this->date_contrat)."'" : 'null').",";
		$sql.= " statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " mise_en_service=".(dol_strlen($this->mise_en_service)!=0 ? "'".$this->db->idate($this->mise_en_service)."'" : 'null').",";
		$sql.= " fin_validite=".(dol_strlen($this->fin_validite)!=0 ? "'".$this->db->idate($this->fin_validite)."'" : 'null').",";
		$sql.= " date_cloture=".(dol_strlen($this->date_cloture)!=0 ? "'".$this->db->idate($this->date_cloture)."'" : 'null').",";
		$sql.= " fk_soc=".(isset($this->fk_soc)?$this->fk_soc:"null").",";
		$sql.= " fk_projet=".(isset($this->fk_projet)?$this->fk_projet:"null").",";
		$sql.= " fk_commercial_signature=".(isset($this->fk_commercial_signature)?$this->fk_commercial_signature:"null").",";
		$sql.= " fk_commercial_suivi=".(isset($this->fk_commercial_suivi)?$this->fk_commercial_suivi:"null").",";
		$sql.= " fk_user_mise_en_service=".(isset($this->fk_user_mise_en_service)?$this->fk_user_mise_en_service:"null").",";
		$sql.= " fk_user_cloture=".(isset($this->fk_user_cloture)?$this->fk_user_cloture:"null").",";
		$sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
		$sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";
		//$sql.= " extraparams=".(isset($this->extraparams)?"'".$this->db->escape($this->extraparams)."'":"null")."";


		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
				}
			}

			// Commit or rollback
			if ($error)
			{
				foreach($this->errors as $errmsg)
				{
					dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
					$this->error.=($this->error?', '.$errmsg:$errmsg);
				}
				$this->db->rollback();
				return -1*$error;
			}
			else
			{
				$this->db->commit();
				return 1;
			}
		}


	/**
	 *  Ajoute une ligne de contrat en base
	 *
	 *  @param	string		$desc            	Description de la ligne
	 *  @param  float		$pu_ht              Prix unitaire HT
	 *  @param  int			$qty             	Quantite
	 *  @param  float		$txtva           	Taux tva
	 *  @param  float		$txlocaltax1        Local tax 1 rate
	 *  @param  float		$txlocaltax2        Local tax 2 rate
	 *  @param  int			$fk_product      	Id produit
	 *  @param  float		$remise_percent  	Pourcentage de remise de la ligne
	 *  @param  int			$date_start      	Date de debut prevue
	 *  @param  int			$date_end        	Date de fin prevue
	 *	@param	string		$price_base_type	HT or TTC
	 * 	@param  float		$pu_ttc             Prix unitaire TTC
	 * 	@param  int			$info_bits			Bits de type de lignes
	 * 	@param  int			$fk_fournprice		Fourn price id
	 *  @param  int			$pa_ht				Buying price HT
	 *  @param	array		$array_options		extrafields array
	 * 	@param 	string		$fk_unit 			Code of the unit to use. Null to use the default one
	 *  @return int             				<0 si erreur, >0 si ok
	 */
	function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type='HT', $pu_ttc=0.0, $info_bits=0, $fk_fournprice=null, $pa_ht = 0,$array_options=0, $fk_unit = null)
	{
		global $user, $langs, $conf, $mysoc;

		dol_syslog(get_class($this)."::addline $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits");

		if ($this->statut >= 0)
		{
			$this->db->begin();

			// Clean parameters
			$remise_percent=price2num($remise_percent);
			$qty=price2num($qty);
			if (! $qty) $qty=1;
			if (! $info_bits) $info_bits=0;
			if (! $pu_ht)  $pu_ht=0;
			if (! $pu_ttc) $pu_ttc=0;

			$pu_ht=price2num($pu_ht);
			$pu_ttc=price2num($pu_ttc);
			$pa_ht=price2num($pa_ht);
			$txtva=price2num($txtva);
			$txlocaltax1=price2num($txlocaltax1);
			$txlocaltax2=price2num($txlocaltax2);

			if ($price_base_type=='HT')
			{
				$pu=$pu_ht;
			}
			else
			{
				$pu=$pu_ttc;
			}

			// Check parameters
			if (empty($remise_percent)) $remise_percent=0;

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type=getLocalTaxesFromRate($txtva, 0, $this->societe, $mysoc);

			$tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, 1,'', $localtaxes_type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1= $tabprice[9];
			$total_localtax2= $tabprice[10];

			$localtax1_type=$localtaxes_type[0];
			$localtax2_type=$localtaxes_type[2];

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$remise = 0;
			$price = price2num(round($pu_ht, 2));
			if (dol_strlen($remise_percent) > 0)
			{
				$remise = round(($pu_ht * $remise_percent / 100), 2);
				$price = $pu_ht - $remise;
			}

		    if (empty($pa_ht)) $pa_ht=0;

			// si prix d'achat non renseigne et utilise pour calcul des marges alors prix achat = prix vente
			if ($pa_ht == 0) {
				if ($pu_ht > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
					$pa_ht = $pu_ht * (1 - $remise_percent / 100);
			}

			// Insertion dans la base
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
			$sql.= " (fk_contrat, label, description, fk_product, qty, tva_tx,";
			$sql.= " localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice,";
			$sql.= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
			$sql.= " info_bits,";
			$sql.= " price_ht, remise, fk_product_fournisseur_price, buy_price_ht";
			if ($date_start > 0) { $sql.= ",date_ouverture_prevue"; }
			if ($date_end > 0)   { $sql.= ",date_fin_validite"; }
			$sql.= ", fk_unit";
			$sql.= ") VALUES ($this->id, '', '" . $this->db->escape($desc) . "',";
			$sql.= ($fk_product>0 ? $fk_product : "null").",";
			$sql.= " '".$qty."',";
			$sql.= " '".$txtva."',";
			$sql.= " '".$txlocaltax1."',";
			$sql.= " '".$txlocaltax2."',";
			$sql.= " '".$localtax1_type."',";
			$sql.= " '".$localtax2_type."',";
			$sql.= " ".price2num($remise_percent).",".price2num($pu_ht).",";
			$sql.= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_localtax1).",".price2num($total_localtax2).",".price2num($total_ttc).",";
			$sql.= " '".$info_bits."',";
			$sql.= " ".price2num($price).",".price2num($remise).",";
			if (isset($fk_fournprice)) $sql.= ' '.$fk_fournprice.',';
			else $sql.= ' null,';
			if (isset($pa_ht)) $sql.= ' '.price2num($pa_ht);
			else $sql.= ' null';
			if ($date_start > 0) { $sql.= ",'".$this->db->idate($date_start)."'"; }
			if ($date_end > 0) { $sql.= ",'".$this->db->idate($date_end)."'"; }
			$sql.= ", ".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null");
			$sql.= ")";

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$result=$this->update_statut($user);
				if ($result > 0)
				{

					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
					{
						$contractline = new ContratLigne($this->db);
						$contractline->array_options=$array_options;
						$contractline->id= $this->db->last_insert_id(MAIN_DB_PREFIX.$contractline->table_element);
						$result=$contractline->insertExtraFields();
						if ($result < 0)
						{
							$this->error[]=$contractline->error;
							$error++;
						}
					}

					if (empty($error)) {
					    // Call trigger
					    $result=$this->call_trigger('LINECONTRACT_CREATE',$user);
					    if ($result < 0)
					    {
					        $this->db->rollback();
					        return -1;
					    }
					    // End call triggers

						$this->db->commit();
						return 1;
					}
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->error()." sql=".$sql;
				return -1;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::addline ErrorTryToAddLineOnValidatedContract", LOG_ERR);
			return -2;
		}
	}

	/**
	 *  Mets a jour une ligne de contrat
	 *
	 *  @param	int			$rowid            	Id de la ligne de facture
	 *  @param  string		$desc             	Description de la ligne
	 *  @param  float		$pu               	Prix unitaire
	 *  @param  int			$qty              	Quantite
	 *  @param  float		$remise_percent   	Pourcentage de remise de la ligne
	 *  @param  int			$date_start       	Date de debut prevue
	 *  @param  int			$date_end         	Date de fin prevue
	 *  @param  float		$tvatx            	Taux TVA
	 *  @param  float		$localtax1tx      	Local tax 1 rate
	 *  @param  float		$localtax2tx      	Local tax 2 rate
	 *  @param  int|string	$date_debut_reel  	Date de debut reelle
	 *  @param  int|string	$date_fin_reel    	Date de fin reelle
	 *	@param	string		$price_base_type	HT or TTC
	 * 	@param  int			$info_bits			Bits de type de lignes
	 * 	@param  int			$fk_fournprice		Fourn price id
	 *  @param  int			$pa_ht				Buying price HT
	 *  @param	array		$array_options		extrafields array
	 * 	@param 	string		$fk_unit 			Code of the unit to use. Null to use the default one
	 *  @return int              				< 0 si erreur, > 0 si ok
	 */
	function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx=0.0, $localtax2tx=0.0, $date_debut_reel='', $date_fin_reel='', $price_base_type='HT', $info_bits=0, $fk_fournprice=null, $pa_ht = 0,$array_options=0, $fk_unit = null)
	{
		global $user, $conf, $langs, $mysoc;

		// Nettoyage parametres
		$qty=trim($qty);
		$desc=trim($desc);
		$desc=trim($desc);
		$price = price2num($pu);
		$tvatx = price2num($tvatx);
		$localtax1tx = price2num($localtax1tx);
		$localtax2tx = price2num($localtax2tx);
		$pa_ht=price2num($pa_ht);

		$subprice = $price;
		$remise = 0;
		if (dol_strlen($remise_percent) > 0)
		{
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		}
		else
		{
			$remise_percent=0;
		}

		dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_debut_reel, $date_fin_reel, $tvatx, $localtax1tx, $localtax2tx, $price_base_type, $info_bits");

		$this->db->begin();

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et tvatx
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$localtaxes_type=getLocalTaxesFromRate($tvatx, 0, $this->societe, $mysoc);

		$tabprice=calcul_price_total($qty, $pu, $remise_percent, $tvatx, $localtax1tx, $localtax2tx, 0, $price_base_type, $info_bits, 1, '', $localtaxes_type);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$total_localtax1= $tabprice[9];
		$total_localtax2= $tabprice[10];

		$localtax1_type=$localtaxes_type[0];
		$localtax2_type=$localtaxes_type[2];

		// TODO A virer
		// Anciens indicateurs: $price, $remise (a ne plus utiliser)
		$remise = 0;
		$price = price2num(round($pu, 2));
		if (dol_strlen($remise_percent) > 0)
		{
		    $remise = round(($pu * $remise_percent / 100), 2);
		    $price = $pu - $remise;
		}

	    if (empty($pa_ht)) $pa_ht=0;

		// si prix d'achat non renseigne et utilise pour calcul des marges alors prix achat = prix vente
		if ($pa_ht == 0) {
			if ($pu > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
				$pa_ht = $pu * (1 - $remise_percent / 100);
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description='".$this->db->escape($desc)."'";
		$sql.= ",price_ht='" .     price2num($price)."'";
		$sql.= ",subprice='" .     price2num($subprice)."'";
		$sql.= ",remise='" .       price2num($remise)."'";
		$sql.= ",remise_percent='".price2num($remise_percent)."'";
		$sql.= ",qty='".$qty."'";
		$sql.= ",tva_tx='".        price2num($tvatx)."'";
		$sql.= ",localtax1_tx='".  price2num($localtax1tx)."'";
		$sql.= ",localtax2_tx='".  price2num($localtax2tx)."'";
		$sql.= ",localtax1_type='".$localtax1_type."'";
		$sql.= ",localtax2_type='".$localtax2_type."'";
		$sql.= ", total_ht='".     price2num($total_ht)."'";
		$sql.= ", total_tva='".    price2num($total_tva)."'";
		$sql.= ", total_localtax1='".price2num($total_localtax1)."'";
		$sql.= ", total_localtax2='".price2num($total_localtax2)."'";
		$sql.= ", total_ttc='".      price2num($total_ttc)."'";
		$sql.= ", fk_product_fournisseur_price='".$fk_fournprice."'";
		$sql.= ", buy_price_ht='".price2num($pa_ht)."'";
		if ($date_start > 0) { $sql.= ",date_ouverture_prevue='".$this->db->idate($date_start)."'"; }
		else { $sql.=",date_ouverture_prevue=null"; }
		if ($date_end > 0) { $sql.= ",date_fin_validite='".$this->db->idate($date_end)."'"; }
		else { $sql.=",date_fin_validite=null"; }
		if ($date_debut_reel > 0) { $sql.= ",date_ouverture='".$this->db->idate($date_debut_reel)."'"; }
		else { $sql.=",date_ouverture=null"; }
		if ($date_fin_reel > 0) { $sql.= ",date_cloture='".$this->db->idate($date_fin_reel)."'"; }
		else { $sql.=",date_cloture=null"; }
		$sql .= ", fk_unit=".($fk_unit?"'".$this->db->escape($fk_unit)."'":"null");
		$sql .= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$result=$this->update_statut($user);
			if ($result >= 0)
			{

				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($array_options) && count($array_options)>0) // For avoid conflicts if trigger used
				{
					$contractline = new ContratLigne($this->db);
					$contractline->array_options=$array_option;
					$contractline->id= $this->db->last_insert_id(MAIN_DB_PREFIX.$contractline->table_element);
					$result=$contractline->insertExtraFields();
					if ($result < 0)
					{
						$this->error[]=$contractline->error;
						$error++;
					}
				}

				if (empty($error)) {
			        // Call trigger
			        $result=$this->call_trigger('LINECONTRACT_UPDATE',$user);
			        if ($result < 0)
			        {
			            $this->db->rollback();
			            return -3;
			        }
			        // End call triggers

					$this->db->commit();
					return 1;
				}
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::updateligne Erreur -2");
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::updateligne Erreur -1");
			return -1;
		}
	}

	/**
	 *  Delete a contract line
	 *
	 *  @param	int		$idline		Id of line to delete
	 *	@param  User	$user       User that delete
	 *  @return int         		>0 if OK, <0 if KO
	 */
	function deleteline($idline,$user)
	{
		global $conf, $langs;

		$error=0;

		if ($this->statut >= 0)
		{

		    // Call trigger
		    $result=$this->call_trigger('LINECONTRACT_DELETE',$user);
		    if ($result < 0) return -1;
		    // End call triggers

		    $this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
			$sql.= " WHERE rowid=".$idline;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql)
			{
				$this->error="Error ".$this->db->lasterror();
				$error++;
			}

			if (empty($error)) {
				// Remove extrafields
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$contractline = new ContratLigne($this->db);
					$contractline->id= $idline;
					$result=$contractline->deleteExtraFields();
					if ($result < 0)
					{
						$error++;
						$this->error="Error ".get_class($this)."::delete deleteExtraFields error -4 ".$contractline->error;
					}
				}
			}

			if (empty($error)) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::delete ERROR:".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}


	/**
	 *  Update statut of contract according to services
	 *
	 *	@param	User	$user		Object user
	 *	@return int     			<0 if KO, >0 if OK
	 *  @deprecated					This function will never be used. Status of a contract is status of its lines.
	 */
	function update_statut($user)
	{
		dol_syslog(__METHOD__ . " is deprecated", LOG_WARNING);

		// If draft, we keep it (should not happen)
		if ($this->statut == 0) return 1;

		// Load $this->lines array
		//		$this->fetch_lines();

//		$newstatut=1;
//		foreach($this->lines as $key => $contractline)
//		{
//			//			if ($contractline)         // Loop on each service
//		}

		return 1;
	}


	/**
	 *  Return label of a contract status
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Long label of all services, 5=Libelle court + Picto, 6=Picto of all services
	 *  @return string      		Label
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Renvoi label of a given contrat status
	 *
	 *  @param	int		$statut      	Status id
	 *  @param  int		$mode          	0=Long label, 1=Short label, 2=Picto + Libelle court, 3=Picto, 4=Picto + Long label of all services, 5=Libelle court + Picto, 6=Picto of all services
	 *	@return string      			Label
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;
		$langs->load("contracts");
		if ($mode == 0)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft"); }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated"); }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
		}
		if ($mode == 1)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft"); }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated"); }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
		}
		if ($mode == 2)
		{
			if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0').' '.$langs->trans("ContractStatusDraft"); }
			if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4').' '.$langs->trans("ContractStatusValidated"); }
			if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6').' '.$langs->trans("ContractStatusClosed"); }
		}
		if ($mode == 3)
		{
			if ($statut == 0) { return img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
			if ($statut == 1) { return img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
			if ($statut == 2) { return img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
		}
		if ($mode == 4 || $mode == 6)
		{
			$line=new ContratLigne($this->db);
			$text='';
			if ($mode == 4)
			{
				$text=($this->nbofserviceswait+$this->nbofservicesopened+$this->nbofservicesexpired+$this->nbofservicesclosed);
				$text.=' '.$langs->trans("Services");
				$text.=': &nbsp; &nbsp; ';
			}
			$text.=$this->nbofserviceswait.' '.$line->LibStatut(0,3).' &nbsp; ';
			$text.=$this->nbofservicesopened.' '.$line->LibStatut(4,3,0).' &nbsp; ';
			$text.=$this->nbofservicesexpired.' '.$line->LibStatut(4,3,1).' &nbsp; ';
			$text.=$this->nbofservicesclosed.' '.$line->LibStatut(5,3);
			return $text;
		}
		if ($mode == 5)
		{
			if ($statut == 0) { return $langs->trans("ContractStatusDraft").' '.img_picto($langs->trans('ContractStatusDraft'),'statut0'); }
			if ($statut == 1) { return $langs->trans("ContractStatusValidated").' '.img_picto($langs->trans('ContractStatusValidated'),'statut4'); }
			if ($statut == 2) { return $langs->trans("ContractStatusClosed").' '.img_picto($langs->trans('ContractStatusClosed'),'statut6'); }
		}
	}


	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	int		$maxlength		Max length of ref
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlength=0)
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowContract").': '.$this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='contract';


		if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.($maxlength?dol_trunc($this->ref,$maxlength):$this->ref).$linkend;
		return $result;
	}

	/**
	 *  Charge les informations d'ordre info dans l'objet contrat
	 *
	 *  @param  int		$id     id du contrat a charger
	 *  @return	void
	 */
	function info($id)
	{
		$sql = "SELECT c.rowid, c.ref, c.datec, c.date_cloture,";
		$sql.= " c.tms as date_modification,";
		$sql.= " fk_user_author, fk_user_cloture";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE c.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_cloture) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cuser;
				}
				$this->ref			     = (! $obj->ref) ? $obj->rowid : $obj->ref;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_cloture      = $this->db->jdate($obj->date_cloture);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return list of line rowid
	 *
	 *  @param	int		$statut     Status of lines to get
	 *  @return array       		Array of line's rowid
	 */
	function array_detail($statut=-1)
	{
		$tab=array();

		$sql = "SELECT cd.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= " WHERE fk_contrat =".$this->id;
		if ($statut >= 0) $sql.= " AND statut = '$statut'";

		dol_syslog(get_class($this)."::array_detail()", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tab[$i]=$obj->rowid;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Return list of other contracts for same company than current contract
	 *
	 *	@param	string		$option		'all' or 'others'
	 *  @return array   				Array of contracts id
	 */
	function getListOfContracts($option='all')
	{
		$tab=array();

		$sql = "SELECT c.rowid, c.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " WHERE fk_soc =".$this->socid;
		if ($option == 'others') $sql.= " AND c.rowid != ".$this->id;

		dol_syslog(get_class($this)."::getOtherContracts()", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$contrat=new Contrat($this->db);
				$contrat->fetch($obj->rowid);
				$tab[]=$contrat;
				$i++;
			}
			return $tab;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param	User	$user           Objet user
     *      @param  string	$mode           "inactive" pour services a activer, "expired" pour services expires
     *      @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
	 */
	function load_board($user,$mode)
	{
		global $conf, $user, $langs;

		$this->from = " FROM ".MAIN_DB_PREFIX."contrat as c";
		$this->from.= ", ".MAIN_DB_PREFIX."contratdet as cd";
		$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$user->societe_id) $this->from.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";

		if ($mode == 'inactives')
		{
			$sql = "SELECT cd.rowid, cd.date_ouverture_prevue as datefin";
			$sql.= $this->from;
			$sql.= " WHERE c.statut = 1";
			$sql.= " AND c.rowid = cd.fk_contrat";
			$sql.= " AND cd.statut = 0";
		}
		if ($mode == 'expired')
		{
			$sql = "SELECT cd.rowid, cd.date_fin_validite as datefin";
			$sql.= $this->from;
			$sql.= " WHERE c.statut = 1";
			$sql.= " AND c.rowid = cd.fk_contrat";
			$sql.= " AND cd.statut = 4";
			$sql.= " AND cd.date_fin_validite < '".$this->db->idate(time())."'";
		}
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		if ($user->societe_id) $sql.=" AND c.fk_soc = ".$user->societe_id;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$langs->load("contracts");
			$now=dol_now();

			if ($mode == 'inactives') {
				$warning_delay = $conf->contrat->services->inactifs->warning_delay;
				$label = $langs->trans("BoardNotActivatedServices");
				$url = DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&amp;leftmenu=contracts&amp;mode=0';
			} else {
				$warning_delay = $conf->contrat->services->expires->warning_delay;
				$url = DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&amp;leftmenu=contracts&amp;mode=4&amp;filter=expired';
				$label = $langs->trans("BoardRunningServices");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $warning_delay/60/60/24;
			$response->label = $label;
			$response->url = $url;
			$response->img = img_object($langs->trans("Contract"),"contract");

			while ($obj=$this->db->fetch_object($resql))
			{
				$response->nbtodo++;

				if ($obj->datefin && $this->db->jdate($obj->datefin) < ($now - $warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *   Charge indicateurs this->nb de tableau de bord
	 *
	 *   @return     int         <0 si ko, >0 si ok
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();
		$clause = "WHERE";

		$sql = "SELECT count(c.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		if (!$user->rights->contrat->activer && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= " ".$clause." c.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["Contracts"]=$obj->nb;
			}
            $this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}


	/* gestion des contacts d'un contrat */

	/**
	 *  Return id des contacts clients de facturation
	 *
	 *  @return     array       Liste des id contacts facturation
	 */
	function getIdBillingContact()
	{
		return $this->getIdContact('external','BILLING');
	}

	/**
	 *  Return id des contacts clients de prestation
	 *
	 *  @return     array       Liste des id contacts prestation
	 */
	function getIdServiceContact()
	{
		return $this->getIdContact('external','SERVICE');
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
		$this->specimen=1;

		$this->ref = 'SPECIMEN';
		$this->socid = 1;
		$this->statut= 0;
		$this->date_contrat = dol_now();
		$this->commercial_signature_id = 1;
		$this->commercial_suivi_id = 1;
		$this->note_private='This is a comment (private)';
		$this->note_public='This is a comment (public)';
		$this->fk_projet = 0;
		// Lines
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$line=new ContratLigne($this->db);
			$line->qty=1;
			$line->subprice=100;
			$line->price=100;
			$line->tva_tx=19.6;
			$line->remise_percent=10;
			$line->total_ht=90;
			$line->total_ttc=107.64;	// 90 * 1.196
			$line->total_tva=17.64;
			$prodid = rand(1, $num_prods);
			$line->fk_product=$prodids[$prodid];
			$this->lines[$xnbp]=$line;
			$xnbp++;
		}

		$this->amount_ht      = $xnbp*100;
		$this->total_ht       = $xnbp*100;
		$this->total_tva      = $xnbp*19.6;
		$this->total_ttc      = $xnbp*119.6;
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$user,$langs;

		$langs->load("contracts");

		// Positionne le modele sur le nom du modele a utiliser
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->CONTRACT_ADDON_PDF))
			{
				$modele = $conf->global->CONTRACT_ADDON_PDF;
			}
			else
			{
				$modele = 'strato';
			}
		}

		$modelpath = "core/modules/contract/doc/";

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
			'contrat'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}


/**
 *	Classe permettant la gestion des lignes de contrats
 */
class ContratLigne extends CommonObjectLine
{

	var $id;
	var $ref;
	var $tms;
	var $fk_contrat;
	var $fk_product;
	var $statut;					// 0 inactive, 4 active, 5 closed
	var $label;

	public $element='contratdet';
	public $table_element='contratdet';

	/**
	 * @var string
	 * @deprecated Use $label instead
	 * @see label
	 */
	public $libelle;

	var $description;
	var $date_commande;
	var $date_ouverture_prevue;		// date start planned
	var $date_ouverture;			// date start real
	var $date_fin_validite;			// date end planned
	var $date_cloture;				// date end real
	var $tva_tx;
	var $localtax1_tx;
	var $localtax2_tx;
	var $localtax1_type;	// Local tax 1 type
	var $localtax2_type;	// Local tax 2 type
	var $qty;
	var $remise_percent;
	var $remise;
	var $fk_remise_except;

	var $subprice;					// Unit price HT

	/**
	 * @var float
	 * @deprecated Use $price_ht instead
	 * @see price_ht
	 */
	public $price;

	var $price_ht;

	var $total_ht;
	var $total_tva;
	var $total_localtax1;
	var $total_localtax2;
	var $total_ttc;

	var $fk_fournprice;
	var $pa_ht;

	var $info_bits;
	var $fk_user_author;
	var $fk_user_ouverture;
	var $fk_user_cloture;
	var $commentaire;


	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Return label of this contract line status
	 *
	 *	@param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string      		Libelle
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$mode,((! empty($this->date_fin_validite))?($this->date_fin_validite < dol_now()?1:0):-1));
	}

	/**
	 *  Return label of a contract line status
	 *
	 *  @param	int		$statut     Id statut
	 *	@param  int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *	@param	int		$expired	0=Not expired, 1=Expired, -1=Both or unknown
	 *  @return string      		Libelle
	 */
	function LibStatut($statut,$mode,$expired=-1)
	{
		global $langs;
		$langs->load("contracts");
		if ($mode == 0)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLate"); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLate"); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
		}
		if ($mode == 1)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLateShort"); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLateShort"); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed");  }
		}
		if ($mode == 2)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4').' '.$langs->trans("ServiceStatusNotLateShort"); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3').' '.$langs->trans("ServiceStatusLateShort"); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
		}
		if ($mode == 3)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4'); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3'); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
		}
		if ($mode == 4)
		{
			if ($statut == 0) { return img_picto($langs->trans('ServiceStatusInitial'),'statut0').' '.$langs->trans("ServiceStatusInitial"); }
			if ($statut == 4 && $expired == -1) { return img_picto($langs->trans('ServiceStatusRunning'),'statut4').' '.$langs->trans("ServiceStatusRunning"); }
			if ($statut == 4 && $expired == 0)  { return img_picto($langs->trans('ServiceStatusNotLate'),'statut4').' '.$langs->trans("ServiceStatusNotLate"); }
			if ($statut == 4 && $expired == 1)  { return img_picto($langs->trans('ServiceStatusLate'),'statut3').' '.$langs->trans("ServiceStatusLate"); }
			if ($statut == 5) { return img_picto($langs->trans('ServiceStatusClosed'),'statut6') .' '.$langs->trans("ServiceStatusClosed"); }
		}
		if ($mode == 5)
		{
			if ($statut == 0) { return $langs->trans("ServiceStatusInitial").' '.img_picto($langs->trans('ServiceStatusInitial'),'statut0'); }
			if ($statut == 4 && $expired == -1) { return $langs->trans("ServiceStatusRunning").' '.img_picto($langs->trans('ServiceStatusRunning'),'statut4'); }
			if ($statut == 4 && $expired == 0)  { return $langs->trans("ServiceStatusNotLateShort").' '.img_picto($langs->trans('ServiceStatusNotLateShort'),'statut4'); }
			if ($statut == 4 && $expired == 1)  { return $langs->trans("ServiceStatusLateShort").' '.img_picto($langs->trans('ServiceStatusLate'),'statut3'); }
			if ($statut == 5) { return $langs->trans("ServiceStatusClosed").' '.img_picto($langs->trans('ServiceStatusClosed'),'statut6'); }
		}
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *  @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int		$maxlength		Max length
	 *  @return	string					Chaine avec URL
 	 */
	function getNomUrl($withpicto=0,$maxlength=0)
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowContractOfService").': '.$this->label;

        $link = '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$this->fk_contrat.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='contract';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$this->label.$linkend;
		return $result;
	}

	/**
	 *    	Load object in memory from database
	 *
	 *    	@param	int		$id         Id object
	 * 		@param	string	$ref		Ref of contract
	 *    	@return int         		<0 if KO, >0 if OK
	 */
	function fetch($id, $ref='')
	{
		global $langs,$user;

		// Check parameters
		if (empty($id) && empty($ref)) return -1;

		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.tms,";
		$sql.= " t.fk_contrat,";
		$sql.= " t.fk_product,";
		$sql.= " t.statut,";
		$sql.= " t.label,";			// This field is not used. Only label of product
		$sql.= " p.ref as product_ref,";
		$sql.= " p.label as product_label,";
		$sql.= " p.description as product_desc,";
		$sql.= " t.description,";
		$sql.= " t.date_commande,";
		$sql.= " t.date_ouverture_prevue as date_ouverture_prevue,";
		$sql.= " t.date_ouverture as date_ouverture,";
		$sql.= " t.date_fin_validite as date_fin_validite,";
		$sql.= " t.date_cloture as date_cloture,";
		$sql.= " t.tva_tx,";
		$sql.= " t.localtax1_tx,";
		$sql.= " t.localtax2_tx,";
		$sql.= " t.qty,";
		$sql.= " t.remise_percent,";
		$sql.= " t.remise,";
		$sql.= " t.fk_remise_except,";
		$sql.= " t.subprice,";
		$sql.= " t.price_ht,";
		$sql.= " t.total_ht,";
		$sql.= " t.total_tva,";
		$sql.= " t.total_localtax1,";
		$sql.= " t.total_localtax2,";
		$sql.= " t.total_ttc,";
		$sql.= " t.fk_product_fournisseur_price as fk_fournprice,";
		$sql.= " t.buy_price_ht as pa_ht,";
		$sql.= " t.info_bits,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_ouverture,";
		$sql.= " t.fk_user_cloture,";
		$sql.= " t.commentaire,";
		$sql.= " t.fk_unit";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as t LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
		if ($id)  $sql.= " WHERE t.rowid = ".$id;
		if ($ref) $sql.= " WHERE t.rowid = '".$this->db->escape($ref)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_contrat = $obj->fk_contrat;
				$this->fk_product = $obj->fk_product;
				$this->statut = $obj->statut;
				$this->product_ref = $obj->product_ref;
				$this->product_label = $obj->product_label;
				$this->product_description = $obj->product_description;
				$this->label = $obj->label;					// deprecated. We do not use this field. Only ref and label of product, and description of contract line
				$this->description = $obj->description;
				$this->date_commande = $this->db->jdate($obj->date_commande);
				$this->date_ouverture_prevue = $this->db->jdate($obj->date_ouverture_prevue);
				$this->date_ouverture = $this->db->jdate($obj->date_ouverture);
				$this->date_fin_validite = $this->db->jdate($obj->date_fin_validite);
				$this->date_cloture = $this->db->jdate($obj->date_cloture);
				$this->tva_tx = $obj->tva_tx;
				$this->localtax1_tx = $obj->localtax1_tx;
				$this->localtax2_tx = $obj->localtax2_tx;
				$this->qty = $obj->qty;
				$this->remise_percent = $obj->remise_percent;
				$this->remise = $obj->remise;
				$this->fk_remise_except = $obj->fk_remise_except;
				$this->subprice = $obj->subprice;
				$this->price_ht = $obj->price_ht;
				$this->total_ht = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_localtax1 = $obj->total_localtax1;
				$this->total_localtax2 = $obj->total_localtax2;
				$this->total_ttc = $obj->total_ttc;
				$this->info_bits = $obj->info_bits;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_ouverture = $obj->fk_user_ouverture;
				$this->fk_user_cloture = $obj->fk_user_cloture;
				$this->commentaire = $obj->commentaire;
				$this->fk_fournprice = $obj->fk_fournprice;
				$marginInfos = getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->fk_fournprice, $obj->pa_ht);
				$this->pa_ht = $marginInfos[0];
				$this->fk_unit     = $obj->fk_unit;

			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *      Update database for contract line
	 *
	 *      @param	User	$user        	User that modify
	 *      @param  int		$notrigger	    0=no, 1=yes (no update trigger)
	 *      @return int         			<0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->fk_contrat=trim($this->fk_contrat);
		$this->fk_product=trim($this->fk_product);
		$this->statut=(int) $this->statut;
		$this->label=trim($this->label);
		$this->description=trim($this->description);
		$this->tva_tx=trim($this->tva_tx);
		$this->localtax1_tx=trim($this->localtax1_tx);
		$this->localtax2_tx=trim($this->localtax2_tx);
		$this->qty=trim($this->qty);
		$this->remise_percent=trim($this->remise_percent);
		$this->remise=trim($this->remise);
		$this->fk_remise_except=trim($this->fk_remise_except);
		$this->subprice=price2num($this->subprice);
		$this->price_ht=price2num($this->price_ht);
		$this->total_ht=trim($this->total_ht);
		$this->total_tva=trim($this->total_tva);
		$this->total_localtax1=trim($this->total_localtax1);
		$this->total_localtax2=trim($this->total_localtax2);
		$this->total_ttc=trim($this->total_ttc);
		$this->info_bits=trim($this->info_bits);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_ouverture=trim($this->fk_user_ouverture);
		$this->fk_user_cloture=trim($this->fk_user_cloture);
		$this->commentaire=trim($this->commentaire);
		//if (empty($this->subprice)) $this->subprice = 0;
		if (empty($this->price_ht)) $this->price_ht = 0;
		if (empty($this->total_ht)) $this->total_ht = 0;
		if (empty($this->total_tva)) $this->total_tva = 0;
		if (empty($this->total_ttc)) $this->total_ttc = 0;

		// Check parameters
		// Put here code to add control on parameters values

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
		$tabprice=calcul_price_total($this->qty, $this->price_ht, $this->remise_percent, $this->tva_tx, $this->localtax1_tx, $this->localtax2_tx, 0, 'HT', 0, 1);
		$this->total_ht  = $tabprice[0];
		$this->total_tva = $tabprice[1];
		$this->total_ttc = $tabprice[2];
		$this->total_localtax1= $tabprice[9];
		$this->total_localtax2= $tabprice[10];

	    if (empty($this->pa_ht)) $this->pa_ht=0;

		// si prix d'achat non renseigné et utilisé pour calcul des marges alors prix achat = prix vente
		if ($this->pa_ht == 0) {
			if ($this->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1))
				$this->pa_ht = $this->subprice * (1 - $this->remise_percent / 100);
		}

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql.= " fk_contrat='".$this->fk_contrat."',";
		$sql.= " fk_product=".($this->fk_product?"'".$this->fk_product."'":'null').",";
		$sql.= " statut='".$this->statut."',";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " description='".$this->db->escape($this->description)."',";
		$sql.= " date_commande=".($this->date_commande!=''?"'".$this->db->idate($this->date_commande)."'":"null").",";
		$sql.= " date_ouverture_prevue=".($this->date_ouverture_prevue!=''?"'".$this->db->idate($this->date_ouverture_prevue)."'":"null").",";
		$sql.= " date_ouverture=".($this->date_ouverture!=''?"'".$this->db->idate($this->date_ouverture)."'":"null").",";
		$sql.= " date_fin_validite=".($this->date_fin_validite!=''?"'".$this->db->idate($this->date_fin_validite)."'":"null").",";
		$sql.= " date_cloture=".($this->date_cloture!=''?"'".$this->db->idate($this->date_cloture)."'":"null").",";
		$sql.= " tva_tx='".$this->tva_tx."',";
		$sql.= " localtax1_tx='".$this->localtax1_tx."',";
		$sql.= " localtax2_tx='".$this->localtax2_tx."',";
		$sql.= " qty='".$this->qty."',";
		$sql.= " remise_percent='".$this->remise_percent."',";
		$sql.= " remise=".($this->remise?"'".$this->remise."'":"null").",";
		$sql.= " fk_remise_except=".($this->fk_remise_except?"'".$this->fk_remise_except."'":"null").",";
		$sql.= " subprice=".($this->subprice != '' ? $this->subprice : "null").",";
		$sql.= " price_ht=".($this->price_ht != '' ? $this->price_ht : "null").",";
		$sql.= " total_ht='".$this->total_ht."',";
		$sql.= " total_tva='".$this->total_tva."',";
		$sql.= " total_localtax1='".$this->total_localtax1."',";
		$sql.= " total_localtax2='".$this->total_localtax2."',";
		$sql.= " total_ttc='".$this->total_ttc."',";
		$sql.= " fk_product_fournisseur_price=".(!empty($this->fk_fournprice)?$this->fk_fournprice:"NULL").",";
		$sql.= " buy_price_ht='".price2num($this->pa_ht)."',";
		$sql.= " info_bits='".$this->info_bits."',";
		$sql.= " fk_user_author=".($this->fk_user_author >= 0?$this->fk_user_author:"NULL").",";
		$sql.= " fk_user_ouverture=".($this->fk_user_ouverture > 0?$this->fk_user_ouverture:"NULL").",";
		$sql.= " fk_user_cloture=".($this->fk_user_cloture > 0?$this->fk_user_cloture:"NULL").",";
		$sql.= " commentaire='".$this->db->escape($this->commentaire)."'";
		$sql.= ", fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$contrat=new Contrat($this->db);
			$contrat->fetch($this->fk_contrat);
			$result=$contrat->update_statut($user);
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			$error++;
			//return -1;
		}

		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && is_array($this->array_options) && count($this->array_options)>0) // For avoid conflicts if trigger used
		{

			$result=$this->insertExtraFields();
			if ($result < 0)
			{
				$error++;
			}
		}

		if (empty($error)) {
		if (! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('LINECONTRACT_UPDATE',$user);
            if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            // End call triggers
		}
		}

		if (empty($error)) {
        $this->db->commit();
		return 1;
		} else {
			$this->db->rollback();
			$this->errors[]=$this->error;
			return -1;
		}
	}


	/**
	 *      Mise a jour en base des champs total_xxx de ligne
	 *		Used by migration process
	 *
	 *		@return		int		<0 if KO, >0 if OK
	 */
	function update_total()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql.= " total_ht=".price2num($this->total_ht,'MT')."";
		$sql.= ",total_tva=".price2num($this->total_tva,'MT')."";
		$sql.= ",total_localtax1=".price2num($this->total_localtax1,'MT')."";
		$sql.= ",total_localtax2=".price2num($this->total_localtax2,'MT')."";
		$sql.= ",total_ttc=".price2num($this->total_ttc,'MT')."";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -2;
		}
	}


	/**
	 * Inserts a contrat line into database
	 *
	 * @param int $notrigger Set to 1 if you don't want triggers to be fired
	 * @return int <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $user;

		// Insertion dans la base
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
		$sql.= " (fk_contrat, label, description, fk_product, qty, tva_tx,";
		$sql.= " localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice,";
		$sql.= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
		$sql.= " info_bits,";
		$sql.= " price_ht, remise, fk_product_fournisseur_price, buy_price_ht";
		if ($this->date_ouverture_prevue > 0) { $sql.= ",date_ouverture_prevue"; }
		if ($this->date_fin_validite > 0)   { $sql.= ",date_fin_validite"; }
		$sql.= ") VALUES ($this->fk_contrat, '', '" . $this->db->escape($this->description) . "',";
		$sql.= ($this->fk_product>0 ? $this->fk_product : "null").",";
		$sql.= " '".$this->qty."',";
		$sql.= " '".$this->tva_tx."',";
		$sql.= " '".$this->localtax1_tx."',";
		$sql.= " '".$this->localtax2_tx."',";
		$sql.= " '".$this->localtax1_type."',";
		$sql.= " '".$this->localtax2_type."',";
		$sql.= " ".price2num($this->remise_percent).",".price2num($this->subprice).",";
		$sql.= " ".price2num($this->total_ht).",".price2num($this->total_tva).",".price2num($this->total_localtax1).",".price2num($this->total_localtax2).",".price2num($this->total_ttc).",";
		$sql.= " '".$this->info_bits."',";
		$sql.= " ".price2num($this->price_ht).",".price2num($this->remise).",";
		if ($this->fk_fournprice > 0) $sql.= ' '.$this->fk_fournprice.',';
		else $sql.= ' null,';
		if ($this->pa_ht > 0) $sql.= ' '.price2num($this->pa_ht);
		else $sql.= ' null';
		if ($this->date_ouverture_prevue > 0) { $sql.= ",'".$this->db->idate($this->date_ouverture_prevue)."'"; }
		if ($this->date_fin_validite > 0) { $sql.= ",'".$this->db->idate($this->date_fin_validite)."'"; }
		$sql.= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'contratdet');

			// FIXME Missing insert of extrafields

			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('LINECONTRACT_CREATE', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error()." sql=".$sql;
			return -1;
		}
	}
}
