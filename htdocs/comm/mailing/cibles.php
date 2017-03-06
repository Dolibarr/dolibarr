<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@uers.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Florian Henry        <florian.henry@open-concept.pro>
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
 *       \file       htdocs/comm/mailing/cibles.php
 *       \ingroup    mailing
 *       \brief      Page to define emailing targets
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("mails");

// Security check
if (! $user->rights->mailing->lire || $user->societe_id > 0) accessforbidden();


// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="email";
if (! $sortorder) $sortorder="ASC";

$id=GETPOST('id','int');
$rowid=GETPOST('rowid','int');
$action=GETPOST("action");
$search_lastname=GETPOST("search_lastname");
$search_firstname=GETPOST("search_firstname");
$search_email=GETPOST("search_email");
$search_dest_status=GETPOST('search_dest_status');

// Search modules dirs
$modulesdir = dolGetModulesDirs('/mailings');

$object = new Mailing($db);



/*
 * Actions
 */

if ($action == 'add')
{
	$module=GETPOST("module");
	$result=-1;

	$var=true;

	foreach ($modulesdir as $dir)
	{
	    // Load modules attributes in arrays (name, numero, orders) from dir directory
	    //print $dir."\n<br>";
	    dol_syslog("Scan directory ".$dir." for modules");

	    // Loading Class
	    $file = $dir."/".$module.".modules.php";
	    $classname = "mailing_".$module;

		if (file_exists($file))
		{
			require_once $file;

			// We fill $filtersarray. Using this variable is now deprecated.
			// Kept for backward compatibility.
			$filtersarray=array();
			if (isset($_POST["filter"])) $filtersarray[0]=$_POST["filter"];

			// Add targets into database
			$obj = new $classname($db);
			dol_syslog("Call add_to_target on class ".$classname);
			$result=$obj->add_to_target($id,$filtersarray);
		}
	}
	if ($result > 0)
	{
		setEventMessages($langs->trans("XTargetsAdded",$result), null, 'mesgs');

		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	if ($result == 0)
	{
		setEventMessages($langs->trans("WarningNoEMailsAdded"), null, 'warnings');
	}
	if ($result < 0)
	{
		setEventMessages($langs->trans("Error").($obj->error?' '.$obj->error:''), null, 'errors');
	}
}

if (GETPOST('clearlist'))
{
	// Loading Class
	$obj = new MailingTargets($db);
	$obj->clear_target($id);
	/* Avoid this to allow reposition
	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit;
	*/
}

if ($action == 'delete')
{
	// Ici, rowid indique le destinataire et id le mailing
	$sql="DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$rowid;
	$resql=$db->query($sql);
	if ($resql)
	{
		if (!empty($id))
		{
			$obj = new MailingTargets($db);
			$obj->update_nb($id);

			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			header("Location: list.php");
			exit;
		}
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_POST["button_removefilter"])
{
	$search_lastname='';
	$search_firstname='';
	$search_email='';
}



/*
 * View
 */
llxHeader('',$langs->trans("Mailing"),'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);
$formmailing = new FormMailing($db);

if ($object->fetch($id) >= 0)
{
	$head = emailing_prepare_head($object);

	dol_fiche_head($head, 'targets', $langs->trans("Mailing"), 0, 'email');

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php">'.$langs->trans("BackToList").'</a>';

	$morehtmlright='';
	if ($object->statut == 2) $morehtmlright.=' ('.$object->countNbOfTargets('alreadysent').'/'.$object->nbemail.') ';
	
	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', $morehtmlright);

	
	print '<div class="underbanner clearboth"></div>';
	
	print '<table class="border" width="100%">';
/*
	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id', $linkback);
	print '</td></tr>';
*/
	print '<tr><td class="titlefield">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$object->titre.'</td></tr>';

	print '<tr><td>'.$langs->trans("MailFrom").'</td><td colspan="3">'.dol_print_email($object->email_from,0,0,0,0,1).'</td></tr>';

	// Errors to
	print '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td colspan="3">'.dol_print_email($object->email_errorsto,0,0,0,0,1);
	print '</td></tr>';

	// Status
/*	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4);
	if ($object->statut == 2) print ' ('.$object->countNbOfTargets('alreadysent').'/'.$object->nbemail.')';
	print '</td></tr>';
*/
	// Nb of distinct emails
	print '<tr><td>';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail?$object->nbemail:'0');
	if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && ($conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail) && ($object->statut == 1 || $object->statut == 2))
	{
		$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
		print $form->textwithpicto($nbemail,$text,1,'warning');
	}
	else
	{
		print $nbemail;
	}
	print '</td></tr>';

	print '</table>';

	print "</div>";

	$var=!$var;

	$allowaddtarget=($object->statut == 0);

	// Show email selectors
	if ($allowaddtarget && $user->rights->mailing->creer)
	{
		print load_fiche_titre($langs->trans("ToAddRecipientsChooseHere"), ($user->admin?info_admin($langs->trans("YouCanAddYourOwnPredefindedListHere"),1):''), 'title_generic');

		//print '<table class="noborder" width="100%">';
		print '<div class="tagtable centpercent liste_titre_bydiv" id="tablelines">';
		
		//print '<tr class="liste_titre">';
		print '<div class="tagtr liste_titre">';
		//print '<td class="liste_titre">'.$langs->trans("RecipientSelectionModules").'</td>';
		print '<div class="tagtd">'.$langs->trans("RecipientSelectionModules").'</div>';
		//print '<td class="liste_titre" align="center">'.$langs->trans("NbOfUniqueEMails").'</td>';
		print '<div class="tagtd" align="center">'.$langs->trans("NbOfUniqueEMails").'</div>';
		//print '<td class="liste_titre" align="left">'.$langs->trans("Filter").'</td>';
		print '<div class="tagtd" align="left">'.$langs->trans("Filter").'</div>';
		//print '<td class="liste_titre" align="center">&nbsp;</td>';
		print '<div class="tagtd">&nbsp;</div>';
		//print "</tr>\n";
		print '</div>';
		
		clearstatcache();

		$var=true;

		foreach ($modulesdir as $dir)
		{
		    $modulenames=array();

		    // Load modules attributes in arrays (name, numero, orders) from dir directory
		    //print $dir."\n<br>";
		    dol_syslog("Scan directory ".$dir." for modules");
		    $handle=@opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
					{
						if (preg_match("/(.*)\.modules\.php$/i",$file,$reg))
						{
							if ($reg[1] == 'example') continue;
							$modulenames[]=$reg[1];
						}
					}
				}
				closedir($handle);
			}

			// Sort $modulenames
			sort($modulenames);

			// Loop on each submodule
            foreach($modulenames as $modulename)
            {
				// Loading Class
				$file = $dir.$modulename.".modules.php";
				$classname = "mailing_".$modulename;
				require_once $file;

				$obj = new $classname($db);

				// Check dependencies
				$qualified=(isset($obj->enabled)?$obj->enabled:1);
				foreach ($obj->require_module as $key)
				{
					if (! $conf->$key->enabled || (! $user->admin && $obj->require_admin))
					{
						$qualified=0;
						//print "Les prerequis d'activation du module mailing ne sont pas respectes. Il ne sera pas actif";
						break;
					}
				}

				// Si le module mailing est qualifie
				if ($qualified)
				{
					$var = !$var;
					//print '<tr '.$bc[$var].'>';
//					print '<div '.$bctag[$var].'>';

					if ($allowaddtarget)
					{
						print '<form '.$bctag[$var].' name="'.$modulename.'" action="'.$_SERVER['PHP_SELF'].'?action=add&id='.$object->id.'&module='.$modulename.'" method="POST" enctype="multipart/form-data">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					}
					else
					{
					    print '<div '.$bctag[$var].'>';
					}

					//print '<td>';
					print '<div class="tagtd">';
					if (empty($obj->picto)) $obj->picto='generic';
					print img_object($langs->trans("Module").': '.get_class($obj),$obj->picto);
					print ' ';
					print $obj->getDesc();
					//print '</td>';
					print '</div>';
						
					try {
						$nbofrecipient=$obj->getNbOfRecipients('');
					}
					catch(Exception $e)
					{
						dol_syslog($e->getMessage(), LOG_ERR);
					}

					//print '<td align="center">';
					print '<div class="tagtd center">';
					if ($nbofrecipient >= 0)
					{
						print $nbofrecipient;
					}
					else
					{
						print $langs->trans("Error").' '.img_error($obj->error);
					}
					//print '</td>';
					print '</div>';
						
					//print '<td align="left">';
					print '<div class="tagtd" align="left">';
					if ($allowaddtarget)
					{
    					try {
    						$filter=$obj->formFilter();
    					}
    					catch(Exception $e)
    					{
    						dol_syslog($e->getMessage(), LOG_ERR);
    					}
    					if ($filter) print $filter;
    					else print $langs->trans("None");
					}
					//print '</td>';
					print '</div>';
						
					//print '<td align="right">';
					print '<div class="tagtd" align="right">';
					if ($allowaddtarget)
					{
						print '<input type="submit" class="button" name="button_'.$modulename.'" value="'.$langs->trans("Add").'">';
					}
					else
					{
					    print '<input type="submit" class="button disabled" disabled="disabled" name="button_'.$modulename.'" value="'.$langs->trans("Add").'">';
						//print $langs->trans("MailNoChangePossible");
						print "&nbsp;";
					}
					//print '</td>';
					print '</div>';
						
					if ($allowaddtarget) print '</form>';
					else print '</div>';
						
					//print "</tr>\n";
//					print '</div>'."\n";
				}
			}
		}	// End foreach dir

		//print '</table>';
		print '</div>';
		
		print '<br><br>';
	}

	// List of selected targets
	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut, mc.date_envoi, mc.source_url, mc.source_id, mc.source_type, mc.error_text";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing=".$object->id;
	if ($search_lastname)    $sql.= " AND mc.lastname    LIKE '%".$db->escape($search_lastname)."%'";
	if ($search_firstname) $sql.= " AND mc.firstname LIKE '%".$db->escape($search_firstname)."%'";
	if ($search_email)  $sql.= " AND mc.email  LIKE '%".$db->escape($search_email)."%'";
	if (!empty($search_dest_status)) $sql.= " AND mc.statut=".$db->escape($search_dest_status)." ";
	$sql .= $db->order($sortfield,$sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
	    $result = $db->query($sql);
	    $nbtotalofrecords = $db->num_rows($result);
	}
	//$nbtotalofrecords=$object->nbemail;     // nbemail is a denormalized field storing nb of targets
	$sql .= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if ($resql)
	{
	    
		$num = $db->num_rows($resql);

		$param = "&amp;id=".$object->id;
		if ($search_lastname)  $param.= "&amp;search_lastname=".urlencode($search_lastname);
		if ($search_firstname) $param.= "&amp;search_firstname=".urlencode($search_firstname);
		if ($search_email)     $param.= "&amp;search_email=".urlencode($search_email);

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';

		$cleartext='';
		if ($allowaddtarget) {
		    $cleartext=$langs->trans("ToClearAllRecipientsClickHere").' '.'<a href="'.$_SERVER["PHP_SELF"].'?clearlist=1&id='.$object->id.'" class="button reposition">'.$langs->trans("TargetsReset").'</a>';
		}
		print_barre_liste($langs->trans("MailSelectedRecipients"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,$cleartext,$num,$nbtotalofrecords,'title_generic',0,'','',$limit);
		
		print '</form>';

		print "\n<!-- Liste destinataires selectionnes -->\n";
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="limit" value="'.$limit.'">';
		

		if ($page)			$param.= "&amp;page=".$page;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"mc.email",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"mc.lastname",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"mc.firstname",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("OtherInformations"),$_SERVER["PHP_SELF"],"",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Source"),$_SERVER["PHP_SELF"],"",$param,"",'align="center"',$sortfield,$sortorder);
		// Date sending
		if ($object->statut < 2)
		{
			print_liste_field_titre('');
		}
		else
		{
			print_liste_field_titre($langs->trans("DateSending"),$_SERVER["PHP_SELF"],"mc.date_envoi",$param,'','align="center"',$sortfield,$sortorder);
		}
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"mc.statut",$param,'','align="right"',$sortfield,$sortorder);
		print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
		print '</tr>';

		// Ligne des champs de filtres
		print '<tr class="liste_titre">';
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth100" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth100" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth100" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		// Source
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';

		// Date sending
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		//Statut
		print '<td class="liste_titre" align="right">';
		print $formmailing->selectDestinariesStatus($search_dest_status,'search_dest_status',1);
		print '</td>';
		// Action column
		print '<td class="liste_titre" align="right">';
		$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
		print $searchpitco;
		print '</td>';
		print '</tr>';

		$var = true;
		$i = 0;

		if ($num)
		{
			while ($i < min($num,$limit))
			{
				$obj = $db->fetch_object($resql);

				$var=!$var;

				print "<tr ".$bc[$var].">";
				print '<td>'.$obj->email.'</td>';
				print '<td>'.$obj->lastname.'</td>';
				print '<td>'.$obj->firstname.'</td>';
				print '<td>'.$obj->other.'</td>';
				print '<td align="center">';
                if (empty($obj->source_id) || empty($obj->source_type))
                {
                    print empty($obj->source_url)?'':$obj->source_url; // For backward compatibility
                }
                else
                {
                    if ($obj->source_type == 'member')
                    {
                        include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
                        $objectstatic=new Adherent($db);
						$objectstatic->fetch($obj->source_id);
                        print $objectstatic->getNomUrl(2);
                    }
                    else if ($obj->source_type == 'user')
                    {
                        include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
                        $objectstatic=new User($db);
						$objectstatic->fetch($obj->source_id);
                        $objectstatic->id=$obj->source_id;
                        print $objectstatic->getNomUrl(2);
                    }
                    else if ($obj->source_type == 'thirdparty')
                    {
                        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                        $objectstatic=new Societe($db);
						$objectstatic->fetch($obj->source_id);
                        print $objectstatic->getNomUrl(2);
                    }
                    else
                    {
                        print $obj->source_url;
                    }
                }
				print '</td>';

				// Statut pour l'email destinataire (Attentioon != statut du mailing)
				if ($obj->statut == 0)
				{
					print '<td align="center">&nbsp;</td>';
					print '<td align="right" class="nowrap">'.$langs->trans("MailingStatusNotSent");
					print '</td>';
				}
				else
				{
					print '<td align="center">'.$obj->date_envoi.'</td>';
					print '<td align="right" class="nowrap">';
					print $object::libStatutDest($obj->statut,2,$obj->error_text);
					print '</td>';
				}

				// Search Icon
				print '<td align="right">';
				if ($obj->statut == 0)
				{
					if ($user->rights->mailing->creer && $allowaddtarget) {
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$obj->rowid.$param.'">'.img_delete($langs->trans("RemoveRecipient"));
					}
				}
				print '</td>';
				print '</tr>';

				$i++;
			}
		}
		else
		{
			if ($object->statut < 2) 
			{
			    print '<tr '.$bc[false].'><td colspan="8" class="opacitymedium">';
    			print $langs->trans("NoTargetYet");
    			print '</td></tr>';
			}
		}
		print "</table><br>";

		print '</form>';

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print "\n<!-- Fin liste destinataires selectionnes -->\n";

}


llxFooter();

$db->close();
