<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   facture     Module facture
        \brief      Module pour gérer les factures clients et/ou fournisseurs
*/

/*!
        \file       htdocs/includes/modules/modFacture.class.php
        \brief      Fichier de description et activation du module Facture
*/

include_once "DolibarrModules.class.php";

class modFacture extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modFacture($DB)
  {
    $this->db = $DB ;
    $this->numero = 30 ;
    
    $this->family = "financial";
    $this->name = "Factures";
    $this->description = "Gestion des factures";
    $this->const_name = "MAIN_MODULE_FACTURE";
    $this->const_config = MAIN_MODULE_FACTURE;

    // Dépendances
    $this->depends = array("modSociete","modComptabilite");
    $this->requiredby = array();

    $this->config_page_url = "facture.php";

    $this->const = array();
    $this->boxes = array();

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

    $this->const[4][0] = "FAC_PDF_SIREN";
    $this->const[4][1] = "chaine";
    $this->const[4][2] = "123 456 789";

    $this->const[5][0] = "FAC_PDF_SIRET";
    $this->const[5][1] = "chaine";
    $this->const[5][2] = "123 456 789 012";

    $this->const[6][0] = "FAC_PDF_INTITULE2";
    $this->const[6][1] = "chaine";

    $this->const[7][0] = "FACTURE_ADDON_PDF";
    $this->const[7][1] = "chaine";
    $this->const[7][2] = "bulot";

    $this->const[8][0] = "FACTURE_ADDON";
    $this->const[8][1] = "chaine";
    $this->const[8][2] = "pluton";

    // Boxes
    $this->boxes[0][0] = "Factures clients récentes impayées";
    $this->boxes[0][1] = "box_factures_imp.php";

    $this->boxes[1][0] = "Factures fournisseurs récentes impayées";
    $this->boxes[1][1] = "box_factures_fourn_imp.php";

    $this->boxes[2][0] = "Dernières factures clients saisies";
    $this->boxes[2][1] = "box_factures.php";

    $this->boxes[3][0] = "Dernières factures fournisseurs saisies";
    $this->boxes[3][1] = "box_factures_fourn.php";

  }
  /*
   *
   *
   *
   */

  function init()
  {
    /*
     * Permissions
     */
    $sql = array(
		 "insert into ".MAIN_DB_PREFIX."rights_def values (10,'Tous les droits sur les factures','facture','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (11,'Lire les factures','facture','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (12,'Créer modifier les factures','facture','w',0);",
		 //"insert into ".MAIN_DB_PREFIX."rights_def values (13,'Modifier les factures d\'autrui','facture','m',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (14,'Valider les factures','facture','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (15,'Envoyer les factures aux clients','facture','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (16,'Emettre des paiements sur les factures','facture','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (19,'Supprimer les factures','facture','d',0);"
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  function remove()
  {
    $sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'facture';"
		 );

    return $this->_remove($sql);
  }
}
?>
