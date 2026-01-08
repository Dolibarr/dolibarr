<?php
/* Copyright (C) 2014	    Maxime Kohlhaas		    <support@atm-consulting.fr>
 * Copyright (C) 2014	    Juanjo Menent		    <jmenent@2byte.es>
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
 *
 * Need to have the following variables defined:
 * $object (invoice, order, ...)
 * $action
 * $db
 * $conf
 * $langs
 * $parameters
 * $thirdpartytopropagateextrafieldsfrom
 */

/**
 * @var CommonObject $object
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 *
 * @var Societe $thirdpartytopropagateextrafieldsfrom
 * @var string $action
 * @var array<string,mixed> $parameters
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

'
@phan-var-force CommonObject $object
@phan-var-force string $action
@phan-var-force DoliDB $db
@phan-var-force Conf $conf
@phan-var-force Translate $langs
@phan-var-force array<string,mixed> $parameters
@phan-var-force Societe $thirdpartytopropagateextrafieldsfrom
';

?>
<!-- BEGIN PHP TEMPLATE extrafields_add.tpl.php -->
<?php

// Other attributes
if (!isset($parameters)) {
	$parameters = array();
}

$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

print $hookmanager->resPrint;
if (empty($reshook)) {
	$params = array();
	$params['cols'] = array_key_exists('colspanvalue', $parameters) ? $parameters['colspanvalue'] : '';
	if (!empty($parameters['tdclass'])) {
		$params['tdclass'] = $parameters['tdclass'];
	}
	if (!empty($parameters['tpl_context'])) {
		$params['tpl_context'] = $parameters['tpl_context'];
	}

	// By default $thirdpartytopropagateextrafieldsfrom is not set.
	// We can have it set to a thirdparty object to propagate also the extrafields from thirdparty to object.
	if (!empty($thirdpartytopropagateextrafieldsfrom) && $thirdpartytopropagateextrafieldsfrom instanceof Societe && !empty($thirdpartytopropagateextrafieldsfrom->id)) {
		// copy from thirdparty
		$tpExtrafields = new ExtraFields($db);
		$tpExtrafieldLabels = $tpExtrafields->fetch_name_optionals_label($thirdpartytopropagateextrafieldsfrom->table_element);
		if ($thirdpartytopropagateextrafieldsfrom->fetch_optionals() > 0) {
			$object->array_options = array_merge($object->array_options, $thirdpartytopropagateextrafieldsfrom->array_options);
		}
	}

	print $object->showOptionals($extrafields, 'create', $params);
}

?>
<!-- END PHP TEMPLATE extrafields_add.tpl.php -->
