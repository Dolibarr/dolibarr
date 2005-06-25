<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id$
 * $Source$
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
 */

/**
        \file       htdocs/actioncomm.class.php
        \ingroup    commercial
        \brief      Fichier de la classe des actions commerciales
        \version    $Revision$
*/


/**     \class      ActionComm
	    \brief      Classe permettant la gestion des actions commerciales
*/

class ActionComm
{
  var $id;
  var $db;

  var $label;
  var $date;
  var $type_code;
  var $type;
  var $priority;
  var $user;
  var $author;
  var $societe;
  var $contact;
  var $note;
  var $percent;
  var $error;

  /**
   *    \brief      Constructeur
   *    \param      db      Handler d'accès base de donnée
   */
  function ActionComm($db) 
    {
      $this->db = $db;
      $this->societe = new Societe($db);
      $this->author = new User($db);
      if (class_exists("Contact"))
      {
	    $this->contact = new Contact($db);
      }
    }

  /**
   *    \brief      Ajout d'une action en base (et eventuellement dans webcalendar)
   *    \param      author      auteur de la creation de l'action
   *    \param      webcal      ressource webcalendar: 0=on oublie webcal, 1=on ajoute une entrée générique dans webcal, objet=ajout de l'objet dans webcal
   *    \return     int         id de l'action créée, < 0 si erreur
   */
    function add($author, $webcal=0)
    {
        global $conf;
    
        if (! $this->percent)  $this->percent = 0;
        if (! $this->priority) $this->priority = 0;
    
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
        $sql.= "(datea,fk_action,fk_soc,note,fk_contact,fk_user_author,fk_user_action,label,percent,priority,";
        $sql.= "fk_facture,propalrowid)";
        $sql.= " VALUES (now(), '".$this->type_code."', '".$this->societe->id."' ,'".addslashes($this->note)."',";
        $sql.= ($this->contact->id?$this->contact->id:"null").",";
        $sql.= "'$author->id', '".$this->user->id ."', '".$this->label."',100,'".$this->priority."',";
        $sql.= ($this->facid?$this->facid:"null").",";
        $sql.= ($this->propalrowid?$this->propalrowid:"null");
        $sql.= ");";
    
        if ($this->db->query($sql) )
        {
            $idaction = $this->db->last_insert_id(MAIN_DB_PREFIX."actioncomm");
    
            if ($conf->webcal->enabled) {
                if (is_object($webcal))
                {
                    // Ajoute entrée dans webcal
                    $result=$webcal->add($author,$webcal->date,$webcal->texte,$webcal->desc);
                    if ($result < 0) {
                        $this->error="Echec insertion dans webcal: ".$webcal->error;
                    }
                }
                else if ($webcal == 1)
                {
                    // \todo On ajoute une entrée générique, pour l'instant pas utilis
    
                }
            }
    
            return $idaction;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -1;
        }
    
    }

  /**
   *    \brief      Charge l'objet action depuis la base
   *    \param      id      id de l'action a récupérer
   */
  function fetch($id)
    {      
        global $langs;
        
      $sql = "SELECT ".$this->db->pdate("a.datea")." as da, a.note, a.label, c.code, c.libelle, fk_soc, fk_user_author, fk_contact, fk_facture, a.percent";
      $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c";
      $sql.= " WHERE a.id=$id AND a.fk_action=c.id;";

      $resql=$this->db->query($sql);
      if ($resql)
	{
	  if ($this->db->num_rows($resql))
	    {
	      $obj = $this->db->fetch_object($resql);
	      
	      $this->id = $id;
	      $this->type_code = $obj->code;
          $transcode=$langs->trans("Action".$obj->code);
          $type_libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);
	      $this->type = $type_libelle;
	      $this->libelle = $obj->label;
	      $this->date = $obj->da;
	      $this->note =$obj->note;
	      $this->percent =$obj->percent;
	      $this->societe->id = $obj->fk_soc;
	      $this->author->id = $obj->fk_user_author;
	      $this->contact->id = $obj->fk_contact;
	      $this->fk_facture = $obj->fk_facture;
	      if ($this->fk_facture)
		{
		  $this->objet_url = '<a href="'. DOL_URL_ROOT . '/compta/facture.php?facid='.$this->fk_facture.'">'.$langs->trans("Bill").'</a>';
		}
	      
	    }
      $this->db->free($resql);
	}
      else
	{
	  dolibarr_print_error($this->db);
	}    
    }

  /**
   *    \brief      Supprime l'action de la base
   *    \param      id      id de l'action a effacer
   *    \return     int     1 en cas de succès
   */
  function delete($id)
    {      
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm WHERE id=$id;";
        
        if ($this->db->query($sql) )
        {
            return 1;
        }
    }

  /**
   *    \brief      Met a jour l'action en base
   *    \return     int     1 en cas de succès
   */
  function update()
    {
      if ($this->percent > 100)
	{
	  $this->percent = 100;
	}
      
      $sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
      $sql .= " SET percent=$this->percent";

      if ($this->percent == 100)
	{
	  $sql .= ", datea = now()";
	}

      $sql .= ", fk_contact =". $this->contact->id;

      $sql .= " WHERE id=$this->id;";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
    }
    
}    
?>
