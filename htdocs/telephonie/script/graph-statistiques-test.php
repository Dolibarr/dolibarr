<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Generation des graphiques
 *
 * Test de charges pour déploiement en cluster
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");

$img_root = DOL_DATA_ROOT."/graph/telephonie/";

$sql = "SELECT SQL_BIG_RESULT date_format(date, '%m'), duree, numero";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " ORDER BY fk_ligne";

$resql = $db->query($sql);

if ($resql)
{
  $durees = array();
  $kilomindurees = array();
  $labels = array();

  $num = $db->num_rows($resql);
  $lim = ($num - $nbval);
  $i = 0;
  $j = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $labels[$j] = $row[0];
      $durees[$j] = $row[1];
      $kilomindurees_mob[$j] = ($row[1]/60000);
      
      $i++;
    }
}


?>
