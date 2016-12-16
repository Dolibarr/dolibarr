<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\defgroup   ficheinter     Module Interventions
 *	\brief      Module to manage intervention cards
 *	\file       htdocs/core/modules/modFicheinter.class.php
 *	\ingroup    ficheinter
 *	\brief      Fichier de description et activation du module Ficheinter
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Ficheinter
 */
class modFicheinter extends DolibarrModules
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
        $this->numero = 70;

        $this->family = "crm";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Gestion des fiches d'intervention";

        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';

        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto = "intervention";

        // Data directories to create when module is enabled
        $this->dirs = array("/ficheinter/temp");

        // Dependencies
        $this->depends = array("modSociete");
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("bills","companies","interventions");

        // Config pages
        $this->config_page_url = array("fichinter.php");

        // Constants
        $this->const = array();
        $r=0;

        $this->const[$r][0] = "FICHEINTER_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "soleil";
        $r++;

        $this->const[$r][0] = "FICHEINTER_ADDON";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "pacific";
        $r++;

        // Boxes
        $this->boxes = array(0=>array('file'=>'box_ficheinter.php','enabledbydefaulton'=>'Home'));

        // Permissions
        $this->rights = array();
        $this->rights_class = 'ficheinter';
        $r=0;

        $r++;
        $this->rights[$r][0] = 61;
        $this->rights[$r][1] = 'Lire les fiches d\'intervention';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 62;
        $this->rights[$r][1] = 'Creer/modifier les fiches d\'intervention';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 64;
        $this->rights[$r][1] = 'Supprimer les fiches d\'intervention';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';

        $r++;
        $this->rights[$r][0] = 67;
        $this->rights[$r][1] = 'Exporter les fiches interventions';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';

        $r++;
        $this->rights[$r][0] = 68;
        $this->rights[$r][1] = 'Envoyer les fiches d\'intervention par courriel';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'ficheinter_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
        $this->rights[$r][5] = 'send';

        $r++;
        $this->rights[$r][0] = 69;
        $this->rights[$r][1] = 'Valider les fiches d\'intervention ';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'ficheinter_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
        $this->rights[$r][5] = 'validate';

        $r++;
        $this->rights[$r][0] = 70;
        $this->rights[$r][1] = 'Dévalider les fiches d\'intervention';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'ficheinter_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
        $this->rights[$r][5] = 'unvalidate';

        
        // Menus
        //-------
        $this->menu = 1;        // This module add menu entries. They are coded into menu manager.
        
        
        //Exports
        //--------
        $r=1;

        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='InterventionCardsAndInterventionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_permission[$r]=array(array("ficheinter","export"));
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InterId",'f.ref'=>"InterRef",'f.datec'=>"InterDateCreation",'f.duree'=>"InterDuration",'f.fk_statut'=>'InterStatus','f.description'=>"InterNote", 'pj.ref'=>'ProjectRef', 'fd.rowid'=>'InterLineId','fd.date'=>"InterLineDate",'fd.duree'=>"InterLineDuration",'fd.description'=>"InterLineDesc");
        //$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','s.fk_pays'=>'List:c_country:label','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.duree'=>"Duree",'f.fk_statut'=>'Statut','f.description'=>"Text",'f.datee'=>"Date",'f.dateo'=>"Date",'f.fulldayevent'=>"Boolean",'fd.date'=>"Date",'fd.duree'=>"Duree",'fd.description'=>"Text",'fd.total_ht'=>"Numeric");
        $this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','s.fk_pays'=>'List:c_country:label','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.duree'=>"Duree",'f.fk_statut'=>'Statut','f.description'=>"Text",'f.datee'=>"Date",'f.dateo'=>"Date",'f.fulldayevent'=>"Boolean", 'pj.ref'=>'Text', 'fd.date'=>"Date",'fd.duree'=>"Duree",'fd.description'=>"Text",'fd.total_ht'=>"Numeric");
        $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"intervention",'f.ref'=>"intervention",'f.datec'=>"intervention",'f.duree'=>"intervention",'f.fk_statut'=>"intervention",'f.description'=>"intervention", 'pj.ref'=>'project', 'fd.rowid'=>"inter_line",'fd.date'=>"inter_line",'fd.duree'=>'inter_line','fd.description'=>'inter_line');
        $this->export_dependencies_array[$r]=array('inter_line'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'fichinter as f';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pj ON f.fk_projet = pj.rowid';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'fichinterdet as fd ON f.rowid = fd.fk_fichinter,';
        $this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'societe as s';
        $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid';
        $this->export_sql_end[$r] .=' AND f.entity IN ('.getEntity('intervention',1).')';
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
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND type = 'ficheinter' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','ficheinter',".$conf->entity.")",
        );

        return $this->_init($sql,$options);
    }
}
