<?php
<<<<<<< HEAD
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@zendsi.com>
=======
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/asset/class/asset_type.class.php
 *  \ingroup    asset
 *  \brief      File of class to manage asset types
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage asset type
 */
class AssetType extends CommonObject
{
<<<<<<< HEAD
	public $table_element = 'asset_type';
	public $element = 'asset_type';
	public $picto = 'group';
	public $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/** @var string Label */
	public $label;
	/** @var string Accountancy code asset */
	public $accountancy_code_asset;
	/** @var string Accountancy code depreciation asset */
	public $accountancy_code_depreciation_asset;
	/** @var string Accountancy code depreciation expense */
	public $accountancy_code_depreciation_expense;
	/** @var string 	Public note */
	public $note;
=======
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'asset_type';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'asset_type';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'invoice';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string Asset type label
	 */
	public $label;

	/** @var string Accountancy code asset */
	public $accountancy_code_asset;

	/** @var string Accountancy code depreciation asset */
	public $accountancy_code_depreciation_asset;

	/** @var string Accountancy code depreciation expense */
	public $accountancy_code_depreciation_expense;

	/** @var string 	Public note */
	public $note;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/** @var array Array of asset */
	public $asset=array();


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
=======
    public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->db = $db;
	}


	/**
	 *  Fonction qui permet de creer le type d'immobilisation
	 *
	 *  @param	User		$user			User making creation
	 *  @param	int			$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int							>0 if OK, < 0 if KO
	 */
<<<<<<< HEAD
	function create($user,$notrigger=0)
	{
=======
    public function create($user, $notrigger = 0)
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf;

		$error=0;

		$this->label=trim($this->label);
		$this->accountancy_code_asset = trim($this->accountancy_code_asset);
		$this->accountancy_code_depreciation_asset = trim($this->accountancy_code_depreciation_asset);
		$this->accountancy_code_depreciation_expense = trim($this->accountancy_code_depreciation_expense);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."asset_type (";
		$sql.= "label";
		$sql.= ", accountancy_code_asset";
		$sql.= ", accountancy_code_depreciation_asset";
		$sql.= ", accountancy_code_depreciation_expense";
<<<<<<< HEAD
=======
		$sql.= ", note";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->accountancy_code_asset)."'";
		$sql.= ", '".$this->db->escape($this->accountancy_code_depreciation_asset)."'";
		$sql.= ", '".$this->db->escape($this->accountancy_code_depreciation_expense)."'";
<<<<<<< HEAD
=======
		$sql.= ", '".$this->db->escape($this->note)."'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog("Asset_type::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."asset_type");

<<<<<<< HEAD
			$result = $this->update($user,1);
