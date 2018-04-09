<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Florian Henry           <forian.henry@open-concept.pro>
 * Copyright (C) 2015       Charles-Fr BENKE        <charles.fr@benke.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
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
 * 	\file 		htdocs/core/class/extrafields.class.php
 *	\ingroup    core
 *	\brief      File of class to manage extra fields
 */


/**
 *	Class to manage standard extra fields
 */
class ExtraFields
{
	var $db;

	// type of element (for what object is the extrafield)
	var $attribute_elementtype;

	// Array with type of the extra field
	var $attribute_type;
	// Array with label of extra field
	var $attribute_label;
	// Array with size of extra field
	var $attribute_size;
	// array with list of possible values for some types of extra fields
	var $attribute_choice;
	// Array to store compute formula for computed fields
	var $attribute_computed;
	// Array to store default value
	var $attribute_default;
	// Array to store if attribute is unique or not
	var $attribute_unique;
	// Array to store if attribute is required or not
	var $attribute_required;
	// Array to store parameters of attribute (used in select type)
	var $attribute_param;
	// Array to store position of attribute
	var $attribute_pos;
	// Array to store if attribute is editable regardless of the document status
	var $attribute_alwayseditable;
	// Array to store permission to check
	var $attribute_perms;
	// Array to store language file to translate label of values
	var $attribute_langfile;
	// Array to store if field is visible by default on list
	var $attribute_list;
	// Array to store if extra field is hidden
	var $attribute_hidden;		// warning, do not rely on this. If your module need a hidden data, it must use its own table.

	// New array to store extrafields definition
	var $attributes;

	var $error;
	var $errno;


	public static $type2label=array(
	'varchar'=>'String',
	'text'=>'TextLong',
	'html'=>'HtmlText',
	'int'=>'Int',
	'double'=>'Float',
	'date'=>'Date',
	'datetime'=>'DateAndTime',
	'boolean'=>'Boolean',
	'price'=>'ExtrafieldPrice',
	'phone'=>'ExtrafieldPhone',
	'mail'=>'ExtrafieldMail',
	'url'=>'ExtrafieldUrl',
	'password' => 'ExtrafieldPassword',
	'select' => 'ExtrafieldSelect',
	'sellist' => 'ExtrafieldSelectList',
	'radio' => 'ExtrafieldRadio',
	'checkbox' => 'ExtrafieldCheckBox',
	'chkbxlst' => 'ExtrafieldCheckBoxFromList',
	'link' => 'ExtrafieldLink',
	'separate' => 'ExtrafieldSeparator',
	);


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	*/
	function __construct($db)
	{
		$this->db = $db;
		$this->error = array();
		$this->attributes = array();

		// For old usage
		$this->attribute_elementtype = array();
		$this->attribute_type = array();
		$this->attribute_label = array();
		$this->attribute_size = array();
		$this->attribute_computed = array();
		$this->attribute_default = array();
		$this->attribute_unique = array();
		$this->attribute_required = array();
		$this->attribute_perms = array();
		$this->attribute_langfile = array();
		$this->attribute_list = array();
		$this->attribute_hidden = array();
	}

