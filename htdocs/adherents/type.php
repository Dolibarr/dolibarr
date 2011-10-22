<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/adherents/type.php
 *      \ingroup    member
 *		\brief      Member's type setup
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

$langs->load("members");

$rowid=GETPOST("rowid");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.nom"; }

// Security check
if (! $user->rights->adherent->lire) accessforbidden();

if ($_REQUEST["button_removefilter"])
{
    $_GET["search_nom"]="";
    $_REQUEST["search_nom"]="";
    $_GET["search_prenom"]="";
    $_REQUEST["search_prenom"]="";
    $_GET["type"]="";
    $_REQUEST["type"]="";
    $_GET["search_email"]="";
    $_REQUEST["search_email"]="";
    $sall="";
}



/*
 *	Actions
 */
if ($user->rights->adherent->configurer && $_POST["action"] == 'add')
{
	if ($_POST["button"] != $langs->trans("Cancel"))
	{
		$adht = new AdherentType($db);

		$adht->libelle     = trim($_POST["libelle"]);
		$adht->cotisation  = trim($_POST["cotisation"]);
		$adht->note        = trim($_POST["comment"]);
		$adht->mail_valid  = trim($_POST["mail_valid"]);
		$adht->vote        = trim($_POST["vote"]);

		if ($adht->libelle)
		{
			$id=$adht->create($user->id);
			if ($id > 0)
			{
				Header("Location: type.php");
				exit;
			}
			else
			{
				$mesg=$adht->error;
				$_GET["action"] = 'create';
			}
		}
		else
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$_GET["action"] = 'create';
		}
	}
}

if ($user->rights->adherent->configurer && $_POST["action"] == 'update')
{
	if ($_POST["button"] != $langs->trans("Cancel"))
	{
		$adht = new AdherentType($db);
		$adht->id          = $_POST["rowid"];
		$adht->libelle     = trim($_POST["libelle"]);
		$adht->cotisation  = trim($_POST["cotisation"]);
		$adht->note        = trim($_POST["comment"]);
		$adht->mail_valid  = trim($_POST["mail_valid"]);
		$adht->vote        = trim($_POST["vote"]);

		$adht->update($user->id);

		Header("Location: type.php?rowid=".$_POST["rowid"]);
		exit;
	}
}

if ($user->rights->adherent->configurer && $_GET["action"] == 'delete')
{
	$adht = new AdherentType($db);
	$adht->delete($rowid);
	Header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($user->rights->adherent->configurer && $_GET["action"] == 'commentaire')
{
	$don = new Don($db);
	$don->fetch($rowid);
	$don->update_note($_POST["commentaire"]);
}


/*
 * View
 */

llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);


// Liste of members type

if (! $rowid && $_GET["action"] != 'create' && $_GET["action"] != 'edit')
{

	print_fiche_titre($langs->trans("MembersTypes"));


	$sql = "SELECT d.rowid, d.libelle, d.cotisation, d.vote";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";

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
			print '<td>'.$objp->libelle.'</td>';
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


	/*
	 * Barre d'actions
	 *
	 */
	print '<div class="tabsAction">';

	// New type
	if ($user->rights->adherent->configurer)
	{
		print "<a class=\"butAction\" href=\"type.php?action=create\">".$langs->trans("NewType")."</a>";
	}

	print "</div>";

}


