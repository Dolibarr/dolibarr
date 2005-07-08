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
 * Prépare les factures à imprimer
 */

/**
   \file       htdocs/telephonie/script/facturation-emission.php
   \ingroup    telephonie
   \brief      Emission des factures
   \version    $Revision$
*/


require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once (DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");

$error = 0;

$datetime = time();

$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

$user = new User($db, 1);

$month = strftime("%m", $datetime);
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $month = "12";
  $year = $year - 1;
}
else
{
  $month = substr("00".($month - 1), -2) ;
}

/*
 * Lecture du batch
 *
 */

$sql = "SELECT distinct(f.fk_facture), ff.facnumber ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_service as cs";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " , ".MAIN_DB_PREFIX."facture as ff";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";

$sql .= " WHERE l.fk_contrat = cs.fk_contrat";
$sql .= " AND f.fk_ligne = l.rowid";
$sql .= " AND f.fk_facture = ff.rowid";
$sql .= " AND date_format(f.date,'%m%Y') = '".$month.$year."'";

$resql = $db->query($sql);
  
dolibarr_syslog("Impression des factures de ".$month.$year);

if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  dolibarr_syslog("$num factures a imprimer");

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $file = DOL_DATA_ROOT."/facture/".$row[1]."/".$row[1].".pdf";

      if (! copy($file,"/tmp/facture/".$row[1].".pdf"))
	{
	  dolibarr_syslog("Error copy $file");
	}

      $i++;
    }

  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
  dolibarr_syslog($db->error());
}

/*
 * Traitements
 *
 */

$db->close();

dolibarr_syslog("Conso mémoire ".memory_get_usage() );

?>
