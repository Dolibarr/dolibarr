<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
		\file       htdocs/compta/export/index.php
		\ingroup    compta
		\brief      Page export ventilations
		\version    $Revision$
*/

require("../../main.inc.php");
require_once("./class/ComptaJournalPaiement.class.php");
require_once("./class/ComptaJournalVente.class.php");

$langs->load("compta");

$now = time();
if (isset($_GET["year"]))
{
  $year = $_GET["year"];
}
else
{
  $year = strftime("%Y",$now);
}

$updir = $conf->compta->dir_output."/export/";
$dir = $conf->compta->dir_output."/export/".$year."/";

/*
 * Actions
 */

if ($_GET["action"] == 'export')
{
  $modulename='Poivre';

  include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.class.php';

  create_exdir($dir);

  $exc = new ComptaExport($db, $user, $modulename);

  if($_GET["id"] > 0)
    {
      $exc->Export($_GET["id"], $dir);
    }
  else
    {
      $exc->Export(0, $dir);
    }

  /* G�n�ration du journal des Paiements */

  $jp= new ComptaJournalPaiement($db);
  $jp->generatePdf($user, $dir, $exc->id, $exc->ref);

  /* G�n�ration du journal des Ventes */

  $jp= new ComptaJournalVente($db);
  $jp->generatePdf($user, $dir, $exc->id, $exc->ref);
}

/*
 * Affichage page
 */

llxHeader('','Compta - Export');

print_fiche_titre($langs->trans("AccountancyExport"));

if ($exc->error_message)
{
   print '<div class="error">'.$exc->error_message.'</div>';
}

print '<table class="notopnoleftnoright" width="100%">';
print '<tr><td valign="top" width="30%">';

$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."facturedet";
$sql .= " WHERE fk_export_compta = 0";
$resql = $db->query($sql);
if ($resql)
{
  $obj = $db->fetch_object($resql);
  $nbfac = $obj->nb;

  $db->free($resql);
}

$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."paiement";
$sql .= " WHERE fk_export_compta = 0";

$resql = $db->query($sql);
if ($resql)
{
  $obj = $db->fetch_object($resql);
  $nbp = $obj->nb;

  $db->free($resql);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Nb").'</td><td align="right">'.$langs->trans("Nb").'</td></tr>';
$var=false;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Invoices").'</td><td align="right">'.$nbfac.'</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Payments").'</td><td align="right">'.$nbp.'</td></tr>';
print "</table><br>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Year").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

$handle=@opendir($updir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (is_readable($updir.$file) && is_dir($updir.$file) && dol_strlen($file) == 4)
	{
	  $var=!$var;
	  print '<tr '.$bc[$var].'><td><a href="index.php?year='.$file.'">'.$file.'</a><td></tr>';
	}
    }
  closedir($handle);
}

print "</table>";

print '</td><td valign="top" width="70%">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("File").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

$handle=@opendir($dir);
if ($handle)
{
  while (($file = readdir($handle))!==false)
    {
      if (is_readable($dir.$file) && is_file($dir.$file))
	{
	  print '<tr><td><a href="'.DOL_URL_ROOT.'/document.php?modulepart=export_compta&amp;file=export/'.$year.'/'.$file.'&amp;type=text/plain">'.$file.'</a><td>';
	  print '</tr>';
	}
    }
  closedir($handle);
}

print "</table>";

print '</td></tr></table>';

llxFooter('$Date$ - $Revision$');
?>
