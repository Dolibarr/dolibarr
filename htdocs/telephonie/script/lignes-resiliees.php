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
 * Calcul des commissions des distributeurs
 */

require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");

$error = 0;
$lignes = array();
$resil  = array();
$datetime = time();

$datel = time() - (3600 * 24 * 7);

/********************************************************
 *
 * Verification des données
 *
 *
 *********************************************************/

$distributeurs = array();

$sql = "SELECT fk_ligne, ".$db->pdate("tms");
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
$sql .= " WHERE statut = 6";
$sql .= " AND tms >= '".strftime("%Y-%m-%d",$datel)."';";
  
$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      array_push($lignes, $row[0]);
      $resil[$row[0]] = strftime("%d/%m/%y",$row[1]);
      $i++;
    }
  $db->free($resql);
}
else
{
  $error = 1;
}

$llig = substr("Ligne".str_repeat(" ",10), 0, 10);
$lsoc = substr("Societe".str_repeat(" ",40), 0, 40);
$lca = substr(str_repeat(" ",10)."CA Moyen", -10);
$lres = substr(str_repeat(" ",10)."Date Resil", -10);

$message ="Bonjour,\n\n";
$message .= "Veuillez trouver ci-joint la liste des lignes résiliées ces 7 derniers jours avec le chiffre d'affaire moyen.\n\n";

$message .= "$llig $lsoc $lca $lres\n";
$message .= str_repeat("-",73)."\n";

foreach ($lignes as $lid)
{
  //  print "$lid\n";
  $ligne = new LigneTel($db);
  $ligne->fetch_by_id($lid);

  $sql = "SELECT sum(cout_vente), count(cout_vente)";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";
  $sql .= " WHERE fk_ligne = ".$ligne->id;

  $resql = $db->query($sql);
  
  if ( $resql )
    {
      while ($row = $db->fetch_row($resql))
	{
	  $sum = $row[0];
	  $nb = $row[1];
	}
      $db->free($resql);
    }
  else
    {
      $error = 1;
    }

  $num = $ligne->numero;

  $societe = new Societe($db);
  $societe->fetch($ligne->client_comm_id);
  $socnom = $societe->nom;

  if (strlen($socnom) > 40)
    {
      $socnom = substr($socnom, 0, 37) . "...";
    }

  $socnom = substr($socnom.str_repeat(" ",40), 0, 40) ;

  if ($nb > 0)
    {
      $ca = $sum / $nb;
    }
  else
    {
      $ca = 0;
    }

  $pm = substr("          ".sprintf("%01.2f",$ca), -10);

  $message .= "$num $socnom $pm   ".$resil[$ligne->id]."\n";
}


$message .="\n--\n";
$message .= "Ceci est un message automatique envoyé par dolibarr auquel vous ne pouvez pas répondre\n";

$users = array(1, 32);

foreach ($users as $xuser)
{
  $cuser = new User($db, $xuser);
  $cuser->fetch();

  $subject ="Liste des lignes résiliées";
  $sendto = $cuser->prenom . " ".$cuser->nom . " <".$cuser->email.">";
  $from = "noreply@noreply.null";
  $message = wordwrap( $message, 76 );
  
  $mailfile = new CMailFile($subject,
			    $sendto,
			    $from,
			    $message, array(), array(), array());
  if ( $mailfile->sendfile() )
    {
      
    }
}
?>
