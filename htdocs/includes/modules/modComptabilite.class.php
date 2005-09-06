<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 *
 */

/**     \defgroup   comptabilite     Module comptabilite
        \brief      Module pour inclure des fonctions de comptabilité (gestion de comptes comptables et rapports)
*/

/**
        \file       htdocs/includes/modules/modComptabilite.class.php
        \ingroup    comptabilite
        \brief      Fichier de description et activation du module Comptabilite
*/

include_once "DolibarrModules.class.php";

/** \class modComptabilite
        \brief      Classe de description et activation du module Comptabilite
*/

class modComptabilite extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modComptabilite($DB)
  {
    global $conf;

    $this->db = $DB ;
    $this->numero = 10 ;
    
    $this->family = "financial";
    $this->name = "Comptabilite";
    $this->description = "Gestion sommaire de comptabilité";

    $this->revision = explode(" ","$Revision$");
    $this->version = $this->revision[1];

    $this->const_name = "MAIN_MODULE_COMPTABILITE";
    $this->const_config = MAIN_MODULE_COMPTABILITE;

    // Config pages
    $this->config_page_url = "compta.php";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modFacture");
    $this->conflictwith = array("modComptabiliteExpert");

    // Constantes
    $this->const = array();

    // Répertoires
    $this->dirs = array();
    $this->dirs[0] = $conf->compta->dir_output;
    $this->dirs[1] = $conf->compta->dir_output."/rapport";
    $this->dirs[2] = $conf->compta->dir_output."/export";
    $this->dirs[3] = $conf->compta->dir_images;

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'compta';

    $this->rights[1][0] = 91;
    $this->rights[1][1] = 'Lire les charges';
    $this->rights[1][2] = 'r';
    $this->rights[1][3] = 1;
    $this->rights[1][4] = 'charges';
    $this->rights[1][5] = 'lire';

    $this->rights[2][0] = 92;
    $this->rights[2][1] = 'Créer modifier les charges';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'charges';
    $this->rights[2][5] = 'creer';

    $this->rights[3][0] = 93;
    $this->rights[3][1] = 'Supprimer les charges';
    $this->rights[3][2] = 'd';
    $this->rights[3][3] = 0;
    $this->rights[3][4] = 'charges';
    $this->rights[3][5] = 'supprimer';

    $this->rights[4][0] = 95;
    $this->rights[4][1] = 'Lire CA, bilans, résultats';
    $this->rights[4][2] = 'r';
    $this->rights[4][3] = 1;
    $this->rights[4][4] = 'resultat';
    $this->rights[4][5] = 'lire';

    $this->rights[5][0] = 96;
    $this->rights[5][1] = 'Paramétrer la ventilation';
    $this->rights[5][2] = 'r';
    $this->rights[5][3] = 0;
    $this->rights[5][4] = 'ventilation';
    $this->rights[5][5] = 'parametrer';

    $this->rights[6][0] = 97;
    $this->rights[6][1] = 'Ventiler les lignes de facture';
    $this->rights[6][2] = 'r';
    $this->rights[6][3] = 0;
    $this->rights[6][4] = 'ventilation';
    $this->rights[6][5] = 'creer';

    $this->rights[7][0] = 98;
    $this->rights[7][1] = "Accès à l'espace compta/tréso";
    $this->rights[7][2] = 'r';
    $this->rights[7][3] = 0;
    $this->rights[7][4] = 'general';
    $this->rights[7][5] = 'lire';

  }


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    global $conf;
    
    // Nettoyage avant activation
    $this->remove();

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
