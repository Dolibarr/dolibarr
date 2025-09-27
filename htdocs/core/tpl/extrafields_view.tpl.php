<?php
/* Copyright (C) 2014	    Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	    Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2021-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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
 * @var CommonObject $object 	Object (invoice, order, ...)
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string	$action
 * @var	array<string,mixed>	$parameters		Array of parameters
 * @var int 	$cols		@deprecated 	Add this information into $parameters['cols']
 * @var string	$forcefieldid
 * @var string	$forceobjectid
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (!is_object($form)) {
	$form = new Form($db);
}

?>
<!-- BEGIN PHP TEMPLATE core/tpl/extrafields_view.tpl.php -->
<?php
if (!isset($parameters) || !is_array($parameters)) {
	$parameters = array();
}
if (!empty($cols)) {
	$parameters['colspan'] = ' colspan="'.$cols.'"';	// deprecated, keptfor backward compatibility
}
if (!empty($cols) && !isset($parameters['cols'])) {
	$parameters['cols'] = $cols;
} elseif (isset($parameters['cols'])) {
	$cols = $parameters['cols'];
}
if (!empty($object->fk_soc)) {
	$parameters['socid'] = $object->fk_soc;
}
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
print $hookmanager->resPrint;
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


//var_dump($extrafields->attributes[$object->table_element]);
if (empty($reshook) && !empty($object->table_element) && isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label'])) {
	//$lastseparatorkeyfound = '';
	$extrafields_collapse_num = '';
	//$extrafields_collapse_num_old = '';
	$i = 0;

	// Loop on each extrafield
	foreach ($extrafields->attributes[$object->table_element]['label'] as $tmpkeyextra => $tmplabelextra) {
		$i++;

		// Discard if extrafield is a hidden field on form
		$enabled = 1;
		if ($enabled && isset($extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra])) {
			$enabled = (int) dol_eval((string) $extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra], 1, 1, '2');
		}
		if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra])) {
			$enabled = (int) dol_eval($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra], 1, 1, '2');
		}

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra])) {
			$perms = (int) dol_eval($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra], 1, 1, '1');
		}
		//print $tmpkeyextra.'-'.$enabled.'-'.$perms.'<br>'."\n";

		if (empty($enabled)) {
			continue; // 0 = Never visible field
		}
		if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
			continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
		}
		/* No perm means we can't edit, but we should be able to see according to visibility field.
		if (empty($perms)) {
			continue; // 0 = Not visible
		}
		*/

		// Load language if required
		if (!empty($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra])) {
			$langs->load($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra]);
		}
		if ($action == 'edit_extras') {
			$value = (GETPOSTISSET("options_".$tmpkeyextra) ? GETPOST("options_".$tmpkeyextra) : (isset($object->array_options["options_".$tmpkeyextra]) ? $object->array_options["options_".$tmpkeyextra] : ''));
		} else {
			$value = (isset($object->array_options["options_".$tmpkeyextra]) ? $object->array_options["options_".$tmpkeyextra] : '');
			//var_dump($tmpkeyextra.' - '.$value);
		}

		// Print line tr of extra field
		if ($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra] == 'separate') {
			$extrafields_collapse_num = $tmpkeyextra;

			print $extrafields->showSeparator($tmpkeyextra, $object);

			//$lastseparatorkeyfound = $tmpkeyextra;
		} else {
			$collapse_group = $extrafields_collapse_num.(!empty($object->id) ? '_'.$object->id : '');

			print '<tr class="trextrafields_collapse'.$collapse_group;
			/*if ($extrafields_collapse_num && $extrafields_collapse_num_old && $extrafields_collapse_num != $extrafields_collapse_num_old) {
				print ' trextrafields_collapse_new';
			}*/
			if ($extrafields_collapse_num && $i == count($extrafields->attributes[$object->table_element]['label'])) {
				print ' trextrafields_collapse_last';
			}
			print '"';
			if (isset($extrafields->expand_display) && empty($extrafields->expand_display[$collapse_group])) {
				print ' style="display: none;"';
			}
			print '>';
			//$extrafields_collapse_num_old = $extrafields_collapse_num;
			print '<td>';
			print '<table class="nobordernopadding centpercent">';
			print '<tr>';

			print '<td class="';
			if ((!empty($action) && ($action == 'create' || $action == 'edit')) && !empty($extrafields->attributes[$object->table_element]['required'][$tmpkeyextra])) {
				print ' fieldrequired';
			}
			print '">';
			if (!empty($extrafields->attributes[$object->table_element]['help'][$tmpkeyextra])) {
				// You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
				$tmptooltip = explode(':', $extrafields->attributes[$object->table_element]['help'][$tmpkeyextra]);
				print $form->textwithpicto($langs->trans($tmplabelextra), $langs->trans($tmptooltip[0]), 1, 'help', '', 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_'.$tmpkeyextra.'_'.$tmptooltip[1]));
			} else {
				print $langs->trans($tmplabelextra);
			}
			print '</td>';

			//TODO Improve element and rights detection
			//var_dump($user->rights);
			$permwriteobject = false;
			$keyforperm = $object->element;

			if ($object->element == 'fichinter') {
				$keyforperm = 'ficheinter';
			}
			if ($object->element == 'product') {
				$keyforperm = 'produit';
			}
			if ($object->element == 'project') {
				$keyforperm = 'projet';
			}
			if (isset($user->rights->$keyforperm)) {
				$permwriteobject = $user->hasRight($keyforperm, 'creer') || $user->hasRight($keyforperm, 'create') || $user->hasRight($keyforperm, 'write');
			}
			if ($object->element == 'order_supplier') {
				if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
					$permwriteobject = $user->hasRight('fournisseur', 'commande', 'creer');
				} else {
					$permwriteobject = $user->hasRight('supplier_order', 'creer');
				}
			}
			if ($object->element == 'invoice_supplier') {
				if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
					$permwriteobject = $user->hasRight('fournisseur', 'facture', 'creer');
				} else {
					$permwriteobject = $user->hasRight('supplier_invoice', 'creer');
				}
			}
			if ($object->element == 'shipping') {
				$permwriteobject = $user->hasRight('expedition', 'creer');
			}
			if ($object->element == 'delivery') {
				$permwriteobject = $user->hasRight('expedition', 'delivery', 'creer');
			}
			if ($object->element == 'productlot') {
				$permwriteobject = $user->hasRight('stock', 'creer');
			}
			if ($object->element == 'facturerec') {
				$permwriteobject = $user->hasRight('facture', 'creer');
			}
			if ($object->element == 'mo') {
				$permwriteobject = $user->hasRight('mrp', 'write');
			}
			if ($object->element == 'contact') {
				$permwriteobject = $user->hasRight('societe', 'contact', 'creer');
			}
			if ($object->element == 'salary') {
				$permwriteobject = $user->hasRight('salaries', 'write');
			}
			if ($object->element == 'member') {
				$permwriteobject = $user->hasRight('adherent', 'creer');
			}

			// Set permission to edit/write extrafield.
			//print "permwriteobject=".$permwriteobject." perms=".$perms;
			$permtoeditextrafield = $perms;
			if (!isset($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra])) {
				$permtoeditextrafield = $permwriteobject;
			}

			$isdraft = ((isset($object->statut) && $object->statut == 0) || (isset($object->status) && $object->status == 0));
			if (($isdraft || !empty($extrafields->attributes[$object->table_element]['alwayseditable'][$tmpkeyextra]))
				&& $permtoeditextrafield && $enabled != 5 && ($action != 'edit_extras' || GETPOST('attribute') != $tmpkeyextra)
				&& empty($extrafields->attributes[$object->table_element]['computed'][$tmpkeyextra])) {
				$fieldid = empty($forcefieldid) ? 'id' : $forcefieldid;
				$valueid = empty($forceobjectid) ? $object->id : $forceobjectid;
				if ($object->table_element == 'societe') {
					$fieldid = 'socid';
				}

				print '<td class="right">';
				if (isModEnabled("ai") && !empty($extrafields->attributes[$object->table_element]["aiprompt"][$tmpkeyextra])) {
					$showlinktoai = "extrafieldfiller_".$tmpkeyextra;
					$showlinktoailabel = $langs->trans("FillExtrafieldWithAi");
					$htmlname = !empty($object->id) ? $object->element.'_extras_'.$tmpkeyextra.'_'.$object->id : "options_".$tmpkeyextra;
					$onlyenhancements = "textgenerationextrafield";
					$morecss = "editfielda";
					$aiprompt = $extrafields->attributes[$object->table_element]["aiprompt"][$tmpkeyextra];
					$out = "";

					// Fill $out
					include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';
					print $out;
					print '<script>
						$(document).ready(function() {
							$("#'.$htmlname.'").on("change", function () {
								value = $(this).html();
								$.ajax({
									method: "POST",
									dataType: "json",
									url: "'. DOL_URL_ROOT.'/core/ajax/updateextrafield.php",
									data: {"token": "'.currentToken().'", "objectType": "'.$object->element.'", "objectId": "'.$object->id.'", "field": "'.$tmpkeyextra.'", "value": value},
									success: function(response) {
										console.log("Extrafield "+'.$tmpkeyextra.'+" successfully updated");
									},
								});
							});
						});
					</script>';
				}
				print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$valueid.'&action=edit_extras&token='.newToken().'&attribute='.$tmpkeyextra.'&ignorecollapsesetup=1">'.img_edit().'</a>';
				print'</td>';
			}
			print '</tr></table>';
			print '</td>';

			$cssview = !empty($extrafields->attributes[$object->table_element]['cssview'][$tmpkeyextra]) ? ($extrafields->attributes[$object->table_element]['cssview'][$tmpkeyextra] . ' ') : '';
			$html_id = !empty($object->id) ? $object->element.'_extras_'.$tmpkeyextra.'_'.$object->id : '';

			print '<td id="' . $html_id . '" class="valuefield ' . $cssview . $object->element . '_extras_' . $tmpkeyextra . ' wordbreakimp"' . (!empty($cols) ? ' colspan="' . $cols . '"' : '') . '>';

			// Convert date into timestamp format
			if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('date'))) {
				$datenotinstring = empty($object->array_options['options_'.$tmpkeyextra]) ? '' : $object->array_options['options_'.$tmpkeyextra];
				// print 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($object->array_options['options_'.$tmpkeyextra]) && !is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(12, 0, 0, GETPOSTINT("options_".$tmpkeyextra."month"), GETPOSTINT("options_".$tmpkeyextra."day"), GETPOSTINT("options_".$tmpkeyextra."year")) : $datenotinstring;
			}
			if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('datetime'))) {
				$datenotinstring = empty($object->array_options['options_'.$tmpkeyextra]) ? '' : $object->array_options['options_'.$tmpkeyextra];
				// print 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!empty($object->array_options['options_'.$tmpkeyextra]) && !is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(GETPOSTINT("options_".$tmpkeyextra."hour"), GETPOSTINT("options_".$tmpkeyextra."min"), GETPOSTINT("options_".$tmpkeyextra."sec"), GETPOSTINT("options_".$tmpkeyextra."month"), GETPOSTINT("options_".$tmpkeyextra."day"), GETPOSTINT("options_".$tmpkeyextra."year"), 'tzuserrel') : $datenotinstring;
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permtoeditextrafield && GETPOST('attribute', 'restricthtml') == $tmpkeyextra) {
				// Show the extrafield in create or edit mode
				$fieldid = 'id';
				if ($object->table_element == 'societe') {
					$fieldid = 'socid';
				}
				print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"] . '?' . $fieldid . '=' . $object->id . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="'.$tmpkeyextra.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="'.$fieldid.'" value="'.$object->id.'">';
				print $extrafields->showInputField($tmpkeyextra, $value, '', '', '', '', $object, $object->table_element);

				print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

				print '</form>';

				/** @var ?FormAI $formai */
				if (empty($formai) || $formai instanceof FormAI) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formai.class.php';
					$formai = new FormAI($db);
				}
				print $formai->getAjaxAICallFunction();
			} else {
				// Show the extrafield in view mode

				//var_dump($tmpkeyextra.'-'.$value.'-'.$object->table_element);
				print $extrafields->showOutputField($tmpkeyextra, $value, '', $object->table_element, null, $object);
			}

			print '</td>';
			print '</tr>'."\n";
		}
	}

	// Add code to manage list depending on others
	// TODO Test/enhance this with a more generic solution
	if (!empty($conf->use_javascript_ajax)) {
		print "\n";
		print '
				<script>
				    jQuery(document).ready(function() {
						setListDependencies();
				    });
				</script>'."\n";
	}
}
?>
<!-- END PHP TEMPLATE core/tpl/extrafields_view.tpl.php -->
