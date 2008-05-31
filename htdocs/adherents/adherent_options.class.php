<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier			  <benoit.mortier@opensides.be>
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
 *
 * $Id$
 * $Source$
 *
 */

/*!	\file htdocs/adherents/adherent_options.class.php
 \ingroup    adherent
 \brief      Fichier de la classe de gestion de la table des champs optionels adhérents
 \author     Rodolphe Quiedville
 \author	    Jean-Louis Bergamo
 \author     Sebastien Di Cintio
 \author     Benoit Mortier
 \version    $Revision$
 */

/*! \class AdherentOptions
 \brief      Classe de gestion de la table des champs optionels adhérents
 */

class AdherentOptions
{
	var $id;
	var $db;
	/*
	 * Tableau contenant le nom des champs en clef et la definition de
	 * ces champs
	 */
	var $attribute_name;
	/*
	 * Tableau contenant le nom des champs en clef et le label de ces
	 * champs en value
	 */
	var $attribute_label;

	var $error;
	/*
	 * Constructor
	 *
	 */

	/*!
		\brief AdherentOptions
		\param DB			base de données
		\param id			id de l'adhérent
		*/

	function AdherentOptions($DB, $id='')
	{
		$this->db = $DB ;
		$this->id = $id;
		$this->error = array();
		$this->attribute_name = array();
		$this->attribute_label = array();
	}

	/*!
		\brief fonction qui imprime un liste d'erreurs
		*/
	function print_error_list()
	{
		$num = sizeof($this->error);
		for ($i = 0 ; $i < $num ; $i++)
		{
			print "<li>" . $this->error[$i];
		}
	}

	/*!
		\brief fonction qui vérifie les données entrées
		\param	minimum
		*/
	function check($minimum=0)
	{
		$err = 0;

		if (strlen(trim($this->societe)) == 0)
		{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	  {
	  	$error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	  	$err++;
	  }
		}

		if (strlen(trim($this->adresse)) == 0)
		{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
		}

		/*
		 * Return errors
		 *
		 */

		if ($err)
		{
	  $this->error = $error_string;
	  return 0;
		}
		else
		{
	  return 1;
		}

	}

