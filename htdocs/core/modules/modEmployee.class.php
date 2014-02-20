<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2013	     Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *      \defgroup   Module employee
 *      \brief      Module to manage employees of a company
 *		  \file       htdocs/core/modules/modEmployee.class.php
 *      \ingroup    employee
 *      \brief      File descriptor or module employee
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 *  Classe de description et activation du module Adherent
 */
class modEmployee extends DolibarrModules
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
        $this->numero = 21000;

        $this->family = "hr";
		    // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		    $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Gestion des salariés de l'entreprise";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/employee/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("employee.php@employees");

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array('modMailmanSpip');
        $this->langfiles = array("employees","companies");

        // Constantes
        //-----------
        $this->const = array();
        
        $this->const[2]  = array("MAIN_SEARCHFORM_EMPLOYEE","yesno","1","Show form for quick employee search");
        $this->const[21] = array("EMPLOYEE_MAIL_FROM","chaine","","From des mails");
        $this->const[23] = array("EMPLOYEE_MAIL_COTIS_SUBJECT","chaine","Fiche salarié","Sujet du mail de validation de la fiche salarié");
        $this->const[25] = array("EMPLOYEE_CARD_HEADER_TEXT","chaine","%ANNEE%","Texte imprimé sur le haut de la fiche salarié");
        $this->const[26] = array("EMPLOYEE_CARD_FOOTER_TEXT","chaine","Société AZERTY","Texte imprimé sur le bas de la fiche salarié");
        $this->const[27] = array("EMPLOYEE_CARD_TEXT","texte","%FULLNAME%\r\nID: %ID%\r\n%EMAIL%\r\n%ADDRESS%\r\n%ZIP% %TOWN%\r\n%COUNTRY%","Text to print on employee cards");
        $this->const[28] = array("EMPLOYEE_MAILMAN_ADMINPW","chaine","","Mot de passe Admin des liste mailman");
        $this->const[34] = array("EMPLOYEE_ETIQUETTE_TYPE","chaine","L7163","Type of address sheets");
        $this->const[35] = array("EMPLOYEE_ETIQUETTE_TEXT",'texte',"%FULLNAME%\n%ADDRESS%\n%ZIP% %TOWN%\n%COUNTRY%","Text to print on employee address sheets");
               
        // Boxes
        //-------
        $this->boxes = array();
        $r=0;
        $this->boxes[$r][1] = "box_employees.php";

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'employee';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $r++;
        $this->rights[$r][0] = 21001;
        $this->rights[$r][1] = 'Read employees\' card';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 21002;
        $this->rights[$r][1] = 'Create/modify employees (need also user module permissions if member linked to a user)';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 21003;
        $this->rights[$r][1] = 'Remove employees';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';

        $r++;
        $this->rights[$r][0] = 21004;
        $this->rights[$r][1] = 'Export employees';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';

        $r++;
        $this->rights[$r][0] = 21005;
        $this->rights[$r][1] = 'Setup types and attributes of employees';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configurer';

        $r++;
        $this->rights[$r][0] = 21100;
        $this->rights[$r][1] = 'Read salaries';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'salary';
        $this->rights[$r][5] = 'lire';

        $r++;
        $this->rights[$r][0] = 21101;
        $this->rights[$r][1] = 'Create/modify/remove salaries';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'salary';
        $this->rights[$r][5] = 'creer';

        // Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les donnees a l'export

        $r++;
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='Employees';
        $this->export_permission[$r]=array(array("employee","export"));
        $this->export_fields_array[$r]=array('e.rowid'=>'Id','e.civility'=>"UserTitle",'e.lastname'=>"Lastname",'e.firstname'=>"Firstname",'e.login'=>"Login",'e.sex'=>'Sex','e.address'=>"Address",'e.zip'=>"Zip",'e.town'=>"Town",'d.nom'=>"State",'p.code'=>"CountryCode",'p.libelle'=>"Country",'e.phone_pro'=>"PhonePro",'e.phone_perso'=>"PhonePerso",'e.phone_mobile'=>"PhoneMobile",'e.email'=>"Email",'e.birth'=>"Birthday",'e.statut'=>"Status",'e.photo'=>"Photo",'e.note'=>"Note",'e.datec'=>'DateCreation','e.datevalid'=>'DateValidation','e.tms'=>'DateLastModification','te.rowid'=>'EmployeeTypeId','te.label'=>'EmployeeTypeLabel');
        $this->export_TypeFields_array[$r]=array('e.civility'=>"Text",'e.lastname'=>"Text",'e.firstname'=>"Text",'e.login'=>"Text",'e.sex'=>'Text','e.address'=>"Text",'e.zip'=>"Text",'e.town'=>"Text",'d.nom'=>"Text",'p.code'=>'Text','p.libelle'=>"Text",'e.phone_pro'=>"Text",'e.phone_perso'=>"Text",'e.phone_mobile'=>"Text",'e.email'=>"Text",'e.birth'=>"Date",'e.statut'=>"Status",'e.note'=>"Text",'e.datec'=>'Date','e.datevalid'=>'Date','e.tms'=>'Date','te.rowid'=>'List:fk_employee_type:label','te.label'=>'Text');
        $this->export_entities_array[$r]=array('e.rowid'=>'employee','e.civilite'=>"employee",'e.lastname'=>"employee",'e.firstname'=>"employee",'e.login'=>"employee",'e.sex'=>'employee','e.address'=>"employee",'e.zip'=>"employee",'e.town'=>"employee",'d.nom'=>"employee",'p.code'=>"employee",'p.libelle'=>"employee",'e.phone_pro'=>"employee",'e.phone_perso'=>"employee",'e.phone_mobile'=>"employee",'e.email'=>"employee",'e.birth'=>"employee",'e.statut'=>"employee",'e.photo'=>"employee",'e.note'=>"employee",'e.datec'=>'employee','e.datevalid'=>'employee','e.tms'=>'employee','te.rowid'=>'employee_type','te.label'=>'employee_type');
    		
        // Add extra fields
    		$sql="SELECT name, label FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'employee' AND entity = ".$conf->entity;
    		$resql=$this->db->query($sql);
    		while ($obj=$this->db->fetch_object($resql))
    		{
    			$fieldname='extra.'.$obj->name;
    			$fieldlabel=ucfirst($obj->label);
    			$this->export_fields_array[$r][$fieldname]=$fieldlabel;
    			$this->export_entities_array[$r][$fieldname]='employee';
    		}
    		
        // End add axtra fields
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'employee_type as te, '.MAIN_DB_PREFIX.'employeet as e)';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'employee_extrafields as extra ON e.rowid = extra.fk_object';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON e.state_id = d.rowid';
		    $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as p ON e.country = p.rowid';
        $this->export_sql_end[$r] .=' WHERE e.fk_employee_type = te.rowid';
        
        // Imports
        //--------
        $r=0;

        $now=dol_now();
        require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

        $r++;
        $this->import_code[$r]=$this->rights_class.'_'.$r;
        $this->import_label[$r]="Employees"; // Translation key
        $this->import_icon[$r]=$this->picto;
        $this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
        $this->import_tables_array[$r]=array('a'=>MAIN_DB_PREFIX.'employee','extra'=>MAIN_DB_PREFIX.'employee_extrafields');
        $this->import_tables_creator_array[$r]=array('e'=>'fk_user_author');    // Fields to store import user id
        $this->import_fields_array[$r]=array('e.civility'=>"UserTitle",'e.lastname'=>"Lastname*",'e.firstname'=>"Firstname",'e.login'=>"Login*","e.pass"=>"Password","e.fk_employee_type"=>"EmployeeType*",'e.sex'=>'Nature*','e.address'=>"Address",'e.zip'=>"Zip",'e.town'=>"Town",'e.state_id'=>'StateId','e.country'=>"CountryId",'e.phone_pro'=>"PhonePro",'e.phone_perso'=>"PhonePerso",'e.phone_mobile'=>"PhoneMobile",'e.email'=>"Email",'e.birth'=>"Birthday",'e.statut'=>"Status*",'e.photo'=>"Photo",'e.note'=>"Note",'e.datec'=>'DateCreation');
    		// Add extra fields
    		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'employee' AND entity = ".$conf->entity;
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
    		$this->import_fieldshidden_array[$r]=array('extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'employee');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
    		$this->import_regex_array[$r]=array('e.civility'=>'code@'.MAIN_DB_PREFIX.'c_civility','e.fk_employee_type'=>'rowid@'.MAIN_DB_PREFIX.'employee_type','e.sex'=>'(sex)','e.statut'=>'^[0|1]','e.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$');
        $this->import_examplevalues_array[$r]=array('e.civility'=>"MR",'e.lastname'=>'Smith','e.firstname'=>'John','e.login'=>'jsmith','e.pass'=>'passofjsmith','e.fk_employee_type'=>'1','e.sex'=>'"sex"','e.address'=>'21 jump street','e.zip'=>'55000','e.town'=>'New York','e.country'=>'1','e.email'=>'jsmith@example.com','e.birth'=>'1972-10-10','e.statut'=>"0 or 1",'e.note'=>"This is a comment on employee",'e.datec'=>dol_print_date($now,'%Y-%m-%d'));
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

        $sql = array();

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
