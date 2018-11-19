<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
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
 *      \file       htdocs/adherents/type.php
 *      \ingroup    member
 *      \brief      Member's type setup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("members");

$rowid  = GETPOST('rowid','int');
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');
$backtopage = GETPOST('backtopage','alpha');

$search_lastname	= GETPOST('search_lastname','alpha');
$search_login		= GETPOST('search_login','alpha');
$search_email		= GETPOST('search_email','alpha');
$type				= GETPOST('type','alpha');
$status				= GETPOST('status','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.lastname"; }

$label=GETPOST("label","alpha");
$subscription=GETPOST("subscription","int");
$vote=GETPOST("vote","int");
$comment=GETPOST("comment");
$mail_valid=GETPOST("mail_valid");

// Security check
$result=restrictedArea($user,'adherent',$rowid,'adherent_type');

$object = new AdherentType($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('adherent_type');

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_lastname="";
    $search_login="";
    $search_email="";
    $type="";
    $sall="";
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypecard','globalcard'));


/*
 *	Actions
 */

if ($cancel) {

	$action='';

	if (! empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == 'add' && $user->rights->adherent->configurer)
{
	$object->label			= trim($label);
	$object->subscription	= (int) trim($subscription);
	$object->note			= trim($comment);
	$object->mail_valid		= trim($mail_valid);
	$object->vote			= (boolean) trim($vote);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
	if ($ret < 0) $error++;

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), null, 'errors');
	}
	else {
		$sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."adherent_type WHERE libelle='".$db->escape($object->label)."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists",$login), null, 'errors');
		}
	}

	if (! $error)
	{
		$id=$object->create($user);
		if ($id > 0)
		{
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	}
	else
	{
		$action = 'create';
	}
}

if ($action == 'update' && $user->rights->adherent->configurer)
{
	$object->fetch($rowid);

	$object->oldcopy = clone $object;

	$object->label			= trim($label);
	$object->subscription	= (int) trim($subscription);
	$object->note			= trim($comment);
	$object->mail_valid		= trim($mail_valid);
	$object->vote			= (boolean) trim($vote);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
	if ($ret < 0) $error++;

	$ret=$object->update($user);

	if ($ret >= 0 && ! count($object->errors))
	{
		setEventMessages($langs->trans("MemberTypeModified"), null, 'mesgs');
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$object->id);
	exit;
}

