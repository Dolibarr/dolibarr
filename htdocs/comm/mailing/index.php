<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/comm/mailing/index.php
 *       \ingroup    mailing
 *       \brief      Home page for emailing area
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('mailingindex'));

// Load translation files required by the page
$langs->loadLangs(array('commercial', 'orders', 'mails'));

$object = new Mailing($db);

// Security check
$result = restrictedArea($user, 'mailing');


/*
 *	View
 */

$help_url = 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
$title = $langs->trans('MailingArea');

llxHeader('', $title, $help_url);

print load_fiche_titre($title);


print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


$titlesearch = $langs->trans("SearchAMailing");
if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
	$titlesearch .= ' | '.$langs->trans("smsing");
}

// Search into emailings
print '<form method="post" action="'.DOL_URL_ROOT.'/comm/mailing/list.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><td colspan="3">'.$titlesearch.'</td></tr>';
print '<tr class="oddeven"><td class="nowrap">';
print $langs->trans("Ref").':</td><td><input type="text" class="flat inputsearch" name="sref"></td>';
print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr class="oddeven"><td class="nowrap">';
print $langs->trans("Other").':</td><td><input type="text" class="flat inputsearch" name="sall"></td>';

print "</table></div></form><br>\n";




// Affiche stats de tous les modules de destinataires mailings
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TargetsStatistics").'</td></tr>';

$dir = DOL_DOCUMENT_ROOT."/core/modules/mailings";
$handle = opendir($dir);

if (is_resource($handle)) {
	while (($file = readdir($handle)) !== false) {
		if (substr($file, 0, 1) != '.' && substr($file, 0, 3) != 'CVS') {
			if (preg_match("/(.*)\.(.*)\.(.*)/i", $file, $reg)) {
				$modulename = $reg[1];
				if ($modulename == 'example') {
					continue;
				}

				// Loading Class
				$file = $dir."/".$modulename.".modules.php";
				$classname = "mailing_".$modulename;
				require_once $file;
				$mailmodule = new $classname($db);
				'@phan-var-force MailingTargets $mailmodule';

				$qualified = 1;
				foreach ($mailmodule->require_module as $key) {
					if (!isModEnabled($key) || (!$user->admin && !empty($mailmodule->require_admin))) {
						$qualified = 0;
						//print "Prerequisites are not not, selector won't be active";
						break;
					}
				}

				// If emailing is qualified for statistic section
				if ($qualified) {
					foreach ($mailmodule->getSqlArrayForStats() as $sql) {
						print '<tr class="oddeven">';

						$result = $db->query($sql);
						if ($result) {
							$num = $db->num_rows($result);

							$i = 0;
							while ($i < $num) {
								$obj = $db->fetch_object($result);
								print '<td>'.img_object('', $mailmodule->picto).' '.dol_escape_htmltag($obj->label).'</td>';
								print '<td class="right">'.$obj->nb.'</td>';
								$i++;
							}

							$db->free($result);
						} else {
							dol_print_error($db);
						}
						print '</tr>';
					}
				}
			}
		}
	}
	closedir($handle);
}


print "</table><br>";


print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


/*
 * List of last emailings
 */

$limit = 10;
$sql  = "SELECT m.rowid, m.titre as title, m.nbemail, m.statut as status, m.date_creat, m.messtype";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.entity = ".$conf->entity;
$sql .= " ORDER BY m.date_creat DESC";
$sql .= " LIMIT ".$limit;
$result = $db->query($sql);
if ($result) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("LastMailings", $limit).'</td>';
	if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
		print '<td class="center">'.$langs->trans("Type").'</td>';
	}
	print '<td class="center">'.$langs->trans("DateCreation").'</td>';
	print '<td class="center">';
	print $langs->trans("NbOfEMails");
	if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
		print ' | '.$langs->trans("Phone");
	}
	print '</td>';
	print '<td class="right"><a href="'.DOL_URL_ROOT.'/comm/mailing/list.php">'.$langs->trans("AllEMailings").'</a></td>';
	print '</tr>';

	$num = $db->num_rows($result);
	if ($num > 0) {
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($result);
			$mailstatic = new Mailing($db);
			$mailstatic->id = $obj->rowid;
			$mailstatic->ref = $obj->rowid;
			$mailstatic->messtype = $obj->messtype;

			print '<tr class="oddeven">';
			print '<td class="nowrap">'.$mailstatic->getNomUrl(1).'</td>';
			print '<td class="tdoverflowmax100">'.dol_escape_htmltag($obj->title).'</td>';
			if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
				print '<td class="center">'.dol_escape_htmltag($obj->messtype).'</td>';
			}
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_creat), 'day').'</td>';
			print '<td class="center">'.($obj->nbemail ? (int) $obj->nbemail : "0").'</td>';
			print '<td class="right">'.$mailstatic->LibStatut($obj->status, 5).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table></div><br>";
	$db->free($result);
} else {
	dol_print_error($db);
}


print '</div></div></div>';


$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardEmailings', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
