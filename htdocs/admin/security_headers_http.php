<?php
/* Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent 		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/security_headers_http.php
 *      \ingroup    core
 *      \brief      Security options setup
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other", "website"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

$forceCSP = getDolGlobalString("MAIN_SECURITY_FORCECSP");
$selectarrayCSPDirectives = GetContentPolicyDirectives();
$selectarrayCSPSources = GetContentPolicySources();
$forceCSPArr = GetContentPolicyToArray($forceCSP);
$error = 0;


/*
 * Actions
 */

$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'removecspsource') {
	$db->begin();
	$sourcetype = "";
	$sourcecsp = explode("_", GETPOST("sourcecsp"));
	$directive = $sourcecsp[0];
	$sourcekey = isset($sourcecsp[1]) ? $sourcecsp[1] : null;
	$sourcedata = isset($sourcecsp[2]) ? $sourcecsp[2] : null;
	$forceCSPArr = GetContentPolicyToArray($forceCSP);
	$directivesarray = GetContentPolicyDirectives();
	$sourcesarray = GetContentPolicySources();
	if (empty($directive)) {
		$error++;
	}

	if (!empty($directivesarray[$directive])) {
		$directivetype = (string) $directivesarray[$directive]["data-directivetype"];
		if (isset($sourcekey)) {
			$sourcetype = $sourcesarray[$directivetype][$sourcekey]["data-sourcetype"];
		}
	}

	$securityspstring = "";
	if (!$error && !empty($forceCSPArr)) {
		if (isset($sourcekey) && !empty($forceCSPArr[$directive][$sourcekey])) {
			unset($forceCSPArr[$directive][$sourcekey]);
		}
		if (count($forceCSPArr[$directive]) == 0) {
			unset($forceCSPArr[$directive]);
		}
		foreach ($forceCSPArr as $directive => $sourcekeys) {
			if ($securityspstring != "") {
				$securityspstring .= "; ";
			}
			$sourcestring = "";
			foreach ($sourcekeys as $key => $source) {
				$directivetype = $directivesarray[$directive]["data-directivetype"];
				$sourcetype = $sourcesarray[$directivetype][$source]["data-sourcetype"];
				if ($sourcetype == "quoted") {
					$sourcestring .= " '".$source."'";
				} else {
					$sourcestring .= " ".$source;
				}
			}
			$securityspstring .= $directive . $sourcestring;
		}
		$res = dolibarr_set_const($db, 'MAIN_SECURITY_FORCECSP', $securityspstring, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("MainSecurityPolicySucesfullyRemoved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("MainErrorRemovingSecurityPolicy"), null, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
} elseif ($action == "updateform" && GETPOST("btn_MAIN_SECURITY_FORCECSP")) {
	$directivecsp = GETPOST("select_identifier_MAIN_SECURITY_FORCECSP");
	$sourcecsp = GETPOST("select_source_MAIN_SECURITY_FORCECSP");
	$sourcedatacsp = GETPOST("input_data_MAIN_SECURITY_FORCECSP");
	$sourcetype = "";

	$forceCSPArr = GetContentPolicyToArray($forceCSP);
	$directivesarray = GetContentPolicyDirectives();
	$sourcesarray = GetContentPolicySources();
	if (empty($directivecsp)) {
		$error++;
	}
	if ($error || (!isset($sourcecsp) && $directivesarray[$directivecsp]["data-directivetype"] != "none")) {
		$error++;
	}
	if (!$error) {
		$directivetype = $directivesarray[$directivecsp]["data-directivetype"];
		if (isset($sourcecsp)) {
			$sourcetype = $sourcesarray[$directivetype][$sourcecsp]["data-sourcetype"];
		}
		$securityspstring = "";
		if (isset($sourcetype) && $sourcetype == "data") {
			$forceCSPArr[$directivecsp][] = "data:".$sourcedatacsp;
		} elseif (isset($sourcetype) && $sourcetype == "input") {
			if (empty($forceCSPArr[$directivecsp])) {
				$forceCSPArr[$directivecsp] = array();
			}
			$forceCSPArr[$directivecsp] = array_merge(explode(" ", $sourcedatacsp), $forceCSPArr[$directivecsp]);
		} else {
			if (empty($forceCSPArr[$directivecsp])) {
				$forceCSPArr[$directivecsp] = array();
			}
			if (!isset($sourcecsp)) {
				$sourcecsp = "";
			}
			array_unshift($forceCSPArr[$directivecsp], $sourcecsp);
		}
		foreach ($forceCSPArr as $directive => $sourcekeys) {
			if ($securityspstring != "") {
				$securityspstring .= "; ";
			}
			$sourcestring = "";
			foreach ($sourcekeys as $key => $source) {
				$directivetype = $directivesarray[$directive]["data-directivetype"];
				$sourcetype = $sourcesarray[$directivetype][$source]["data-sourcetype"];
				if (isset($sourcetype) && $sourcetype == "quoted") {
					$sourcestring .= " '".$source."'";
				} elseif ($directivetype != "none") {
					$sourcestring .= " ".$source;
				}
			}
			$securityspstring .= $directive . $sourcestring;
		}
		$res = dolibarr_set_const($db, 'MAIN_SECURITY_FORCECSP', $securityspstring, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("MainSecurityPolicySucesfullyAdded"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("MainErrorAddingSecurityPolicy"), null, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
} elseif ($action == "updateform") {
	$db->begin();
	$res1 = $res2 = $res3 = $res4 = 0;
	$securityrp = GETPOST('MAIN_SECURITY_FORCERP', 'alpha');
	$securitysts = GETPOST('MAIN_SECURITY_FORCESTS', 'alpha');
	$securitypp = GETPOST('MAIN_SECURITY_FORCEPP', 'alpha');
	$securitysp = GETPOST('MAIN_SECURITY_FORCECSP', 'alpha');
	$securitycspro = GETPOST('MAIN_SECURITY_FORCECSPRO', 'alpha');

	$res1 = dolibarr_set_const($db, 'MAIN_SECURITY_FORCERP', $securityrp, 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'MAIN_SECURITY_FORCESTS', $securitysts, 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'MAIN_SECURITY_FORCEPP', $securitypp, 'chaine', 0, '', $conf->entity);
	$res4 = dolibarr_set_const($db, 'MAIN_SECURITY_FORCECSP', $securitysp, 'chaine', 0, '', $conf->entity);
	$res5 = dolibarr_set_const($db, 'MAIN_SECURITY_FORCECSPRO', $securitycspro, 'chaine', 0, '', $conf->entity);

	if ($res1 >= 0 && $res2 >= 0 && $res3 >= 0 && $res4 >= 0 && $res5 >= 0) {
		$db->commit();
		setEventMessages($langs->trans("Saved"), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		$db->rollback();
		setEventMessages($langs->trans("ErrorSavingChanges"), null, 'errors');
	}
	$action = '';
	$forceCSP = getDolGlobalString("MAIN_SECURITY_FORCECSP");
}



/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("MainHttpSecurityHeaders"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security_other');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');
$head = security_prepare_head();

print dol_get_fiche_head($head, 'headers_http', '', -1);

print '<br>';

print '<span class="opacitymedium">'.$langs->trans("HTTPHeaderEditor").'. '.$langs->trans("ReservedToAdvancedUsers").'.</span><br><br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateform">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("HTTPHeader").'</td>';
print '<td></td>'."\n";
print '</tr>';

// Force RP
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans('MainSecurityForceRP'), 'HTTP Header Referer-Policy<br><br>'.$langs->trans("Recommended").':<br>strict-origin-when-cross-origin <span class="opacitymedium"> &nbsp; '.$langs->trans("or").' &nbsp; </span> same-origin <span class="opacitymedium">(more secured)</span>', 1, 'help', 'valignmiddle', 0, 3, 'MAIN_SECURITY_FORCERP').'</td>';
print '<td><input class="minwidth500" name="MAIN_SECURITY_FORCERP" id="MAIN_SECURITY_FORCERP" value="'.getDolGlobalString("MAIN_SECURITY_FORCERP").'" spellcheck="false"></td>';
print '</tr>';
// Force STS
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans('MainSecurityForceSTS'), 'HTTP Header Strict-Transport-Security<br><br>'.$langs->trans("Example").':<br>max-age=31536000; includeSubDomains', 1, 'help', 'valignmiddle', 0, 3, 'MAIN_SECURITY_FORCESTS').'</td>';
print '<td><input class="minwidth500" name="MAIN_SECURITY_FORCESTS" id="MAIN_SECURITY_FORCESTS" value="'.getDolGlobalString("MAIN_SECURITY_FORCESTS").'" spellcheck="false"></td>';
print '</tr>';
// Force PP
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans('MainSecurityForcePP'), 'HTTP Header Permissions-Policy<br><br>'.$langs->trans("Example").':<br>camera=*, microphone=(), geolocation=*', 1, 'help', 'valignmiddle', 0, 3, 'MAIN_SECURITY_FORCEPP').'</td>';
print '<td><input class="minwidth500" name="MAIN_SECURITY_FORCEPP" id="MAIN_SECURITY_FORCEPP" value="'.getDolGlobalString("MAIN_SECURITY_FORCEPP").'" spellcheck="false"></td>';
print '</tr>';

$examplecsprule = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com;";

// Force CSP - Content Security Policy
print '<tr class="oddeven nohover">';
print '<td class="tdtop">'.$form->textwithpicto($langs->trans('MainContentSecurityPolicy'), 'HTTP Header Content-Security-Policy<br><br>'.$langs->trans("Example").":<br>".$examplecsprule, 1, 'help', 'valignmiddle', 0, 3, 'MAIN_SECURITY_FORCECSP').'</td>';
print '<td>';

print '<div class="div-table-responsive-no-min">';

print '<input class="minwidth500 quatrevingtpercent" name="MAIN_SECURITY_FORCECSP" id="MAIN_SECURITY_FORCECSP" value="'.$forceCSP.'" spellcheck="false"> <a href="#" id="btnaddcontentsecuritypolicy">'.img_picto('', 'add').'</a><br>';

print '<br class="selectaddcontentsecuritypolicy hidden">';

print '<div id="selectaddcontentsecuritypolicy" class="hidden selectaddcontentsecuritypolicy">';
print $form->selectarray("select_identifier_MAIN_SECURITY_FORCECSP", $selectarrayCSPDirectives, "select_identifier_MAIN_SECURITY_FORCECSP", $langs->trans("FillCSPDirective"), 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth350 inline-block');
print ' ';
print '<input type="hidden" id="select_source_MAIN_SECURITY_FORCECSP" name="select_source_MAIN_SECURITY_FORCECSP">';
foreach ($selectarrayCSPSources as $key => $values) {
	print '<div class="div_MAIN_SECURITY_FORCECSP hidden inline-block maxwidth350" id="div_'.$key.'_MAIN_SECURITY_FORCECSP">';
	print $form->selectarray("select_".$key."_MAIN_SECURITY_FORCECSP", $values, "select_".$key."_MAIN_SECURITY_FORCECSP", $langs->trans("FillCSPSource"), 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth300 inline-block select_MAIN_SECURITY_FORCECSP');
	print '</div>';
}
print ' ';
print '<div class="div_input_data_MAIN_SECURITY_FORCECSP hidden inline-block maxwidth200"><input id="input_data_MAIN_SECURITY_FORCECSP" name="input_data_MAIN_SECURITY_FORCECSP"></div>';
print ' ';
print '<div class="div_btn_class_MAIN_SECURITY_FORCECSP inline-block maxwidth200"><input type="submit" id="btn_MAIN_SECURITY_FORCECSP" name="btn_MAIN_SECURITY_FORCECSP" class="butAction small smallpaddingimp" value="'.$langs->trans("Add").'" disabled></div>';
print '<br><br>';
print '</div>';

if (!empty($forceCSP)) {
	// Content Security Policy list of selected rules
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print img_picto('', 'graph', 'class="pictofixedwidth"').$langs->trans("HierarchicView").'<br>';
	print '<ul>';
	foreach ($forceCSPArr as $directive => $sources) {
		print '<li>';
		if (in_array($directive, array_keys($selectarrayCSPDirectives))) {
			print '<span>'.$directive.'</span>';
		} else {
			print $form->textwithpicto($directive, $langs->trans("UnknowContentSecurityPolicyDirective"), 1, 'warning');
		}
		if (!empty($sources)) {
			print '<ul>';
			foreach ($sources as $key => $source) {
				print '<li><span>'.$source.'</span>&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?action=removecspsource&sourcecsp='.$directive.'_'.$key.'&token='.newToken().'">'.img_delete().'</a></li>';
			}
			print '</ul>';
		} else {
			print '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?action=removecspsource&sourcecsp='.$directive.'&token='.newToken().'">'.img_delete().'</a>';
		}
		print '</li>';
	}
	print '</ul>';
	print '</div>';
}
print '</div>';

print '</td>';
print '</tr>';

// Force CSPRO
if (getDolGlobalString("MAIN_SECURITY_FORCECSPRO")) {
	print '<tr class="oddeven">';
	print '<td>'.$form->textwithpicto($langs->trans('MainSecurityForceCSPRO'), 'HTTP Header Content-Security-Policy-Report-Only<br><br>'.$langs->trans("Example").":<br>".$examplecsprule, 1, 'help', 'valignmiddle', 0, 3, 'MAIN_SECURITY_FORCECSPRO').'</td>';
	print '<td><input class="minwidth500" name="MAIN_SECURITY_FORCECSPRO" id="MAIN_SECURITY_FORCECSPRO" value="'.getDolGlobalString("MAIN_SECURITY_FORCECSPRO").'"></td>';
	print '</tr>';
}

print '</table>';
print '</div>';


print '<div class="center">';

print '<input type="submit" class="button small" name="updateandstay" value="'.$langs->trans("Save").'">';
print '<input class="button button-cancel small" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

print '</div>';


print '<script>
	$(document).ready(function() {
		$("#btnaddcontentsecuritypolicy").on("click", function(){
			if($("#selectaddcontentsecuritypolicy").is(":visible")){
				console.log("We hide select to add Content Security Policy");
				$(".selectaddcontentsecuritypolicy").hide();
			} else {
				console.log("We show select to add Content Security Policy");
				$(".selectaddcontentsecuritypolicy").show();
			}
		});

		$("#select_identifier_MAIN_SECURITY_FORCECSP").on("change", function() {
			key = $(this).find(":selected").data("directivetype");
			console.log("We hide all select div");
			$(".div_MAIN_SECURITY_FORCECSP").hide();
			$(".select_MAIN_SECURITY_FORCECSP").val(null).trigger("change");
			$(".div_input_data_MAIN_SECURITY_FORCECSP").hide();
			$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",true);
			if (key == "none"){
				$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",false);
			} else {
				console.log("We show div select with key "+key);
				$("#div_"+key+"_MAIN_SECURITY_FORCECSP").css("display", "inline-block");
			}
		});

		$(".select_MAIN_SECURITY_FORCECSP").on("change", function() {
			keysource = $(this).find(":selected").data("sourcetype");
			$("#select_source_MAIN_SECURITY_FORCECSP").val($(this).val());
			console.log("We hide and show fields");
			if (keysource == "data" || keysource == "input") {
				$(".div_input_data_MAIN_SECURITY_FORCECSP").css("display", "inline-block");
				$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",true);
			} else {
				$("#input_data_MAIN_SECURITY_FORCECSP").val("");
				$(".div_input_data_MAIN_SECURITY_FORCECSP").hide();
				if (keysource != undefined) {
					$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",false);
				} else {
					$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",true);
				}
			}
		});

		$("#input_data_MAIN_SECURITY_FORCECSP").on("change keyup", function(){
			if ($(this).val() != "") {
				console.log("We show add button");
				$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",false);
			} else {
				console.log("We hide add button");
				$("#btn_MAIN_SECURITY_FORCECSP").prop("disabled",true);
			}
		});
	});
</script>';

print '</form>';

print dol_get_fiche_end();
print '</div>';

// End of page
llxFooter();
$db->close();
