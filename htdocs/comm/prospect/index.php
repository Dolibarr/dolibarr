<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
	    \file       htdocs/comm/prospect/index.php
        \ingroup    commercial
		\brief      Page accueil de la zone prospection
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");

$langs->load("propal");


if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}



/*
 *	Affichage zone prospect
 */

llxHeader();

print_fiche_titre($langs->trans("ProspectionArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

if ($conf->propal->enabled) 
{
  $var=false;
  print '<table class="noborder" width="100%">';
  print '<form method="post" action="'.DOL_URL_ROOT.'/comm/propal.php">';
  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProposal").'</td></tr>';
  print '<tr '.$bc[$var].'><td>';
  print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
  print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
  print '</tr>';
  print "</form></table><br>\n";
}

/*
 * Prospects par status
 *
 */  

$sql = "SELECT count(*) as cc, st.libelle, st.id";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE s.fk_stcomm = st.id AND s.client=2";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql .= " GROUP BY st.id";
$sql .= " ORDER BY st.id";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num > 0 )
	{
		$var=true;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("ProspectsByStatus").'</td></tr>';
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;
			print "<tr $bc[$var]><td><a href=\"prospects.php?page=0&amp;stcomm=".$obj->id."\">";
			print img_action($langs->trans("Show"),$obj->id).' ';
			print $langs->trans("StatusProspect".$obj->id);
			print '</a></td><td align="right">'.$obj->cc.'</td></tr>';
			$i++;
		}
		print "</table><br>";
	}
}


/*
 * Liste des propal brouillons
 */
if ($conf->propal->enabled && $user->rights->propale->lire)
{
    $sql = "SELECT p.rowid, p.ref, p.price, s.nom";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_statut = 0 and p.fk_soc = s.rowid";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

    $resql=$db->query($sql);
    if ($resql)
    {
        $var=true;

        $total=0;
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num > 0)
        {
            print '<table class="noborder"" width="100%">';
            print '<tr class="liste_titre">';
            print '<td colspan="2">'.$langs->trans("ProposalsDraft").'</td></tr>';

            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                print '<tr '.$bc[$var].'><td>';
                print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->rowid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$obj->ref.'</a>';
                print '</td><td align="right">';
                print price($obj->price);
                print "</td></tr>";
                $i++;
                $total += $obj->price;
            }
            if ($total>0) {
                $var=!$var;
                print '<tr class="liste_total"><td>'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td></tr>";
            }
            print "</table><br>";
        }
        $db->free($resql);
    }
}

/*
 * Actions commerciales a faire
 *
 */
print '</td><td valign="top" width="70%" class="notopnoleftnoright">';

if ($conf->agenda->enabled) show_array_actions_to_do(10);

/*
 * Dernieres propales ouvertes
 *
 */
if ($conf->propal->enabled && $user->rights->propale->lire)
{
    $sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id AND p.fk_statut = 1";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid) $sql .= " AND s.rowid = ".$socid;
    $sql .= " ORDER BY p.rowid DESC";
    $sql .= $db->plimit(5, 0);
    
    $resql=$db->query($sql);
    if ($resql)
    {
        $total = 0;
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num > 0)
        {
            $var=true;
    
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProposalsOpened").'</td></tr>';
    
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]><td><a href=\"../propal.php?propalid=".$obj->propalid."\">";
                print img_object($langs->trans("ShowPropal"),"propal").' '.$obj->ref.'</a></td>';
    
                print "<td><a href=\"fiche.php?id=".$obj->socid."\">".img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom."</a></td>\n";
                print "<td align=\"right\">";
                print dolibarr_print_date($obj->dp)."</td>\n";
                print "<td align=\"right\">".price($obj->price)."</td></tr>\n";
                $i++;
                $total += $obj->price;
            }
            if ($total>0) {
                print '<tr class="liste_total"><td colspan="3" align="right">'.$langs->trans("Total")."</td><td align=\"right\">".price($total)."</td></tr>";
            }
            print "</table><br>";
        }
    }
}

/*
 * Soci�t�s � contacter
 *
 */
$sql = "SELECT s.nom, s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE s.fk_stcomm = 1";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql .= " ORDER BY s.tms ASC";
$sql .= $db->plimit(15, 0);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0 )
    {
      $var=true;

      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProspectToContact").'</td></tr>';
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print "<tr $bc[$var]><td width=\"12%\"><a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?id=".$obj->rowid."\">";
	  print img_object($langs->trans("ShowCompany"),"company");
	  print ' '.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter('$Date$ - $Revision$');
?>
