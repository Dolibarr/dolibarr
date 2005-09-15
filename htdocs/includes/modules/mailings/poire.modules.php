<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
       	\file       htdocs/includes/modules/mailings/poire.modules.php
		\ingroup    mailing
		\brief      Fichier de la classe permettant de générer la liste de destinataires Poire
		\version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
	    \class      mailing_poire
		\brief      Classe permettant de générer la liste des destinataires Poire
*/

class mailing_poire extends MailingTargets
{
    var $name='ContactCompanies';                       // Identifiant du module mailing
    var $desc='Contacts des sociétés';                  // Libellé utilisé si aucune traduction pour MailingModuleDescXXX ou XXX=name trouvée
    var $require_module=array("commercial");            // Module mailing actif si modules require_module actifs
    var $require_admin=0;                               // Module mailing actif pour user admin ou non
    var $picto='contact';
    
    var $db;
    var $statssql=array();
    

    function mailing_poire($DB)
    {
        global $langs;
        $langs->load("commercial");

        $this->db=$DB;

        // Liste des tableaux des stats espace mailing
        //$this->statssql[0]="SELECT '".$langs->trans("Customers")."' as label, count(*) as nb FROM ".MAIN_DB_PREFIX."societe WHERE client = 1";
        $this->statssql[0]="SELECT '".$langs->trans("NbOfCompaniesContacts")."' as label, count(distinct(c.email)) as nb FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."societe as s WHERE s.idp = c.fk_soc AND s.client = 1 AND c.email != ''";
    }
    
    function getNbOfRecipients()
    {
        // La requete doit retourner: nb
        $sql  = "SELECT count(distinct(c.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE s.idp = c.fk_soc";
        $sql .= " AND s.client = 1";
        $sql .= " AND c.email != ''";

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
        $langs->load("commercial");
        $langs->load("suppliers");
        
        $s='';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="all">'.$langs->trans("All").'</option>';
        $s.='<option value="prospects">'.$langs->trans("Prospects").'</option>';
        $s.='<option value="customers">'.$langs->trans("Customers").'</option>';
        $s.='<option value="suppliers">'.$langs->trans("Suppliers").'</option>';
        $s.='</select>';
        return $s;
    }
    
    
    /**
     *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
     *      \return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$id.'">'.img_object('',"contact").'</a>';
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
        $sql = "SELECT s.idp as id, c.email as email, c.idp as fk_contact, c.name as name, c.firstname as firstname";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
        $sql .= ", ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE s.idp = c.fk_soc";
        $sql .= " AND c.email != ''";
        foreach($filtersarray as $key)
        {
            if ($key == 'prospects') $sql.= " AND s.client=2";
            if ($key == 'customers') $sql.= " AND s.client=1";
            if ($key == 'suppliers') $sql.= " AND s.fournisseur=1";
        }
        $sql .= " ORDER BY c.email";

        return parent::add_to_target($mailing_id, $sql);
    }

}

?>
