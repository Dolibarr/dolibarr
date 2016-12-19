<?php
/* Copyright (C) 2011       Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/imports/class/import.class.php
 *	\ingroup    import
 *	\brief      File of class to manage imports
 */

/**
 *	Class to manage imports
 */
class Import
{
	var $array_import_module;
	var $array_import_perms;
	var $array_import_icon;
	var $array_import_code;
	var $array_import_label;
	var $array_import_tables;
	var $array_import_tables_creator;
	var $array_import_fields;
	var $array_import_fieldshidden;
	var $array_import_entities;
	var $array_import_regex;
	var $array_import_updatekeys;
	var $array_import_examplevalues;
	var $array_import_convertvalue;
	var $array_import_run_sql_after;

	var $error;
	var $errors;
	

	/**
	 *    Constructor
	 *
	 *    @param  	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}


	/**
	 *  Load description int this->array_import_module, this->array_import_fields, ... of an importable dataset
	 *
	 *  @param		User	$user      	Object user making import
	 *  @param  	string	$filter		Load a particular dataset only. Index will start to 0.
 	 *  @return		int					<0 if KO, >0 if OK
	 */
	function load_arrays($user,$filter='')
	{
		global $langs,$conf;

		dol_syslog(get_class($this)."::load_arrays user=".$user->id." filter=".$filter);

        $var=true;
        $i=0;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $modulesdir = dolGetModulesDirs();

        // Load list of modules
        foreach($modulesdir as $dir)
        {
			$handle=@opendir(dol_osencode($dir));
			if (! is_resource($handle)) continue;

			// Search module files
			while (($file = readdir($handle))!==false)
			{
				if (! preg_match("/^(mod.*)\.class\.php/i",$file,$reg)) continue;

				$modulename=$reg[1];

				// Defined if module is enabled
				$enabled=true;
				$part=strtolower(preg_replace('/^mod/i','',$modulename));
				if (empty($conf->$part->enabled)) $enabled=false;

				if (empty($enabled)) continue;

				// Init load class
				$file = $dir."/".$modulename.".class.php";
				$classname = $modulename;
				require_once $file;
				$module = new $classname($this->db);

				if (isset($module->import_code) && is_array($module->import_code))
				{
					foreach($module->import_code as $r => $value)
					{
						if ($filter && ($filter != $module->import_code[$r])) continue;

						// Test if permissions are ok
						/*$perm=$module->import_permission[$r][0];
						//print_r("$perm[0]-$perm[1]-$perm[2]<br>");
						if ($perm[2])
						{
						$bool=$user->rights->{$perm[0]}->{$perm[1]}->{$perm[2]};
						}
						else
						{
						$bool=$user->rights->{$perm[0]}->{$perm[1]};
						}
						if ($perm[0]=='user' && $user->admin) $bool=true;
						//print $bool." $perm[0]"."<br>";
						*/

						// Load lang file
						$langtoload=$module->getLangFilesArray();
						if (is_array($langtoload))
						{
							foreach($langtoload as $key)
							{
								$langs->load($key);
							}
						}

						// Permission
						$this->array_import_perms[$i]=$user->rights->import->run;
						// Icon
						$this->array_import_icon[$i]=(isset($module->import_icon[$r])?$module->import_icon[$r]:$module->picto);
						// Code du dataset export
						$this->array_import_code[$i]=$module->import_code[$r];
						// Libelle du dataset export
						$this->array_import_label[$i]=$module->getImportDatasetLabel($r);
						// Array of tables to import (key=alias, value=tablename)
						$this->array_import_tables[$i]=$module->import_tables_array[$r];
						// Array of tables creator field to import (key=alias, value=creator field name)
						$this->array_import_tables_creator[$i]=(isset($module->import_tables_creator_array[$r])?$module->import_tables_creator_array[$r]:'');
						// Array of fields to import (key=field, value=label)
						$this->array_import_fields[$i]=$module->import_fields_array[$r];
						// Array of hidden fields to import (key=field, value=label)
						$this->array_import_fieldshidden[$i]=$module->import_fieldshidden_array[$r];
						// Tableau des entites a exporter (cle=champ, valeur=entite)
						$this->array_import_entities[$i]=$module->import_entities_array[$r];
						// Tableau des alias a exporter (cle=champ, valeur=alias)
						$this->array_import_regex[$i]=$module->import_regex_array[$r];
						// Array of columns allowed as UPDATE options
						$this->array_import_updatekeys[$i]=$module->import_updatekeys_array[$r];
						// Array of examples
						$this->array_import_examplevalues[$i]=$module->import_examplevalues_array[$r];
						// Tableau des regles de conversion d'une valeur depuis une autre source (cle=champ, valeur=tableau des regles)
						$this->array_import_convertvalue[$i]=(isset($module->import_convertvalue_array[$r])?$module->import_convertvalue_array[$r]:'');
						// Sql request to run after import
						$this->array_import_run_sql_after[$i]=(isset($module->import_run_sql_after_array[$r])?$module->import_run_sql_after_array[$r]:'');
						// Module
						$this->array_import_module[$i]=$module;

						dol_syslog("Import loaded for module ".$modulename." with index ".$i.", dataset=".$module->import_code[$r].", nb of fields=".count($module->import_fields_array[$r]));
						$i++;
					}
				}
			}
	        closedir($handle);
		}
		return 1;
	}



