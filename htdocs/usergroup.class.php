<?php
/* Copyright (c) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**	  
	 \file       htdocs/usergroup.class.php
	 \brief      Fichier de la classe des groupes d'utilisateur
	 \author     Rodolphe Qiedeville
	 \version    $Revision$
*/

/**    
       \class      UserGroup
       \brief      Classe permettant la gestion des groupes d'utilisateur
*/

class UserGroup
{
    var $db;
    
    var $id;
    var $nom;
    var $note;
    var $datec;
    var $datem;


  /**
   *    \brief Constructeur de la classe
   *    \param  $DB         handler accès base de données
   *    \param  $id         id du groupe (0 par défaut)
   */
	 
    function UserGroup($DB, $id=0)
    {
        $this->db = $DB;
        $this->id = $id;
        
        return 0;
    }


  /**
   *    \brief      Charge un objet user avec toutes ces caractéristiques
   *    \param      id      id du groupe à charger
   */
	 
    function fetch($id)
    {
        $this->id = $id;
    
        $sql  = "SELECT g.rowid, g.nom, g.note, g.datec, tms as datem";
        $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
        $sql .= " WHERE g.rowid = ".$this->id;
    
        $result = $this->db->query($sql);
    
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
    
                $this->id = $obj->rowid;
                $this->nom  = $obj->nom;
                $this->note = $obj->note;
                $this->datec = $obj->datec;
                $this->datem = $obj->datem;
            }
            $this->db->free($result);
    
        }
        else
        {
            dolibarr_syslog("UserGroup::Fetch Erreur");
        }
    
    }

  
  /**
   *    \brief      Ajoute un droit a l'utilisateur
   *    \param      rid         id du droit à ajouter
   *    \param      allmodule   Ajouter tous les droits du module allmodule
   *    \param      allperms    Ajouter tous les droits du module allmodule, perms allperms
   *    \return     int         > 0 si ok, < 0 si erreur
   */
	 
    function addrights($rid,$allmodule='',$allperms='')
    {
        $err=0;
        $whereforadd='';
        
        $this->db->begin();

        if ($rid) 
        {
            // Si on a demandé ajout d'un droit en particulier, on récupère
            // les caractéristiques (module, perms et subperms) de ce droit.
            $sql = "SELECT module, perms, subperms";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE ";
            $sql.=" id = '".$rid."'";
       
            $result=$this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
                $module=$obj->module;
                $perms=$obj->perms;
                $subperms=$obj->subperms;
            }
            else {
                $err++;
                dolibarr_print_error($this->db);
            }

            // Where pour la liste des droits à ajouter
            $whereforadd="id=".$rid;
            // Ajout des droits induits
            if ($subperms) $whereforadd.=" OR (module='$module' AND perms='$perms' AND subperms='lire')";
            if ($perms)    $whereforadd.=" OR (module='$module' AND perms='lire' AND subperms IS NULL)";

            // Pour compatibilité, si lowid = 0, on est en mode ajout de tout
            // \todo A virer quand sera géré par l'appelant
            if (substr($rid,-1,1) == 0) $whereforadd="module='$module'";
        }
        else {
            // Where pour la liste des droits à ajouter
            if ($allmodule) $whereforadd="module='$allmodule'";
            if ($allperms)  $whereforadd=" AND perms='$allperms'";
        }

        // Ajout des droits de la liste whereforadd
        if ($whereforadd)
        {
            //print "$module-$perms-$subperms";
            $sql = "SELECT id";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE $whereforadd";
            
            $result=$this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    $nid = $obj->id;
       
                    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=$nid";
                    if (! $this->db->query($sql)) $err++;
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_rights (fk_usergroup, fk_id) VALUES ($this->id, $nid)";
                    if (! $this->db->query($sql)) $err++;
    
                    $i++;
                }
            }
            else 
            {
                $err++;
                dolibarr_print_error($this->db);
            }
        }
    
        if ($err) {
            $this->db->rollback();
            return -$err;
        }
        else {
            $this->db->commit();
            return 1;
        }
        
    }


  /**
   *    \brief      Retire un droit a l'utilisateur
   *    \param      rid         id du droit à retirer
   *    \param      allmodule   Retirer tous les droits du module allmodule
   *    \param      allperms    Retirer tous les droits du module allmodule, perms allperms
   *    \return     int         > 0 si ok, < 0 si erreur
   */
	 
    function delrights($rid,$allmodule='',$allperms='')
    {
        $err=0;
        $wherefordel='';
        
        $this->db->begin();

        if ($rid) 
        {
            // Si on a demandé supression d'un droit en particulier, on récupère
            // les caractéristiques module, perms et subperms de ce droit.
            $sql = "SELECT module, perms, subperms";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE ";
            $sql.=" id = '".$rid."'";
       
            $result=$this->db->query($sql);
            if ($result) {
                $obj = $this->db->fetch_object($result);
                $module=$obj->module;
                $perms=$obj->perms;
                $subperms=$obj->subperms;
            }
            else {
                $err++;
                dolibarr_print_error($this->db);
            }

            // Where pour la liste des droits à supprimer
            $wherefordel="id=".$rid;
            // Suppression des droits induits
            if ($subperms=='lire') $wherefordel.=" OR (module='$module' AND perms='$perms' AND subperms IS NOT NULL)";
            if ($perms=='lire')    $wherefordel.=" OR (module='$module')";

            // Pour compatibilité, si lowid = 0, on est en mode suppression de tout
            // \todo A virer quand sera géré par l'appelant
            if (substr($rid,-1,1) == 0) $wherefordel="module='$module'";
        }
        else {
            // Where pour la liste des droits à supprimer
            if ($allmodule) $wherefordel="module='$allmodule'";
            if ($allperms)  $wherefordel=" AND perms='$allperms'";
        }

        // Suppression des droits de la liste wherefordel
        if ($wherefordel)
        {
            //print "$module-$perms-$subperms";
            $sql = "SELECT id";
            $sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
            $sql.= " WHERE $wherefordel";

            $result=$this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);
                    $nid = $obj->id;
       
                    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=$nid";
                    if (! $this->db->query($sql)) $err++;
    
                    $i++;
                }
            }
            else 
            {
                $err++;
                dolibarr_print_error($this->db);
            }
        }
    
        if ($err) {
            $this->db->rollback();
            return -$err;
        }
        else {
            $this->db->commit();
            return 1;
        }

    }
    

  /**
   *    \brief      Charge dans l'objet group, la liste des permissions auquels le groupe a droit
   *    \param      module    nom du module dont il faut récupérer les droits ('' par defaut signifie tous les droits)
   */
	 
  function getrights($module='')
    {
      if ($this->all_permissions_are_loaded)
      {
        // Si les permissions ont déja été chargées, on quitte
        return;
      }

      /*
       * Récupération des droits
       */
      $sql = "SELECT r.module, r.perms, r.subperms ";
      $sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as u, ".MAIN_DB_PREFIX."rights_def as r";
      $sql .= " WHERE r.id = u.fk_id AND u.fk_usergroup= $this->id AND r.perms IS NOT NULL";
      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $row = $this->db->fetch_row();

	      if (strlen($row[1]) > 0)
		{

		  if (strlen($row[2]) > 0)
		    {
		      $this->rights->$row[0]->$row[1]->$row[2] = 1;
		    }
		  else
		    {
		      $this->rights->$row[0]->$row[1] = 1;
		    }

		}
	      $i++;
	    }
	}

        if ($module == '')
        {
          // Si module etait non defini, alors on a tout chargé, on peut donc considérer
          // que les droits sont en cache (car tous chargés) pour cet instance de user
          $this->all_permissions_are_loaded=1;
        }
        
    }

  /**
   *        \brief      Efface un groupe de la base
   *        \return     < 0 si erreur, > 0 si ok
   */
	 
  function delete()
  {
    $this->db->begin();
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
    $sql .= " WHERE fk_usergroup = ".$this->id;
    $this->db->query($sql);

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
    $sql .= " WHERE fk_usergroup = ".$this->id;
    $this->db->query($sql);

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup";
    $sql .= " WHERE rowid = ".$this->id;
    $result=$this->db->query($sql);
    if ($result) 
    {
        $this->db->commit();
	    return 1;
    }
    else
    {
        $this->db->rollback();
        dolibarr_print_error($this->db);
	    return -1;
    }
  }

  /**
   *        \brief      Crée un groupe en base
   *        \return     si erreur <0, si ok renvoie id groupe créé
   */
	 
  function create()
  {

    $sql = "INSERT into ".MAIN_DB_PREFIX."usergroup (datec,nom)";
    $sql .= " VALUES(now(),'$this->nom')";

    $result=$this->db->query($sql);
    if ($result)
    {
        $table =  "".MAIN_DB_PREFIX."usergroup";
        $this->id = $this->db->last_insert_id($table);

        if ($this->update() < 0) return -2;

        return $this->id;
    }
    else
    {
        dolibarr_syslog("UserGroup::Create");
        return -1;
    }
  }


  /**
   *    \brief      Mise à jour en base d'un utilisateur
   *    \return     <0 si echec, >=0 si ok
   */
  function update()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."usergroup SET ";
        $sql .= " nom = '$this->nom',";
        $sql .= " note = '$this->note'";
        $sql .= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->affected_rows())
            {
                return 1;
            }
            return 0;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -2;
        }

   }
   
}

?>
