<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier	    <benoit.mortier@opensides.be>
 * Copyright (C) 2009      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * 	\file 		htdocs/adherents/class/adherent_options.class.php
 *	\ingroup    member
 *	\brief      File of class to manage optionnal fields
 *	\version    $Id$
 */

/**
 * 	\class 		AdherentOptions
 *	\brief      Class to manage table of optionnal fields
 */
class AdherentOptions
{
	var $id;
	var $db;
	// Tableau contenant le nom des champs en clef et la definition de ces champs
	var $attribute_type;
	// Tableau contenant le nom des champs en clef et le label de ces champs en value
	var $attribute_label;
	// Tableau contenant le nom des champs en clef et la taille de ces champs en value
	var $attribute_size;

	var $error;
	/*
	 * Constructor
	 *
	 */

	/**
	 *  \brief AdherentOptions
	 *  \param DB			base de donnees
	 *  \param id			id de l'adherent
	 */
	function AdherentOptions($DB, $id='')
	{
		$this->db = $DB ;
		$this->id = $id;
		$this->error = array();
		$this->attribute_type = array();
		$this->attribute_label = array();
		$this->attribute_size = array();
	}

	/**
	 *	Add a new optionnal attribute
	 *	@param	attrname			code of attribute
	 *  @param	type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param	length				Size/length of attribute
	 */
	function create($attrname,$type='varchar',$length=255) {

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$field_desc = array('type'=>$type, 'value'=>$length);
			$result=$this->db->DDLAddField(MAIN_DB_PREFIX.'adherent_extrafields', $attrname, $field_desc);
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
	 *	@param	attrname			code of attribute
	 *	@param	label				label of attribute
	 *  @param	type				Type of attribute ('int', 'text', 'varchar', 'date', 'datehour')
	 *  @param	pos					Position of attribute
	 *  @param	size				Size/length of attribute
	 *  @param  elementtype         Element type ('member', 'product', ...)
	 *  @return	int					<=0 if KO, >0 if OK
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
			$sql.= " ".$conf->entity;
            $sql.= ", '".$elementtype."'";
			$sql.=')';

			dol_syslog("AdherentOptions::create_label sql=".$sql);
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
	 *	@param	attrname			Code of attribute to delete
	 *  TODO This does not work with multicompany module
	 */
	function delete($attrname)
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$result=$this->db->DDLDropField(MAIN_DB_PREFIX."adherent_extrafields",$attrname);
			if ($result < 0)
			{
				$this->error=$this->db->lasterror();
				dol_syslog("AdherentOption::delete ".$this->error, LOG_ERR);
			}

			$result=$this->delete_label($attrname);

			return $result;
		}
		else
		{
			return 0;
		}

	}

	/**
	 *	Delete description of an optionnal attribute
	 *	@param	attrname			Code of attribute to delete
	 */
	function delete_label($attrname)
	{
		global $conf;

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql.= " WHERE name = '$attrname'";
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("AdherentOptions::delete_label sql=".$sql);
			if ( $this->db->query( $sql) )
			{
				return 1;
			}
			else
			{
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
	 * 	Modify type of a personalized attribute
	 *  @param		attrname			name of attribute
	 *  @param		type				type of attribute
	 *  @param		length				length of attribute
	 * 	@return		int					>0 if OK, <=0 if KO
	 *  TODO This does not works with mutlicompany module
	 */
	function update($attrname,$type='varchar',$length=255)
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$field_desc = array('type'=>$type, 'value'=>$length);
			$result=$this->db->DDLUpdateField(MAIN_DB_PREFIX.'extrafields', $attrname, $field_desc);
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
	 *  Modify description of an optionnal attribute
	 *  @param	attrname			nom de l'atribut
	 *  @param	label				nom du label
	 *  @param	type				type
	 *  @param	size				size
	 *  @param  elementtype         Element type ('member', 'product', ...)
	 */
	function update_label($attrname,$label,$type,$size,$elementtype='member')
	{
		global $conf;
		dol_syslog("AdherentOptions::update_label $attrname,$label,$type,$size");

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$this->db->begin();

			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."extrafields";
			$sql_del.= " WHERE name = '".$attrname."'";
			$sql_del.= " AND entity = ".$conf->entity;
			$sql_del.= " AND elementtype = '".$elementtype."'";
			dol_syslog("AdherentOptions::update_label sql=".$sql_del);
			$resql1=$this->db->query($sql_del);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."extrafields(";
			$sql.= " name,";		// This is code
			$sql.= " entity,";
			$sql.= " label,";
			$sql.= " type,";
			$sql.= " size";
			$sql.= ") VALUES (";
			$sql.= "'".$attrname."',";
			$sql.= " ".$conf->entity.",";
			$sql.= " '".$this->db->escape($label)."',";
			$sql.= " '".$type."',";
			$sql.= " '".$size."',";
            $sql.= " '".$elementtype."'";
			$sql.= ")";
			dol_syslog("AdherentOptions::update_label sql=".$sql);
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
	 *  \brief fonction qui modifie un label
	 */
	function fetch_optionals()
	{
		$this->fetch_name_optionals_label();
	}


	/**
	 * 	\brief 	Load array this->attribute_label
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

		dol_syslog("Adherent_options::fetch_name_optionals_label sql=".$sql);
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

}
?>
