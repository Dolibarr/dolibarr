<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 *      \defgroup   member     Module foundation
 *      \brief      Module to manage members of a foundation
 *		\file       htdocs/core/modules/modAdherent.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Member
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Classe de description et activation du module Adherent
 */
class modAdherent extends DolibarrModules
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
        $this->numero = 310;

        $this->family = "hr";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Gestion des adhérents d'une association";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/adherent/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("adherent.php@adherents");

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array('modMailmanSpip');
        $this->langfiles = array("members","companies");

        // Constantes
        //-----------
        $this->const = array();
        $this->const[2]  = array("MAIN_SEARCHFORM_ADHERENT","yesno","1","Show form for quick member search");
        $this->const[3]  = array("ADHERENT_MAIL_RESIL","texte","Votre adhésion vient d'être résiliée.\r\nNous espérons vous revoir très bientôt","Mail de résiliation");
        $this->const[4]  = array("ADHERENT_MAIL_VALID","texte","Votre adhésion vient d'être validée. \r\nVoici le rappel de vos coordonnées (toute information erronée entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\n","Mail de validation");
        $this->const[5]  = array("ADHERENT_MAIL_VALID_SUBJECT","chaine","Votre adhésion a été validée","Sujet du mail de validation");
        $this->const[6]  = array("ADHERENT_MAIL_RESIL_SUBJECT","chaine","Résiliation de votre adhésion","Sujet du mail de résiliation");
        $this->const[21] = array("ADHERENT_MAIL_FROM","chaine","","From des mails");
        $this->const[22] = array("ADHERENT_MAIL_COTIS","texte","Bonjour %FIRSTNAME%,\r\nCet email confirme que votre cotisation a été reçue\r\net enregistrée","Mail de validation de cotisation");
        $this->const[23] = array("ADHERENT_MAIL_COTIS_SUBJECT","chaine","Reçu de votre cotisation","Sujet du mail de validation de cotisation");
        $this->const[25] = array("ADHERENT_CARD_HEADER_TEXT","chaine","%ANNEE%","Texte imprimé sur le haut de la carte adhérent");
        $this->const[26] = array("ADHERENT_CARD_FOOTER_TEXT","chaine","Association AZERTY","Texte imprimé sur le bas de la carte adhérent");
        $this->const[27] = array("ADHERENT_CARD_TEXT","texte","%FULLNAME%\r\nID: %ID%\r\n%EMAIL%\r\n%ADDRESS%\r\n%ZIP% %TOWN%\r\n%COUNTRY%","Text to print on member cards");
        $this->const[28] = array("ADHERENT_MAILMAN_ADMINPW","chaine","","Mot de passe Admin des liste mailman");
        $this->const[31] = array("ADHERENT_BANK_USE_AUTO","yesno","","Insertion automatique des cotisations dans le compte banquaire");
        $this->const[32] = array("ADHERENT_BANK_ACCOUNT","chaine","","ID du Compte banquaire utilise");
        $this->const[33] = array("ADHERENT_BANK_CATEGORIE","chaine","","ID de la catégorie banquaire des cotisations");
        $this->const[34] = array("ADHERENT_ETIQUETTE_TYPE","chaine","L7163","Type of address sheets");
        $this->const[35] = array("ADHERENT_ETIQUETTE_TEXT",'texte',"%FULLNAME%\n%ADDRESS%\n%ZIP% %TOWN%\n%COUNTRY%","Text to print on member address sheets");

        // Boxes
        //-------
        $this->boxes = array();
        $r=0;
        $this->boxes[$r][1] = "box_members.php";

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'adherent';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $r++;
        $this->rights[$r][0] = 71;
        $this->rights[$r][1] = 'Read members\' card';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 72;
        $this->rights[$r][1] = 'Create/modify members (need also user module permissions if member linked to a user)';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 74;
        $this->rights[$r][1] = 'Remove members';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';

        $r++;
        $this->rights[$r][0] = 76;
        $this->rights[$r][1] = 'Export members';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';

        $r++;
        $this->rights[$r][0] = 75;
        $this->rights[$r][1] = 'Setup types and attributes of members';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configurer';

        $r++;
        $this->rights[$r][0] = 78;
        $this->rights[$r][1] = 'Read subscriptions';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'lire';

        $r++;
        $this->rights[$r][0] = 79;
        $this->rights[$r][1] = 'Create/modify/remove subscriptions';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cotisation';
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
        $this->export_label[$r]='MembersAndSubscriptions';
        $this->export_permission[$r]=array(array("adherent","export"));
        $this->export_fields_array[$r]=array('a.rowid'=>'Id','a.civilite'=>"UserTitle",'a.lastname'=>"Lastname",'a.firstname'=>"Firstname",'a.login'=>"Login",'a.morphy'=>'Nature','a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",'a.country'=>"Country",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status",'a.photo'=>"Photo",'a.note'=>"Note",'a.datec'=>'DateCreation','a.datevalid'=>'DateValidation','a.tms'=>'DateLastModification','a.datefin'=>'DateEndSubscription','ta.rowid'=>'MemberTypeId','ta.libelle'=>'MemberTypeLabel','c.rowid'=>'SubscriptionId','c.dateadh'=>'DateSubscription','c.cotisation'=>'Amount');
        $this->export_TypeFields_array[$r]=array('a.civilite'=>"Text",'a.lastname'=>"Text",'a.firstname'=>"Text",'a.login'=>"Text",'a.morphy'=>'Text','a.societe'=>'Text','a.address'=>"Text",'a.zip'=>"Text",'a.town'=>"Text",'a.country'=>"Text",'a.phone'=>"Text",'a.phone_perso'=>"Text",'a.phone_mobile'=>"Text",'a.email'=>"Text",'a.birth'=>"Date",'a.statut'=>"Status",'a.note'=>"Text",'a.datec'=>'Date','a.datevalid'=>'Date','a.tms'=>'Date','a.datefin'=>'Date','ta.rowid'=>'List:fk_adherent_type:libelle','ta.libelle'=>'Text','c.dateadh'=>'Date','c.cotisation'=>'Number');
        $this->export_entities_array[$r]=array('a.rowid'=>'member','a.civilite'=>"member",'a.lastname'=>"member",'a.firstname'=>"member",'a.login'=>"member",'a.morphy'=>'member','a.societe'=>'member','a.address'=>"member",'a.zip'=>"member",'a.town'=>"member",'a.country'=>"member",'a.phone'=>"member",'a.phone_perso'=>"member",'a.phone_mobile'=>"member",'a.email'=>"member",'a.birth'=>"member",'a.statut'=>"member",'a.photo'=>"member",'a.note'=>"member",'a.datec'=>'member','a.datevalid'=>'member','a.tms'=>'member','a.datefin'=>'member','ta.rowid'=>'member_type','ta.libelle'=>'member_type','c.rowid'=>'subscription','c.dateadh'=>'subscription','c.cotisation'=>'subscription');
		// Add extra fields
		$sql="SELECT name, label FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'member' AND entity = ".$conf->entity;
		$resql=$this->db->query($sql);
		while ($obj=$this->db->fetch_object($resql))
		{
			$fieldname='extra.'.$obj->name;
			$fieldlabel=ucfirst($obj->label);
			$this->export_fields_array[$r][$fieldname]=$fieldlabel;
			$this->export_entities_array[$r][$fieldname]='member';
		}
		// End add axtra fields
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'adherent_type as ta, '.MAIN_DB_PREFIX.'adherent as a)';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'adherent_extrafields as extra ON a.rowid = extra.fk_object';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'cotisation as c ON c.fk_adherent = a.rowid';
        $this->export_sql_end[$r] .=' WHERE a.fk_adherent_type = ta.rowid';
        $this->export_dependencies_array[$r]=array('subscription'=>'c.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

        // Imports
        //--------
        $r=0;

        $now=dol_now();
        require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

        $r++;
        $this->import_code[$r]=$this->rights_class.'_'.$r;
        $this->import_label[$r]="Members"; // Translation key
        $this->import_icon[$r]=$this->picto;
        $this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
        $this->import_tables_array[$r]=array('a'=>MAIN_DB_PREFIX.'adherent','extra'=>MAIN_DB_PREFIX.'adherent_extrafields');
        $this->import_tables_creator_array[$r]=array('a'=>'fk_user_author');    // Fields to store import user id
        $this->import_fields_array[$r]=array('a.civilite'=>"UserTitle",'a.lastname'=>"Lastname*",'a.firstname'=>"Firstname",'a.login'=>"Login*","a.pass"=>"Password","a.fk_adherent_type"=>"MemberType*",'a.morphy'=>'Nature*','a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",'a.country'=>"Country",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status*",'a.photo'=>"Photo",'a.note'=>"Note",'a.datec'=>'DateCreation','a.datefin'=>'DateEndSubscription');
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'member' AND entity = ".$conf->entity;
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
		$this->import_fieldshidden_array[$r]=array('extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'adherent');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_regex_array[$r]=array('a.civilite'=>'code@'.MAIN_DB_PREFIX.'c_civilite','a.fk_adherent_type'=>'rowid@'.MAIN_DB_PREFIX.'adherent_type','a.morphy'=>'(phy|mor)','a.statut'=>'^[0|1]','a.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','a.datefin'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$');
        $this->import_examplevalues_array[$r]=array('a.civilite'=>"MR",'a.lastname'=>'Smith','a.firstname'=>'John','a.login'=>'jsmith','a.pass'=>'passofjsmith','a.fk_adherent_type'=>'1','a.morphy'=>'"mor" or "phy"','a.societe'=>'JS company','a.address'=>'21 jump street','a.zip'=>'55000','a.town'=>'New York','a.country'=>'1','a.email'=>'jsmith@example.com','a.birth'=>'1972-10-10','a.statut'=>"0 or 1",'a.note'=>"This is a comment on member",'a.datec'=>dol_print_date($now,'%Y-%m-%d'),'a.datefin'=>dol_print_date(dol_time_plus_duree($now, 1, 'y'),'%Y-%m-%d'));
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
