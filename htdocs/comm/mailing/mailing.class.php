<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \file       htdocs/comm/mailing/mailing.class.php
        \brief      Fichier de la classe de gestion des mailings
        \version    $Revision$
*/


/**     \class      Mailing
	    \brief      Classe permettant la gestion des mailings
*/
class Mailing
{
  var $id;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   */
  function Mailing($DB, $soc_idp="")
    {
      $this->db = $DB ;
      $this->db_table = MAIN_DB_PREFIX."mailing";

      $this->statuts[0] = "En préparation";
      $this->statuts[1] = "Validé";
      $this->statuts[2] = "Approuvé";
      $this->statuts[3] = "Envoyé";
  }
  
  /**
   *    \brief      Création du mailing
   *    \param      user object utilisateur qui crée
   *
   */
  function create($user)
    {
      dolibarr_syslog("Mailing::Create");
      $sql = "INSERT INTO ".$this->db_table;
      $sql .= " (date_creat, fk_user_creat)";
      $sql .= " VALUES (now(), ".$user->id.")";

      if (strlen(trim($this->titre)) == 0)
	{
	  $this->titre = "Sans titre";
	}

      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();
	  
	  return $this->update();
	}
      else
	{
	  dolibarr_syslog("Mailing::Create Erreur -1");
	  return -1;
	}
    }
  /**
   * \brief     Update les infos du mailing
   */
  function update()
    {
      dolibarr_syslog("Mailing::Update");

      $sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
      $sql .= " SET titre = '".$this->titre."'";
      $sql .= " , sujet = '".$this->sujet."'";
      $sql .= " , body = '".$this->body."'";
      $sql .= " WHERE rowid = ".$this->id;

      if ($this->db->query($sql) )
	{
	  return 0;
	}
      else
	{
	  dolibarr_syslog("Mailing::Update Erreur -1");
	  return -1;
	}
    }
  /**
   *    \brief      Recupére l'objet mailing
   *    \param      rowid       id du mailing
   */
  function fetch($rowid)
    {
      $sql = "SELECT m.rowid, m.titre, m.sujet, m.body";
      $sql .= " , m.email_from, m.email_replyto, m.email_errorsto";
      $sql .= " , m.statut, m.nbemail";
      $sql .= ", m.fk_user_creat, m.fk_user_valid, m.fk_user_appro";
      $sql .= ", ".$this->db->pdate("m.date_creat") . " as date_creat";
      $sql .= ", ".$this->db->pdate("m.date_valid") . " as date_valid";
      $sql .= ", ".$this->db->pdate("m.date_appro") . " as date_appro";
      $sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
      $sql .= " WHERE m.rowid = ".$rowid;

      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object();
	      
	      $this->id                 = $obj->rowid;
	      $this->statut             = $obj->statut;
	      $this->nbemail            = $obj->nbemail;
	      $this->titre              = stripslashes($obj->titre);
	      $this->sujet              = stripslashes($obj->sujet);
	      $this->body               = stripslashes($obj->body);

	      $this->email_from         = $obj->email_from;
	      $this->email_replyto      = $obj->email_replyto;
	      $this->email_errorsto     = $obj->email_errorsto;

	      $this->user_creat         = $obj->fk_user_creat;
	      $this->user_valid         = $obj->fk_user_valid;
	      $this->user_appro         = $obj->fk_user_appro;

	      $this->date_creat         = $obj->date_creat;
	      $this->date_valid         = $obj->date_valid;
	      $this->date_appro         = $obj->date_appro;

	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("Mailing::Fetch Erreur -1");
	      return -1;
	    }
	}
      else
	{
	  dolibarr_syslog("Mailing::Fetch Erreur -2");
	  return -2;
	}    
    }


  /**
   * \brief     Valide le mailing
   * \param     user      objet user qui valide
   */
  function valid($user)
    {
      dolibarr_syslog("Mailing::Valid");

      $sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
      $sql .= " SET statut = 1, date_valid = now(), fk_user_valid=".$user->id;

      $sql .= " WHERE rowid = ".$this->id." AND statut = 0 ;";

      if ($this->db->query($sql) )
	{
	  return 0;
	}
      else
	{
	  dolibarr_syslog("Mailing::Valid Erreur -1");
	  return -1;
	}
    }

  /**
   * \brief     Approuve le mailing
   * \param     user      objet user qui approuve
   */
  function approve($user)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
      $sql .= " SET statut = 2, date_appro = now(), fk_user_appro=".$user->id;

      $sql .= " WHERE rowid = ".$this->id." AND statut = 1 ;";

      if ($this->db->query($sql) )
	{
	  return 0;
	}
      else
	{
	  dolibarr_syslog("Mailing::Valid Erreur -1");
	  return -1;
	}
    }


  /**
   * \brief     Supprime le mailing
   * \param     rowid      id du mailing à supprimer
   */
  function delete($rowid)
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing";
    $sql .= " WHERE fk_facture = ".$rowid;
    
  }
}

?>
