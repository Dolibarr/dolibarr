<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Charles-Fr BENKE	<charles.fr@benke.fr>
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
 *	Class to describe and enable module Projet
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
		$this->module_position = 10;
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
		$this->const[$r][3] = 'Name of PDF/ODT project manager class';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_project_simple";
		$this->const[$r][3] = 'Name of Numbering Rule project manager class';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/projects";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_TASK_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = 'Name of PDF/ODT tasks manager class';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_TASK_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_task_simple";
		$this->const[$r][3] = 'Name of Numbering Rule task manager class';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_TASK_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/tasks";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_USE_OPPORTUNITIES";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_DELAY_PROJECT_TO_CLOSE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "7";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;
		$this->const[$r][0] = "MAIN_DELAY_TASKS_TODO";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "7";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();
		$r=0;
		$this->boxes[$r][1] = "box_project.php";
		$r++;
		$this->boxes[$r][1] = "box_task.php";
		$r++;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'projet';
		$r=0;

		$r++;
		$this->rights[$r][0] = 41; // id de la permission
		$this->rights[$r][1] = "Read projects and tasks (shared projects or projects I am contact for). Can also enter time consumed on assigned tasks (timesheet)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 42; // id de la permission
		$this->rights[$r][1] = "Create/modify projects and tasks (shared projects or projects I am contact for)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 44; // id de la permission
		$this->rights[$r][1] = "Delete project and tasks (shared projects or projects I am contact for)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 45; // id de la permission
		$this->rights[$r][1] = "Export projects"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'export';

		$r++;
		$this->rights[$r][0] = 141; // id de la permission
		$this->rights[$r][1] = "Read all projects and tasks (also private projects I am not contact for)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 142; // id de la permission
		$this->rights[$r][1] = "Create/modify all projects and tasks (also private projects I am not contact for). Can also enter time consumed on assigned tasks (timesheet)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 144; // id de la permission
		$this->rights[$r][1] = "Delete all projects and tasks (also private projects I am not contact for)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'supprimer';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		//Exports
		//--------
		$r=1;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ProjectsAndTasksLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("projet","export"));
		$this->export_dependencies_array[$r]=array('projecttask'=>'pt.rowid', 'task_time'=>'ptt.rowid');

		$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','s.fk_pays'=>'List:c_country:label',
		's.phone'=>'Text','s.email'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text',
		'p.rowid'=>"List:projet:ref",'p.ref'=>"Text",'p.title'=>"Text",'p.datec'=>"Date",'p.dateo'=>"Date",'p.datee'=>"Date",'p.fk_statut'=>'Status','cls.code'=>"Text",'p.opp_percent'=>'Numeric','p.opp_amount'=>'Numeric','p.description'=>"Text",'p.entity'=>'Numeric',
		'pt.rowid'=>'Text','pt.label'=>'Text','pt.dateo'=>"Date",'pt.datee'=>"Date",'pt.duration_effective'=>"Duree",'pt.planned_workload'=>"Numeric",'pt.progress'=>"Numeric",'pt.description'=>"Text",
		'ptt.rowid'=>'Numeric','ptt.task_date'=>'Date','ptt.task_duration'=>"Duree",'ptt.fk_user'=>"List:user:CONCAT(lastname,' ',firstname)",'ptt.note'=>"Text");
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company',
		's.phone'=>'company','s.email'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company');

		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country',
		's.phone'=>'Phone','s.email'=>'Email','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode',
		'p.rowid'=>"ProjectId",'p.ref'=>"RefProject",'p.title'=>'ProjectLabel', 'p.datec'=>"DateCreation",'p.dateo'=>"DateStart",'p.datee'=>"DateEnd",'p.fk_statut'=>'ProjectStatus','cls.code'=>'OpportunityStatus','p.opp_percent'=>'OpportunityProbability','p.opp_amount'=>'OpportunityAmount','p.description'=>"Description");
	    // Add multicompany field
        if (! empty($conf->global->MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED))
        {
            $nbofallowedentities=count(explode(',',getEntity('project')));    // If project are shared, nb will be > 1
            if (! empty($conf->multicompany->enabled) && $nbofallowedentities > 1) $this->export_fields_array[$r]+=array('p.entity'=>'Entity');
        }
		if (empty($conf->global->PROJECT_USE_OPPORTUNITIES))
		{
		    unset($this->export_fields_array[$r]['p.opp_percent']);
		    unset($this->export_fields_array[$r]['p.opp_amount']);
		    unset($this->export_fields_array[$r]['cls.code']);
		}

		// Add fields for project
		$this->export_fields_array[$r]=array_merge($this->export_fields_array[$r], array());
		// Add extra fields for project
		$keyforselect='projet'; $keyforelement='project'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// Add fields for tasks
		$this->export_fields_array[$r]=array_merge($this->export_fields_array[$r], array('pt.rowid'=>'TaskId', 'pt.ref'=>'RefTask', 'pt.label'=>'LabelTask','pt.dateo'=>"TaskDateStart",'pt.datee'=>"TaskDateEnd",'pt.duration_effective'=>"DurationEffective",'pt.planned_workload'=>"PlannedWorkload",'pt.progress'=>"Progress",'pt.description'=>"TaskDescription"));
		$this->export_entities_array[$r]=array_merge($this->export_entities_array[$r], array('pt.rowid'=>'projecttask', 'pt.ref'=>'projecttask', 'pt.label'=>'projecttask','pt.dateo'=>"projecttask",'pt.datee'=>"projecttask",'pt.duration_effective'=>"projecttask",'pt.planned_workload'=>"projecttask",'pt.progress'=>"projecttask",'pt.description'=>"projecttask"));
        // Add extra fields for task
		$keyforselect='projet_task'; $keyforelement='projecttask'; $keyforaliasextra='extra2';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        // End add extra fields
		$this->export_fields_array[$r]=array_merge($this->export_fields_array[$r], array('ptt.rowid'=>'IdTaskTime','ptt.task_date'=>'TaskTimeDate','ptt.task_duration'=>"TimesSpent",'ptt.fk_user'=>"TaskTimeUser",'ptt.note'=>"TaskTimeNote"));
        $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r], array('ptt.rowid'=>'task_time','ptt.task_date'=>'task_time','ptt.task_duration'=>"task_time",'ptt.fk_user'=>"task_time",'ptt.note'=>"task_time"));

        $this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'projet as p';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet_extrafields as extra ON p.rowid = extra.fk_object';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_lead_status as cls ON p.fk_opp_status = cls.rowid';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX."projet_task as pt ON p.rowid = pt.fk_projet";
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as extra2 ON pt.rowid = extra2.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX."projet_task_time as ptt ON pt.rowid = ptt.fk_task";
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON p.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=" WHERE p.entity IN (".getEntity('project').")";


		// Import list of tasks
		if (empty($conf->global->PROJECT_HIDE_TASKS))
		{
    		$r++;
    		$this->import_code[$r]='tasksofprojects';
    		$this->import_label[$r]='ImportDatasetTasks';
    		$this->import_icon[$r]='task';
    		$this->import_entities_array[$r]=array('t.fk_projet'=>'project');	// We define here only fields that use another icon that the one defined into import_icon
    		$this->import_tables_array[$r]=array('t'=>MAIN_DB_PREFIX.'projet_task','extra'=>MAIN_DB_PREFIX.'projet_task_extrafields');	// List of tables to insert into (insert done in same order)
    		$this->import_fields_array[$r]=array('t.fk_projet'=>'ProjectRef*','t.ref'=>'RefTask*','t.label'=>'LabelTask*','t.dateo'=>"DateStart",'t.datee'=>"DateEnd",'t.planned_workload'=>"PlannedWorkload",'t.progress'=>"Progress",'t.note_private'=>"NotePrivate",'t.note_public'=>"NotePublic",'t.datec'=>"DateCreation");
    		// Add extra fields
    		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'projet_task' AND entity IN (0,".$conf->entity.")";
    		$resql=$this->db->query($sql);
    		if ($resql)    // This can fail when class is used on old database (during migration for example)
    		{
    		    while ($obj=$this->db->fetch_object($resql))
    		    {
    		        $fieldname='extra.'.$obj->name;
    		        $fieldlabel=ucfirst($obj->label);
    		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
    		    }
    		}
    		// End add extra fields
    		$this->import_fieldshidden_array[$r]=array('t.fk_user_creat'=>'user->id','extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'projet_task');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
    		$this->import_convertvalue_array[$r]=array(
    		    't.fk_projet'=>array('rule'=>'fetchidfromref','classfile'=>'/projet/class/project.class.php','class'=>'Project','method'=>'fetch','element'=>'Project'),
    		    't.ref'=>array('rule'=>'getrefifauto')
    		);
    		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
    		$this->import_regex_array[$r]=array('t.dateo'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','t.datee'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','t.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$');
    		$this->import_examplevalues_array[$r]=array('t.fk_projet'=>'MyProjectRef','t.ref'=>"auto or TK2010-1234",'t.label'=>"My task",'t.progress'=>"0 (not started) to 100 (finished)",'t.datec'=>'1972-10-10','t.note_private'=>"My private note",'t.note_public'=>"My public note");
		}
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
		global $conf,$langs;

		// Permissions
		$this->remove($options);

		//ODT template for project
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/projects/template_project.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/projects';
		$dest=$dirodt.'/template_project.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		//ODT template for tasks
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/tasks/template_task_summary.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/tasks';
		$dest=$dirodt.'/template_task_summary.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'project' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','project',".$conf->entity.")",
		);

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[3][2])."' AND type = 'task' AND entity = ".$conf->entity,
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[3][2])."','task',".$conf->entity.")"
		);

		return $this->_init($sql,$options);
	}
}
