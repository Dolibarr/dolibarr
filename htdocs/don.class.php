<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
	    \file       htdocs/don.class.php
        \ingroup    don
		\brief      Fichier de la classe des dons
		\version    $Revision$
*/


/*! \class Don
		\brief Classe permettant la gestion des dons
*/

class Don
{
  var $id;
  var $db;
  var $date;
  var $amount;
  var $prenom;
  var $nom;
  var $societe;
  var $adresse;
  var $cp;
  var $ville;
  var $pays;
  var $email;
  var $public;
  var $projetid;
  var $modepaiement;
  var $modepaiementid;
  var $commentaire;
  var $statut;

  var $projet;
  var $errorstr;

  /*
   * Statut du don
   * 0 : promesse non validée
   * 1 : promesse validée
   * 2 : don validé
   * 3 : don payé
   *
   */

  /*
   *    \brief  Constructeur
   *    \param  DB          handler d'accès base
   *    \param  soc_idp     id société
   */
  function Don($DB, $soc_idp="") 
    {
      $this->db = $DB ;
      $this->modepaiementid = 0;
    }


  /*
   *
   */
  function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }

  /*
   *
   *
   */
  function check($minimum=0) 
    {
      $err = 0;

      if (strlen(trim($this->societe)) == 0)
	{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	    {
	      $error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	      $err++;
	    }
	}

      if (strlen(trim($this->adresse)) == 0)
	{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->cp)) == 0)
	{
	  $error_string[$err] = "Le code postal saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->ville)) == 0)
	{
	  $error_string[$err] = "La ville saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->email)) == 0)
	{
	  $error_string[$err] = "L'email saisi est invalide";
	  $err++;
	}

      $this->amount = trim($this->amount);

      $map = range(0,9);
      for ($i = 0; $i < strlen($this->amount) ; $i++)
	{
	  if (!isset($map[substr($this->amount, $i, 1)] ))
	    {
	      $error_string[$err] = "Le montant du don contient un/des caractère(s) invalide(s)";
	      $err++;
	      $amount_invalid = 1;
	      break;
	    } 	      
	}

      if (! $amount_invalid)
	{
	  if ($this->amount == 0)
	    {
	      $error_string[$err] = "Le montant du don est null";
	      $err++;
	    }
	  else
	    {
	      if ($this->amount < $minimum && $minimum > 0)
		{
		  $error_string[$err] = "Le montant minimum du don est de $minimum";
		  $err++;
		}
	    }
	}
      
      if ($err)
	{
	  $this->errorstr = $error_string;
	  return 0;
	}
      else
	{
	  return 1;
	}

    }

  /*
   *    \brief  Création du don en base
   *    \param  userid      utilisateur qui crée le don
   */
  function create($userid) 
    {
      $this->date = $this->db->idate($this->date);

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."don (datec, amount, fk_paiement,prenom, nom, societe,adresse, cp, ville, pays, public,";
      if ($this->projetid) {
        $sql .= " fk_don_projet,";
      }
      $sql .= " note, fk_user_author, datedon, email)";
      $sql .= " VALUES (now(), $this->amount, $this->modepaiementid,'$this->prenom','$this->nom','$this->societe','$this->adresse', '$this->cp','$this->ville','$this->pays',$this->public, ";
      if ($this->projetid) {
        $sql .= " $this->projetid,";
      }
      $sql .= " '$this->commentaire', $userid, '$this->date','$this->email')";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  return $this->db->last_insert_id();
	}
      else
	{
      dolibarr_print_error($this->db);
	  return 0;
	}  
    }

  /*
   *    \brief  Mise à jour du don
   *    \param  userid      utilisateur qui crée le don
   *
   */
  function update($userid) 
    {
      
      $this->date = $this->db->idate($this->date);

      $sql = "UPDATE ".MAIN_DB_PREFIX."don SET ";
      $sql .= "amount = " . $this->amount;
      $sql .= ",fk_paiement = ".$this->modepaiementid;
      $sql .= ",prenom = '".$this->prenom ."'";
      $sql .= ",nom='".$this->nom."'";
      $sql .= ",societe='".$this->societe."'";
      $sql .= ",adresse='".$this->adresse."'";
      $sql .= ",cp='".$this->cp."'";
      $sql .= ",ville='".$this->ville."'";
      $sql .= ",pays='".$this->pays."'";
      $sql .= ",public=".$this->public;
      if ($this->projetid) {    $sql .= ",fk_don_projet=".$this->projetid; }
      $sql .= ",note='".$this->commentaire."'";
      $sql .= ",datedon='".$this->date."'";
      $sql .= ",email='".$this->email."'";
      $sql .= ",fk_statut=".$this->statut;

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  return 1;
	}
      else
	{
      dolibarr_print_error($this->db);
	  return 0;
	}  
    }

  /*
   *    \brief  Suppression du don de la base
   *    \param  rowid   id du don à supprimer 
   */
  function delete($rowid)
  {
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."don WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

  /*
   *    \brief  Charge l'objet don en mémoire depuis la base de donnée
   *    \param  rowid   id du don à charger
   *
   */
  function fetch($rowid)
  {
    $sql = "SELECT d.rowid, ".$this->db->pdate("d.datedon")." as datedon, d.prenom, d.nom, d.societe, d.amount, p.libelle as projet, d.fk_statut, d.adresse, d.cp, d.ville, d.pays, d.public, d.amount, d.fk_paiement, d.note, cp.libelle, d.email, d.fk_don_projet";
    $sql .= " FROM ".MAIN_DB_PREFIX."don as d, ".MAIN_DB_PREFIX."c_paiement as cp LEFT JOIN ".MAIN_DB_PREFIX."don_projet as p";
    $sql .= " ON p.rowid = d.fk_don_projet WHERE cp.id = d.fk_paiement AND d.rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {

	    $obj = $this->db->fetch_object(0);

	    $this->id             = $obj->rowid;
	    $this->date           = $obj->datedon;
	    $this->prenom         = stripslashes($obj->prenom);
	    $this->nom            = stripslashes($obj->nom);
	    $this->societe        = stripslashes($obj->societe);
	    $this->statut         = $obj->fk_statut;
	    $this->adresse        = stripslashes($obj->adresse);
	    $this->cp             = stripslashes($obj->cp);
	    $this->ville          = stripslashes($obj->ville);
	    $this->email          = stripslashes($obj->email);
	    $this->pays           = stripslashes($obj->pays);
	    $this->projet         = $obj->projet;
	    $this->projetid       = $obj->fk_don_projet;
	    $this->public         = $obj->public;
	    $this->modepaiementid = $obj->fk_paiement;
	    $this->modepaiement   = $obj->libelle;
	    $this->amount         = $obj->amount;
	    $this->commentaire    = stripslashes($obj->note);
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
      }
    
  }

  /*
   *    \brief  Valide une promesse de don
   *    \param  rowid   id du don à modifier
   *    \param  userid  utilisateur qui valide la promesse
   *
   */
  function valid_promesse($rowid, $userid)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

  /*
   *    \brief  Classe le don comme payé, le don a été recu
   *    \param  rowid           id du don à modifier
   *    \param  modepaiementd   mode de paiement
   *
   */
  function set_paye($rowid, $modepaiement='')
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2";

    if ($modepaiement)
      {
	$sql .= ", fk_paiement=$modepaiement";
      }
    $sql .=  " WHERE rowid = $rowid AND fk_statut = 1;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

  /*
   *    \brief  Défini le commentaire
   *    \param  rowid           id du don à modifier
   *    \param  commentaire     commentaire à définir ('' par défaut)
   *
   */
  function set_commentaire($rowid, $commentaire='')
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."don SET note = '$commentaire'";

    $sql .=  " WHERE rowid = $rowid ;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

  /*
   *    \brief  Classe le don comme encaissé
   *    \param  rowid   id du don à modifier
   *
   */
  function set_encaisse($rowid)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 3 WHERE rowid = $rowid AND fk_statut = 2;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
      dolibarr_print_error($this->db);
	  return 0;
      }    
  }

  /*
   *    \brief  Somme des dons encaissés
   */
  function sum_actual()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."don";
    $sql .= " WHERE fk_statut = 3";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }

  /*
   *    \brief  Paiement recu en attente d'encaissement
   *
   */
  function sum_pending()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."don";
    $sql .= " WHERE fk_statut = 2";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }

  /*
   *    \brief  Somme des promesses de dons validées
   *
   */
  function sum_intent()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."don";
    $sql .= " WHERE fk_statut = 1";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }
}
?>
