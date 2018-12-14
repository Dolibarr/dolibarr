<?php
/* Copyright (C) 2003,2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2013       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@zendsi.com>
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
 *  Class to describe and enable module Adherent
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
        $this->module_position = '55';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Management of members of a foundation or association";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/adherent/temp");

        // Config pages
        $this->config_page_url = array("adherent.php@adherents");

        // Dependencies
        $this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array('modMailmanSpip');	// List of module class names as string this module is in conflict with
        $this->langfiles = array("members","companies");
        $this->phpmin = array(5,4);		// Minimum version of PHP required by module

        // Constants
        $this->const = array();
        $r=0;

        $this->const[$r][0] = "ADHERENT_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "standard";
        $this->const[$r][3] = 'Name of PDF model of member';
        $this->const[$r][4] = 0;
        $r++;

        // For emails
        $this->const[$r][0] = "ADHERENT_MAIL_FROM";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "From des mails";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER";
        $this->const[$r][1] = "emailtemplate:member";
        $this->const[$r][2] = "(SendingEmailOnAutoSubscription)";
        $this->const[$r][3] = "";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION";
        $this->const[$r][1] = "emailtemplate:member";
        $this->const[$r][2] = "(SendingEmailOnNewSubscription)";
        $this->const[$r][3] = "";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION";
        $this->const[$r][1] = "emailtemplate:member";
        $this->const[$r][2] = "(SendingReminderForExpiredSubscription)";
        $this->const[$r][3] = "";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_EMAIL_TEMPLATE_CANCELATION";
        $this->const[$r][1] = "emailtemplate:member";
        $this->const[$r][2] = "(SendingEmailOnCancelation)";
        $this->const[$r][3] = "";
        $this->const[$r][4] = 0;
        $r++;

        // For cards
        $this->const[$r][0] = "ADHERENT_CARD_HEADER_TEXT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "__YEAR__";
        $this->const[$r][3] = "Texte imprimé sur le haut de la carte adhérent";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_CARD_FOOTER_TEXT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "__COMPANY__";
        $this->const[$r][3] = "Texte imprimé sur le bas de la carte adhérent";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_CARD_TEXT";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "__FULLNAME__\r\nID: __ID__\r\n__EMAIL__\r\n__ADDRESS__\r\n__ZIP__ __TOWN__\r\n__COUNTRY__";
        $this->const[$r][3] = "Text to print on member cards";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAILMAN_ADMINPW";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "Mot de passe Admin des liste mailman";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_ETIQUETTE_TYPE";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "L7163";
        $this->const[$r][3] = "Type of address sheets";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_ETIQUETTE_TEXT";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "__FULLNAME__\n__ADDRESS__\n__ZIP__ __TOWN__\n__COUNTRY%";
        $this->const[$r][3] = "Text to print on member address sheets";
        $this->const[$r][4] = 0;
        $r++;

        // For subscriptions
        $this->const[$r][0] = "ADHERENT_BANK_ACCOUNT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "ID of bank account to use";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_BANK_CATEGORIE";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "ID of bank transaction category to use";
        $this->const[$r][4] = 0;
        $r++;


        // Boxes
        //-------
        $this->boxes = array(0=>array('file'=>'box_members.php','enabledbydefaulton'=>'Home'));

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
        $this->rights[$r][3] = 0;
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
        $this->rights[$r][1] = 'Setup types of membership';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configurer';

        $r++;
        $this->rights[$r][0] = 78;
        $this->rights[$r][1] = 'Read subscriptions';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'lire';

        $r++;
        $this->rights[$r][0] = 79;
        $this->rights[$r][1] = 'Create/modify/remove subscriptions';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'creer';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


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
        $this->export_fields_array[$r]=array(
			'a.rowid'=>'Id','a.civility'=>"UserTitle",'a.lastname'=>"Lastname",'a.firstname'=>"Firstname",'a.login'=>"Login",'a.gender'=>"Gender",'a.morphy'=>'Nature',
			'a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",'d.nom'=>"State",'co.code'=>"CountryCode",'co.label'=>"Country",
			'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status",
			'a.photo'=>"Photo",'a.note_public'=>"NotePublic",'a.note_private'=>"NotePrivate",'a.datec'=>'DateCreation','a.datevalid'=>'DateValidation',
			'a.tms'=>'DateLastModification','a.datefin'=>'DateEndSubscription','ta.rowid'=>'MemberTypeId','ta.libelle'=>'MemberTypeLabel',
			'c.rowid'=>'SubscriptionId','c.dateadh'=>'DateSubscription','c.subscription'=>'Amount'
		);
        $this->export_TypeFields_array[$r]=array(
			'a.civility'=>"Text",'a.lastname'=>"Text",'a.firstname'=>"Text",'a.login'=>"Text",'a.gender'=>'Text','a.morphy'=>'Text','a.societe'=>'Text','a.address'=>"Text",
			'a.zip'=>"Text",'a.town'=>"Text",'d.nom'=>"Text",'co.code'=>'Text','co.label'=>"Text",'a.phone'=>"Text",'a.phone_perso'=>"Text",'a.phone_mobile'=>"Text",
			'a.email'=>"Text",'a.birth'=>"Date",'a.statut'=>"Status",'a.note_public'=>"Text",'a.note_private'=>"Text",'a.datec'=>'Date','a.datevalid'=>'Date',
			'a.tms'=>'Date','a.datefin'=>'Date','ta.rowid'=>'List:adherent_type:libelle','ta.libelle'=>'Text','c.rowid'=>'Numeric','c.dateadh'=>'Date','c.subscription'=>'Numeric'
		);
        $this->export_entities_array[$r]=array(
			'a.rowid'=>'member','a.civility'=>"member",'a.lastname'=>"member",'a.firstname'=>"member",'a.login'=>"member",'a.gender'=>'member','a.morphy'=>'member',
			'a.societe'=>'member','a.address'=>"member",'a.zip'=>"member",'a.town'=>"member",'d.nom'=>"member",'co.code'=>"member",'co.label'=>"member",
			'a.phone'=>"member",'a.phone_perso'=>"member",'a.phone_mobile'=>"member",'a.email'=>"member",'a.birth'=>"member",'a.statut'=>"member",
			'a.photo'=>"member",'a.note_public'=>"member",'a.note_private'=>"member",'a.datec'=>'member','a.datevalid'=>'member','a.tms'=>'member',
			'a.datefin'=>'member','ta.rowid'=>'member_type','ta.libelle'=>'member_type','c.rowid'=>'subscription','c.dateadh'=>'subscription','c.subscription'=>'subscription'
		);
        // Add extra fields
        $keyforselect='adherent'; $keyforelement='member'; $keyforaliasextra='extra';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add axtra fields
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'adherent_type as ta, '.MAIN_DB_PREFIX.'adherent as a)';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'adherent_extrafields as extra ON a.rowid = extra.fk_object';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'subscription as c ON c.fk_adherent = a.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON a.state_id = d.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON a.country = co.rowid';
        $this->export_sql_end[$r] .=' WHERE a.fk_adherent_type = ta.rowid AND ta.entity IN ('.getEntity('member_type').') ';
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
        $this->import_fields_array[$r]=array(
			'a.civility'=>"UserTitle",'a.lastname'=>"Lastname*",'a.firstname'=>"Firstname",'a.gender'=>"Gender",'a.login'=>"Login*","a.pass"=>"Password",
			"a.fk_adherent_type"=>"MemberType*",'a.morphy'=>'Nature*','a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",
			'a.state_id'=>'StateId','a.country'=>"CountryId",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",
			'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status*",'a.photo'=>"Photo",'a.note_public'=>"NotePublic",'a.note_private'=>"NotePrivate",
			'a.datec'=>'DateCreation','a.datefin'=>'DateEndSubscription'
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'adherent' AND entity IN (0,".$conf->entity.")";
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
		$this->import_regex_array[$r]=array(
			'a.civility'=>'code@'.MAIN_DB_PREFIX.'c_civility','a.fk_adherent_type'=>'rowid@'.MAIN_DB_PREFIX.'adherent_type','a.morphy'=>'(phy|mor)',
			'a.statut'=>'^[0|1]','a.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','a.datefin'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$');
        $this->import_examplevalues_array[$r]=array(
			'a.civility'=>"MR",'a.lastname'=>'Smith','a.firstname'=>'John','a.login'=>'jsmith','a.pass'=>'passofjsmith','a.fk_adherent_type'=>'1',
			'a.morphy'=>'"mor" or "phy"','a.societe'=>'JS company','a.address'=>'21 jump street','a.zip'=>'55000','a.town'=>'New York','a.country'=>'1',
			'a.email'=>'jsmith@example.com','a.birth'=>'1972-10-10','a.statut'=>"0 or 1",'a.note_public'=>"This is a public comment on member",
			'a.note_private'=>"This is private comment on member",'a.datec'=>dol_print_date($now,'%Y-%m__%d'),'a.datefin'=>dol_print_date(dol_time_plus_duree($now, 1, 'y'),'%Y-%m-%d')
		);

        // Cronjobs
        $arraydate=dol_getdate(dol_now());
        $datestart=dol_mktime(22, 0, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
        $this->cronjobs = array(
			0=>array(
				'label'=>'SendReminderForExpiredSubscriptionTitle',
				'jobtype'=>'method', 'class'=>'adherents/class/adherent.class.php',
				'objectname'=>'Adherent',
				'method'=>'sendReminderForExpiredSubscription',
				'parameters'=>'10;0',
				'comment'=>'SendReminderForExpiredSubscription',
				'frequency'=>1,
				'unitfrequency'=> 3600 * 24,
				'priority'=>50,
				'status'=>1,
				'test'=>'$conf->adherent->enabled',
				'datestart'=>$datestart
			),
        );
    }


    /**
     *		Function called when module is enabled.
     *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function init($options='')
    {
        global $conf,$langs;

        // Permissions
        $this->remove($options);

        //ODT template
        /*
        $src=DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt';
        $dirodt=DOL_DATA_ROOT.'/doctemplates/orders';
        $dest=$dirodt.'/template_order.odt';

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
        }*/

        $sql = array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type='member' AND entity = ".$conf->entity,
            "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','member',".$conf->entity.")"
        );

        return $this->_init($sql,$options);
    }
}
