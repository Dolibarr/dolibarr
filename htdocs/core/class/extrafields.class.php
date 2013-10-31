<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
* Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
* Copyright (C) 2004      Benoit Mortier	    <benoit.mortier@opensides.be>
* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@capnetworks.com>
* Copyright (C) 2013	  Florian Henry        <forian.henry@open-concept.pro>
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
	// Tableau contenant le nom des champs en clef et la definition de ces champs
	var $attribute_type;
	// Tableau contenant le nom des champs en clef et le label de ces champs en value
	var $attribute_label;
	// Tableau contenant le nom des champs en clef et la taille de ces champs en value
	var $attribute_size;
	// Tableau contenant le nom des choix en clef et la valeur de ces choix en value
	var $attribute_choice;
	// Array to store if attribute is unique or not
	var $attribute_unique;
	// Array to store if attribute is required or not
	var $attribute_required;
	// Array to store parameters of attribute (used in select type)
	var $attribute_param;
	// Int to store position of attribute
	var $attribute_pos;

	var $error;
	var $errno;

	static $type2label=array(
	'varchar'=>'String',
	'text'=>'TextLong',
	'int'=>'Int',
	'double'=>'Float',
	'date'=>'Date',
	'datetime'=>'DateAndTime',
	'boolean'=>'Boolean',
	'price'=>'ExtrafieldPrice',
	'phone'=>'ExtrafieldPhone',
	'mail'=>'ExtrafieldMail',
	'select' => 'ExtrafieldSelect',
	'sellist' => 'ExtrafieldSelectList',
	'separate' => 'ExtrafieldSeparator',
	'checkbox' => 'ExtrafieldCheckBox',
	'radio' => 'ExtrafieldRadio',

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
		$this->attribute_type = array();
		$this->attribute_label = array();
		$this->attribute_size = array();
		$this->attribute_elementtype = array();
		$this->attribute_unique = array();
		$this->attribute_required = array();
	}

	/**
	 *  Add a new extra field parameter
	 *
	 *  @param	string	$attrname           Code of attribute
	 *  @param  string	$label              label of attribute
	 *  @param  int		$type               Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param  int		$pos                Position of attribute
	 *  @param  int		$size               Size/length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	string	$default_value		Defaulted value
	 *  @param  array	$param				Params for field
	 *  @return int      					<=0 if KO, >0 if OK
	 */
	function addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique=0, $required=0,$default_value='', $param=0)
	{
		if (empty($attrname)) return -1;
		if (empty($label)) return -1;

		// Create field into database except for separator type which is not stored in database
		if ($type != 'separate')
		{
			$result=$this->create($attrname,$type,$size,$elementtype, $unique, $required, $default_value,$param);
		}
		$err1=$this->errno;
		if ($result > 0 || $err1 == 'DB_ERROR_COLUMN_ALREADY_EXISTS' || $type == 'separate')
		{
			// Add declaration of field into table
			$result2=$this->create_label($attrname,$label,$type,$pos,$size,$elementtype, $unique, $required, $param);
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
	 *	Add a new optionnal attribute.
	 *  This is a private method. For public method, use addExtraField.
	 *
	 *	@param	string	$attrname			code of attribute
	 *  @param	int		$type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param	int		$length				Size/length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param  string  $default_value		Default value for field
	 *  @param  array	$param				Params for field  (ex for select list : array('options'=>array('value'=>'label of option'))
	 *
	 *  @return int      	           		<=0 if KO, >0 if OK
	 */
	private function create($attrname, $type='varchar', $length=255, $elementtype='member', $unique=0, $required=0, $default_value='',$param='')
	{
		$table=$elementtype.'_extrafields';

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
			}elseif($type=='mail') {
				$typedb='varchar';
				$lengthdb='128';
			} elseif (($type=='select') || ($type=='sellist') || ($type=='radio') ||($type=='checkbox')){
				$typedb='text';
				$lengthdb='';
			} else {
				$typedb=$type;
				$lengthdb=$length;
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
	 *	Add description of a new optionnal attribute
	 *
	 *	@param	string	$attrname			code of attribute
	 *	@param	string	$label				label of attribute
	 *  @param	int		$type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour', 'float')
	 *  @param	int		$pos				Position of attribute
	 *  @param	int		$size				Size/length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param  array	$param				Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @return	int							<=0 if KO, >0 if OK
	 */
	private function create_label($attrname, $label='', $type='', $pos=0, $size=0, $elementtype='member', $unique=0, $required=0, $param='')
	{
		global $conf;

		// Clean parameters
		if (empty($pos)) $pos=0;


		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			if(is_array($param) and count($param) > 0)
			{
				$params = $this->db->escape(serialize($param));
			}
			elseif (strlen($param) > 0)
			{
				$params = trim($param);
			}
			else
			{
				$params='';
			}

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."extrafields(name, label, type, pos, size, entity, elementtype, fieldunique, fieldrequired, param)";
			$sql.= " VALUES('".$attrname."',";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$type."',";
			$sql.= " '".$pos."',";
			$sql.= " '".$size."',";
			$sql.= " ".$conf->entity.",";
			$sql.= " '".$elementtype."',";
			$sql.= " '".$unique."',";
			$sql.= " '".$required."',";
			$sql.= " '".$params."'";
			$sql.=')';

			dol_syslog(get_class($this)."::create_label sql=".$sql);
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
	 *	Delete an optionnal attribute
	 *
	 *	@param	string	$attrname		Code of attribute to delete
	 *  @param  string	$elementtype    Element type ('member', 'product', 'company', 'contact', ...)
	 *  @return int              		< 0 if KO, 0 if nothing is done, 1 if OK
	 */
	function delete($attrname, $elementtype='member')
	{
		$table=$elementtype.'_extrafields';

		if (! empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$result=$this->db->DDLDropField(MAIN_DB_PREFIX.$table,$attrname);	// This also drop the unique key
			if ($result < 0)
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			}

			$result=$this->delete_label($attrname,$elementtype);

			return $result;
		}
		else
		{
			return 0;
		}

	}

	/**
	 *	Delete description of an optionnal attribute
	 *
	 *	@param	string	$attrname			Code of attribute to delete
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
	 *  @return int              			< 0 if KO, 0 if nothing is done, 1 if OK
	 */
	private function delete_label($attrname, $elementtype='member')
	{
		global $conf;

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql.= " WHERE name = '".$attrname."'";
			$sql.= " AND entity IN  (0,".$conf->entity.')';
			$sql.= " AND elementtype = '".$elementtype."'";

			dol_syslog(get_class($this)."::delete_label sql=".$sql);
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
	 *  @param	string	$type				Type of attribute
	 *  @param	int		$length				Length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', 'contact', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	int		$pos				Position of attribute
	 *  @param  array	$param				Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 * 	@return	int							>0 if OK, <=0 if KO
	 */
	function update($attrname,$label,$type,$length,$elementtype,$unique=0,$required=0,$pos=0,$param='')
	{
		$table=$elementtype.'_extrafields';

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
			}elseif($type=='mail') {
				$typedb='varchar';
				$lengthdb='128';
			} elseif (($type=='select') || ($type=='sellist') || ($type=='radio') ||($type=='checkbox')) {
				$typedb='text';
				$lengthdb='';
			} else {
				$typedb=$type;
				$lengthdb=$length;
			}
			$field_desc = array('type'=>$typedb, 'value'=>$lengthdb, 'null'=>($required?'NOT NULL':'NULL'));

			if ($type != 'separate') // No table update when separate type
			{
				$result=$this->db->DDLUpdateField(MAIN_DB_PREFIX.$table, $attrname, $field_desc);
			}
			if ($result > 0 || $type == 'separate')
			{
				if ($label)
				{
					$result=$this->update_label($attrname,$label,$type,$length,$elementtype,$unique,$required,$pos,$param);
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
					dol_syslog(get_class($this).'::update sql='.$sql);
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
	 *  @param  string	$elementtype		Element type ('member', 'product', 'company', ...)
	 *  @param	int		$unique				Is field unique or not
	 *  @param	int		$required			Is field required or not
	 *  @param	int		$pos				Position of attribute
	 *  @param  array	$param				Params for field  (ex for select list : array('options' => array(value'=>'label of option')) )
	 *  @return	int							<=0 if KO, >0 if OK
	 */
	private function update_label($attrname,$label,$type,$size,$elementtype,$unique=0,$required=0,$pos=0,$param='')
	{
		global $conf;
		dol_syslog(get_class($this)."::update_label ".$attrname.", ".$label.", ".$type.", ".$size.", ".$elementtype.", ".$unique.", ".$required);

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$this->db->begin();

			if(is_array($param) && count($param) > 0)
			{
				$param = $this->db->escape(serialize($param));
			}

			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql_del.= " WHERE name = '".$attrname."'";
			$sql_del.= " AND entity = ".$conf->entity;
			$sql_del.= " AND elementtype = '".$elementtype."'";
			dol_syslog(get_class($this)."::update_label sql=".$sql_del);
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
			$sql.= " pos,";
			$sql.= " param";
			$sql.= ") VALUES (";
			$sql.= "'".$attrname."',";
			$sql.= " ".$conf->entity.",";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$type."',";
			$sql.= " '".$size."',";
			$sql.= " '".$elementtype."',";
			$sql.= " '".$unique."',";
			$sql.= " '".$required."',";
			$sql.= " '".$pos."',";
			$sql.= " '".$param."'";
			$sql.= ")";
			dol_syslog(get_class($this)."::update_label sql=".$sql);
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
	 *  Load array of labels
	 *
	 *  @return	void
	 */
	function fetch_optionals()
	{
		$this->fetch_name_optionals_label();
	}


	/**
	 * 	Load array this->attribute_label
	 *
	 * 	@param	string		$elementtype		Type of element
	 * 	@param	boolean		$forceload			Force load of extra fields whatever is option MAIN_EXTRAFIELDS_DISABLED
	 * 	@return	array							Array of attributes for all extra fields
	 */
	function fetch_name_optionals_label($elementtype='adherent',$forceload=false)
	{
		global $conf;

		$array_name_label=array();

		// For avoid conflicts with external modules
		if (!$forceload && !empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))
			return $array_name_label;

		$sql = "SELECT rowid,name,label,type,size,elementtype,fieldunique,fieldrequired,param,pos";
		$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
		$sql.= " WHERE entity IN (0,".$conf->entity.")";
		if ($elementtype) $sql.= " AND elementtype = '".$elementtype."'";
		$sql.= " ORDER BY pos";

		dol_syslog(get_class($this)."::fetch_name_optionals_label sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				while ($tab = $this->db->fetch_object($resql))
				{
					// we can add this attribute to adherent object
					if ($tab->type != 'separate')
					{
						$array_name_label[$tab->name]=$tab->label;
					}

					$this->attribute_type[$tab->name]=$tab->type;
					$this->attribute_label[$tab->name]=$tab->label;
					$this->attribute_size[$tab->name]=$tab->size;
					$this->attribute_elementtype[$tab->name]=$tab->elementtype;
					$this->attribute_unique[$tab->name]=$tab->fieldunique;
					$this->attribute_required[$tab->name]=$tab->fieldrequired;
					$this->attribute_param[$tab->name]=unserialize($tab->param);
					$this->attribute_pos[$tab->name]=$tab->pos;
				}
			}

			return $array_name_label;
		}
		else
		{
			print dol_print_error($this->db);
		}
	}


	/**
	 *  Return HTML string to put an input field into a page
	 *
	 *  @param	string	$key             Key of attribute
	 *  @param  string	$value           Value to show (for date type it must be in timestamp format)
	 *  @param  string	$moreparam       To add more parametes on html input tag
	 *  @return	void
	 */
	function showInputField($key,$value,$moreparam='')
	{
		global $conf,$langs;

		$label=$this->attribute_label[$key];
		$type =$this->attribute_type[$key];
		$size =$this->attribute_size[$key];
		$elementtype=$this->attribute_elementtype[$key];
		$unique=$this->attribute_unique[$key];
		$required=$this->attribute_required[$key];
		$param=$this->attribute_param[$key];
		if ($type == 'date')
		{
			$showsize=10;
		}
		elseif ($type == 'datetime')
		{
			$showsize=19;
		}
		elseif (in_array($type,array('int','double')))
		{
			$showsize=10;
		}
		else
		{
			$showsize=round($size);
			if ($showsize > 48) $showsize=48;
		}

		if (in_array($type,array('date','datetime')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];
			if(!class_exists('Form'))
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$formstat = new Form($db);

			$showtime = in_array($type,array('datetime')) ? 1 : 0;
			// Do not show current date when field not required (see select_date() method)
			if(!$required && $value == '')
				$value = '-1';

			$out = $formstat->select_date($value, 'options_'.$key, $showtime, $showtime, $required, '', 1, 1, 1, 0, 1);
			//$out='<input type="text" name="options_'.$key.'" size="'.$showsize.'" maxlength="'.$newsize.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
		}
		elseif (in_array($type,array('int')))
		{
			$tmp=explode(',',$size);
			$newsize=$tmp[0];
			$out='<input type="text" class="flat" name="options_'.$key.'" size="'.$showsize.'" maxlength="'.$newsize.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'varchar')
		{
			$out='<input type="text" class="flat" name="options_'.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'text')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor=new DolEditor('options_'.$key,$value,'',200,'dolibarr_notes','In',false,false,! empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE,5,100);
			$out=$doleditor->Create(1);
		}
		elseif ($type == 'boolean')
		{
			$checked='';
			if (!empty($value)) {
				$checked=' checked="checked" value="1" ';
			} else {
				$checked=' value="1" ';
			}
			$out='<input type="checkbox" class="flat" name="options_'.$key.'" '.$checked.' '.($moreparam?$moreparam:'').'>';
		}
		elseif ($type == 'mail')
		{
			$out='<input type="text" class="flat" name="options_'.$key.'" size="32" value="'.$value.'">';
		}
		elseif ($type == 'phone')
		{
			$out='<input type="text" class="flat" name="options_'.$key.'"  size="20" value="'.$value.'">';
		}
		elseif ($type == 'price')
		{
			$out='<input type="text" class="flat" name="options_'.$key.'"  size="6" value="'.price($value).'"> '.$langs->getCurrencySymbol($conf->currency);
		}
		elseif ($type == 'double')
		{
			if (!empty($value)) {
				$value=price($value);
			}
			$out='<input type="text" class="flat" name="options_'.$key.'"  size="6" value="'.$value.'"> ';
		}
		elseif ($type == 'select')
		{
			$out='<select class="flat" name="options_'.$key.'">';
			foreach ($param['options'] as $key=>$val )
			{
				list($val, $parent) = explode('|', $val);
				$out.='<option value="'.$key.'"';
				$out.= ($value==$key?' selected="selected"':'');
				$out.= (!empty($parent)?' parent="'.$parent.'"':'');
				$out.='>'.$val.'</option>';
			}
			$out.='</select>';
		}
		elseif ($type == 'sellist')
		{
			$out='<select class="flat" name="options_'.$key.'">';
			if (is_array($param['options']))
			{
				$param_list=array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);

				// 0 1 : tableName
				// 1 2 : label field name Nom du champ contenant le libelle
				// 2 3 : key fields name (if differ of rowid)
				// 3 4 : key field parent (for dependent lists)

				$keyList='rowid';

				if (count($InfoFieldList)>=3)
					$keyList=$InfoFieldList[2].' as rowid';
				if (count($InfoFieldList)>=4) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList.= ', '.$parentField;
				}

				$fields_label = explode('|',$InfoFieldList[1]);
				if(is_array($fields_label)) {
					$keyList .=', ';
					$keyList .= implode(', ', $fields_label);
				}

				$fields_label = explode('|',$InfoFieldList[1]);
				if(is_array($fields_label)) {
					$keyList .=', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sql = 'SELECT '.$keyList;
				$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
				//$sql.= ' WHERE entity = '.$conf->entity;
				//print $sql;

				dol_syslog(get_class($this).'::showInputField type=sellist sql='.$sql);
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
						$fields_label = explode('|',$InfoFieldList[1]);
						if(is_array($fields_label))
						{
							foreach ($fields_label as $field_toshow)
							{
								$labeltoshow.= $obj->$field_toshow.' ';
							}
						}
						else
						{
							$labeltoshow=$obj->$InfoFieldList[1];
						}
						$labeltoshow=dol_trunc($labeltoshow,45);

						if ($value==$obj->rowid)
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
						}
						else
						{
							$translabel=$langs->trans($obj->$InfoFieldList[1]);
							if ($translabel!=$obj->$InfoFieldList[1]) {
								$labeltoshow=dol_trunc($translabel,18);
							}
							else {
								$labeltoshow=dol_trunc($obj->$InfoFieldList[1],18);
							}
							if (empty($labeltoshow)) $labeltoshow='(not defined)';
							if ($value==$obj->rowid)
							{
								$out.='<option value="'.$obj->rowid.'" selected="selected">'.$labeltoshow.'</option>';
							}

							if (!empty($InfoFieldList[3]))
							{
								$parent = $parentName.':'.$obj->{$parentField};
							}

							$out.='<option value="'.$obj->rowid.'"';
							$out.= ($value==$obj->rowid?' selected="selected"':'');
							$out.= (!empty($parent)?' parent="'.$parent.'"':'');
							$out.='>'.$labeltoshow.'</option>';
						}

						$i++;
					}
					$this->db->free($resql);
				}
			}
			$out.='</select>';
		}
		elseif ($type == 'checkbox')
		{
			$out='';
			$value_arr=explode(',',$value);

			foreach ($param['options'] as $keyopt=>$val )
			{

				$out.='<input class="flat" type="checkbox" name="options_'.$key.'[]"';
				$out.=' value="'.$keyopt.'"';

				if ((is_array($value_arr)) && in_array($keyopt,$value_arr)) {
					$out.= 'checked="checked"';
				}else {
					$out.='';
				}

				$out.='/>'.$val.'<br>';
			}
		}
		elseif ($type == 'radio')
		{
			$out='';
			foreach ($param['options'] as $keyopt=>$val )
			{
				$out.='<input class="flat" type="radio" name="options_'.$key.'"';
				$out.=' value="'.$keyopt.'"';
				$out.= ($value==$keyopt?'checked="checked"':'');
				$out.='/>'.$val.'<br>';
			}
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
	 * @param   string	$key            Key of attribute
	 * @param   string	$value          Value to show
	 * @param	string	$moreparam		More param
	 * @return	string					Formated value
	 */
	function showOutputField($key,$value,$moreparam='')
	{
		global $conf,$langs;

		$label=$this->attribute_label[$key];
		$type=$this->attribute_type[$key];
		$size=$this->attribute_size[$key];
		$elementtype=$this->attribute_elementtype[$key];
		$unique=$this->attribute_unique[$key];
		$required=$this->attribute_required[$key];
		$params=$this->attribute_param[$key];
		if ($type == 'date')
		{
			$showsize=10;
			$value=dol_print_date($value,'day');
		}
		elseif ($type == 'datetime')
		{
			$showsize=19;
			$value=dol_print_date($value,'dayhour');
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
				$checked=' checked="checked" ';
			}
			$value='<input type="checkbox" '.$checked.' '.($moreparam?$moreparam:'').' readonly="readonly" disabled="disabled">';
		}
		elseif ($type == 'mail')
		{
			$value=dol_print_email($value);
		}
		elseif ($type == 'phone')
		{
			$value=dol_print_phone($value);
		}
		elseif ($type == 'price')
		{
			$value=price($value,0,$langs,0,0,-1,$conf->currency);
		}
		elseif ($type == 'select')
		{
			$value=$params['options'][$value];
		}
		elseif ($type == 'sellist')
		{
			$param_list=array_keys($params['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey="rowid";
			$keyList='rowid';

			if (count($InfoFieldList)==3)
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
			$sql.= ' WHERE '.$selectkey.'=\''.$this->db->escape($value).'\'';
			//$sql.= ' AND entity = '.$conf->entity;
			dol_syslog(get_class($this).':showOutputField:$type=sellist sql='.$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|',$InfoFieldList[1]);

				if(is_array($fields_label))
				{
					foreach ($fields_label as $field_toshow)
					{
						$translabel=$langs->trans($obj->$InfoFieldList[1]);
						if ($translabel!=$obj->$InfoFieldList[1]) {
							$value=dol_trunc($translabel,18).' ';
						}else {
							$value=$obj->$InfoFieldList[1].' ';
						}
					}
				}
				else
				{
					$translabel=$langs->trans($obj->$InfoFieldList[1]);
					if ($translabel!=$obj->$InfoFieldList[1]) {
						$value=dol_trunc($translabel,18);
					}else {
						$value=$obj->$InfoFieldList[1];
					}
				}
			}
		}
		elseif ($type == 'radio')
		{
			$value=$params['options'][$value];
		}
		elseif ($type == 'checkbox')
		{
			$value_arr=explode(',',$value);
			$value='';
			if (is_array($value_arr))
			{
				foreach ($value_arr as $keyval=>$valueval) {
					$value.=$params['options'][$valueval].'<br>';
				}
			}
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
	 * Return HTML string to print separator extrafield
	 *
	 * @param   string	$key            Key of attribute
	 * @return string
	 */
	function showSeparator($key)
	{
		$out = '<tr class="liste_titre"><td colspan="4"><strong>'.$this->attribute_label[$key].'</strong></td></tr>';
		return $out;
	}

	/**
	 * Fill array_options array for object by extrafields value (using for data send by forms)
	 *
	 * @param   array	$extralabels    $array of extrafields
	 * @param   object	&$object         object
	 * @return	int						1 if array_options set / 0 if no value
	 */
	function setOptionalsFromPost($extralabels,&$object)
	{
		global $_POST, $langs;
		$nofillrequired='';// For error when required field left blank
		$error_field_required = array();

		if (is_array($extralabels))
		{
			// Get extra fields
			foreach ($extralabels as $key => $value)
			{
				$key_type = $this->attribute_type[$key];
				if($this->attribute_required[$key] && !GETPOST("options_$key",2))
				{
					$nofillrequired++;
					$error_field_required[] = $value;
				}

				if (in_array($key_type,array('date','datetime')))
				{
					// Clean parameters
					$value_key=dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				else if (in_array($key_type,array('checkbox')))
				{
					$value_arr=GETPOST("options_".$key);
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

			if($nofillrequired) {
				$langs->load('errors');
				setEventMessage($langs->trans('ErrorFieldsRequired').' : '.implode(', ',$error_field_required),'errors');
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
	 * return array_options array for object by extrafields value (using for data send by forms)
	 *
	 * @param   array	$extralabels    $array of extrafields
	 * @return	int						1 if array_options set / 0 if no value
	 */
	function getOptionalsFromPost($extralabels)
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
					$value_key=dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]);
				}
				else if (in_array($key_type,array('checkbox')))
				{
					$value_arr=GETPOST("options_".$key);
					$value_key=implode($value_arr,',');
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
				$array_options["options_".$key]=$value_key;
			}

			return $array_options;
		}
		else {
			return 0;
		}
	}
}
