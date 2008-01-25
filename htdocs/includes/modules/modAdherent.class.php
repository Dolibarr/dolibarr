<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
        \defgroup   adherent     Module adherents
        \brief      Module pour gerer les adherents d'une association
*/

/**
        \file       htdocs/includes/modules/modAdherent.class.php
        \ingroup    adherent
        \brief      Fichier de description et activation du module adherents
		\version	$Id$
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modAdherent extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acc�s base
     */
    function modAdherent($DB)
    {
        $this->db = $DB;
        $this->numero = 310 ;
    
        $this->family = "hr";
        $this->name = "Adherents";
        $this->description = "Gestion des adherents d'une association";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_ADHERENT';
        $this->special = 0;
        $this->picto='user';
    
        // Dir
        //----
        $this->dirs = array();
    
        // Config pages
        //-------------
        $this->config_page_url = array("adherent.php");
    
        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("members","companies");
    
        // Constantes
        //-----------
        $this->const = array();
        $this->const[0]  = array("ADHERENT_MAIL_RESIL","texte","Votre adhesion vient d'etre resiliee.\r\nNous esperons vous revoir tres bientot","Mail de resiliation");
        $this->const[1]  = array("ADHERENT_MAIL_VALID","texte","Votre adhesion vient d'etre validee. \r\nVoici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l'adresse suivante : \r\n%DOL_MAIN_URL_ROOT%/public/adherents/","Mail de validation");
        $this->const[3]  = array("ADHERENT_MAIL_RESIL","texte","Votre adhesion vient d'etre resilie.\r\nNous esperons vous revoir tres bientot","Mail de resiliation");
        $this->const[5]  = array("ADHERENT_MAIL_VALID_SUBJECT","chaine","Votre adhesion a ete validee","Sujet du mail de validation");
        $this->const[6]  = array("ADHERENT_MAIL_RESIL_SUBJECT","chaine","Resiliation de votre adhesion","Sujet du mail de resiliation");
        $this->const[10] = array("ADHERENT_MAILMAN_UNSUB_URL","chaine","http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%","Url de desinscription aux listes mailman");
        $this->const[11] = array("ADHERENT_MAILMAN_URL","chaine","http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%","Url pour les inscriptions mailman");
        $this->const[12] = array("ADHERENT_MAILMAN_LISTS","chaine","","Listes auxquelles les nouveaux adherents sont inscris");
        $this->const[16] = array("ADHERENT_USE_SPIP_AUTO","yesno","","Utilisation de SPIP automatiquement");
        $this->const[17] = array("ADHERENT_SPIP_USER","chaine","","Utilisateur de connexion a la base spip");
        $this->const[18] = array("ADHERENT_SPIP_PASS","chaine","","Mot de passe de connexion a la base spip");
        $this->const[19] = array("ADHERENT_SPIP_SERVEUR","chaine","","serveur spip");
        $this->const[20] = array("ADHERENT_SPIP_DB","chaine","","db spip");
        $this->const[21] = array("ADHERENT_MAIL_FROM","chaine","","From des mails");
        $this->const[22] = array("ADHERENT_MAIL_COTIS","texte","Bonjour %PRENOM%,\r\nCet email confirme que votre cotisation a ete recue\r\net enregistree","Mail de validation de cotisation");
        $this->const[23] = array("ADHERENT_MAIL_COTIS_SUBJECT","chaine","Recu de votre cotisation","Sujet du mail de validation de cotisation");
        $this->const[25] = array("ADHERENT_CARD_HEADER_TEXT","chaine","%ANNEE%","Texte imprime sur le haut de la carte adherent");
        $this->const[26] = array("ADHERENT_CARD_FOOTER_TEXT","chaine","Association AZERTY","Texte imprime sur le bas de la carte adherent");
        $this->const[27] = array("ADHERENT_CARD_TEXT","texte","%PRENOM% %NOM%\r\nMembre ne %ID%\r\n%EMAIL%\r\n%ADRESSE%\r\n%CP% %VILLE%\r\n%PAYS%","Texte imprime sur la carte adherent");
        $this->const[28] = array("ADHERENT_MAILMAN_ADMINPW","chaine","","Mot de passe Admin des liste mailman");
        $this->const[29] = array("ADHERENT_MAILMAN_SERVER","chaine","","Serveur hebergeant les interfaces d'Admin des listes mailman");
        $this->const[30] = array("ADHERENT_MAILMAN_LISTS_COTISANT","chaine","","Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement");
        $this->const[31] = array("ADHERENT_BANK_USE_AUTO","yesno","","Insertion automatique des cotisation dans le compte banquaire");
        $this->const[32] = array("ADHERENT_BANK_ACCOUNT","chaine","","ID du Compte banquaire utilise");
        $this->const[33] = array("ADHERENT_BANK_CATEGORIE","chaine","","ID de la categorie banquaire des cotisations");
        $this->const[34] = array("ADHERENT_ETIQUETTE_TYPE","chaine","L7163","Type d etiquette (pour impression de planche d etiquette)");
    
        // Boites
        //-------
        $this->boxes = array();
    
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
        $this->rights[$r][1] = 'Lire les fiche adherents';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';
    
        $r++;
        $this->rights[$r][0] = 72;
        $this->rights[$r][1] = 'Creer/modifier tous les adherents';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
    
        $r++;
        $this->rights[$r][0] = 73;
        $this->rights[$r][1] = 'Creer/modifier ses propres infos adherents';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'self';
        $this->rights[$r][5] = 'creer';

        $r++;
        $this->rights[$r][0] = 74;
        $this->rights[$r][1] = 'Supprimer les adherents';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';
    
        $r++;
        $this->rights[$r][0] = 76;
        $this->rights[$r][1] = 'Exporter les adherents';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';
    
        $r++;
        $this->rights[$r][0] = 75;
        $this->rights[$r][1] = 'Configurer les types et attributs des adherents';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configurer';

        $r++;
        $this->rights[$r][0] = 78;
        $this->rights[$r][1] = 'Lire les cotisations';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'lire';
    
        $r++;
        $this->rights[$r][0] = 79;
        $this->rights[$r][1] = 'Creer/modifier/supprimer les cotisations';
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
        $this->export_label[$r]='Adherents et adhesions';
        $this->export_permission[$r]=array(array("adherent","export"));
        $this->export_fields_array[$r]=array('a.rowid'=>'Id','a.nom'=>"Lastname",'a.prenom'=>"Firstname",'a.login'=>"Login",'a.morphy'=>'MorPhy','a.adresse'=>"Address",'a.cp'=>"Zip",'a.ville'=>"Town",'a.pays'=>"Country",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.naiss'=>"Birthday",'a.statut'=>"Status",'a.photo'=>"Photo",'a.note'=>"Note",'a.datec'=>'DateCreation','a.datevalid'=>'DateValidation','a.tms'=>'DateLastModification','a.datefin'=>'DateEndSubscription','ta.rowid'=>'MemberTypeId','ta.libelle'=>'MemberTypeLabel','c.dateadh'=>'DateSubscription','c.cotisation'=>'Amount');
		$this->export_entities_array[$r]=array('a.rowid'=>'member','a.nom'=>"member",'a.prenom'=>"member",'a.login'=>"member",'a.morphy'=>'member','a.adresse'=>"member",'a.cp'=>"member",'a.ville'=>"member",'a.pays'=>"member",'a.phone'=>"member",'a.phone_perso'=>"member",'a.phone_mobile'=>"member",'a.email'=>"member",'a.naiss'=>"member",'a.statut'=>"member",'a.photo'=>"member",'a.note'=>"member",'a.datec'=>'member','a.datevalid'=>'member','a.tms'=>'member','a.datefin'=>'member','ta.rowid'=>'member_type','ta.libelle'=>'member_type','c.dateadh'=>'subscription','c.cotisation'=>'subscription');
        $this->export_alias_array[$r]=array('a.rowid'=>'Id','a.nom'=>"lastname",'a.prenom'=>"firstname",'a.login'=>"login",'a.morphy'=>'morphy','a.adresse'=>"address",'a.cp'=>"zip",'a.ville'=>"town",'a.pays'=>"country",'a.phone'=>"phone",'a.phone_perso'=>"phone_perso",'a.phone_mobile'=>"phone_mobile",'a.email'=>"email",'a.naiss'=>"birthday",'a.statut'=>"status",'a.photo'=>'photo','a.note'=>'note','a.datec'=>'datec','a.datevalid'=>'datevalid','a.tms'=>'datem','a.datefin'=>'dateend','ta.rowid'=>'type_id','ta.libelle'=>'type_label','c.dateadh'=>'date_subscription','c.cotisation'=>'amount_subscription');
		// On complete avec champs options
		$sql='SELECT name, label FROM '.MAIN_DB_PREFIX.'adherent_options_label'; 
		$resql=$this->db->query($sql);
		while ($obj=$this->db->fetch_object($resql))
		{
			$fieldname='ao.'.$obj->name;
			$fieldlabel=ucfirst($obj->label);
			$this->export_fields_array[$r][$fieldname]=$fieldlabel;
			$this->export_entities_array[$r][$fieldname]='member';
			$this->export_alias_array[$r][$fieldname]='opt_'.$obj->name;
		}
		// Fin complement
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'adherent as a, '.MAIN_DB_PREFIX.'adherent_type as ta)';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'adherent_options as ao ON a.rowid = ao.adhid';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'cotisation as c ON c.fk_adherent = a.rowid';
        $this->export_sql_end[$r] .=' WHERE a.fk_adherent_type = ta.rowid';
    }

    
    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        global $conf;
        
        // Dir
        $this->dirs[0] = $conf->adherent->dir_output;
        $this->dirs[1] = $conf->adherent->dir_output."/photos";
        $this->dirs[2] = $conf->adherent->dir_export;
        
        $sql = array();
        
        return $this->_init($sql);
    }
    
    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
		$sql = array();
    
		return $this->_remove($sql);
    }

}
?>
