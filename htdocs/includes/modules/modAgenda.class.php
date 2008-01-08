<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \defgroup   adherent     Module adherents
        \brief      Module pour gerer les adherents d'une association
*/

/**
        \file       htdocs/includes/modules/modAgenda.class.php
        \ingroup    agenda
        \brief      Fichier de description et activation du module agenda
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modAgenda extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acc�s base
     */
    function modAgenda($DB)
    {
        $this->db = $DB;
        $this->id = 'agenda';   // Same value xxx than in file modXxx.class.php file
        $this->numero = 2400;
    
        $this->family = "projects";
        $this->name = "Agenda";
        $this->description = "Gestion de l'agenda et des actions";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_AGENDA';
        $this->special = 0;
        $this->picto='calendar';
    
        // Dir
        //----
        $this->dirs = array();
    
        // Config pages
        //-------------
        $this->config_page_url = array();
    
        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("companies");
    
        // Constantes
        //-----------
        $this->const = array();
    
        // Boites
        //-------
        $this->boxes = array();
        $this->boxes[0][1] = "box_actions.php";
    
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
        
        $r++;
        $this->rights[$r][0] = 2401;
        $this->rights[$r][1] = 'Lire les actions liees a son compte';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'read';
        
        $r++;
        $this->rights[$r][0] = 2402;
        $this->rights[$r][1] = 'Creer/modifier/supprimer les actions liees a son compte';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'create';
        
        $r++;
        $this->rights[$r][0] = 2403;
        $this->rights[$r][1] = 'Lire les actions des autres';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'read';
        
        $r++;
        $this->rights[$r][0] = 2405;
        $this->rights[$r][1] = 'Creer/modifier/supprimer les actions pour les autres';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'create';

        // Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les donnees a l'export
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export

        $r++;
        $this->export_code[$r]=$this->id.'_'.$r;
        $this->export_label[$r]='Adherents et adhesions';
        $this->export_fields_array[$r]=array('a.nom'=>"Lastname",'a.prenom'=>"Firstname",'a.login'=>"Login",'a.morphy'=>'MorPhy','a.adresse'=>"Address",'a.cp'=>"Zip",'a.ville'=>"Town",'a.pays'=>"Country",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.naiss'=>"Birthday",'a.statut'=>"Status",'a.photo'=>"Photo",'a.note'=>"Note",'a.datec'=>'DateCreation','a.datevalid'=>'DateValidation','a.tms'=>'DateLastModification','a.datefin'=>'DateEndSubscription','ta.rowid'=>'MemberTypeId','ta.libelle'=>'MemberTypeLabel','c.dateadh'=>'DateSubscription','c.cotisation'=>'Amount');
		$this->export_entities_array[$r]=array('a.nom'=>"member",'a.prenom'=>"member",'a.login'=>"member",'a.morphy'=>'member','a.adresse'=>"member",'a.cp'=>"member",'a.ville'=>"member",'a.pays'=>"member",'a.phone'=>"member",'a.phone_perso'=>"member",'a.phone_mobile'=>"member",'a.email'=>"member",'a.naiss'=>"member",'a.statut'=>"member",'a.photo'=>"member",'a.note'=>"member",'a.datec'=>'member','a.datevalid'=>'member','a.tms'=>'member','a.datefin'=>'member','ta.rowid'=>'member_type','ta.libelle'=>'member_type','c.dateadh'=>'subscription','c.cotisation'=>'subscription');
        $this->export_alias_array[$r]=array('a.nom'=>"lastname",'a.prenom'=>"firstname",'a.login'=>"login",'a.morphy'=>'morphy','a.adresse'=>"address",'a.cp'=>"zip",'a.ville'=>"town",'a.pays'=>"country",'a.phone'=>"phone",'a.phone_perso'=>"phone_perso",'a.phone_mobile'=>"phone_mobile",'a.email'=>"email",'a.naiss'=>"birthday",'a.statut'=>"status",'a.photo'=>'photo','a.note'=>'note','a.datec'=>'datec','a.datevalid'=>'datevalid','a.tms'=>'datem','a.datefin'=>'dateend','ta.rowid'=>'type_id','ta.libelle'=>'type_label','c.dateadh'=>'date_subscription','c.cotisation'=>'amount_subscription');
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
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from ('.MAIN_DB_PREFIX.'adherent as a, '.MAIN_DB_PREFIX.'adherent_type as ta)';
        $this->export_sql[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'adherent_options as ao ON a.rowid = ao.adhid';
        $this->export_sql[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'cotisation as c ON c.fk_adherent = a.rowid';
        $this->export_sql[$r].=' WHERE a.fk_adherent_type = ta.rowid';
        $this->export_permission[$r]=array(array("adherent","export"));
    }

    
    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        global $conf;
        
        // Permissions
        $this->remove();
        
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
