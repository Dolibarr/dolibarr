<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Xavier Dutoit <doli@sydesy.com> 
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
 * Ce fichier a vocation de disparaitre, la configuration se faisant 
 * dorénavant dans la base de donnée
 *
 */

class Conf
{
  var $readonly;
  var $dbi;

  Function Conf()
    {
      $this->db = new DbConf();
      
      $this->compta    = new ComptaConf();
      $this->propal    = new PropalConf();
      $this->facture   = new FactureConf();
      $this->webcal    = new WebcalConf();
      $this->produit   = new ProduitConf();
      $this->service   = new ServiceConf();
      $this->adherent  = new AdherentConf();
      $this->domaine   = new DomaineConf();
      $this->don       = new DonConf();
      
      $this->readonly   = 0;
      $this->voyage     = 0;
      $this->password_encrypted = 0;
    }  
}

class ComptaConf
{
  Function ComptaConf() 
    {
      $this->tva = 1;
    }
}

class PropalConf
{
  Function PropalConf()
    {

    }
}
/*
 * Base de données
 * Ne pas modifier ces valeurs
 */

class DbConf {
  Function DbConf() {
    $this->type = "mysql";
    $this->host = "";
    $this->user = "";
    $this->pass = "";
    $this->name = "";
/*
 * If you want to activate virtualhosting you need tou use these lines and add 
 * this to your pache virtualhost file
 SetEnv LLX_DBTYPE mysql
 SetEnv LLX_DBHOST localhost
 SetEnv LLX_DBUSER user
 SetEnv LLX_DBPASS pass
 SetEnv LLX_DBNAME dolibarr
*/

/*
 * Ce bloc de code, casse l'authentification par Pear::Auth !!
 * La conf est positionnée dans conf.php les virtualhost ne sont
 * pas encore valide
 *
    $this->type = getenv("LLX_DBTYPE");
    $this->host = getenv("LLX_DBHOST");
    $this->user = getenv("LLX_DBUSER");
    $this->pass = getenv("LLX_DBPASS");
    $this->name = getenv("LLX_DBNAME");
*/

  }

  /** return the dsn according to the pear syntax
  */
  function getdsn ()
  {
  	return ($this->type.'://'.$this->user.':'.$this->pass.'@'.$this->host.'/'.$this->name);
  }

}
/*
 * Calendrier
 *
 */
class WebcalConf
{
  Function WebcalConf()
    {
      $this->enabled = 1;

      $this->url = PHPWEBCALENDAR_URL;

      $this->db = new DbConf();
      $this->db->host = PHPWEBCALENDAR_HOST;
      $this->db->user = PHPWEBCALENDAR_USER;
      $this->db->pass = PHPWEBCALENDAR_PASS;
      $this->db->name = PHPWEBCALENDAR_DBNAME;    
    }
}

/*
 * Factures
 *
 */
class FactureConf
{
  Function FactureConf()
    {
      $this->enabled = 1;
    }
}

/*
 * Dons
 *
 */
class DonConf
{
  Function DonConf()
    {
      $this->enabled = 1;
      
      /* Paiement en ligne */

      $this->onlinepayment = 0;

      /* Don minimum, 0 pas de limite */ 
      $this->minimum = 0;

      /* Email des moderateurs */

      $this->email_moderator = "root@localhost";
    }
}
/*
 * Produits
 *
 */
class ProduitConf
{
  Function ProduitConf()
    {
      $this->enabled = 0;
    }
}
/*
 * Service
 *
 */
class ServiceConf
{
  Function ServiceConf()
    {
      $this->enabled = 0;
    }
}
/*
 * Adherents
 *
 */
class AdherentConf {
  Function AdherentConf() {
    $this->enabled = 0;
    $this->email_new = "Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\n\n%INFO%\n\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l'adresse suivante :\n%SERVEUR%public/adherents/\n\n";
    $this->email_new_subject = 'Vos coordonnees sur %SERVEUR%';
    $this->email_edit = "Voici le rappel des coordonnees que vous avez modifiees (toute information erronee entrainera la non validation de votre inscription) :\n\n%INFO%\n\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l'adresse suivante :\n%SERVEUR%public/adherents/\n\n";
    $this->email_edit_subject = 'Vos coordonnees sur %SERVEUR%';
    $this->email_valid = "Votre adhesion vient d'etre validee. Voici le rappel de vos coordonnees (toute information erronee entrainera la non validation de votre inscription) :\n\n%INFO%\n\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l'adresse suivante :\n%SERVEUR%public/adherents/\n\n";
    $this->email_valid_subject = 'Vos coordonnees sur %SERVEUR%';
    $this->email_resil = "Votre adhesion sur %SERVEUR% vient d'etre resilie.\nNous esperons vous revoir bientot\n";
    $this->email_resil_subject = 'Vos coordonnees sur %SERVEUR%';

  }
}
/*
 * Domaines
 *
 */
class DomaineConf
{
  Function DomaineConf()
    {
      $this->enabled = 0;
    }
}

?>
