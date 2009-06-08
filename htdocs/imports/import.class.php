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
     *    \brief  Load an importable dataset
     *    \param  user      Object user making export
     *    \param  filter    Code export pour charger un lot de donnees particulier
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
			                            $this->array_import_alias[$i]=$module->import_alias_array[$r];
			                            // Tableau des operations speciales sur champ
			                            $this->array_import_special[$i]=$module->import_special_array[$r];

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



	/*
	 *	\brief Importe un fichier clients
	 */
	function ImportClients($file)
	{
		$this->nb_import_ok = 0;
		$this->nb_import_ko = 0;
		$this->nb_import = 0;

		dol_syslog("Import::ImportClients($file)", LOG_DEBUG);

		$this->ReadFile($file);

		foreach ($this->lines as $this->line)
		{
			$societe = new Societe($this->db);

			$this->SetInfosTiers($societe);

			$societe->client = 1;
			$societe->tva_assuj   = $this->line[12];
			$societe->code_client = $this->line[13];
			$societe->tva_intra   = $this->line[14];

			$this->nb_import++;

			if ( $societe->create($user) == 0)
	  {
	  	dol_syslog("Import::ImportClients ".$societe->nom." SUCCESS", LOG_DEBUG);
	  	$this->nb_import_ok++;
	  }
	  else
	  {
	  	dol_syslog("Import::ImportClients ".$societe->nom." ERROR", LOG_ERR);
	  	$this->nb_import_ko++;
	  }
		}

	}


	function SetInfosTiers(&$obj)
	{
		$obj->nom     = $this->line[0];
		$obj->adresse = $this->line[1];

		if (strlen(trim($this->line[2])) > 0)
		$obj->adresse .= "\n". trim($this->line[2]);

		if (strlen(trim($this->line[3])) > 0)
		$obj->adresse .= "\n". trim($this->line[3]);

		$obj->cp      = $this->line[4];
		$obj->ville   = $this->line[5];
		$obj->tel     = $this->line[6];
		$obj->fax     = $this->line[7];
		$obj->email   = $this->line[8];
		$obj->url     = $this->line[9];
		$obj->siren   = $this->line[10];
		$obj->siret   = $this->line[11];
	}


	function ReadFile($file)
	{
		$this->errno = 0;

		if (is_readable($file))
		{
			dol_syslog("Import::ReadFile Lecture du fichier $file", LOG_DEBUG);

			$line = 0;
			$hf = fopen ($file, "r");
			$line = 0;
			$i=0;

			$this->lines = array();


			while (!feof($hf) )
	  {
	  	$cont = fgets($hf, 1024);

	  	if (strlen(trim($cont)) > 0)
	  	{
	  		$this->lines[$i] = explode(";", $cont);
	  	}
	  	$i++;

	  }
		}
		else
		{
			$this->errno = -2;
		}

		return $errno;
	}

}
?>
