<?php
/* Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   deplacement     Module trips
 *	\brief      Module pour gerer les deplacements et notes de frais
 *	\file       htdocs/core/modules/modDeplacement.class.php
 *	\ingroup    deplacement
 *	\brief      Fichier de description et activation du module Deplacement et notes de frais
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Deplacement
 */
class modDeplacement extends DolibarrModules
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
		$this->numero = 75 ;

		$this->family = "hr";
		$this->module_position = 41;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des notes de frais et deplacements";		// Si traduction Module75Desc non trouvee

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr_deprecated';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "trip";

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array();
		$this->langfiles = array("companies","trips");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'deplacement';

		$this->rights[1][0] = 171;
		$this->rights[1][1] = 'Lire ses notes de frais et deplacements et celles de sa hierarchy';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 172;
		$this->rights[2][1] = 'Creer/modifier une note de frais et deplacements';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

    	$this->rights[3][0] = 173;
		$this->rights[3][1] = 'Supprimer les notes de frais et deplacements';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';

    	$this->rights[4][0] = 174;
		$this->rights[4][1] = 'Lire toutes les notes de frais';
		$this->rights[4][2] = 'd';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'readall';

		$this->rights[5][0] = 178;
		$this->rights[5][1] = 'Exporter les notes de frais et deplacements';
		$this->rights[5][2] = 'd';
		$this->rights[5][3] = 0;
		$this->rights[5][4] = 'export';

		
		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		
		
		// Exports
		$r=0;

		$r++;
		$this->export_code[$r]='trips_'.$r;
		$this->export_label[$r]='ListTripsAndExpenses';
		$this->export_permission[$r]=array(array("deplacement","export"));
        $this->export_fields_array[$r]=array('u.login'=>'Login','u.lastname'=>'Lastname','u.firstname'=>'Firstname','d.rowid'=>"TripId",'d.type'=>"Type",'d.km'=>"FeesKilometersOrAmout",'d.dated'=>"Date",'d.note_private'=>'NotePrivate','d.note_public'=>'NotePublic','s.nom'=>'ThirdParty');
        $this->export_TypeFields_array[$r]=array('u.rowid'=>'List:user:name','u.login'=>'Text','u.lastname'=>'Text','u.firstname'=>'Text','d.type'=>"Text",'d.km'=>"Numeric",'d.dated'=>"Date",'d.note_private'=>'Text','d.note_public'=>'Text','s.rowid'=>"List:societe:CompanyName",'s.nom'=>'Text');
        $this->export_entities_array[$r]=array('u.login'=>'user','u.lastname'=>'user','u.firstname'=>'user','d.rowid'=>"trip",'d.type'=>"trip",'d.km'=>"trip",'d.dated'=>"trip",'d.note_private'=>'trip','d.note_public'=>'trip','s.nom'=>'company');
        $this->export_dependencies_array[$r]=array('trip'=>'d.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .=', '.MAIN_DB_PREFIX.'deplacement as d';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON d.fk_soc = s.rowid';
		if (empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' WHERE d.fk_user = u.rowid';
		$this->export_sql_end[$r] .=' AND d.entity IN ('.getEntity('deplacement',1).')';
		if (empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' AND (sc.fk_user = '.(empty($user)?0:$user->id).' OR d.fk_soc IS NULL)';
		
		if (! empty($user))   // Not defined during migration process
		{
    		$childids = $user->getAllChildIds();
    		$childids[]=$user->id;
    		
    		if (empty($user->rights->deplacement->readall) && empty($user->rights->deplacement->lire_tous)) $sql.=' AND d.fk_user IN ('.join(',',$childids).')';
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
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
