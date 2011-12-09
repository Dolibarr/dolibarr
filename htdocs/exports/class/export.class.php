<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/exports/class/export.class.php
 *	\ingroup    export
 *	\brief      File of class to manage exports
 */


/**
 *	\class 		Export
 *	\brief 		Class to manage exports
 */
class Export
{
	var $db;

	var $array_export_code=array();             // Tableau de "idmodule_numlot"
	var $array_export_module=array();           // Tableau de "nom de modules"
	var $array_export_label=array();            // Tableau de "libelle de lots"
	var $array_export_sql=array();              // Tableau des "requetes sql"
	var $array_export_fields=array();           // Tableau des listes de champ+libelle a exporter
	//var $array_export_alias=array();            // Tableau des listes de champ+alias a exporter
	var $array_export_special=array();          // Tableau des operations speciales sur champ

	// To store export modules
	var $hexa;
	var $datatoexport;
	var $model_name;

	var $sqlusedforexport;


	/**
	 *    Constructor
	 *
	 *    @param  	DoliDB		$DB		Database handler
	 */
	function Export($DB)
	{
		$this->db=$DB;
	}


	/**
	 *    Load an exportable dataset
	 *
	 *    @param  	User		$user      	Object user making export
	 *    @param  	string		$filter    	Load a particular dataset only
	 *    @return	int						<0 if KO, >0 if OK
	 */
	function load_arrays($user,$filter='')
	{
		global $langs,$conf,$mysoc;

		dol_syslog("Export::load_arrays user=".$user->id." filter=".$filter);

        $var=true;
        $i=0;

        foreach ($conf->file->dol_document_root as $type => $dirroot)
		{
			$modulesdir[] = $dirroot . "/core/modules/";

			if ($type == 'alt')
			{
				$handle=@opendir($dirroot);
				if (is_resource($handle))
				{
					while (($file = readdir($handle))!==false)
					{
					    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
					    {
					    	if (is_dir($dirroot . '/' . $file . '/core/modules/'))
					    	{
					    		$modulesdir[] = $dirroot . '/' . $file . '/core/modules/';
					    	}
					    }
					}
					closedir($handle);
				}
			}
		}

		foreach($modulesdir as $dir)
		{
			// Search available exports
			$handle=@opendir($dir);
			if (is_resource($handle))
			{
                // Search module files
			    while (($file = readdir($handle))!==false)
				{
					if (is_readable($dir.$file) && preg_match("/^(mod.*)\.class\.php$/i",$file,$reg))
					{
						$modulename=$reg[1];

						// Defined if module is enabled
						$enabled=true;
						$part=strtolower(preg_replace('/^mod/i','',$modulename));
						if (empty($conf->$part->enabled)) $enabled=false;

						if ($enabled)
						{
							// Chargement de la classe
							$file = $dir.$modulename.".class.php";
							$classname = $modulename;
							require_once($file);
							$module = new $classname($this->db);

							if (is_array($module->export_code))
							{
							    foreach($module->export_code as $r => $value)
								{
                                    //print $i.'-'.$filter.'-'.$modulename.'-'.join(',',$module->export_code).'<br>';
								    if ($filter && ($filter != $module->export_code[$r])) continue;

                                    // Test if condition to show are ok
                                    if (! empty($module->export_enabled[$r]) && ! verifCond($module->export_enabled[$r])) continue;

                                    // Test if permissions are ok
									$bool=true;
									foreach($module->export_permission[$r] as $val)
									{
    									$perm=$val;
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
    									if (! $bool) break;
									}
									//print $bool." $perm[0]"."<br>";

									// Permissions ok
									//	          if ($bool)
									//	          {
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
									$this->array_export_module[$i]=$module;
									// Permission
									$this->array_export_perms[$i]=$bool;
									// Icon
									$this->array_export_icon[$i]=(isset($module->export_icon[$r])?$module->export_icon[$r]:$module->picto);
									// Code du dataset export
									$this->array_export_code[$i]=$module->export_code[$r];
									// Libelle du dataset export
									$this->array_export_label[$i]=$module->getExportDatasetLabel($r);
									// Tableau des champ a exporter (cle=champ, valeur=libelle)
									$this->array_export_fields[$i]=$module->export_fields_array[$r];
									// Tableau des entites a exporter (cle=champ, valeur=entite)
									$this->array_export_entities[$i]=$module->export_entities_array[$r];
									// Tableau des operations speciales sur champ
									$this->array_export_special[$i]=$module->export_special_array[$r];

									// Requete sql du dataset
									$this->array_export_sql_start[$i]=$module->export_sql_start[$r];
									$this->array_export_sql_end[$i]=$module->export_sql_end[$r];
									//$this->array_export_sql[$i]=$module->export_sql[$r];

									dol_syslog("Export loaded for module ".$modulename." with index ".$i.", dataset=".$module->export_code[$r].", nb of fields=".count($module->export_fields_code[$r]));
									$i++;
									//	          }
								}
							}
						}
					}
				}
                closedir($handle);
			}
		}

		return 1;
	}


