<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/comm/mailing/list.php
 *       \ingroup    mailing
 *       \brief      Liste des mailings
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';

$langs->load("mails");

// Security check
$result=restrictedArea($user,'mailing');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="m.date_creat";

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$sref=GETPOST("sref", "alpha");
$filteremail=GETPOST('filteremail','alpha');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('mailinglist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('mailing');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'm.titre'=>'Ref',
);



/*
 * View
 */

llxHeader('',$langs->trans("Mailing"),'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);

if ($filteremail)
{
	$sql = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat as datec, m.date_envoi as date_envoi,";
	$sql.= " mc.statut as sendstatut";
	$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql.= " WHERE m.rowid = mc.fk_mailing AND m.entity = ".$conf->entity;
	$sql.= " AND mc.email = '".$db->escape($filteremail)."'";
	if ($sref) $sql.= " AND m.rowid = '".$db->escape($sref)."'";
	if ($sall) $sql.= " AND (m.titre like '%".$db->escape($sall)."%' OR m.sujet like '%".$db->escape($sall)."%' OR m.body like '%".$db->escape($sall)."%')";
	if (! $sortorder) $sortorder="ASC";
	if (! $sortfield) $sortfield="m.rowid";
	$sql.= $db->order($sortfield,$sortorder);
	$sql.= $db->plimit($conf->liste_limit +1, $offset);
}
else
{
	$sql = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat as datec, m.date_envoi as date_envoi";
	$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
	$sql.= " WHERE m.entity = ".$conf->entity;
	if ($sref) $sql.= " AND m.rowid = '".$db->escape($sref)."'";
	if ($sall) $sql.= " AND (m.titre like '%".$db->escape($sall)."%' OR m.sujet like '%".$db->escape($sall)."%' OR m.body like '%".$db->escape($sall)."%')";
	if (! $sortorder) $sortorder="ASC";
	if (! $sortfield) $sortfield="m.rowid";
	$sql.= $db->order($sortfield,$sortorder);
	$sql.= $db->plimit($conf->liste_limit +1, $offset);
}

//print $sql;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$title=$langs->trans("ListOfEMailings");
	if ($filteremail) $title.=' ('.$langs->trans("SentTo",$filteremail).')';

	$newcardbutton='<a class="butAction" href="'.DOL_URL_ROOT.'/comm/mailing/card.php?action=create">'.$langs->trans('NewMailing').'</a>';

	$i = 0;

	$param = "&sall=".urlencode($sall);
	if ($filteremail) $param.='&filteremail='.urlencode($filteremail);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '',$num, '', 'title_generic.png', 0, $newcardbutton);

	$moreforfilter = '';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth50" name="sref" value="'.dol_escape_htmltag($sref).'">';
	print '</td>';
	// Title
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100 maxwidth50onsmartphone" name="sall" value="'.dol_escape_htmltag($sall).'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	if (! $filteremail) print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	$searchpicto=$form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"m.rowid",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre("Title",$_SERVER["PHP_SELF"],"m.titre",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre("DateCreation",$_SERVER["PHP_SELF"],"m.date_creat",$param,"",'align="center"',$sortfield,$sortorder);
	if (! $filteremail) print_liste_field_titre("NbOfEMails",$_SERVER["PHP_SELF"],"m.nbemail",$param,"",'align="center"',$sortfield,$sortorder);
	if (! $filteremail) print_liste_field_titre("DateLastSend",$_SERVER["PHP_SELF"],"m.date_envoi",$param,"",'align="center"',$sortfield,$sortorder);
	else print_liste_field_titre("DateSending",$_SERVER["PHP_SELF"],"mc.date_envoi",$param,"",'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("Status",$_SERVER["PHP_SELF"],($filteremail?"mc.statut":"m.statut"),$param,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";


	$email=new Mailing($db);

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);



		print "<tr>";
		print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$obj->rowid.'">';
		print img_object($langs->trans("ShowEMail"),"email").' '.stripslashes($obj->rowid).'</a></td>';
		print '<td>'.$obj->titre.'</td>';
		// Date creation
		print '<td align="center">';
		print dol_print_date($db->jdate($obj->datec),'day');
		print '</td>';
		// Nb of email
		if (! $filteremail)
		{
			print '<td align="center">';
			$nbemail = $obj->nbemail;
			/*if ($obj->statut != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
			{
				$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
				print $form->textwithpicto($nbemail,$text,1,'warning');
			}
			else
			{
				print $nbemail;
			}*/
			print $nbemail;
			print '</td>';
		}
		// Last send
		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->date_envoi),'day').'</td>';
		print '</td>';
		// Status
		print '<td align="right" class="nowrap">';
		if ($filteremail)
		{
			print $email::libStatutDest($obj->sendstatut,2);
		}
		else
		{
			print $email->LibStatut($obj->statut,5);
		}
		print '</td>';
		print '<td></td>';
		print "</tr>\n";
		$i++;
	}
	print '</table>';
	print '</div>';
	print '</form>';
	$db->free($result);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
