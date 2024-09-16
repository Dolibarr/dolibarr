<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <forian.henry@open-concept.pro>
 * Copyright (C) 2015-2023  Charlene BENKE          <charlene@patas-monkey.com>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022 		Antonin MARCHAL         <antonin@letempledujeu.fr>
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
 * 	\file 		htdocs/core/class/extrafields.class.php
 *	\ingroup    core
 *	\brief      File of class to manage extra fields
 */


/**
 *	Class to manage standard extra fields
 */
class ExtraFields
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var array<string,array{label:array<string,string>,type:array<string,string>,size:array<string,string>,default:array<string,string>,computed:array<string,string>,unique:array<string,int>,required:array<string,int>,param:array<string,mixed>,perms:array<string,mixed[]>,list:array<string,int|string>,pos:array<string,int>,totalizable:array<string,int>,help:array<string,string>,printable:array<string,int>,enabled:array<string,int>,langfile:array<string,string>,css:array<string,string>,csslist:array<string,string>,cssview:array<string,string>,hidden:array<string,int>,mandatoryfieldsofotherentities:array<string,string>,loaded?:int,count:int}> New array to store extrafields definition  Note: count set as present to avoid static analysis notices
	 */
	public $attributes = array();

	/**
	 * @var array<string,bool>	Array with boolean of status of groups
	 */
	public $expand_display;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Array of Error code (or message)
	 */
	public $errors = array();

	/**
	 * @var string 	DB Error number
	 */
	public $errno;

	/**
	 * @var array<string,string> 	Array of type to label
	 */
	public static $type2label = array(
		'varchar' => 'String1Line',
		'text' => 'TextLongNLines',
		'html' => 'HtmlText',
		'int' => 'Int',
		'double' => 'Float',
		'date' => 'Date',
		'datetime' => 'DateAndTime',
		//'datetimegmt'=>'DateAndTimeUTC',
		'boolean' => 'Boolean',
		'price' => 'ExtrafieldPrice',
		'pricecy' => 'ExtrafieldPriceWithCurrency',
		'phone' => 'ExtrafieldPhone',
		'mail' => 'ExtrafieldMail',
		'url' => 'ExtrafieldUrl',
		'ip' => 'ExtrafieldIP',
		'icon' => 'Icon',
		'password' => 'ExtrafieldPassword',
		'radio' => 'ExtrafieldRadio',
		'select' => 'ExtrafieldSelect',
		'sellist' => 'ExtrafieldSelectList',
		'checkbox' => 'ExtrafieldCheckBox',
		'chkbxlst' => 'ExtrafieldCheckBoxFromList',
		'link' => 'ExtrafieldLink',
		'point' => 'ExtrafieldPointGeo',
		'multipts' => 'ExtrafieldMultiPointGeo',
		'linestrg' => 'ExtrafieldLinestringGeo',
		'polygon' => 'ExtrafieldPolygonGeo',
		'separate' => 'ExtrafieldSeparator',
	);

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Add a new extra field parameter
	 *
	 *  @param	string			$attrname           Code of attribute
	 *  @param  string			$label              label of attribute
	 *  @param  string			$type               Type of attribute ('boolean','int','varchar','text','html','date','datetime','price', 'pricecy', 'phone','mail','password','url','select','checkbox','separate',...)
	 *  @param  int				$pos                Position of attribute
	 *  @param  string			$size               Size/length definition of attribute ('5', '24,8', ...). For float, it contains 2 numeric separated with a comma.
	 *  @param  string			$elementtype        Element type. Same value than object->table_element (Example 'member', 'product', 'thirdparty', ...)
	 *  @param	int				$unique				Is field unique or not
	 *  @param	int				$required			Is field required or not
	 *  @param	string			$default_value		Defaulted value (In database. use the default_value feature for default value on screen. Example: '', '0', 'null', 'avalue')
	 *  @param  array|string	$param				Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int				$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param	string			$perms				Permission to check
	 *  @param	string			$list				Visibility ('0'=never visible, '1'=visible on list+forms, '2'=list only, '3'=form only or 'eval string')
	 *  @param	string			$help				Text with help tooltip
	 *  @param  string  		$computed           Computed value
	 *  @param  string  		$entity    		 	Entity of extrafields (for multicompany modules)
	 *  @param  string  		$langfile  		 	Language file
	 *  @param  string  		$enabled  		 	Condition to have the field enabled or not
	 *  @param	int				$totalizable		Is a measure. Must show a total on lists
	 *  @param  int             $printable          Is extrafield displayed on PDF
	 *  @param	array			$moreparams			More parameters. Example: array('css'=>, 'csslist'=>Css on list, 'cssview'=>...)
	 *  @return int      							Return integer <=0 if KO, >0 if OK
	 */
	public function addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique = 0, $required = 0, $default_value = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array())
	{
		if (empty($attrname)) {
			return -1;
		}
		if (empty($label)) {
			return -1;
		}

		$result = 0;

		if ($type == 'separator' || $type == 'separate') {
			$type = 'separate';
			$unique = 0;
			$required = 0;
		}	// Force unique and not required if this is a separator field to avoid troubles.
		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		// Create field into database except for separator type which is not stored in database
		if ($type != 'separate') {
			$result = $this->create($attrname, $type, $size, $elementtype, $unique, $required, $default_value, $param, $perms, $list, $computed, $help, $moreparams);
		}
		$err1 = $this->errno;
		if ($result > 0 || $err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' || $type == 'separate') {
			// Add declaration of field into table
			$result2 = $this->create_label($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $param, $alwayseditable, $perms, $list, $help, $default_value, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams);
			$err2 = $this->errno;
			if ($result2 > 0 || ($err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' && $err2 == 'DB_ERROR_RECORD_ALREADY_EXISTS')) {
				$this->error = '';
				$this->errno = '0';
				return 1;
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	}

	/**
	 *  Update an existing extra field parameter
	 *
	 *  @param  string			$attrname           Code of attribute
	 *  @param  string			$label              label of attribute
	 *  @param  string			$type               Type of attribute ('boolean','int','varchar','text','html','date','datetime','price', 'pricecy', 'phone','mail','password','url','select','checkbox','separate',...)
	 *  @param  int				$pos                Position of attribute
	 *  @param  string			$size               Size/length definition of attribute ('5', '24,8', ...). For float, it contains 2 numeric separated with a comma.
	 *  @param  string			$elementtype        Element type. Same value than object->table_element (Example 'member', 'product', 'thirdparty', ...)
	 *  @param  int				$unique				Is field unique or not
	 *  @param  int				$required			Is field required or not
	 *  @param  string			$default_value		Defaulted value (In database. use the default_value feature for default value on screen. Example: '', '0', 'null', 'avalue')
	 *  @param  array|string	$param				Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int				$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param  string			$perms				Permission to check
	 *  @param  string			$list				Visibility ('0'=never visible, '1'=visible on list+forms, '2'=list only, '3'=form only or 'eval string')
	 *  @param  string			$help				Text with help tooltip
	 *  @param  string  		$computed           Computed value
	 *  @param  string  		$entity    		 	Entity of extrafields (for multicompany modules)
	 *  @param  string  		$langfile  		 	Language file
	 *  @param  string  		$enabled  		 	Condition to have the field enabled or not
	 *  @param  int				$totalizable		Is a measure. Must show a total on lists
	 *  @param  int             $printable          Is extrafield displayed on PDF
	 *  @param  array			$moreparams			More parameters. Example: array('css'=>, 'csslist'=>Css on list, 'cssview'=>...)
	 *  @return int      							Return integer <=0 if KO, >0 if OK
	 */
	public function updateExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique = 0, $required = 0, $default_value = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array())
	{
		if (empty($attrname)) {
			return -1;
		}
		if (empty($label)) {
			return -1;
		}

		$result = 0;

		if ($type == 'separator' || $type == 'separate') {
			$type = 'separate';
			$unique = 0;
			$required = 0;
		}	// Force unique and not required if this is a separator field to avoid troubles.
		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		// Create field into database except for separator type which is not stored in database
		if ($type != 'separate') {
			dol_syslog(get_class($this).'::thisupdate', LOG_DEBUG);
			$result = $this->update($attrname, $label, $type, $size, $elementtype, $unique, $required, $pos, $param, $alwayseditable, $perms, $list, $help, $default_value, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams);
		}
		$err1 = $this->errno;
		if ($result > 0 || $err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' || $type == 'separate') {
			// Add declaration of field into table
			dol_syslog(get_class($this).'::thislabel', LOG_DEBUG);
			$result2 = $this->update_label($attrname, $label, $type, $size, $elementtype, $unique, $required, $pos, $param, $alwayseditable, $perms, $list, $help, $default_value, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams);
			$err2 = $this->errno;
			if ($result2 > 0 || ($err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' && $err2 == 'DB_ERROR_RECORD_ALREADY_EXISTS')) {
				$this->error = '';
				$this->errno = '0';
				return 1;
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	}

	/**
	 *	Add a new optional attribute.
	 *  This is a private method. For public method, use addExtraField.
	 *
	 *	@param	string	$attrname			code of attribute
	 *  @param	string	$type				Type of attribute ('boolean', 'int', 'varchar', 'text', 'html', 'date', 'datetime', 'price', 'pricecy', 'phone', 'mail', 'password', 'url', 'select', 'checkbox', ...)
	 *  @param	string	$length				Size/length of attribute ('5', '24,8', ...)
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param  string  $default_value		Default value for field (in database)
	 *  @param  array	$param				Params for field  (ex for select list : array('options'=>array('value'=>'label of option'))
	 *  @param	string	$perms				Permission
	 *	@param	string	$list				Into list view by default
	 *  @param  string  $computed           Computed value
	 *  @param	string	$help				Help on tooltip
	 *  @param	array	$moreparams			More parameters. Example: array('css'=>, 'csslist'=>, 'cssview'=>...)
	 *  @return int      	           		Return integer <=0 if KO, >0 if OK
	 */
	private function create($attrname, $type = 'varchar', $length = '255', $elementtype = '', $unique = 0, $required = 0, $default_value = '', $param = array(), $perms = '', $list = '0', $computed = '', $help = '', $moreparams = array())
	{
		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$table = $elementtype.'_extrafields';
		if ($elementtype == 'categorie') {
			$table = 'categories_extrafields';
		}

		if (!empty($attrname) && preg_match("/^\w[a-zA-Z0-9_]*$/", $attrname) && !is_numeric($attrname)) {
			if ($type == 'boolean') {
				$typedb = 'int';
				$lengthdb = '1';
			} elseif ($type == 'price') {
				$typedb = 'double';
				$lengthdb = '24,8';
			} elseif ($type == 'pricecy') {
				$typedb = 'varchar';
				$lengthdb = '64';
			} elseif ($type == 'phone') {
				$typedb = 'varchar';
				$lengthdb = '20';
			} elseif ($type == 'mail' || $type == 'ip' || $type == 'icon') {
				$typedb = 'varchar';
				$lengthdb = '128';
			} elseif ($type == 'url') {
				$typedb = 'varchar';
				$lengthdb = '255';
			} elseif (($type == 'select') || ($type == 'sellist') || ($type == 'radio') || ($type == 'checkbox') || ($type == 'chkbxlst')) {
				$typedb = 'varchar';
				$lengthdb = '255';
			} elseif ($type == 'link') {
				$typedb = 'int';
				$lengthdb = '11';
			} elseif ($type == 'point') {
				$typedb = 'point';
				$lengthdb = '';
			} elseif ($type == 'multipts') {
				$typedb = 'multipoint';
				$lengthdb = '';
			} elseif ($type == 'linestrg') {
				$typedb = 'linestring';
				$lengthdb = '';
			} elseif ($type == 'polygon') {
				$typedb = 'polygon';
				$lengthdb = '';
			} elseif ($type == 'html') {
				$typedb = 'text';
				$lengthdb = $length;
			} elseif ($type == 'password') {
				$typedb = 'varchar';
				$lengthdb = '128';
			} else {
				$typedb = $type;
				$lengthdb = $length;
				if ($type == 'varchar' && empty($lengthdb)) {
					$lengthdb = '255';
				}
			}
			$field_desc = array(
				'type' => $typedb,
				'value' => $lengthdb,
				'null' => ($required ? 'NOT NULL' : 'NULL'),
				'default' => $default_value
			);

			$result = $this->db->DDLAddField($this->db->prefix().$table, $attrname, $field_desc);
			if ($result > 0) {
				if ($unique) {
					$sql = "ALTER TABLE ".$this->db->prefix().$table." ADD UNIQUE INDEX uk_".$table."_".$attrname." (".$attrname.")";
					$resql = $this->db->query($sql, 1, 'dml');
				}
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				$this->errno = $this->db->lasterrno();
				return -1;
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add description of a new optional attribute
	 *
	 *	@param	string			$attrname		code of attribute
	 *	@param	string			$label			label of attribute
	 *  @param	string			$type			Type of attribute ('int', 'varchar', 'text', 'html', 'date', 'datehour', 'float')
	 *  @param	int				$pos			Position of attribute
	 *  @param	string			$size			Size/length of attribute ('5', '24,8', ...)
	 *  @param  string			$elementtype	Element type ('member', 'product', 'thirdparty', ...)
	 *  @param	int				$unique			Is field unique or not
	 *  @param	int				$required		Is field required or not
	 *  @param  array|string	$param			Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int				$alwayseditable	Is attribute always editable regardless of the document status
	 *  @param	string			$perms			Permission to check
	 *  @param	string			$list			Visibility
	 *  @param	string			$help			Help on tooltip
	 *  @param  string          $default        Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string          $computed       Computed value
	 *  @param  string          $entity     	Entity of extrafields
	 *  @param	string			$langfile		Language file
	 *  @param  string  		$enabled  		Condition to have the field enabled or not
	 *  @param	int				$totalizable	Is a measure. Must show a total on lists
	 *  @param  int             $printable      Is extrafield displayed on PDF
	 *  @param	array			$moreparams		More parameters. Example: array('css'=>, 'csslist'=>, 'cssview'=>...)
	 *  @return	int								Return integer <=0 if KO, >0 if OK
	 *  @throws Exception
	 */
	private function create_label($attrname, $label = '', $type = '', $pos = 0, $size = '', $elementtype = '', $unique = 0, $required = 0, $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $default = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array())
	{
		// phpcs:enable
		global $conf, $user;

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		// Clean parameters
		if (empty($pos)) {
			$pos = 0;
		}
		if (empty($list)) {
			$list = '0';
		}
		if (empty($required)) {
			$required = 0;
		}
		if (empty($unique)) {
			$unique = 0;
		}
		if (empty($printable)) {
			$printable = 0;
		}
		if (empty($alwayseditable)) {
			$alwayseditable = 0;
		}
		if (empty($totalizable)) {
			$totalizable = 0;
		}

		$css = '';
		if (!empty($moreparams) && !empty($moreparams['css'])) {
			$css = $moreparams['css'];
		}
		$csslist = '';
		if (!empty($moreparams) && !empty($moreparams['csslist'])) {
			$csslist = $moreparams['csslist'];
		}
		$cssview = '';
		if (!empty($moreparams) && !empty($moreparams['cssview'])) {
			$cssview = $moreparams['cssview'];
		}

		if (!empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $attrname) && !is_numeric($attrname)) {
			if (is_array($param) && count($param) > 0) {
				$params = serialize($param);
			} elseif (strlen($param) > 0) {
				$params = trim($param);
			} else {
				$params = '';
			}

			$sql = "INSERT INTO ".$this->db->prefix()."extrafields(";
			$sql .= " name,";
			$sql .= " label,";
			$sql .= " type,";
			$sql .= " pos,";
			$sql .= " size,";
			$sql .= " entity,";
			$sql .= " elementtype,";
			$sql .= " fieldunique,";
			$sql .= " fieldrequired,";
			$sql .= " param,";
			$sql .= " alwayseditable,";
			$sql .= " perms,";
			$sql .= " langs,";
			$sql .= " list,";
			$sql .= " printable,";
			$sql .= " fielddefault,";
			$sql .= " fieldcomputed,";
			$sql .= " fk_user_author,";
			$sql .= " fk_user_modif,";
			$sql .= " datec,";
			$sql .= " enabled,";
			$sql .= " help,";
			$sql .= " totalizable,";
			$sql .= " css,";
			$sql .= " csslist,";
			$sql .= " cssview";
			$sql .= " )";
			$sql .= " VALUES('".$this->db->escape($attrname)."',";
			$sql .= " '".$this->db->escape($label)."',";
			$sql .= " '".$this->db->escape($type)."',";
			$sql .= " ".((int) $pos).",";
			$sql .= " '".$this->db->escape($size)."',";
			$sql .= " ".($entity === '' ? $conf->entity : $entity).",";
			$sql .= " '".$this->db->escape($elementtype)."',";
			$sql .= " ".((int) $unique).",";
			$sql .= " ".((int) $required).",";
			$sql .= " '".$this->db->escape($params)."',";
			$sql .= " ".((int) $alwayseditable).",";
			$sql .= " ".($perms ? "'".$this->db->escape($perms)."'" : "null").",";
			$sql .= " ".($langfile ? "'".$this->db->escape($langfile)."'" : "null").",";
			$sql .= " '".$this->db->escape($list)."',";
			$sql .= " '".$this->db->escape($printable)."',";
			$sql .= " ".($default ? "'".$this->db->escape($default)."'" : "null").",";
			$sql .= " ".($computed ? "'".$this->db->escape($computed)."'" : "null").",";
			$sql .= " ".(is_object($user) ? $user->id : 0).",";
			$sql .= " ".(is_object($user) ? $user->id : 0).",";
			$sql .= "'".$this->db->idate(dol_now())."',";
			$sql .= " ".($enabled ? "'".$this->db->escape($enabled)."'" : "1").",";
			$sql .= " ".($help ? "'".$this->db->escape($help)."'" : "null").",";
			$sql .= " ".($totalizable ? 'TRUE' : 'FALSE').",";
			$sql .= " ".($css ? "'".$this->db->escape($css)."'" : "null").",";
			$sql .= " ".($csslist ? "'".$this->db->escape($csslist)."'" : "null").",";
			$sql .= " ".($cssview ? "'".$this->db->escape($cssview)."'" : "null");
			$sql .= ')';

			if ($this->db->query($sql)) {
				dol_syslog(get_class($this)."::create_label_success", LOG_DEBUG);
				return 1;
			} else {
				dol_syslog(get_class($this)."::create_label_error", LOG_DEBUG);
				$this->error = $this->db->lasterror();
				$this->errno = $this->db->lasterrno();
				return -1;
			}
		}
		return -1;
	}

	/**
	 *	Delete an optional attribute
	 *
	 *	@param	string	$attrname		Code of attribute to delete
	 *  @param  string	$elementtype    Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @return int              		Return integer < 0 if KO, 0 if nothing is done, 1 if OK
	 */
	public function delete($attrname, $elementtype = '')
	{
		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$table = $elementtype.'_extrafields';
		if ($elementtype == 'categorie') {
			$table = 'categories_extrafields';
		}

		$error = 0;

		if (!empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $attrname)) {
			$result = $this->delete_label($attrname, $elementtype);
			if ($result < 0) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				$sql = "SELECT COUNT(rowid) as nb";
				$sql .= " FROM ".$this->db->prefix()."extrafields";
				$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'";
				$sql .= " AND name = '".$this->db->escape($attrname)."'";
				//$sql.= " AND entity IN (0,".$conf->entity.")";      Do not test on entity here. We want to see if there is still on field remaining in other entities before deleting field in table
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					if ($obj->nb <= 0) {
						$result = $this->db->DDLDropField($this->db->prefix().$table, $attrname); // This also drop the unique key
						if ($result < 0) {
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->db->lasterror();
							$error++;
						}
					}
				} else {
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->db->lasterror();
					$error++;
				}
			}
			if (empty($error)) {
				return $result;
			} else {
				return $error*-1;
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Delete description of an optional attribute
	 *
	 *	@param	string	$attrname			Code of attribute to delete
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', ...)
	 *  @return int              			Return integer < 0 if KO, 0 if nothing is done, 1 if OK
	 */
	private function delete_label($attrname, $elementtype = '')
	{
		// phpcs:enable
		global $conf;

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/", $attrname)) {
			$sql = "DELETE FROM ".$this->db->prefix()."extrafields";
			$sql .= " WHERE name = '".$this->db->escape($attrname)."'";
			$sql .= " AND entity IN  (0,".$conf->entity.')';
			$sql .= " AND elementtype = '".$this->db->escape($elementtype)."'";

			dol_syslog(get_class($this)."::delete_label", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				return 1;
			} else {
				dol_print_error($this->db);
				return -1;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 	Modify type of a personalized attribute
	 *
	 *  @param	string	$attrname			Name of attribute
	 *  @param	string	$label				Label of attribute
	 *  @param	string	$type				Type of attribute ('boolean', 'int', 'varchar', 'text', 'html', 'date', 'datetime','price','phone','mail','password','url','select','checkbox', ...)
	 *  @param	int		$length				Length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	int		$pos				Position of attribute
	 *  @param  array	$param				Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int		$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param	string	$perms				Permission to check
	 *  @param	string	$list				Visibility
	 *  @param	string	$help				Help on tooltip
	 *  @param  string  $default            Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string  $computed           Computed value
	 *  @param  string  $entity	            Entity of extrafields
	 *  @param	string	$langfile			Language file
	 *  @param  string  $enabled  			Condition to have the field enabled or not
	 *  @param  int     $totalizable        Is extrafield totalizable on list
	 *  @param  int     $printable        	Is extrafield displayed on PDF
	 *  @param	array	$moreparams			More parameters. Example: array('css'=>, 'csslist'=>, 'cssview'=>...)
	 * 	@return	int							>0 if OK, <=0 if KO
	 *  @throws Exception
	 */
	public function update($attrname, $label, $type, $length, $elementtype, $unique = 0, $required = 0, $pos = 0, $param = array(), $alwayseditable = 0, $perms = '', $list = '', $help = '', $default = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array())
	{
		global $action, $hookmanager;

		$result = 0;

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$table = $elementtype.'_extrafields';
		if ($elementtype == 'categorie') {
			$table = 'categories_extrafields';
		}

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/", $attrname)) {
			if ($type == 'boolean') {
				$typedb = 'int';
				$lengthdb = '1';
			} elseif ($type == 'price') {
				$typedb = 'double';
				$lengthdb = '24,8';
			} elseif ($type == 'pricecy') {
				$typedb = 'varchar';
				$lengthdb = '64';
			} elseif ($type == 'phone') {
				$typedb = 'varchar';
				$lengthdb = '20';
			} elseif ($type == 'mail' || $type == 'ip' || $type == 'icon') {
				$typedb = 'varchar';
				$lengthdb = '128';
			} elseif ($type == 'url') {
				$typedb = 'varchar';
				$lengthdb = '255';
			} elseif (($type == 'select') || ($type == 'sellist') || ($type == 'radio') || ($type == 'checkbox') || ($type == 'chkbxlst')) {
				$typedb = 'varchar';
				$lengthdb = '255';
			} elseif ($type == 'html') {
				$typedb = 'text';
				$lengthdb = $length;
			} elseif ($type == 'link') {
				$typedb = 'int';
				$lengthdb = '11';
			} elseif ($type == 'point') {
				$typedb = 'point';
				$lengthdb = '';
			} elseif ($type == 'multipts') {
				$typedb = 'multipoint';
				$lengthdb = '';
			} elseif ($type == 'linestrg') {
				$typedb = 'linestring';
				$lengthdb = '';
			} elseif ($type == 'polygon') {
				$typedb = 'polygon';
				$lengthdb = '';
			} elseif ($type == 'password') {
				$typedb = 'varchar';
				$lengthdb = '128';
			} else {
				$typedb = $type;
				$lengthdb = $length;
			}
			$field_desc = array('type' => $typedb, 'value' => $lengthdb, 'null' => ($required ? 'NOT NULL' : 'NULL'), 'default' => $default);

			if (is_object($hookmanager)) {
				$hookmanager->initHooks(array('extrafieldsdao'));
				$parameters = array('field_desc' => &$field_desc, 'table' => $table, 'attr_name' => $attrname, 'label' => $label, 'type' => $type, 'length' => $length, 'unique' => $unique, 'required' => $required, 'pos' => $pos, 'param' => $param, 'alwayseditable' => $alwayseditable, 'perms' => $perms, 'list' => $list, 'help' => $help, 'default' => $default, 'computed' => $computed, 'entity' => $entity, 'langfile' => $langfile, 'enabled' => $enabled, 'totalizable' => $totalizable, 'printable' => $printable);
				$reshook = $hookmanager->executeHooks('updateExtrafields', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				if ($reshook < 0) {
					$this->error = $this->db->lasterror();
					return -1;
				}
			}

			dol_syslog(get_class($this).'::DDLUpdateField', LOG_DEBUG);
			if ($type != 'separate') { // No table update when separate type
				$result = $this->db->DDLUpdateField($this->db->prefix().$table, $attrname, $field_desc);
			}
			if ($result > 0 || $type == 'separate') {
				if ($label) {
					dol_syslog(get_class($this).'::update_label', LOG_DEBUG);
					$result = $this->update_label($attrname, $label, $type, $length, $elementtype, $unique, $required, $pos, $param, $alwayseditable, $perms, $list, $help, $default, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams);
				}
				if ($result > 0) {
					$sql = '';
					if ($unique) {
						dol_syslog(get_class($this).'::update_unique', LOG_DEBUG);
						$sql = "ALTER TABLE ".$this->db->prefix().$table." ADD UNIQUE INDEX uk_".$table."_".$this->db->sanitize($attrname)." (".$this->db->sanitize($attrname).")";
					} else {
						dol_syslog(get_class($this).'::update_common', LOG_DEBUG);
						$sql = "ALTER TABLE ".$this->db->prefix().$table." DROP INDEX IF EXISTS uk_".$table."_".$this->db->sanitize($attrname);
					}
					dol_syslog(get_class($this).'::update', LOG_DEBUG);
					$resql = $this->db->query($sql, 1, 'dml');
					/*if ($resql < 0) {
					 $this->error = $this->db->lasterror();
					 return -1;
					 }*/
					return 1;
				} else {
					$this->error = $this->db->lasterror();
					return -1;
				}
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Modify description of personalized attribute
	 * 	This is a private method. For public method, use updateExtraField.
	 *
	 *  @param	string	$attrname			Name of attribute
	 *  @param	string	$label				Label of attribute
	 *  @param  string	$type               Type of attribute
	 *  @param  int		$size		        Length of attribute
	 *  @param  string	$elementtype		Element type ('member', 'product', 'thirdparty', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	int		$pos				Position of attribute
	 *  @param  array	$param				Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int		$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param	string	$perms				Permission to check
	 *  @param	string	$list				Visibility
	 *  @param	string	$help				Help on tooltip.
	 *  @param  string  $default            Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string  $computed           Computed value
	 *  @param  string  $entity     		Entity of extrafields
	 *  @param	string	$langfile			Language file
	 *  @param  string  $enabled  			Condition to have the field enabled or not
	 *  @param  int     $totalizable        Is extrafield totalizable on list
	 *  @param  int     $printable        	Is extrafield displayed on PDF
	 *  @param	array	$moreparams			More parameters. Example: array('css'=>, 'csslist'=>, 'cssview'=>...)
	 *  @return	int							Return integer <=0 if KO, >0 if OK
	 *  @throws Exception
	 */
	private function update_label($attrname, $label, $type, $size, $elementtype, $unique = 0, $required = 0, $pos = 0, $param = array(), $alwayseditable = 0, $perms = '', $list = '0', $help = '', $default = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array())
	{
		// phpcs:enable
		global $conf, $user;
		dol_syslog(get_class($this)."::update_label ".$attrname.", ".$label.", ".$type.", ".$size.", ".$elementtype.", ".$unique.", ".$required.", ".$pos.", ".$alwayseditable.", ".$perms.", ".$list.", ".$default.", ".$computed.", ".$entity.", ".$langfile.", ".$enabled.", ".$totalizable.", ".$printable);

		// Clean parameters
		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		if (empty($pos)) {
			$pos = 0;
		}
		if (empty($list)) {
			$list = '0';
		}
		if (empty($totalizable)) {
			$totalizable = 0;
		}
		if (empty($required)) {
			$required = 0;
		}
		if (empty($unique)) {
			$unique = 0;
		}
		if (empty($alwayseditable)) {
			$alwayseditable = 0;
		}

		$css = '';
		if (!empty($moreparams) && !empty($moreparams['css'])) {
			$css = $moreparams['css'];
		}
		$csslist = '';
		if (!empty($moreparams) && !empty($moreparams['csslist'])) {
			$csslist = $moreparams['csslist'];
		}
		$cssview = '';
		if (!empty($moreparams) && !empty($moreparams['cssview'])) {
			$cssview = $moreparams['cssview'];
		}

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/", $attrname)) {
			$this->db->begin();

			if (is_array($param) && count($param) > 0) {
				$params = serialize($param);
			} elseif (is_array($param)) {
				$params = '';
			} elseif (strlen($param) > 0) {
				$params = trim($param);
			} else {
				$params = '';
			}

			if ($entity === '' || $entity != '0') {
				// We don't want on all entities, we delete all and current
				$sql_del = "DELETE FROM ".$this->db->prefix()."extrafields";
				$sql_del .= " WHERE name = '".$this->db->escape($attrname)."'";
				$sql_del .= " AND entity IN (0, ".($entity === '' ? $conf->entity : $entity).")";
				$sql_del .= " AND elementtype = '".$this->db->escape($elementtype)."'";
			} else {
				// We want on all entities ($entities = '0'), we delete on all only (we keep setup specific to each entity)
				$sql_del = "DELETE FROM ".$this->db->prefix()."extrafields";
				$sql_del .= " WHERE name = '".$this->db->escape($attrname)."'";
				$sql_del .= " AND entity = 0";
				$sql_del .= " AND elementtype = '".$this->db->escape($elementtype)."'";
			}
			$resql1 = $this->db->query($sql_del);

			$sql = "INSERT INTO ".$this->db->prefix()."extrafields(";
			$sql .= " name,"; // This is code
			$sql .= " entity,";
			$sql .= " label,";
			$sql .= " type,";
			$sql .= " size,";
			$sql .= " elementtype,";
			$sql .= " fieldunique,";
			$sql .= " fieldrequired,";
			$sql .= " perms,";
			$sql .= " langs,";
			$sql .= " pos,";
			$sql .= " alwayseditable,";
			$sql .= " param,";
			$sql .= " list,";
			$sql .= " printable,";
			$sql .= " totalizable,";
			$sql .= " fielddefault,";
			$sql .= " fieldcomputed,";
			$sql .= " fk_user_author,";
			$sql .= " fk_user_modif,";
			$sql .= " datec,";
			$sql .= " enabled,";
			$sql .= " help,";
			$sql .= " css,";
			$sql .= " csslist,";
			$sql .= " cssview";
			$sql .= ") VALUES (";
			$sql .= "'".$this->db->escape($attrname)."',";
			$sql .= " ".($entity === '' ? $conf->entity : $entity).",";
			$sql .= " '".$this->db->escape($label)."',";
			$sql .= " '".$this->db->escape($type)."',";
			$sql .= " '".$this->db->escape($size)."',";
			$sql .= " '".$this->db->escape($elementtype)."',";
			$sql .= " ".$unique.",";
			$sql .= " ".$required.",";
			$sql .= " ".($perms ? "'".$this->db->escape($perms)."'" : "null").",";
			$sql .= " ".($langfile ? "'".$this->db->escape($langfile)."'" : "null").",";
			$sql .= " ".$pos.",";
			$sql .= " '".$this->db->escape($alwayseditable)."',";
			$sql .= " '".$this->db->escape($params)."',";
			$sql .= " '".$this->db->escape($list)."',";
			$sql .= " ".((int) $printable).",";
			$sql .= " ".($totalizable ? 'TRUE' : 'FALSE').",";
			$sql .= " ".(($default != '') ? "'".$this->db->escape($default)."'" : "null").",";
			$sql .= " ".($computed ? "'".$this->db->escape($computed)."'" : "null").",";
			$sql .= " ".$user->id.",";
			$sql .= " ".$user->id.",";
			$sql .= "'".$this->db->idate(dol_now())."',";
			$sql .= "'".$this->db->escape($enabled)."',";
			$sql .= " ".($help ? "'".$this->db->escape($help)."'" : "null").",";
			$sql .= " ".($css ? "'".$this->db->escape($css)."'" : "null").",";
			$sql .= " ".($csslist ? "'".$this->db->escape($csslist)."'" : "null").",";
			$sql .= " ".($cssview ? "'".$this->db->escape($cssview)."'" : "null");
			$sql .= ")";

			$resql2 = $this->db->query($sql);

			if ($resql1 && $resql2) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				dol_print_error($this->db);
				return -1;
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Load the array of extrafields definition $this->attributes
	 *
	 * 	@param	string		$elementtype		Type of element ('all' = all or $object->table_element like 'adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...).
	 * 	@param	boolean		$forceload			Force load of extra fields whatever is status of cache.
	 *  @param  string		$attrname           The name of the attribute.
	 *  @return array{}|array{label:array<string,string>,type:array<string,string>,size:array<string,string>,default:array<string,string>,computed:array<string,string>,unique:array<string,int>,required:array<string,int>,param:array<string,mixed>,perms:array<string,mixed[]>,list:array<string,int>|array<string,string>,pos:array<string,int>,totalizable:array<string,int>,help:array<string,string>,printable:array<string,int>,enabled:array<string,int>,langfile:array<string,string>,css:array<string,string>,csslist:array<string,string>,hidden:array<string,int>,mandatoryfieldsofotherentities?:array<string,string>,loaded?:int,count:int}		Array of attributes keys+label for all extra fields.  Note: count set as present to avoid static analysis notices
	 */
	public function fetch_name_optionals_label($elementtype, $forceload = false, $attrname = '')
	{
		// phpcs:enable
		global $conf;

		if (empty($elementtype)) {
			return array();
		}

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}
		if ($elementtype == 'order_supplier') {
			$elementtype = 'commande_fournisseur';
		}

		// Test cache $this->attributes[$elementtype]['loaded'] to see if we must do something
		// TODO

		$array_name_label = array();

		// We should not have several time this request. If we have, there is some optimization to do by calling a simple $extrafields->fetch_optionals() in top of code and not into subcode
		$sql = "SELECT rowid, name, label, type, size, elementtype, fieldunique, fieldrequired, param, pos, alwayseditable, perms, langs, list, printable, totalizable, fielddefault, fieldcomputed, entity, enabled, help,";
		$sql .= " css, cssview, csslist";
		$sql .= " FROM ".$this->db->prefix()."extrafields";
		//$sql.= " WHERE entity IN (0,".$conf->entity.")";    // Filter is done later
		if ($elementtype && $elementtype != 'all') {
			$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'"; // Filed with object->table_element
		}
		if ($attrname && $elementtype && $elementtype != 'all') {
			$sql .= " AND name = '".$this->db->escape($attrname)."'";
		}
		$sql .= " ORDER BY pos";

		$resql = $this->db->query($sql);
		if ($resql) {
			$count = 0;
			if ($this->db->num_rows($resql)) {
				while ($tab = $this->db->fetch_object($resql)) {
					if ($tab->entity != 0 && $tab->entity != $conf->entity) {
						// This field is not in current entity. We discard but before we save it into the array of mandatory fields if it is a mandatory field without default value
						if ($tab->fieldrequired && is_null($tab->fielddefault)) {
							$this->attributes[$tab->elementtype]['mandatoryfieldsofotherentities'][$tab->name] = $tab->type;
						}
						continue;
					}

					// We can add this attribute to object. TODO Remove this and return $this->attributes[$elementtype]['label']
					if ($tab->type != 'separate') {
						$array_name_label[$tab->name] = $tab->label;
					}


					$this->attributes[$tab->elementtype]['type'][$tab->name] = $tab->type;
					$this->attributes[$tab->elementtype]['label'][$tab->name] = $tab->label;
					$this->attributes[$tab->elementtype]['size'][$tab->name] = $tab->size;
					$this->attributes[$tab->elementtype]['elementtype'][$tab->name] = $tab->elementtype;
					$this->attributes[$tab->elementtype]['default'][$tab->name] = $tab->fielddefault;
					$this->attributes[$tab->elementtype]['computed'][$tab->name] = $tab->fieldcomputed;
					$this->attributes[$tab->elementtype]['unique'][$tab->name] = $tab->fieldunique;
					$this->attributes[$tab->elementtype]['required'][$tab->name] = $tab->fieldrequired;
					$this->attributes[$tab->elementtype]['param'][$tab->name] = ($tab->param ? jsonOrUnserialize($tab->param) : '');
					$this->attributes[$tab->elementtype]['pos'][$tab->name] = $tab->pos;
					$this->attributes[$tab->elementtype]['alwayseditable'][$tab->name] = $tab->alwayseditable;
					$this->attributes[$tab->elementtype]['perms'][$tab->name] = ((is_null($tab->perms) || strlen($tab->perms) == 0) ? 1 : $tab->perms);
					$this->attributes[$tab->elementtype]['langfile'][$tab->name] = $tab->langs;
					$this->attributes[$tab->elementtype]['list'][$tab->name] = $tab->list;
					$this->attributes[$tab->elementtype]['printable'][$tab->name] = $tab->printable;
					$this->attributes[$tab->elementtype]['totalizable'][$tab->name] = ($tab->totalizable ? 1 : 0);
					$this->attributes[$tab->elementtype]['entityid'][$tab->name] = $tab->entity;
					$this->attributes[$tab->elementtype]['enabled'][$tab->name] = $tab->enabled;
					$this->attributes[$tab->elementtype]['help'][$tab->name] = $tab->help;
					$this->attributes[$tab->elementtype]['css'][$tab->name] = $tab->css;
					$this->attributes[$tab->elementtype]['cssview'][$tab->name] = $tab->cssview;
					$this->attributes[$tab->elementtype]['csslist'][$tab->name] = $tab->csslist;

					$this->attributes[$tab->elementtype]['loaded'] = 1;
					$count++;
				}
			}
			if ($elementtype) {
				$this->attributes[$elementtype]['loaded'] = 1; // Note: If nothing is found, we also set the key 'loaded' to 1.
				$this->attributes[$elementtype]['count'] = $count;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_name_optionals_label ".$this->error, LOG_ERR);
		}

		return $array_name_label;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of common object
	 *
	 * @param  string        $key            		Key of attribute
	 * @param  string|array  $value 			    Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value); for dates in filter mode, a range array('start'=><timestamp>, 'end'=><timestamp>) should be provided
	 * @param  string        $moreparam      		To add more parameters on html input tag
	 * @param  string        $keysuffix      		Suffix string to add after name and id of field (can be used to avoid duplicate names)
	 * @param  string        $keyprefix      		Prefix string to add before name and id of field (can be used to avoid duplicate names)
	 * @param  string        $morecss        		More css (to defined size of field. Old behaviour: may also be a numeric)
	 * @param  int           $objectid       		Current object id
	 * @param  string        $extrafieldsobjectkey	The key to use to store retrieved data (commonly $object->table_element)
	 * @param  int	         $mode                  1=Used for search filters
	 * @return string
	 */
	public function showInputField($key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '', $objectid = 0, $extrafieldsobjectkey = '', $mode = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		$out = '';

		if (!preg_match('/options_$/', $keyprefix)) {	// Because we work on extrafields, we add 'options_' to prefix if not already added
			$keyprefix .= 'options_';
		}

		if (empty($extrafieldsobjectkey)) {
			dol_syslog(get_class($this).'::showInputField extrafieldsobjectkey required', LOG_ERR);
			return 'BadValueForParamExtraFieldsObjectKey';
		}

		$label = $this->attributes[$extrafieldsobjectkey]['label'][$key];
		$type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
		$size = $this->attributes[$extrafieldsobjectkey]['size'][$key];
		$default = $this->attributes[$extrafieldsobjectkey]['default'][$key];
		$computed = $this->attributes[$extrafieldsobjectkey]['computed'][$key];
		$unique = $this->attributes[$extrafieldsobjectkey]['unique'][$key];
		$required = $this->attributes[$extrafieldsobjectkey]['required'][$key];
		$param = $this->attributes[$extrafieldsobjectkey]['param'][$key];
		$perms = (int) dol_eval($this->attributes[$extrafieldsobjectkey]['perms'][$key], 1, 1, '2');
		$langfile = $this->attributes[$extrafieldsobjectkey]['langfile'][$key];
		$list = (string) dol_eval($this->attributes[$extrafieldsobjectkey]['list'][$key], 1, 1, '2');
		$totalizable = $this->attributes[$extrafieldsobjectkey]['totalizable'][$key];
		$help = $this->attributes[$extrafieldsobjectkey]['help'][$key];
		$hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		//var_dump('key='.$key.' '.$value.' '.$moreparam.' '.$keysuffix.' '.$keyprefix.' '.$objectid.' '.$extrafieldsobjectkey.' '.$mode);
		//var_dump('label='.$label.' type='.$type.' param='.var_export($param, 1));

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) {
				return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			} else {
				return '';
			}
		}

		//
		// 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
		if (empty($morecss)) {
			// Add automatic css
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'datetimegmt' || $type == 'link') {
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'integer', 'double', 'price'))) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'password') {
				$morecss = 'maxwidth100';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} elseif ($type == 'radio') {
				$morecss = 'width25';
			} else {
				if (empty($size) || round((float) $size) < 12) {
					$morecss = 'minwidth100';
				} elseif (round((float) $size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
			// If css forced in attribute, we use this one
			if (!empty($this->attributes[$extrafieldsobjectkey]['css'][$key])) {
				$morecss = $this->attributes[$extrafieldsobjectkey]['css'][$key];
			}
		}

		if (in_array($type, array('date'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			if ($mode == 1) {
				// search filter on a date extrafield shows two inputs to select a date range
				$prefill = array(
					'start' => isset($value['start']) ? $value['start'] : '',
					'end'   => isset($value['end']) ? $value['end'] : ''
				);
				$out = '<div ' . ($moreparam ? $moreparam : '') . '><div class="nowrap">';
				$out .= $form->selectDate($prefill['start'], $keyprefix.$key.$keysuffix.'_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
				$out .= '</div><div class="nowrap">';
				$out .= $form->selectDate($prefill['end'], $keyprefix.$key.$keysuffix.'_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
				$out .= '</div></div>';
			} else {
				// TODO Must also support $moreparam
				$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
			}
		} elseif (in_array($type, array('datetime', 'datetimegmt'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 1;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			if ($mode == 1) {
				// search filter on a date extrafield shows two inputs to select a date range
				$prefill = array(
					'start' => isset($value['start']) ? $value['start'] : '',
					'end'   => isset($value['end']) ? $value['end'] : ''
				);
				$out = '<div ' . ($moreparam ? $moreparam : '') . '><div class="nowrap">';
				$out .= $form->selectDate($prefill['start'], $keyprefix.$key.$keysuffix.'_start', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"), 'tzuserrel');
				$out .= '</div><div class="nowrap">';
				$out .= $form->selectDate($prefill['end'], $keyprefix.$key.$keysuffix.'_end', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"), 'tzuserrel');
				$out .= '</div></div>';
			} else {
				// TODO Must also support $moreparam
				$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1, '', '', '', 1, '', '', 'tzuserrel');
			}
		} elseif (in_array($type, array('int', 'integer'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$newsize.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
		} elseif (preg_match('/varchar/', $type)) {
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$size.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
		} elseif (in_array($type, array('mail', 'ip', 'phone', 'url'))) {
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
		} elseif ($type == 'icon') {
			/* External lib inclusion are not allowed in backoffice. Also lib is included several time if there is several icon file.
			 Some code must be added into main when MAIN_ADD_ICONPICKER_JS is set to add of lib in html header
			 $out ='<link rel="stylesheet" href="'.dol_buildpath('/myfield/css/fontawesome-iconpicker.min.css', 1).'">';
			 $out.='<script src="'.dol_buildpath('/myfield/js/fontawesome-iconpicker.min.js', 1).'"></script>';
			 */
			$out .= '<input type="text" class="form-control icp icp-auto iconpicker-element iconpicker-input flat '.$morecss.' maxwidthonsmartphone"';
			$out .= ' name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			if (getDolGlobalInt('MAIN_ADD_ICONPICKER_JS')) {
				$out .= '<script>';
				$options = "{ title: '<b>".$langs->trans("IconFieldSelector")."</b>', placement: 'right', showFooter: false, templates: {";
				$options .= "iconpicker: '<div class=\"iconpicker\"><div style=\"background-color:#EFEFEF;\" class=\"iconpicker-items\"></div></div>',";
				$options .= "iconpickerItem: '<a role=\"button\" href=\"#\" class=\"iconpicker-item\" style=\"background-color:#DDDDDD;\"><i></i></a>',";
				// $options.="buttons: '<button style=\"background-color:#FFFFFF;\" class=\"iconpicker-btn iconpicker-btn-cancel btn btn-default btn-sm\">".$langs->trans("Cancel")."</button>";
				// $options.="<button style=\"background-color:#FFFFFF;\" class=\"iconpicker-btn iconpicker-btn-accept btn btn-primary btn-sm\">".$langs->trans("Save")."</button>',";
				$options .= "footer: '<div class=\"popover-footer\" style=\"background-color:#EFEFEF;\"></div>',";
				$options .= "search: '<input type=\"search\" class\"form-control iconpicker-search\" placeholder=\"".$langs->trans("TypeToFilter")."\" />',";
				$options .= "popover: '<div class=\"iconpicker-popover popover\">";
				$options .= "   <div class=\"arrow\" ></div>";
				$options .= "   <div class=\"popover-title\" style=\"text-align:center;background-color:#EFEFEF;\"></div>";
				$options .= "   <div class=\"popover-content \" ></div>";
				$options .= "</div>'}}";
				$out .= "$('#".$keyprefix.$key.$keysuffix."').iconpicker(".$options.");";
				$out .= '</script>';
			}
		} elseif ($type == 'text') {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out = (string) $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'html') {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor') && getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_5, '90%');
				$out = (string) $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'boolean') {
			if (empty($mode)) {
				$checked = '';
				if (!empty($value)) {
					$checked = ' checked value="1" ';
				} else {
					$checked = ' value="1" ';
				}
				$out = '<input type="checkbox" class="flat valignmiddle'.($morecss ? ' '.$morecss : '').' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
			} else {
				$out = $form->selectyesno($keyprefix.$key.$keysuffix, $value, 1, false, 1, 1, 'width75 yesno');
			}
			$out .= '<input type="hidden" name="'.$keyprefix.$key.$keysuffix.'_boolean" value="1">';	// A hidden field ending with "_boolean" that is always set to 1.
		} elseif ($type == 'price') {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone right" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').' placeholder="'.$langs->getCurrencySymbol($conf->currency).'">';
		} elseif ($type == 'pricecy') {
			$currency = $conf->currency;
			if (!empty($value)) {
				// $value in memory is a php string like '10.01:USD'
				$pricetmp = explode(':', $value);
				$currency = !empty($pricetmp[1]) ? $pricetmp[1] : $conf->currency;
				$value = price($pricetmp[0]);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
			$out .= $form->selectCurrency($currency, $keyprefix.$key.$keysuffix.'currency_id');
		} elseif ($type == 'double') {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
		} elseif ($type == 'select') {
			$out = '';
			if ($mode) {
				$options = array();
				foreach ($param['options'] as $okey => $val) {
					if ((string) $okey == '') {
						continue;
					}

					$valarray = explode('|', $val);
					$val = $valarray[0];

					if ($langfile && $val) {
						$options[$okey] = $langs->trans($val);
					} else {
						$options[$okey] = $val;
					}
				}
				$selected = array();
				if (!is_array($value)) {
					$selected = explode(',', $value);
				}

				$out .= $form->multiselectarray($keyprefix.$key.$keysuffix, $options, $selected, 0, 0, $morecss, 0, 0, '', '', '', !empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2'));
			} else {
				if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
					include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
					$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
				}

				$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
				$out .= '<option value="0">&nbsp;</option>';
				foreach ($param['options'] as $key2 => $val2) {
					if ((string) $key2 == '') {
						continue;
					}
					$valarray = explode('|', $val2);
					$val2 = $valarray[0];
					$parent = '';
					if (!empty($valarray[1])) {
						$parent = $valarray[1];
					}
					$out .= '<option value="'.$key2.'"';
					$out .= (((string) $value == (string) $key2) ? ' selected' : '');
					$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
					$out .= '>';
					if ($langfile && $val2) {
						$out .= $langs->trans($val2);
					} else {
						$out .= $val2;
					}
					$out .= '</option>';
				}
				$out .= '</select>';
			}
		} elseif ($type == 'sellist') {		// List of values selected from a table (1 choice)
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if (is_array($param['options'])) {
				$tmpparamoptions = array_keys($param['options']);
				$paramoptions = preg_split('/[\r\n]+/', $tmpparamoptions[0]);

				$InfoFieldList = explode(":", $paramoptions[0], 5);
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if different of rowid)
				// optional parameters...
				// 3 : key field parent (for dependent lists). How this is used ?
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value. Or use USF on the second line.
				// 5 : string category type. This replace the filter.
				// 6 : ids categories list separated by comma for category root. This replace the filter.
				// 7 : sort field (not used here but used into format for commobject)

				// If there is a filter, we extract it by taking all content inside parenthesis.
				if (! empty($InfoFieldList[4])) {
					$pos = 0;
					$parenthesisopen = 0;
					while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
						if (substr($InfoFieldList[4], $pos, 1) == '(') {
							$parenthesisopen++;
						}
						if (substr($InfoFieldList[4], $pos, 1) == ')') {
							$parenthesisopen--;
						}
						$pos++;
					}
					$tmpbefore = substr($InfoFieldList[4], 0, $pos);
					$tmpafter = substr($InfoFieldList[4], $pos + 1);
					//var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
					$InfoFieldList[4] = $tmpbefore;
					if ($tmpafter !== '') {
						$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
					}
					//var_dump($InfoFieldList);
				}

				//$Usf = empty($paramoptions[1]) ? '' :$paramoptions[1];

				$parentName = '';
				$parentField = '';
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if (!$filter_categorie) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT ".$keyList;
					$sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];

					// Add filter from 4th field
					if (!empty($InfoFieldList[4])) {
						// can use current entity filter
						if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
							$InfoFieldList[4] = str_replace('$ENTITY$', (string) $conf->entity, $InfoFieldList[4]);
						}
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}
						//We have to join on extrafield table
						$errstr = '';
						if (strpos($InfoFieldList[4], 'extra.') !== false) {
							$sql .= ' as main, '.$this->db->prefix().$InfoFieldList[0].'_extrafields as extra';
							$sqlwhere .= " WHERE extra.fk_object = main.".$InfoFieldList[2]." AND ".$InfoFieldList[4];
							$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						} else {
							$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}

					// Add Usf filter on second line
					/*
					 if ($Usf) {
					 $errorstr = '';
					 $sqlusf .= forgeSQLFromUniversalSearchCriteria($Usf, $errorstr);
					 if (!$errorstr) {
					 $sqlwhere .= $sqlusf;
					 } else {
					 $sqlwhere .= " AND invalid_usf_filter_of_extrafield";
					 }
					 }
					 */

					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= ' AND entity = '.((int) $conf->entity);
					}
					$sql .= $sqlwhere;
					//print $sql;

					$sql .= ' ORDER BY '.implode(', ', $fields_label);

					dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$out .= '<option value="0">&nbsp;</option>';
						$num = $this->db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							// Several field into label (eq table:code|label:rowid)
							$notrans = false;
							$fields_label = explode('|', $InfoFieldList[1]);
							if (is_array($fields_label) && count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow.' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}

							if ($value == $obj->rowid) {
								if (!$notrans) {
									foreach ($fields_label as $field_toshow) {
										$translabel = $langs->trans($obj->$field_toshow);
										$labeltoshow = $translabel.' ';
									}
								}
								$out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									$labeltoshow = $translabel;
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName.':'.$obj->{$parentField};
								}

								$out .= '<option value="'.$obj->rowid.'"';
								$out .= ($value == $obj->rowid ? ' selected' : '');
								$out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
								$out .= '>'.$labeltoshow.'</option>';
							}

							$i++;
						}
						$this->db->free($resql);
					} else {
						print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out .= '<option value="0">&nbsp;</option>';
					if (is_array($data)) {
						foreach ($data as $data_key => $data_value) {
							$out .= '<option value="'.$data_key.'"';
							$out .= ($value == $data_key ? ' selected' : '');
							$out .= '>'.$data_value.'</option>';
						}
					}
				}
			}
			$out .= '</select>';
		} elseif ($type == 'checkbox') {
			$value_arr = $value;
			if (!is_array($value)) {
				$value_arr = explode(',', $value);
			}
			$out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ? null : $param['options']), $value_arr, '', 0, '', 0, '100%');
		} elseif ($type == 'radio') {
			$out = '';
			foreach ($param['options'] as $keyopt => $val) {
				$out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
				$out .= ' value="'.$keyopt.'"';
				$out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$langs->trans($val).'</label><br>';
			}
		} elseif ($type == 'chkbxlst') {	// List of values selected from a table (n choices)
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$tmpparamoptions = array_keys($param['options']);
				$paramoptions = preg_split('/[\r\n]+/', $tmpparamoptions[0]);

				$InfoFieldList = explode(":", $paramoptions[0], 5);
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if different of rowid)
				// optional parameters...
				// 3 : key field parent (for dependent lists). How this is used ?
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value. Or use USF on the second line.
				// 5 : string category type. This replace the filter.
				// 6 : ids categories list separated by comma for category root. This replace the filter.
				// 7 : sort field (not used here but used into format for commobject)

				// If there is a filter, we extract it by taking all content inside parenthesis.
				if (! empty($InfoFieldList[4])) {
					$pos = 0;
					$parenthesisopen = 0;
					while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
						if (substr($InfoFieldList[4], $pos, 1) == '(') {
							$parenthesisopen++;
						}
						if (substr($InfoFieldList[4], $pos, 1) == ')') {
							$parenthesisopen--;
						}
						$pos++;
					}
					$tmpbefore = substr($InfoFieldList[4], 0, $pos);
					$tmpafter = substr($InfoFieldList[4], $pos + 1);
					//var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
					$InfoFieldList[4] = $tmpbefore;
					if ($tmpafter !== '') {
						$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
					}
					//var_dump($InfoFieldList);
				}

				//$Usf = empty($paramoptions[1]) ? '' :$paramoptions[1];

				$parentName = '';
				$parentField = '';
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if (!$filter_categorie) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT ".$keyList;
					$sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];

					// Add filter from 4th field
					if (!empty($InfoFieldList[4])) {
						// can use current entity filter
						if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
							$InfoFieldList[4] = str_replace('$ENTITY$', (string) $conf->entity, $InfoFieldList[4]);
						}
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
						} elseif (preg_match("#^.*list.php$#", $_SERVER["PHP_SELF"])) {
							// Pattern for word=$ID$
							$word = '\b[a-zA-Z0-9-\.-_]+\b=\$ID\$';

							// Removing spaces around =, ( and )
							$InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

							$nbPreg = 1;
							// While we have parenthesis
							while ($nbPreg != 0) {
								// Initialise counters
								$nbPregRepl = $nbPregSel = 0;
								// Remove all parenthesis not preceded with '=' sign
								$InfoFieldList[4] = preg_replace('#([^=])(\([^)^(]*('.$word.')[^)^(]*\))#', '$1 $3 ', $InfoFieldList[4], -1, $nbPregRepl);
								// Remove all escape characters around '=' and parenthesis
								$InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);
								// Remove all parentheses preceded with '='
								$InfoFieldList[4] = preg_replace('#\b[a-zA-Z0-9-\.-_]+\b=\([^)^(]*('.$word.')[^)^(]*\)#', '$1 ', $InfoFieldList[4], -1, $nbPregSel);
								// On retire les escapes autour des = et parenthèses
								$InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

								// UPdate the totals counter for the loop
								$nbPreg = $nbPregRepl + $nbPregSel;
							}

							// In case there is AND ou OR, before or after
							$matchCondition = array();
							preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
							while (!empty($matchCondition[0])) {
								// If the two sides differ but are not empty
								if (!empty($matchCondition[1]) && !empty($matchCondition[3]) && $matchCondition[1] != $matchCondition[3]) {
									// Nobody sain would do that without parentheses
									$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
								} else {
									if (!empty($matchCondition[1])) {
										$boolCond = (($matchCondition[1] == "AND") ? ' AND TRUE ' : ' OR FALSE ');
										$InfoFieldList[4] = str_replace($matchCondition[0], $boolCond.$matchCondition[3], $InfoFieldList[4]);
									} elseif (!empty($matchCondition[3])) {
										$boolCond = (($matchCondition[3] == "AND") ? ' TRUE AND ' : ' FALSE OR');
										$InfoFieldList[4] = str_replace($matchCondition[0], $boolCond, $InfoFieldList[4]);
									} else {
										$InfoFieldList[4] = " TRUE ";
									}
								}

								// In case there is AND ou OR, before or after
								preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
							}
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}

						// We have to join on extrafield table
						$errstr = '';
						if (strpos($InfoFieldList[4], 'extra.') !== false) {
							$sql .= ' as main, '.$this->db->prefix().$InfoFieldList[0].'_extrafields as extra';
							$sqlwhere .= " WHERE extra.fk_object = main.".$InfoFieldList[2];
							$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						} else {
							$sqlwhere .= " WHERE " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}

					// Add Usf filter on second line
					/*
					 if ($Usf) {
					 $errorstr = '';
					 $sqlusf .= forgeSQLFromUniversalSearchCriteria($Usf, $errorstr);
					 if (!$errorstr) {
					 $sqlwhere .= $sqlusf;
					 } else {
					 $sqlwhere .= " AND invalid_usf_filter_of_extrafield";
					 }
					 }
					 */

					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = ".((int) $conf->entity);
					}
					// $sql.=preg_replace('/^ AND /','',$sqlwhere);
					// print $sql;

					$sql .= $sqlwhere;
					$sql .= ' ORDER BY '.implode(', ', $fields_label);

					dol_syslog(get_class($this).'::showInputField type=chkbxlst', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						$i = 0;

						$data = array();

						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							$notrans = false;
							// Several field into label (eq table:code|label:rowid)
							$fields_label = explode('|', $InfoFieldList[1]);
							if (is_array($fields_label)) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow.' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
								$labeltoshow = '';
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow .= ' '.dol_trunc($translabel, 18).' ';
									} else {
										$labeltoshow .= ' '.dol_trunc($obj->$field_toshow, 18).' ';
									}
								}
								$data[$obj->rowid] = $labeltoshow;
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 18);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}

								if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
									$data[$obj->rowid] = $labeltoshow;
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName.':'.$obj->{$parentField};
								}

								$data[$obj->rowid] = $labeltoshow;
							}

							$i++;
						}
						$this->db->free($resql);

						$out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
					} else {
						print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
				}
			}
		} elseif ($type == 'link') {
			$param_list = array_keys($param['options']); // $param_list[0] = 'ObjectName:classPath' but can also be 'ObjectName:classPath:1:(status:=:1)'
			/* Removed.
			 The selectForForms is called with parameter $objectfield defined, so the app can retrieve the filter inside the ajax component instead of being provided as parameters. The
			 filter was used to pass SQL requests leading to serious SQL injection problem. This should not be possible. Also the call of the ajax was broken by some WAF.
			 if (strpos($param_list[0], '$ID$') !== false && !empty($objectid)) {
			 $param_list[0] = str_replace('$ID$', $objectid, $param_list[0]);
			 }*/
			$showempty = (($required && $default != '') ? 0 : 1);

			$tmparray = explode(':', $param_list[0]);

			$element = $extrafieldsobjectkey;		// $extrafieldsobjectkey comes from $object->table_element but we need $object->element
			if ($element == 'socpeople') {
				$element = 'contact';
			} elseif ($element == 'projet') {
				$element = 'project';
			}

			//$objectdesc = $param_list[0];				// Example: 'ObjectName:classPath:1:(status:=:1)'	Replaced by next line: this was propagated also a filter by ajax call that was blocked by some WAF
			$objectdesc = $tmparray[0];					// Example: 'ObjectName:classPath'					To not propagate any filter (selectForForms do ajax call and propagating SQL filter is blocked by some WAF). Also we should use the one into the definition in the ->fields of $elem if found.
			$objectfield = $element.':options_'.$key;	// Example: 'actioncomm:options_fff'				To be used in priority to know object linked with all its definition (including filters)

			$out = $form->selectForForms($objectdesc, $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss, '', 0, 0, '', $objectfield);
		} elseif (in_array($type, ['point', 'multipts', 'linestrg', 'polygon'])) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeophp.class.php';
			$dolgeophp = new DolGeoPHP($this->db);
			$geojson = '{}';
			$centroidjson = getDolGlobalString('MAIN_INFO_SOCIETE_GEO_COORDINATES', '{}');
			if (!empty($value)) {
				$tmparray = $dolgeophp->parseGeoString($value);
				$geojson = $tmparray['geojson'];
				$centroidjson = $tmparray['centroidjson'];
			}
			if (!preg_match('/search_/', $keyprefix)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/geomapeditor.class.php';
				$geomapeditor = new GeoMapEditor();
				$out .= $geomapeditor->getHtml($keyprefix.$key.$keysuffix, $geojson, $centroidjson, $type);
			} else {
				// If keyprefix is search_ or search_options_, we must just use a simple text field
				$out = '';
			}
		} elseif ($type == 'password') {
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out = '<input style="display:none" type="text" name="fakeusernameremembered">'; // Hidden field to reduce impact of evil Google Chrome autopopulate bug.
			$out .= '<input autocomplete="new-password" type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */
		/*if (!empty($help) && $keyprefix != 'search_options_') {
		 $out .= $form->textwithpicto('', $help, 1, 'help', '', 0, 3);
		 }*/
		return $out;
	}


	/**
	 * Return HTML string to put an output field into a page
	 *
	 * @param   string	$key            		Key of attribute
	 * @param   string	$value          		Value to show
	 * @param	string	$moreparam				To add more parameters on html input tag (only checkbox use html input for output rendering)
	 * @param	string	$extrafieldsobjectkey	Required (for example $object->table_element).
	 * @param 	Translate $outputlangs 			Output language
	 * @return	string							Formatted value
	 */
	public function showOutputField($key, $value, $moreparam = '', $extrafieldsobjectkey = '', $outputlangs = null)
	{
		global $conf, $langs;

		if (is_null($outputlangs) || !is_object($outputlangs)) {
			$outputlangs = $langs;
		}

		if (empty($extrafieldsobjectkey)) {
			dol_syslog(get_class($this).'::showOutputField extrafieldsobjectkey required', LOG_ERR);
			return 'BadValueForParamExtraFieldsObjectKey';
		}

		$label = $this->attributes[$extrafieldsobjectkey]['label'][$key];
		$type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
		$size = $this->attributes[$extrafieldsobjectkey]['size'][$key];			// Can be '255', '24,8'...
		$default = $this->attributes[$extrafieldsobjectkey]['default'][$key];
		$computed = $this->attributes[$extrafieldsobjectkey]['computed'][$key];
		$unique = $this->attributes[$extrafieldsobjectkey]['unique'][$key];
		$required = $this->attributes[$extrafieldsobjectkey]['required'][$key];
		$param = $this->attributes[$extrafieldsobjectkey]['param'][$key];
		$perms = (int) dol_eval($this->attributes[$extrafieldsobjectkey]['perms'][$key], 1, 1, '2');
		$langfile = $this->attributes[$extrafieldsobjectkey]['langfile'][$key];
		$list = (string) dol_eval($this->attributes[$extrafieldsobjectkey]['list'][$key], 1, 1, '2');
		$help = $this->attributes[$extrafieldsobjectkey]['help'][$key];
		$cssview = $this->attributes[$extrafieldsobjectkey]['cssview'][$key];

		$hidden = (empty($list) ? 1 : 0); // If $list empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) {
			return ''; // This is a protection. If field is hidden, we should just not call this method.
		}

		//if ($computed) $value =		// $value is already calculated into $value before calling this method

		$showsize = 0;
		if ($type == 'date') {
			$showsize = 10;
			if ($value !== '') {
				$value = dol_print_date($value, 'day');	// For date without hour, date is always GMT for storage and output
			}
		} elseif ($type == 'datetime') {
			$showsize = 19;
			if ($value !== '') {
				$value = dol_print_date($value, 'dayhour', 'tzuserrel');
			}
		} elseif ($type == 'datetimegmt') {
			$showsize = 19;
			if ($value !== '') {
				$value = dol_print_date($value, 'dayhour', 'gmt');
			}
		} elseif ($type == 'int') {
			$showsize = 10;
		} elseif ($type == 'double') {
			if (!empty($value)) {
				//$value=price($value);
				$sizeparts = explode(",", $size);
				$number_decimals = array_key_exists(1, $sizeparts) ? $sizeparts[1] : 0;
				$value = price($value, 0, $outputlangs, 0, 0, $number_decimals, '');
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked ';
			}
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2) {
				$value = '<input type="checkbox" '.$checked.' '.($moreparam ? $moreparam : '').' readonly disabled>';
			} else {
				$value = yn($value ? 1 : 0);
			}
		} elseif ($type == 'mail') {
			$value = dol_print_email($value, 0, 0, 0, 64, 1, 1);
		} elseif ($type == 'ip') {
			$value = dol_print_ip($value, 0);
		} elseif ($type == 'icon') {
			$value = '<span class="'.$value.'"></span>';
		} elseif ($type == 'url') {
			$value = dol_print_url($value, '_blank', 32, 1);
		} elseif ($type == 'phone') {
			$value = dol_print_phone($value, '', 0, 0, '', '&nbsp;', 'phone');
		} elseif ($type == 'price') {
			//$value = price($value, 0, $langs, 0, 0, -1, $conf->currency);
			if ($value || $value == '0') {
				$value = price($value, 0, $outputlangs, 0, $conf->global->MAIN_MAX_DECIMALS_TOT, -1).' '.$outputlangs->getCurrencySymbol($conf->currency);
			}
		} elseif ($type == 'pricecy') {
			$currency = $conf->currency;
			if (!empty($value)) {
				// $value in memory is a php string like '0.01:EUR'
				$pricetmp = explode(':', $value);
				$currency = !empty($pricetmp[1]) ? $pricetmp[1] : $conf->currency;
				$value = $pricetmp[0];
			}
			if ($value || $value == '0') {
				$value = price($value, 0, $outputlangs, 0, $conf->global->MAIN_MAX_DECIMALS_TOT, -1, $currency);
			}
		} elseif ($type == 'select') {
			$valstr = (!empty($param['options'][$value]) ? $param['options'][$value] : '');
			if (($pos = strpos($valstr, "|")) !== false) {
				$valstr = substr($valstr, 0, $pos);
			}
			if ($langfile && $valstr) {
				$value = $outputlangs->trans($valstr);
			} else {
				$value = $valstr;
			}
		} elseif ($type == 'sellist') {
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT ".$keyList;
			$sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];
			if (!empty($InfoFieldList[4]) && strpos($InfoFieldList[4], 'extra.') !== false) {
				$sql .= ' as main';
			}
			if ($selectkey == 'rowid' && empty($value)) {
				$sql .= " WHERE ".$selectkey." = 0";
			} elseif ($selectkey == 'rowid') {
				$sql .= " WHERE ".$selectkey." = ".((int) $value);
			} else {
				$sql .= " WHERE ".$selectkey." = '".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output

					$obj = $this->db->fetch_object($resql);

					// Several field into label (eq table:code|label:rowid)
					$fields_label = explode('|', $InfoFieldList[1]);

					if (is_array($fields_label) && count($fields_label) > 1) {
						foreach ($fields_label as $field_toshow) {
							$translabel = '';
							if (!empty($obj->$field_toshow)) {
								$translabel = $outputlangs->trans($obj->$field_toshow);

								if ($translabel != $obj->$field_toshow) {
									$value .= dol_trunc($translabel, 24) . ' ';
								} else {
									$value .= $obj->$field_toshow . ' ';
								}
							}
						}
					} else {
						$translabel = '';
						$tmppropname = $InfoFieldList[1];
						//$obj->$tmppropname = '';
						if (!empty(isset($obj->$tmppropname) ? $obj->$tmppropname : '')) {
							$translabel = $outputlangs->trans($obj->$tmppropname);
						}
						if ($translabel != (isset($obj->$tmppropname) ? $obj->$tmppropname : '')) {
							$value = dol_trunc($translabel, 18);
						} else {
							$value = isset($obj->$tmppropname) ? $obj->$tmppropname : '';
						}
					}
				} else {
					$toprint = array();
					$obj = $this->db->fetch_object($resql);
					if ($obj->rowid) {
						require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
						$c = new Categorie($this->db);
						$result = $c->fetch($obj->rowid);
						if ($result > 0) {
							$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
							foreach ($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . img_object('', 'category') . ' ' . $way . '</li>';
							}
						}
					}
					$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
				}
			} else {
				dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'radio') {
			if (!isset($param['options'][$value])) {
				$outputlangs->load('errors');
				$value = $outputlangs->trans('ErrorNoValueForRadioType');
			} else {
				$value = $outputlangs->trans($param['options'][$value]);
			}
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$value = '';
			$toprint = array();
			if (is_array($value_arr)) {
				foreach ($value_arr as $keyval => $valueval) {
					if (!empty($valueval)) {
						$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">'.$param['options'][$valueval].'</li>';
					}
				}
			}
			$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		} elseif ($type == 'chkbxlst') {
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT ".$keyList;
			$sql .= " FROM ".$this->db->prefix().$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra.') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output
					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						// Several field into label (eq table:code|label:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							if (is_array($fields_label) && count($fields_label) > 1) {
								$label = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">';
								foreach ($fields_label as $field_toshow) {
									$translabel = '';
									if (!empty($obj->$field_toshow)) {
										$translabel = $outputlangs->trans($obj->$field_toshow);
									}
									if ($translabel != $field_toshow) {
										$label .= ' '.dol_trunc($translabel, 18);
									} else {
										$label .= ' '.$obj->$field_toshow;
									}
								}
								$label .= '</li>';
								$toprint[] = $label;
							} else {
								$translabel = '';
								if (!empty($obj->{$InfoFieldList[1]})) {
									$translabel = $outputlangs->trans($obj->{$InfoFieldList[1]});
								}
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">'.dol_trunc($translabel, 18).'</li>';
								} else {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">'.$obj->{$InfoFieldList[1]}.'</li>';
								}
							}
						}
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							$c = new Categorie($this->db);
							$c->fetch($obj->rowid);
							$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
							foreach ($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"'.($c->color ? ' style="background: #'.$c->color.';"' : ' style="background: #bbb"').'>'.img_object('', 'category').' '.$way.'</li>';
							}
						}
					}
				}
				if (!empty($toprint)) {
					$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
				}
			} else {
				dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'link') {
			$out = '';

			// Only if something to display (perf)
			if ($value) {		// If we have -1 here, pb is into insert, not into output (fix insert instead of changing code here to compensate)
				$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];
				if (!empty($classpath)) {
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname)) {
						$object = new $classname($this->db);
						'@phan-var-force CommonObject $object';
						$object->fetch($value);
						$value = $object->getNomUrl(3);
					}
				} else {
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
		} elseif ($type == 'point') {
			if (!empty($value)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeophp.class.php';
				$dolgeophp = new DolGeoPHP($this->db);
				$value = $dolgeophp->getXYString($value);
			} else {
				$value = '';
			}
		} elseif (in_array($type, ['multipts','linestrg', 'polygon'])) {
			if (!empty($value)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeophp.class.php';
				$dolgeophp = new DolGeoPHP($this->db);
				$value = $dolgeophp->getPointString($value);
			} else {
				$value = '';
			}
		} elseif ($type == 'text') {
			$value = '<div class="'.($cssview ? $cssview : 'shortmessagecut').'">'.dol_htmlentitiesbr($value).'</div>';
		} elseif ($type == 'html') {
			$value = dol_htmlentitiesbr($value);
		} elseif ($type == 'password') {
			$value = dol_trunc(preg_replace('/./i', '*', $value), 8, 'right', 'UTF-8', 1);
		} else {
			$showsize = round((float) $size);
			if ($showsize > 48) {
				$showsize = 48;
			}
		}

		//print $type.'-'.$size;
		$out = $value;

		return $out;
	}

	/**
	 * Return the CSS to use for this extrafield into list
	 *
	 * @param   string	$key            		Key of attribute
	 * @param	string	$extrafieldsobjectkey	If defined, use the new method to get extrafields data
	 * @return	string							Formatted value
	 */
	public function getAlignFlag($key, $extrafieldsobjectkey = '')
	{
		$type = 'varchar';
		if (!empty($extrafieldsobjectkey)) {
			$type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
		}

		$cssstring = '';

		if (in_array($type, array('date', 'datetime', 'datetimegmt',))) {
			$cssstring = "center";
		} elseif (in_array($type, array('int', 'price', 'double'))) {
			$cssstring = "right";
		} elseif (in_array($type, array('boolean', 'radio', 'checkbox', 'ip', 'icon'))) {
			$cssstring = "center";
		}

		if (!empty($this->attributes[$extrafieldsobjectkey]['csslist'][$key])) {
			$cssstring .= ($cssstring ? ' ' : '').$this->attributes[$extrafieldsobjectkey]['csslist'][$key];
		} else {
			if (in_array($type, array('ip'))) {
				$cssstring .= ($cssstring ? ' ' : '').'tdoverflowmax150';
			}
		}

		return $cssstring;
	}

	/**
	 * Return HTML string to print separator extrafield
	 *
	 * @param   string	$key            Key of attribute
	 * @param	object	$object			Object
	 * @param	int		$colspan		Value of colspan to use (it must includes the first column with title)
	 * @param	string	$display_type	"card" for form display, "line" for document line display (extrafields on propal line, order line, etc...)
	 * @param 	string  $mode           Show output ('view') or input ('create' or 'edit') for extrafield
	 * @return 	string					HTML code with line for separator
	 */
	public function showSeparator($key, $object, $colspan = 2, $display_type = 'card', $mode = '')
	{
		global $conf, $langs;

		$tagtype = 'tr';
		$tagtype_dyn = 'td';

		if ($display_type == 'line') {
			$tagtype = 'div';
			$tagtype_dyn = 'span';
			$colspan = 0;
		}

		$extrafield_param = $this->attributes[$object->table_element]['param'][$key];
		$extrafield_param_list = array();
		if (!empty($extrafield_param) && is_array($extrafield_param)) {
			$extrafield_param_list = array_keys($extrafield_param['options']);
		}

		// Set $extrafield_collapse_display_value (do we have to collapse/expand the group after the separator)
		$extrafield_collapse_display_value = -1;
		$expand_display = false;
		if (is_array($extrafield_param_list) && count($extrafield_param_list) > 0) {
			$extrafield_collapse_display_value = intval($extrafield_param_list[0]);
			$expand_display = ((isset($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key]) || GETPOSTINT('ignorecollapsesetup')) ? (!empty($_COOKIE['DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key])) : !($extrafield_collapse_display_value == 2));
		}
		$disabledcookiewrite = 0;
		if ($mode == 'create') {
			// On create mode, force separator group to not be collapsible
			$extrafield_collapse_display_value = 1;
			$expand_display = true;	// We force group to be shown expanded
			$disabledcookiewrite = 1; // We keep status of group unchanged into the cookie
		}

		$out = '<'.$tagtype.' id="trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').'" class="trextrafieldseparator trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').'">';
		$out .= '<'.$tagtype_dyn.' '.(!empty($colspan) ? 'colspan="' . $colspan . '"' : '').'>';
		// Some js code will be injected here to manage the collapsing of extrafields
		// Output the picto
		$out .= '<span class="'.($extrafield_collapse_display_value ? 'cursorpointer ' : '').($extrafield_collapse_display_value == 0 ? 'fas fa-square opacitymedium' : 'far fa-'.(($expand_display ? 'minus' : 'plus').'-square')).'"></span>';
		$out .= '&nbsp;';
		$out .= '<strong>';
		$out .= $langs->trans($this->attributes[$object->table_element]['label'][$key]);
		$out .= '</strong>';
		$out .= '</'.$tagtype_dyn.'>';
		$out .= '</'.$tagtype.'>';

		$collapse_group = $key.(!empty($object->id) ? '_'.$object->id : '');
		//$extrafields_collapse_num = $this->attributes[$object->table_element]['pos'][$key].(!empty($object->id)?'_'.$object->id:'');

		if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
			// Set the collapse_display status to cookie in priority or if ignorecollapsesetup is 1, if cookie and ignorecollapsesetup not defined, use the setup.
			$this->expand_display[$collapse_group] = $expand_display;

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '<!-- Add js script to manage the collapse/uncollapse of extrafields separators '.$key.' -->'."\n";
				$out .= '<script nonce="'.getNonce().'" type="text/javascript">'."\n";
				$out .= 'jQuery(document).ready(function(){'."\n";
				if (empty($disabledcookiewrite)) {
					if (!$expand_display) {
						$out .= '   console.log("Inject js for the collapsing of extrafield '.$key.' - hide");'."\n";
						$out .= '   jQuery(".trextrafields_collapse'.$collapse_group.'").hide();'."\n";
					} else {
						$out .= '   console.log("Inject js for collapsing of extrafield '.$key.' - keep visible and set cookie");'."\n";
						$out .= '   document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
					}
				}
				$out .= '   jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').'").click(function(){'."\n";
				$out .= '       console.log("We click on collapse/uncollapse to hide/show .trextrafields_collapse'.$collapse_group.'");'."\n";
				$out .= '       jQuery(".trextrafields_collapse'.$collapse_group.'").toggle(100, function(){'."\n";
				$out .= '           if (jQuery(".trextrafields_collapse'.$collapse_group.'").is(":hidden")) {'."\n";
				$out .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').' '.$tagtype_dyn.' span").addClass("fa-plus-square").removeClass("fa-minus-square");'."\n";
				$out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=0; path='.$_SERVER["PHP_SELF"].'"'."\n";
				$out .= '           } else {'."\n";
				$out .= '               jQuery("#trextrafieldseparator'.$key.(!empty($object->id) ? '_'.$object->id : '').' '.$tagtype_dyn.' span").addClass("fa-minus-square").removeClass("fa-plus-square");'."\n";
				$out .= '               document.cookie = "DOLCOLLAPSE_'.$object->table_element.'_extrafields_'.$key.'=1; path='.$_SERVER["PHP_SELF"].'"'."\n";
				$out .= '           }'."\n";
				$out .= '       });'."\n";
				$out .= '   });'."\n";
				$out .= '});'."\n";
				$out .= '</script>'."\n";
			}
		} else {
			$this->expand_display[$collapse_group] = 1;
		}

		return $out;
	}

	/**
	 * Fill array_options property of object by extrafields value (using for data sent by forms)
	 *
	 * @param   array|null	$extralabels    	Deprecated (old $array of extrafields, now set this to null)
	 * @param   object		$object         	Object
	 * @param	string		$onlykey			Only some keys are filled:
	 *                      	            	'string' => When we make update of only one extrafield ($action = 'update_extras'), calling page can set this to avoid to have other extrafields being reset.
	 *                          	        	'@GETPOSTISSET' => When we make update of several extrafields ($action = 'update'), calling page can set this to avoid to have fields not into POST being reset.
	 * @param	int			$todefaultifmissing 1=Set value to the default value in database if value is mandatory and missing
	 * @return	int								1 if array_options set, 0 if no value, -1 if error (field required missing for example)
	 */
	public function setOptionalsFromPost($extralabels, &$object, $onlykey = '', $todefaultifmissing = 0)
	{
		global $langs;

		$nofillrequired = 0; // For error when required field left blank
		$error_field_required = array();

		if (isset($this->attributes[$object->table_element]['label']) && is_array($this->attributes[$object->table_element]['label'])) {
			$extralabels = $this->attributes[$object->table_element]['label'];
		}

		if (is_array($extralabels)) {
			// Get extra fields
			foreach ($extralabels as $key => $value) {
				if (!empty($onlykey) && $onlykey != '@GETPOSTISSET' && $key != $onlykey) {
					continue;
				}

				if (!empty($onlykey) && $onlykey == '@GETPOSTISSET' && !GETPOSTISSET('options_'.$key) && (! in_array($this->attributes[$object->table_element]['type'][$key], array('boolean', 'checkbox', 'chkbxlst', 'point', 'multipts', 'linestrg', 'polygon')))) {
					//when unticking boolean field, it's not set in POST
					continue;
				}

				$key_type = $this->attributes[$object->table_element]['type'][$key];
				if ($key_type == 'separate') {
					continue;
				}

				$enabled = 1;
				if (isset($this->attributes[$object->table_element]['enabled'][$key])) {	// 'enabled' is often a condition on module enabled or not
					$enabled = (int) dol_eval((string) $this->attributes[$object->table_element]['enabled'][$key], 1, 1, '2');
				}

				$visibility = 1;
				if (isset($this->attributes[$object->table_element]['list'][$key])) {		// 'list' is option for visibility
					$visibility = (int) dol_eval($this->attributes[$object->table_element]['list'][$key], 1, 1, '2');
				}

				$perms = 1;
				if (isset($this->attributes[$object->table_element]['perms'][$key])) {
					$perms = (int) dol_eval($this->attributes[$object->table_element]['perms'][$key], 1, 1, '2');
				}
				if (empty($enabled)
					|| (
						$onlykey === '@GETPOSTISSET'
						&& in_array($this->attributes[$object->table_element]['type'][$key], array('boolean', 'checkbox', 'chkbxlst'))
						&& in_array(abs($enabled), array(2, 5))
						&& ! GETPOSTISSET('options_' . $key) // Update hidden checkboxes and multiselect only if they are provided
						)
					) {
						continue;
				}

					$visibility_abs = abs($visibility);
					// not modify if extra field is not in update form (0 : never, 2 or -2 : list only, 5 or - 5 : list and view only)
				if (empty($visibility_abs) || $visibility_abs == 2 || $visibility_abs == 5) {
					continue;
				}
				if (empty($perms)) {
					continue;
				}

				if ($this->attributes[$object->table_element]['required'][$key]) {	// Value is required
					// Check if functionally empty without using GETPOST (depending on the type of extrafield, a
					// technically non-empty value may be treated as empty functionally).
						// value can be alpha, int, array, etc...
						$v = $_POST["options_".$key] ?? null;
						$type = $this->attributes[$object->table_element]['type'][$key];
					if (self::isEmptyValue($v, $type)) {
						//print 'ccc'.$value.'-'.$this->attributes[$object->table_element]['required'][$key];

						// Field is not defined. We mark this as an error. We may fix it later if there is a default value and $todefaultifmissing is set.

						$nofillrequired++;
						if (!empty($this->attributes[$object->table_element]['langfile'][$key])) {
							$langs->load($this->attributes[$object->table_element]['langfile'][$key]);
						}
						$error_field_required[$key] = $langs->transnoentitiesnoconv($value);
					}
				}

				if (in_array($key_type, array('date'))) {
					// Clean parameters
					$value_key = dol_mktime(12, 0, 0, GETPOSTINT("options_".$key."month"), GETPOSTINT("options_".$key."day"), GETPOSTINT("options_".$key."year"));
				} elseif (in_array($key_type, array('datetime'))) {
					// Clean parameters
					$value_key = dol_mktime(GETPOSTINT("options_".$key."hour"), GETPOSTINT("options_".$key."min"), GETPOSTINT("options_".$key."sec"), GETPOSTINT("options_".$key."month"), GETPOSTINT("options_".$key."day"), GETPOSTINT("options_".$key."year"), 'tzuserrel');
				} elseif (in_array($key_type, array('datetimegmt'))) {
					// Clean parameters
					$value_key = dol_mktime(GETPOSTINT("options_".$key."hour"), GETPOSTINT("options_".$key."min"), GETPOSTINT("options_".$key."sec"), GETPOSTINT("options_".$key."month"), GETPOSTINT("options_".$key."day"), GETPOSTINT("options_".$key."year"), 'gmt');
				} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
					$value_arr = GETPOST("options_".$key, 'array'); // check if an array
					if (!empty($value_arr)) {
						$value_key = implode(',', $value_arr);
					} else {
						$value_key = '';
					}
				} elseif (in_array($key_type, array('price', 'double'))) {
					$value_arr = GETPOST("options_".$key, 'alpha');
					$value_key = price2num($value_arr);
				} elseif (in_array($key_type, array('pricecy', 'double'))) {
					$value_key = price2num(GETPOST("options_".$key, 'alpha')).':'.GETPOST("options_".$key."currency_id", 'alpha');
				} elseif (in_array($key_type, array('html'))) {
					$value_key = GETPOST("options_".$key, 'restricthtml');
				} elseif (in_array($key_type, ['point', 'multipts', 'linestrg', 'polygon'])) {
					// construct point
					require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeophp.class.php';
					$geojson = GETPOST("options_".$key, 'restricthtml');
					if ($geojson != '{}') {
						$dolgeophp = new DolGeoPHP($this->db);
						$value_key = $dolgeophp->getWkt($geojson);
					} else {
						$value_key = '';
					}
				} elseif (in_array($key_type, array('text'))) {
					$label_security_check = 'alphanohtml';
					// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
					if (getDolGlobalString('MAIN_SECURITY_ALLOW_UNSECURED_REF_LABELS')) {
						$label_security_check = 'nohtml';
					} else {
						$label_security_check = !getDolGlobalString('MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML') ? 'alphanohtml' : 'restricthtml';
					}
					$value_key = GETPOST("options_".$key, $label_security_check);
				} else {
					$value_key = GETPOST("options_".$key);
					if (in_array($key_type, array('link')) && $value_key == '-1') {
						$value_key = '';
					}
				}

				if (!empty($error_field_required[$key]) && $todefaultifmissing) {
					// Value is required but we have a default value and we asked to set empty value to the default value
					if (!empty($this->attributes[$object->table_element]['default']) && !is_null($this->attributes[$object->table_element]['default'][$key])) {
						$value_key = $this->attributes[$object->table_element]['default'][$key];
						unset($error_field_required[$key]);
						$nofillrequired--;
					}
				}

					$object->array_options["options_".$key] = $value_key;
			}

			if ($nofillrequired) {
				$langs->load('errors');
				$this->error = $langs->trans('ErrorFieldsRequired').' : '.implode(', ', $error_field_required);
				setEventMessages($this->error, null, 'errors');
				return -1;
			} else {
				return 1;
			}
		} else {
			return 0;
		}
	}

	/**
	 * return array_options array of data of extrafields value of object sent by a search form
	 *
	 * @param  array|string		$extrafieldsobjectkey  	array of extrafields (old usage) or value of object->table_element (new usage)
	 * @param  string			$keysuffix      		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string			$keyprefix      		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return array|int								array_options set or 0 if no value
	 */
	public function getOptionalsFromPost($extrafieldsobjectkey, $keysuffix = '', $keyprefix = '')
	{
		global $_POST;

		if (is_string($extrafieldsobjectkey) && !empty($this->attributes[$extrafieldsobjectkey]['label']) && is_array($this->attributes[$extrafieldsobjectkey]['label'])) {
			$extralabels = $this->attributes[$extrafieldsobjectkey]['label'];
		} else {
			$extralabels = $extrafieldsobjectkey;
		}

		if (is_array($extralabels)) {
			$array_options = array();

			// Get extra fields
			foreach ($extralabels as $key => $value) {
				$key_type = '';
				if (is_string($extrafieldsobjectkey)) {
					$key_type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
				}

				if (in_array($key_type, array('date'))) {
					$dateparamname_start = $keyprefix . 'options_' . $key . $keysuffix . '_start';
					$dateparamname_end   = $keyprefix . 'options_' . $key . $keysuffix . '_end';

					if (GETPOST($dateparamname_start . 'year') || GETPOST($dateparamname_end . 'year')) {
						$value_key = array();
						// values provided as a component year, month, day, etc.
						if (GETPOST($dateparamname_start . 'year')) {
							$value_key['start'] = dol_mktime(0, 0, 0, GETPOSTINT($dateparamname_start . 'month'), GETPOSTINT($dateparamname_start . 'day'), GETPOSTINT($dateparamname_start . 'year'));
						}
						if (GETPOST($dateparamname_end . 'year')) {
							$value_key['end'] = dol_mktime(23, 59, 59, GETPOSTINT($dateparamname_end . 'month'), GETPOSTINT($dateparamname_end . 'day'), GETPOSTINT($dateparamname_end . 'year'));
						}
					} elseif (GETPOST($keyprefix."options_".$key.$keysuffix."year")) {
						// Clean parameters
						$value_key = dol_mktime(12, 0, 0, GETPOSTINT($keyprefix."options_".$key.$keysuffix."month"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."day"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."year"));
					} else {
						continue; // Value was not provided, we should not set it.
					}
				} elseif (in_array($key_type, array('datetime', 'datetimegmt'))) {
					$dateparamname_start = $keyprefix . 'options_' . $key . $keysuffix . '_start';
					$dateparamname_end   = $keyprefix . 'options_' . $key . $keysuffix . '_end';

					if (GETPOST($dateparamname_start . 'year') || GETPOST($dateparamname_end . 'year')) {
						// values provided as a date pair (start date + end date), each date being broken down as year, month, day, etc.
						$dateparamname_start_hour = GETPOSTINT($dateparamname_start . 'hour') != '-1' ? GETPOSTINT($dateparamname_start . 'hour') : '00';
						$dateparamname_start_min = GETPOSTINT($dateparamname_start . 'min') != '-1' ? GETPOSTINT($dateparamname_start . 'min') : '00';
						$dateparamname_start_sec = GETPOSTINT($dateparamname_start . 'sec') != '-1' ? GETPOSTINT($dateparamname_start . 'sec') : '00';
						$dateparamname_end_hour = GETPOSTINT($dateparamname_end . 'hour') != '-1' ? GETPOSTINT($dateparamname_end . 'hour') : '23';
						$dateparamname_end_min = GETPOSTINT($dateparamname_end . 'min') != '-1' ? GETPOSTINT($dateparamname_end . 'min') : '59';
						$dateparamname_end_sec = GETPOSTINT($dateparamname_end . 'sec') != '-1' ? GETPOSTINT($dateparamname_end . 'sec') : '59';
						if ($key_type == 'datetimegmt') {
							$value_key = array(
								'start' => dol_mktime($dateparamname_start_hour, $dateparamname_start_min, $dateparamname_start_sec, GETPOSTINT($dateparamname_start . 'month'), GETPOSTINT($dateparamname_start . 'day'), GETPOSTINT($dateparamname_start . 'year'), 'gmt'),
								'end' => dol_mktime($dateparamname_end_hour, $dateparamname_end_min, $dateparamname_end_sec, GETPOSTINT($dateparamname_end . 'month'), GETPOSTINT($dateparamname_end . 'day'), GETPOSTINT($dateparamname_end . 'year'), 'gmt')
							);
						} else {
							$value_key = array(
								'start' => dol_mktime($dateparamname_start_hour, $dateparamname_start_min, $dateparamname_start_sec, GETPOSTINT($dateparamname_start . 'month'), GETPOSTINT($dateparamname_start . 'day'), GETPOSTINT($dateparamname_start . 'year'), 'tzuserrel'),
								'end' => dol_mktime($dateparamname_end_hour, $dateparamname_end_min, $dateparamname_end_sec, GETPOSTINT($dateparamname_end . 'month'), GETPOSTINT($dateparamname_end . 'day'), GETPOSTINT($dateparamname_end . 'year'), 'tzuserrel')
							);
						}
					} elseif (GETPOST($keyprefix."options_".$key.$keysuffix."year")) {
						// Clean parameters
						if ($key_type == 'datetimegmt') {
							$value_key = dol_mktime(GETPOSTINT($keyprefix."options_".$key.$keysuffix."hour"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."min"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."sec"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."month"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."day"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."year"), 'gmt');
						} else {
							$value_key = dol_mktime(GETPOSTINT($keyprefix."options_".$key.$keysuffix."hour"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."min"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."sec"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."month"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."day"), GETPOSTINT($keyprefix."options_".$key.$keysuffix."year"), 'tzuserrel');
						}
					} else {
						continue; // Value was not provided, we should not set it.
					}
				} elseif ($key_type == 'select') {
					// to detect if we are in search context
					if (GETPOSTISARRAY($keyprefix."options_".$key.$keysuffix)) {
						$value_arr = GETPOST($keyprefix."options_".$key.$keysuffix, 'array:aZ09');
						// Make sure we get an array even if there's only one selected
						$value_arr = (array) $value_arr;
						$value_key = implode(',', $value_arr);
					} else {
						$value_key = GETPOST($keyprefix."options_".$key.$keysuffix);
					}
				} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
					// We test on a hidden field named "..._multiselect" that is always set to 1 if param is in form so
					// when nothing is provided we can make a difference between noparam in the form and param was set to nothing.
					if (!GETPOSTISSET($keyprefix."options_".$key.$keysuffix.'_multiselect')) {
						continue; // Value was not provided, we should not set it.
					}
					$value_arr = GETPOST($keyprefix."options_".$key.$keysuffix);
					// Make sure we get an array even if there's only one checkbox
					$value_arr = (array) $value_arr;
					$value_key = implode(',', $value_arr);
				} elseif (in_array($key_type, array('price', 'double', 'int'))) {
					if (!GETPOSTISSET($keyprefix."options_".$key.$keysuffix)) {
						continue; // Value was not provided, we should not set it.
					}
					$value_arr = GETPOST($keyprefix."options_".$key.$keysuffix);
					if ($keyprefix != 'search_') {    // If value is for a search, we must keep complex string like '>100 <=150'
						$value_key = price2num($value_arr);
					} else {
						$value_key = $value_arr;
					}
				} elseif (in_array($key_type, array('boolean'))) {
					// We test on a hidden field named "..._boolean" that is always set to 1 if param is in form so
					// when nothing is provided we can make a difference between noparam in the form and param was set to nothing.
					if (!GETPOSTISSET($keyprefix."options_".$key.$keysuffix."_boolean")) {
						$value_key = '';
					} else {
						$value_arr = GETPOST($keyprefix."options_".$key.$keysuffix);
						$value_key = $value_arr;
					}
				} elseif (in_array($key_type, array('html'))) {
					if (!GETPOSTISSET($keyprefix."options_".$key.$keysuffix)) {
						continue; // Value was not provided, we should not set it.
					}
					$value_key = dol_htmlcleanlastbr(GETPOST($keyprefix."options_".$key.$keysuffix, 'restricthtml'));
				} else {
					if (!GETPOSTISSET($keyprefix."options_".$key.$keysuffix)) {
						continue; // Value was not provided, we should not set it.
					}
					$value_key = GETPOST($keyprefix."options_".$key.$keysuffix);
				}

				$array_options[$keyprefix."options_".$key] = $value_key; // No keyprefix here. keyprefix is used only for read.
			}

			return $array_options;
		}

		return 0;
	}

	/**
	 * Return array with all possible types and labels of extrafields
	 *
	 * @return string[]
	 */
	public static function getListOfTypesLabels()
	{
		global $langs;

		$arraytype2label = array('');

		$tmptype2label = ExtraFields::$type2label;
		foreach ($tmptype2label as $key => $val) {
			$arraytype2label[$key] = $langs->transnoentitiesnoconv($val);
		}

		if (!getDolGlobalString('MAIN_USE_EXTRAFIELDS_ICON')) {
			unset($arraytype2label['icon']);
		}
		if (!getDolGlobalString('MAIN_USE_GEOPHP')) {
			unset($arraytype2label['point']);
			unset($arraytype2label['multipts']);
			unset($arraytype2label['linestrg']);
			unset($arraytype2label['polygon']);
		}

		return $arraytype2label;
	}

	/**
	 * Return if a value is "empty" for a mandatory vision.
	 *
	 * @param 	mixed	$v		Value to test
	 * @param 	string 	$type	Type of extrafield 'sellist', 'link', 'select', ...
	 * @return 	boolean			True is value is an empty value, not allowed for a mandatory field.
	 */
	public static function isEmptyValue($v, string $type)
	{
		if ($v === null || $v === '') {
			return true;
		}
		if (is_array($v) || $type == 'select') {
			return empty($v);
		}
		if ($type == 'link') {
			return ($v == '-1');
		}
		if ($type == 'sellist') {
			return ($v == '0');
		}
		return (empty($v) && $v != '0');
	}
}
