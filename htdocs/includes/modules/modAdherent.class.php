<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

include_once "modDolibarrModules.class.php";

class modAdherent extends modDolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modAdherent($DB)
  {
    $this->db = $DB ;
    $this->depends = array();

    $this->const = array();
    $this->boxes = array();

    $this->const[0][0] = "ADHERENT_MAIL_RESIL";
    $this->const[0][1] = "texte";
    $this->const[0][2] = "Mail de résiliation";

    $this->const[1][0] = "ADHERENT_MAIL_VALID";
    $this->const[1][1] = "texte";
    $this->const[1][2] = "Mail de validation";

    $this->const[2][0] = "ADHERENT_MAIL_EDIT";
    $this->const[2][1] = "texte";
    $this->const[2][2] = "Mail d'edition";

    $this->const[3] = array("ADHERENT_MAIL_RESIL","texte","Mail de résiliation");

    $this->const[4] = array("ADHERENT_MAIL_NEW","texte","Mail de nouvel inscription");

    $this->const[5] = array("ADHERENT_MAIL_VALID_SUBJECT","chaine","sujet du mail de validation");

    $this->const[6] = array("ADHERENT_MAIL_RESIL_SUBJECT","chaine","sujet du mail de resiliation");

    $this->const[7] = array("ADHERENT_MAIL_NEW_SUBJECT","chaine","Sujet du mail de nouvelle adhesion");

    $this->const[8] = array("ADHERENT_MAIL_EDIT_SUBJECT","chaine","Sujet du mail d'edition");

    $this->const[9] = array("ADHERENT_GLASNOST_SERVEUR","chaine","serveur glasnost");

    $this->const[10] = array("ADHERENT_MAILMAN_UNSUB_URL","chaine","Url de desinscription aux listes mailman");

    $this->const[11] = array("ADHERENT_MAILMAN_URL","chaine","url pour les inscriptions mailman");

    $this->const[12] = array("ADHERENT_MAILMAN_LISTS","chaine","Listes auxquelles les nouveaux adhérents sont inscris");

    $this->const[13] = array("ADHERENT_GLASNOST_USER","chaine","Administrateur glasnost");

    $this->const[14] = array("ADHERENT_GLASNOST_PASS","chaine","password de l'administrateur");

    $this->const[15] = array("ADHERENT_USE_GLASNOST_AUTO","yesno","inscription automatique a glasnost ?");

    $this->const[16] = array("ADHERENT_USE_SPIP_AUTO","yesno","Utilisation de SPIP automatiquement");

    $this->const[17] = array("ADHERENT_SPIP_USER","chaine","Utilisateur de connection a la base spip");

    $this->const[18] = array("ADHERENT_SPIP_PASS","chaine","Mot de passe de connection a la base spip");

    $this->const[19] = array("ADHERENT_SPIP_SERVEUR","chaine","serveur spip");

    $this->const[20] = array("ADHERENT_SPIP_DB","chaine","db spip");

    $this->const[21] = array("ADHERENT_MAIL_FROM","chaine","From des mails");

    $this->const[22] = array("ADHERENT_MAIL_COTIS","texte","Mail de validation de cotisation");

    $this->const[23] = array("ADHERENT_MAIL_COTIS_SUBJECT","chaine","sujet du mail de validation de cotisation");

    $this->const[24] = array("ADHERENT_TEXT_NEW_ADH","texte","Texte d'entete du formaulaire d'adhesion en ligne");

    $this->const[25] = array("ADHERENT_CARD_HEADER_TEXT","chaine","Texte imprime sur le haut de la carte adherent");

    $this->const[26] = array("ADHERENT_CARD_FOOTER_TEXT","chaine","Texte imprime sur le bas de la carte adherent");

    $this->const[27] = array("ADHERENT_CARD_TEXT","texte","Texte imprime sur la carte adherent");

    $this->const[28] = array("ADHERENT_MAILMAN_ADMINPW","chaine","Mot de passe Admin des liste mailman");

    $this->const[29] = array("ADHERENT_MAILMAN_SERVER","chaine","Serveur hebergeant les interfaces d'Admin des listes mailman");

    $this->const[30] = array("ADHERENT_MAILMAN_LISTS_COTISANT","chaine","Liste(s) auxquelles les nouveaux cotisants sont inscris automatiquement");

    $this->const[31] = array("ADHERENT_BANK_USE_AUTO","yesno","Insertion automatique des cotisation dans le compte banquaire");

    $this->const[32] = array("ADHERENT_BANK_ACCOUNT","chaine","ID du Compte banquaire utilise");

    $this->const[33] = array("ADHERENT_BANK_CATEGORIE","chaine","ID de la categorie banquaire des cotisations");

    $this->const[34] = array("ADHERENT_ETIQUETTE_TYPE","chaine","Type d etiquette (pour impression de planche d etiquette)");
  }
  /*
   *
   *
   *
   */

  Function init()
  {
    /*
     * Permissions
     */
    $sql = array(
		 "insert into llx_rights_def values (10,'Tous les droits sur les adherents','adherent','a',0);"
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array(
		 "DELETE FROM llx_rights_def WHERE module = 'adherent';"
		 );

    return $this->_remove($sql);
  }
}
?>
