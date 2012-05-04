<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2007-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis@dolibarr.fr>
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
 *	\file       htdocs/public/members/public_card.php
 *	\ingroup    member
 * 	\brief      File to show a public card of a member
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

// For MultiCompany module
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : 1);
if (is_int($entity))
{
	define("DOLENTITY", $entity);
}

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");

// Security check
if (empty($conf->adherent->enabled)) accessforbidden('',1,1,1);


$langs->load("main");
$langs->load("members");
$langs->load("companies");
$langs->load("other");

$id=GETPOST('id','int');
$object = new Adherent($db);
$extrafields = new ExtraFields($db);



/*
 * Actions
 */

// None



/*
 * View
 */

llxHeaderVierge($langs->trans("MemberCard"));

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('member');
if ($id > 0)
{
	$res=$object->fetch($id,$ref);
	if ($res < 0) { dol_print_error($db,$object->error); exit; }
	$res=$object->fetch_optionals($object->id,$extralabels);

	print_titre($langs->trans("MemberCard"));

	if (empty($object->public))
	{
		 print $langs->trans("ErrorThisMemberIsNotPublic");
	}
	else
	{
		print '<table class="border" cellspacing="0" width="100%" cellpadding="3">';

		print '<tr><td width="15%">'.$langs->trans("Type").'</td><td class="valeur">'.$object->type."</td></tr>\n";

		print '<tr><td>'.$langs->trans("Person").'</td><td class="valeur">'.$object->morphy.'</td></tr>';

		print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" width="35%">'.$object->firstname.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$object->lastname.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($object->address).'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Zip").' '.$langs->trans("Town").'</td><td class="valeur">'.$object->zip.' '.$object->town.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">'.$object->pays.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.$object->email.'&nbsp;</td></tr>';

		print '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.$object->naiss.'&nbsp;</td></tr>';

		if (isset($object->photo) && $object->photo !=''){
			print '<tr><td>URL Photo</td><td class="valeur">'."<A HREF=\"$object->photo\"><IMG SRC=\"$object->photo\"></A>".'&nbsp;</td></tr>';
		}
		//  foreach($objecto->attribute_label as $key=>$value){
		//    print "<tr><td>$value</td><td>".$object->array_options["options_$key"]."&nbsp;</td></tr>\n";
		//  }

		print '<tr><td valign="top">'.$langs->trans("Comments").'</td><td>'.nl2br($object->note).'</td></tr>';

		print '</table>';
	}

}


llxFooterVierge();

$db->close();



/**
 * Show header for card member
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
* Show footer for card member
*
* @return	void
*/
function llxFooterVierge()
{
    printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
}

?>
