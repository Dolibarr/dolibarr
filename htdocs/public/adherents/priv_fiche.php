<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/public/adherents/priv_fiche.php
 \brief      Fichier de gestion de la popup de selection de date eldy
 \version    $Id$
 */

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$langs->load("main");
$langs->load("members");
$langs->load("companies");


function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	print "<html>\n";
	print "<head>\n";
	print "<title>".$title."</title>\n";
	if ($head) print $head."\n";
	print "</head>\n";
	print "<body>\n";
}

function llxFooter()
{
	print "</body>\n";
	print "</html>\n";
}

$rowid=$_GET["id"];
$adho = new AdherentOptions($db);


/*
 * View
 */

llxHeaderVierge($langs->trans("MemberCard"));

// fetch optionals attributes and labels
$adho->fetch_optionals();
if ($rowid > 0)
{

	$adh = new Adherent($db);
	$adh->id = $rowid;
	$adh->fetch($rowid);
	$adh->fetch_optionals($rowid);

	print_titre($langs->trans("MemberCard"));

	if (empty($adh->public))
	{
		 print $langs->trans("ErrorThisMemberIsNotPublic");
	}
	else
	{
		print '<table class="border" cellspacing="0" width="100%" cellpadding="3">';

		print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adh->type."</td>\n";
		print '<td valign="top" width="50%">'.$langs->trans("Comments").'</tr>';

		print '<tr><td>Personne</td><td class="valeur">'.$adh->morphy.'&nbsp;</td>';

		print '<td rowspan="13" valign="top" width="50%">';
		print nl2br($adh->note).'&nbsp;</td></tr>';

		print '<tr><td width="15%">'.$langs->trans("Surname").'</td><td class="valeur" width="35%">'.$adh->prenom.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Name").'</td><td class="valeur">'.$adh->nom.'&nbsp;</td></tr>';


		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$adh->societe.'&nbsp;</td></tr>';
		print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($adh->adresse).'&nbsp;</td></tr>';
		print '<tr><td>'.$langs->trans("Zip").' '.$langs->trans("Town").'</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'&nbsp;</td></tr>';
		print '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">'.$adh->pays.'&nbsp;</td></tr>';
		print '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.$adh->email.'&nbsp;</td></tr>';
		print '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.$adh->naiss.'&nbsp;</td></tr>';
		if (isset($adh->photo) && $adh->photo !=''){
			print '<tr><td>URL Photo</td><td class="valeur">'."<A HREF=\"$adh->photo\"><IMG SRC=\"$adh->photo\"></A>".'&nbsp;</td></tr>';
		}
		//  foreach($adho->attribute_label as $key=>$value){
		//    print "<tr><td>$value</td><td>".$adh->array_options["options_$key"]."&nbsp;</td></tr>\n";
		//  }
		print '</table>';
	}

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
