<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *		\brief      Member's type setup
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

$search_lastname	= GETPOST('search_lastname','alpha');
$search_login		= GETPOST('search_login','alpha');
$search_email		= GETPOST('search_email','alpha');
$type				= GETPOST('type','alpha');
$status				= GETPOST('status','alpha');

$sortfield	= GETPOST('sortfield','alpha');
$sortorder	= GETPOST('sortorder','alpha');
$page		= GETPOST('page','int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.lastname"; }

$label=GETPOST("libelle","alpha");
$cotisation=GETPOST("cotisation","int");
$vote=GETPOST("vote","int");
$comment=GETPOST("comment");
$mail_valid=GETPOST("mail_valid");

// Security check
$result=restrictedArea($user,'adherent',$rowid,'adherent_type');

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('adherent_type');

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_lastname="";
    $search_login="";
    $search_email="";
    $type="";
    $sall="";
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('membertypecard','globalcard'));

/*
 *	Actions
 */
if ($action == 'add' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$object = new AdherentType($db);

		$object->libelle     = trim($label);
		$object->cotisation  = trim($cotisation);
		$object->note        = trim($comment);
		$object->mail_valid  = trim($mail_valid);
		$object->vote        = trim($vote);

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		if ($object->libelle)
		{
			$id=$object->create($user);
			if ($id > 0)
			{
				header("Location: ".$_SERVER["PHP_SELF"]);
				exit;
			}
			else
			{
				$mesg=$object->error;
				$action = 'create';
			}
		}
		else
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$action = 'create';
		}
	}
}

if ($action == 'update' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$object = new AdherentType($db);
		$object->id          = $rowid;
		$object->libelle     = trim($label);
		$object->cotisation  = trim($cotisation);
		$object->note        = trim($comment);
		$object->mail_valid  = trim($mail_valid);
		$object->vote        = trim($vote);

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		$object->update($user);

		header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$_POST["rowid"]);
		exit;
	}
}

if ($action == 'delete' && $user->rights->adherent->configurer)
{
	$object = new AdherentType($db);
	$object->delete($rowid);
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);


