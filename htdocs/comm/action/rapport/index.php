<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/action/rapport/index.php
        \ingroup    commercial
		\brief      Page accueil des rapports des actions
		\version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datea";

// Sécurité accés client
if ($user->societe_id > 0) 
{
	$action = '';
	$socid = $user->societe_id;
}



/*
 * Actions
 */
if ($_GET["action"] == 'builddoc')
{
	$cat = new CommActionRapport($db, $_GET["month"], $_GET["year"]);
	$result=$cat->generate($_GET["id"]);
}


/*
 * Affichage liste
 */

llxHeader();

$sql = "SELECT count(*) as cc, date_format(a.datea, '%m/%Y') as df";
$sql.= ", date_format(a.datea, '%m') as month";
$sql.= ", date_format(a.datea, '%Y') as year";
$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql.= " WHERE percent = 100";
$sql.= " GROUP BY date_format(a.datea, '%m/%Y') ";
$sql.= " ORDER BY a.datea DESC";
$sql.= $db->plimit($limit+1,$offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print_barre_liste($langs->trans("DoneActions"), $page, "index.php",'',$sortfield,$sortorder,'',$num);

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td align="center">'.$langs->trans("Nb").'</td>';
	print '<td>'.$langs->trans("Action").'</td>';
	print '<td align="center">'.$langs->trans("PDF").'</td>';
	print '<td align="center">'.$langs->trans("Date").'</td>';
	print '<td align="center">'.$langs->trans("Size").'</td>';
	print "</tr>\n";
	$var=true;
	while ($i < min($num,$limit))
	{
		$obj=$db->fetch_object($resql);
		
		if ($obj)
		{
			$var=!$var;
			print "<tr $bc[$var]>";
	
			print "<td>$obj->df</td>\n";
			print '<td align="center">'.$obj->cc.'</td>';
	
			print '<td>';
			print '<a href="index.php?action=builddoc&amp;page='.$page.'&amp;month='.$obj->month.'&amp;year='.$obj->year.'">'.img_file_new().'</a>';
			print '</td>';
	
			$name = "actions-".$obj->month."-".$obj->year.".pdf";
			$relativepath= $name;
			$file = $conf->actions->dir_temp."/".$name;
	
			if (file_exists($file))
			{
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/document.php?page='.$page.'&amp;file='.urlencode($relativepath).'&amp;modulepart=actionsreport">'.img_pdf().'</a></td>';
				print '<td align="center">'.dolibarr_print_date(filemtime($file),'dayhour').'</td>';
				print '<td align="center">'.filesize($file). ' '.$langs->trans("Bytes").'</td>';
			}
			else {
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
			}
	
			print "</tr>\n";
		}
		$i++;
	}
	print "</table>";
	$db->free();
}
else
{
	dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
