<?php
/* Copyright (C) 2016	    Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2022       Open-Dsi			<support@open-dsi.fr>
 * Copyright (C) 2023-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/variants/class/ProductAttribute.class.php
 *	\ingroup    variants
 *	\brief      File of the ProductAttribute class
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class ProductAttribute
 * Used to represent a Product attribute
 * Examples:
 * - Attribute 'color' (of type ProductAttribute) with values 'white', 'blue' or 'red' (each of type ProductAttributeValue).
 * - Attribute 'size' (of type ProductAttribute) with values 'S', 'L' or 'XL' (each of type ProductAttributeValue).
 */
class ProductAttribute extends CommonObject
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string ID of module.
	 */
	public $module = 'variants';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'productattribute';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'product_attribute';

	/**
	 * @var string    Name of sub table line
	 */
	public $table_element_line = 'product_attribute_value';

	/**
	 * @var string Field with ID of parent key if this field has a parent or for child tables
	 */
	public $fk_element = 'fk_product_attribute';

	/**
	 * @var string String with name of icon for conferenceorbooth. Must be the part after the 'object_' into object_conferenceorbooth.png
	 */
	public $picto = 'product';

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'visible' => 1, 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'css' => 'width200'),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'ExternalRef', 'enabled' => 1, 'visible' => 0, 'position' => 20, 'searchall' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'help' => "", 'showoncombobox' => 1,),
		'position' => array('type' => 'integer', 'label' => 'Rank', 'enabled' => 1, 'visible' => 0, 'default' => '0', 'position' => 40, 'notnull' => 1,),
	);

	/**
	 * @var int rowid
	 */
	public $id;

	/**
	 * @var string ref
	 */
	public $ref;

	/**
	 * @var string external ref
	 */
	public $ref_ext;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var int position
	 * @deprecated
	 * @see $position
	 */
	public $rang;

	/**
	 * @var int position
	 */
	public $position;

	/**
	 * @var ProductAttributeValue[]
	 */
	public $lines = array();

	/**
	 * @var ProductAttributeValue
	 */
	public $line;

	/**
	 * @var int		Number of product that use this attribute
	 */
	public $is_used_by_products;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 0;
		$this->entity = $conf->entity;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Creates a product attribute
	 *
	 * @param   User    $user      Object user
	 * @param   int     $notrigger Do not execute trigger
	 * @return 					int Return integer <0 KO, Id of new variant if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->ref = strtoupper(dol_sanitizeFileName(dol_string_nospecial(trim($this->ref)))); // Ref must be uppercase
		$this->label = trim($this->label);
		$this->position = $this->position > 0 ? $this->position : 0;

		// Position to use
		if (empty($this->position)) {
			$positionmax = $this->getMaxAttributesPosition();
			$this->position = $positionmax + 1;
		}

		// Check parameters
		if (empty($this->ref)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
		$sql .= " ref, ref_ext, label, entity, position";
		$sql .= ")";
		$sql .= " VALUES (";
		$sql .= "  '" . $this->db->escape($this->ref) . "'";
		$sql .= ", '" . $this->db->escape($this->ref_ext) . "'";
		$sql .= ", '" . $this->db->escape($this->label) . "'";
		$sql .= ", " . ((int) $this->entity);
		$sql .= ", " . ((int) $this->position);
		$sql .= ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Fetches the properties of a product attribute
	 *
	 * @param int $id Attribute id
	 * @return int Return integer <1 KO, >1 OK
	 */
	public function fetch($id)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$id = $id > 0 ? $id : 0;

		// Check parameters
		if (empty($id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT rowid, ref, ref_ext, label, position";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . ((int) $id);
		$sql .= " AND entity IN (" . getEntity('product') . ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$numrows = $this->db->num_rows($resql);
		if ($numrows) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->ref = $obj->ref;
			$this->ref_ext = $obj->ref_ext;
			$this->label = $obj->label;
			$this->rang = $obj->position; // deprecated
			$this->position = $obj->position;
		}
		$this->db->free($resql);

		return $numrows;
	}

	/**
	 * Returns an array with all the ProductAttribute objects of a given entity
	 *
	 * @return ProductAttribute[]
	 */
	public function fetchAll()
	{
		$return = array();

		$sql = "SELECT rowid, ref, ref_ext, label, position";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity('product') . ")";
		$sql .= $this->db->order("position", "asc");

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_print_error($this->db);
			return $return;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$tmp = new ProductAttribute($this->db);

			$tmp->id = $obj->rowid;
			$tmp->ref = $obj->ref;
			$tmp->ref_ext = $obj->ref_ext;
			$tmp->label = $obj->label;
			$tmp->rang = $obj->position; // deprecated
			$tmp->position = $obj->position;

			$return[] = $tmp;
		}

		return $return;
	}

	/**
	 * Updates a product attribute
	 *
	 * @param   User		$user		User who updates the attribute
	 * @param   int<0,1>	$notrigger	1 = Do not execute trigger (0 by default)
	 * @return 	int<-1,1>				<0 if KO, 1 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;
		$this->ref = strtoupper(dol_sanitizeFileName(dol_string_nospecial(trim($this->ref)))); // Ref must be uppercase
		$this->label = trim($this->label);

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if (empty($this->ref)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";

		$sql .= "  ref = '" . $this->db->escape($this->ref) . "'";
		$sql .= ", ref_ext = '" . $this->db->escape($this->ref_ext) . "'";
		$sql .= ", label = '" . $this->db->escape($this->label) . "'";
		$sql .= ", position = " . ((int) $this->position);

		$sql .= " WHERE rowid = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_MODIFY', $user);
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
			return -1 * $error;
		}
	}

	/**
	 * Deletes a product attribute
	 *
	 * @param   User    $user      Object user
	 * @param   int     $notrigger Do not execute trigger
	 * @return 	int Return integer <0 KO, >0 OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$result = $this->isUsed();
		if ($result < 0) {
			return -1;
		} elseif ($result > 0) {
			$this->errors[] = $langs->trans('ErrorAttributeIsUsedIntoProduct');
			return -1;
		}

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			// Delete values
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element_line;
			$sql .= " WHERE " . $this->fk_element . " = " . ((int) $this->id);

			dol_syslog(__METHOD__ . ' - Delete values', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " WHERE rowid = " . ((int) $this->id);

			dol_syslog(__METHOD__ . ' - Delete attribute', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load array lines
	 *
	 * @param	string		$filters	Filter on other fields
	 * @return	int						    Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines($filters = '')
	{
		// phpcs:enable
		global $langs;

		$this->lines = array();

		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT td.rowid, td.fk_product_attribute, td.ref, td.value, td.position";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element_line . " AS td";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $this->table_element . " AS t ON t.rowid = td." . $this->fk_element;
		$sql .= " WHERE t.rowid = " . ((int) $this->id);
		$sql .= " AND t.entity IN (" . getEntity('product') . ")";
		if ($filters) {
			$sql .= $filters;
		}
		$sql .= $this->db->order("td.position", "asc");

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -3;
		}

		$num = $this->db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$line = new ProductAttributeValue($this->db);

				$line->id = $obj->rowid;
				$line->fk_product_attribute = $obj->fk_product_attribute;
				$line->ref = $obj->ref;
				$line->value = $obj->value;
				$line->position = $obj->position;

				$this->lines[$i] = $line;
				$i++;
			}
		}
		$this->db->free($resql);

		return $num;
	}

	/**
	 * 	Retrieve an array of proposal lines
	 *	@param  string              $filters        Filter on other fields
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	public function getLinesArray($filters = '')
	{
		return $this->fetch_lines($filters);
	}

	/**
	 *    	Add a proposal line into database (linked to product/service or not)
	 *      The parameters are already supposed to be appropriate and with final values to the call
	 *      of this method. Also, for the VAT rate, it must have already been defined
	 *      by whose calling the method get_default_tva (societe_vendeuse, societe_acheteuse, '' product)
	 *      and desc must already have the right value (it's up to the caller to manage multilanguage)
	 *
	 * @param	string	$ref			Ref of the value
	 * @param	string	$value			Value
	 * @param	int		$position		Position of line
	 * 	@param	int		$notrigger		disable line update trigger
	 * @return	int						>0 if OK, <0 if KO
	 */
	public function addLine($ref, $value, $position = -1, $notrigger = 0)
	{
		global $langs, $user;
		dol_syslog(__METHOD__ . " id=".$this->id.", ref=".$ref.", value=".$value.", notrigger=".$notrigger);
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		//Fetch current line from the database and then clone the object and set it in $oldcopy property
		$this->line = new ProductAttributeValue($this->db);

		// Position to use
		$positiontouse = $position;
		if ($positiontouse == -1) {
			$positionmax = $this->line_max(0);
			$positiontouse = $positionmax + 1;
		}

		$this->line->context = $this->context;
		$this->line->fk_product_attribute = $this->id;
		$this->line->ref = $ref;
		$this->line->value = $value;
		$this->line->position = $positiontouse;

		$result = $this->line->create($user, $notrigger);

		if ($result < 0) {
			$this->error = $this->line->error;
			$this->errors = $this->line->errors;
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->line->id;
		}
	}


	/**
	 *  Update a line
	 *
	 * @param	int		$lineid       	Id of line
	 * @param	string	$ref			Ref of the value
	 * @param	string	$value			Value
	 * @param	int		$notrigger		disable line update trigger
	 * @return	int     	        	>=0 if OK, <0 if KO
	 */
	public function updateLine($lineid, $ref, $value, $notrigger = 0)
	{
		global $user;

		dol_syslog(__METHOD__ . " lineid=$lineid, ref=$ref, value=$value, notrigger=$notrigger");

		// Clean parameters
		$lineid = $lineid > 0 ? $lineid : 0;

		$this->db->begin();

		//Fetch current line from the database and then clone the object and set it in $oldcopy property
		$this->line = new ProductAttributeValue($this->db);
		$result = $this->line->fetch($lineid);
		if ($result > 0) {
			$this->line->oldcopy = clone $this->line;

			$this->line->context = $this->context;
			$this->line->ref = $ref;
			$this->line->value = $value;

			$result = $this->line->update($user, $notrigger);
		}

		if ($result < 0) {
			$this->error = $this->line->error;
			$this->errors = $this->line->errors;
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $result;
		}
	}

	/**
	 *  Delete a line
	 *
	 * @param   User    $user      Object user
	 * @param	int		$lineid			Id of line to delete
	 * @param	int		$notrigger		disable line update trigger
	 * @return	int         			>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $lineid, $notrigger = 0)
	{
		dol_syslog(__METHOD__ . " lineid=$lineid, notrigger=$notrigger");

		// Clean parameters
		$lineid = $lineid > 0 ? $lineid : 0;

		$this->db->begin();

		//Fetch current line from the database
		$this->line = new ProductAttributeValue($this->db);
		$result = $this->line->fetch($lineid);
		if ($result > 0) {
			$this->line->context = $this->context;

			$result = $this->line->delete($user, $notrigger);
		}

		if ($result < 0) {
			$this->error = $this->line->error;
			$this->errors = $this->line->errors;
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $result;
		}
	}

	/**
	 * Returns the number of values for this attribute
	 *
	 * @return int
	 */
	public function countChildValues()
	{
		global $langs;
		$error = 0;
		$count = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT COUNT(*) AS count";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element_line;
		$sql .= " WHERE " . $this->fk_element . " = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		if ($obj = $this->db->fetch_object($resql)) {
			$count = $obj->count;
		}

		return $count;
	}

	/**
	 * Return the number of product variants using this attribute
	 *
	 * @return int<-1,max>		-1 if K0, nb of variants using this attribute
	 */
	public function countChildProducts()
	{
		global $langs;
		$error = 0;
		$count = 0;

		// Clean parameters
		$this->id = ($this->id > 0) ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT COUNT(*) AS count";
		$sql .= " FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val AS pac2v";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_attribute_combination AS pac ON pac2v.fk_prod_combination = pac.rowid";
		$sql .= " WHERE pac2v.fk_prod_attr = " . ((int) $this->id);
		$sql .= " AND pac.entity IN (" . getEntity('product') . ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		if ($obj = $this->db->fetch_object($resql)) {
			$count = $obj->count;
		}

		return $count;
	}

	/**
	 * Test if this attribute is used by a Product
	 *
	 * @return 	int			Return -1 if KO, 0 if not used, 1 if used
	 */
	public function isUsed()
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT COUNT(*) AS nb FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val WHERE fk_prod_attr = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		$used = 0;
		if ($obj = $this->db->fetch_object($resql)) {
			$used = $obj->nb;
		}

		return $used ? 1 : 0;
	}

	/**
	 *  Save a new position (field position) for details lines.
	 *  You can choose to set position for lines with already a position or lines without any position defined.
	 *
	 * @param	boolean		$renum			   True to renum all already ordered lines, false to renum only not already ordered lines.
	 * @param	string		$rowidorder		   ASC or DESC
	 * @return	int                            Return integer <0 if KO, >0 if OK
	 */
	public function attributeOrder($renum = false, $rowidorder = 'ASC')
	{
		// Count number of attributes to reorder (according to choice $renum)
		$nl = 0;
		$sql = "SELECT count(rowid) FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity('product') . ")";
		if (!$renum) {
			$sql .= " AND position = 0";
		} else {
			$sql .= " AND position <> 0";
		}

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		} else {
			dol_print_error($this->db);
		}
		if ($nl > 0) {
			// The goal of this part is to reorder all attributes.
			$rows = array();

			// We first search all attributes
			$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " WHERE entity IN (" . getEntity('product') . ")";
			$sql .= " ORDER BY position ASC, rowid " . $rowidorder;

			dol_syslog(__METHOD__ . " search all attributes", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$i = 0;
				$num = $this->db->num_rows($resql);
				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					$rows[] = $row[0]; // Add attributes into array rows
					$i++;
				}

				// Now we set a new number for each attributes
				if (!empty($rows)) {
					foreach ($rows as $key => $row) {
						$this->updatePositionOfAttribute($row, ($key + 1));
					}
				}
			} else {
				dol_print_error($this->db);
			}
		}
		return 1;
	}

	/**
	 * 	Update position of line (rang)
	 *
	 * @param	int		$rowid		Id of line
	 * @param	int		$position	Position
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function updatePositionOfAttribute($rowid, $position)
	{
		global $hookmanager;

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET position = " . ((int) $position);
		$sql .= " WHERE rowid = " . ((int) $rowid);

		dol_syslog(__METHOD__, LOG_DEBUG);
		if (!$this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		} else {
			$parameters = array('rowid' => $rowid, 'position' => $position);
			$action = '';
			$reshook = $hookmanager->executeHooks('afterPositionOfAttributeUpdate', $parameters, $this, $action);
			return ($reshook >= 0 ? 1 : -1);
		}
	}

	/**
	 * 	Get position of attribute
	 *
	 * @param	int		$rowid		Id of line
	 * @return	int     			Value of position in table of attributes
	 */
	public function getPositionOfAttribute($rowid)
	{
		$sql = "SELECT position FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity('product') . ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}

		return 0;
	}

	/**
	 * 	Update a attribute to have a higher position
	 *
	 * @param	int		$rowid		Id of line
	 * @return	int					Return integer <0 KO, >0 OK
	 */
	public function attributeMoveUp($rowid)
	{
		$this->attributeOrder(false, 'ASC');

		// Get position of attribute
		$position = $this->getPositionOfAttribute($rowid);

		// Update position of attribute
		$this->updateAttributePositionUp($rowid, $position);

		return 1;
	}

	/**
	 * 	Update a attribute to have a lower position
	 *
	 * @param	int		$rowid		Id of line
	 * @return	int					Return integer <0 KO, >0 OK
	 */
	public function attributeMoveDown($rowid)
	{
		$this->attributeOrder(false, 'ASC');

		// Get position of line
		$position = $this->getPositionOfAttribute($rowid);

		// Get max value for position
		$max = $this->getMaxAttributesPosition();

		// Update position of attribute
		$this->updateAttributePositionDown($rowid, $position, $max);

		return 1;
	}

	/**
	 * 	Update position of attribute (up)
	 *
	 * @param	int		$rowid		Id of line
	 * @param	int		$position	Position
	 * @return	void
	 */
	public function updateAttributePositionUp($rowid, $position)
	{
		if ($position > 1) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET position = " . ((int) $position);
			$sql .= " WHERE entity IN (" . getEntity('product') . ")";
			$sql .= " AND position = " . ((int) ($position - 1));
			if ($this->db->query($sql)) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET position = " . ((int) ($position - 1));
				$sql .= " WHERE rowid = " . ((int) $rowid);
				if (!$this->db->query($sql)) {
					dol_print_error($this->db);
				}
			} else {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 * 	Update position of attribute (down)
	 *
	 * @param	int		$rowid		Id of line
	 * @param	int		$position	Position
	 * @param	int		$max		Max
	 * @return	void
	 */
	public function updateAttributePositionDown($rowid, $position, $max)
	{
		if ($position < $max) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET position = " . ((int) $position);
			$sql .= " WHERE entity IN (" . getEntity('product') . ")";
			$sql .= " AND position = " . ((int) ($position + 1));
			if ($this->db->query($sql)) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET position = " . ((int) ($position + 1));
				$sql .= " WHERE rowid = " . ((int) $rowid);
				if (!$this->db->query($sql)) {
					dol_print_error($this->db);
				}
			} else {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 * 	Get max value used for position of attributes
	 *
	 * @return     int  			Max value of position in table of attributes
	 */
	public function getMaxAttributesPosition()
	{
		// Search the last position of attributes
		$sql = "SELECT max(position) FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity('product') . ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}

		return 0;
	}

	/**
	 * 	Update position of attributes with ajax
	 *
	 * 	@param	array	$rows	Array of rows
	 * 	@return	void
	 */
	public function attributesAjaxOrder($rows)
	{
		$num = count($rows);
		for ($i = 0; $i < $num; $i++) {
			$this->updatePositionOfAttribute($rows[$i], ($i + 1));
		}
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("ProductAttribute") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if (!empty($this->label)) {
			$label .= '<br><b>' . $langs->trans('Label') . ':</b> ' . $this->label;
		}

		$url = dol_buildpath('/variants/card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowProductAttribute");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="' . $url . '"';
		}
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (!getDolGlobalString(strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('variantsdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Return a thumb for kanban views
	 *
	 *	@param	string	    			$option		Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	?array<string,string>	$arraydata	Array of data
	 *  @return	string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut(0, $mode);
	}

	/**
	 * Return label of status of product attribute
	 *
	 * @param      int			$mode        0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return     string		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(0, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return label of a status
	 *
	 * @param      int			$status		Id status
	 * @param      int			$mode      	0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return     string		Label
	 */
	public function LibStatut($status, $mode = 1)
	{
		// phpcs:enable
		return '';
	}

	// --------------------
	// TODO: All functions here must be redesigned and moved as they are not business functions but output functions
	// --------------------

	/* This is to show add lines */

	/**
	 *	Show add free and predefined products/services form
	 *
	 *  @param	int		        $dateSelector       1=Show also date range input fields
	 *  @param	Societe			$seller				Object thirdparty who sell
	 *  @param	?Societe		$buyer				Object thirdparty who buy
	 *  @param	string			$defaulttpldir		Directory where to find the template
	 *	@return	void
	 */
	public function formAddObjectLine($dateSelector, $seller, $buyer, $defaulttpldir = '/variants/tpl')
	{
		global $conf, $user, $langs, $object, $hookmanager;
		global $form;

		// Output template part (modules that overwrite templates must declare this into descriptor)
		// Use global variables + $dateSelector + $seller and $buyer
		// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook 'formAddObjectLine'.
		$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
		foreach ($dirtpls as $module => $reldir) {
			if (!empty($module)) {
				$tpl = dol_buildpath($reldir . '/productattributevalueline_create.tpl.php');
			} else {
				$tpl = DOL_DOCUMENT_ROOT . $reldir . '/productattributevalueline_create.tpl.php';
			}

			if (empty($conf->file->strict_mode)) {
				$res = @include $tpl;
			} else {
				$res = include $tpl; // for debug
			}
			if ($res) {
				break;
			}
		}
	}

	/* This is to show array of line of details */

	/**
	 *	Return HTML table for object lines
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *	If lines are into a template, title must also be into a template
	 *	But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 *	@param	string		$action				Action code
	 *	@param  Societe		$seller            	Object of seller third party
	 *	@param  ?Societe  	$buyer             	Object of buyer third party
	 *	@param	int			$selected		   	Object line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *  @param	string		$defaulttpldir		Directory where to find the template
	 *  @param	int			$addcreateline		1=Add create line
	 *	@return	void
	 */
	public function printObjectLines($action, $seller, $buyer, $selected = 0, $dateSelector = 0, $defaulttpldir = '/variants/tpl', $addcreateline = 0)
	{
		global $conf, $hookmanager, $langs, $user, $form, $object;
		global $mysoc;
		// TODO We should not use global var for this
		global $disableedit, $disablemove, $disableremove;

		$num = count($this->lines);

		$parameters = array('num' => $num, 'selected' => $selected, 'table_element_line' => $this->table_element_line);
		$reshook = $hookmanager->executeHooks('printObjectLineTitle', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir . '/productattributevalueline_title.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT . $reldir . '/productattributevalueline_title.tpl.php';
				}
				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}
		}


		if ($addcreateline) {
			// Form to add new line
			if ($action != 'selectlines') {
				if ($action != 'editline') {
					// Add products/services form

					$parameters = array();
					$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
					}
					if (empty($reshook)) {
						$object->formAddObjectLine(1, $mysoc, $buyer);
					}
				}
			}
		}

		$i = 0;

		print "<!-- begin printObjectLines() -->\n";
		foreach ($this->lines as $line) {
			if (is_object($hookmanager)) {   // Old code is commented on preceding line.
				$parameters = array('line' => $line, 'num' => $num, 'i' => $i, 'selected' => $selected, 'table_element_line' => $line->table_element);
				$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			}
			if (empty($reshook)) {
				$this->printObjectLine($action, $line, '', $num, $i, $dateSelector, $seller, $buyer, $selected, null, $defaulttpldir);
			}

			$i++;
		}
		print "<!-- end printObjectLines() -->\n";
	}

	/**
	 *	Return HTML content of a detail line
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *
	 *	@param	string      		$action				GET/POST action
	 *	@param  CommonObjectLine 	$line			    Selected object line to output
	 *	@param  ''		    		$var               	Is it a an odd line (not used)
	 *	@param  int		    		$num               	Number of line (0)
	 *	@param  int		    		$i					I
	 *	@param  int		    		$dateSelector      	1=Show also date range input fields
	 *	@param  Societe	    		$seller            	Object of seller third party
	 *	@param  Societe	    		$buyer             	Object of buyer third party
	 *	@param	int					$selected		   	Object line selected
	 *  @param  ?Extrafields		$extrafields		Object of extrafields
	 *  @param	string				$defaulttpldir		Directory where to find the template (deprecated)
	 *	@return	void
	 */
	public function printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafields = null, $defaulttpldir = '/variants/tpl')
	{
		global $conf, $langs, $user, $object, $hookmanager;
		global $form;
		global $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

		// Line in view mode
		if ($action != 'editline' || $selected != $line->id) {
			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir . '/productattributevalueline_view.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT . $reldir . '/productattributevalueline_view.tpl.php';
				}

				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}
		}

		// Line in update mode
		if ($action == 'editline' && $selected == $line->id) {
			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir . '/productattributevalueline_edit.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT . $reldir . '/productattributevalueline_edit.tpl.php';
				}

				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}
		}
	}

	/* This is to show array of line of details of source object */
}
