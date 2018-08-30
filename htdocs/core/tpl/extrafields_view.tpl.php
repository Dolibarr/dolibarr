<?php
/* Copyright (C) 2014	Maxime Kohlhaas		<support@atm-consulting.fr>
 * Copyright (C) 2014	Juanjo Menent		<jmenent@2byte.es>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $conf
 * $langs
 *
 * $parameters
 * $cols
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}
if (! is_object($form)) $form=new Form($db);

?>
<!-- BEGIN PHP TEMPLATE extrafields_view.tpl.php -->
<?php

if (! is_array($parameters)) $parameters = array();
if (! empty($cols)) $parameters['colspan'] = ' colspan="'.$cols.'"';
if (! empty($cols)) $parameters['cols'] = $cols;
if (! empty($object->fk_soc)) $parameters['socid'] = $object->fk_soc;

$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
print $hookmanager->resPrint;
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

//var_dump($extrafields->attributes[$object->table_element]);
if (empty($reshook) && is_array($extrafields->attributes[$object->table_element]['label']))
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label)
	{
		// Discard if extrafield is a hidden field on form
		$enabled = 1;
		if ($enabled && isset($extrafields->attributes[$object->table_element]['enabled'][$key]))
		{
			$enabled = dol_eval($extrafields->attributes[$object->table_element]['enabled'][$key], 1);
		}
		if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$key]))
		{
			$enabled = dol_eval($extrafields->attributes[$object->table_element]['list'][$key], 1);
		}
		$perms = 1;
		if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$key]))
		{
			$perms = dol_eval($extrafields->attributes[$object->table_element]['perms'][$key], 1);
		}
		//print $key.'-'.$enabled.'-'.$perms.'-'.$label.$_POST["options_" . $key].'<br>'."\n";

		if (empty($enabled)) continue;	// 0 = Never visible field
		if (abs($enabled) != 1 && abs($enabled) != 3) continue;  // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
		if (empty($perms)) continue;    // 0 = Not visible

		// Load language if required
		if (! empty($extrafields->attributes[$object->table_element]['langfile'][$key])) $langs->load($extrafields->attributes[$object->table_element]['langfile'][$key]);

		if ($action == 'edit_extras')
		{
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $object->array_options["options_" . $key]);
		}
		else
		{
			$value = $object->array_options["options_" . $key];
		}
		if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate')
		{
			print $extrafields->showSeparator($key, $object);
		}
		else
		{
			print '<tr>';
			print '<td class="titlefield">';
			print '<table width="100%" class="nobordernopadding">';
			print '<tr>';
			print '<td';
			print ' class="';
			//var_dump($action);exit;
			if ((! empty($action) && ($action == 'create' || $action == 'edit')) && ! empty($extrafields->attributes[$object->table_element]['required'][$key])) print ' fieldrequired';
			print '">';
			if (! empty($extrafields->attributes[$object->table_element]['help'][$key])) print $form->textwithpicto($langs->trans($label), $extrafields->attributes[$object->table_element]['help'][$key]);
			else print $langs->trans($label);
			print '</td>';

			//TODO Improve element and rights detection
			//var_dump($user->rights);
			$permok=false;

			$keyforperm=$object->element;
			if ($object->element == 'fichinter') $keyforperm='ficheinter';
			if (isset($user->rights->$keyforperm)) $permok=$user->rights->$keyforperm->creer||$user->rights->$keyforperm->create||$user->rights->$keyforperm->write;

			if ($object->element=='order_supplier')   $permok=$user->rights->fournisseur->commande->creer;
			if ($object->element=='invoice_supplier') $permok=$user->rights->fournisseur->facture->creer;
			if ($object->element=='shipping')         $permok=$user->rights->expedition->creer;
			if ($object->element=='delivery')         $permok=$user->rights->expedition->livraison->creer;
			if ($object->element=='productlot')       $permok=$user->rights->stock->creer;
			if ($object->element=='facturerec') 	  $permok=$user->rights->facture->creer;

			if (($object->statut == 0 || ! empty($extrafields->attributes[$object->table_element]['alwayseditable'][$key]))
				&& $permok && ($action != 'edit_extras' || GETPOST('attribute') != $key)
			    && empty($extrafields->attributes[$object->table_element]['computed'][$key]))
			{
			    $fieldid='id';
			    if ($object->table_element == 'societe') $fieldid='socid';
				print '<td align="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?'.$fieldid.'=' . $object->id . '&action=edit_extras&attribute=' . $key . '">' . img_edit().'</a></td>';
			}
			print '</tr></table>';
			print '</td>';

			$html_id = !empty($object->id) ? $object->element.'_extras_'.$key.'_'.$object->id : '';
			print '<td id="'.$html_id.'" class="'.$object->element.'_extras_'.$key.'"'.($cols?' colspan="'.$cols.'"':'').'>';

			// Convert date into timestamp format
			if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('date','datetime')))
			{
				$datenotinstring = $object->array_options['options_' . $key];
				// print 'X'.$object->array_options['options_' . $key].'-'.$datenotinstring.'x';
				if (! is_numeric($object->array_options['options_' . $key]))	// For backward compatibility
				{
					$datenotinstring = $db->jdate($datenotinstring);
				}
				//print 'x'.$object->array_options['options_' . $key].'-'.$datenotinstring.' - '.dol_print_date($datenotinstring, 'dayhour');
				$value = isset($_POST["options_" . $key]) ? dol_mktime($_POST["options_" . $key . "hour"], $_POST["options_" . $key . "min"], 0, $_POST["options_" . $key . "month"], $_POST["options_" . $key . "day"], $_POST["options_" . $key . "year"]) : $datenotinstring;
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permok && GETPOST('attribute','none') == $key)
			{
			    $fieldid='id';
			    if ($object->table_element == 'societe') $fieldid='socid';

			    print '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="' . $key . '">';
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="'.$fieldid.'" value="' . $object->id . '">';

				print $extrafields->showInputField($key, $value, '', '', '', 0, $object->id);

				print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '">';

				print '</form>';
			}
			else
			{
				//print $key.'-'.$value.'-'.$object->table_element;
				print $extrafields->showOutputField($key, $value, '', $object->table_element);
			}
			print '</td>';
			print '</tr>' . "\n";
		}
	}


	// Add code to manage list depending on others
	// TODO Test/enhance this with a more generic solution
	if (! empty($conf->use_javascript_ajax))
	{
		print "\n";
		print '
				<script type="text/javascript">
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