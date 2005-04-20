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
       	\file       htdocs/includes/modules/mailings/pomme.modules.php
		\ingroup    mailing
		\brief      Fichier de la classe permettant de générer la liste de destinataires Pomme
		\version    $Revision$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
	    \class      mailing_pomme
		\brief      Classe permettant de générer la liste des destinataires Pomme
*/

class mailing_pomme extends MailingTargets
{
    var $name='DolibarrUsers';                      // Identifiant du module mailing
    var $desc='Tous les utilisateurs avec emails de Dolibarr';  // Libellé utilisé si aucune traduction pour MailingModuleDescXXX ou XXX=name trouvée
    var $require_module=array();                    // Module mailing actif si modules require_module actifs
    var $require_admin=1;                           // Module mailing actif pour user admin ou non
    var $picto='user';

    var $db;
    var $statssql=array();


    function mailing_pomme($DB)
    {
        global $langs;
        $langs->load("users");
        
        $this->db=$DB;

        // Liste des tableaux des stats espace mailing
        $sql = "SELECT '".$langs->trans("DolibarrUsers")."' as label, count(distinct(email)) as nb FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
        $this->statssql[0]=$sql;
        
    }
    
    function getNbOfRecipients()
    {
        // La requete doit retourner: nb
        $sql  = "SELECT count(distinct(u.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test

        return parent::getNbOfRecipients($sql); 
    }
    
    function add_to_target($mailing_id)
    {
        // La requete doit retourner: email, fk_contact, name, firstname
        $sql = "SELECT u.email as email, null as fk_contact, u.name as name, u.firstname as firstname";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
        $sql .= " ORDER BY u.email";

        return parent::add_to_target($mailing_id, $sql);
    }

}

?>
