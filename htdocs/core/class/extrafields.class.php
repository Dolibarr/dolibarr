<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier	    <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

	var $error;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function ExtraFields($db)
	{
		$this->db = $db;
		$this->error = array();
		$this->attribute_type = array();
		$this->attribute_label = array();
		$this->attribute_size = array();
		$this->attribute_elementtype = array();
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
     *  @return int      					<=0 if KO, >0 if OK
     */
    function addExtraField($attrname,$label,$type='',$pos=0,$size=0, $elementtype='member')
	{
        if (empty($attrname)) return -1;
        if (empty($label)) return -1;

        $result=$this->create($attrname,$type,$size,$elementtype);
        if ($result > 0)
        {
            $result2=$this->create_label($attrname,$label,$type,$pos,$size,$elementtype);
            if ($result2 > 0) return 1;
            else return -2;
        }
        else
        {
            return -1;
        }
	}

	/**
	 *	Add a new optionnal attribute
	 *
	 *	@param	string	$attrname			code of attribute
	 *  @param	int		$type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param	int		$length				Size/length of attribute
     *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
     *  @return int      	           		<=0 if KO, >0 if OK
	 */
	function create($attrname,$type='varchar',$length=255,$elementtype='member')
	{
        $table='';
        if ($elementtype == 'member')  $table='adherent_extrafields';
        if ($elementtype == 'company') $table='societe_extrafields';
        if ($elementtype == 'contact') $table='socpeople_extrafields';
        if ($elementtype == 'product') $table='product_extrafields';
        if (empty($table))
        {
            print 'ErrorBarValueForParameters';
            return -1;
        }

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$field_desc = array('type'=>$type, 'value'=>$length);
			$result=$this->db->DDLAddField(MAIN_DB_PREFIX.$table, $attrname, $field_desc);
			if ($result > 0)
			{
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
			return 0;
		}
	}

	/**
	 *	Add description of a new optionnal attribute
	 *
	 *	@param	string	$attrname			code of attribute
	 *	@param	string	$label				label of attribute
	 *  @param	int		$type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param	int		$pos				Position of attribute
	 *  @param	int		$size				Size/length of attribute
	 *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
	 *  @return	int							<=0 if KO, >0 if OK
	 */
	function create_label($attrname,$label='',$type='',$pos=0,$size=0, $elementtype='member')
	{
		global $conf;

		// Clean parameters
		if (empty($pos)) $pos=0;


		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."extrafields(name, label, type, pos, size, entity, elementtype)";
			$sql.= " VALUES('".$attrname."',";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$type."',";
			$sql.= " '".$pos."',";
			$sql.= " '".$size."',";
			$sql.= " ".$conf->entity.",";
            $sql.= " '".$elementtype."'";
			$sql.=')';

			dol_syslog(get_class($this)."::create_label sql=".$sql);
			if ($this->db->query($sql))
			{
				return 1;
			}
			else
			{
				print dol_print_error($this->db);
				return 0;
			}
		}
	}

	/**
	 *	Delete an optionnal attribute
	 *
	 *	@param	string	$attrname		Code of attribute to delete
	 *  @param  string	$elementtype    Element type ('member', 'product', 'company', ...)
	 *  @return int              		< 0 if KO, 0 if nothing is done, 1 if OK
	 */
	function delete($attrname,$elementtype='member')
	{
	    $table='';
	    if ($elementtype == 'member')  $table='adherent_extrafields';
        if ($elementtype == 'company') $table='societe_extrafields';
        if ($elementtype == 'contact') $table='socpeople_extrafields';
        if ($elementtype == 'product') $table='product_extrafields';
        if (empty($table))
        {
            print 'ErrorBarValueForParameters';
            return -1;
        }

		if (! empty($attrname) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
		    $result=$this->db->DDLDropField(MAIN_DB_PREFIX.$table,$attrname);
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
	function delete_label($attrname,$elementtype='member')
	{
		global $conf;

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql.= " WHERE name = '".$attrname."'";
			$sql.= " AND entity = ".$conf->entity;
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
	 *  @param	string	$type				Type of attribute
	 *  @param	int		$length				Length of attribute
     *  @param  string	$elementtype        Element type ('member', 'product', 'company', ...)
	 * 	@return	int							>0 if OK, <=0 if KO
	 */
	function update($attrname,$type='varchar',$length=255,$elementtype='member')
	{
        $table='';
        if ($elementtype == 'member')  $table='adherent_extrafields';
        if ($elementtype == 'company') $table='societe_extrafields';
        if ($elementtype == 'contact') $table='socpeople_extrafields';
        if ($elementtype == 'product') $table='product_extrafields';
        if (empty($table))
        {
            print 'ErrorBarValueForParameters';
            return -1;
        }

        if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$field_desc = array('type'=>$type, 'value'=>$length);
			$result=$this->db->DDLUpdateField(MAIN_DB_PREFIX.$table, $attrname, $field_desc);
			if ($result > 0)
			{
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
     *  @return	int							<0 if KO, >0 if OK
     */
	function update_label($attrname,$label,$type,$size,$elementtype='member')
	{
		global $conf;
		dol_syslog(get_class($this)."::update_label $attrname,$label,$type,$size");

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$this->db->begin();

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
			$sql.= " elementtype";
			$sql.= ") VALUES (";
			$sql.= "'".$attrname."',";
			$sql.= " ".$conf->entity.",";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$type."',";
			$sql.= " '".$size."',";
            $sql.= " '".$elementtype."'";
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
				return 0;
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
	 * 	@return	array							Array of attributes for all extra fields
	 */
	function fetch_name_optionals_label($elementtype='member')
	{
		global $conf;

		$array_name_label=array();

		$sql = "SELECT rowid,name,label,type,size,elementtype";
		$sql.= " FROM ".MAIN_DB_PREFIX."extrafields";
		$sql.= " WHERE entity = ".$conf->entity;
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
					$array_name_label[$tab->name]=$tab->label;
					$this->attribute_type[$tab->name]=$tab->type;
					$this->attribute_label[$tab->name]=$tab->label;
					$this->attribute_size[$tab->name]=$tab->size;
                    $this->attribute_elementtype[$tab->name]=$tab->elementtype;
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
	 *  @param  string	$value           Value to show
	 *  @param  string	$moreparam       To add more parametes on html input tag
	 *  @return	void
	 */
	function showInputField($key,$value,$moreparam='')
	{
		global $conf;

        $label=$this->attribute_label[$key];
	    $type=$this->attribute_type[$key];
        $size=$this->attribute_size[$key];
        $elementtype=$this->attribute_elementtype[$key];
        if ($type == 'date')
        {
            $showsize=10;
        }
        elseif ($type == 'datetime')
        {
            $showsize=19;
        }
        elseif ($type == 'int')
        {
            $showsize=10;
        }
        else
        {
            $showsize=round($size);
            if ($showsize > 48) $showsize=48;
        }

		if ($type == 'int')
        {
        	$out='<input type="text" name="options_'.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
        }
        else if ($type == 'varchar')
        {
        	$out='<input type="text" name="options_'.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$value.'"'.($moreparam?$moreparam:'').'>';
        }
        else if ($type == 'text')
        {
        	require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        	$doleditor=new DolEditor('options_'.$key,$value,'',200,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE,5,100);
        	$out=$doleditor->Create(1);
        }
	    else if ($type == 'date') $out.=' (YYYY-MM-DD)';
        else if ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
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
        $label=$this->attribute_label[$key];
        $type=$this->attribute_type[$key];
        $size=$this->attribute_size[$key];
        $elementtype=$this->attribute_elementtype[$key];
        if ($type == 'date')
        {
            $showsize=10;
        }
        elseif ($type == 'datetime')
        {
            $showsize=19;
        }
        elseif ($type == 'int')
        {
            $showsize=10;
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

}
?>
