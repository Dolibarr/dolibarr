<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/exports/export.class.php
 *	\ingroup    export
 *	\brief      Fichier de la classe des exports
 *	\version    $Id$
 */


/**
 *	\class 		Export
 *	\brief 		Classe permettant la gestion des exports
 */
class Export
{
	var $db;

	var $array_export_code=array();             // Tableau de "idmodule_numlot"
	var $array_export_module=array();           // Tableau de "nom de modules"
	var $array_export_label=array();            // Tableau de "libelle de lots"
	var $array_export_sql=array();              // Tableau des "requetes sql"
	var $array_export_fields=array();           // Tableau des listes de champ+libell� � exporter
	var $array_export_alias=array();            // Tableau des listes de champ+alias � exporter
	var $array_export_special=array();          // Tableau des operations speciales sur champ

	// To store export modules
	var $hexa;
	var $datatoexport;
	var $model_name;

	var $sqlusedforexport;


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB        Handler acces base de donnees
	 */
	function Export($DB)
	{
		$this->db=$DB;
	}


	/**
	 *    \brief  Load an exportable dataset
	 *    \param  user      Object user making export
	 *    \param  filter    Code export pour charger un lot de donnees particulier
	 */
	function load_arrays($user,$filter='')
	{
		global $langs,$conf;

		dol_syslog("Export::load_arrays user=".$user->id." filter=".$filter);

		//$dir=DOL_DOCUMENT_ROOT."/includes/modules";
		foreach($conf->file->dol_document_root as $dirroot)
		{
			$dir = $dirroot.'/includes/modules';
			$handle=opendir($dir);

			// Search available exports
			$handle=@opendir($dir);
			if ($handle)
			{
				$var=True;
				$i=0;
				while (($file = readdir($handle))!==false)
				{
					if (eregi("^(mod.*)\.class\.php$",$file,$reg))
					{
						$modulename=$reg[1];

						// Defined if module is enabled
						$enabled=true;
						$part=strtolower(eregi_replace('^mod','',$modulename));
						if (empty($conf->$part->enabled)) $enabled=false;

						if ($enabled)
						{
							// Chargement de la classe
							$file = $dir."/".$modulename.".class.php";
							$classname = $modulename;
							require_once($file);
							$module = new $classname($this->db);

							if (is_array($module->export_code))
							{
								foreach($module->export_code as $r => $value)
								{
									if ($filter && ($filter != $module->export_code[$r])) continue;

									// Test si permissions ok \todo tester sur toutes permissions
									$perm=$module->export_permission[$r][0];
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
									$this->array_export_label[$i]=$module->getDatasetLabel($r);
									// Tableau des champ a exporter (cle=champ, valeur=libelle)
									$this->array_export_fields[$i]=$module->export_fields_array[$r];
									// Tableau des entites a exporter (cle=champ, valeur=entite)
									$this->array_export_entities[$i]=$module->export_entities_array[$r];
									// Tableau des alias a exporter (cle=champ, valeur=alias)
									$this->array_export_alias[$i]=$module->export_alias_array[$r];
									// Tableau des operations speciales sur champ
									$this->array_export_special[$i]=$module->export_special_array[$r];

									// Requete sql du dataset
									$this->array_export_sql_start[$i]=$module->export_sql_start[$r];
									$this->array_export_sql_end[$i]=$module->export_sql_end[$r];
									//$this->array_export_sql[$i]=$module->export_sql[$r];

									dol_syslog("Export loaded for module ".$modulename." with index ".$i.", dataset=".$module->export_code[$r].", nb of fields=".sizeof($module->export_fields_code[$r]));
									$i++;
									//	          }
								}
							}
						}
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 *      \brief      Lance la generation du fichier
	 *      \param      user                User qui exporte
	 *      \param      model               Modele d'export
	 *      \param      datatoexport        Lot de donnee a exporter
	 *      \param      array_selected      Tableau des champs a exporter
	 *      \remarks    Les tableaux array_export_xxx sont deja chargees pour le bon datatoexport
	 *                  aussi le parametre datatoexport est inutilise
	 */
	function build_file($user, $model, $datatoexport, $array_selected)
	{
		global $conf,$langs;

		$indice=0;
		asort($array_selected);

		dol_syslog("Export::build_file $model, $datatoexport, $array_selected");

		// Creation de la classe d'export du model ExportXXX
		$dir = DOL_DOCUMENT_ROOT . "/includes/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once($dir.$file);
		$objmodel = new $classname($db);

		// Build the sql request
		$sql=$this->array_export_sql_start[$indice];
		$i=0;
		//print_r($array_selected);
		foreach ($this->array_export_alias[$indice] as $key => $value)
		{
			if (! array_key_exists($key, $array_selected)) continue;		// Field not selected

			if ($i > 0) $sql.=', ';
			else $i++;
			$newfield=$key.' as '.$value;

			$sql.=$newfield;
		}
		$sql.=$this->array_export_sql_end[$indice];

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

			$outputlangs=$langs;	// Lang for output

			// Open file
			create_exdir($dirname);
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
								$alias=$this->array_export_alias[$indice][$key];
								if ($objp->$alias < 0) $objp->$alias='';
							}
							// Operation ZEROIFNEG
							if ($this->array_export_special[$indice][$key]=='ZEROIFNEG')
							{
								$alias=$this->array_export_alias[$indice][$key];
								if ($objp->$alias < 0) $objp->$alias='0';
							}
						}
					}
					// end of special operation processing

					$objmodel->write_record($this->array_export_alias[$indice],$array_selected,$objp,$outputlangs);
				}

				// Genere en-tete
				$objmodel->write_footer($outputlangs);

				// Close file
				$objmodel->close_file();
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
	 *  \brief	Create an export model in database
	 *  \param	user Objet utilisateur qui cree
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
	 *    \brief      Load an export profil from database
	 *    \param      rowid       id of profil to load
	 */
	function fetch($id)
	{
		$sql = 'SELECT em.rowid, em.field, em.label, em.type';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'export_model as em';
		$sql.= ' WHERE em.rowid = '.$id;

		dol_syslog("Export::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql) ;
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
	 *   \brief      Delete object in database
	 *	\param      user        	User that delete
	 *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *	\return		int				<0 if KO, >0 if OK
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
				//include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
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
