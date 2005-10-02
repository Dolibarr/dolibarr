<?php
/* Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 *
 * L'utilisation d'adresses de courriers électroniques dans les opérations
 * de prospection commerciale est subordonnée au recueil du consentement 
 * préalable des personnes concernées.
 *
 * Le dispositif juridique applicable a été introduit par l'article 22 de 
 * la loi du 21 juin 2004  pour la confiance dans l'économie numérique.
 *
 * Les dispositions applicables sont définies par les articles L. 34-5 du 
 * code des postes et des télécommunications et L. 121-20-5 du code de la 
 * consommation. L'application du principe du consentement préalable en 
 * droit français résulte de la transposition de l'article 13 de la Directive 
 * européenne du 12 juillet 2002 « Vie privée et communications électroniques ». 
 *
 */

/**
       	\file       htdocs/includes/modules/mailings/fraise.modules.php
		\ingroup    mailing
		\brief      Fichier de la classe permettant de générer la liste de destinataires Fraise
		\version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
	    \class      mailing_fraise
		\brief      Classe permettant de générer la liste des destinataires Fraise
*/

class mailing_fraise extends MailingTargets
{
    var $name='FundationMembers';                    // Identifiant du module mailing
    var $desc='Membres de l\'association';           // Libellé utilisé si aucune traduction pour MailingModuleDescXXX ou XXX=name trouvée
    var $require_module=array('adherent');  // Module mailing actif si modules require_module actifs
    var $require_admin=0;                   // Module mailing actif pour user admin ou non
    var $picto='user';
    
    var $db;
    var $statssql=array();


    function mailing_fraise($DB)
    {
        global $langs;
        $langs->load("members");
        
        $this->db=$DB;

        // Liste des tableaux des stats espace mailing
        $this->statssql[0]="SELECT '".addslashes($langs->trans("FundationMembers"))."' as label, count(*) as nb FROM ".MAIN_DB_PREFIX."adherent where statut = 1";
    }
    
    function getNbOfRecipients()
    {
        // La requete doit retourner: nb
        $sql  = "SELECT count(distinct(a.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql .= " WHERE a.email IS NOT NULL";

        return parent::getNbOfRecipients($sql); 
    }
    
    /**
     *      \brief      Affiche formulaire de filtre qui apparait dans page de selection
     *                  des destinataires de mailings
     *      \return     string      Retourne zone select
     */
    function formFilter()
    {
        global $langs;
        $s='';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="-1">'.$langs->trans("ToValidate").'</option>';
        $s.='<option value="1" selected="true">'.$langs->trans("Validated").'</option>';
        $s.='<option value="0">'.$langs->trans("Resiliated").'</option>';
        $s.='</select>';
        return $s;
    }
    
    
    /**
     *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
     *      \return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$id.'">'.img_object('',"user").'</a>';
    }
    
    
    /**
     *    \brief      Ajoute destinataires dans table des cibles
     *    \param      mailing_id    Id du mailing concerné
     *    \param      filterarray   Requete sql de selection des destinataires
     *    \return     int           < 0 si erreur, nb ajout si ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
        // La requete doit retourner: id, email, fk_contact, name, firstname
        $sql = "SELECT a.rowid as id, a.email as email, null as fk_contact, a.nom as name, a.prenom as firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.email IS NOT NULL";
        foreach($filtersarray as $key)
        {
            if ($key == -1) $sql.= " AND a.statut=-1";
            if ($key == 0)  $sql.= " AND a.statut=0";
            if ($key == 1)  $sql.= " AND a.statut=1";
        }
        $sql.= " ORDER BY a.email";

        return parent::add_to_target($mailing_id, $sql);
    }

}

?>
