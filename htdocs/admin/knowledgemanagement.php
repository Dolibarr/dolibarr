<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/admin/knowledgemanagement.php
 * \ingroup knowledgemanagement
 * \brief   KnowledgeManagement setup page.
 */

// Load Dolibarr environment
require '../main.inc.php';

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/knowledgemanagement/lib/knowledgemanagement.lib.php";

// Translations
$langs->loadLangs(array("admin", "knowledgemanagement"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'knowledgemanagement';

$arrayofparameters = array(
	'KNOWLEDGEMANAGEMENT_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>0),
	//'KNOWLEDGEMANAGEMENT_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
	//'KNOWLEDGEMANAGEMENT_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
	//'KNOWLEDGEMANAGEMENT_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
	//'KNOWLEDGEMANAGEMENT_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
	//'KNOWLEDGEMANAGEMENT_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
);

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstorder = GETPOST('maskconstorder', 'alpha');
	$maskorder = GETPOST('maskorder', 'alpha');

	if ($maskconstorder) {
		$res = dolibarr_set_const($db, $maskconstorder, $maskorder, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/knowledgemanagement/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'KNOWLEDGEMANAGEMENT_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object');
		if (!empty($tmpobjectkey)) {
			$constforval = 'KNOWLEDGEMANAGEMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if ($conf->global->$constforval == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'KNOWLEDGEMANAGEMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'KNOWLEDGEMANAGEMENT_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$page_name = "KnowledgeManagementSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = knowledgemanagementAdminPrepareHead();
print dol_get_fiche_head($head, 'setup', $langs->trans('ModuleKnowledgeManagementName'), -1, "knowledgemanagement");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("KnowledgeManagementSetupPage").'</span><br><br>';


if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $constname => $val) {
		if ($val['enabled']==1) {
			$setupnotempty++;
			print '<tr class="oddeven"><td>';
			$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
			print '<span id="helplink'.$constname.'" class="spanforparamtooltip">'.$form->textwithpicto($langs->trans($constname), $tooltiphelp, 1, 'info', '', 0, 3, 'tootips'.$constname).'</span>';
			print '</td><td>';

			if ($val['type'] == 'textarea') {
				print '<textarea class="flat" name="'.$constname.'" id="'.$constname.'" cols="50" rows="5" wrap="soft">' . "\n";
				print $conf->global->{$constname};
				print "</textarea>\n";
			} elseif ($val['type']== 'html') {
				require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
				$doleditor = new DolEditor($constname, $conf->global->{$constname}, '', 160, 'dolibarr_notes', '', false, false, $conf->fckeditor->enabled, ROWS_5, '90%');
				$doleditor->Create();
			} elseif ($val['type'] == 'yesno') {
				print $form->selectyesno($constname, $conf->global->{$constname}, 1);
			} elseif (preg_match('/emailtemplate:/', $val['type'])) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);

				$tmp = explode(':', $val['type']);
				$nboftemplates = $formmail->fetchAllEMailTemplate($tmp[1], $user, null, 1); // We set lang=null to get in priority record with no lang
				//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
				$arrayofmessagename = array();
				if (is_array($formmail->lines_model)) {
					foreach ($formmail->lines_model as $modelmail) {
						//var_dump($modelmail);
						$moreonlabel = '';
						if (!empty($arrayofmessagename[$modelmail->label])) {
							$moreonlabel = ' <span class="opacitymedium">(' . $langs->trans("SeveralLangugeVariatFound") . ')</span>';
						}
						// The 'label' is the key that is unique if we exclude the language
						$arrayofmessagename[$modelmail->id] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->label)) . $moreonlabel;
					}
				}
				print $form->selectarray($constname, $arrayofmessagename, $conf->global->{$constname}, 'None', 0, 0, '', 0, 0, 0, '', '', 1);
			} elseif (preg_match('/category:/', $val['type'])) {
				require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
				$formother = new FormOther($db);

				$tmp = explode(':', $val['type']);
				print img_picto('', 'category', 'class="pictofixedwidth"');
				print $formother->select_categories($tmp[1],  $conf->global->{$constname}, $constname, 0, $langs->trans('CustomersProspectsCategoriesShort'));
			} elseif (preg_match('/thirdparty_type/', $val['type'])) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
				$formcompany = new FormCompany($db);
				print $formcompany->selectProspectCustomerType($conf->global->{$constname}, $constname);
			} else {
				print '<input name="'.$constname.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->{$constname}.'">';
			}
			print '</td></tr>';
		}
	}
	print '</table>';

	print $form->buttonsSaveCancel("Save", '');

	print '</form>';
	print '<br>';
} else {
	if (!empty($arrayofparameters)) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $constname => $val) {
			if ($val['enabled']==1) {
				$setupnotempty++;
				print '<tr class="oddeven"><td>';
				$tooltiphelp = (($langs->trans($constname . 'Tooltip') != $constname . 'Tooltip') ? $langs->trans($constname . 'Tooltip') : '');
				print $form->textwithpicto($langs->trans($constname), $tooltiphelp);
				print '</td><td>';

				if ($val['type'] == 'textarea') {
					print dol_nl2br($conf->global->{$constname});
				} elseif ($val['type']== 'html') {
					print  $conf->global->{$constname};
				} elseif ($val['type'] == 'yesno') {
					print ajax_constantonoff($constname);
				} elseif (preg_match('/emailtemplate:/', $val['type'])) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);

					$tmp = explode(':', $val['type']);

					$template = $formmail->getEMailTemplate($db, $tmp[1], $user, $langs, $conf->global->{$constname});
					if ($template<0) {
						setEventMessages(null, $formmail->errors, 'errors');
					}
					print $langs->trans($template->label);
				} elseif (preg_match('/category:/', $val['type'])) {
					$c = new Categorie($db);
					$result = $c->fetch($conf->global->{$constname});
					if ($result < 0) {
						setEventMessages(null, $c->errors, 'errors');
					}
					$ways = $c->print_all_ways(' &gt;&gt; ', 'none', 0, 1); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
					$toprint = array();
					foreach ($ways as $way) {
						$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #bbb"') . '>' . $way . '</li>';
					}
					print '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
				} elseif (preg_match('/thirdparty_type/', $val['type'])) {
					if ($conf->global->{$constname}==2) {
						print $langs->trans("Prospect");
					} elseif ($conf->global->{$constname}==3) {
						print $langs->trans("ProspectCustomer");
					} elseif ($conf->global->{$constname}==1) {
						print $langs->trans("Customer");
					} elseif ($conf->global->{$constname}==0) {
						print $langs->trans("NorProspectNorCustomer");
					}
				} else {
					print  $conf->global->{$constname};
				}
				print '</td></tr>';
			}
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		//print '<br>'.$langs->trans("NothingToSetup");
	}
}


