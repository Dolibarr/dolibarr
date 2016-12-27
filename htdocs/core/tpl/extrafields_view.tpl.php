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
 * $conf
 * $langs
 *
 * $cols
 */
?>
<!-- BEGIN PHP TEMPLATE admin_extrafields_view.tpl.php -->
<?php

//$res = $object->fetch_optionals($object->id, $extralabels);
$parameters = array('colspan' => ' colspan="'.$cols.'"', 'cols' => $cols, 'socid' => $object->fk_soc);
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);

if (empty($reshook) && ! empty($extrafields->attribute_label))
{
	foreach ($extrafields->attribute_label as $key => $label)
	{
		if ($action == 'edit_extras')
		{
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $object->array_options["options_" . $key]);
		}
		else
		{
			$value = $object->array_options["options_" . $key];
		}
		if ($extrafields->attribute_type[$key] == 'separate')
		{
			print $extrafields->showSeparator($key);
		}
		else
		{
			if (!empty($extrafields->attribute_hidden[$key])) print '<tr class="hideobject"><td>';
			else print '<tr><td>';
			print '<table width="100%" class="nobordernopadding">';
			print '<tr>';
			print '<td';
			//var_dump($action);exit;
			if ((! empty($action) && ($action == 'create' || $action == 'edit')) && ! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
			print '>' . $langs->trans($label) . '</td>';

			//TODO Improve element and rights detection
			//var_dump($user->rights);
			$permok=false;
			$keyforperm=$object->element;
			if ($object->element == 'fichinter') $keyforperm='ficheinter';
			if (isset($user->rights->$keyforperm)) $permok=$user->rights->$keyforperm->creer||$user->rights->$keyforperm->create||$user->rights->$keyforperm->write;
			if ($object->element=='order_supplier') $permok=$user->rights->fournisseur->commande->creer;
			if ($object->element=='invoice_supplier') $permok=$user->rights->fournisseur->facture->creer;
			if ($object->element=='shipping') $permok=$user->rights->expedition->creer;
			if ($object->element=='delivery') $permok=$user->rights->expedition->livraison->creer;
			if ($object->element=='productlot') $permok=$user->rights->stock->creer;

			if (($object->statut == 0 || $extrafields->attribute_alwayseditable[$key])
				&& $permok && ($action != 'edit_extras' || GETPOST('attribute') != $key))
				print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_extras&attribute=' . $key . '">' . img_edit().'</a></td>';

			print '</tr></table>';
			$html_id = !empty($object->id) ? $object->element.'_extras_'.$key.'_'.$object->id : '';
			print '<td id="'.$html_id.'" class="'.$object->element.'_extras_'.$key.'" colspan="'.$cols.'">';

			// Convert date into timestamp format
			if (in_array($extrafields->attribute_type[$key], array('date','datetime'))) {
				$value = isset($_POST["options_" . $key]) ? dol_mktime($_POST["options_" . $key . "hour"], $_POST["options_" . $key . "min"], 0, $_POST["options_" . $key . "month"], $_POST["options_" . $key . "day"], $_POST["options_" . $key . "year"]) : $db->jdate($object->array_options['options_' . $key]);
			}

			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && $permok && GETPOST('attribute') == $key)
			{
				print '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="' . $key . '">';
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="id" value="' . $object->id . '">';

				print $extrafields->showInputField($key, $value,'','','',0,$object->id);

				print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';

				print '</form>';
			}
			else
			{
				print $extrafields->showOutputField($key, $value);
			}
			print '</td></tr>' . "\n";
		}
	}
}
?>
<!-- END PHP TEMPLATE admin_extrafields_view.tpl.php -->