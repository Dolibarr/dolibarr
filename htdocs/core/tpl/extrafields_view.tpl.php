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

//$res = $object->fetch_optionals($object->id, $extralabels);
$parameters = array('colspan' => ' colspan="'.$cols.'"', 'cols' => $cols, 'socid' => $object->fk_soc);
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);

if (empty($reshook) && ! empty($extrafields->attribute_label))
{
	foreach ($extrafields->attribute_label as $key => $label)
	{
		if ($action == 'edit_extras')
		{
			$value = (isset($_POST ["options_" . $key]) ? $_POST ["options_" . $key] : $object->array_options ["options_" . $key]);
		}
		else
		{
			$value = $object->array_options ["options_" . $key];
		}
		if ($extrafields->attribute_type [$key] == 'separate')
		{
			print $extrafields->showSeparator($key);
		}
		else
		{
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td';
			if (! empty($extrafields->attribute_required [$key])) print ' class="fieldrequired"';
			print '>' . $label . '</td>';
			
			//TODO Improve element and rights detection
			if (($object->statut == 0 || $extrafields->attribute_alwayseditable[$key]) && ($object->element=='order_supplier'?$user->rights->fournisseur>commande:($object->element=='invoice_supplier'?$user->rights->fournisseur>facture:$user->rights->{$object->element}->creer)) && ($action != 'edit_extras' || GETPOST('attribute') != $key))
				print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_extras&attribute=' . $key . '">' . img_edit().'</a></td>';
			
			print '</tr></table>';
			print '<td colspan="5">';
			
			// Convert date into timestamp format
			if (in_array($extrafields->attribute_type [$key], array('date','datetime'))) {
				$value = isset($_POST ["options_" . $key]) ? dol_mktime($_POST ["options_" . $key . "hour"], $_POST ["options_" . $key . "min"], 0, $_POST ["options_" . $key . "month"], $_POST ["options_" . $key . "day"], $_POST ["options_" . $key . "year"]) : $db->jdate($object->array_options ['options_' . $key]);
			}
			
			//TODO Improve element and rights detection
			if ($action == 'edit_extras' && ($object->element=='order_supplier'?$user->rights->fournisseur>commande:($object->element=='invoice_supplier'?$user->rights->fournisseur>facture:$user->rights->{$object->element}->creer)) && GETPOST('attribute') == $key)
			{
				print '<form enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '" method="post" name="formextra">';
				print '<input type="hidden" name="action" value="update_extras">';
				print '<input type="hidden" name="attribute" value="' . $key . '">';
				print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
				print '<input type="hidden" name="id" value="' . $object->id . '">';

				print $extrafields->showInputField($key, $value);

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
