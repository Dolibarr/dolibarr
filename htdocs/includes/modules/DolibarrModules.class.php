<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       	htdocs/includes/modules/DolibarrModules.class.php
 *	\brief 			Fichier de description et activation des modules Dolibarr
 *	\version		$Id$
 */


/**
 *	\class      DolibarrModules
 *	\brief      Classe mere des classes de description et activation des modules Dolibarr
 */
class DolibarrModules
{
	//! Database handler
	var $db;
	//! Relative path to module style sheet
	var $style_sheet = '';
	//! Path to create when module activated
	var $dirs = array();
	//! Tableau des boites
	var $boxes;
	//! Tableau des constantes
	var $const;
	//! Tableau des droits
	var $rights;
	//! Tableau des menus
	var $menu=array();
	//! Tableau des documents ???
	var $docs;

	var $dbversion;


	/**
	 *      \brief      Constructeur
	 *      \param      DB      handler d'acces base
	 */
	function DolibarrModules($DB)
	{
		$this->db = $DB ;
		$this->dbversion = "-";
	}


	/**
	 *      \brief      Fonction d'activation. Insere en base les constantes et boites du module
	 *      \param      array_sql       Tableau de requete sql a executer a l'activation
	 *      \param		options			Options when enabling module
	 * 		\return     int             1 if OK, 0 if KO
	 */
	function _init($array_sql, $options='')
	{
		global $langs;
		$err=0;

		$this->db->begin();

		// Insert line in module table
		if (! $err) $err+=$this->_dbactive();

		// Insert activation module constant
		if (! $err) $err+=$this->_active();

		// Insere le nom de la feuille de style
		if (! $err) $err+=$this->insert_style_sheet();

		// Insert new pages for tabs into llx_const
		if (! $err) $err+=$this->insert_tabs();

		// Insere les constantes associees au module dans llx_const
		if (! $err) $err+=$this->insert_const();

		// Insere les boites dans llx_boxes_def
		if (! $err && $options != 'noboxes') $err+=$this->insert_boxes();

		// Insere les permissions associees au module actif dans llx_rights_def
		if (! $err) $err+=$this->insert_permissions();

		// Insere les constantes associees au module dans llx_const
		if (! $err) $err+=$this->insert_menus();
		
		// Insert module's directories into llx_const and create dir
		if (! $err) $err+=$this->insert_dirs();

		// Execute les requetes sql complementaires
		for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
		{
			if (! $err)
			{
				$sql=$array_sql[$i];

				dol_syslog("DolibarrModules::_init sql=".$sql, LOG_DEBUG);
				$result=$this->db->query($sql);
				if (! $result)
				{
					$this->error=$this->db->error();
					dol_syslog("DolibarrModules::_init Error ".$this->error, LOG_ERR);
					$err++;
				}
			}
		}

		// Return code
		if (! $err)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 *  \brief      Fonction de desactivation. Supprime de la base les constantes et boites du module
	 *  \param      array_sql       tableau de requete sql a executer a la desactivation
	 *  \param		options			Options when disabling module
	 *  \return     int             1 if OK, 0 if KO
	 */
	function _remove($array_sql, $options='')
	{
		global $langs;
		$err=0;

		$this->db->begin();

		// Remove line in activation module
		if (! $err) $err+=$this->_dbunactive();

		// Remove activation module line
		if (! $err) $err+=$this->_unactive();

		// Remove activation of module's style sheet
		if (! $err) $err+=$this->delete_style_sheet();

		// Remove activation of module's new tabs
		if (! $err) $err+=$this->delete_tabs();

		// Remove list of module's available boxes
		if (! $err && $options != 'noboxes') $err+=$this->delete_boxes();

		// Remove module's permissions from list of available permissions
		if (! $err) $err+=$this->delete_permissions();

		// Remove module's menus
		if (! $err) $err+=$this->delete_menus();
		
		// Remove module's directories
		if (! $err) $err+=$this->delete_dirs();

		// Run complementary sql requests
		for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
		{
			if (! $err)
			{
				if (!$this->db->query($array_sql[$i]))
		  		{
		  			$err++;
		  		}
			}
		}

		// Return code
		if (! $err)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return 0;
		}
	}


