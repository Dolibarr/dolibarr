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
 * Script de verfication des données dans l'import des CDR
 */
require ("../../master.inc.php");
require(DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
/*
 * Vérification des lignes présentes
 *
 */

$sql = "SELECT distinct(ligne), fk_fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$resql = $db->query($sql);
if ($resql)
{  
  $num = $db->num_rows();

  while ($row = $db->fetch_row($resql))
    {
      $ligne = new LigneTel($db);
      if ($ligne->fetch($row[0]) >= 0)
	{
	  if ($ligne->fournisseur_id <> $row[1])
	    {
	      print "Ligne $row[0] fournisseur incohérent\n";
	    }

	  if ($ligne->statut <> 3)
	    {
	      print "Ligne $row[0] non activée, comms présentes\n";
	    }
	}
      else
	{
	  print "Ligne $row[0] inconnue\n";
	}  
    }
}
else
{
  dol_syslog("Erreur recherche fournisseur");
  exit ;
}




return $error;
