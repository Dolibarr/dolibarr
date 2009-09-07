<?php
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/admin/import/import.class.php
 *	\ingroup    import
 *	\brief      Fichier de la classe des imports
 *	\version    $Id$
 */

/**
 *	\class 		Import
 *	\brief 		Classe permettant la gestion des imports
 */
class Import
{
	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB        Database handler
	 */
	function Import($DB)
	{
		$this->db=$DB;
	}


	/**
	 *    \brief  Load description of an importable dataset
	 *    \param  user      Object user making import
	 *    \param  filter    Load a particular dataset only
	 */
	function load_arrays($user,$filter='')
	{
		global $langs,$conf;

		dol_syslog("Import::load_arrays user=".$user->id." filter=".$filter);

		//$dir=DOL_DOCUMENT_ROOT."/includes/modules";
		foreach($conf->file->dol_document_root as $dirroot)
		{
			$dir = $dirroot.'/includes/modules';

			// Search available exports
			$handle=@opendir($dir);
			if ($handle)
			{
				// Recherche des exports disponibles
				$var=True;
				$i=0;
				while (($file = readdir($handle))!==false)
				{
					if (eregi("^(mod.*)\.class\.php",$file,$reg))
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
									$this->array_import_perms[$i]=$user->admin;
									// Icon
									$this->array_import_icon[$i]=(isset($module->import_icon[$r])?$module->import_icon[$r]:$module->picto);
									// Code du dataset export
									$this->array_import_code[$i]=$module->import_code[$r];
									// Libelle du dataset export
									$this->array_import_label[$i]=$module->getDatasetLabel($r);
									// Tableau des champ a exporter (cle=champ, valeur=libelle)
									$this->array_import_fields[$i]=$module->import_fields_array[$r];
									// Tableau des entites a exporter (cle=champ, valeur=entite)
									$this->array_import_entities[$i]=$module->import_entities_array[$r];
									// Tableau des alias a exporter (cle=champ, valeur=alias)
									$this->array_import_examplevalues[$i]=$module->import_examplevalues_array[$r];
									// Requete sql du dataset
									$this->array_import_sql_start[$i]=$module->import_sql_start[$r];
									$this->array_import_sql_end[$i]=$module->import_sql_end[$r];
									//$this->array_import_sql[$i]=$module->import_sql[$r];

									dol_syslog("Import loaded for module ".$modulename." with index ".$i.", dataset=".$module->import_code[$r].", nb of fields=".sizeof($module->import_fields_code[$r]));
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
	}



	/**
	 *      \brief      Lance la generation du fichier example
	 *      \param      user                User qui exporte
	 *      \param      model               Modele d'export
	 *      \param      $headerlinefields   Array of values for first line of example file
	 *      \param      $contentlinevalues  Array of values for content line of example file
	 *      \remarks    Les tableaux array_export_xxx sont deja chargees pour le bon datatoexport
	 *                  aussi le parametre datatoexport est inutilise
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
}
?>