	/**
	 *  Add a new extra field parameter
	 *
	 *  @param	string			$attrname           Code of attribute
	 *  @param  string			$label              label of attribute
	 *  @param  int				$type               Type of attribute ('boolean','int','varchar','text','html','date','datehour','price','phone','mail','password','url','select','checkbox','separate',...)
	 *  @param  int				$pos                Position of attribute
	 *  @param  string			$size               Size/length of attribute
	 *  @param  string			$elementtype        Element type ('member', 'product', 'thirdparty', ...)
	 *  @param	int				$unique				Is field unique or not
	 *  @param	int				$required			Is field required or not
	 *  @param	string			$default_value		Defaulted value (In database. use the default_value feature for default value on screen. Example: '', '0', 'null', 'avalue')
	 *  @param  array|string	$param				Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int				$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param	string			$perms				Permission to check
	 *  @param	int				$list				Visibilty (0=never visible, 1=visible on list+forms, 2=list onyl, 3=form only)
	 *  @param	int				$notused			Deprecated.
	 *  @param  string  		$computed           Computed value
	 *  @param  string  		$entity    		 	Entity of extrafields (for multicompany modules)
	 *  @param  string  		$langfile  		 	Language file
	 *  @param  string  		$enabled  		 	Condition to have the field enabled or not
	 *  @return int      							<=0 if KO, >0 if OK
	 */
	function addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique=0, $required=0, $default_value='', $param='', $alwayseditable=0, $perms='', $list=-1, $notused=0, $computed='', $entity='', $langfile='', $enabled='1')
	{
		if (empty($attrname)) return -1;
		if (empty($label)) return -1;

		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		// Create field into database except for separator type which is not stored in database
		if ($type != 'separate')
		{
			$result=$this->create($attrname, $type, $size, $elementtype, $unique, $required, $default_value, $param, $perms, $list, $computed);
		}
		$err1=$this->errno;
		if ($result > 0 || $err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' || $type == 'separate')
		{
			// Add declaration of field into table
			$result2=$this->create_label($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $param, $alwayseditable, $perms, $list, $notused, $default_value, $computed, $entity, $langfile, $enabled);
			$err2=$this->errno;
			if ($result2 > 0 || ($err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' && $err2 == 'DB_ERROR_RECORD_ALREADY_EXISTS'))
			{
				$this->error='';
				$this->errno=0;
				return 1;
			}
			else return -2;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	Add a new optional attribute.
	 *  This is a private method. For public method, use addExtraField.
	 *
	 *	@param	string	$attrname			code of attribute
	 *  @param	int		$type				Type of attribute ('boolean', 'int', 'varchar', 'text', 'html', 'date', 'datehour','price','phone','mail','password','url','select','checkbox', ...)
	 *  @param	string	$length				Size/length of attribute ('5', '24,8', ...)
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param  string  $default_value		Default value for field (in database)
	 *  @param  array	$param				Params for field  (ex for select list : array('options'=>array('value'=>'label of option'))
	 *  @param	string	$perms				Permission
	 *	@param	int		$list				Into list view by default
	 *  @param  string  $computed           Computed value
	 *  @return int      	           		<=0 if KO, >0 if OK
	 */
	private function create($attrname, $type='varchar', $length=255, $elementtype='member', $unique=0, $required=0, $default_value='',$param='', $perms='', $list=0, $computed='')
	{
		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		$table=$elementtype.'_extrafields';
		if ($elementtype == 'categorie') $table='categories_extrafields';

		if (! empty($attrname) && preg_match("/^\w[a-zA-Z0-9_]*$/",$attrname) && ! is_numeric($attrname))
		{
			if ($type=='boolean') {
				$typedb='int';
				$lengthdb='1';
			} elseif($type=='price') {
				$typedb='double';
				$lengthdb='24,8';
			} elseif($type=='phone') {
				$typedb='varchar';
				$lengthdb='20';
			} elseif($type=='mail') {
				$typedb='varchar';
				$lengthdb='128';
			} elseif($type=='url') {
				$typedb='varchar';
				$lengthdb='255';
			} elseif (($type=='select') || ($type=='sellist') || ($type=='radio') ||($type=='checkbox') ||($type=='chkbxlst')){
				$typedb='text';
				$lengthdb='';
			} elseif ($type=='link') {
				$typedb='int';
				$lengthdb='11';
			} elseif ($type=='html') {
				$typedb='text';
				$lengthdb=$length;
			} elseif($type=='password') {
				$typedb='varchar';
				$lengthdb='128';
			} else {
				$typedb=$type;
				$lengthdb=$length;
				if ($type == 'varchar' && empty($lengthdb)) $lengthdb='255';
			}
			$field_desc = array(
			'type'=>$typedb,
			'value'=>$lengthdb,
			'null'=>($required?'NOT NULL':'NULL'),
			'default' => $default_value
			);

			$result=$this->db->DDLAddField(MAIN_DB_PREFIX.$table, $attrname, $field_desc);
			if ($result > 0)
			{
				if ($unique)
				{
					$sql="ALTER TABLE ".MAIN_DB_PREFIX.$table." ADD UNIQUE INDEX uk_".$table."_".$attrname." (".$attrname.")";
					$resql=$this->db->query($sql,1,'dml');
				}
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->errno=$this->db->lasterrno();
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}

	/**
	 *	Add description of a new optional attribute
	 *
	 *	@param	string			$attrname		code of attribute
	 *	@param	string			$label			label of attribute
	 *  @param	int				$type			Type of attribute ('int', 'varchar', 'text', 'html', 'date', 'datehour', 'float')
	 *  @param	int				$pos			Position of attribute
	 *  @param	string			$size			Size/length of attribute ('5', '24,8', ...)
	 *  @param  string			$elementtype	Element type ('member', 'product', 'thirdparty', ...)
	 *  @param	int				$unique			Is field unique or not
	 *  @param	int				$required		Is field required or not
	 *  @param  array|string	$param			Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int				$alwayseditable	Is attribute always editable regardless of the document status
	 *  @param	string			$perms			Permission to check
	 *  @param	int				$list			Visibily
	 *  @param	int				$notused		Deprecated.
	 *  @param  string          $default        Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string          $computed       Computed value
	 *  @param  string          $entity     	Entity of extrafields
	 *  @param	string			$langfile		Language file
	 *  @param  string  		$enabled  		Condition to have the field enabled or not
	 *  @return	int								<=0 if KO, >0 if OK
	 */
	private function create_label($attrname, $label='', $type='', $pos=0, $size=0, $elementtype='member', $unique=0, $required=0, $param='', $alwayseditable=0, $perms='', $list=-1, $notused=0, $default='', $computed='',$entity='', $langfile='', $enabled='1')
	{
		global $conf,$user;

		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		// Clean parameters
		if (empty($pos)) $pos=0;
		if (empty($list)) $list=0;
		if (empty($required)) $required=0;
		if (empty($unique)) $unique=0;
		if (empty($alwayseditable)) $alwayseditable=0;

		if (! empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname) && ! is_numeric($attrname))
		{
			if (is_array($param) && count($param) > 0)
			{
				$params = serialize($param);
			}
			elseif (strlen($param) > 0)
			{
				$params = trim($param);
			}
			else
			{
				$params='';
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."extrafields(";
			$sql.= " name,";
			$sql.= " label,";
			$sql.= " type,";
			$sql.= " pos,";
			$sql.= " size,";
			$sql.= " entity,";
			$sql.= " elementtype,";
			$sql.= " fieldunique,";
			$sql.= " fieldrequired,";
			$sql.= " param,";
			$sql.= " alwayseditable,";
			$sql.= " perms,";
			$sql.= " langs,";
			$sql.= " list,";
			$sql.= " fielddefault,";
			$sql.= " fieldcomputed,";
			$sql.= " fk_user_author,";
			$sql.= " fk_user_modif,";
			$sql.= " datec,";
			$sql.= " enabled";
			$sql.= " )";
			$sql.= " VALUES('".$attrname."',";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$this->db->escape($type)."',";
			$sql.= " ".$pos.",";
			$sql.= " '".$this->db->escape($size)."',";
			$sql.= " ".($entity===''?$conf->entity:$entity).",";
			$sql.= " '".$this->db->escape($elementtype)."',";
			$sql.= " ".$unique.",";
			$sql.= " ".$required.",";
			$sql.= " '".$this->db->escape($params)."',";
			$sql.= " ".$alwayseditable.",";
			$sql.= " ".($perms?"'".$this->db->escape($perms)."'":"null").",";
			$sql.= " ".($langfile?"'".$this->db->escape($langfile)."'":"null").",";
			$sql.= " ".$list.",";
			$sql.= " ".($default?"'".$this->db->escape($default)."'":"null").",";
			$sql.= " ".($computed?"'".$this->db->escape($computed)."'":"null").",";
			$sql .= " " . $user->id . ",";
			$sql .= " " . $user->id . ",";
			$sql .= "'" . $this->db->idate(dol_now()) . "',";
			$sql.= " ".($enabled?"'".$this->db->escape($enabled)."'":"1");
			$sql.=')';

			dol_syslog(get_class($this)."::create_label", LOG_DEBUG);
			if ($this->db->query($sql))
			{
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->errno=$this->db->lasterrno();
				return -1;
			}
		}
	}

	/**
	 *	Delete an optional attribute
	 *
	 *	@param	string	$attrname		Code of attribute to delete
	 *  @param  string	$elementtype    Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @return int              		< 0 if KO, 0 if nothing is done, 1 if OK
	 */
	function delete($attrname, $elementtype='member')
	{
		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		$table=$elementtype.'_extrafields';
		if ($elementtype == 'categorie') $table='categories_extrafields';

		$error=0;

		if (! empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$result=$this->delete_label($attrname,$elementtype);
			if ($result < 0)
			{
			    $this->error=$this->db->lasterror();
			    $error++;
			}

			if (! $error)
			{
        		$sql = "SELECT COUNT(rowid) as nb";
        		$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
        		$sql.= " WHERE elementtype = '".$elementtype."'";
        		$sql.= " AND name = '".$attrname."'";
        		//$sql.= " AND entity IN (0,".$conf->entity.")";      Do not test on entity here. We want to see if there is still on field remaning in other entities before deleting field in table
                $resql = $this->db->query($sql);
                if ($resql)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($obj->nb <= 0)
                    {
            			$result=$this->db->DDLDropField(MAIN_DB_PREFIX.$table,$attrname);	// This also drop the unique key
            			if ($result < 0)
            			{
            				$this->error=$this->db->lasterror();
            				$error++;
            			}
                    }
                }
			}

			return $result;
		}
		else
		{
			return 0;
		}

	}

	/**
	 *	Delete description of an optional attribute
	 *
	 *	@param	string	$attrname			Code of attribute to delete
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', ...)
	 *  @return int              			< 0 if KO, 0 if nothing is done, 1 if OK
	 */
	private function delete_label($attrname, $elementtype='member')
	{
		global $conf;

		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql.= " WHERE name = '".$attrname."'";
			$sql.= " AND entity IN  (0,".$conf->entity.')';
			$sql.= " AND elementtype = '".$elementtype."'";

			dol_syslog(get_class($this)."::delete_label", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				return 1;
			}
			else
			{
				print dol_print_error($this->db);
				return -1;
			}
		}
		else
		{
			return 0;
		}

	}

	/**
	 * 	Modify type of a personalized attribute
	 *
	 *  @param	string	$attrname			Name of attribute
	 *  @param	string	$label				Label of attribute
	 *  @param	string	$type				Type of attribute ('boolean', 'int', 'varchar', 'text', 'html', 'date', 'datehour','price','phone','mail','password','url','select','checkbox', ...)
	 *  @param	int		$length				Length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'thirdparty', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	int		$pos				Position of attribute
	 *  @param  array	$param				Params for field (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @param  int		$alwayseditable		Is attribute always editable regardless of the document status
	 *  @param	string	$perms				Permission to check
	 *  @param	int		$list				Visibility
	 *  @param	int		$notused			Deprecated.
	 *  @param  string  $default            Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string  $computed           Computed value
	 *  @param  string  $entity	            Entity of extrafields
	 *  @param	string	$langfile			Language file
	 *  @param  string  $enabled  			Condition to have the field enabled or not
	 * 	@return	int							>0 if OK, <=0 if KO
	 */
	function update($attrname, $label, $type, $length, $elementtype, $unique=0, $required=0, $pos=0, $param='', $alwayseditable=0, $perms='', $list='', $notused=0, $default='', $computed='', $entity='', $langfile='', $enabled='1')
	{
		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		$table=$elementtype.'_extrafields';
		if ($elementtype == 'categorie') $table='categories_extrafields';

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			if ($type=='boolean') {
				$typedb='int';
				$lengthdb='1';
			} elseif($type=='price') {
				$typedb='double';
				$lengthdb='24,8';
			} elseif($type=='phone') {
				$typedb='varchar';
				$lengthdb='20';
			} elseif($type=='mail') {
				$typedb='varchar';
				$lengthdb='128';
			} elseif($type=='url') {
				$typedb='varchar';
				$lengthdb='255';
			} elseif (($type=='select') || ($type=='sellist') || ($type=='radio') || ($type=='checkbox') || ($type=='chkbxlst')) {
				$typedb='text';
				$lengthdb='';
			} elseif ($type == 'html') {
				$typedb='text';
			} elseif ($type=='link') {
				$typedb='int';
				$lengthdb='11';
			} elseif($type=='password') {
				$typedb='varchar';
				$lengthdb='50';
			} else {
				$typedb=$type;
				$lengthdb=$length;
			}
			$field_desc = array('type'=>$typedb, 'value'=>$lengthdb, 'null'=>($required?'NOT NULL':'NULL'), 'default'=>$default);

			if ($type != 'separate') // No table update when separate type
			{
				$result=$this->db->DDLUpdateField(MAIN_DB_PREFIX.$table, $attrname, $field_desc);
			}
			if ($result > 0 || $type == 'separate')
			{
				if ($label)
				{
					$result=$this->update_label($attrname,$label,$type,$length,$elementtype,$unique,$required,$pos,$param,$alwayseditable,$perms,$list,$notused,$default,$computed,$entity,$langfile,$enabled);
				}
				if ($result > 0)
				{
					$sql='';
					if ($unique)
					{
						$sql="ALTER TABLE ".MAIN_DB_PREFIX.$table." ADD UNIQUE INDEX uk_".$table."_".$attrname." (".$attrname.")";
					}
					else
					{
						$sql="ALTER TABLE ".MAIN_DB_PREFIX.$table." DROP INDEX uk_".$table."_".$attrname;
					}
					dol_syslog(get_class($this).'::update', LOG_DEBUG);
					$resql=$this->db->query($sql,1,'dml');
					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					return -1;
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -1;
			}
		}
		else
		{
			return 0;
		}

	}

	/**
	 *  Modify description of personalized attribute
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
	 *  @param	int		$list				Visiblity
	 *  @param	int		$notused			Deprecated.
	 *  @param  string  $default            Default value (in database. use the default_value feature for default value on screen).
	 *  @param  string  $computed           Computed value
	 *  @param  string  $entity     		Entity of extrafields
	 *  @param	string	$langfile			Language file
	 *  @param  string  $enabled  			Condition to have the field enabled or not
	 *  @return	int							<=0 if KO, >0 if OK
	 */
	private function update_label($attrname,$label,$type,$size,$elementtype,$unique=0,$required=0,$pos=0,$param='',$alwayseditable=0,$perms='',$list=0,$notused=0,$default='',$computed='',$entity='',$langfile='',$enabled='1')
	{
		global $conf, $user;
		dol_syslog(get_class($this)."::update_label ".$attrname.", ".$label.", ".$type.", ".$size.", ".$elementtype.", ".$unique.", ".$required.", ".$pos.", ".$alwayseditable.", ".$perms.", ".$list.", ".$notused.", ".$default.", ".$computed.", ".$entity.", ".$langfile.", ".$enabled);

		// Clean parameters
		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		if (empty($pos)) $pos=0;
		if (empty($list)) $list=0;
		if (empty($required)) $required=0;
		if (empty($unique)) $unique=0;
		if (empty($alwayseditable)) $alwayseditable=0;

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$this->db->begin();

			if (is_array($param) && count($param) > 0)
			{
				$params = serialize($param);
			}
			elseif (strlen($param) > 0)
			{
				$params = trim($param);
			}
			else
			{
				$params='';
			}

			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql_del.= " WHERE name = '".$attrname."'";
			$sql_del.= " AND entity = ".($entity===''?$conf->entity:$entity);
			$sql_del.= " AND elementtype = '".$elementtype."'";

			$resql1=$this->db->query($sql_del);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."extrafields(";
			$sql.= " name,";		// This is code
			$sql.= " entity,";
			$sql.= " label,";
			$sql.= " type,";
			$sql.= " size,";
			$sql.= " elementtype,";
			$sql.= " fieldunique,";
			$sql.= " fieldrequired,";
			$sql.= " perms,";
			$sql.= " langs,";
			$sql.= " pos,";
			$sql.= " alwayseditable,";
			$sql.= " param,";
			$sql.= " list,";
			$sql.= " fielddefault,";
			$sql.= " fieldcomputed,";
			$sql.= " fk_user_author,";
			$sql.= " fk_user_modif,";
			$sql.= " datec,";
			$sql.= " enabled";
			$sql.= ") VALUES (";
			$sql.= "'".$attrname."',";
			$sql.= " ".($entity===''?$conf->entity:$entity).",";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$this->db->escape($type)."',";
			$sql.= " '".$this->db->escape($size)."',";
			$sql.= " '".$this->db->escape($elementtype)."',";
			$sql.= " ".$unique.",";
			$sql.= " ".$required.",";
			$sql.= " ".($perms?"'".$this->db->escape($perms)."'":"null").",";
			$sql.= " ".($langfile?"'".$this->db->escape($langfile)."'":"null").",";
			$sql.= " ".$pos.",";
			$sql.= " '".$this->db->escape($alwayseditable)."',";
			$sql.= " '".$this->db->escape($params)."',";
			$sql.= " ".$list.", ";
			$sql.= " ".(($default!='')?"'".$this->db->escape($default)."'":"null").",";
			$sql.= " ".($computed?"'".$this->db->escape($computed)."'":"null").",";
			$sql .= " " . $user->id . ",";
			$sql .= " " . $user->id . ",";
			$sql .= "'" . $this->db->idate(dol_now()) . "',";
			$sql .= "'" . $this->db->escape($enabled). "'";
			$sql.= ")";

			$resql2=$this->db->query($sql);

			if ($resql1 && $resql2)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				print dol_print_error($this->db);
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}


	/**
	 * 	Load array this->attributes, or old this->attribute_xxx like attribute_label, attribute_type, ...
	 *
	 * 	@param	string		$elementtype		Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...).
	 * 	@param	boolean		$forceload			Force load of extra fields whatever is option MAIN_EXTRAFIELDS_DISABLED. Deprecated. Should not be required.
	 * 	@return	array							Array of attributes keys+label for all extra fields.
	 */
	function fetch_name_optionals_label($elementtype,$forceload=false)
	{
		global $conf;

		if (empty($elementtype) ) return array();

		if ($elementtype == 'thirdparty') $elementtype='societe';
		if ($elementtype == 'contact') $elementtype='socpeople';

		$array_name_label=array();

		// To avoid conflicts with external modules. TODO Remove this.
		if (!$forceload && !empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) return $array_name_label;

		// Set array of label of entity
		// TODO Remove completely loading of label. This should be done by presentation.
		$labelmulticompany=array();
		if (!empty($conf->multicompany->enabled))
		{
			$sql_entity_name='SELECT rowid, label FROM '.MAIN_DB_PREFIX.'entity WHERE rowid in (0,'.$conf->entity.')';
			$resql_entity_name=$this->db->query($sql_entity_name);
			if ($resql_entity_name)
			{
				while ($obj = $this->db->fetch_object($resql_entity_name))
				{
					$labelmulticompany[$obj->rowid]=$obj->label;
				}
			}
		}

		// We should not have several time this log. If we have, there is some optimization to do by calling a simple $object->fetch_optionals() that include cache management.
		dol_syslog("fetch_name_optionals_label elementtype=".$elementtype);

		$sql = "SELECT rowid,name,label,type,size,elementtype,fieldunique,fieldrequired,param,pos,alwayseditable,perms,langs,list,fielddefault,fieldcomputed,entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
		$sql.= " WHERE entity IN (0,".$conf->entity.")";
		if ($elementtype) $sql.= " AND elementtype = '".$elementtype."'";
		$sql.= " ORDER BY pos";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				while ($tab = $this->db->fetch_object($resql))
				{
					// We can add this attribute to object. TODO Remove this and return $this->attributes[$elementtype]['label']
					if ($tab->type != 'separate')
					{
						$array_name_label[$tab->name]=$tab->label;
					}

					// Old usage
					$this->attribute_type[$tab->name]=$tab->type;
					$this->attribute_label[$tab->name]=$tab->label;
					$this->attribute_size[$tab->name]=$tab->size;
					$this->attribute_elementtype[$tab->name]=$tab->elementtype;
					$this->attribute_default[$tab->name]=$tab->fielddefault;
					$this->attribute_computed[$tab->name]=$tab->fieldcomputed;
					$this->attribute_unique[$tab->name]=$tab->fieldunique;
					$this->attribute_required[$tab->name]=$tab->fieldrequired;
					$this->attribute_param[$tab->name]=($tab->param ? unserialize($tab->param) : '');
					$this->attribute_pos[$tab->name]=$tab->pos;
					$this->attribute_alwayseditable[$tab->name]=$tab->alwayseditable;
					$this->attribute_perms[$tab->name]=(strlen($tab->perms) == 0 ? 1 : $tab->perms);
					$this->attribute_langfile[$tab->name]=$tab->langs;
					$this->attribute_list[$tab->name]=$tab->list;
					$this->attribute_entityid[$tab->name]=$tab->entity;
					$this->attribute_entitylabel[$tab->name]=(empty($labelmulticompany[$tab->entity])?'Entity'.$tab->entity:$labelmulticompany[$tab->entity]);

					// New usage
					$this->attributes[$tab->elementtype]['type'][$tab->name]=$tab->type;
					$this->attributes[$tab->elementtype]['label'][$tab->name]=$tab->label;
					$this->attributes[$tab->elementtype]['size'][$tab->name]=$tab->size;
					$this->attributes[$tab->elementtype]['elementtype'][$tab->name]=$tab->elementtype;
					$this->attributes[$tab->elementtype]['default'][$tab->name]=$tab->fielddefault;
					$this->attributes[$tab->elementtype]['computed'][$tab->name]=$tab->fieldcomputed;
					$this->attributes[$tab->elementtype]['unique'][$tab->name]=$tab->fieldunique;
					$this->attributes[$tab->elementtype]['required'][$tab->name]=$tab->fieldrequired;
					$this->attributes[$tab->elementtype]['param'][$tab->name]=($tab->param ? unserialize($tab->param) : '');
					$this->attributes[$tab->elementtype]['pos'][$tab->name]=$tab->pos;
					$this->attributes[$tab->elementtype]['alwayseditable'][$tab->name]=$tab->alwayseditable;
					$this->attributes[$tab->elementtype]['perms'][$tab->name]=(strlen($tab->perms) == 0 ? 1 : $tab->perms);
					$this->attributes[$tab->elementtype]['langfile'][$tab->name]=$tab->langs;
					$this->attributes[$tab->elementtype]['list'][$tab->name]=$tab->list;
					$this->attributes[$tab->elementtype]['entityid'][$tab->name]=$tab->entity;
					$this->attributes[$tab->elementtype]['entitylabel'][$tab->name]=(empty($labelmulticompany[$tab->entity])?'Entity'.$tab->entity:$labelmulticompany[$tab->entity]);

					$this->attributes[$tab->elementtype]['loaded']=1;
				}
			}
			if ($elementtype) $this->attributes[$elementtype]['loaded']=1;	// If nothing found, we also save tag 'loaded'
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_name_optionals_label ".$this->error, LOG_ERR);
		}

		return $array_name_label;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of common object
	 *
	 * @param  string  $key            Key of attribute
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add after name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add before name and id of field (can be used to avoid duplicate names)
	 * @param  string  $morecss        More css (to defined size of field. Old behaviour: may also be a numeric)
	 * @param  int     $objectid       Current object id
	 * @return string
	 */
	function showInputField($key, $value, $moreparam='', $keysuffix='', $keyprefix='', $morecss='', $objectid=0)
	{
		global $conf,$langs,$form;

		if (! is_object($form))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form=new Form($this->db);
		}

		$out='';

		$keyprefix = $keyprefix.'options_';		// Because we work on extrafields

		$label=$this->attribute_label[$key];
		$type =$this->attribute_type[$key];
		$size =$this->attribute_size[$key];
		$elementtype=$this->attribute_elementtype[$key];	// Seems not used
		$default=$this->attribute_default[$key];
		$computed=$this->attribute_computed[$key];
		$unique=$this->attribute_unique[$key];
		$required=$this->attribute_required[$key];
		$param=$this->attribute_param[$key];
		$langfile=$this->attribute_langfile[$key];
		$list=$this->attribute_list[$key];
		$hidden=$this->attribute_hidden[$key];

		if ($computed)
		{
			if (! preg_match('/^search_/', $keyprefix)) return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			else return '';
		}

		if (empty($morecss))
		{
			if ($type == 'date')
			{
				$morecss = 'minwidth100imp';
			}
			elseif ($type == 'datetime')
			{
				$morecss = 'minwidth200imp';
			}
			elseif (in_array($type,array('int','integer','double','price')))
			{
				$morecss = 'maxwidth75';
			}
			elseif ($type == 'url')
			{
				$morecss='minwidth400';
			}
			elseif ($type == 'boolean')
			{
				$morecss='';
			}
			else
			{
				if (round($size) < 12)
				{
					$morecss = 'minwidth100';
				}
				else if (round($size) <= 48)
				{
					$morecss = 'minwidth200';
				}
				else
				{
					$morecss = 'minwidth400';
				}
			}
		}

		if (in_array($type,array('date','datetime')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];

			$showtime = in_array($type,array('datetime')) ? 1 : 0;

			// Do not show current date when field not required (see select_date() method)
			if (!$required && $value == '') $value = '-1';

			// TODO Must also support $moreparam
			$out = $form->select_date($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 1, 0, 1);
		}
		elseif (in_array($type,array('int','integer')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];
			$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$newsize.'" value="'.dol_escape_htmltag($value).'"'.($moreparam?$moreparam:'').'>';
		}
		elseif (preg_match('/varchar/', $type))
		{
			$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$size.'" value="'.dol_escape_htmltag($value).'"'.($moreparam?$moreparam:'').'>';
		}
		elseif (in_array($type, array('mail', 'phone', 'url')))
		{
			$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'text')
		{
			if (! preg_match('/search_/', $keyprefix))		// If keyprefix is search_ or search_options_, we must just use a simple text field
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor=new DolEditor($keyprefix.$key.$keysuffix,$value,'',200,'dolibarr_notes','In',false,false,false,ROWS_5,'90%');
				$out=$doleditor->Create(1);
			}
			else
			{
				$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam?$moreparam:'').'>';
			}
		}
		elseif ($type == 'html')
		{
			if (! preg_match('/search_/', $keyprefix))		// If keyprefix is search_ or search_options_, we must just use a simple text field
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor=new DolEditor($keyprefix.$key.$keysuffix,$value,'',200,'dolibarr_notes','In',false,false,! empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_5,'90%');
				$out=$doleditor->Create(1);
			}
			else
			{
				$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam?$moreparam:'').'>';
			}
		}
		elseif ($type == 'boolean')
		{
			$checked='';
			if (!empty($value)) {
				$checked=' checked value="1" ';
			} else {
				$checked=' value="1" ';
			}
			$out='<input type="checkbox" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'price')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value=price($value);
			}
			$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'> '.$langs->getCurrencySymbol($conf->currency);
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value=price($value);
			}
			$out='<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'> ';
		}
		elseif ($type == 'select')
		{
			$out = '';
			if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out.='<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'').'>';
			$out.='<option value="0">&nbsp;</option>';
			foreach ($param['options'] as $key => $val)
			{
				if ((string) $key == '') continue;
				list($val, $parent) = explode('|', $val);
				$out.='<option value="'.$key.'"';
				$out.= (((string) $value == (string) $key)?' selected':'');
				$out.= (!empty($parent)?' parent="'.$parent.'"':'');
				$out.='>'.$val.'</option>';
			}
			$out.='</select>';
		}
		elseif ($type == 'sellist')
		{
			$out = '';
			if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2))
			{
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out.='<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'').'>';
			if (is_array($param['options']))
			{
				$param_list=array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName='';
				$parentField='';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList=(empty($InfoFieldList[2])?'rowid':$InfoFieldList[2].' as rowid');


				if (count($InfoFieldList) > 4 && ! empty($InfoFieldList[4]))
				{
					if (strpos($InfoFieldList[4], 'extra.') !== false)
					{
						$keyList='main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList=$InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && ! empty($InfoFieldList[3]))
				{
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList.= ', '.$parentField;
				}

				$fields_label = explode('|',$InfoFieldList[1]);
				if (is_array($fields_label))
				{
					$keyList .=', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere='';
				$sql = 'SELECT '.$keyList;
				$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
				if (!empty($InfoFieldList[4]))
				{
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$')!==false) {
						$InfoFieldList[4]=str_replace('$SEL$','SELECT',$InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$')!==false && !empty($objectid)) {
						$InfoFieldList[4]=str_replace('$ID$',$objectid,$InfoFieldList[4]);
					} else {
						$InfoFieldList[4]=str_replace('$ID$','0',$InfoFieldList[4]);
					}
					//We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra')!==false)
					{
						$sql.= ' as main, '.MAIN_DB_PREFIX .$InfoFieldList[0].'_extrafields as extra';
						$sqlwhere.= ' WHERE extra.fk_object=main.'.$InfoFieldList[2]. ' AND '.$InfoFieldList[4];
					}
					else
					{
						$sqlwhere.= ' WHERE '.$InfoFieldList[4];
					}
				}
				else
				{
					$sqlwhere.= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0],array('tablewithentity')))
				{
					$sqlwhere.= ' AND entity = '.$conf->entity;
				}
				$sql.=$sqlwhere;
				//print $sql;

				$sql .= ' ORDER BY ' . implode(', ', $fields_label);

				dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$out.='<option value="0">&nbsp;</option>';
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num)
					{
						$labeltoshow='';
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$notrans = false;
						$fields_label = explode('|',$InfoFieldList[1]);
						if (is_array($fields_label))
						{
							$notrans = true;
							foreach ($fields_label as $field_toshow)
							{
								$labeltoshow.= $obj->$field_toshow.' ';
							}
						}
						else
						{
							$labeltoshow=$obj->{$InfoFieldList[1]};
						}
						$labeltoshow=dol_trunc($labeltoshow,45);

						if ($value == $obj->rowid)
						{
							foreach ($fields_label as $field_toshow)
							{
								$translabel=$langs->trans($obj->$field_toshow);
								if ($translabel!=$obj->$field_toshow) {
									$labeltoshow=dol_trunc($translabel,18).' ';
								}else {
									$labeltoshow=dol_trunc($obj->$field_toshow,18).' ';
								}
							}
							$out.='<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
						}
						else
						{
							if (! $notrans)
							{
								$translabel=$langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel!=$obj->{$InfoFieldList[1]}) {
									$labeltoshow=dol_trunc($translabel,18);
								}
								else {
									$labeltoshow=dol_trunc($obj->{$InfoFieldList[1]},18);
								}
							}
							if (empty($labeltoshow)) $labeltoshow='(not defined)';
							if ($value==$obj->rowid)
							{
								$out.='<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
							}

							if (!empty($InfoFieldList[3]) && $parentField)
							{
								$parent = $parentName.':'.$obj->{$parentField};
							}

							$out.='<option value="'.$obj->rowid.'"';
							$out.= ($value==$obj->rowid?' selected':'');
							$out.= (!empty($parent)?' parent="'.$parent.'"':'');
							$out.='>'.$labeltoshow.'</option>';
						}

						$i++;
					}
					$this->db->free($resql);
				}
				else {
					print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
				}
			}
			$out.='</select>';
		}
		elseif ($type == 'checkbox')
		{
			$value_arr=explode(',',$value);
			$out=$form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options'])?null:$param['options']), $value_arr, '', 0, '', 0, '100%');
		}
		elseif ($type == 'radio')
		{
			$out='';
			foreach ($param['options'] as $keyopt => $val)
			{
				$out.='<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam?$moreparam:'');
				$out.=' value="'.$keyopt.'"';
				$out.=' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out.= ($value==$keyopt?'checked':'');
				$out.='/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$val.'</label><br>';
			}
		}
		elseif ($type == 'chkbxlst')
		{
			if (is_array($value)) {
				$value_arr = $value;
			}
			else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName='';
				$parentField='';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 3 && ! empty($InfoFieldList[3])) {
					list ( $parentName, $parentField ) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}
				if (count($InfoFieldList) > 4 && ! empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT ' . $keyList;
				$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
				if (! empty($InfoFieldList[4])) {

					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$')!==false) {
						$InfoFieldList[4]=str_replace('$SEL$','SELECT',$InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$')!==false && !empty($objectid)) {
						$InfoFieldList[4]=str_replace('$ID$',$objectid,$InfoFieldList[4]);
					} else {
						$InfoFieldList[4]=str_replace('$ID$','0',$InfoFieldList[4]);
					}

					// We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList[0] . '_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.' . $InfoFieldList[2] . ' AND ' . $InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE ' . $InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array ('tablewithentity')))
				{
					$sqlwhere .= ' AND entity = ' . $conf->entity;
				}
				// $sql.=preg_replace('/^ AND /','',$sqlwhere);
				// print $sql;

				$sql .= $sqlwhere;
				dol_syslog(get_class($this) . '::showInputField type=chkbxlst',LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					$data=array();

					while ( $i < $num ) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						$notrans = false;
						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (is_array($fields_label)) {
							$notrans = true;
							foreach ( $fields_label as $field_toshow ) {
								$labeltoshow .= $obj->$field_toshow . ' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							foreach ( $fields_label as $field_toshow ) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel, 18) . ' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow, 18) . ' ';
								}
							}

							$data[$obj->rowid]=$labeltoshow;

						} else {
							if (! $notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
								}
							}
							if (empty($labeltoshow))
								$labeltoshow = '(not defined)';

								if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
									$data[$obj->rowid]=$labeltoshow;
								}

								if (! empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
								}

								$data[$obj->rowid]=$labeltoshow;
						}

						$i ++;
					}
					$this->db->free($resql);

					$out=$form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');

				} else {
					print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
				}
			}
		}
		elseif ($type == 'link')
		{
			$param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'
			$showempty=(($required && $default != '')?0:1);
			$out=$form->selectForForms($param_list[0], $keyprefix.$key.$keysuffix, $value, $showempty);
		}
		elseif ($type == 'password')
		{
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out='<input type="'.($keyprefix=='search_'?'text':'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam?$moreparam:'').'>';
		}
		if (!empty($hidden)) {
			$out='<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */
		return $out;
	}


	/**
	 * Return HTML string to put an output field into a page
	 *
	 * @param   string	$key            		Key of attribute
	 * @param   string	$value          		Value to show
	 * @param	string	$moreparam				To add more parameters on html input tag (only checkbox use html input for output rendering)
	 * @param	string	$extrafieldsobjectkey	If defined, use the new method to get extrafields data
	 * @return	string							Formated value
	 */
	function showOutputField($key, $value, $moreparam='', $extrafieldsobjectkey='')
	{
		global $conf,$langs;

		if (! empty($extrafieldsobjectkey))
		{
			$elementtype=$this->attributes[$extrafieldsobjectkey]['elementtype'][$key];	// seems not used
			$label=$this->attributes[$extrafieldsobjectkey]['label'][$key];
			$type=$this->attributes[$extrafieldsobjectkey]['type'][$key];
			$size=$this->attributes[$extrafieldsobjectkey]['size'][$key];
			$default=$this->attributes[$extrafieldsobjectkey]['default'][$key];
			$computed=$this->attributes[$extrafieldsobjectkey]['computed'][$key];
			$unique=$this->attributes[$extrafieldsobjectkey]['unique'][$key];
			$required=$this->attributes[$extrafieldsobjectkey]['required'][$key];
			$param=$this->attributes[$extrafieldsobjectkey]['param'][$key];
			$perms=$this->attributes[$extrafieldsobjectkey]['perms'][$key];
			$langfile=$this->attributes[$extrafieldsobjectkey]['langfile'][$key];
			$list=$this->attributes[$extrafieldsobjectkey]['list'][$key];
			$hidden=(($list == 0) ? 1 : 0);		// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}
		else	// Old usage
		{
			$elementtype=$this->attribute_elementtype[$key];	// seems not used
			$label=$this->attribute_label[$key];
			$type=$this->attribute_type[$key];
			$size=$this->attribute_size[$key];
			$default=$this->attribute_default[$key];
			$computed=$this->attribute_computed[$key];
			$unique=$this->attribute_unique[$key];
			$required=$this->attribute_required[$key];
			$param=$this->attribute_param[$key];
			$perms=$this->attribute_perms[$key];
			$langfile=$this->attribute_langfile[$key];
			$list=$this->attribute_list[$key];
			$hidden=(($list == 0) ? 1 : 0);		// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
		}

		if ($hidden) return '';		// This is a protection. If field is hidden, we should just not call this method.

		// If field is a computed field, value must become result of compute
		if ($computed)
		{
		    // Make the eval of compute string
		    //var_dump($computed);
		    $value = dol_eval($computed, 1, 0);
		}

		$showsize=0;
		if ($type == 'date')
		{
			$showsize=10;
			$value=dol_print_date($value, 'day');
		}
		elseif ($type == 'datetime')
		{
			$showsize=19;
			$value=dol_print_date($value, 'dayhour');
		}
		elseif ($type == 'int')
		{
			$showsize=10;
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {
				$value=price($value);
			}
		}
		elseif ($type == 'boolean')
		{
			$checked='';
			if (!empty($value)) {
				$checked=' checked ';
			}
			$value='<input type="checkbox" '.$checked.' '.($moreparam?$moreparam:'').' readonly disabled>';
		}
		elseif ($type == 'mail')
		{
			$value=dol_print_email($value, 0, 0, 0, 64, 1, 1);
		}
		elseif ($type == 'url')
		{
			$value=dol_print_url($value,'_blank',32,1);
		}
		elseif ($type == 'phone')
		{
			$value=dol_print_phone($value, '', 0, 0, '', '&nbsp;', 1);
		}
		elseif ($type == 'price')
		{
			$value=price($value, 0, $langs, 0, 0, -1, $conf->currency);
		}
		elseif ($type == 'select')
		{
			$value=$param['options'][$value];
		}
		elseif ($type == 'sellist')
		{
			$param_list=array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey="rowid";
			$keyList='rowid';

			if (count($InfoFieldList)>=3)
			{
				$selectkey = $InfoFieldList[2];
				$keyList=$InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|',$InfoFieldList[1]);
			if(is_array($fields_label)) {
				$keyList .=', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT '.$keyList;
			$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra')!==false)
			{
				$sql.= ' as main';
			}
			if ($selectkey=='rowid' && empty($value)) {
				$sql.= " WHERE ".$selectkey."=0";
			} elseif ($selectkey=='rowid') {
				$sql.= " WHERE ".$selectkey."=".$this->db->escape($value);
			}else {
				$sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$value='';	// value was used, so now we reste it to use it to build final output

				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|',$InfoFieldList[1]);

				if(is_array($fields_label) && count($fields_label)>1)
				{
					foreach ($fields_label as $field_toshow)
					{
						$translabel='';
						if (!empty($obj->$field_toshow)) {
							$translabel=$langs->trans($obj->$field_toshow);
						}
						if ($translabel!=$field_toshow) {
							$value.=dol_trunc($translabel,18).' ';
						}else {
							$value.=$obj->$field_toshow.' ';
						}
					}
				}
				else
				{
					$translabel='';
					if (!empty($obj->{$InfoFieldList[1]})) {
						$translabel=$langs->trans($obj->{$InfoFieldList[1]});
					}
					if ($translabel!=$obj->{$InfoFieldList[1]}) {
						$value=dol_trunc($translabel,18);
					}else {
						$value=$obj->{$InfoFieldList[1]};
					}
				}
			}
			else dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
		}
		elseif ($type == 'radio')
		{
			$value=$param['options'][$value];
		}
		elseif ($type == 'checkbox')
		{
			$value_arr=explode(',',$value);
			$value='';
			$toprint=array();
			if (is_array($value_arr))
			{
				foreach ($value_arr as $keyval=>$valueval) {
					$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$param['options'][$valueval].'</li>';
				}
			}
			$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		}
		elseif ($type == 'chkbxlst')
		{
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT ' . $keyList;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this) . ':showOutputField:$type=chkbxlst',LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output
				$toprint=array();
				while ( $obj = $this->db->fetch_object($resql) ) {

					// Several field into label (eq table:code|libelle:rowid)
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ( $fields_label as $field_toshow ) {
								$translabel = '';
								if (! empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
								} else {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->$field_toshow.'</li>';
								}
							}
						} else {
							$translabel = '';
							if (! empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
							} else {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->{$InfoFieldList[1]}.'</li>';
							}
						}
					}
				}
				$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';

			} else {
				dol_syslog(get_class($this) . '::showOutputField error ' . $this->db->lasterror(), LOG_WARNING);
			}
		}
		elseif ($type == 'link')
		{
			$out='';

			// Only if something to display (perf)
			if ($value)		// If we have -1 here, pb is into sert, not into ouptu
			{
				$param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname=$InfoFieldList[0];
				$classpath=$InfoFieldList[1];
				if (! empty($classpath))
				{
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname))
					{
						$object = new $classname($this->db);
						$object->fetch($value);
						$value=$object->getNomUrl(3);
					}
				}
				else
				{
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
		}
		elseif ($type == 'text')
		{
			$value=dol_htmlentitiesbr($value);
		}
		elseif ($type == 'html')
		{
			$value=dol_htmlentitiesbr($value);
		}
		elseif ($type == 'password')
		{
			$value=preg_replace('/./i','*',$value);
		}
		else
		{
			$showsize=round($size);
			if ($showsize > 48) $showsize=48;
		}

		//print $type.'-'.$size;
		$out=$value;

		return $out;
	}

	/**
	 * Return tag to describe alignement to use for this extrafield
	 *
	 * @param   string	$key            		Key of attribute
	 * @param	string	$extrafieldsobjectkey	If defined, use the new method to get extrafields data
	 * @return	string							Formated value
	 */
	function getAlignFlag($key, $extrafieldsobjectkey='')
	{
		global $conf,$langs;

		if (! empty($extrafieldsobjectkey)) $type=$this->attributes[$extrafieldsobjectkey]['type'][$key];
		else $type=$this->attribute_type[$key];

		$align='';

		if ($type == 'date')
		{
			$align="center";
		}
		elseif ($type == 'datetime')
		{
			$align="center";
		}
		elseif ($type == 'int')
		{
			$align="right";
		}
		elseif ($type == 'double')
		{
			$align="right";
		}
		elseif ($type == 'boolean')
		{
			$align="center";
		}
		elseif ($type == 'radio')
		{
			$align="center";
		}
		elseif ($type == 'checkbox')
		{
			$align="center";
		}

		return $align;
	}

	/**
	 * Return HTML string to print separator extrafield
	 *
	 * @param   string	$key            Key of attribute
	 * @return string
	 */
	function showSeparator($key)
	{
		$out = '<tr class="trextrafieldseparator trextrafieldseparator'.$key.'"><td colspan="4"><strong>'.$this->attribute_label[$key].'</strong></td></tr>';
		return $out;
	}

	/**
	 * Fill array_options property of object by extrafields value (using for data sent by forms)
	 *
	 * @param   array	$extralabels    $array of extrafields
	 * @param   object	$object         Object
	 * @param	string	$onlykey		Only following key is filled. When we make update of only one extrafield ($action = 'update_extras'), calling page must must set this to avoid to have other extrafields being reset.
	 * @return	int						1 if array_options set, 0 if no value, -1 if error (field required missing for example)
	 */
	function setOptionalsFromPost($extralabels,&$object,$onlykey='')
	{
		global $_POST, $langs;
		$nofillrequired='';// For error when required field left blank
		$error_field_required = array();

		if (is_array($extralabels))
		{
			// Get extra fields
			foreach ($extralabels as $key => $value)
			{
				if (! empty($onlykey) && $key != $onlykey) continue;

				$key_type = $this->attribute_type[$key];
				if ($this->attribute_required[$key] && empty($_POST["options_".$key])) // Check if empty without GETPOST, value can be alpha, int, array, etc...
				{
					$nofillrequired++;
					$error_field_required[] = $value;
				}

				if (in_array($key_type,array('date')))
				{
					// Clean parameters
					// TODO GMT date in memory must be GMT so we should add gm=true in parameters
					$value_key=dol_mktime(0, 0, 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				elseif (in_array($key_type,array('datetime')))
				{
					// Clean parameters
					// TODO GMT date in memory must be GMT so we should add gm=true in parameters
					$value_key=dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				else if (in_array($key_type,array('checkbox','chkbxlst')))
				{
					$value_arr=GETPOST("options_".$key, 'array'); // check if an array
					if (!empty($value_arr)) {
						$value_key=implode($value_arr,',');
					}else {
						$value_key='';
					}
				}
				else if (in_array($key_type,array('price','double')))
				{
					$value_arr=GETPOST("options_".$key);
					$value_key=price2num($value_arr);
				}
				else
				{
					$value_key=GETPOST("options_".$key);
				}
				$object->array_options["options_".$key]=$value_key;
			}

			if ($nofillrequired) {
				$langs->load('errors');
				setEventMessages($langs->trans('ErrorFieldsRequired').' : '.implode(', ',$error_field_required), null, 'errors');
				return -1;
			}
			else {
				return 1;
			}
		}
		else {
			return 0;
		}
	}

	/**
	 * return array_options array of data of extrafields value of object sent by a search form
	 *
	 * @param  array   $extralabels    $array of extrafields
	 * @param  string  $keyprefix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keysuffix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return array|int               array_options set or 0 if no value
	 */
	function getOptionalsFromPost($extralabels,$keyprefix='',$keysuffix='')
	{
		global $_POST;

		$array_options = array();
		if (is_array($extralabels))
		{
			// Get extra fields
			foreach ($extralabels as $key => $value)
			{
				$key_type = $this->attribute_type[$key];

				if (in_array($key_type,array('date','datetime')))
				{
					// Clean parameters
					$value_key=dol_mktime($_POST[$keysuffix."options_".$key.$keyprefix."hour"], $_POST[$keysuffix."options_".$key.$keyprefix."min"], 0, $_POST[$keysuffix."options_".$key.$keyprefix."month"], $_POST[$keysuffix."options_".$key.$keyprefix."day"], $_POST[$keysuffix."options_".$key.$keyprefix."year"]);
				}
				else if (in_array($key_type,array('checkbox', 'chkbxlst')))
				{
					$value_arr=GETPOST($keysuffix."options_".$key.$keyprefix);
					// Make sure we get an array even if there's only one checkbox
					$value_arr=(array) $value_arr;
					$value_key=implode(',', $value_arr);
				}
				else if (in_array($key_type,array('price','double')))
				{
					$value_arr=GETPOST($keysuffix."options_".$key.$keyprefix);
					$value_key=price2num($value_arr);
				}
				else
				{
					$value_key=GETPOST($keysuffix."options_".$key.$keyprefix);
				}

				$array_options[$keysuffix."options_".$key]=$value_key;	// No keyprefix here. keyprefix is used only for read.
			}

			return $array_options;
		}
		else {
			return 0;
		}
	}
}