	/**
	 *  Build an import example file.
	 *  Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *  @param      string	$model              Name of import engine ('csv', ...)
	 *  @param      string	$headerlinefields   Array of values for first line of example file
	 *  @param      string	$contentlinevalues	Array of values for content line of example file
	 *  @param		string	$datatoimport		Dataset to import
	 *  @return		string						<0 if KO, >0 if OK
	 */
	function build_example_file($model, $headerlinefields, $contentlinevalues,$datatoimport)
	{
		global $conf,$langs;

		$indice=0;

		dol_syslog(get_class($this)."::build_example_file ".$model);

		// Creation de la classe d'import du model Import_XXX
		$dir = DOL_DOCUMENT_ROOT . "/core/modules/import/";
		$file = "import_".$model.".modules.php";
		$classname = "Import".$model;
		require_once $dir.$file;
		$objmodel = new $classname($this->db,$datatoimport);

		$outputlangs=$langs;	// Lang for output
		$s='';

		// Genere en-tete
		$s.=$objmodel->write_header_example($outputlangs);

		// Genere ligne de titre
		$s.=$objmodel->write_title_example($outputlangs,$headerlinefields);

		// Genere ligne de titre
		$s.=$objmodel->write_record_example($outputlangs,$contentlinevalues);

		// Genere pied de page
		$s.=$objmodel->write_footer_example($outputlangs);

		return $s;
	}

	/**
	 *  Save an export model in database
	 *
	 *  @param		User	$user 	Object user that save
	 *  @return		int				<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf;

		dol_syslog("Import.class.php::create");

		// Check parameters
		if (empty($this->model_name))	{ $this->error='ErrorWrongParameters'; return -1; }
		if (empty($this->datatoimport)) { $this->error='ErrorWrongParameters'; return -1; }
		if (empty($this->hexa)) 		{ $this->error='ErrorWrongParameters'; return -1; }

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'import_model (';
		$sql.= 'fk_user, label, type, field';
		$sql.= ')';
		$sql.= " VALUES (".($user->id > 0 ? $user->id : 0).", '".$this->db->escape($this->model_name)."', '".$this->datatoimport."', '".$this->hexa."')";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->errno=$this->db->lasterrno();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load an import profil from database
	 *
	 *  @param		int		$id		Id of profil to load
	 *  @return		int				<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = 'SELECT em.rowid, em.field, em.label, em.type';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'import_model as em';
		$sql.= ' WHERE em.rowid = '.$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id                   = $obj->rowid;
				$this->hexa                 = $obj->field;
				$this->model_name           = $obj->label;
				$this->datatoimport         = $obj->type;
				$this->fk_user              = $obj->fk_user;
				return 1;
			}
			else
			{
				$this->error="Model not found";
				return -2;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -3;
		}
	}

	/**
	 *	Delete object in database
	 *
	 *	@param      User	$user        	User that delete
	 *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."import_model";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				/* Not used. This is not a business object. To convert it we must herit from CommonObject
                // Call trigger
                $result=$this->call_trigger('IMPORT_DELETE',$user);
                if ($result < 0) $error++;
                // End call triggers
                 */
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

}
