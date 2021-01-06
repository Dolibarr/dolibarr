<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@uers.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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

// Load translation files required by the page
$langs->load("mails");

// Security check
if (!$user->rights->mailing->lire || $user->socid > 0) accessforbidden();


// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "mc.statut,email";
if (!$sortorder) $sortorder = "DESC,ASC";

$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');
$search_lastname = GETPOST("search_lastname", 'alphanohtml');
$search_firstname = GETPOST("search_firstname", 'alphanohtml');
$search_email = GETPOST("search_email", 'alphanohtml');
$search_other = GETPOST("search_other", 'alphanohtml');
$search_dest_status = GETPOST('search_dest_status', 'alphanohtml');

// Search modules dirs
$modulesdir = dolGetModulesDirs('/mailings');

$object = new Mailing($db);
$result = $object->fetch($id);


/*
 * Actions
 */

if ($action == 'add')
{
	$module = GETPOST("module", 'alpha');
	$result = -1;

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

			// Add targets into database
			$obj = new $classname($db);
			dol_syslog("Call add_to_target on class ".$classname);
			$result = $obj->add_to_target($id);
		}
	}
	if ($result > 0)
	{
		setEventMessages($langs->trans("XTargetsAdded", $result), null, 'mesgs');

		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	if ($result == 0)
	{
		setEventMessages($langs->trans("WarningNoEMailsAdded"), null, 'warnings');
	}
	if ($result < 0)
	{
		setEventMessages($langs->trans("Error").($obj->error ? ' '.$obj->error : ''), null, 'errors');
	}
}

if (GETPOST('clearlist', 'int'))
{
	// Loading Class
	$obj = new MailingTargets($db);
	$obj->clear_target($id);
	/* Avoid this to allow reposition
	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit;
	*/
}

if (GETPOST('exportcsv', 'int'))
{
	$completefilename = 'targets_emailing'.$object->id.'_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename='.$completefilename);

	// List of selected targets
	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut, mc.date_envoi, mc.tms,";
	$sql .= " mc.source_url, mc.source_id, mc.source_type, mc.error_text";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing=".$object->id;
	$sql .= $db->order($sortfield, $sortorder);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$sep = ',';

		while ($obj = $db->fetch_object($resql))
		{
			print $obj->rowid.$sep;
			print $obj->lastname.$sep;
			print $obj->firstname.$sep;
			print $obj->email.$sep;
			print $obj->other.$sep;
			print $obj->date_envoi.$sep;
			print $obj->tms.$sep;
			print $obj->source_url.$sep;
			print $obj->source_id.$sep;
			print $obj->source_type.$sep;
			print $obj->error_text.$sep;
			print "\n";
		}

		exit;
	}
	else
	{
		dol_print_error($db);
	}
	exit;
}

