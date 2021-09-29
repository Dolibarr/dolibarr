<?php
/* Copyright (C) 2014	Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2021		Frédéric France		<frederic.france@netlogic.fr>
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
 *
 * Show extrafields. It also show fields from hook formObjectOptions. Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $parameters
 * $cols
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

if (!is_object($form)) {
	$form = new Form($db);
}


?>
<!-- BEGIN PHP TEMPLATE extrafields_view.tpl.php -->
<?php
if (!is_array($parameters)) {
	$parameters = array();
}
if (!empty($cols)) {
	$parameters['colspan'] = ' colspan="'.$cols.'"';
}
if (!empty($cols)) {
	$parameters['cols'] = $cols;
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
if (empty($reshook) && isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label'])) {
	$lastseparatorkeyfound = '';
	$extrafields_collapse_num = '';
	$extrafields_collapse_num_old = '';
	$i = 0;
	foreach ($extrafields->attributes[$object->table_element]['label'] as $tmpkeyextra => $tmplabelextra) {
		$i++;

		// Discard if extrafield is a hidden field on form

		$enabled = 1;
		if ($enabled && isset($extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$object->table_element]['enabled'][$tmpkeyextra], 1);
		}
		if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra])) {
			$enabled = dol_eval($extrafields->attributes[$object->table_element]['list'][$tmpkeyextra], 1);
		}

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra])) {
			$perms = dol_eval($extrafields->attributes[$object->table_element]['perms'][$tmpkeyextra], 1);
		}
		//print $tmpkeyextra.'-'.$enabled.'-'.$perms.'<br>'."\n";

		if (empty($enabled)) {
			continue; // 0 = Never visible field
		}
		if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
			continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
		}
		if (empty($perms)) {
			continue; // 0 = Not visible
		}

		// Load language if required
		if (!empty($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra])) {
			$langs->load($extrafields->attributes[$object->table_element]['langfile'][$tmpkeyextra]);
		}
		if ($action == 'edit_extras') {
			$value = (GETPOSTISSET("options_".$tmpkeyextra) ? GETPOST("options_".$tmpkeyextra) : $object->array_options["options_".$tmpkeyextra]);
		} else {
			$value = (!empty($object->array_options["options_".$tmpkeyextra]) ? $object->array_options["options_".$tmpkeyextra] : '');
			//var_dump($tmpkeyextra.' - '.$value);
		}

		// Print line tr of extra field
		if ($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra] == 'separate') {
			$extrafields_collapse_num = '';
			$extrafield_param = $extrafields->attributes[$object->table_element]['param'][$tmpkeyextra];
			if (!empty($extrafield_param) && is_array($extrafield_param)) {
				$extrafield_param_list = array_keys($extrafield_param['options']);

				if (count($extrafield_param_list) > 0) {
					$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

					if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
						$extrafields_collapse_num = $extrafields->attributes[$object->table_element]['pos'][$tmpkeyextra];
					}
				}
			}

			print $extrafields->showSeparator($tmpkeyextra, $object);

			$lastseparatorkeyfound = $tmpkeyextra;
		} else {
			print '<tr class="trextrafields_collapse'.$extrafields_collapse_num.(!empty($object->id)?'_'.$object->id:'');
			/*if ($extrafields_collapse_num && $extrafields_collapse_num_old && $extrafields_collapse_num != $extrafields_collapse_num_old) {
				print ' trextrafields_collapse_new';
			}*/
			if ($extrafields_collapse_num && $i == count($extrafields->attributes[$object->table_element]['label'])) {
				print ' trextrafields_collapse_last';
			}
			print '">';
			$extrafields_collapse_num_old = $extrafields_collapse_num;
			print '<td class="titlefield">';
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
			$permok = false;
			$keyforperm = $object->element;
			if ($object->element == 'fichinter') {
				$keyforperm = 'ficheinter';
			}
			if (isset($user->rights->$keyforperm)) {
				$permok = !empty($user->rights->$keyforperm->creer) || !empty($user->rights->$keyforperm->create) || !empty($user->rights->$keyforperm->write);
			}
			if ($object->element == 'order_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->commande->creer;
				} else {
					$permok = $user->rights->supplier_order->creer;
				}
			}
			if ($object->element == 'invoice_supplier') {
				if (empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) {
					$permok = $user->rights->fournisseur->facture->creer;
				} else {
					$permok = $user->rights->supplier_invoice->creer;
				}
			}
			if ($object->element == 'shipping') {
				$permok = $user->rights->expedition->creer;
			}
			if ($object->element == 'delivery') {
				$permok = $user->rights->expedition->delivery->creer;
			}
			if ($object->element == 'productlot') {
				$permok = $user->rights->stock->creer;
			}
			if ($object->element == 'facturerec') {
				$permok = $user->rights->facture->creer;
			}
			if ($object->element == 'mo') {
				$permok = $user->rights->mrp->write;
			}
			if ($object->element == 'contact') {
				$permok = $user->rights->societe->contact->creer;
			}
			if ($object->element == 'salary') {
				$permok = $user->rights->salaries->read;
			}

			$isdraft = ((isset($object->statut) && $object->statut == 0) || (isset($object->status) && $object->status == 0));
			if (($isdraft || !empty($extrafields->attributes[$object->table_element]['alwayseditable'][$tmpkeyextra]))
				&& $permok && $enabled != 5 && ($action != 'edit_extras' || GETPOST('attribute') != $tmpkeyextra)
				&& empty($extrafields->attributes[$object->table_element]['computed'][$tmpkeyextra])) {
				$fieldid = empty($forcefieldid) ? 'id' : $forcefieldid;
				$valueid = empty($forceobjectid) ? $object->id : $forceobjectid;
				if ($object->table_element == 'societe') {
					$fieldid = 'socid';
				}

				print '<td class="right"><a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?'.$fieldid.'='.$valueid.'&action=edit_extras&attribute='.$tmpkeyextra.'&ignorecollapsesetup=1">'.img_edit().'</a></td>';
			}
			print '</tr></table>';
			print '</td>';

			$html_id = !empty($object->id) ? $object->element.'_extras_'.$tmpkeyextra.'_'.$object->id : '';

			print '<td id="'.$html_id.'" class="valuefield '.$object->element.'_extras_'.$tmpkeyextra.' wordbreak"'.(!empty($cols) ? ' colspan="'.$cols.'"' : '').'>';

			// Convert date into timestamp format
			if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('date'))) {
				$datenotinstring = $object->array_options['options_'.$tmpkeyextra];
				// print 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(12, 0, 0, GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int')) : $datenotinstring;
			}
			if (in_array($extrafields->attributes[$object->table_element]['type'][$tmpkeyextra], array('datetime'))) {
				$datenotinstring = $object->array_options['options_'.$tmpkeyextra];
				// print 'X'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.'x';
				if (!is_numeric($object->array_options['options_'.$tmpkeyextra])) {	// For backward compatibility
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$object->array_options['options_' . $tmpkeyextra].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = GETPOSTISSET("options_".$tmpkeyextra) ? dol_mktime(GETPOST("options_".$tmpkeyextra."hour", 'int'), GETPOST("options_".$tmpkeyextra."min", 'int'), GETPOST("options_".$tmpkeyextra."sec", 'int'), GETPOST("options_".$tmpkeyextra."month", 'int'), GETPOST("options_".$tmpkeyextra."day", 'int'), GETPOST("options_".$tmpkeyextra."year", 'int'), 'tzuserrel') : $datenotinstring;
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permok && GETPOST('attribute', 'restricthtml') == $tmpkeyextra) {
				$fieldid = 'id';
				if ($object->table_element == 'societe') {
					$fieldid = 'socid';
				}
				print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="'.$tmpkeyextra.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="'.$fieldid.'" value="'.$object->id.'">';
				print $extrafields->showInputField($tmpkeyextra, $value, '', '', '', 0, $object->id, $object->table_element);

				print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';

				print '</form>';
			} else {
				//var_dump($tmpkeyextra.'-'.$value.'-'.$object->table_element);
				print $extrafields->showOutputField($tmpkeyextra, $value, '', $object->table_element);
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
				    	function showOptions(child_list, parent_list)
				    	{
				    		var val = $("select[name="+parent_list+"]").val();
				    		var parentVal = parent_list + ":" + val;
							if(val > 0) {
					    		$("select[name=\""+child_list+"\"] option[parent]").hide();
					    		$("select[name=\""+child_list+"\"] option[parent=\""+parentVal+"\"]").show();
							} else {
								$("select[name=\""+child_list+"\"] option").show();
							}
				    	}
						function setListDependencies() {
					    	jQuery("select option[parent]").parent().each(function() {
					    		var child_list = $(this).attr("name");
								var parent = $(this).find("option[parent]:first").attr("parent");
								var infos = parent.split(":");
								var parent_list = infos[0];
								showOptions(child_list, parent_list);

								/* Activate the handler to call showOptions on each future change */
								$("select[name=\""+parent_list+"\"]").change(function() {
									showOptions(child_list, parent_list);
								});
					    	});
						}
						setListDependencies();
				    });
				</script>'."\n";
	}
}
?>
<!-- END PHP TEMPLATE extrafields_view.tpl.php -->