=======
			$result = $this->update($user, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($result < 0)
			{
				$this->db->rollback();
				return -3;
			}

			if (! $notrigger)
			{
				// Call trigger
<<<<<<< HEAD
				$result=$this->call_trigger('ASSET_TYPE_CREATE',$user);
=======
				$result=$this->call_trigger('ASSET_TYPE_CREATE', $user);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
<<<<<<< HEAD
	}
=======
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *  Met a jour en base donnees du type
	 *
	 *  @param	User		$user			Object user making change
	 *  @param	int			$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int							>0 if OK, < 0 if KO
	 */
<<<<<<< HEAD
	function update($user,$notrigger=0)
=======
    public function update($user, $notrigger = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf, $hookmanager;

		$error=0;

		$this->label=trim($this->label);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."asset_type ";
		$sql.= "SET ";
		$sql.= "label = '".$this->db->escape($this->label) ."',";
		$sql.= "accountancy_code_asset = '".$this->db->escape($this->accountancy_code_asset)."',";
		$sql.= "accountancy_code_depreciation_asset = '".$this->db->escape($this->accountancy_code_depreciation_asset)."',";
<<<<<<< HEAD
		$sql.= "accountancy_code_depreciation_expense = '".$this->db->escape($this->accountancy_code_depreciation_expense)."'";
=======
		$sql.= "accountancy_code_depreciation_expense = '".$this->db->escape($this->accountancy_code_depreciation_expense)."',";
		$sql.= "note = '".$this->db->escape($this->note) ."'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$sql.= " WHERE rowid =".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$action='update';

			// Actions on extra fields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (! $error && ! $notrigger)
			{
				// Call trigger
<<<<<<< HEAD
				$result=$this->call_trigger('ASSET_TYPE_MODIFY',$user);
=======
				$result=$this->call_trigger('ASSET_TYPE_MODIFY', $user);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
				return -$error;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Fonction qui permet de supprimer le status de l'adherent
	 *
	 *  @return		int					>0 if OK, 0 if not found, < 0 if KO
	 */
<<<<<<< HEAD
	function delete()
=======
	public function delete()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."asset_type";
		$sql.= " WHERE rowid = ".$this->id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Call trigger
<<<<<<< HEAD
			$result=$this->call_trigger('ASSET_TYPE_DELETE',$user);
=======
			$result=$this->call_trigger('ASSET_TYPE_DELETE', $user);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($result < 0) { $error++; $this->db->rollback(); return -2; }
			// End call triggers

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
	 *  Fonction qui permet de recuperer le status de l'immobilisation
	 *
	 *  @param 		int		$rowid			Id of member type to load
	 *  @return		int						<0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function fetch($rowid)
=======
	public function fetch($rowid)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$sql = "SELECT d.rowid, d.label as label, d.accountancy_code_asset, d.accountancy_code_depreciation_asset, d.accountancy_code_depreciation_expense, d.note";
		$sql .= " FROM ".MAIN_DB_PREFIX."asset_type as d";
		$sql .= " WHERE d.rowid = ".(int) $rowid;

		dol_syslog("Asset_type::fetch", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

<<<<<<< HEAD
				$this->id                                       = $obj->rowid;
				$this->ref                                      = $obj->rowid;
				$this->label                                    = $obj->label;
				$this->accountancy_code_asset                   = $obj->accountancy_code_asset;
				$this->accountancy_code_depreciation_asset      = $obj->accountancy_code_depreciation_asset;
				$this->accountancy_code_depreciation_expense    = $obj->accountancy_code_depreciation_expense;
				$this->note                                     = $obj->note;
=======
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->label = $obj->label;
				$this->accountancy_code_asset = $obj->accountancy_code_asset;
				$this->accountancy_code_depreciation_asset = $obj->accountancy_code_depreciation_asset;
				$this->accountancy_code_depreciation_expense = $obj->accountancy_code_depreciation_expense;
				$this->note = $obj->note;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  Return list of asset's type
	 *
	 *  @return 	array	List of types of members
	 */
<<<<<<< HEAD
	function liste_array()
	{
=======
	public function liste_array()
	{
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf,$langs;

		$assettypes = array();

		$sql = "SELECT rowid, label as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."asset_type";
		$sql.= " WHERE entity IN (".getEntity('asset_type').")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);

					$assettypes[$obj->rowid] = $langs->trans($obj->label);
					$i++;
				}
			}
		}
		else
		{
			print $this->db->error();
		}
		return $assettypes;
	}

	/**
	 * 	Return array of Asset objects for asset type this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude
	 *  @param	int		$mode				0=Return array of asset instance
	 *  									1=Return array of asset instance without extra data
	 *  									2=Return array of asset id only
	 * 	@return	mixed						Array of asset or -1 on error
	 */
<<<<<<< HEAD
	function listAssetForAssetType($excludefilter='', $mode=0)
=======
	public function listAssetForAssetType($excludefilter = '', $mode = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf, $user;

		$ret=array();

		$sql = "SELECT a.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."asset as a";
		$sql.= " WHERE a.entity IN (".getEntity('asset').")";
		$sql.= " AND a.fk_asset_type = ".$this->id;
		if (! empty($excludefilter)) $sql.=' AND ('.$excludefilter.')';

		dol_syslog(get_class($this)."::listAssetsForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				if (! array_key_exists($obj->rowid, $ret))
				{
					if ($mode < 2)
					{
						$assetstatic=new Asset($this->db);
						if ($mode == 1) {
<<<<<<< HEAD
							$assetstatic->fetch($obj->rowid,'','','',false, false);
=======
							$assetstatic->fetch($obj->rowid, '', '', '', false, false);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						} else {
							$assetstatic->fetch($obj->rowid);
						}
						$ret[$obj->rowid]=$assetstatic;
					}
					else $ret[$obj->rowid]=$obj->rowid;
				}
			}

			$this->db->free($resql);

			$this->asset=$ret;

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    	Return clicable name (with picto eventually)
	 *
	 *		@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *		@param		int		$maxlen			length max label
	 *  	@param		int  	$notooltip		1=Disable tooltip
	 *		@return		string					String with URL
	 */
<<<<<<< HEAD
	function getNomUrl($withpicto=0, $maxlen=0, $notooltip=0)
=======
	public function getNomUrl($withpicto = 0, $maxlen = 0, $notooltip = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;

		$result='';
<<<<<<< HEAD
		$label=$langs->trans("ShowTypeCard",$this->label);
=======
		$label=$langs->trans("ShowTypeCard", $this->label);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		$linkstart = '<a href="'.DOL_URL_ROOT.'/asset/type.php?rowid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
<<<<<<< HEAD
		if ($withpicto != 2) $result.= ($maxlen?dol_trunc($this->label,$maxlen):$this->label);
=======
		if ($withpicto != 2) $result.= ($maxlen?dol_trunc($this->label, $maxlen):$this->label);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
<<<<<<< HEAD
	function initAsSpecimen()
	{
=======
    public function initAsSpecimen()
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		global $conf, $user, $langs;

		// Initialize parameters
		$this->id = 0;
		$this->ref = 'ATSPEC';
		$this->specimen=1;

		$this->label='ASSET TYPE SPECIMEN';
		$this->note='This is a note';

		// Assets of this asset type is just me
		$this->asset=array(
			$user->id => $user
		);
<<<<<<< HEAD
	}

	/**
	 *     getLibStatut
	 *
	 *     @return string     Return status of a type of asset
	 */
	function getLibStatut()
	{
		return '';
	}

=======
    }

    /**
     *     getLibStatut
     *
     *     @return string     Return status of a type of asset
     */
    public function getLibStatut()
    {
        return '';
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
