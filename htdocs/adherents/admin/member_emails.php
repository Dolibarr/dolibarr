<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2012		J. Fernando Lagrange		<fernando@demo-tic.org>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *   	\file       htdocs/adherents/admin/member_emails.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

if (!$user->admin) {
	accessforbidden();
}


$oldtypetonewone = array('texte'=>'text', 'chaine'=>'string'); // old type to new ones

$action = GETPOST('action', 'aZ09');

$error = 0;

$helptext = '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
//$helptext.='__YEAR__, __MONTH__, __DAY__';	// Not supported

// Editing global variables not related to a specific theme
$constantes = array(
	'MEMBER_REMINDER_EMAIL'                         => array('type'=>'yesno', 'label' => $langs->trans('MEMBER_REMINDER_EMAIL', $langs->transnoentities("Module2300Name")), 'help' => $langs->trans('MEMBER_REMINDER_EMAILHelp', $langs->transnoentities("Module2300Name"))),
	'ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION' 	=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER'			=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION'		=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION'			=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_EMAIL_TEMPLATE_CANCELATION'			=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_EMAIL_TEMPLATE_EXCLUSION'				=> array('type'=>'emailtemplate:member', 'label'=>''),
	'ADHERENT_MAIL_FROM'							=> array('type'=>'string', 'label'=>''),
	'ADHERENT_CC_MAIL_FROM'							=> array('type'=>'string', 'label'=>''),
	'ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT'		=> array('type'=>'string', 'label'=>''),
	'ADHERENT_AUTOREGISTER_NOTIF_MAIL'				=> array('type'=>'html', 'tooltip' => $helptext, 'label' => '')
);



/*
 * Actions
 */

