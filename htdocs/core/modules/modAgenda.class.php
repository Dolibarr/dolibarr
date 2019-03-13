<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Bahfir Abbes         <bafbes@gmail.com>
 * Copyright (C) 2017      Juanjo Menent        <jmenent@2byte.es>
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
 *		\defgroup   agenda     Module agenda
 *      \brief      Module to manage agenda and events
 *      \file       htdocs/core/modules/modAgenda.class.php
 *      \ingroup    agenda
 *      \brief      File of class to describe and enable/disable module Agenda
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe and enable/disable module Agenda
 */
class modAgenda extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 2400;

		$this->family = "projects";
		$this->module_position = '15';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Follow events or rendez-vous. Record manual events into Agendas or let application record automatic events for log tracking.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='action';

		// Data directories to create when module is enabled
		$this->dirs = array("/agenda/temp");

		// Config pages
		$this->config_page_url = array("agenda_other.php");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("companies");
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module

		// Module parts
        $this->module_parts = array();

		// Constants
        //-----------
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
        // );
		$this->const = array();
		//$this->const[] = array('AGENDA_DEFAULT_FILTER_TYPE', 'chaine', 'AC_NON_AUTO', 'Default filter for type of event on agenda', 0, 'current');
		$sqlreadactions="SELECT code, label, description FROM ".MAIN_DB_PREFIX."c_action_trigger ORDER by rang";
		$resql = $this->db->query($sqlreadactions);
		if ($resql)
		{
		    while ($obj = $this->db->fetch_object($resql))
		    {
		        //if (preg_match('/_CREATE$/',$obj->code) && (! in_array($obj->code, array('COMPANY_CREATE','PRODUCT_CREATE','TASK_CREATE')))) continue;    // We don't track such events (*_CREATE) by default, we prefer validation (except thirdparty/product/task creation because there is no validation).
		        if (preg_match('/^TASK_/',$obj->code)) continue;      // We don't track such events by default.
		        //if (preg_match('/^_MODIFY/',$obj->code)) continue;    // We don't track such events by default.
		        $this->const[] = array('MAIN_AGENDA_ACTIONAUTO_'.$obj->code, "chaine", "1", '', 0, 'current');
		    }
		}
		else
		{
		    dol_print_error($this->db->lasterror());
		}

		// New pages on tabs
		// -----------------
		$this->tabs = array();

		// Boxes
		//------
		$this->boxes = array(0=>array('file'=>'box_actions.php','enabledbydefaulton'=>'Home'));

		// Cronjobs
		//------------
		$datestart=dol_now();
		$this->cronjobs = array(
			0=>array('label'=>'SendEmailsReminders', 'jobtype'=>'method', 'class'=>'comm/action/class/actioncomm.class.php', 'objectname'=>'ActionComm', 'method'=>'sendEmailsReminder', 'parameters'=>'', 'comment'=>'SendEMailsReminder', 'frequency'=>10, 'unitfrequency'=>60, 'priority'=>10, 'status'=>1, 'test'=>'$conf->agenda->enabled', 'datestart'=>$datestart),
		);

		// Permissions
		//------------
		$this->rights = array();
		$this->rights_class = 'agenda';
		$r=0;

		// $this->rights[$r][0]     Id permission (unique tous modules confondus)
		// $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
		// $this->rights[$r][2]     Non utilise
		// $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
		// $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
		// $this->rights[$r][5]     Niveau 2 pour nommer permission dans code
		// $r++;

		$this->rights[$r][0] = 2401;
		$this->rights[$r][1] = 'Read actions/tasks linked to his account';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = 2402;
		$this->rights[$r][1] = 'Create/modify actions/tasks linked to his account';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
		$this->rights[$r][5] = 'create';
		$r++;

		$this->rights[$r][0] = 2403;
		$this->rights[$r][1] = 'Delete actions/tasks linked to his account';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
		$this->rights[$r][5] = 'delete';
		$r++;

		$this->rights[$r][0] = 2411;
		$this->rights[$r][1] = 'Read actions/tasks of others';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = 2412;
		$this->rights[$r][1] = 'Create/modify actions/tasks of others';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
		$this->rights[$r][5] = 'create';
		$r++;

		$this->rights[$r][0] = 2413;
		$this->rights[$r][1] = 'Delete actions/tasks of others';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
		$this->rights[$r][5] = 'delete';
		$r++;

		$this->rights[$r][0] = 2414;
		$this->rights[$r][1] = 'Export actions/tasks of others';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		// Example to declare the Top Menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
		//							'type'=>'top',			// This is a Top menu entry
		//							'titre'=>'MyModule top menu',
		//							'mainmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagetop.php',
		//							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
		//							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		$this->menu[$r]=array('fk_menu'=>0,
													'type'=>'top',
													'titre'=>'TMenuAgenda',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php',
													'langs'=>'agenda',
													'position'=>86,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;

		$this->menu[$r]=array('fk_menu'=>'r=0',
													'type'=>'left',
													'titre'=>'Actions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?mainmenu=agenda&amp;leftmenu=agenda',
													'langs'=>'agenda',
													'position'=>100,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=1',
													'type'=>'left',
													'titre'=>'NewAction',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/card.php?mainmenu=agenda&amp;leftmenu=agenda&amp;action=create',
													'langs'=>'commercial',
													'position'=>101,
													'perms'=>'($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create)',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		// Calendar
		$this->menu[$r]=array('fk_menu'=>'r=1',
													'type'=>'left',
													'titre'=>'Calendar',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?action=default&amp;mainmenu=agenda&amp;leftmenu=agenda',
													'langs'=>'agenda',
													'position'=>140,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=3',
													'type'=>'left',
													'titre'=>'MenuToDoMyActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?action=default&amp;mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine',
													'langs'=>'agenda',
													'position'=>141,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=3',
													'type'=>'left',
													'titre'=>'MenuDoneMyActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?action=default&amp;mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine',
													'langs'=>'agenda',
													'position'=>142,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=3',
													'type'=>'left',
													'titre'=>'MenuToDoActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?action=default&amp;mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filtert=-1',
													'langs'=>'agenda',
													'position'=>143,
													'perms'=>'$user->rights->agenda->allactions->read',
													'enabled'=>'$user->rights->agenda->allactions->read',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=3',
													'type'=>'left',
													'titre'=>'MenuDoneActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/index.php?action=default&amp;mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filtert=-1',
													'langs'=>'agenda',
													'position'=>144,
													'perms'=>'$user->rights->agenda->allactions->read',
													'enabled'=>'$user->rights->agenda->allactions->read',
													'target'=>'',
													'user'=>2);

		// List
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=1',
													'type'=>'left',
													'titre'=>'List',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/list.php?mainmenu=agenda&amp;leftmenu=agenda',
													'langs'=>'agenda',
													'position'=>110,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=8',
													'type'=>'left',
													'titre'=>'MenuToDoMyActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/list.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filter=mine',
													'langs'=>'agenda',
													'position'=>111,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=8',
													'type'=>'left',
													'titre'=>'MenuDoneMyActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/list.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filter=mine',
													'langs'=>'agenda',
													'position'=>112,
													'perms'=>'$user->rights->agenda->myactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=8',
													'type'=>'left',
													'titre'=>'MenuToDoActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/list.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=todo&amp;filtert=-1',
													'langs'=>'agenda',
													'position'=>113,
													'perms'=>'$user->rights->agenda->allactions->read',
													'enabled'=>'$user->rights->agenda->allactions->read',
													'target'=>'',
													'user'=>2);
		$r++;
		$this->menu[$r]=array('fk_menu'=>'r=8',
													'type'=>'left',
													'titre'=>'MenuDoneActions',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/list.php?mainmenu=agenda&amp;leftmenu=agenda&amp;status=done&amp;filtert=-1',
													'langs'=>'agenda',
													'position'=>114,
													'perms'=>'$user->rights->agenda->allactions->read',
													'enabled'=>'$user->rights->agenda->allactions->read',
													'target'=>'',
													'user'=>2);
		$r++;
		// Reports
		$this->menu[$r]=array('fk_menu'=>'r=1',
													'type'=>'left',
													'titre'=>'Reportings',
													'mainmenu'=>'agenda',
													'url'=>'/comm/action/rapport/index.php?mainmenu=agenda&amp;leftmenu=agenda',
													'langs'=>'agenda',
													'position'=>160,
													'perms'=>'$user->rights->agenda->allactions->read',
													'enabled'=>'$conf->agenda->enabled',
													'target'=>'',
													'user'=>2);
		$r++;


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="ExportDataset_event1";
		$this->export_permission[$r]=array(array("agenda","export"));
		$this->export_fields_array[$r]=array('ac.id'=>"IdAgenda",'ac.ref_ext'=>"ExternalRef",'ac.datec'=>"DateCreation",'ac.datep'=>"DateActionBegin",
			'ac.datep2'=>"DateActionEnd",'ac.label'=>"Title",'ac.note'=>"Note",'ac.percent'=>"Percent",'ac.durationp'=>"Duration",
			'cac.libelle'=>"ActionType",
			's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town',
			'co.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5','s.idprof6'=>'ProfId6',
			's.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','s.tva_intra'=>'VATIntra');
		$this->export_TypeFields_array[$r]=array('ac.ref_ext'=>"Text",'ac.datec'=>"Date",'ac.datep'=>"Date",
			'ac.datep2'=>"Date",'ac.label'=>"Text",'ac.note'=>"Text",'ac.percent'=>"Numeric",
			'ac.durationp'=>"Duree",
			'cac.libelle'=>"List:c_actioncomm:libelle:libelle",
			's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text',
			'co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.idprof5'=>'Text','s.idprof6'=>'Text',
			's.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text');
		$this->export_entities_array[$r]=array('ac.id'=>"action",'ac.ref_ext'=>"action",'ac.datec'=>"action",'ac.datep'=>"action",
			'ac.datep2'=>"action",'ac.label'=>"action",'ac.note'=>"action",'ac.percent'=>"action",'ac.durationp'=>"action",
			'cac.libelle'=>"action",
			's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company',
			'co.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.idprof5'=>'company','s.idprof6'=>'company',
			's.code_compta'=>'company','s.code_compta_fournisseur'=>'company','s.tva_intra'=>'company',);

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM  '.MAIN_DB_PREFIX.'actioncomm as ac';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_actioncomm as cac on ac.fk_action = cac.id';
		if (! empty($user) && empty($user->rights->agenda->allactions->read)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_resources acr on ac.id = acr.fk_actioncomm';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople as sp on ac.fk_contact = sp.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s on ac.fk_soc = s.rowid';
		if (! empty($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co on s.fk_pays = co.rowid';
		$this->export_sql_end[$r] .=' WHERE ac.entity IN ('.getEntity('agenda').')';
		if (empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' AND (sc.fk_user = '.(empty($user)?0:$user->id).' OR ac.fk_soc IS NULL)';
		if (empty($user->rights->agenda->allactions->read)) $this->export_sql_end[$r] .=' AND acr.fk_element = '.(empty($user)?0:$user->id);
		$this->export_sql_order[$r] .=' ORDER BY ac.datep';
	}
}