	/**
		\brief  fonction qui crée un attribut optionnel
		\param	attrname			nom de l'atribut
		\param	type				type de l'attribut
		\param	length				longuer de l'attribut

		\remarks	Ceci correspond a une modification de la table et pas a un rajout d'enregistrement
		*/
	function create($attrname,$type='varchar',$length=255) {

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."adherent_options ";
			switch ($type){
				case 'varchar' :
				case 'interger' :
					$sql .= " ADD $attrname $type($length)";
					break;
				case 'text' :
				case 'date' :
				case 'datetime' :
					$sql .= " ADD $attrname $type";
					break;
				default:
					$sql .= " ADD $attrname $type";
					break;
			}

			dolibarr_syslog("AdherentOptions::create sql=".$sql);
			if ($this->db->query($sql))
			{
				return 1;
			}
			else
			{
				dolibarr_print_error($this->db);
				return 0;
			}
		}else{
			return 0;
		}
	}

	/**
		\brief fonction qui crée un label
		\param	attrname			nom de l'atribut
		\param	label				nom du label
		*/
	function create_label($attrname,$label='')
	{

		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname))
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_options_label SET ";
			$escaped_label=mysql_escape_string($label);
			$sql .= " name='$attrname',label='".addslashes($escaped_label)."'";

			dolibarr_syslog("AdherentOptions::create_label sql=".$sql);
			if ($this->db->query($sql))
			{
				return 1;
			}
			else
			{
				print dolibarr_print_error($this->db);
				return 0;
			}
		}
	}

	/*!
		\brief fonction qui supprime un attribut
		\param	attrname			nom de l'atribut
		*/

	function delete($attrname)
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname)){
			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."adherent_options DROP COLUMN $attrname";

			if ( $this->db->query( $sql) )
			{
				return $this->delete_label($attrname);
			}
			else
			{
				print dolibarr_print_error($this->db);
				return 0;
			}
		}else{
			return 0;
		}

	}

	/*!
		\brief fonction qui supprime un label
		\param	attrname			nom du label
		*/

	function delete_label($attrname)
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname)){
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options_label WHERE name='$attrname'";

			if ( $this->db->query( $sql) )
			{
				return 1;
			}
			else
			{
				print dolibarr_print_error($this->db);
				return 0;
			}
		}else{
			return 0;
		}

	}

	/*!
		\brief fonction qui modifie un attribut optionnel
		\param	attrname			nom de l'atribut
		\param	type					type de l'attribut
		\param	length				longuer de l'attribut
		*/

	function update($attrname,$type='varchar',$length=255)
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname)){
			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."adherent_options ";
			switch ($type){
				case 'varchar' :
				case 'interger' :
					$sql .= " MODIFY COLUMN $attrname $type($length)";
					break;
				case 'text' :
				case 'date' :
				case 'datetime' :
					$sql .= " MODIFY COLUMN $attrname $type";
					break;
				default:
					$sql .= " MODIFY COLUMN $attrname $type";
					break;
			}
			//$sql .= "MODIFY COLUMN $attrname $type($length)";

			if ( $this->db->query( $sql) )
			{
				return 1;
			}
			else
			{
				print dolibarr_print_error($this->db);
				return 0;
			}
		}else{
			return 0;
		}

	}

	/*!
		\brief fonction qui modifie un label
		\param	attrname			nom de l'atribut
		\param	label					nom du label
		*/

	function update_label($attrname,$label='')
	{
		if (isset($attrname) && $attrname != '' && preg_match("/^\w[a-zA-Z0-9-_]*$/",$attrname)){
			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."adherent_options_label WHERE name =
			'$attrname';";
			$this->db->query($sql_del);
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_options_label (name,label)
			VALUES ('$attrname','".addslashes($escaped_label)."')";
			//$sql = "REPLACE INTO ".MAIN_DB_PREFIX."adherent_options_label SET name='$attrname',label='$escaped_label'";

			if ( $this->db->query( $sql) )
			{
				return 1;
			}
			else
			{
				print dolibarr_print_error($this->db);
				return 0;
			}
		}else{
			return 0;
		}

	}


	/*!
		\brief fonction qui modifie un label
		*/
	function fetch_optionals()
	{
		$this->fetch_name_optionals();
		$this->fetch_name_optionals_label();
	}


	/*!
		\brief fonction qui modifie un label
		*/
	function fetch_name_optionals()
	{
		$array_name_options=array();
		$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."adherent_options";

		if ( $this->db->query( $sql) )
		{
			if ($this->db->num_rows())
			{
				while ($tab = $this->db->fetch_object())
				{
					if ($tab->Field != 'optid' && $tab->Field != 'tms' && $tab->Field != 'adhid')
	    {
	    	// we can add this attribute to adherent object
	    	$array_name_options[]=$tab->Field;
	    	$this->attribute_name[$tab->Field]=$tab->Type;
	    }
				}
				return $array_name_options;
			}else{
				return array();
			}
		}else{
			print $this->db->error();
			return array() ;
		}

	}

	/*!
		\brief fonction qui modifie un label
		*/
	function fetch_name_optionals_label()
	{
		$array_name_label=array();
		$sql = "SELECT name,label FROM ".MAIN_DB_PREFIX."adherent_options_label";

		if ( $this->db->query( $sql) )
		{
			if ($this->db->num_rows())
			{
				while ($tab = $this->db->fetch_object())
				{
	    // we can add this attribute to adherent object
	    $array_name_label[$tab->name]=stripslashes($tab->label);
	    $this->attribute_label[$tab->name]=stripslashes($tab->label);
				}
				return $array_name_label;
			}else{
				return array();
			}
		}else{
			print dolibarr_print_error($this->db);
			return array() ;
		}

	}
}
?>
