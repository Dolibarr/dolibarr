<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/imports/class/import.class.php
 *	\ingroup    import
 *	\brief      File of class to manage imports
 */

/**
 *	\class 		Import
 *	\brief 		Class to manage imports
 */
class Import
{
	var $array_import_module;
	var $array_import_perms;
	var $array_import_icon;
	var $array_import_code;
	var $array_import_label;
	var $array_import_tables;
	var $array_import_fields;
	var $array_import_entities;
	var $array_import_regex;
	var $array_import_examplevalues;
	var $array_import_convertvalue;


	/**
	 *    Constructor
	 *
	 *    @param  	DoliDB		$DB		Database handler
	 */
	function Import($DB)
	{
		$this->db=$DB;
	}


	/**
	 *  Load description of an importable dataset
	 *
	 *  @param		User	$user      	Object user making import
	 *  @param  	string	$filter		Load a particular dataset only
 	 *  @return		int					<0 if KO, >0 if OK
	 */
	function load_arrays($user,$filter='')
	{
		global $langs,$conf;

		dol_syslog("Import::load_arrays user=".$user->id." filter=".$filter);

        $var=true;
        $i=0;

		//$dir=DOL_DOCUMENT_ROOT."/includes/modules";
		foreach($conf->file->dol_document_root as $dirroot)
		{
			$dir = $dirroot.'/includes/modules';

			// Search available exports
			$handle=@opendir($dir);
			if (is_resource($handle))
			{
				// Search module files
				while (($file = readdir($handle))!==false)
				{
					if (preg_match("/^(mod.*)\.class\.php/i",$file,$reg))
					{
						$modulename=$reg[1];

						// Defined if module is enabled
						$enabled=true;
						$part=strtolower(preg_replace('/^mod/i','',$modulename));
						if (empty($conf->$part->enabled)) $enabled=false;

						if ($enabled)
						{
							// Chargement de la classe
							$file = $dir."/".$modulename.".class.php";
							$classname = $modulename;
							require_once($file);
							$module = new $classname($this->db);

							if (is_array($module->import_code))
							{
								foreach($module->import_code as $r => $value)
								{
									if ($filter && ($filter != $module->import_code[$r])) continue;

									// Test if permissions are ok
									/*$perm=$module->import_permission[$r][0];
									//print_r("$perm[0]-$perm[1]-$perm[2]<br>");
									if ($perm[2])
									{
									$bool=$user->rights->$perm[0]->$perm[1]->$perm[2];
									}
									else
									{
									$bool=$user->rights->$perm[0]->$perm[1];
									}
									if ($perm[0]=='user' && $user->admin) $bool=true;
									//print $bool." $perm[0]"."<br>";
									*/

									// Permissions ok
									//	                        if ($bool)
									//	                        {
									// Charge fichier lang en rapport
									$langtoload=$module->getLangFilesArray();
									if (is_array($langtoload))
									{
										foreach($langtoload as $key)
										{
											$langs->load($key);
										}
									}

									// Module
									$this->array_import_module[$i]=$module;
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
									// Array of tables creator field to import (key=alias, value=creator field)
									$this->array_import_tables_creator[$i]=$module->import_tables_creator_array[$r];
									// Array of fiels to import (key=field, value=label)
									$this->array_import_fields[$i]=$module->import_fields_array[$r];
									// Tableau des entites a exporter (cle=champ, valeur=entite)
									$this->array_import_entities[$i]=$module->import_entities_array[$r];
									// Tableau des alias a exporter (cle=champ, valeur=alias)
									$this->array_import_regex[$i]=$module->import_regex_array[$r];
									// Tableau des alias a exporter (cle=champ, valeur=exemple)
									$this->array_import_examplevalues[$i]=$module->import_examplevalues_array[$r];
									// Tableau des regles de conversion d'une valeur depuis une autre source (cle=champ, valeur=tableau des regles)
									$this->array_import_convertvalue[$i]=$module->import_convertvalue_array[$r];

									dol_syslog("Import loaded for module ".$modulename." with index ".$i.", dataset=".$module->import_code[$r].", nb of fields=".count($module->import_fields_code[$r]));
									$i++;
									//	                        }
								}
							}
						}
					}
				}
			}
		}
		closedir($handle);
		return 1;
	}



	/**
	 *  Build an import example file.
	 *  Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *  @param      User	$user               User qui exporte
	 *  @param      string	$model              Modele d'export
	 *  @param      string	$headerlinefields   Array of values for first line of example file
	 *  @param      string	$contentlinevalues	Array of values for content line of example file
	 *  @return		string						<0 if KO, >0 if OK
	 */
	function build_example_file($user, $model, $headerlinefields, $contentlinevalues)
	{
		global $conf,$langs;

		$indice=0;

		dol_syslog("Import::build_example_file ".$model);

		// Creation de la classe d'import du model Import_XXX
		$dir = DOL_DOCUMENT_ROOT . "/includes/modules/import/";
		$file = "import_".$model.".modules.php";
		$classname = "Import".$model;
		require_once($dir.$file);
		$objmodel = new $classname($this->db);

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

		dol_syslog("Import::create sql=".$sql, LOG_DEBUG);
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
			dol_syslog("Import::create error ".$this->error, LOG_ERR);
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

		dol_syslog("Import::fetch sql=".$sql, LOG_DEBUG);
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

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('IMPORT_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
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
?>