$moduledir = 'knowledgemanagement';
$myTmpObjects = array();
$myTmpObjects['MyObject'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);


foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectKey == 'MyObject') {
		continue;
	}
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		$setupnotempty++;

		print load_fiche_titre($langs->trans("NumberingModules", $myTmpObjectKey), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td class="nowrap">'.$langs->trans("Example").'</td>';
		print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
		print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
		print '</tr>'."\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir."core/modules/".$moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_'.strtolower($myTmpObjectKey).'_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir.'/'.$file.'.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
								continue;
							}
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								dol_include_once('/'.$moduledir.'/class/'.strtolower($myTmpObjectKey).'.class.php');

								print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
								print $module->info();
								print '</td>';

								// Show example of numbering model
								print '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									$langs->load("errors");
									print '<div class="error">'.$langs->trans($tmp).'</div>';
								} elseif ($tmp == 'NotConfigured') {
									print '<span class="opacitymedium">'.$langs->trans($tmp).'</span>';
								} else {
									print $tmp;
								}
								print '</td>'."\n";

								print '<td class="center">';
								$constforvar = 'KNOWLEDGEMANAGEMENT_'.strtoupper($myTmpObjectKey).'_ADDON';
								if (getDolGlobalString($constforvar) == $file) {
									print img_picto($langs->trans("Activated"), 'switch_on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&object='.strtolower($myTmpObjectKey).'&value='.urlencode($file).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
								}
								print '</td>';

								$mytmpinstance = new $myTmpObjectKey($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
									$htmltooltip .= ''.$langs->trans("NextValue").': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
											$nextval = $langs->trans($nextval);
										}
										$htmltooltip .= $nextval.'<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error).'<br>';
									}
								}

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								print "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		print "</table><br>\n";
	}

	if ($myTmpObjectArray['includedocgeneration']) {
		/*
		 * Document templates generators
		 */
		$setupnotempty++;
		$type = strtolower($myTmpObjectKey);

		print load_fiche_titre($langs->trans("DocumentModules", $myTmpObjectKey), '', '');

		// Load array def with activated templates
		$def = array();
		$sql = "SELECT nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		$sql .= " WHERE type = '".$db->escape($type)."'";
		$sql .= " AND entity = ".$conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num_rows = $db->num_rows($resql);
			while ($i < $num_rows) {
				$array = $db->fetch_array($resql);
				array_push($def, $array[0]);
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print "<table class=\"noborder\" width=\"100%\">\n";
		print "<tr class=\"liste_titre\">\n";
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
		print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
		print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
		print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
		print "</tr>\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			foreach (array('', '/doc') as $valdir) {
				$realpath = $reldir."core/modules/".$moduledir.$valdir;
				$dir = dol_buildpath($realpath);

				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							$filelist[] = $file;
						}
						closedir($handle);
						arsort($filelist);

						foreach ($filelist as $file) {
							if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
								if (file_exists($dir.'/'.$file)) {
									$name = substr($file, 4, dol_strlen($file) - 16);
									$classname = substr($file, 0, dol_strlen($file) - 12);

									require_once $dir.'/'.$file;
									$module = new $classname($db);

									$modulequalified = 1;
									if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
										$modulequalified = 0;
									}
									if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
										$modulequalified = 0;
									}

									if ($modulequalified) {
										print '<tr class="oddeven"><td width="100">';
										print (empty($module->name) ? $name : $module->name);
										print "</td><td>\n";
										if (method_exists($module, 'info')) {
											print $module->info($langs);
										} else {
											print $module->description;
										}
										print '</td>';

										// Active
										if (in_array($name, $def)) {
											print '<td class="center">'."\n";
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
											print img_picto($langs->trans("Enabled"), 'switch_on');
											print '</a>';
											print '</td>';
										} else {
											print '<td class="center">'."\n";
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
											print "</td>";
										}

										// Default
										print '<td class="center">';
										$constforvar = 'KNOWLEDGEMANAGEMENT_'.strtoupper($myTmpObjectKey).'_ADDON';
										if (getDolGlobalString($constforvar) == $name) {
											//print img_picto($langs->trans("Default"), 'on');
											// Even if choice is the default value, we allow to disable it. Replace this with previous line if you need to disable unset
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&amp;token='.newToken().'&amp;object='.urlencode(strtolower($myTmpObjectKey)).'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type='.urlencode($type).'" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
										} else {
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&amp;object='.urlencode(strtolower($myTmpObjectKey)).'&amp;value='.$name.'&amp;scan_dir='.urlencode($module->scandir).'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
										}
										print '</td>';

										// Info
										$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
										$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
										if ($module->type == 'pdf') {
											$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
										}
										$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

										$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
										$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
										$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);

										print '<td class="center">';
										print $form->textwithpicto('', $htmltooltip, 1, 0);
										print '</td>';

										// Preview
										print '<td class="center">';
										if ($module->type == 'pdf') {
											print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&object='.$myTmpObjectKey.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
										} else {
											print img_object($langs->trans("PreviewNotAvailable"), 'generic');
										}
										print '</td>';

										print "</tr>\n";
									}
								}
							}
						}
					}
				}
			}
		}

		print '</table>';
	}
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
