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
 * Generation des graphiques clients
 *
 *
 *
 */
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");



require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/ca.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/gain.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/heureappel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/joursemaine.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camoyen.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/appelsdureemoyenne.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/comm.nbmensuel.class.php");

//require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/montant.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/camenbert.class.php");

$error = 0;


/***********************************************************************/
/*
/* Chiffre d'affaire mensuel par client
/*
/***********************************************************************/

/*
 * Lecture des clients
 *
 */

$sql = "SELECT s.idp as socidp, s.nom, count(l.ligne) as ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE l.fk_client_comm = s.idp ";
$sql .= " GROUP BY s.idp";

if ($db->query($sql))
{

  $clients = array();

  $num = $db->num_rows();
  print "$num client a traiter\n";
  $i = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object();	

      $dir = $img_root . "client/".substr($obj->socidp,0,1)."/".$obj->socidp."/";

      $clients[$i] = $obj->socidp;

      $i++;
    }
}

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_client_stats";
$db->query($sql);

foreach ($clients as $client)
{
  print ".";

  /*
  $file = $img_root . "client/".substr($client,0,1)."/".$client."/graphca.png";
  $graphca = new GraphCa($db, $file);
  $graphca->client = $client;
  $graphca->GraphDraw();
  */

  $file = $img_root . "client/".substr($client,0,1)."/".$client."/graphgain.png";
  $file = "/dev/null";
  $graphgain = new GraphGain ($db, $file);
  $graphgain->client = $client;
  $graphgain->show_console = 0 ;
  $graphgain->GraphDraw();

  if ($graphgain->total_cout > 0)
    {
      $marge = ( ($graphgain->total_ca - $graphgain->total_cout) / $graphgain->total_cout * 100);
    }

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_client_stats (fk_client_comm, gain, ca, cout, marge)";
  $sql .= " VALUES (".$client.",'".ereg_replace(",",".",$graphgain->total_gain)."'";
  $sql .= ",'".ereg_replace(",",".",$graphgain->total_ca)."'";
  $sql .= ",'".ereg_replace(",",".",$graphgain->total_cout)."'";
  $sql .= ",'".ereg_replace(",",".",$marge)."')";
  $db->query($sql);

  /*

  $file = $img_root . "client/".substr($client,0,1)."/".$client."/graphappelsdureemoyenne.png";

  $graphgain = new GraphAppelsDureeMoyenne ($db, $file);
  $graphgain->client = $client;
  $graphgain->show_console = 0 ;
  $graphgain->GraphDraw();

  $file = $img_root . "client/".substr($client,0,1)."/".$client."/nb-comm-mensuel.png";

  $graphx = new GraphCommNbMensuel ($db, $file);
  $graphx->client = $client;
  $graphx->show_console = 0 ;
  $graphx->Graph();

  */
}
print "\n";
?>
