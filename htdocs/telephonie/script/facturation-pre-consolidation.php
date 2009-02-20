<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Prépare les factures à imprimer
 */

/**
   \file       htdocs/telephonie/script/facturation-pre-consolidation.php
   \ingroup    telephonie
   \brief      Consolidation des données de facturation
   \version    $Revision$
*/

require ("../../master.inc.php");

$sql = "UPDATE ";
$sql .= " ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " SET num_prefix = numero WHERE num_prefix IS NULL;";

$resql = $db->query($sql);
  
if (! $resql )
{
  $error = 1;
  dol_syslog("Erreur ".$error);
  dol_syslog($db->error());
}

$sql = "UPDATE ";
$sql .= " ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " SET ym = date_format(date,'%y%m') WHERE ym IS NULL;";

$resql = $db->query($sql);
  
if (! $resql )
{
  $error = 1;
  dol_syslog("Erreur ".$error);
  dol_syslog($db->error());
}

$db->close();
?>
