<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       class/indexadjustment.class.php
 * \ingroup    indexadjustment
 * \brief      File for the IndexAdjustment business object class
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class IndexAdjustment
 *
 * Business object for index adjustment batches.
 * Each batch represents one adjustment operation (e.g., VPI 2024).
 */
class IndexAdjustment extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'indexadjustment';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'indexadjustment';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_indexadjustment';

	/**
	 * @var string String with name of icon for indexadjustment
	 */
	public $picto = 'fa-percent';

	// Status constants
	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_EXECUTED = 2;
	const STATUS_CANCELLED = 9;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string Reference
	 */
	public $ref;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int Creation timestamp
	 */
	public $datec;

	/**
	 * @var int Modification timestamp
	 */
	public $tms;

	/**
	 * @var int User ID who created
	 */
	public $fk_user_creat;

	/**
	 * @var int User ID who modified
	 */
	public $fk_user_modif;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var string Description
	 */
	public $description;

	/**
	 * @var int Adjustment date (timestamp)
	 */
	public $adjustment_date;

	/**
	 * @var float Adjustment percentage
	 */
	public $adjustment_percent;

	/**
	 * @var int VPI base year
	 */
	public $vpi_base_year;

	/**
	 * @var float VPI base value
	 */
	public $vpi_base_value;

	/**
	 * @var int VPI current year
	 */
	public $vpi_current_year;

	/**
	 * @var float VPI current value
	 */
	public $vpi_current_value;

	/**
	 * @var int Third party ID (null = all customers)
	 */
	public $fk_soc;

	/**
	 * @var int Status (0=draft, 1=validated, 2=executed, 9=cancelled)
	 */
	public $status;

	/**
	 * @var int Execution timestamp
	 */
	public $date_executed;

	/**
	 * @var int User ID who executed
	 */
	public $fk_user_executed;

	/**
	 * @var int Total contracts affected
	 */
	public $total_contracts;

	/**
	 * @var int Total lines affected
	 */
	public $total_lines;

	/**
	 * @var float Total HT before adjustment
	 */
	public $total_ht_before;

	/**
	 * @var float Total HT after adjustment
	 */
	public $total_ht_after;

	/**
	 * @var array Lines (IndexAdjustmentLine objects)
	 */
	public $lines = array();

	/**
	 * @var array Fields for fetch_optionals
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 1, 'index' => 1),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 10, 'searchall' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => 1, 'notnull' => 1, 'position' => 20, 'index' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 30, 'searchall' => 1),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => 3, 'position' => 40),
		'adjustment_date' => array('type' => 'date', 'label' => 'AdjustmentDate', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 50),
		'adjustment_percent' => array('type' => 'double(10,4)', 'label' => 'AdjustmentPercent', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 60),
		'vpi_base_year' => array('type' => 'integer', 'label' => 'VPIBaseYear', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'vpi_base_value' => array('type' => 'double(10,2)', 'label' => 'VPIBaseValue', 'enabled' => 1, 'visible' => 1, 'position' => 80),
		'vpi_current_year' => array('type' => 'integer', 'label' => 'VPICurrentYear', 'enabled' => 1, 'visible' => 1, 'position' => 90),
		'vpi_current_value' => array('type' => 'double(10,2)', 'label' => 'VPICurrentValue', 'enabled' => 1, 'visible' => 1, 'position' => 100),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 1, 'position' => 110, 'index' => 1),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'default' => 0, 'notnull' => 1, 'position' => 120, 'index' => 1, 'arrayofkeyval' => array(0 => 'Draft', 1 => 'Validated', 2 => 'Executed', 9 => 'Cancelled')),
		'date_executed' => array('type' => 'datetime', 'label' => 'DateExecuted', 'enabled' => 1, 'visible' => 1, 'position' => 130),
		'fk_user_executed' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'ExecutedBy', 'enabled' => 1, 'visible' => 1, 'position' => 140),
		'total_contracts' => array('type' => 'integer', 'label' => 'TotalContracts', 'enabled' => 1, 'visible' => 1, 'default' => 0, 'position' => 150),
		'total_lines' => array('type' => 'integer', 'label' => 'TotalLines', 'enabled' => 1, 'visible' => 1, 'default' => 0, 'position' => 160),
		'total_ht_before' => array('type' => 'price', 'label' => 'TotalHTBefore', 'enabled' => 1, 'visible' => 1, 'default' => 0, 'position' => 170),
		'total_ht_after' => array('type' => 'price', 'label' => 'TotalHTAfter', 'enabled' => 1, 'visible' => 1, 'default' => 0, 'position' => 180),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'position' => 510),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 520),
	);

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}
	}

	/**
	 * Create object into database
	 *
	 * @param User $user      User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int            <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = false)
	{
		global $conf;

		$this->status = self::STATUS_DRAFT;
		$this->datec = dol_now();
		$this->fk_user_creat = $user->id;
		$this->entity = $conf->entity;

		// Get next reference
		if (empty($this->ref)) {
			$this->ref = $this->getNextNumRef();
		}

		// Direct SQL insert (createCommon has issues with fk_user_modif)
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
		$sql .= "ref, entity, datec, fk_user_creat, label, description,";
		$sql .= "adjustment_date, adjustment_percent, vpi_base_year, vpi_base_value,";
		$sql .= "vpi_current_year, vpi_current_value, fk_soc, status";
		$sql .= ") VALUES (";
		$sql .= "'" . $this->db->escape($this->ref) . "',";
		$sql .= (int)$this->entity . ",";
		$sql .= "'" . $this->db->idate($this->datec) . "',";
		$sql .= (int)$this->fk_user_creat . ",";
		$sql .= "'" . $this->db->escape($this->label) . "',";
		$sql .= ($this->description ? "'" . $this->db->escape($this->description) . "'" : "NULL") . ",";
		$sql .= "'" . $this->db->idate($this->adjustment_date) . "',";
		$sql .= (float)$this->adjustment_percent . ",";
		$sql .= ($this->vpi_base_year ? (int)$this->vpi_base_year : "NULL") . ",";
		$sql .= ($this->vpi_base_value ? (float)$this->vpi_base_value : "NULL") . ",";
		$sql .= ($this->vpi_current_year ? (int)$this->vpi_current_year : "NULL") . ",";
		$sql .= ($this->vpi_current_value ? (float)$this->vpi_current_value : "NULL") . ",";
		$sql .= ($this->fk_soc > 0 ? (int)$this->fk_soc : "NULL") . ",";
		$sql .= (int)$this->status;
		$sql .= ")";

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		// Direct SQL fetch - only select columns that exist in base schema
		$sql = "SELECT rowid, ref, entity, datec, tms, fk_user_creat,";
		$sql .= " label, description, adjustment_date, adjustment_percent,";
		$sql .= " vpi_base_year, vpi_base_value, vpi_current_year, vpi_current_value,";
		$sql .= " fk_soc, status, date_executed, fk_user_executed,";
		$sql .= " total_contracts, total_lines, total_ht_before, total_ht_after";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		if ($id > 0) {
			$sql .= " WHERE rowid = " . (int)$id;
		} elseif ($ref) {
			$sql .= " WHERE ref = '" . $this->db->escape($ref) . "'";
		} else {
			return 0;
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->entity = $obj->entity;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->label = $obj->label;
				$this->description = $obj->description;
				$this->adjustment_date = $this->db->jdate($obj->adjustment_date);
				$this->adjustment_percent = $obj->adjustment_percent;
				$this->vpi_base_year = $obj->vpi_base_year;
				$this->vpi_base_value = $obj->vpi_base_value;
				$this->vpi_current_year = $obj->vpi_current_year;
				$this->vpi_current_value = $obj->vpi_current_value;
				$this->fk_soc = $obj->fk_soc;
				$this->status = $obj->status;
				$this->date_executed = $this->db->jdate($obj->date_executed);
				$this->fk_user_executed = $obj->fk_user_executed;
				$this->total_contracts = $obj->total_contracts;
				$this->total_lines = $obj->total_lines;
				$this->total_ht_before = $obj->total_ht_before;
				$this->total_ht_after = $obj->total_ht_after;

				$this->fetchLines();
				return 1;
			}
			return 0;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		require_once __DIR__ . '/indexadjustmentline.class.php';

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "indexadjustment_line";
		$sql .= " WHERE fk_indexadjustment = " . (int)$this->id;
		$sql .= " ORDER BY rowid ASC";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);

				$line = new IndexAdjustmentLine($this->db);
				$line->fetch($obj->rowid);
				$this->lines[] = $line;

				$i++;
			}
			return $num;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user      User that modifies
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = false)
	{
		$this->fk_user_modif = $user->id;

		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Validate object
	 *
	 * @param User $user      User making status change
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function validate($user, $notrigger = false)
	{
		global $conf;

		if ($this->status != self::STATUS_DRAFT) {
			return 0;
		}

		$this->status = self::STATUS_VALIDATED;

		return $this->update($user, $notrigger);
	}

	/**
	 * Set executed status
	 *
	 * @param User $user      User making status change
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function setExecuted($user, $notrigger = false)
	{
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		$this->status = self::STATUS_EXECUTED;
		$this->date_executed = dol_now();
		$this->fk_user_executed = $user->id;

		return $this->update($user, $notrigger);
	}

	/**
	 * Cancel object
	 *
	 * @param User $user      User making status change
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = false)
	{
		$this->status = self::STATUS_CANCELLED;

		return $this->update($user, $notrigger);
	}

	/**
	 * Return next reference not already used
	 *
	 * @return string Next ref
	 */
	public function getNextNumRef()
	{
		global $conf;

		$year = date('Y');
		$prefix = 'IA-' . $year . '-';

		$sql = "SELECT MAX(CAST(SUBSTRING(ref, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as maxnum";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE ref LIKE '" . $this->db->escape($prefix) . "%'";
		$sql .= " AND entity IN (" . getEntity($this->element) . ")";

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$num = $obj->maxnum ? $obj->maxnum + 1 : 1;
			return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
		}

		return $prefix . '0001';
	}

	/**
	 * Return clickable link of object (with eventually picto)
	 *
	 * @param int    $withpicto              Add picto into link
	 * @param string $option                 Where point the link
	 * @param int    $notooltip              1=Disable tooltip
	 * @param string $morecss                Add more css on link
	 * @param int    $save_lastsearch_value  -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string                        String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		$result = '';

		$label = '<u>' . $langs->trans("IndexAdjustment") . '</u>';
		$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if ($this->label) {
			$label .= '<br><b>' . $langs->trans('Label') . ':</b> ' . $this->label;
		}

		$url = dol_buildpath('/indexadjustment/card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowIndexAdjustment");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}

	/**
	 * Return status label of object
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string   Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 * Return status label for a status
	 *
	 * @param int $status Id status
	 * @param int $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string     Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		$langs->load("indexadjustment@indexadjustment");

		$statusType = 'status' . $status;
		$labelStatus = $labelStatusShort = '';

		switch ($status) {
			case self::STATUS_DRAFT:
				$labelStatus = $langs->trans('StatusDraft');
				$labelStatusShort = $langs->trans('StatusDraft');
				$statusType = 'status0';
				break;
			case self::STATUS_VALIDATED:
				$labelStatus = $langs->trans('StatusValidated');
				$labelStatusShort = $langs->trans('StatusValidated');
				$statusType = 'status1';
				break;
			case self::STATUS_EXECUTED:
				$labelStatus = $langs->trans('StatusExecuted');
				$labelStatusShort = $langs->trans('StatusExecuted');
				$statusType = 'status4';
				break;
			case self::STATUS_CANCELLED:
				$labelStatus = $langs->trans('StatusCancelled');
				$labelStatusShort = $langs->trans('StatusCancelled');
				$statusType = 'status9';
				break;
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}

	/**
	 * Return price difference
	 *
	 * @return float Price difference (after - before)
	 */
	public function getPriceDiff()
	{
		return $this->total_ht_after - $this->total_ht_before;
	}
}