if ($action == 'confirm_delete' && $user->rights->adherent->configurer)
{
	$object->fetch($rowid);
	$res=$object->delete();

	if ($res > 0)
	{
		setEventMessages($langs->trans("MemberTypeDeleted"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages($langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
		$action='';
	}
}


/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');


// List of members type
if (! $rowid && $action != 'create' && $action != 'edit')
{
	//dol_fiche_head('');

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.vote";
	$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql.= " WHERE d.entity IN (".getEntity('member_type').")";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';

		$newcardbutton='';
		if ($user->rights->adherent->configurer)
		{
			$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/adherents/type.php?action=create"><span class="valignmiddle">'.$langs->trans('NewMemberType').'</span>';
			$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
			$newcardbutton.= '</a>';
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

		print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, $newcardbutton, '', $limit);

		$moreforfilter = '';

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th align="center">'.$langs->trans("SubscriptionRequired").'</th>';
		print '<th align="center">'.$langs->trans("VoteAllowed").'</th>';
		print '<th>&nbsp;</th>';
		print "</tr>\n";

		$membertype = new AdherentType($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);

			$membertype->id = $objp->rowid;
			$membertype->ref = $objp->rowid;
			$membertype->label = $objp->rowid;

			print '<tr class="oddeven">';
			print '<td>';
			print $membertype->getNomUrl(1);
			//<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a>
			print '</td>';
			print '<td>'.dol_escape_htmltag($objp->label).'</td>';
			print '<td align="center">'.yn($objp->subscription).'</td>';
			print '<td align="center">'.yn($objp->vote).'</td>';
			if ($user->rights->adherent->configurer)
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			else
				print '<td align="right">&nbsp;</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	}
	else
	{
		dol_print_error($db);
	}
}


/* ************************************************************************** */
/*                                                                            */
/* Creation mode                                                              */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	$object = new AdherentType($db);

	print load_fiche_titre($langs->trans("NewMemberType"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $form->selectyesno("subscription",1,1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $form->selectyesno("vote",0,1);
	print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="comment" wrap="soft" class="centpercent" rows="3"></textarea></td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (empty($reshook))
	{
		print $object->showOptionals($extrafields,'edit');
	}
	print '<tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
	print '</div>';

	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
	if ($action != 'edit')
	{
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".$object->id,$langs->trans("DeleteAMemberType"),$langs->trans("ConfirmDeleteMemberType",$object->label),"confirm_delete", '',0,1);
		}

		$head = member_type_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("SubscriptionRequired").'</td><td>';
		print yn($object->subscription);
		print '</tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print yn($object->vote);
		print '</tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->note)."</td></tr>";

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		print nl2br($object->mail_valid)."</td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>';

		dol_fiche_end();

		/*
		 * Buttons
		 */

		print '<div class="tabsAction">';

		// Edit
		if ($user->rights->adherent->configurer)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Add
		print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&typeid='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.$langs->trans("AddMember").'</a></div>';

		// Delete
		if ($user->rights->adherent->configurer)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$object->id.'">'.$langs->trans("DeleteType").'</a></div>';
		}

		print "</div>";


		// Show list of members (nearly same code than in page list.php)

		$membertypestatic=new AdherentType($db);

		$now=dol_now();

		$sql = "SELECT d.rowid, d.login, d.firstname, d.lastname, d.societe, ";
		$sql.= " d.datefin,";
		$sql.= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
		$sql.= " t.libelle as type, t.subscription";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
		$sql.= " WHERE d.fk_adherent_type = t.rowid ";
		$sql.= " AND d.entity IN (".getEntity('adherent').")";
		$sql.= " AND t.rowid = ".$object->id;
		if ($sall)
		{
			$sql.=natural_search(array("f.firstname","d.lastname","d.societe","d.email","d.login","d.address","d.town","d.note_public","d.note_private"), $sall);
		}
		if ($status != '')
		{
		    $sql.= natural_search('d.statut', $status, 2);
		}
		if ($action == 'search')
		{
			if (GETPOST('search','alpha'))
			{
		  		$sql.= natural_search(array("d.firstname","d.lastname"), GETPOST('search','alpha'));
		  	}
		}
		if (! empty($search_lastname))
		{
			$sql.= natural_search(array("d.firstname","d.lastname"), $search_lastname);
		}
		if (! empty($search_login))
		{
			$sql.= natural_search("d.login", $search_login);
		}
		if (! empty($search_email))
		{
			$sql.= natural_search("d.email", $search_email);
		}
		if ($filter == 'uptodate')
		{
		    $sql.=" AND datefin >= '".$db->idate($now)."'";
		}
		if ($filter == 'outofdate')
		{
		    $sql.=" AND datefin < '".$db->idate($now)."'";
		}

		$sql.= " ".$db->order($sortfield,$sortorder);

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$resql = $db->query($sql);
		    if ($resql) $nbtotalofrecords = $db->num_rows($result);
		    else dol_print_error($db);
		    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
		    {
		    	$page = 0;
		    	$offset = 0;
		    }
		}

		$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);

		$resql = $db->query($sql);
		if ($resql)
		{
		    $num = $db->num_rows($resql);
		    $i = 0;

		    $titre=$langs->trans("MembersList");
		    if ($status != '')
		    {
		        if ($status == '-1,1')								{ $titre=$langs->trans("MembersListQualified"); }
		        else if ($status == '-1')							{ $titre=$langs->trans("MembersListToValid"); }
		        else if ($status == '1' && ! $filter)				{ $titre=$langs->trans("MembersListValid"); }
		        else if ($status == '1' && $filter=='uptodate')		{ $titre=$langs->trans("MembersListUpToDate"); }
		        else if ($status == '1' && $filter=='outofdate')	{ $titre=$langs->trans("MembersListNotUpToDate"); }
		        else if ($status == '0')							{ $titre=$langs->trans("MembersListResiliated"); }
		    }
		    elseif ($action == 'search')
		    {
		        $titre=$langs->trans("MembersListQualified");
		    }

		    if ($type > 0)
		    {
				$membertype=new AdherentType($db);
		        $result=$membertype->fetch($type);
				$titre.=" (".$membertype->label.")";
		    }

		    $param="&rowid=".$object->id;
		    if (! empty($status))			$param.="&status=".$status;
		    if (! empty($search_lastname))	$param.="&search_lastname=".$search_lastname;
		    if (! empty($search_firstname))	$param.="&search_firstname=".$search_firstname;
		    if (! empty($search_login))		$param.="&search_login=".$search_login;
		    if (! empty($search_email))		$param.="&search_email=".$search_email;
		    if (! empty($filter))			$param.="&filter=".$filter;

		    if ($sall)
		    {
		        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
		    }

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input class="flat" type="hidden" name="rowid" value="'.$object->id.'" size="12"></td>';

			print '<br>';
            print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

			// Lignes des champs de filtre
			print '<tr class="liste_titre_filter">';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'" size="12"></td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'" size="7"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'" size="12"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td align="right" colspan="2" class="liste_titre">';
			print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		    print '&nbsp; ';
		    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
			print '</td>';

			print "</tr>\n";

			print '<tr class="liste_titre">';
		    print_liste_field_titre( $langs->trans("Name")." / ".$langs->trans("Company"),$_SERVER["PHP_SELF"],"d.lastname",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Login",$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Nature",$_SERVER["PHP_SELF"],"d.morphy",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("EMail",$_SERVER["PHP_SELF"],"d.email",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("EndSubscription",$_SERVER["PHP_SELF"],"d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
		    print_liste_field_titre("Action",$_SERVER["PHP_SELF"],"",$param,"",'width="60" align="center"',$sortfield,$sortorder);
		    print "</tr>\n";

		    while ($i < $num && $i < $conf->liste_limit)
		    {
		        $objp = $db->fetch_object($resql);

		        $datefin=$db->jdate($objp->datefin);

		        $adh=new Adherent($db);
		        $adh->lastname=$objp->lastname;
		        $adh->firstname=$objp->firstname;

		        // Lastname
		        print '<tr class="oddeven">';
		        if ($objp->societe != '')
		        {
		            print '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"),"user").' '.$adh->getFullName($langs,0,-1,20).' / '.dol_trunc($objp->societe,12).'</a></td>'."\n";
		        }
		        else
		        {
		            print '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"),"user").' '.$adh->getFullName($langs,0,-1,32).'</a></td>'."\n";
		        }

		        // Login
		        print "<td>".$objp->login."</td>\n";

		        // Type
		        /*print '<td class="nowrap">';
		        $membertypestatic->id=$objp->type_id;
		        $membertypestatic->label=$objp->type;
		        print $membertypestatic->getNomUrl(1,12);
		        print '</td>';
				*/

		        // Moral/Physique
		        print "<td>".$adh->getmorphylib($objp->morphy)."</td>\n";

		        // EMail
		        print "<td>".dol_print_email($objp->email,0,0,1)."</td>\n";

		        // Statut
		        print '<td class="nowrap">';
		        print $adh->LibStatut($objp->statut,$objp->subscription,$datefin,2);
		        print "</td>";

		        // Date end subscription
		        if ($datefin)
		        {
			        print '<td align="center" class="nowrap">';
		            if ($datefin < dol_now() && $objp->statut > 0)
		            {
		                print dol_print_date($datefin,'day')." ".img_warning($langs->trans("SubscriptionLate"));
		            }
		            else
		            {
		                print dol_print_date($datefin,'day');
		            }
		            print '</td>';
		        }
		        else
		        {
			        print '<td align="left" class="nowrap">';
			        if ($objp->subscription == 'yes')
			        {
		                print $langs->trans("SubscriptionNotReceived");
		                if ($objp->statut > 0) print " ".img_warning();
			        }
			        else
			        {
			            print '&nbsp;';
			        }
		            print '</td>';
		        }

		        // Actions
		        print '<td align="center">';
				if ($user->rights->adherent->creer)
				{
					print '<a href="card.php?rowid='.$objp->rowid.'&action=edit&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.img_edit().'</a>';
				}
				print '&nbsp;';
				if ($user->rights->adherent->supprimer)
				{
					print '<a href="card.php?rowid='.$objp->rowid.'&action=resign">'.img_picto($langs->trans("Resiliate"),'disable.png').'</a>';
		        }
				print "</td>";

		        print "</tr>\n";
		        $i++;
		    }

		    print "</table>\n";
            print '</div>';
            print '</form>';

			if ($num > $conf->liste_limit)
			{
			    print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'');
			}
		}
		else
		{
		    dol_print_error($db);
		}
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit')
	{
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		$head = member_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$object->id.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $form->selectyesno("subscription",$object->subscription,1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $form->selectyesno("vote",$object->vote,1);
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="comment" wrap="soft" class="centpercent" rows="3">'.$object->note.'</textarea></td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
		$doleditor->Create();
		print "</td></tr>";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook))
		{
		    print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';

		// Extra field
		if (empty($reshook))
		{
			print '<br><br><table class="border" width="100%">';
			foreach($extrafields->attribute_label as $key=>$label)
			{
				if (isset($_POST["options_" . $key])) {
					if (is_array($_POST["options_" . $key])) {
						// $_POST["options"] is an array but following code expects a comma separated string
						$value = implode(",", $_POST["options_" . $key]);
					} else {
						$value = $_POST["options_" . $key];
					}
				} else {
					$value = $adht->array_options["options_" . $key];
				}
				print '<tr><td width="30%">'.$label.'</td><td>';
				print $extrafields->showInputField($key,$value);
				print "</td></tr>\n";
			}
			print '</table><br><br>';
		}

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
