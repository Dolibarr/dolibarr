<?php
/* Copyright (C) 2011   Stephen Larroque <lrq3000@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/customfields/conf/conf_customfields.lib.php
 *	\ingroup    others
 *	\brief          Contains all the configurable variables to expand the functionnalities of CustomFields
 *	\version    $Id: customfields.php, v1.2.2
 */

// **** EXPANSION VARIABLES ****
$fields_prefix = 'cf_'; // prefix that will be prepended to the variable name of a field for accessing the field's values

// $modulesarray contains the modules support and their associated contexts : keys = contexts and values = table_element (the name of the module in the database like llx_product, product is the table_element)
$modulesarray = array("invoicecard"=>"facture",
                                            "propalcard"=>"propal",
                                            "productcard"=>"product"); // Edit me to add the support of another module - NOTE: Lowercase only!

// Triggers to attach to commit actions
$triggersarray = array("bill_create"=>"facture"); // Edit me to add the support of actions of another module - NOTE: Lowercase only!

// Native SQL data types natively supported by CustomFields
// Edit me to add new data types to be supported in custom fields (then manage their output in forms in /htdocs/customfields/class/customfields.class.php in showOutputField() function and printField())
// sqldatatype => long_name_you_choose_to_show_to_user
$sql_datatypes = array( 'varchar' => $langs->trans("Textbox"),
                                             'text' => $langs->trans("Areabox"),
                                             'enum(\'Yes\',\'No\')' => $langs->trans("YesNoBox"),
                                             'boolean' => $langs->trans("TrueFalseBox"),
                                             'enum' => $langs->trans("DropdownBox"),
                                             'other' => $langs->trans("Other").'/'.$langs->trans("Constraint"),
                                                );
