<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/admin/sql.php
		\brief      Fichier de fonction sql
		\version    $Revision$
    \todo       A deplacer dans le gestionnaire d'abstraction de base (mysql.lib.php ou pgsql.lib.php)
*/

function db_create_table($db, $table)
{

  $sql = "";
  $fcontents = file( "../../mysql/tables/$table.sql" );

  while ( list( $numero_ligne, $ligne ) = each( $fcontents ) )
    {
      $sql .=  $ligne;
    }

  return $db->query($sql);
} 

function db_drop_table($db, $table)
{

  return $db->query("DROP table $table");
} 


?>