	/**
	 *	\brief      Retourne le nom traduit du module si la traduction existe dans admin.lang,
	 *				sinon le nom defini par defaut dans le module.
	 *	\return     string      Nom du module traduit
	 */
	function getName()
	{
		global $langs;
		$langs->load("admin");

		if ($langs->trans("Module".$this->numero."Name") != ("Module".$this->numero."Name"))
		{
			// Si traduction du nom du module existe
			return $langs->trans("Module".$this->numero."Name");
		}
		else
		{
			// If translation of module with its numero does not exists, we take its name
			return $this->name;
		}
	}


	/**
	 *	\brief      Retourne la description traduite du module si la traduction existe dans admin.lang,
	 *				sinon la description definie par defaut dans le module.
	 *	\return     string      Nom du module traduit
	 */
	function getDesc()
	{
		global $langs;
		$langs->load("admin");

		if ($langs->trans("Module".$this->numero."Desc") != ("Module".$this->numero."Desc"))
		{
			// Si traduction de la description du module existe
			return $langs->trans("Module".$this->numero."Desc");
		}
		else
		{
			// Si traduction de la description du module n'existe pas, on prend definition en dur dans module
			return $this->description;
		}
	}


	/**
	 *	\brief      Retourne la version du module.
	 *				Pour les modules a l'etat 'experimental', retourne la traduction de 'experimental'
	 *				Pour les modules 'dolibarr', retourne la version de Dolibarr
	 *				Pour les autres modules, retourne la version du module
	 *	\return     string      Version du module
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'development') return $langs->trans("VersionDevelopment");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("VersionUnknown");
	}


	/**
	 *	\brief      Return version of module stored in database table.
	 *	\return     string      Version of module
	 * 	\remarks	This function is not used but is kept in code because it can be used later.
	 */
	function getDbVersion()
	{
		global $langs;
		$langs->load("admin");

		$sql ="SELECT active_version FROM ".MAIN_DB_PREFIX."dolibarr_modules";
		$sql .= " WHERE numero=".$this->numero." AND active = 1";

		dol_syslog("DolibarrModules::getDbVersion sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			if ($num > 0)
			{
				$row = $this->db->fetch_row($resql);

				$this->dbversion = $row[0];
			}

			$this->db->free($resql);
		}

		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'development') return $langs->trans("VersionDevelopment");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return "";

	}


	/**
	 *	\brief      Retourne la liste des fichiers lang en rapport avec le module
	 *	\return     array       Tableau des fichier lang
	 */
	function getLangFilesArray()
	{
		return $this->langfiles;
	}

	/**
	 *	\brief      Retourne le libelle d'un lot de donnees exportable
	 *	\return     string      Libelle du lot de donnees
	 */
	function getDatasetLabel($r)
	{
		global $langs;

		$langstring="ExportDataset_".$this->export_code[$r];
		if ($langs->trans($langstring) == $langstring)
		{
			// Traduction non trouvee
			return $langs->trans($this->export_label[$r]);
		}
		else
		{
			// Traduction trouvee
			return $langs->trans($langstring);
		}
	}


	/**
	 *	\brief      Insert line in dolibarr_modules table.
	 *	\return     int		Nb of errors (0 if OK)
	 * 	\remarks	Storage is made for information only, table is not required for Dolibarr usage.
	 */
	function _dbactive()
	{
		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."dolibarr_modules WHERE numero=".$this->numero.";";
		dol_syslog("DolibarrModules::_dbactive sql=".$sql, LOG_DEBUG);
		$this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarr_modules (numero,active,active_date,active_version)";
		$sql.= " VALUES (";
		$sql.= $this->numero.",1,".$this->db->idate(gmmktime()).",'".$this->version."')";
		dol_syslog("DolibarrModules::_dbactive sql=".$sql, LOG_DEBUG);
		$this->db->query($sql);

		return $err;
	}


	/**
	 *	\brief      Remove line in dolibarr_modules table
	 *	\return     int     Nb of errors (0 if OK)
	 * 	\remarks	Storage is made for information only, table is not required for Dolibarr usage.
	 */
	function _dbunactive()
	{
		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."dolibarr_modules WHERE numero=".$this->numero;
		dol_syslog("DolibarrModules::_dbunactive sql=".$sql, LOG_DEBUG);
		$this->db->query($sql);

		return $err;
	}


	/**
	 *	\brief      Insert constant to activate module
	 *	\return     int     Nb of errors (0 if OK)
	 */
	function _active()
	{
		global $conf;

		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name = '".$this->const_name."'";
		$sql.= " AND entity = ".$conf->entity;
		dol_syslog("DolibarrModules::_active sql=".$sql, LOG_DEBUG);
		$this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible,entity) VALUES";
    	$sql.= " ('".$this->const_name."','1',0,".$conf->entity.")";
		dol_syslog("DolibarrModules::_active sql=".$sql, LOG_DEBUG);
		if (!$this->db->query($sql))
		{
			$err++;
		}

		return $err;
	}


	/**
	 *	\brief      Remove activation line
	 *	\return     int     Nb of errors (0 if OK)
	 **/
	function _unactive()
	{
		global $conf;

		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name = '".$this->const_name."'";
		$sql.= " AND entity = ".$conf->entity;
		dol_syslog("DolibarrModules::_unactive sql=".$sql);
		$this->db->query($sql);

		return $err;
	}


	/**
	 *		\brief		Create tables and keys required by module
	 * 					Files module.sql and module.key.sql with create table and create keys
	 * 					commands must be stored in directory reldir='/module/sql/'
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function _load_tables($reldir)
	{
		global $db,$conf;

		include_once(DOL_DOCUMENT_ROOT ."/lib/admin.lib.php");

		$ok = 1;
		foreach($conf->dol_document_root as $dirroot)
		{
			if ($ok)
			{
				$dir = $dirroot.$reldir;
				$ok = 0;

				// Run llx_mytable.sql files
				$handle=@opendir($dir);			// Dir may not exists
				if ($handle)
				{
					while (($file = readdir($handle))!==false)
					{
						if (eregi('\.sql$',$file) && substr($file,0,4) == 'llx_' && substr($file, -8) <> '.key.sql')
						{
							$result=run_sql($dir.$file,1);
						}
					}
					closedir($handle);
				}

				// Run llx_mytable.key.sql files
				$handle=@opendir($dir);			// Dir may not exist
				if ($handle)
				{
					while (($file = readdir($handle))!==false)
					{
						if (eregi('\.sql$',$file) && substr($file,0,4) == 'llx_' && substr($file, -8) == '.key.sql')
						{
							$result=run_sql($dir.$file,1);
						}
					}
					closedir($handle);
				}

				if ($error == 0)
				{
					$ok = 1;
				}
			}
		}

		return $ok;
	}


	/**
	 *	\brief      Insere les boites associees au module dans llx_boxes_def
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_boxes()
	{
		global $conf;
		
		$err=0;

		if (is_array($this->boxes))
		{
			foreach ($this->boxes as $key => $value)
			{
				//$titre = $this->boxes[$key][0];
				$file  = isset($this->boxes[$key][1])?$this->boxes[$key][1]:'';
				$note  = isset($this->boxes[$key][2])?$this->boxes[$key][2]:'';

				$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."boxes_def";
				$sql.= " WHERE file = '".$file."'";
				$sql.= " AND entity = ".$conf->entity;
				if ($note) $sql.=" AND note ='".addslashes($note)."'";

				$result=$this->db->query($sql);
				if ($result)
				{
					$row = $this->db->fetch_row($result);
					if ($row[0] == 0)
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file,entity,note)";
						$sql.= " VALUES ('".addslashes($file)."',";
						$sql.= $conf->entity.",";
						$sql.= $note?"'".addslashes($note)."'":"null";
						$sql.= ")";
						dol_syslog("DolibarrModules::insert_boxes sql=".$sql);
						if (! $this->db->query($sql))
						{
							$err++;
						}
					}
				}
				else
				{
					$err++;
				}
			}
		}

		return $err;
	}


	/**
	 *	\brief      Supprime les boites
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_boxes()
	{
		global $conf;
		
		$err=0;

		if (is_array($this->boxes))
		{
			foreach ($this->boxes as $key => $value)
			{
				//$titre = $this->boxes[$key][0];
				$file  = $this->boxes[$key][1];
				//$note  = $this->boxes[$key][2];

				$sql = "DELETE ".MAIN_DB_PREFIX."boxes";
				$sql.= " FROM ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
				$sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
				$sql.= " AND ".MAIN_DB_PREFIX."boxes_def.file = '".addslashes($file)."'";
				$sql.= " AND ".MAIN_DB_PREFIX."boxes_def.entity = ".$conf->entity;
				dol_syslog("DolibarrModules::delete_boxes sql=".$sql);
				$this->db->query($sql);

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
				$sql.= " WHERE file = '".addslashes($file)."'";
				$sql.= " AND entity = ".$conf->entity;
				dol_syslog("DolibarrModules::delete_boxes sql=".$sql);
				if (! $this->db->query($sql))
				{
					$err++;
				}
			}
		}

		return $err;
	}

	/**
	 *	\brief      Desactive feuille de style du module par suppression ligne dans llx_const
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_style_sheet()
	{
		global $conf;
		
		$err=0;

		if ($this->style_sheet)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
			$sql.= " WHERE name = '".$this->const_name."_CSS'";
			$sql.= " AND entity = ".$conf->entity;
			dol_syslog("DolibarrModules::delete_style_sheet sql=".$sql);
			if (! $this->db->query($sql))
			{
				$err++;
			}
		}

		return $err;
	}

	/**
	 *	\brief      Remove links to new module page present in llx_const
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_tabs()
	{
		global $conf;
		
		$err=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name like '".$this->const_name."_TABS_%'";
		$sql.= " AND entity = ".$conf->entity;
		dol_syslog("DolibarrModules::delete_tabs sql=".$sql);
		if (! $this->db->query($sql))
		{
			$err++;
		}

		return $err;
	}

	/**
	 *	\brief      Active la feuille de style associee au module par insertion ligne dans llx_const
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_style_sheet()
	{
		global $conf;
		
		$err=0;

		if ($this->style_sheet)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
			$sql.= " VALUES ('".$this->const_name."_CSS','chaine','".$this->style_sheet."','Style sheet for module ".$this->name."','0',".$conf->entity.")";
			dol_syslog("DolibarrModules::insert_style_sheet sql=".$sql);
			$resql=$this->db->query($sql);
			/* Allow duplicate key
			 if (! $resql)
			 {
				$err++;
				}
				*/
		}

		return $err;
	}

	/**
	 *	\brief      Add links of new pages from modules in llx_const
	 *	\return     int     Number of errors (0 if ok)
	 */
	function insert_tabs()
	{
		global $conf;
		
		$err=0;

		if (! empty($this->tabs))
		{
			$i=0;
			foreach ($this->tabs as $key => $value)
			{
				if ($value)
				{
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
					$sql.= " VALUES ('".$this->const_name."_TABS_".$i."','chaine','".$value."',null,'0',".$conf->entity.")";
					dol_syslog("DolibarrModules::insert_tabs sql=".$sql);
					$resql=$this->db->query($sql);
					/* Allow duplicate key
					 if (! $resql)
					 {
						$err++;
					}
					*/
				}
				$i++;
			}
		}
		return $err;
	}

	/**
	 *	\brief      Insere les constantes associees au module dans llx_const
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_const()
	{
		global $conf;
		
		$err=0;

		foreach ($this->const as $key => $value)
		{
			$name   = $this->const[$key][0];
			$type   = $this->const[$key][1];
			$val    = $this->const[$key][2];
			$note   = $this->const[$key][3];
			$visible= $this->const[$key][4];

			$sql = "SELECT count(*)";
			$sql.= " FROM ".MAIN_DB_PREFIX."const";
			$sql.= " WHERE name ='".$name."'";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$row = $this->db->fetch_row($result);

				if ($row[0] == 0)
				{
					if (! $visible) $visible='0';
					if (strlen($note))
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
						$sql.= " VALUES ('".$name."','".$type."','".$val."','".$note."','".$visible."',".$conf->entity.")";
					}
					elseif (strlen($val))
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,visible,entity)";
						$sql.= " VALUES ('".$name."','".$type."','".$val."','".$visible."',".$conf->entity.")";
					}
					else
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,visible,entity)";
						$sql.= " VALUES ('".$name."','".$type."','".$visible."',".$conf->entity.")";
					}

					dol_syslog("DolibarrModules::insert_const sql=".$sql);
					if (! $this->db->query($sql) )
					{
						dol_syslog("DolibarrModules::insert_const ".$this->db->lasterror(), LOG_ERR);
						$err++;
					}
				}
			}
			else
			{
				$err++;
			}
		}

		return $err;
	}

	/**
	 \brief      Insere les permissions associees au module dans llx_rights_def
	 \return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_permissions()
	{
		global $conf;
		
		$err=0;

		//print $this->rights_class." ".sizeof($this->rights)."<br>";

		// Test si module actif
		$sql_del = "SELECT value FROM ".MAIN_DB_PREFIX."const";
		$sql_del.= " WHERE name = '".$this->const_name."'";
		$sql_del.= " AND entity IN (0,".$conf->entity.")";
		$resql=$this->db->query($sql_del);
		if ($resql) {

			$obj=$this->db->fetch_object($resql);
			if ($obj->value) {

				// Si module actif
				foreach ($this->rights as $key => $value)
				{
					$r_id       = $this->rights[$key][0];
					$r_desc     = $this->rights[$key][1];
					$r_type     = $this->rights[$key][2];
					$r_def      = $this->rights[$key][3];
					$r_perms    = $this->rights[$key][4];
					$r_subperms = isset($this->rights[$key][5])?$this->rights[$key][5]:'';
					$r_modul    = $this->rights_class;

					if (empty($r_type)) $r_type='w';

					if (strlen($r_perms) )
					{
						if (strlen($r_subperms) )
						{
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
							$sql .= " (id, entity, libelle, module, type, bydefault, perms, subperms)";
							$sql .= " VALUES ";
							$sql .= "(".$r_id.",".$conf->entity.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."','".$r_subperms."')";
						}
						else
						{
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
							$sql .= " (id, entity, libelle, module, type, bydefault, perms)";
							$sql .= " VALUES ";
							$sql .= "(".$r_id.",".$conf->entity.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."')";
						}
					}
					else
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
						$sql .= " (id, entity, libelle, module, type, bydefault)";
						$sql .= " VALUES ";
						$sql .= "(".$r_id.",".$conf->entity.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.")";
					}

					dol_syslog("DolibarrModules::insert_permissions sql=".$sql, LOG_DEBUG);
					$resql=$this->db->query($sql);
					if (! $resql)
					{
						if ($this->db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS") {
							dol_syslog("DolibarrModules::insert_permissions error ".$this->db->lasterror(), LOG_ERR);
							$err++;
						}
					}
				}
			}
		}

		return $err;
	}


	/**
	 \brief      Supprime les permissions
	 \return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_permissions()
	{
		global $conf;
		
		$err=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def";
		$sql.= " WHERE module = '".$this->rights_class."'";
		$sql.= " AND entity = ".$conf->entity;
		dol_syslog("DolibarrModules::delete_permissions sql=".$sql);
		if (!$this->db->query($sql))
		{
			$err++;
		}

		return $err;
	}


	/**
	 *	\brief      Insere les menus dans llx_menu*
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_menus()
	{
		global $user;

		require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");

		$err=0;

		$this->db->begin();

		//var_dump($this->menu); exit;
		foreach ($this->menu as $key => $value)
		{
			$menu = new Menubase($this->db);
			$menu->menu_handler='all';
			$menu->module=$this->rights_class;
			if (! $this->menu[$key]['fk_menu'])
			{
				$menu->fk_menu=0;
				//print 'aaa'.$this->menu[$key]['fk_menu'];
			}
			else
			{
				//print 'xxx'.$this->menu[$key]['fk_menu'];exit;
				$numparent=$this->menu[$key]['fk_menu'];
				$numparent=eregi_replace('r=','',$numparent);
				if (isset($this->menu[$numparent]['rowid']))
				{
					$menu->fk_menu=$this->menu[$numparent]['rowid'];
				}
				else
				{
					$this->error="BadDefinitionOfMenuArrayInModuleDescriptor";
					dol_syslog("DolibarrModules::insert_menus ".$this->error." ".$this->menu[$key]['fk_menu'], LOG_ERR);
					$err++;
				}
			}
			$menu->type=$this->menu[$key]['type'];
			$menu->mainmenu=$this->menu[$key]['mainmenu'];
			$menu->titre=$this->menu[$key]['titre'];
			$menu->leftmenu=$this->menu[$key]['leftmenu'];
			$menu->url=$this->menu[$key]['url'];
			$menu->langs=$this->menu[$key]['langs'];
			$menu->position=$this->menu[$key]['position'];
			$menu->perms=$this->menu[$key]['perms'];
			$menu->target=$this->menu[$key]['target'];
			$menu->user=$this->menu[$key]['user'];
			//$menu->constraint=$this->menu[$key]['constraint'];
			$menu->enabled=$this->menu[$key]['enabled'];
			if (! $err)
			{
				$result=$menu->create($user);
				if ($result > 0)
				{
					$this->menu[$key]['rowid']=$result;
				}
				else
				{
					$this->error=$menu->error;
					dol_syslog('DolibarrModules::insert_menus result='.$result." ".$this->error, LOG_ERR);
					$err++;
					break;
				}
			}
		}

		if (! $err)
		{
			$this->db->commit();
		}
		else
		{
			dol_syslog("DolibarrModules::insert_menus ".$this->error, LOG_ERR);
			$this->db->rollback();
		}

		return $err;
	}


	/**
	 *	\brief      Remove menus entries
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_menus()
	{
		global $conf;
		
		$err=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
		$sql.= " WHERE module = '".addslashes($this->rights_class)."'";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("DolibarrModules::delete_menus sql=".$sql);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			$err++;
		}

		return $err;
	}
	
	/**
	 *	\brief      Insere et cree les répertoires output et temp
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function insert_dirs()
	{
		global $langs, $conf;
		
		$err=0;
		
		if (is_array($this->dirs))
		{
			foreach ($this->dirs as $key => $value)
			{
				$name = $this->const_name."_DIR_".strtoupper($this->dirs[$key][0]);
				$dir  = $this->dirs[$key][1];

				$sql = "SELECT count(*)";
				$sql.= " FROM ".MAIN_DB_PREFIX."const";
				$sql.= " WHERE name ='".$name."'";
				$sql.= " AND entity = ".$conf->entity;
				
				$result=$this->db->query($sql);
				if ($result)
				{
					$row = $this->db->fetch_row($result);
					
					if ($row[0] == 0)
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
						$sql.= " VALUES ('".$name."','chaine','".$dir."','Directory for module ".$this->name."','0',".$conf->entity.")";
			
						dol_syslog("DolibarrModules::insert_dir_output sql=".$sql);
						$resql=$this->db->query($sql);
						
						if ($resql)
						{
							// On defini l'entite
							$dir = DOL_DATA_ROOT."/entity_".$conf->entity.$dir;
							
							if ($dir && ! file_exists($dir))
							{
								if (create_exdir($dir) < 0)
								{
									$this->error = $langs->trans("ErrorCanNotCreateDir",$dir);
									dol_syslog("DolibarrModules::_init ".$this->error, LOG_ERR);
								}
							}
						}
					}
					else
					{
						$err++;
					}
				}
			}
		}
		
		return $err;
  }
			
	
	/**
	 *	\brief      Remove directory entries
	 *	\return     int     Nombre d'erreurs (0 si ok)
	 */
	function delete_dirs()
	{
		global $conf;
		
		$err=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name like '".$this->const_name."_DIR_%'";
		$sql.= " AND entity = ".$conf->entity;
		
		dol_syslog("DolibarrModules::delete_tabs sql=".$sql);
		if (! $this->db->query($sql))
		{
			$err++;
		}

		return $err;
	}

}
?>
