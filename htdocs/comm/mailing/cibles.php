<?PHP
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@uers.sourceforge.net>
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
 *       \file       htdocs/comm/mailing/cibles.php
 *       \ingroup    mailing
 *       \brief      Page des cibles de mailing
 *       \version    $Id$
 */

require("./pre.inc.php");

$langs->load("mails");

if (! $user->rights->mailing->lire || $user->societe_id > 0)
accessforbidden();


$dirmod=DOL_DOCUMENT_ROOT."/includes/modules/mailings";


$mesg = '';


$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="email";

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_prenom=isset($_GET["search_prenom"])?$_GET["search_prenom"]:$_POST["search_prenom"];
$search_email=isset($_GET["search_email"])?$_GET["search_email"]:$_POST["search_email"];


/*
 * Actions
 */
if ($_GET["action"] == 'add')
{
	$modulename=$_GET["module"];
	$result=0;

	$var=true;
	foreach ($conf->dol_document_root as $dirmod)
	{
		$dir=$dirmod."/includes/modules/mailings/";

		if (is_dir($dir))
		{
			// Chargement de la classe
			$file = $dir."/".$modulename.".modules.php";
			$classname = "mailing_".$modulename;

			if (file_exists($file))
			{
				require_once($file);

				$filtersarray=array();
				if (isset($_POST["filter"])) $filtersarray[0]=$_POST["filter"];

				$obj = new $classname($db);
				$result=$obj->add_to_target($_GET["rowid"],$filtersarray);
			}
		}
	}

	if ($result > 0)
	{
		Header("Location: cibles.php?id=".$_GET["rowid"]);
		exit;
	}
	if ($result == 0)
	{
		$mesg='<div class="warning">'.$langs->trans("WarningNoEMailsAdded").'</div>';
	}
	if ($result < 0)
	{
		$mesg='<div class="error">'.$obj->error.'</div>';
	}
	$_REQUEST["id"]=$_GET["rowid"];
}

if ($_GET["action"] == 'clear')
{
	// Chargement de la classe
	$file = $dirmod."/modules_mailings.php";
	$classname = "MailingTargets";
	require_once($file);

	$obj = new $classname($db);
	$obj->clear_target($_GET["rowid"]);

	Header("Location: cibles.php?id=".$_GET["rowid"]);
	exit;
}

