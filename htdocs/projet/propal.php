<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/projet/propal.php
        \ingroup    projet propale
		\brief      Page des propositions commerciales par projet
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$langs->load("projects");
$langs->load("companies");
$langs->load("propal");

$user->getrights('projet');

if (!$user->rights->projet->lire) accessforbidden();

// Sécurité accés client
$projetid='';
if ($_GET["id"]) { $projetid=$_GET["id"]; }

if ($projetid == '') accessforbidden();

if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}

// Protection restriction commercial
if ($projetid)
{
	$sql = "SELECT p.rowid, p.fk_soc";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	if (!$user->rights->commercial->client->voir) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc ";
	$sql.= " WHERE p.rowid = ".$projetid;
	if (!$user->rights->commercial->client->voir) $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND p.fk_soc = ".$socid;
	
	if ( $db->query($sql) )
	{
		if ( $db->num_rows() == 0) accessforbidden();
	}
}


llxHeader("","../");

$projet = new Project($db);
$projet->fetch($_GET["id"]);
$projet->societe->fetch($projet->societe->id);


$head=project_prepare_head($projet);
dolibarr_fiche_head($head, 'propal', $langs->trans("Project"));


$propales = array();

print '<table class="border" width="100%">';
print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';      
print '<tr><td>'.$langs->trans("Company").'</td><td>'.$projet->societe->getNomUrl(1).'</td></tr>';
print '</table>';

print '</div>';

/*
 * Barre d'action
 *
 */
 print '<div class="tabsAction">';

 if ($conf->propal->enabled && $user->rights->propale->creer)
 {
     $langs->load("propal");
     print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$projet->societe->id.'&amp;action=create&amp;projetid='.$projet->id.'">'.$langs->trans("AddProp").'</a>';
 }
 print '</div>';

$propales = $projet->get_propal_list();

if (sizeof($propales)>0 && is_array($propales))
{
    print '<br>';
    
    print_titre($langs->trans("ListProposalsAssociatedProject"));
    print '<table class="noborder" width="100%">';
    
    print '<tr class="liste_titre">';
    print '<td width="15%">'.$langs->trans("Ref").'</td><td width="25%">'.$langs->trans("Date").'</td><td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';
    
    for ($i = 0; $i<sizeof($propales);$i++)
    {
        $propale = new Propal($db);
        $propale->fetch($propales[$i]);
    
        $var=!$var;
        print "<tr $bc[$var]>";
        print "<td><a href=\"../comm/propal.php?propalid=$propale->id\">$propale->ref</a></td>\n";
    
        print '<td>'.dolibarr_print_date($propale->datep).'</td>';
    
        print '<td align="right">'.price($propale->price).'</td><td>&nbsp;</td></tr>';
        $total = $total + $propale->total_ht;
    }
    
    print '<tr class="liste_total"><td colspan="2">'.$i.' '.$langs->trans("Proposal").'</td>';
    print '<td align="right">'.$langs->trans("TotalHT").': '.price($total).'</td>';
    print '<td align="left">'.$langs->trans("Currency".$conf->monnaie).'</td></tr></table>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
