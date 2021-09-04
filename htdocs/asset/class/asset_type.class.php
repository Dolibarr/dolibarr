<?php
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
	public $picto = 'asset';

	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>15, 'index'=>1),
		'label' =>array('type'=>'varchar(50)', 'label'=>'Label', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>25, 'showoncombobox'=>1),
		'accountancy_code_asset' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountAsset', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'accountancy_code_depreciation_asset' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountDepreciationAsset', 'enabled'=>0, 'visible'=>-1, 'position'=>35),
		'accountancy_code_depreciation_expense' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountDepreciationExpense', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'accountancy_code_value_asset_sold' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountValueAssetSold', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'accountancy_code_receivable_on_assignment' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountReceivableOnAssignment', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'accountancy_code_proceeds_from_sales' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountProceedsFromSales', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'accountancy_code_vat_collected' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountCollectedVAT', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'accountancy_code_vat_deductible' =>array('type'=>'varchar(32)', 'label'=>'AccountancyAccountDeductibleVAT', 'enabled'=>0, 'visible'=>-1, 'position'=>40),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'note' =>array('type'=>'mediumtext', 'label'=>'Note', 'enabled'=>0, 'visible'=>-1, 'position'=>45),
	);

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string Asset type label
	 */
	public $label;

	/** @var string Accountancy account asset */
	public $accountancy_code_asset;

	/** @var string Accountancy account depreciation asset */
	public $accountancy_code_depreciation_asset;

	/** @var string Accountancy account depreciation expense */
	public $accountancy_code_depreciation_expense;

	/** @var string Accounting account value of assets sold */
	public $accountancy_code_value_asset_sold;

	/** @var string Accounting account receivable on assignment */
	public $accountancy_code_receivable_on_assignment;

	/** @var string Accounting account proceeds from sales */
	public $accountancy_code_proceeds_from_sales;

	/** @var string Accounting account collected VAT on assets */
	public $accountancy_code_vat_collected;

	/** @var string Accounting account deductible VAT on assets */
	public $accountancy_code_vat_deductible;

	/** @var string 	Public note */
	public $note;

	/** @var array Array of asset */
	public $asset = array();

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled)) {
			$this->fields['entity']['enabled'] = 0;
		}
	}


	/**
	 *  Fonction qui permet de creer le type d'immobilisation
	 *
	 *  @param	User		$user			User making creation
	 *  @param	int			$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int							>0 if OK, < 0 if KO
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		$this->label = trim($this->label);
		$this->accountancy_code_asset = trim($this->accountancy_code_asset);
		$this->accountancy_code_depreciation_asset = trim($this->accountancy_code_depreciation_asset);
		$this->accountancy_code_depreciation_expense = trim($this->accountancy_code_depreciation_expense);
		$this->accountancy_code_value_asset_sold = trim($this->accountancy_code_value_asset_sold);
		$this->accountancy_code_receivable_on_assignment = trim($this->accountancy_code_receivable_on_assignment);
		$this->accountancy_code_proceeds_from_sales = trim($this->accountancy_code_proceeds_from_sales);
		$this->accountancy_code_vat_collected = trim($this->accountancy_code_vat_collected);
		$this->accountancy_code_vat_deductible = trim($this->accountancy_code_vat_deductible);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."asset_type (";
		$sql .= "label";
		$sql .= ", accountancy_code_asset";
		$sql .= ", accountancy_code_depreciation_asset";
		$sql .= ", accountancy_code_depreciation_expense";
		$sql .= ", accountancy_code_value_asset_sold";
		$sql .= ", accountancy_code_receivable_on_assignment";
		$sql .= ", accountancy_code_proceeds_from_sales";
		$sql .= ", accountancy_code_vat_collected";
		$sql .= ", accountancy_code_vat_deductible";
		$sql .= ", note";
		$sql .= ", entity";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_asset)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_depreciation_asset)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_depreciation_expense)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_value_asset_sold)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_receivable_on_assignment)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_proceeds_from_sales)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_vat_collected)."'";
		$sql .= ", '".$this->db->escape($this->accountancy_code_vat_deductible)."'";
		$sql .= ", '".$this->db->escape($this->note)."'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ")";
		$sql .= ")";

		dol_syslog("Asset_type::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."asset_type");

			$result = $this->update($user, 1);
			if ($result < 0) {
				$this->db->rollback();
				return -3;
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ASSET_TYPE_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Met a jour en base donnees du type
	 *
	 *  @param	User		$user			Object user making change
	 *  @param	int			$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int							>0 if OK, < 0 if KO
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf, $hookmanager;

		$error = 0;

		$this->label = trim($this->label);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."asset_type";
		$sql .= " SET";
		$sql .= " label = '".$this->db->escape($this->label)."'";
		$sql .= ", accountancy_code_asset = '".$this->db->escape($this->accountancy_code_asset)."'";
		$sql .= ", accountancy_code_depreciation_asset = '".$this->db->escape($this->accountancy_code_depreciation_asset)."'";
		$sql .= ", accountancy_code_depreciation_expense = '".$this->db->escape($this->accountancy_code_depreciation_expense)."'";
		$sql .= ", accountancy_code_value_asset_sold = '".$this->db->escape($this->accountancy_code_value_asset_sold)."'";
		$sql .= ", accountancy_code_receivable_on_assignment = '".$this->db->escape($this->accountancy_code_receivable_on_assignment)."'";
		$sql .= ", accountancy_code_proceeds_from_sales = '".$this->db->escape($this->accountancy_code_proceeds_from_sales)."'";
		$sql .= ", accountancy_code_vat_collected = '".$this->db->escape($this->accountancy_code_vat_collected)."'";
		$sql .= ", accountancy_code_vat_deductible = '".$this->db->escape($this->accountancy_code_vat_deductible)."'";
		$sql .= ", note = '".$this->db->escape($this->note)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			$action = 'update';

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ASSET_TYPE_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
				return -$error;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Fonction qui permet de supprimer le status de l'adherent
	 *
	 *  @return		int					>0 if OK, 0 if not found, < 0 if KO
	 */
	public function delete()
	{
		global $user;

		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."asset_type";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('ASSET_TYPE_DELETE', $user);
			if ($result < 0) {
				$error++; $this->db->rollback(); return -2;
			}
			// End call triggers

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Fonction qui permet de recuperer le status de l'immobilisation
	 *
	 *  @param 		int		$rowid			Id of member type to load
	 *  @return		int						<0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT d.rowid, d.label as label, d.accountancy_code_asset, d.accountancy_code_depreciation_asset, d.accountancy_code_depreciation_expense,";
		$sql .= " accountancy_code_value_asset_sold, accountancy_code_receivable_on_assignment, accountancy_code_proceeds_from_sales, accountancy_code_vat_collected, accountancy_code_vat_deductible, d.note";
		$sql .= " FROM ".MAIN_DB_PREFIX."asset_type as d";
		$sql .= " WHERE d.rowid = ".(int) $rowid;

		dol_syslog("Asset_type::fetch", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->label = $obj->label;
				$this->accountancy_code_asset = $obj->accountancy_code_asset;
				$this->accountancy_code_depreciation_asset = $obj->accountancy_code_depreciation_asset;
				$this->accountancy_code_depreciation_expense = $obj->accountancy_code_depreciation_expense;
				$this->accountancy_code_value_asset_sold = $obj->accountancy_code_value_asset_sold;
				$this->accountancy_code_receivable_on_assignment = $obj->accountancy_code_receivable_on_assignment;
				$this->accountancy_code_proceeds_from_sales = $obj->accountancy_code_proceeds_from_sales;
				$this->accountancy_code_vat_collected = $obj->accountancy_code_vat_collected;
				$this->accountancy_code_vat_deductible = $obj->accountancy_code_vat_deductible;
				$this->note = $obj->note;
			}

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of asset's type
	 *
	 *  @return 	array	List of types of members
	 */
	public function liste_array()
	{
		// phpcs:enable
		global $conf, $langs;

		$assettypes = array();

		$sql = "SELECT rowid, label as label";
		$sql .= " FROM ".MAIN_DB_PREFIX."asset_type";
		$sql .= " WHERE entity IN (".getEntity('asset_type').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);

			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);

					$assettypes[$obj->rowid] = $langs->trans($obj->label);
					$i++;
				}
			}
		} else {
			print $this->db->error();
		}
		return $assettypes;
	}

	/**
	 * 	Return array of Asset objects for asset type this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter string to exclude. This parameter must not be provided by input of users
	 *  @param	int		$mode				0=Return array of asset instance
	 *  									1=Return array of asset instance without extra data
	 *  									2=Return array of asset id only
	 * 	@return	mixed						Array of asset or -1 on error
	 */
	public function listAssetForAssetType($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT a.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."asset as a";
		$sql .= " WHERE a.entity IN (".getEntity('asset').")";
		$sql .= " AND a.fk_asset_type = ".((int) $this->id);
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}

		dol_syslog(get_class($this)."::listAssetsForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode < 2) {
						$assetstatic = new Asset($this->db);
						if ($mode == 1) {
							$assetstatic->fetch($obj->rowid, '', '', '', false, false);
						} else {
							$assetstatic->fetch($obj->rowid);
						}
						$ret[$obj->rowid] = $assetstatic;
					} else {
						$ret[$obj->rowid] = $obj->rowid;
					}
				}
			}

			$this->db->free($resql);

			$this->asset = $ret;

			return $ret;
		} else {
			$this->error = $this->db->lasterror();
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
	public function getNomUrl($withpicto = 0, $maxlen = 0, $notooltip = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("ShowTypeCard", $this->label);

		$linkstart = '<a href="'.DOL_URL_ROOT.'/asset/type.php?rowid='.((int) $this->id).'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($maxlen ?dol_trunc($this->label, $maxlen) : $this->label);
		}
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
	public function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialize parameters
		$this->id = 0;
		$this->ref = 'ATSPEC';
		$this->specimen = 1;

		$this->label = 'ASSET TYPE SPECIMEN';
		$this->note = 'This is a note';

		// Assets of this asset type is just me
		$this->asset = array(
			$user->id => $user
		);
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
}