if ($_GET["action"] == 'delete')
{
	// Ici, rowid indique le destinataire et id le mailing
	$sql="DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles where rowid=".$_GET["rowid"];
	$resql=$db->query($sql);
	if ($resql)
	{
		$file = $dirmod."/modules_mailings.php";
		$classname = "MailingTargets";
		require_once($file);

		$obj = new $classname($db);
		$obj->update_nb($_REQUEST["id"]);
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_POST["button_removefilter"])
{
	$search_nom='';
	$search_prenom='';
	$search_email='';
}



/*
 * Liste des destinataires
 */

llxHeader("","",$langs->trans("MailCard"));

$mil = new Mailing($db);

$html = new Form($db);
if ($mil->fetch($_REQUEST["id"]) >= 0)
{

	$h=0;
	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
	$head[$h][1] = $langs->trans("MailCard");
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
	$head[$h][1] = $langs->trans("MailRecipients");
	$hselected = $h;
	$h++;

	/*
	 $head[$h][0] = DOL_URL_ROOT."/comm/mailing/info.php?id=".$mil->id;
	 $head[$h][1] = $langs->trans("MailHistory");
	 $h++;
	 */
	dol_fiche_head($head, $hselected, $langs->trans("Mailing"));


	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $html->showrefnav($mil,'id');
	print '</td></tr>';
	print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
	print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.htmlentities($mil->email_from).'</td></tr>';
	print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->getLibStatut(4).'</td></tr>';
	print '<tr><td width="25%">'.$langs->trans("TotalNbOfDistinctRecipients").'</td><td colspan="3">'.($mil->nbemail?$mil->nbemail:'0').'</td></tr>';
	print '</table>';

	print "</div>";

	if ($mesg) print "$mesg<br>\n";

	$var=!$var;

	// Affiche les listes de selection
	if ($mil->statut == 0)
	{
		print_titre($langs->trans("ToAddRecipientsChooseHere"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RecipientSelectionModules");
		if ($user->admin) print ' '.info_admin($langs->trans("YouCanAddYourOwnPredefindedListHere"),1);
		print '</td>';
		print '<td align="center">'.$langs->trans("NbOfUniqueEMails").'</td>';
		print '<td align="left">'.$langs->trans("Filter").'</td>';
		print '<td align="center" width="120">&nbsp;</td>';
		print "</tr>\n";

		clearstatcache();

		$var=true;
		foreach ($conf->dol_document_root as $dirroot)
		{
			$dir=$dirroot."/includes/modules/mailings/";

			if (is_dir($dir))
			{
				$handle=opendir($dir);
				if ($handle)
				{
					while (($file = readdir($handle))!==false)
					{
						if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
						{
							if (eregi("(.*)\.modules\.php$",$file,$reg))
							{
								$modulename=$reg[1];
								if ($modulename == 'example') continue;

								// Chargement de la classe
								$file = $dir.$modulename.".modules.php";
								$classname = "mailing_".$modulename;
								require_once($file);

								$obj = new $classname($db);

								$qualified=1;
								foreach ($obj->require_module as $key)
								{
									if (! $conf->$key->enabled || (! $user->admin && $obj->require_admin))
									{
										$qualified=0;
										//print "Les pr�requis d'activation du module mailing ne sont pas respect�s. Il ne sera pas actif";
										break;
									}
								}

								// Si le module mailing est qualifie
								if ($qualified)
								{
									$var = !$var;
									print '<tr '.$bc[$var].'>';

									if ($mil->statut == 0) print '<form name="'.$modulename.'" action="cibles.php?action=add&rowid='.$mil->id.'&module='.$modulename.'" method="POST" enctype="multipart/form-data">';

									print '<td>';
									if (! $obj->picto) $obj->picto='generic';
									print img_object($langs->trans("Module").': '.get_class($obj),$obj->picto).' '.$obj->getDesc();
									print '</td>';

									/*
									 print '<td width=\"100\">';
									 print $modulename;
									 print "</td>";
									 */
									$nbofrecipient=$obj->getNbOfRecipients();
									print '<td align="center">';
									if ($nbofrecipient >= 0)
									{
										print $nbofrecipient;
									}
									else
									{
										print $langs->trans("Error").' '.img_error($obj->error);
									}
									print '</td>';

									print '<td align="left">';
									$filter=$obj->formFilter();
									if ($filter) print $filter;
									else print $langs->trans("None");
									print '</td>';

									print '<td align="right">';
									if ($mil->statut == 0)
									{
										print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
									}
									else
									{
										//print $langs->trans("MailNoChangePossible");
										print "&nbsp;";
									}
									print '</td>';

									if ($mil->statut == 0) print '</form>';

									print "</tr>\n";
								}
							}
						}
					}
					closedir($handle);
				}
			}
		}	// End foreach dir

		print '</table>';
		print '<br>';

		print '<form action="cibles.php?action=clear&rowid='.$mil->id.'" method="POST">';
		print_titre($langs->trans("ToClearAllRecipientsClickHere"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("TargetsReset").'"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br>';
	}



	// List of selected targets
	print "\n<!-- Liste destinataires selectionnes -->\n";
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="id" value="'.$mil->id.'">';

	$sql  = "SELECT mc.rowid, mc.nom, mc.prenom, mc.email, mc.other, mc.statut, mc.date_envoi, mc.url";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing=".$mil->id;
	if ($search_nom)    $sql.= " AND mc.nom    like '%".addslashes($search_nom)."%'";
	if ($search_prenom) $sql.= " AND mc.prenom like '%".addslashes($search_prenom)."%'";
	if ($search_email)  $sql.= " AND mc.email  like '%".addslashes($search_email)."%'";
	if ($sortfield) { $sql .= " ORDER BY $sortfield $sortorder"; }
	$sql .= $db->plimit($conf->liste_limit+1, $offset);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$parm = "&amp;id=".$mil->id;
		if ($search_nom)    $parm.= "&amp;search_nom=".urlencode($search_nom);
		if ($search_prenom) $parm.= "&amp;search_prenom=".urlencode($search_prenom);
		if ($search_email)  $parm.= "&amp;search_email=".urlencode($search_email);

		print_barre_liste($langs->trans("MailSelectedRecipients"),$page,$_SERVER["PHP_SELF"],$parm,$sortfield,$sortorder,"",$num,$mil->nbemail,'');

		if ($page)			$parm.= "&amp;page=".$page;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"mc.email",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"mc.nom",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"mc.prenom",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("OtherInformations"),$_SERVER["PHP_SELF"],"",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Source"),$_SERVER["PHP_SELF"],"",$parm,"",'align="center"',$sortfield,$sortorder);

		// Date
		if ($mil->statut < 2)
		{
			print '<td>&nbsp;</td>';
		}
		else
		{
			print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"mc.date_envoi",$parm,'','align="center"',$sortfield,$sortorder);
		}

		// Statut
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"mc.statut",$parm,'','align="right"',$sortfield,$sortorder);

		print '</tr>';

		// Ligne des champs de filtres
		print '<tr class="liste_titre">';
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_email" size="14" value="'.$search_email.'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_nom" size="12" value="'.$search_nom.'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_prenom" size="10" value="'.$search_prenom.'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		// Url
		print '<td class="liste_titre" align="right" colspan="3">';
		print '<input type="image" value="button_search" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
		print '&nbsp; <input type="image" value="button_removefilter" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" alt="'.$langs->trans("RemoveFilter").'">';
		print '</td>';
		print '</tr>';

		$var = true;
		$i = 0;

		if ($num)
		{
			while ($i < min($num,$conf->liste_limit))
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;

				print "<tr $bc[$var]>";
				print '<td>'.$obj->email.'</td>';
				print '<td>'.$obj->nom.'</td>';
				print '<td>'.$obj->prenom.'</td>';
				print '<td>'.$obj->other.'</td>';
				print '<td align="center">'.$obj->url.'</td>';

				// Statut pour l'email destinataire (Attentioon != statut du mailing)
				if ($obj->statut == 0)
				{
					print '<td align="center">&nbsp;</td>';
					print '<td align="right" nowrap="nowrap">'.$langs->trans("MailingStatusNotSent").' <a href="cibles.php?action=delete&rowid='.$obj->rowid.$parm.'">'.img_delete($langs->trans("RemoveRecipient")).'</td>';
				}
				else
				{
					print '<td align="center">'.$obj->date_envoi.'</td>';
					print '<td align="right" nowrap="nowrap">';
					if ($obj->statut==-1) print $langs->trans("MailingStatusError").' '.img_error();
					if ($obj->statut==1) print $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
					print '</td>';
				}
				print '</tr>';

				$i++;
			}
		}
		else
		{
			print '<tr '.$bc[false].'><td colspan="7">'.$langs->trans("NoTargetYet").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</form>';
	print "\n<!-- Fin liste destinataires selectionnes -->\n";

}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
