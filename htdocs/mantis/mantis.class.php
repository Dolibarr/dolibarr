<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/mantis/mantis.class.php
        \ingroup    mantis
		\brief      Ensemble des fonctions permettant d'acceder a la database mantis.
		\author     Laurent Destailleur.
		\version    $Revision$
*/


/**
        \class      Mantis
		\brief      Classe permettant d'acceder a la database mantis
*/

class Mantis {
    
    var $localdb;

    var $date;
    var $duree = 0;     // Secondes
    var $texte;
    var $desc;
    
    var $error;

  
    /**
    		\brief      Constructeur de la classe d'interface à mantisendar
    */
    function Mantis()
    {
        global $conf;
        global $dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user;
        global $dolibarr_main_db_pass,$dolibarr_main_db_name;

        // Défini parametres mantis (avec substitution eventuelle)
        $mantistype=eregi_replace('__dolibarr_main_db_type__',$dolibarr_main_db_type,$conf->mantis->db->type);
        $mantishost=eregi_replace('__dolibarr_main_db_host__',$dolibarr_main_db_host,$conf->mantis->db->host);
        $mantisport=eregi_replace('__dolibarr_main_db_port__',$dolibarr_main_db_port,$conf->mantis->db->port);
        $mantisuser=eregi_replace('__dolibarr_main_db_user__',$dolibarr_main_db_user,$conf->mantis->db->user);
        $mantispass=eregi_replace('__dolibarr_main_db_pass__',$dolibarr_main_db_pass,$conf->mantis->db->pass);
        $mantisname=eregi_replace('__dolibarr_main_db_name__',$dolibarr_main_db_name,$conf->mantis->db->name);

        // On initie la connexion à la base mantisendar
        require_once (DOL_DOCUMENT_ROOT ."/lib/databases/".$mantistype.".lib.php");
        $this->localdb = new DoliDb($mantistype,$mantishost,$mantisuser,$mantispass,$mantisname,$mantisport);
    }


   
}
?>
