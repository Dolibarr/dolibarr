<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  }

   /**
    *   \brief      Fonction appelé lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {       
    /*
     * Permissions et valeurs par défaut
     */
		 $this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (20,'Tous les droits sur les propositions commerciales','propale','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (21,'Lire les propositions commerciales','propale','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (22,'Créer modifier les propositions commerciales','propale','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (24,'Valider les propositions commerciales','propale','d',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (25,'Envoyer les propositions commerciales aux clients','propale','d',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (26,'Clôturer les propositions commerciales','propale','d',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (27,'Supprimer les propositions commerciales','propale','d',0);",
		 "DELETE FROM ".MAIN_DB_PREFIX."propal_model_pdf WHERE nom = '".$this->const[0][2]."'",
		 "INSERT INTO ".MAIN_DB_PREFIX."propal_model_pdf (nom) 
		 VALUES('".$this->const[0][2]."');",
		 );
    //"INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (23,'Modifier les propositions commerciales d\'autrui','propale','m',0);",
    
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
