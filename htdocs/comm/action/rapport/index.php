<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/comm/action/rapport/index.php
 *      \ingroup    commercial
 *		\brief      Page with reports of actions
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/action/rapport.pdf.php';

$langs->load("commercial");

$action=GETPOST('action','alpha');
$month=GETPOST('month');
$year=GETPOST('year');

$mesg='';
$mesgs=array();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datep";

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', $socid, '', 'myactions');


/*
 * Actions
 */
if ($action == 'builddoc')
{
	$cat = new CommActionRapport($db, $month, $year);
	$result=$cat->write_file(GETPOST('id','int'));
	if ($result < 0)
	{
		$mesg=$cat->error;
	}
}


/*
 * View
 */

llxHeader();

$sql = "SELECT count(*) as cc,";
$sql.= " date_format(a.datep, '%m/%Y') as df,";
$sql.= " date_format(a.datep, '%m') as month,";
$sql.= " date_format(a.datep, '%Y') as year";
$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE a.fk_user_author = u.rowid";
$sql.= " AND a.entity = ".$conf->entity;
//$sql.= " AND percent = 100";
$sql.= " GROUP BY year, month, df";
$sql.= " ORDER BY year DESC, month DESC, df DESC";
$sql.= $db->plimit($limit+1,$offset);

//print $sql;
dol_syslog("select sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print_barre_liste($langs->trans("Actions"), $page, "index.php",'',$sortfield,$sortorder,'',$num);

	dol_htmloutput_mesg($mesg,$mesgs);

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
			print "<tr ".$bc[$var].">";

			print "<td>".$obj->df."</td>\n";
			print '<td align="center">'.$obj->cc.'</td>';

			print '<td>';
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=builddoc&amp;page='.$page.'&amp;month='.$obj->month.'&amp;year='.$obj->year.'">'.img_picto('','filenew').'</a>';
			print '</td>';

			$name = "actions-".$obj->month."-".$obj->year.".pdf";
			$relativepath= $name;
			$file = $conf->agenda->dir_temp."/".$name;

			if (file_exists($file))
			{
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/document.php?page='.$page.'&amp;file='.urlencode($relativepath).'&amp;modulepart=actionsreport">'.img_pdf().'</a></td>';
				print '<td align="center">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
				print '<td align="center">'.dol_print_size(dol_filesize($file)).'</td>';
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
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter();
?>