//
if ($action == 'updateall') {
	$db->begin();

	$res = 0;
	foreach ($constantes as $constname => $value) {
		$constvalue = (GETPOSTISSET('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname, 'alphanohtml') : GETPOST('constvalue'));
		$consttype = (GETPOSTISSET('consttype_'.$constname) ? GETPOST('consttype_'.$constname, 'alphanohtml') : GETPOST('consttype'));
		$constnote = (GETPOSTISSET('constnote_'.$constname) ? GETPOST('constnote_'.$constname, 'restricthtml') : GETPOST('constnote'));

		$typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];
		$constvalue = preg_replace('/:member$/', '', $constvalue);

		$res = dolibarr_set_const($db, $constname, $constvalue, $consttype, 0, $constnote, $conf->entity);
		if ($res <= 0) {
			$error++;
			$action = 'list';
		}
	}

	if ($error > 0) {
		setEventMessages('ErrorFailedToSaveDate', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
}

// Action to update or add a constant
if ($action == 'update' || $action == 'add') {
	$constlineid = GETPOSTINT('rowid');
	$constname = GETPOST('constname', 'alpha');

	$constvalue = (GETPOSTISSET('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname, 'alphanohtml') : GETPOST('constvalue'));
	$consttype = (GETPOSTISSET('consttype_'.$constname) ? GETPOST('consttype_'.$constname, 'alphanohtml') : GETPOST('consttype'));
	$constnote = (GETPOSTISSET('constnote_'.$constname) ? GETPOST('constnote_'.$constname, 'restricthtml') : GETPOST('constnote'));

	$typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];
	$constvalue = preg_replace('/:member$/', '', $constvalue);

	$res = dolibarr_set_const($db, $constname, $constvalue, $typetouse, 0, $constnote, $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("MembersSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-admin_emails');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'emails', $langs->trans("Members"), -1, 'user');

// Use global form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateall">';

print '<br>';

// TODO Try to use the formsetup class.

$tableau = $constantes;

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="">'.$langs->trans("Description").'</td>';
print '<td>';
print '</td>';
print "</tr>\n";

foreach ($tableau as $key => $const) {	// Loop on each param
	$label = '';
	$type = $const['type'];
	$label = $const['label'];
	$help = empty($const['help']) ? '' : $const['help'];
	$const = $key;

	$sql = "SELECT rowid, ".$db->decrypt('name')." as name, ".$db->decrypt('value')." as value, type, note";
	$sql .= " FROM ".MAIN_DB_PREFIX."const";
	$sql .= " WHERE ".$db->decrypt('name')." = '".$db->escape($const)."'";
	$sql .= " AND entity IN (0, ".$conf->entity.")";
	$sql .= " ORDER BY name ASC, entity DESC";
	$resql = $db->query($sql);

	dol_syslog("List params", LOG_DEBUG);

	if ($resql) {
		$obj = $db->fetch_object($resql); // Take first result of select

		if (empty($obj)) {	// If not yet into table
			$obj = (object) array('rowid' => '', 'name' => $const, 'value' => '', 'type' => $type, 'note' => '');
		}
		if ($const == 'ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION' && !getDolGlobalString('MEMBER_REMINDER_EMAIL')) {
			continue;
		}

		print '<tr class="oddeven">';

		// Show label of parameter
		print '<td>';
		print '<input type="hidden" name="rowid[]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="constname[]" value="'.$const.'">';
		print '<input type="hidden" name="constnote_'.$obj->name.'" value="'.nl2br(dol_escape_htmltag($obj->note)).'">';
		print '<input type="hidden" name="consttype_'.$obj->name.'" value="'.($obj->type ? $obj->type : 'string').'">';

		$picto = '';
		$tmparray = explode(':', $obj->type);
		if (!empty($tmparray[1])) {
			$picto = preg_replace('/_send$/', '', $tmparray[1]);
		}
		if ($picto) {
			print img_picto('', $picto, 'class="pictofixedwidth"');
		}

		if (!empty($tableau[$key]['tooltip'])) {
			print $form->textwithpicto($label ? $label : $langs->trans('Desc'.$const), $tableau[$key]['tooltip']);
		} else {
			print($label ? $label : $langs->trans('Desc'.$const));
		}

		if (in_array($const, ['ADHERENT_MAIL_FROM', 'ADHERENT_CC_MAIL_FROM'])) {
			print ' '.img_help(1, $langs->trans("EMailHelpMsgSPFDKIM"));
		}
		if ($help) {
			print '<br><span class="opacitymedium">';
			//print $langs->trans("MEMBER_REMINDER_EMAILHelp");
			print $help;
			print '</span>';
		}

		print "</td>\n";

		// Value
		print '<td>';
		print '<input type="hidden" name="consttype_'.$const.'" value="'.($obj->type ? $obj->type : 'string').'">';
		print '<input type="hidden" name="constnote_'.$const.'" value="'.nl2br(dol_escape_htmltag($obj->note)).'">';
		if ($obj->type == 'textarea') {
			print '<textarea class="flat" name="constvalue_'.$const.'" cols="50" rows="5" wrap="soft">'."\n";
			print $obj->value;
			print "</textarea>\n";
		} elseif ($obj->type == 'html') {
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor = new DolEditor('constvalue_'.$const, $obj->value, '', 160, 'dolibarr_notes', '', false, false, isModEnabled('fckeditor'), ROWS_5, '90%');
			$doleditor->Create();
		} elseif ($obj->type == 'yesno') {
			print $form->selectyesno('constvalue_'.$const, $obj->value, 1, false, 0, 1);
		} elseif (preg_match('/emailtemplate/', $obj->type)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);

			$tmp = explode(':', $obj->type);

			$formmail->fetchAllEMailTemplate($tmp[1], $user, null, -1); // We set lang=null to get in priority record with no lang
			$arrayofmessagename = array();
			if (is_array($formmail->lines_model)) {
				foreach ($formmail->lines_model as $modelmail) {
					//var_dump($modelmail);
					$moreonlabel = '';
					if (!empty($arrayofmessagename[$modelmail->label])) {
						$moreonlabel = ' <span class="opacitymedium">('.$langs->trans("SeveralLangugeVariatFound").')</span>';
					}
					// The 'label' is the key that is unique if we exclude the language
					$arrayofmessagename[$modelmail->label.':'.$tmp[1]] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->label)).$moreonlabel;
				}
			}
			//var_dump($arraydefaultmessage);
			//var_dump($arrayofmessagename);
			print $form->selectarray('constvalue_'.$const, $arrayofmessagename, $obj->value.':'.$tmp[1], 'None', 0, 0, '', 0, 0, 0, '', '', 1);

			print '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/mails_templates.php', ['action' => 'create', 'type_template' => $tmp[1], 'backtopage' => dolBuildUrl($_SERVER["PHP_SELF"])]).'">'.img_picto('', 'add').'</a>';
		} elseif (preg_match('/MAIL_FROM$/i', $const)) {
			print img_picto('', 'email', 'class="pictofixedwidth"').'<input type="text" class="flat minwidth300" name="constvalue_'.$const.'" value="'.dol_escape_htmltag($obj->value).'">';
		} else { // type = 'string' ou 'chaine'
			print '<input type="text" class="flat minwidth300" name="constvalue_'.$const .'" value="'.dol_escape_htmltag($obj->value).'">';
		}
		print '</td>';

		print "</tr>\n";
	}
}
print '</table>';
print '</div>';


print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';
print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