/* ************************************************************************** */
/*                                                                            */
/* Creation d'un type adherent                                                */
/*                                                                            */
/* ************************************************************************** */
if ($_GET["action"] == 'create')
{
	$htmls = new Form($db);

	print_fiche_titre($langs->trans("NewMemberType"));

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print "<form action=\"type.php\" method=\"post\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40"></td></tr>';

	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $htmls->selectyesno("cotisation",1,1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $htmls->selectyesno("vote",0,1);
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\"></textarea></td></tr>";

	print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	$doleditor=new DolEditor('mail_valid',$adht->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,90);
	$doleditor->Create();
	print '</td></tr>';

	print "</table>\n";

	print '<br>';
	print '<center><input type="submit" name="button" class="button" value="'.$langs->trans("Add").'"> &nbsp; &nbsp; ';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></center>';

	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
	if ($_GET["action"] != 'edit')
	{
		$adht = new AdherentType($db);
		$adht->id = $rowid;
		$adht->fetch($rowid);


		$h=0;

		$head[$h][0] = $_SERVER["PHP_SELF"].'?rowid='.$adht->id;
		$head[$h][1] = $langs->trans("Card");
		$head[$h][2] = 'card';
		$h++;

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');


		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
		print '<td>';
		print $form->showrefnav($adht,'rowid');
		print '</td></tr>';

		// Label
		print '<tr><td width="15%">'.$langs->trans("Label").'</td><td>'.$adht->libelle.'</td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print yn($adht->cotisation);
		print '</tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print yn($adht->vote);
		print '</tr>';

		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		print nl2br($adht->note)."</td></tr>";

		print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
		print nl2br($adht->mail_valid)."</td></tr>";

		print '</table>';

		print '</div>';

		/*
		 * Barre d'actions
		 *
		 */
		print '<div class="tabsAction">';

		// Edit
		if ($user->rights->adherent->configurer)
		{
			print "<a class=\"butAction\" href=\"type.php?action=edit&amp;rowid=".$adht->id."\">".$langs->trans("Modify")."</a>";
		}

		// Add
		print "<a class=\"butAction\" href=\"fiche.php?action=create&typeid=".$adht->id."\">".$langs->trans("AddMember")."</a>";

		// Delete
		if ($user->rights->adherent->configurer)
		{
			print "<a class=\"butActionDelete\" href=\"type.php?action=delete&rowid=".$adht->id."\">".$langs->trans("DeleteType")."</a>";
		}

		print "</div>";


		// Show list of members (nearly same code than in page liste.php)

		$membertypestatic=new AdherentType($db);

		$sql = "SELECT d.rowid, d.login, d.prenom, d.nom, d.societe, ";
		$sql.= " d.datefin,";
		$sql.= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
		$sql.= " t.libelle as type, t.cotisation";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
		$sql.= " WHERE d.fk_adherent_type = t.rowid ";
		$sql.= " AND d.entity = ".$conf->entity;
		if ($sall)
		{
		    $sql.=" AND (d.prenom like '%".$sall."%' OR d.nom like '%".$sall."%' OR d.societe like '%".$sall."%'";
		    $sql.=" OR d.email like '%".$sall."%' OR d.login like '%".$sall."%' OR d.adresse like '%".$sall."%'";
		    $sql.=" OR d.ville like '%".$sall."%' OR d.note like '%".$sall."%')";
		}
		//if ($_REQUEST["type"] > 0)
		//{
		    //$sql.=" AND t.rowid=".$_REQUEST["type"];
		    $sql.=" AND t.rowid=".$adht->id;
		//}
		if (isset($_GET["statut"]))
		{
		    $sql.=" AND d.statut in ($statut)";     // Peut valoir un nombre ou liste de nombre separes par virgules
		}
		if ( $_POST["action"] == 'search')
		{
		  if (isset($_POST['search']) && $_POST['search'] != ''){
		    $sql.= " AND (d.prenom LIKE '%".$_POST['search']."%' OR d.nom LIKE '%".$_POST['search']."%')";
		  }
		}
		if ($_GET["search_nom"])
		{
		    $sql.= " AND (d.prenom LIKE '%".$_GET["search_nom"]."%' OR d.nom LIKE '%".$_GET["search_nom"]."%')";
		}
		if ($_GET["search_login"])
		{
		    $sql.= " AND d.login LIKE '%".$_GET["search_login"]."%'";
		}
		if ($_GET["search_email"])
		{
		    $sql.= " AND (d.email LIKE '%".$_GET["search_email"]."%')";
		}
		if ($filter == 'uptodate')
		{
		    $sql.=" AND datefin >= ".$db->idate(mktime());
		}
		if ($filter == 'outofdate')
		{
		    $sql.=" AND datefin < ".$db->idate(mktime());
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
		    if (isset($_GET["statut"]))
		    {
		        if ($statut == '-1,1') { $titre=$langs->trans("MembersListQualified"); }
		        if ($statut == '-1')   { $titre=$langs->trans("MembersListToValid"); }
		        if ($statut == '1' && ! $filter)    		{ $titre=$langs->trans("MembersListValid"); }
		        if ($statut == '1' && $filter=='uptodate')  { $titre=$langs->trans("MembersListUpToDate"); }
		        if ($statut == '1' && $filter=='outofdate')	{ $titre=$langs->trans("MembersListNotUpToDate"); }
		        if ($statut == '0')    { $titre=$langs->trans("MembersListResiliated"); }
		    }
		    elseif ($_POST["action"] == 'search') {
		        $titre=$langs->trans("MembersListQualified");
		    }

		    if ($_REQUEST["type"] > 0)
		    {
				$membertype=new AdherentType($db);
		        $result=$membertype->fetch($_REQUEST["type"]);
				$titre.=" (".$membertype->libelle.")";
		    }

		    $param="&rowid=".$rowid;
		    if (isset($_GET["statut"]))       $param.="&statut=".$_GET["statut"];
		    if (isset($_GET["search_nom"]))   $param.="&search_nom=".$_GET["search_nom"];
		    if (isset($_GET["search_login"])) $param.="&search_login=".$_GET["search_login"];
		    if (isset($_GET["search_email"])) $param.="&search_email=".$_GET["search_email"];
		    if (isset($_GET["filter"]))       $param.="&filter=".$_GET["filter"];

		    if ($sall)
		    {
		        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
		    }

		    print '<br>';
            print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
		    print "<table class=\"noborder\" width=\"100%\">";

		    print '<tr class="liste_titre">';
		    print_liste_field_titre($langs->trans("Name")." / ".$langs->trans("Company"),$_SERVER["PHP_SELF"],"d.nom",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Login"),$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"d.morphy",$param,"","",$sortfield,$sortorder);
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
			print '<input class="flat" type="text" name="search_nom" value="'.$_REQUEST["search_nom"].'" size="12"></td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_login" value="'.$_REQUEST["search_login"].'" size="7"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_email" value="'.$_REQUEST["search_email"].'" size="12"></td>';

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

		        // Nom
		        $var=!$var;
		        print "<tr $bc[$var]>";
		        if ($objp->societe != '')
		        {
		            print "<td><a href=\"fiche.php?rowid=$objp->rowid\">".img_object($langs->trans("ShowMember"),"user").' '.$objp->prenom." ".dol_trunc($objp->nom,12)." / ".dol_trunc($objp->societe,12)."</a></td>\n";
		        }
		        else
		        {
		            print "<td><a href=\"fiche.php?rowid=$objp->rowid\">".img_object($langs->trans("ShowMember"),"user").' '.$objp->prenom." ".dol_trunc($objp->nom)."</a></td>\n";
		        }

		        // Login
		        print "<td>".$objp->login."</td>\n";

		        // Type
		        /*print '<td nowrap="nowrap">';
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
		        print '<td nowrap="nowrap">';
		        print $adh->LibStatut($objp->statut,$objp->cotisation,$datefin,2);
		        print "</td>";

		        // Date fin cotisation
		        if ($datefin)
		        {
			        print '<td align="center" nowrap="nowrap">';
		            if ($datefin < time() && $objp->statut > 0)
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
			        print '<td align="left" nowrap="nowrap">';
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
					print "<a href=\"fiche.php?rowid=$objp->rowid&action=edit&return=liste.php\">".img_edit()."</a>";
				}
				print '&nbsp;';
				if ($user->rights->adherent->supprimer)
				{
					print "<a href=\"fiche.php?rowid=$objp->rowid&action=resign&return=liste.php\">".img_picto($langs->trans("Resiliate"),'disable.png')."</a>";
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

	if ($_GET["action"] == 'edit')
	{
		$htmls = new Form($db);

		$adht = new AdherentType($db);
		$adht->id = $rowid;
		$adht->fetch($rowid);


		$h=0;

		$head[$h][0] = $_SERVER["PHP_SELF"].'?rowid='.$adht->id;
		$head[$h][1] = $langs->trans("Card");
		$head[$h][2] = 'card';
		$h++;

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');


		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';
		print '<table class="border" width="100%">';

		print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$adht->id.'</td></tr>';

		print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40" value="'.$adht->libelle.'"></td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $htmls->selectyesno("cotisation",$adht->cotisation,1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $htmls->selectyesno("vote",$adht->vote,1);
		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		print "<textarea name=\"comment\" wrap=\"soft\" cols=\"90\" rows=\"3\">".$adht->note."</textarea></td></tr>";

		print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('mail_valid',$adht->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,90);
		$doleditor->Create();
		print "</td></tr>";

		print '</table>';
		
		print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; &nbsp;';
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></center>';

		print "</form>";
	}
}

$db->close();

llxFooter();
?>
