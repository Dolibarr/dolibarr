<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Script de vérification avant facturation
 */

require ("../../master.inc.php");

dol_syslog("facturation-verif.php BEGIN"); 

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

$error = 0;

$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
  
if ( $db->query($sql) )
{
  $row = $db->fetch_row();
  dol_syslog("facturation-verif.php ".$row[0]." lignes de communications a verifier");
}

/*******************************************************************************
 *
 * Verifie la présence des tarifs adequat
 *
 */
$grille_vente = TELEPHONIE_GRILLE_VENTE_DEFAUT_ID;

$tarif_vente = new TelephonieTarif($db, $grille_vente, "vente");

dol_syslog("facturation-verif.php Grille : $grille contient ".$tarif_vente->num_tarifs." tarifs");

$sql = "SELECT distinct(num) FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";

$resql = $db->query($sql);
  
if ( $resql )
{
  $nums = $db->num_rows($resql);

  while($row = $db->fetch_row($resql) )
    {
      $numero = $row[0];

      /* Reformatage du numéro */

      if (substr($numero,0,2) == '00') /* International */
	{
	}     
      elseif (substr($numero,0,2) == '06') /* Telephones Mobiles */
	{	
	  $numero = "0033".substr($numero,1);
	}
      elseif (substr($numero,0,4) == substr($objp->client,0,4) ) /* Tarif Local */
	{
	  $numero = "0033999".substr($numero, 1);
	}
      else
	{
	  $numero = "0033".substr($numero, 1);
	}	  

      /* Numéros spéciaux */
      if (substr($numero,4,1) == 8)
	{

	}
      else
	{	  
	  if ( $tarif_vente->cout($numero, $x, $y, $z) == 0)
	    {
	      print "Tarif vente manquant pour $numero ($row[0]) $x $y dans la grille $grille\n";
	    }
	}

    }
  $db->free($resql);
}
dol_syslog($error ." erreurs trouvées"); 

dol_syslog("facturation-verif.php END"); 
?>
