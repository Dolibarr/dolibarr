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

/**     \defgroup   facture     Module facture
        \brief      Module pour gérer les factures clients et/ou fournisseurs
*/


/** \file htdocs/includes/modules/modFacture.class.php
		\ingroup    facture
		\brief      Fichier de la classe de description et activation du module Facture
*/

include_once "DolibarrModules.class.php";


/** \class modFacture
        \brief      Classe de description et activation du module Facture
*/

class modFacture extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modFacture($DB)
  {
    $this->db = $DB ;
    $this->numero = 30 ;
    
    $this->family = "financial";
    $this->name = "Factures";
    $this->description = "Gestion des factures";
    $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    $this->const_name = "MAIN_MODULE_FACTURE";
    $this->const_config = MAIN_MODULE_FACTURE;
    $this->special = 0;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array("modSociete","modComptabilite");
    $this->requiredby = array();

    // Config pages
    $this->config_page_url = "facture.php";

    // Constantes
    $this->const = array();

    $this->const[0][0] = "FAC_PDF_INTITULE";
    $this->const[0][1] = "chaine";
    $this->const[0][2] = "Facture Dolibarr";

    $this->const[1][0] = "FAC_PDF_ADRESSE";
    $this->const[1][1] = "texte";
    $this->const[1][2] = "Adresse";

    $this->const[2][0] = "FAC_PDF_TEL";
    $this->const[2][1] = "chaine";
    $this->const[2][2] = "02 97 42 42 42";

    $this->const[3][0] = "FAC_PDF_FAX";
    $this->const[3][1] = "chaine";
    $this->const[3][2] = "02 97 00 00 00";

    $this->const[4][0] = "FAC_PDF_INTITULE2";
    $this->const[4][1] = "chaine";
    $this->const[4][2] = "";

    $this->const[5][0] = "FACTURE_ADDON_PDF";
    $this->const[5][1] = "chaine";
    $this->const[5][2] = "bulot";

    $this->const[6][0] = "FACTURE_ADDON";
    $this->const[6][1] = "chaine";
    $this->const[6][2] = "pluton";

    // Boites
    $this->boxes = array();

    $this->boxes[0][0] = "Factures clients récentes impayées";
    $this->boxes[0][1] = "box_factures_imp.php";

    $this->boxes[1][0] = "Factures fournisseurs récentes impayées";
    $this->boxes[1][1] = "box_factures_fourn_imp.php";

    $this->boxes[2][0] = "Dernières factures clients saisies";
    $this->boxes[2][1] = "box_factures.php";

    $this->boxes[3][0] = "Dernières factures fournisseurs saisies";
    $this->boxes[3][1] = "box_factures_fourn.php";

    // Permissions
    $this->rights = array();
    $this->rights_class = 'facture';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    global $conf;
    
    // Permissions
    $this->remove();

    $this->rights[0][0] = 10;
    $this->rights[0][1] = 'Tous les droits sur les factures';
    $this->rights[0][2] = 'a';
    $this->rights[0][3] = 0;

    $this->rights[1][0] = 11;
    $this->rights[1][1] = 'Consulter les factures';
    $this->rights[1][2] = 'a';
    $this->rights[1][3] = 0;

    $this->rights[2][0] = 12;
    $this->rights[2][1] = 'Créer/modifier les factures';
    $this->rights[2][2] = 'a';
    $this->rights[2][3] = 0;

    $this->rights[3][0] = 14;
    $this->rights[3][1] = 'Valider les factures';
    $this->rights[3][2] = 'a';
    $this->rights[3][3] = 0;

    $this->rights[4][0] = 15;
    $this->rights[4][1] = 'Envoyer les factures aux clients';
    $this->rights[4][2] = 'a';
    $this->rights[4][3] = 0;

    $this->rights[5][0] = 16;
    $this->rights[5][1] = 'Emettre des paiements sur les factures';
    $this->rights[5][2] = 'a';
    $this->rights[5][3] = 0;

    $this->rights[6][0] = 19;
    $this->rights[6][1] = 'Supprimer les factures';
    $this->rights[6][2] = 'a';
    $this->rights[6][3] = 0;

    // Dir
    $this->dirs[0] = $conf->facture->dir_output;
    
    $sql = array();

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
