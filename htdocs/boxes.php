<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/boxes.php
		\brief      Fichier de la classe boxes
		\author     Rodolphe Qiedeville
		\author	    Laurent Destailleur
		\version    $Revision$
*/



/**
        \class      InfoBox
		\brief      Classe permettant la gestion des boxes sur une page
*/

class InfoBox 
{
    var $db;

    /**
     *      \brief      Constructeur de la classe
     *      \param      $DB         Handler d'accès base
     */
    function InfoBox($DB)
    {
        $this->db=$DB;
    }
    

    /**
     *      \brief      Retourne tableau des boites elligibles pour la zone
     *      \param      $zone       ID de la zone (0 pour la Homepage, ...)
     *      \return     array       Tableau d'objet box
     */
    function listBoxes($zone)
    {
        $boxes=array();
        
        $sql = "SELECT b.rowid, b.box_id,";
        $sql.= " d.file, d.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d";
        $sql.= " WHERE b.box_id = d.rowid";
        $sql.= " AND position = ".$zone;
        $sql.= " ORDER BY box_order";
        
        dolibarr_syslog("InfoBox::listBoxes sql=$sql");
        
        $result = $this->db->query($sql);
        if ($result) 
        {
          $num = $this->db->num_rows($result);
          $j = 0;
          while ($j < $num)
            {
              $obj = $this->db->fetch_object($result);
              $boxname=eregi_replace('.php$','',$obj->file);

			  include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$boxname.".php");
			  $box=new $boxname($this->db,$obj->note);
              $boxes[$j]=$box;

              $j++;
            }
        }
        else {
            return array();
        }
        return $boxes;
    }
  
}
?>