// List of members type
if (! $rowid && $action != 'create' && $action != 'edit')
{

	print load_fiche_titre($langs->trans("MembersTypes"));

	dol_fiche_head('');

	$sql = "SELECT d.rowid, d.libelle, d.cotisation, d.vote";
	$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql.= " WHERE d.entity IN (".getEntity().")";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$i = 0;

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Ref").'</td>';
		print '<td>'.$langs->trans("Label").'</td>';
		print '<td align="center">'.$langs->trans("SubscriptionRequired").'</td>';
		print '<td align="center">'.$langs->trans("VoteAllowed").'</td>';
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		$var=True;
		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_escape_htmltag($objp->libelle).'</td>';
			print '<td align="center">'.yn($objp->cotisation).'</td>';
			print '<td align="center">'.yn($objp->vote).'</td>';
			print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

	dol_fiche_end();

	/*
	 * Hotbar
	 */
	print '<div class="tabsAction">';

	// New type
	if ($user->rights->adherent->configurer)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create">'.$langs->trans("NewType").'</a></div>';
	}

	print "</div>";

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

    dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';

	print '<input type="hidden" name="action" value="add">';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40"></td></tr>';

	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $form->selectyesno("cotisation",1,1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $form->selectyesno("vote",0,1);
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="comment" wrap="soft" cols="60" rows="3"></textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,90);
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
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
		$object->fetch_optionals($rowid,$extralabels);

		$head = member_type_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
		print '<td>';
		print $form->showrefnav($object, 'rowid', $linkback);
		print '</td></tr>';

		// Label
		print '<tr><td width="15%">'.$langs->trans("Label").'</td><td>'.dol_escape_htmltag($object->libelle).'</td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print yn($object->cotisation);
		print '</tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print yn($object->vote);
		print '</tr>';

		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->note)."</td></tr>";

		print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
		print nl2br($object->mail_valid)."</td></tr>";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			// View extrafields
			print $object->showOptionals($extrafields);
		}

		print '</table>';

		dol_fiche_end();


		/*
		 * Hotbar
		 */
		print '<div class="tabsAction">';

		// Edit
		if ($user->rights->adherent->configurer)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Add
		print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&typeid='.$object->id.'">'.$langs->trans("AddMember").'</a></div>';

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
		$sql.= " t.libelle as type, t.cotisation";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
		$sql.= " WHERE d.fk_adherent_type = t.rowid ";
		$sql.= " AND d.entity IN (".getEntity().")";
		$sql.= " AND t.rowid = ".$object->id;
		if ($sall)
		{
		    $sql.= " AND (d.firstname LIKE '%".$sall."%' OR d.lastname LIKE '%".$sall."%' OR d.societe LIKE '%".$sall."%'";
		    $sql.= " OR d.email LIKE '%".$sall."%' OR d.login LIKE '%".$sall."%' OR d.address LIKE '%".$sall."%'";
		    $sql.= " OR d.town LIKE '%".$sall."%' OR d.note_public LIKE '%".$sall."%' OR d.note_private LIKE '%".$sall."%')";
		}
		if ($status != '')
		{
		    $sql.= " AND d.statut IN (".$status.")";     // Peut valoir un nombre ou liste de nombre separes par virgules
		}
		if ($action == 'search')
		{
		  if (isset($_POST['search']) && $_POST['search'] != '')
		  {
		    $sql.= " AND (d.firstname LIKE '%".$_POST['search']."%' OR d.lastname LIKE '%".$_POST['search']."%')";
		  }
		}
		if (! empty($search_lastname))
		{
			$sql.= " AND (d.firstname LIKE '%".$search_lastname."%' OR d.lastname LIKE '%".$search_lastname."%')";
		}
		if (! empty($search_login))
		{
		    $sql.= " AND d.login LIKE '%".$search_login."%'";
		}
		if (! empty($search_email))
		{
		    $sql.= " AND d.email LIKE '%".$search_email."%'";
		}
		if ($filter == 'uptodate')
		{
		    $sql.=" AND datefin >= '".$db->idate($now)."'";
		}
		if ($filter == 'outofdate')
		{
		    $sql.=" AND datefin < '".$db->idate($now)."'";
		}
		// Count total nb of records
		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$resql = $db->query($sql);
		    if ($resql) $nbtotalofrecords = $db->num_rows($result);
		    else dol_print_error($db);
		}
		// Add order and limit
		$sql.= " ".$db->order($sortfield,$sortorder);
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
				$titre.=" (".$membertype->libelle.")";
		    }

		    $param="&rowid=".$rowid;
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

		    print '<br>';
            print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
		    print '<table class="noborder" width="100%">';

		    print '<tr class="liste_titre">';
		    print_liste_field_titre($langs->trans("Name")." / ".$langs->trans("Company"),$_SERVER["PHP_SELF"],"d.lastname",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Login"),$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Nature"),$_SERVER["PHP_SELF"],"d.morphy",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"d.email",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("EndSubscription"),$_SERVER["PHP_SELF"],"d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"",$param,"",'width="60" align="center"',$sortfield,$sortorder);
		    print "</tr>\n";

			// Lignes des champs de filtre
			print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input class="flat" type="hidden" name="rowid" value="'.$rowid.'" size="12"></td>';

			print '<tr class="liste_titre">';

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
			print '</form>';

		    $var=True;
		    while ($i < $num && $i < $conf->liste_limit)
		    {
		        $objp = $db->fetch_object($resql);

		        $datefin=$db->jdate($objp->datefin);

		        $adh=new Adherent($db);
		        $adh->lastname=$objp->lastname;
		        $adh->firstname=$objp->firstname;

		        // Lastname
		        $var=!$var;
		        print '<tr '.$bc[$var].'>';
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
		        $membertypestatic->libelle=$objp->type;
		        print $membertypestatic->getNomUrl(1,12);
		        print '</td>';
				*/

		        // Moral/Physique
		        print "<td>".$adh->getmorphylib($objp->morphy)."</td>\n";

		        // EMail
		        print "<td>".dol_print_email($objp->email,0,0,1)."</td>\n";

		        // Statut
		        print '<td class="nowrap">';
		        print $adh->LibStatut($objp->statut,$objp->cotisation,$datefin,2);
		        print "</td>";

		        // Date fin cotisation
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
			        if ($objp->cotisation == 'yes')
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
					print '<a href="card.php?rowid='.$objp->rowid.'&action=edit&return=list.php">'.img_edit().'</a>';
				}
				print '&nbsp;';
				if ($user->rights->adherent->supprimer)
				{
					print '<a href="card.php?rowid='.$objp->rowid.'&action=resign&return=list.php">'.img_picto($langs->trans("Resiliate"),'disable.png').'</a>';
		        }
				print "</td>";

		        print "</tr>\n";
		        $i++;
		    }

		    print "</table>\n";

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
		$object->id = $rowid;
		$object->fetch($rowid);
		$object->fetch_optionals($rowid,$extralabels);

		$head = member_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40" value="'.dol_escape_htmltag($object->libelle).'"></td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $form->selectyesno("cotisation",$object->cotisation,1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $form->selectyesno("vote",$object->vote,1);
		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="comment" wrap="soft" cols="90" rows="3">'.$object->note.'</textarea></td></tr>';

		print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,90);
		$doleditor->Create();
		print "</td></tr>";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook

		print '</table>';

		// Extra field
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print '<br><br><table class="border" width="100%">';
			foreach($extrafields->attribute_label as $key=>$label)
			{
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:(isset($object->array_options['options_'.$key])?$object->array_options['options_'.$key]:''));
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
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}


llxFooter();

$db->close();