if ($action == 'delete')
{
	// Ici, rowid indique le destinataire et id le mailing
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$rowid;
	$resql = $db->query($sql);
	if ($resql)
	{
		if (!empty($id))
		{
			$obj = new MailingTargets($db);
			$obj->update_nb($id);

			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
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

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_lastname = '';
	$search_firstname = '';
	$search_email = '';
	$search_other = '';
	$search_dest_status = '';
}



/*
 * View
 */

llxHeader('', $langs->trans("Mailing"), 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);
$formmailing = new FormMailing($db);

if ($object->fetch($id) >= 0)
{
	$head = emailing_prepare_head($object);

	dol_fiche_head($head, 'targets', $langs->trans("Mailing"), -1, 'email');

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlright = '';
	$nbtry = $nbok = 0;
	if ($object->statut == 2 || $object->statut == 3)
	{
		$nbtry = $object->countNbOfTargets('alreadysent');
		$nbko  = $object->countNbOfTargets('alreadysentko');
		$nbok = ($nbtry - $nbko);

		$morehtmlright .= ' ('.$nbtry.'/'.$object->nbemail;
		if ($nbko) $morehtmlright .= ' - '.$nbko.' '.$langs->trans("Error");
		$morehtmlright .= ') &nbsp; ';
	}

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefield">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$object->titre.'</td></tr>';

	print '<tr><td>'.$langs->trans("MailFrom").'</td><td colspan="3">';
	$emailarray = CMailFile::getArrayAddress($object->email_from);
	foreach ($emailarray as $email => $name) {
		if ($name && $name != $email) {
			print dol_escape_htmltag($name).' &lt;'.$email;
			print '&gt;';
			if (!isValidEmail($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadEMail", $email));
			}
		} else {
			print dol_print_email($object->email_from, 0, 0, 0, 0, 1);
		}
	}
	//print dol_print_email($object->email_from, 0, 0, 0, 0, 1);
	//var_dump($object->email_from);
	print '</td></tr>';

	// Errors to
	print '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td colspan="3">';
	$emailarray = CMailFile::getArrayAddress($object->email_errorsto);
	foreach ($emailarray as $email => $name) {
		if ($name != $email) {
			print dol_escape_htmltag($name).' &lt;'.$email;
			print '&gt;';
			if (!isValidEmail($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadEMail", $email));
			}
		} else {
			print dol_print_email($object->email_errorsto, 0, 0, 0, 0, 1);
		}
	}
	print '</td></tr>';

	// Nb of distinct emails
	print '<tr><td>';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail ? $object->nbemail : 0);
	if (is_numeric($nbemail))
	{
		$text = '';
		if ((!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail) && ($object->statut == 1 || ($object->statut == 2 && $nbtry < $nbemail)))
		{
			if ($conf->global->MAILING_LIMIT_SENDBYWEB > 0)
			{
				$text .= $langs->trans('LimitSendingEmailing', $conf->global->MAILING_LIMIT_SENDBYWEB);
			}
			else
			{
				$text .= $langs->trans('SendingFromWebInterfaceIsNotAllowed');
			}
		}
		if (empty($nbemail)) $nbemail .= ' '.img_warning('').' <font class="warning">'.$langs->trans("NoTargetYet").'</font>';
		if ($text)
		{
			print $form->textwithpicto($nbemail, $text, 1, 'warning');
		}
		else
		{
			print $nbemail;
		}
	}
	print '</td></tr>';

	print '</table>';

	print "</div>";

	dol_fiche_end();

	print '<br>';


	$allowaddtarget = ($object->statut == 0);

	// Show email selectors
	if ($allowaddtarget && $user->rights->mailing->creer)
	{
		print load_fiche_titre($langs->trans("ToAddRecipientsChooseHere"), ($user->admin ?info_admin($langs->trans("YouCanAddYourOwnPredefindedListHere"), 1) : ''), 'generic');

		//print '<table class="noborder centpercent">';
		print '<div class="tagtable centpercent liste_titre_bydiv borderbottom" id="tablelines">';

		//print '<tr class="liste_titre">';
		print '<div class="tagtr liste_titre">';
		//print '<td class="liste_titre">'.$langs->trans("RecipientSelectionModules").'</td>';
		print '<div class="tagtd">'.$langs->trans("RecipientSelectionModules").'</div>';
		//print '<td class="liste_titre" align="center">'.$langs->trans("NbOfUniqueEMails").'</td>';
		print '<div class="tagtd" align="center">'.$langs->trans("NbOfUniqueEMails").'</div>';
		//print '<td class="liste_titre" align="left">'.$langs->trans("Filter").'</td>';
		print '<div class="tagtd left">'.$langs->trans("Filter").'</div>';
		//print '<td class="liste_titre" align="center">&nbsp;</td>';
		print '<div class="tagtd">&nbsp;</div>';
		//print "</tr>\n";
		print '</div>';

		clearstatcache();

		foreach ($modulesdir as $dir)
		{
		    $modulenames = array();

		    // Load modules attributes in arrays (name, numero, orders) from dir directory
		    //print $dir."\n<br>";
		    dol_syslog("Scan directory ".$dir." for modules");
		    $handle = @opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle)) !== false)
				{
					if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
					{
						if (preg_match("/(.*)\.modules\.php$/i", $file, $reg))
						{
							if ($reg[1] == 'example') continue;
							$modulenames[] = $reg[1];
						}
					}
				}
				closedir($handle);
			}

			// Sort $modulenames
			sort($modulenames);

			$var = true;

			// Loop on each submodule
			foreach ($modulenames as $modulename)
			{
				// Loading Class
				$file = $dir.$modulename.".modules.php";
				$classname = "mailing_".$modulename;
				require_once $file;

				$obj = new $classname($db);

				// Check dependencies
				$qualified = (isset($obj->enabled) ? $obj->enabled : 1);
				foreach ($obj->require_module as $key)
				{
					if (!$conf->$key->enabled || (!$user->admin && $obj->require_admin))
					{
						$qualified = 0;
						//print "Les prerequis d'activation du module mailing ne sont pas respectes. Il ne sera pas actif";
						break;
					}
				}

				// Si le module mailing est qualifie
				if ($qualified)
				{
					$var = !$var;

					if ($allowaddtarget)
					{
						print '<form '.$bctag[$var].' name="'.$modulename.'" action="'.$_SERVER['PHP_SELF'].'?action=add&id='.$object->id.'&module='.$modulename.'" method="POST" enctype="multipart/form-data">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
					}
					else
					{
					    print '<div '.$bctag[$var].'>';
					}

					print '<div class="tagtd">';
					if (empty($obj->picto)) $obj->picto = 'generic';
					print img_object($langs->trans("EmailingTargetSelector").': '.get_class($obj), $obj->picto, 'class="valignmiddle pictomodule"');
					print ' ';
					print $obj->getDesc();
					print '</div>';

					try {
						$nbofrecipient = $obj->getNbOfRecipients('');
					}
					catch (Exception $e)
					{
						dol_syslog($e->getMessage(), LOG_ERR);
					}

					print '<div class="tagtd center">';
					if ($nbofrecipient >= 0)
					{
						print $nbofrecipient;
					}
					else
					{
						print $langs->trans("Error").' '.img_error($obj->error);
					}
					print '</div>';

					print '<div class="tagtd left">';
					if ($allowaddtarget)
					{
    					try {
    						$filter = $obj->formFilter();
    					}
    					catch (Exception $e)
    					{
    						dol_syslog($e->getMessage(), LOG_ERR);
    					}
    					if ($filter) print $filter;
    					else print $langs->trans("None");
					}
					print '</div>';

					print '<div class="tagtd right">';
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
					print '</div>';

					if ($allowaddtarget) print '</form>';
					else print '</div>';
				}
			}
		}	// End foreach dir

		print '</div>';

		print '<br><br>';
	}

	// List of selected targets
	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut, mc.date_envoi, mc.tms,";
	$sql .= " mc.source_url, mc.source_id, mc.source_type, mc.error_text";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing=".$object->id;
	$asearchcriteriahasbeenset = 0;
	if ($search_lastname)  {
		$sql .= natural_search("mc.lastname", $search_lastname);
		$asearchcriteriahasbeenset++;
	}
	if ($search_firstname) {
		$sql .= natural_search("mc.firstname", $search_firstname);
		$asearchcriteriahasbeenset++;
	}
	if ($search_email)     {
		$sql .= natural_search("mc.email", $search_email);
		$asearchcriteriahasbeenset++;
	}
	if ($search_other)     {
		$sql .= natural_search("mc.other", $search_other);
		$asearchcriteriahasbeenset++;
	}
	if ($search_dest_status != '' && $search_dest_status >= -1) {
		$sql .= " AND mc.statut=".$db->escape($search_dest_status)." ";
		$asearchcriteriahasbeenset++;
	}
	$sql .= $db->order($sortfield, $sortorder);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
	{
	    $result = $db->query($sql);
	    $nbtotalofrecords = $db->num_rows($result);
	    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	    {
	    	$page = 0;
	    	$offset = 0;
	    }

	    // Fix/update nbemail on emailing record if it differs (may happen if user edit lines from database directly)
	    if (empty($asearchcriteriahasbeenset)) {
	    	if ($nbtotalofrecords != $object->email) {
	    		dol_syslog("We found a difference in nb of record in target table and the property ->nbemail, we fix ->nbemail");
	    		//print "nbemail=".$object->nbemail." nbtotalofrecords=".$nbtotalofrecords;
	    		$resultrefresh = $object->refreshNbOfTargets();
	    		if ($resultrefresh < 0) {
	    			dol_print_error($db, $object->error, $object->errors);
	    		}
	    	}
	    }
	}

	//$nbtotalofrecords=$object->nbemail;     // nbemail is a denormalized field storing nb of targets
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$param = "&id=".$object->id;
		//if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
		if ($search_lastname)  $param .= "&search_lastname=".urlencode($search_lastname);
		if ($search_firstname) $param .= "&search_firstname=".urlencode($search_firstname);
		if ($search_email)     $param .= "&search_email=".urlencode($search_email);
		if ($search_other)     $param .= "&search_other=".urlencode($search_other);

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';

		$morehtmlcenter = '';
		if ($allowaddtarget) {
			$morehtmlcenter = '<span class="opacitymedium">'.$langs->trans("ToClearAllRecipientsClickHere").'</span> <a href="'.$_SERVER["PHP_SELF"].'?clearlist=1&id='.$object->id.'" class="button reposition">'.$langs->trans("TargetsReset").'</a>';
		}
		$morehtmlcenter .= ' <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?exportcsv=1&id='.$object->id.'">'.$langs->trans("Download").'</a>';

		print_barre_liste($langs->trans("MailSelectedRecipients"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $morehtmlcenter, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);

		print '</form>';

		print "\n<!-- Liste destinataires selectionnes -->\n";
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="limit" value="'.$limit.'">';

		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';

		// Ligne des champs de filtres
		print '<tr class="liste_titre_filter">';
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth75" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth100" type="text" name="search_other" value="'.dol_escape_htmltag($search_other).'">';
		print '</td>';
		// Source
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';

		// Date last update
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';

		// Date sending
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';

		//Statut
		print '<td class="liste_titre right">';
		print $formmailing->selectDestinariesStatus($search_dest_status, 'search_dest_status', 1);
		print '</td>';
		// Action column
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
		print $searchpicto;
		print '</td>';
		print '</tr>';

		if ($page) $param .= "&page=".urlencode($page);

		print '<tr class="liste_titre">';
		print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "mc.email", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Lastname", $_SERVER["PHP_SELF"], "mc.lastname", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Firstname", $_SERVER["PHP_SELF"], "mc.firstname", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("OtherInformations", $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Source", $_SERVER["PHP_SELF"], "", $param, "", 'align="center"', $sortfield, $sortorder);
		// Date last update
		print_liste_field_titre("DateLastModification", $_SERVER["PHP_SELF"], "mc.tms", $param, "", 'align="center"', $sortfield, $sortorder);
		// Date sending
		print_liste_field_titre("DateSending", $_SERVER["PHP_SELF"], "mc.date_envoi", $param, '', 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "mc.statut", $param, '', 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
		print '</tr>';

		$i = 0;

		if ($num)
		{
			include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$objectstaticmember = new Adherent($db);
			$objectstaticuser = new User($db);
			$objectstaticcompany = new Societe($db);
			$objectstaticcontact = new Contact($db);

			while ($i < min($num, $limit))
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td>'.$obj->email.'</td>';
				print '<td>'.$obj->lastname.'</td>';
				print '<td>'.$obj->firstname.'</td>';
				print '<td>'.$obj->other.'</td>';
				print '<td class="center">';
                if (empty($obj->source_id) || empty($obj->source_type))
                {
                    print empty($obj->source_url) ? '' : $obj->source_url; // For backward compatibility
                }
                else
                {
                    if ($obj->source_type == 'member')
                    {
						$objectstaticmember->fetch($obj->source_id);
                        print $objectstaticmember->getNomUrl(1);
                    }
                    elseif ($obj->source_type == 'user')
                    {
						$objectstaticuser->fetch($obj->source_id);
                        print $objectstaticuser->getNomUrl(1);
                    }
                    elseif ($obj->source_type == 'thirdparty')
                    {
						$objectstaticcompany->fetch($obj->source_id);
                        print $objectstaticcompany->getNomUrl(1);
                    }
                    elseif ($obj->source_type == 'contact')
                    {
                    	$objectstaticcontact->fetch($obj->source_id);
                    	print $objectstaticcontact->getNomUrl(1);
                    }
                    else
                    {
                        print $obj->source_url;
                    }
                }
				print '</td>';

				// Date last update
				print '<td class="center">';
				print dol_print_date($obj->tms, 'dayhour');
				print '</td>';

				// Status of recipient sending email (Warning != status of emailing)
				if ($obj->statut == 0)
				{
					// Date sent
					print '<td align="center">&nbsp;</td>';

					print '<td class="nowrap right">';
					print $object::libStatutDest($obj->statut, 2, '');
					print '</td>';
				}
				else
				{
					// Date sent
					print '<td class="center">'.$obj->date_envoi.'</td>';

					print '<td class="nowrap right">';
					print $object::libStatutDest($obj->statut, 2, $obj->error_text);
					print '</td>';
				}

				// Search Icon
				print '<td class="right">';
				if ($obj->statut == 0)	// Not sent yet
				{
					if ($user->rights->mailing->creer && $allowaddtarget) {
						print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$obj->rowid.$param.'">'.img_delete($langs->trans("RemoveRecipient")).'</a>';
					}
				}
				/*if ($obj->statut == -1)	// Sent with error
				{
					print '<a href="'.$_SERVER['PHP_SELF'].'?action=retry&rowid='.$obj->rowid.$param.'">'.$langs->trans("Retry").'</a>';
				}*/
				print '</td>';
				print '</tr>';

				$i++;
			}
		}
		else
		{
			if ($object->statut < 2)
			{
			    print '<tr><td colspan="9" class="opacitymedium">';
    			print $langs->trans("NoTargetYet");
    			print '</td></tr>';
			}
		}
		print "</table><br>";
		print '</div>';

		print '</form>';

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print "\n<!-- Fin liste destinataires selectionnes -->\n";
}

// End of page
llxFooter();
$db->close();
