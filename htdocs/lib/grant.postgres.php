<?php
/* Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

/**	
        \file       htdocs/lib/grant.postgres.php
		\brief      Effectue les GRANT sur toutes les tables 
		\author     Sebastien Di Cintio
		\author	    Benoit Mortier
		\version    $Revision$
		
*/

$conf = "../conf/conf.php";

if (file_exists($conf))
{
  include($conf);

}
$nom =$dolibarr_main_db_user;


// Scan tables pour générer le grant
$dir = "../../pgsql/tables";

$handle=opendir($dir);
$table_list="";
while (($file = readdir($handle))!==false)
{
    if (! ereg("^mysql",$file,$reg) && ! ereg("\.key\.sql",$file) && ereg("^(.*)\.sql",$file,$reg))
    {
        if ($table_list) {
            $table_list.=", ".$reg[0];
        }
        else {
            $table_list.=$reg[0];
        }
    }
}

// Genere le grant_query
$grant_query = "GRANT ALL ON $table_list TO \"$nom\";";
print $grant_query;

?>
