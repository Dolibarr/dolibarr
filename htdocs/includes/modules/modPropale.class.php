<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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
 * $Source$
 *
 */

/*!     \defgroup   propale     Module propale
        \brief      Module pour gérer la tenue de propositions commerciales
*/

/*!
        \file       htdocs/includes/modules/modPropale.class.php
        \ingroup    propale
        \brief      Fichier de description et activation du module Propale
*/

include_once "DolibarrModules.class.php";

/*! \class modPropale
		\brief      Classe de description et activation du module Propale
*/

class modPropale extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modPropale($DB)
  {
    $this->db = $DB ;
    $this->numero = 20 ;
    
    $this->family = "crm";
    $this->name = "Propositions commerciales";
    $this->description = "Gestion des propositions commerciales";
    $this->const_name = "MAIN_MODULE_PROPALE";
    $this->const_config = MAIN_MODULE_PROPALE;
    $this->special = 0;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array("modSociete","modCommercial");
    $this->config_page_url = "propale.php";

    // Constantes
    $this->const = array();

    $this->const[0][0] = "PROPALE_ADDON_PDF";
    $this->const[0][1] = "chaine";
    $this->const[0][2] = "rouge";
    $this->const[0][3] = 'Nom du gestionnaire de génération des propales en PDF';
    $this->const[0][4] = 0;

    $this->const[1][0] = "PROPALE_ADDON";
    $this->const[1][1] = "chaine";
    $this->const[1][2] = "mod_propale_ivoire";
    $this->const[1][3] = 'Nom du gestionnaire de numérotation des propales';
    $this->const[1][4] = 0;

    // Boxes
    $this->boxes = array();
    $this->boxes[0][0] = "Proposition commerciales";
    $this->boxes[0][1] = "box_propales.php";

    // Permissions
    $this->rights = array();
    $this->rights_class = 'propale';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {       

    // Permissions et valeurs par défaut
    $this->remove();

    $this->rights[0][0] = 20; // id de la permission
    $this->rights[0][1] = 'Tous les droits sur les propositions commerciales'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[1][0] = 21; // id de la permission
    $this->rights[1][1] = 'Lire les propositions commerciales'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut

    $this->rights[2][0] = 22; // id de la permission
    $this->rights[2][1] = 'Créer modifier les propositions commerciales'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[3][0] = 24; // id de la permission
    $this->rights[3][1] = 'Valider les propositions commerciales'; // libelle de la permission
    $this->rights[3][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[3][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[4][0] = 25; // id de la permission
    $this->rights[4][1] = 'Envoyer les propositions commerciales aux clients'; // libelle de la permission
    $this->rights[4][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[4][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[5][0] = 26; // id de la permission
    $this->rights[5][1] = 'Clôturer les propositions commerciales'; // libelle de la permission
    $this->rights[5][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[5][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[6][0] = 27; // id de la permission
    $this->rights[6][1] = 'Supprimer les propositions commerciales'; // libelle de la permission
    $this->rights[6][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[6][3] = 0; // La permission est-elle une permission par défaut



    $sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."propal_model_pdf WHERE nom = '".$this->const[0][2]."'",
		 "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) 
		 VALUES('".$this->const[0][2]."');",
		 );
    
    return $this->_init($sql);

  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'propale';"
		 );

    return $this->_remove($sql);

  }
}
?>
