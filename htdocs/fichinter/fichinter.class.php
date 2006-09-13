<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/fichinter/fichinter.class.php
		\ingroup    fucheinter
		\brief      Fichier de la classe des gestion des fiches interventions
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


/**	    \class      Ficheinter
		\brief      Classe des gestion des fiches interventions
*/
class Fichinter extends CommonObject
{
    var $db;
	var $element='ficheinter';

    var $id;
    
	var $socid;		// Id client
	var $client;		// Objet societe client (à charger par fetch_client)

    var $author;
    var $ref;
    var $date;
    var $duree;
    var $note;
    var $projet_id;


    /**
     *    \brief      Constructeur de la classe
     *    \param      DB            Handler accès base de données
     *    \param      socid			Id societe
     */
    function Fichinter($DB, $socid="")
    {
        global $langs;

        $this->db = $DB ;
        $this->socid = $socid;
        $this->products = array();
        $this->projet_id = 0;

        // Statut 0=brouillon, 1=validé
        $this->statuts[0]=$langs->trans("Draft");
        $this->statuts[1]=$langs->trans("Validated");
        $this->statuts_short[0]=$langs->trans("Draft");
        $this->statuts_short[1]=$langs->trans("Validated");
    }


    /*
     *    \brief      Crée une fiche intervention en base
     *
     */
    function create()
    {
        if (! is_numeric($this->duree)) { $this->duree = 0; }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter (fk_soc, datei, datec, ref, fk_user_author, note, duree";
        if ($this->projet_id) {
            $sql .=  ",fk_projet";
        }
        $sql .= ") ";
        $sql .= " VALUES ($this->socid, $this->date, now(), '$this->ref', $this->author, '".addslashes($this->note)."', $this->duree";
        if ($this->projet_id) {
            $sql .= ", $this->projet_id";
        }
        $sql .= ")";
        $sqlok = 0;

        $result=$this->db->query($sql);
        if ($result)
        {
            return $this->db->last_insert_id(MAIN_DB_PREFIX."fichinter");
        }
        else
        {
            return -1;
        }

    }

    /*
     *	\brief		Met a jour une intervention
     *	\return		int		<0 si ko, >0 si ok
     */
    function update($id)
    {
        if (! is_numeric($this->duree)) { $this->duree = 0; }
        if (! strlen($this->projet_id))
        {
            $this->projet_id = 0;
        }

        /*
         *  Insertion dans la base
         */
        $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET ";
        $sql .= " datei = ".$this->date;
        $sql .= ", note  = '".addslashes($this->note)."'";
        $sql .= ", duree = ".$this->duree;
        $sql .= ", fk_projet = ".$this->projet_id;
        $sql .= " WHERE rowid = $id";

        if (! $this->db->query($sql) )
        {
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
        }

        return 1;
    }

    /*
     *	\todo A virer quand module de numerotation dispo sur les fiches interventions
     *
     */
    function get_new_num($societe)
    {
        $socprefix = sanitize_string($societe->prefix_comm);
        
        $sql = "SELECT max(ref) FROM ".MAIN_DB_PREFIX."fichinter";
        $sql.= " WHERE ref like 'FI-".$socprefix."%'";

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $row = $this->db->fetch_row(0);
                $num = $row[0];

                /*
                *$num = substr($num, strlen($num) - 4, 4);
                *$num = $num + 1;
                *$num = '0000' . $num;
                *$num = 'FI-' . $prefix_comm . '-' . substr($num, strlen($num) - 4, 4);
                */
                $num = substr($num, 3);
                $num = substr(strstr($num, "-"),1);

                $num = $num + 1;
                //$num = '0000' . $num;
                //$num = 'FI-' . $prefix_comm . '-' . substr($num, strlen($num) - 4, 4);
                $num = 'FI-' . $socprefix . '-' . $num;
                return $num;
            }
        }
        else
        {
            print $this->db->error();
        }
    }

    /**
     *		\brief		Charge en mémoire la fiche intervention
     *		\param		rowid		Id de la fiche à charger
     *		\return		int			<0 si ko, >0 si ok
     */
    function fetch($rowid)
    {
        $sql = "SELECT ref,note,fk_soc,fk_statut,duree,".$this->db->pdate(datei)." as di, fk_projet";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter WHERE rowid=".$rowid;

		dolibarr_syslog("Fichinter.class::fetch rowid=$rowid sql=$sql");

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id         = $rowid;
                $this->date       = $obj->di;
                $this->duree      = $obj->duree;
                $this->ref        = $obj->ref;
                $this->note       = $obj->note;
                $this->socid     = $obj->fk_soc;
                $this->societe_id = $obj->fk_soc;		// A virer, obsolete
                $this->projet_id  = $obj->fk_projet;
                $this->statut     = $obj->fk_statut;

                $this->db->free($resql);
                return 1;
            }
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
    }

    /**
     *		\brief		Valide une fiche intervention
     *		\param		user		User qui valide
     *		\return		int			<0 si ko, >0 si ok
     */
    function valid($user, $outputdir)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
        $sql.= " SET fk_statut = 1, date_valid=now(), fk_user_valid=".$user->id;
        $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";
		$resql=$this->db->query($sql);
        if ($resql)
        {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('FICHEINTER_VALIDATE',$this,$user,$langs,$conf);
            // Fin appel triggers

            return 1;
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
    }

    /**
     *    \brief      Retourne le libellé du statut de l'intervantion
     *    \return     string      Libellé
     */
    function getLibStatut($mode=0)
    {
		return $this->LibStatut($this->statut,$mode);
    }

    /**
     *    \brief      Renvoi le libellé d'un statut donné
     *    \param      statut      id statut
     *    \return     string      Libellé
     */
    function LibStatut($statut,$mode=0)
    {
        if ($mode == 0)
        {
	        return $this->statuts[$statut];
		}
        if ($mode == 1)
        {
        	return $this->statuts_short[$statut];
        }
        if ($mode == 2)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0').' '.$this->statuts_short[$statut];
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut1').' '.$this->statuts_short[$statut];
        }
        if ($mode == 3)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0');
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut1');
        }
        if ($mode == 4)
        {
        	if ($statut==0) return img_picto($this->statuts_short[$statut],'statut0').' '.$this->statuts[$statut];
        	if ($statut==1) return img_picto($this->statuts_short[$statut],'statut1').' '.$this->statuts[$statut];
        }
        if ($mode == 5)
        {
        	if ($statut==0) return $this->statuts_short[$statut].' '.img_picto($this->statuts_short[$statut],'statut0');
        	if ($statut==1) return $this->statuts_short[$statut].' '.img_picto($this->statuts_short[$statut],'statut1');
        }
    }
}  
?>