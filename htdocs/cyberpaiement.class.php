<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/* Classe de gestion du système de paiement de la banque Populaire
 * de Lorraine et Champagne
 * http://www.cyberpaiement.tm.fr/
 *
 */

class Cyberpaiement
{

  function Cyberpaiement($conf) 
    /*
     *   Initialisation des valeurs par défaut
     */
  {

    /* Numéro abonné Internet : 6 chiffres */
    
    $this->champ000 = $conf->bplc->numabonne;
    
    /* Code activité commercant : 4 chiffres */
      
    $this->champ001 = $conf->bplc->code_activite;

    /* Numéro de contrat : 10 chiffres */
      
    $this->champ002 = $conf->bplc->num_contrat;
      
    /* Type de paiement */
        
    $this->champ003 = $conf->bplc->typepaiement;
      
    /* Nom du serveur commercant, champ purement informatif */
      
    $this->champ004 = trim($conf->bplc->nom_serveur);
    
    /* Url du CGI de retour */
    
    $this->champ005 = $conf->bplc->cgi_retour;
    
    /* Nom du commercant */
    
    $this->champ006 = $conf->bplc->nom_commercant;
      
    /* url retour */
    
    $this->champ007 = $conf->bplc->url_retour;
      
    /* Email confirmation commercant*/
    
    $this->champ008 = trim($conf->bplc->email_commercant);
    
    /* Devise : EUR*/
      
    $this->champ202 = $conf->bplc->devise;
    
    /* Adhérent : 01 */
    
    $this->champ900 = $conf->bplc->adherent;
    

    /* *********************************************** */
    /* Initialisation à vide des valeurs qui ne seront */
    /* pas transmises                                  */
    /* *********************************************** */

    $this->champ100 = ".";
    $this->champ101 = ".";
    $this->champ102 = ".";    
    $this->champ103 = ".";
    $this->champ104 = ".";
    $this->champ106 = ".";
    $this->champ107 = ".";
    $this->champ108 = ".";
    $this->champ109 = ".";
    $this->champ110 = ".";


  }

  /* ********************** */
  /*                        */
  /* Client                 */
  /*                        */
  /* ********************** */

  function set_client($nom,$prenom,$email,$societe='')
  {          
    /* Nom */
    
    $this->champ100 = $nom;
    
    /* Prenom */
    
    $this->champ101 = $prenom;
    
    /* Société */
    if (strlen(trim($societe)))
      {
	$this->champ102 = $societe;
      }
    /* Téléphone */
    if (strlen(trim($telephone)))
      {
	$this->champ103 = $telephone;
      }
    
    /* Adresse email */
    
    $this->champ104 = trim($email);
    
    /* Fax */
    if (strlen(trim($fax)))
      {
	$this->champ106 = $fax;
      }
    
    /* Adresse numéro et rue */
    if (strlen(trim($adresse)))
      {
	$this->champ107 = $adresse;
      }
    
    /* Ville */
    if (strlen(trim($ville)))
      {
	$this->champ108 = $ville;
      }
    
    /* Code Postal */
    if (strlen(trim($cp)))
      {
	$this->champ109 = trim($cp);
      }
    
    /* Code Pays : purement Informatif */
    if (strlen(trim($pays)))
      {
	$this->champ110 = trim($pays);
      }
  }
  /* ********************** */
  /*                        */
  /* Commande               */
  /*                        */
  /* ********************** */
  
  function set_commande($ref, $montant)
  {          
    /* Référence */
    
    $this->champ200 = $ref;
    
    /* Montant */
    
    $this->champ201 = $montant;         
  }
  /*
   *
   *
   *
   */
  function print_hidden()
  {
      print '<input type="hidden" name="CHAMP000" value="'.$this->champ000.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP001" value="'.$this->champ001.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP002" value="'.$this->champ002.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP003" value="'.$this->champ003.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP004" value="'.$this->champ004.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP005" value="'.$this->champ005.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP006" value="'.$this->champ006.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP007" value="'.$this->champ007.'ref='.$this->champ200.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP008" value="'.$this->champ008.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP100" value="'.$this->champ100.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP101" value="'.$this->champ101.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP102" value="'.$this->champ102.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP103" value="'.$this->champ103.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP104" value="'.$this->champ104.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP106" value="'.$this->champ106.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP107" value="'.$this->champ107.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP108" value="'.$this->champ108.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP109" value="'.$this->champ109.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP110" value="'.$this->champ110.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP200" value="'.$this->champ200.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP201" value="'.$this->champ201.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP202" value="'.$this->champ202.'">';
      print "\n";
      print '<input type="hidden" name="CHAMP900" value="'.$this->champ900.'">';
      print "\n";
  }
  /*
   *
   *
   *
   */

}
?>
