<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/includes/modules/export/modules_export.php
        \ingroup    export
        \brief      Fichier contenant la classe mère de generation des exports
        \version    $Revision$
*/


/**
   \class      ModeleExport
   \brief      Classe mère des modèles de format d'export
*/

class ModeleExports
{
    var $error='';
    
    var $modelname;
    var $drivername;
    var $driverversion;

    
    /**
     *      \brief      Constructeur
     */
    function ModeleExports()
    {
        $this->modelname=array('csv'=>'Csv','excel'=>'Excel');
        $this->drivername=array('csv'=>'Dolibarr','excel'=>'Php_WriteExcel');
        $this->driverversion=array('csv'=>DOL_VERSION,'excel'=>'?');
    }
    
    /**
     *      \brief      Renvoi la liste des modèles actifs
     *      \param      db      Handler de base
     */
    function liste_modeles($db)
    {
        //$liste=array('csv','excel');
        $liste=array('csv');
    
        return $liste;
    }
    
    /**
     *      \brief      Renvoi nom d'un format export
     */
    function getModelName($key)
    {
        return $this->modelname[$key];
    }

    /**
     *      \brief      Renvoi libelle d'un driver export
     */
    function getDriverName($key)
    {
        return $this->drivername[$key];
    }

    /**
     *      \brief      Renvoi version d'un driver export
     */
    function getDriverVersion($key)
    {
        return $this->driverversion[$key];
    }



    /**
     *      \brief      Lance la generation du fichier
     *      \remarks    Les tableaux array_export_xxx sont déjà chargées pour le bon datatoexport
     *                  aussi le parametre datatoexport est inutilisé
     */ 
    function build_file($model, $datatoexport, $array_selected)
    {
        global $langs;

        dolibarr_syslog("Export::build_file $model, $datatoexport, $array_selected");
        
        // Creation de la classe d'export du model ExportXXX
        $dir = DOL_DOCUMENT_ROOT . "/includes/modules/export/";
        $file = "export_".$model.".modules.php";
        $classname = "Export".$model;
        require_once($dir.$file);
        $obj = new $classname($db);
        
        // Execute requete export        
        $sql=$this->array_export_sql[0];
		$resql = $this->db->query($sql);
		if ($resql)
		{
            // Genere en-tete
            $obj->write_header();		    
		    
            // Genere ligne de titre
            $obj->write_title();

			while ($objp = $this->db->fetch_object($resql))
			{
				$var=!$var;
                $obj->write_record($objp,$array_selected);
            }
            
            // Genere en-tete
            $obj->write_footer();
        }
        else
        {
            $this->error=$this->db->error();
            dolibarr_syslog("Error: sql=$sql ".$this->error);
            return -1;
        }
    }
    
}


?>
