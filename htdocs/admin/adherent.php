<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *   	\file       htdocs/admin/adherent.php
 *		\ingroup    adherent
 *		\brief      Page d'administration/configuration du module Adherent
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("members");

if (!$user->admin)
accessforbidden();


$typeconst=array('yesno','texte','chaine');


// Action mise a jour ou ajout d'une constante
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	$result=dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:'',$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}

// Action activation d'un sous module du module adherent
if ($_GET["action"] == 'set')
{
	$result=dolibarr_set_const($db, $_GET["name"],$_GET["value"],'',0,'',$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}

// Action desactivation d'un sous module du module adherent
if ($_GET["action"] == 'unset')
{
	$result=dolibarr_del_const($db,$_GET["name"],$conf->entity);
	if ($result < 0)
	{
		print $db->error();
	}
}


/*
 * View
 */

llxHeader();


$var=True;

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MembersSetup"),$linkback,'setup');
print "<br>";


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

// Mail required for members
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_MAIL_REQUIRED">';
print "<tr $bc[$var] class=value><td>".$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_MAIL_REQUIRED,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Send mail information is on by default
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_DEFAULT_SENDINFOBYMAIL">';
print "<tr $bc[$var] class=value><td>".$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';




// Insertion cotisations dans compte financier
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_BANK_USE">';
print "<tr $bc[$var] class=value><td>".$langs->trans("AddSubscriptionIntoAccount").'</td>';
if ($conf->banque->enabled)
{
	print '<td>';
	print $form->selectyesno('constvalue',$conf->global->ADHERENT_BANK_USE,1);
	print '</td><td align="center" width="80">';
	print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
	print '</td>';
}
else
{
	print '<td align="right" colspan="2">';
	print $langs->trans("WarningModuleNotActive",$langs->transnoentities("Module85Name")).' '.img_warning("","");
	print '</td>';
}
print "</tr>\n";
print '</form>';
print '</table>';
print '<br>';

/*
 * Mailman
 */
if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
{
	$var=!$var;
	if ($conf->global->ADHERENT_USE_MAILMAN)
	{
		$lien=img_tick().' ';
		$lien.='<a href="adherent.php?action=unset&value=0&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Disable").'</a>';
		// Edition des varibales globales rattache au theme Mailman
		$constantes=array('ADHERENT_MAILMAN_LISTS',
			    'ADHERENT_MAILMAN_LISTS_COTISANT',
			    'ADHERENT_MAILMAN_ADMINPW',
			    'ADHERENT_MAILMAN_SERVER',
			    'ADHERENT_MAILMAN_UNSUB_URL',
			    'ADHERENT_MAILMAN_URL'
			    );
			    print_fiche_titre("Mailman - Systeme de mailing listes",$lien,'');
			    form_constantes($constantes);
	}
	else
	{
		$lien='<a href="adherent.php?action=set&value=1&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Activate").'</a>';
		print_fiche_titre("Mailman - Systeme de mailing listes",$lien,'');
	}

	print "<hr>\n";
}

/*
 * Spip
 */
if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
{
	$var=!$var;
	if ($conf->global->ADHERENT_USE_SPIP)
	{
		$lien=img_tick().' ';
		$lien.='<a href="adherent.php?action=unset&value=0&name=ADHERENT_USE_SPIP">'.$langs->trans("Disable").'</a>';
		// Edition des varibales globales rattache au theme Mailman
		$constantes=array('ADHERENT_USE_SPIP_AUTO',
			    'ADHERENT_SPIP_SERVEUR',
			    'ADHERENT_SPIP_DB',
			    'ADHERENT_SPIP_USER',
			    'ADHERENT_SPIP_PASS'
			    );
			    print_fiche_titre("SPIP - Systeme de publication en ligne",$lien,'');
			    form_constantes($constantes);
	}
	else
	{
		$lien='<a href="adherent.php?action=set&value=1&name=ADHERENT_USE_SPIP">'.$langs->trans("Activate").'</a>';
		print_fiche_titre("SPIP - Systeme de publication en ligne",$lien,'');
	}

	print "<hr>\n";
}

/*
 * Edition des variables globales non rattache a un theme specifique
 */
$constantes=array(
		'ADHERENT_AUTOREGISTER_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_MAIL',
		'ADHERENT_MAIL_VALID_SUBJECT',
		'ADHERENT_MAIL_VALID',
		'ADHERENT_MAIL_COTIS_SUBJECT',
		'ADHERENT_MAIL_COTIS',
		'ADHERENT_MAIL_RESIL_SUBJECT',
		'ADHERENT_MAIL_RESIL',
		'ADHERENT_MAIL_FROM',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_FOOTER_TEXT',
		'ADHERENT_ETIQUETTE_TYPE'
		);
print_fiche_titre($langs->trans("Other"),'','');

print $langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%,';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%,';
//print '%INFOS%'; Deprecated
print '<br>';

form_constantes($constantes);

$db->close();

print '<br>';

llxFooter('$Date$ - $Revision$');

function form_constantes($tableau)
{
	global $db,$bc,$langs;

	$form = new Form($db);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
	print "</tr>\n";
	$var=true;

	foreach($tableau as $const)
	{
		$sql = "SELECT rowid, name, value, type, note FROM ".MAIN_DB_PREFIX."const WHERE name='".$const."'";
		$result = $db->query($sql);
		if ($result)
		{
			$obj = $db->fetch_object($result);
			$var=!$var;
			print '<form action="adherent.php" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="rowid" value="'.$rowid.'">';
			print '<input type="hidden" name="constname" value="'.$obj->name.'">';
			print '<input type="hidden" name="constnote" value="'.nl2br($obj->note).'">';

			print "<tr $bc[$var]>";

			// Affiche nom constante
			print '<td>';
			print $langs->trans("Desc".$const) != ("Desc".$const) ? $langs->trans("Desc".$const) : ($obj->note?$obj->note:$const);
			print "</td>\n";

			if ($const == 'ADHERENT_ETIQUETTE_TYPE')
			{
				print '<td>';
				// List of possible labels. Values must exists in
				// file htdocs/adherents/PDF_Card.class.php
				require_once(DOL_DOCUMENT_ROOT.'/includes/modules/member/PDF_card.class.php');
				$pdfcardstatic=new PDF_card('5160',1,1,'mm');
				$arrayoflabels=array_keys($pdfcardstatic->_Avery_Labels);

				$form->select_array('constvalue',$arrayoflabels,$obj->value,1,0,1);
				print '</td><td>';
				$form->select_array('consttype',array('yesno','texte','chaine'),1);
			}
			else
			{
				print '<td>';
				if ($obj->type == 'yesno')
				{
					print $form->selectyesno('constvalue',$obj->value,1);
					print '</td><td>';
					$form->select_array('consttype',array('yesno','texte','chaine'),0);
				}
				else if ($obj->type == 'texte')
				{
					print '<textarea class="flat" name="constvalue" cols="35" rows="5" wrap="soft">';
					print $obj->value;
					print "</textarea>\n";
					print '</td><td>';
					$form->select_array('consttype',array('yesno','texte','chaine'),1);
				}
				else
				{
					print '<input type="text" class="flat" size="30" name="constvalue" value="'.$obj->value.'">';
					print '</td><td>';
					$form->select_array('consttype',array('yesno','texte','chaine'),2);
				}
				print '</td>';
			}
			print '<td align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button"> &nbsp;';
			// print '<a href="adherent.php?name='.$const.'&action=unset">'.img_delete().'</a>';
			print "</td></tr>\n";
			print '</form>';
			$i++;
		}
	}
	print '</table>';
}

?>