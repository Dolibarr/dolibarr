<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/public/members/public_list.php
 *	\ingroup    member
 *  \brief      File sample to list members
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : 1);
if (is_int($entity))
{
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';

// Security check
if (empty($conf->adherent->enabled)) accessforbidden('',1,1,1);


$langs->load("main");
$langs->load("members");
$langs->load("companies");
$langs->load("other");


/**
 * Show header for member list
 *
 * @param 	string		$title		Title
 * @param 	string		$head		More info into header
 * @return	void
 */
function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	header("Content-type: text/html; charset=".$conf->file->character_set_client);
	print "<html>\n";
    print "<head>\n";
    print "<title>".$title."</title>\n";
    if ($head) print $head."\n";
    print "</head>\n";
	print "<body>\n";
}

/**
 * Show footer for member list
 *
 * @return	void
 */
function llxFooterVierge()
{
    printCommonFooter('public');

    print "</body>\n";
	print "</html>\n";
}


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$filter=GETPOST('filter');
$statut=GETPOST('statut');

if (! $sortorder) {  $sortorder="ASC"; }
if (! $sortfield) {  $sortfield="nom"; }


/*
 * View
 */

llxHeaderVierge($langs->trans("ListOfValidatedPublicMembers"));

$sql = "SELECT rowid, firstname, lastname, societe, zip, town, email, birth, photo";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent";
$sql.= " WHERE entity = ".$entity;
$sql.= " AND statut = 1";
$sql.= " AND public = 1";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//$sql = "SELECT d.rowid, d.firstname, d.lastname, d.societe, zip, town, d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
//$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
//$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	$param="&statut=$statut&sortorder=$sortorder&sortfield=$sortfield";
	print_barre_liste($langs->trans("ListOfValidatedPublicMembers"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, 0, '');
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td><a href="'.$_SERVER["PHP_SELF"].'?page='.$page.'&sortorder=ASC&sortfield=firstname">'.$langs->trans("Firstname").'</a>';
	print ' <a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&sortorder=ASC&sortfield=lastname">'.$langs->trans("Lastname").'</a>';
	print ' / <a href="'.$_SERVER["PHP_SELF"].'?page='.$page.'&sortorder=ASC&sortfield=societe">'.$langs->trans("Company").'</a></td>'."\n";
	//print_liste_field_titre($langs->trans("DateToBirth"),"public_list.php","birth",'',$param,$sortfield,$sortorder); // est-ce nécessaire ??
	print_liste_field_titre($langs->trans("EMail"),"public_list.php","email",'',$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),"public_list.php","zip","",$param,$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),"public_list.php","town","",$param,$sortfield,$sortorder);
	print "<td>".$langs->trans("Photo")."</td>\n";
	print "</tr>\n";

	$var=True;
	while ($i < $num && $i < $conf->liste_limit)
	{
		$objp = $db->fetch_object($result);
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="public_card.php?id='.$objp->rowid.'">'.dolGetFirstLastname($obj->firstname, $obj->lastname).($objp->societe?' / '.$objp->societe:'').'</a></td>'."\n";
		//print "<td>$objp->birth</td>\n"; // est-ce nécessaire ??
		print '<td>'.$objp->email.'</td>'."\n";
		print '<td>'.$objp->zip.'</td>'."\n";
		print '<td>'.$objp->town.'</td>'."\n";
		if (isset($objp->photo) && $objp->photo != '')
		{
			$form = new Form($db);
			print '<td>';
			print $form->showphoto('memberphoto', $objp, 64);
			print '</td>'."\n";
		}
		else
		{
			print "<td>&nbsp;</td>\n";
		}
		print "</tr>";
		$i++;
	}
	print "</table>";
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooterVierge();
?>