	/**
	 *      Build the sql export request.
	 *      Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *      @param      int		$indice				Indice of export
	 *      @param      array	$array_selected     Filter on array of fields to export
	 *      @return		string						SQL String. Example "select s.rowid as r_rowid, s.status as s_status from ..."
	 */
	function build_sql($indice,$array_selected)
	{
		// Build the sql request
		$sql=$this->array_export_sql_start[$indice];
		$i=0;

		//print_r($array_selected);
		foreach ($this->array_export_fields[$indice] as $key => $value)
		{
			if (! array_key_exists($key, $array_selected)) continue;		// Field not selected

			if ($i > 0) $sql.=', ';
			else $i++;
			$newfield=$key.' as '.str_replace(array('.', '-'),'_',$key);;

			$sql.=$newfield;
		}
		$sql.=$this->array_export_sql_end[$indice];

		return $sql;
	}

	/**
	 *      Build export file.
	 *      File is built into directory $conf->export->dir_temp.'/'.$user->id
	 *      Arrays this->array_export_xxx are already loaded for required datatoexport
	 *
	 *      @param      User		$user               User that export
	 *      @param      string		$model              Export format
	 *      @param      string		$datatoexport       Name of dataset to export
	 *      @param      array		$array_selected     Filter on array of fields to export
	 *      @param		string		$sqlquery			If set, transmit a sql query instead of building it from arrays
	 *      @return		int								<0 if KO, >0 if OK
	 */
	function build_file($user, $model, $datatoexport, $array_selected, $sqlquery = '')
 	{
		global $conf,$langs;

		$indice=0;
		asort($array_selected);

		dol_syslog("Export::build_file $model, $datatoexport, $array_selected");

		// Check parameters or context properties
		if (! is_array($this->array_export_fields[$indice]))
		{
			$this->error="ErrorBadParameter";
			return -1;
		}

		// Creation de la classe d'export du model ExportXXX
		$dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once($dir.$file);
		$objmodel = new $classname($this->db);

		if ($sqlquery) $sql = $sqlquery;
        else $sql=$this->build_sql($indice,$array_selected);

		// Run the sql
		$this->sqlusedforexport=$sql;
		dol_syslog("Export::build_file sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			//$this->array_export_label[$indice]
			$filename="export_".$datatoexport;
			$filename.='.'.$objmodel->getDriverExtension();
			$dirname=$conf->export->dir_temp.'/'.$user->id;

			$outputlangs=dol_clone($langs);	// We clone to have an object we can modify (for example to change output charset by csv handler) without changing original value

			// Open file
			dol_mkdir($dirname);
			$result=$objmodel->open_file($dirname."/".$filename, $outputlangs);

			if ($result >= 0)
			{
				// Genere en-tete
				$objmodel->write_header($outputlangs);

				// Genere ligne de titre
				$objmodel->write_title($this->array_export_fields[$indice],$array_selected,$outputlangs);

				while ($objp = $this->db->fetch_object($resql))
				{
					$var=!$var;

					// Process special operations
					if (! empty($this->array_export_special[$indice]))
					{
						foreach ($this->array_export_special[$indice] as $key => $value)
						{
							if (! array_key_exists($key, $array_selected)) continue;		// Field not selected
							// Operation NULLIFNEG
							if ($this->array_export_special[$indice][$key]=='NULLIFNEG')
							{
								//$alias=$this->array_export_alias[$indice][$key];
								$alias=str_replace(array('.', '-'),'_',$key);
								if ($objp->$alias < 0) $objp->$alias='';
							}
							// Operation ZEROIFNEG
							if ($this->array_export_special[$indice][$key]=='ZEROIFNEG')
							{
								//$alias=$this->array_export_alias[$indice][$key];
								$alias=str_replace(array('.', '-'),'_',$key);
								if ($objp->$alias < 0) $objp->$alias='0';
							}
						}
					}
					// end of special operation processing

					$objmodel->write_record($array_selected,$objp,$outputlangs);
				}

				// Genere en-tete
				$objmodel->write_footer($outputlangs);

				// Close file
				$objmodel->close_file();

        		return 1;
			}
			else
			{
				$this->error=$objmodel->error;
				dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." - sql=".$sql;
			dol_syslog("Export::build_file Error: ".$this->error, LOG_ERR);
			return -1;
		}
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

		dol_syslog("Export.class.php::create");

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'export_model (';
		$sql.= 'label, type, field)';
		$sql.= " VALUES ('".$this->model_name."', '".$this->datatoexport."', '".$this->hexa."')";

		dol_syslog("Export::create sql=".$sql, LOG_DEBUG);
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
			dol_syslog("Export::create error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load an export profil from database
	 *
	 *  @param		int		$id		Id of profil to load
	 *  @return		int				<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = 'SELECT em.rowid, em.field, em.label, em.type';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'export_model as em';
		$sql.= ' WHERE em.rowid = '.$id;

		dol_syslog("Export::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id                   = $obj->rowid;
				$this->hexa                 = $obj->field;
				$this->model_name           = $obj->label;
				$this->datatoexport         = $obj->type;

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
	 *	@param      User		$user        	User that delete
	 *  @param      int			$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return		int							<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."export_model";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				//// Call triggers
				//include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
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
