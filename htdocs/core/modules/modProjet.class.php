<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \defgroup   projet     Module project
 *	\brief      Module to create projects/tasks/gantt diagram. Projects can them be affected to tasks.
 *  \file       htdocs/core/modules/modProjet.class.php
 *	\ingroup    projet
 *	\brief      Fichier de description et activation du module Projet
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Projet
 */
class modProjet extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 400;

		$this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des projets";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->config_page_url = array("project.php@projet");
		$this->picto='project';

		// Data directories to create when module is enabled
		$this->dirs = array("/projet/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('projects');

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "PROJECT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "baleine";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des projets en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_project_simple";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des projets';
		$this->const[$r][4] = 0;
		$r++;

		$r++;
		$this->const[$r][0] = "PROJECT_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/projects";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'projet';
		$r=0;

		$r++;
		$this->rights[$r][0] = 41; // id de la permission
		$this->rights[$r][1] = "Lire les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 42; // id de la permission
		$this->rights[$r][1] = "Creer/modifier les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 44; // id de la permission
		$this->rights[$r][1] = "Supprimer les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 141; // id de la permission
		$this->rights[$r][1] = "Lire tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 142; // id de la permission
		$this->rights[$r][1] = "Creer/modifier tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 144; // id de la permission
		$this->rights[$r][1] = "Supprimer tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'supprimer';


		//Exports
		//--------
		$r=1;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ProjectsAndTasksLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("projet","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country',
				's.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode',
				'p.rowid'=>"ProjectId",'p.ref'=>"ProjectRef",'p.datec'=>"DateCreation",'p.dateo'=>"DateDebutProjet",'p.datee'=>"DateFinProjet",'p.fk_statut'=>'ProjectStatus','p.description'=>"projectNote",
				'pt.rowid'=>'RefTask','pt.dateo'=>"TaskDateo",'pt.datee'=>"TaskDatee",'pt.duration_effective'=>"DurationEffective",'pt.duration_planned'=>"DurationPlanned",'pt.progress'=>"Progress",'pt.description'=>"TaskDesc");

		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','s.fk_pays'=>'List:c_pays:libelle',
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','s.fk_pays'=>'List:c_pays:libelle',
				's.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text',
				'p.rowid'=>"List:projet:ref",'p.ref'=>"Text",'p.datec'=>"Date",'p.dateo'=>"Date",'p.datee'=>"Date",'p.fk_statut'=>'Status','p.description'=>"Text",
				'pt.dateo'=>"Date",'pt.datee'=>"Date",'pt.duration_effective'=>"Duree",'pt.duration_planned'=>"Duree",'pt.progress'=>"Number",'pt.description'=>"Text");

		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company',
				's.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company',
				'f.rowid'=>"project",'f.ref'=>"project",'f.datec'=>"project",'f.duree'=>"project",'f.fk_statut'=>"project",'f.description'=>"project",
				'pt.rowid'=>'task','pt.dateo'=>"task",'pt.datee'=>"task",'pt.duration_effective'=>"task",'pt.duration_planned'=>"task",'pt.progress'=>"task",'pt.description'=>"task");

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'projet as p, '.MAIN_DB_PREFIX.'projet_task as pt, '.MAIN_DB_PREFIX.'societe as s)';
		$this->export_sql_end[$r] .=' WHERE p.fk_soc = s.rowid AND p.rowid = pt.fk_projet ';
		$this->export_sql_end[$r] .=' AND p.entity = '.$conf->entity;
		$r++;
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','project',".$conf->entity.")",
		);

		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
