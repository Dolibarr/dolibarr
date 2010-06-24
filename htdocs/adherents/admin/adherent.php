<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    adherent
 *		\brief      Page to setup the module Foundation
 *		\version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("members");

if (!$user->admin)
accessforbidden();


$typeconst=array('yesno','texte','chaine');


// Action mise a jour ou ajout d'une constante
if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	if (($_POST["constname"]=='ADHERENT_CARD_TYPE' || $_POST["constname"]=='ADHERENT_ETIQUETTE_TYPE')
		&& $_POST["constvalue"] == -1) $_POST["constvalue"]='';

	$const=$_POST["constname"];
	$value=$_POST["constvalue"];
	if (in_array($const,array('ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL'))) $value=$_POST["constvalue".$const];
	$type=$_POST["consttype"];
	$constnote=isset($_POST["constnote"])?$_POST["constnote"]:'';

	$result=dolibarr_set_const($db,$const,$value,$typeconst[$type],0,$constnote,$conf->entity);
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
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
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
			    'ADHERENT_MAILMAN_URL',
			    'ADHERENT_MAILMAN_UNSUB_URL'
			    );
			    print_fiche_titre("Mailman mailing list system",$lien,'');
			    form_constantes($constantes);
	}
	else
	{
		$lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Activate").'</a>';
		print_fiche_titre("Mailman mailing list system",$lien,'');
	}

	print "<hr>\n";
}

/*
 * Spip
 */
$var=!$var;
if ($conf->global->ADHERENT_USE_SPIP)
{
	$lien=img_tick().' ';
	$lien.='<a href="'.$_SERVER["PHP_SELF"].'?action=unset&value=0&name=ADHERENT_USE_SPIP">'.$langs->trans("Disable").'</a>';
	// Edition des varibales globales rattache au theme Mailman
	$constantes=array('ADHERENT_USE_SPIP_AUTO',
		    'ADHERENT_SPIP_SERVEUR',
		    'ADHERENT_SPIP_DB',
		    'ADHERENT_SPIP_USER',
		    'ADHERENT_SPIP_PASS'
		    );
		    print_fiche_titre("SPIP CMS",$lien,'');
		    form_constantes($constantes);
}
else
{
	$lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_SPIP">'.$langs->trans("Activate").'</a>';
	print_fiche_titre("SPIP - CMS",$lien,'');
}

print "<hr>\n";

/*
 * Edition info modele document
 */
$constantes=array(
		'ADHERENT_CARD_TYPE',
//		'ADHERENT_CARD_BACKGROUND',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_TEXT_RIGHT',
		'ADHERENT_CARD_FOOTER_TEXT'
		);
print_fiche_titre($langs->trans("MembersCards"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%, ';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
//print '%INFOS%'; Deprecated
print '<br>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array(
		'ADHERENT_ETIQUETTE_TYPE'
		);
print_fiche_titre($langs->trans("MembersTickets"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%, ';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
//print '%INFOS%'; Deprecated
print '<br>';

print '<br>';
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

		);
print_fiche_titre($langs->trans("Other"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%,';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%';
//print '%YEAR%, %MONTH%, %DAY%';	// Not supported
//print '%INFOS%'; Deprecated
print '<br>';


$db->close();

print '<br>';

llxFooter('$Date$ - $Revision$');



function form_constantes($tableau)
{
	global $db,$bc,$langs,$conf,$_Avery_Labels;

	$form = new Form($db);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("Value").'*</td>';
	print '<td>&nbsp;</td>';
	print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
	print "</tr>\n";
	$var=true;

	$listofparam=array();
	foreach($tableau as $const)	// Loop on each param
	{
		$sql = "SELECT ";
		$sql.= "rowid";
		$sql.= ", ".$db->decrypt('name')." as name";
		$sql.= ", ".$db->decrypt('value')." as value";
		$sql.= ", type";
		$sql.= ", note";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE ".$db->decrypt('name')." = '".$const."'";
		$sql.= " AND entity in (0, ".$conf->entity.")";
		$sql.= " ORDER BY name ASC, entity DESC";
		$result = $db->query($sql);

		dol_syslog("List params sql=".$sql);
		if ($result)
		{
			$obj = $db->fetch_object($result);	// Take first result of select
			$var=!$var;

			print "\n".'<form action="adherent.php" method="POST">';

			print "<tr ".$bc[$var].">";

			// Affiche nom constante
			print '<td>';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="rowid" value="'.$rowid.'">';
			print '<input type="hidden" name="constname" value="'.$const.'">';
			print '<input type="hidden" name="constnote" value="'.nl2br($obj->note).'">';

			print $langs->trans("Desc".$const) != ("Desc".$const) ? $langs->trans("Desc".$const) : ($obj->note?$obj->note:$const);
			print "</td>\n";

			if ($const == 'ADHERENT_CARD_TYPE' || $const == 'ADHERENT_ETIQUETTE_TYPE')
			{
				print '<td>';
				// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
				require_once(DOL_DOCUMENT_ROOT.'/lib/format_cards.lib.php');
				$arrayoflabels=array();
				foreach(array_keys($_Avery_Labels) as $codecards)
				{
					$arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
				}
				print $form->selectarray('constvalue',$arrayoflabels,($obj->value?$obj->value:'CARD'),1,0,0);
				print '</td><td>';
				print '<input type="hidden" name="consttype" value="yesno">';
				print '</td>';
			}
			else
			{
				print '<td>';
				//print 'aa'.$const;
				if (in_array($const,array('ADHERENT_CARD_TEXT','ADHERENT_CARD_TEXT_RIGHT')))
				{
					print '<textarea class="flat" name="constvalue" cols="35" rows="5" wrap="soft">'."\n";
					print $obj->value;
					print "</textarea>\n";
					print '</td><td>';
					print '<input type="hidden" name="consttype" value="texte">';
				}
				else if (in_array($const,array('ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL')))
				{
				    // Editor wysiwyg
					if ($conf->fckeditor->enabled)
					{
						require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
						$doleditor=new DolEditor('constvalue'.$const,$obj->value,160,'dolibarr_notes','',false,false);
						$doleditor->Create();
					}
					else
					{
						print '<textarea class="flat" name="constvalue'.$const.'" cols="60" rows="5" wrap="soft">';
						print dol_htmlentitiesbr_decode($obj->value);
						print '</textarea>';
					}

					print '</td><td>';
					print '<input type="hidden" name="consttype" value="texte">';
				}
				else if ($obj->type == 'yesno')
				{
					print $form->selectyesno('constvalue',$obj->value,1);
					print '</td><td>';
					print '<input type="hidden" name="consttype" value="yesno">';
				}
				else
				{
					print '<input type="text" class="flat" size="30" name="constvalue" value="'.$obj->value.'">';
					print '</td><td>';
					print '<input type="hidden" name="consttype" value="chaine">';
				}
				print '</td>';
			}
			print '<td align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button"> &nbsp;';
			// print '<a href="adherent.php?name='.$const.'&action=unset">'.img_delete().'</a>';
			print "</td>";
			print "</tr>\n";
			print "</form>\n";
			$i++;
		}
	}
	print '</table>';
}

?>