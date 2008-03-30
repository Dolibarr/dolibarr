<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/exports/export.class.php
        \ingroup    export
        \brief      Fichier de la classe des exports
        \version    $Id$
*/


/**
        \class 		Export
        \brief 		Classe permettant la gestion des exports
*/

class Export
{
    var $db;
	
	var $array_export_code=array();             // Tableau de "idmodule_numlot"
    var $array_export_module=array();           // Tableau de "nom de modules"
    var $array_export_label=array();            // Tableau de "libellé de lots"
    var $array_export_sql=array();              // Tableau des "requetes sql"
    var $array_export_fields=array();           // Tableau des listes de champ+libellé à exporter
    var $array_export_alias=array();            // Tableau des listes de champ+alias à exporter
    var $array_export_special=array();          // Tableau des operations speciales sur champ
    
    // Création des modéles d'export
    var $hexa;
    var $datatoexport;
    var $model_name;
    
    
    /**
     *    \brief  Constructeur de la classe
     *    \param  DB        Handler accès base de données
     */
    function Export($DB)
    {
        $this->db=$DB;
    }
        
    
    /**
     *    \brief  Charge les lots de données exportables
     *    \param  user      Objet utilisateur qui exporte
     *    \param  filter    Code export pour charger un lot de données particulier
     */
    function load_arrays($user,$filter='')
    {
        global $langs;
        
        dolibarr_syslog("Export::load_arrays user=".$user->id." filter=".$filter);

        $dir=DOL_DOCUMENT_ROOT."/includes/modules";
        $handle=opendir($dir);

        // Recherche des exports disponibles
        $var=True;
        $i=0;
        while (($file = readdir($handle))!==false)
        {
            if (eregi("^(mod.*)\.class\.php",$file,$reg))
            {
                $modulename=$reg[1];
    
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
                        //print("$bool<br>");
                        
                        // Permissions ok
                        if ($bool)
                        {
                            // Charge fichier lang en rapport
                            $langtoload=$module->getLangFilesArray();
                            if (is_array($langtoload))
                            {
                                foreach($langtoload as $key) 
                                {
                                    $langs->load($key);
                                }
                            }

                            // Nom module
                            $this->array_export_module[$i]=$module;
                            // Code du dataset export
                            $this->array_export_code[$i]=$module->export_code[$r];
                            // Libellé du dataset export
                            $this->array_export_label[$i]=$module->getDatasetLabel($r);
                            // Tableau des champ à exporter (clé=champ, valeur=libellé)
                            $this->array_export_fields[$i]=$module->export_fields_array[$r];
                            // Tableau des entites à exporter (clé=champ, valeur=entite)
                            $this->array_export_entities[$i]=$module->export_entities_array[$r];
                            // Tableau des alias à exporter (clé=champ, valeur=alias)
                            $this->array_export_alias[$i]=$module->export_alias_array[$r];
                            // Tableau des operations speciales sur champ
                            $this->array_export_special[$i]=$module->export_special_array[$r];

                            // Requete sql du dataset
                            $this->array_export_sql_start[$i]=$module->export_sql_start[$r];
                            $this->array_export_sql_end[$i]=$module->export_sql_end[$r];
                            //$this->array_export_sql[$i]=$module->export_sql[$r];

                            dolibarr_syslog("Export loaded for module ".$modulename." with index ".$i.", dataset=".$module->export_code[$r].", nb of fields=".sizeof($module->export_fields_code[$r]));
                            $i++;
                        }
                    }            
                }
            }
        }
        closedir($handle);
    }

    /**
     *      \brief      Lance la generation du fichier
     *      \param      user                User qui exporte
     *      \param      model               Modele d'export
     *      \param      datatoexport        Lot de donnée à exporter
     *      \param      array_selected      Tableau des champs à exporter
     *      \remarks    Les tableaux array_export_xxx sont déjà chargées pour le bon datatoexport
     *                  aussi le parametre datatoexport est inutilisé
     */ 
    function build_file($user, $model, $datatoexport, $array_selected)
    {
        global $conf,$langs;
        
        $indice=0;
        asort($array_selected);
        
        dolibarr_syslog("Export::build_file $model, $datatoexport, $array_selected");
        
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
		dolibarr_syslog("Export::build_file sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
            //$this->array_export_label[$indice]
            $filename="export_".$datatoexport;
            $filename.='.'.$objmodel->getDriverExtension();
            $dirname=$conf->export->dir_temp.'/'.$user->id;

            // Open file
            create_exdir($dirname);
            $result=$objmodel->open_file($dirname."/".$filename);

			if ($result >= 0)
			{
	            // Genere en-tete
	            $objmodel->write_header($langs);

	            // Genere ligne de titre
	            $objmodel->write_title($this->array_export_fields[$indice],$array_selected,$langs);

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
					
					$objmodel->write_record($this->array_export_alias[$indice],$array_selected,$objp);
	            }
	            
	            // Genere en-tete
	            $objmodel->write_footer($langs);
	            
	            // Close file
	            $objmodel->close_file();
			}
			else
			{
	            $this->error=$objmodel->error;
	            dolibarr_syslog("Error: ".$this->error);
	            return -1;
			}
        }
        else
        {
            $this->error=$this->db->error()." - sql=".$sql;
            dolibarr_syslog("Error: ".$this->error);
            return -1;
        }
    }
    
	/**
	*  \brief	Créé un modéle d'export
	*  \param	user Objet utilisateur qui crée
	*/
	function create($user)
	{
		global $conf;
		
		dolibarr_syslog("Export.class.php::create");
		
		$this->db->begin();
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'export_model (';
		$sql.= 'label, type, field)';
		$sql.= " VALUES ('".$this->model_name."', '".$this->datatoexport."', '".$this->hexa."')";
		
		dolibarr_syslog("Export.class.php::create sql=".$sql);
		
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	*    \brief      Recupère de la base les caractéristiques d'un modele d'export
	*    \param      rowid       id du modéle à récupérer
	*/
	function fetch($id)
	{
		$sql = 'SELECT em.rowid, em.field, em.label, em.type';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'export_model as em';
		$sql.= ' WHERE em.rowid = '.$id;
		
		dolibarr_syslog("Export::fetch sql=$sql");
		
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
			dolibarr_print_error($this->db);
			return -3;
		}
	}
    
}

?>
