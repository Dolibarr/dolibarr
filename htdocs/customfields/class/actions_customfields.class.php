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
 *      \file       htdocs/customfields/class/actions_customfields.class.php
 *      \ingroup    customfields
 *      \brief      Hook file for CustomFields to manage printing and editing in module's forms and datasheets
 *		\version    $Id: actions_customfields.class.php, v1.2.0
 *		\author		Stephen Larroque
 */

/**
 *      \class      actions_customfields
 *      \brief      Hook file for CustomFields to manage printing and editing in module's forms and datasheets
 */
class ActionsCustomFields // extends CommonObject
{

    /** Generic printing hook for the CustomFields module: call it with the right $printtype and it will do the rest for you!
     *  @param      printtype       'create'|'edit'
     *  @param      parameters  meta datas of the hook (context, etc...)
     *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     *  @param      action             current action (if set). Generally create or edit or null
     *  @return       void
     */
    function customfields_print_forms($printtype, $parameters, $object, $action) {
        global $conf, $user;
        // CustomFields : print fields at creation
        if ($conf->global->MAIN_MODULE_CUSTOMFIELDS) { // if the customfields module is activated...
            if (!is_object($parameters)) $parameters = (object)$parameters;

            if ($parameters->context == 'invoicecard' or $object->table_element == 'facture') {
                $currentmodule = 'facture';
                $idvar = 'facid';
                $rights = 'facture';
            }
            elseif ($parameters->context == 'propalcard' or $object->table_element == 'propal') {
                $currentmodule = 'propal'; // EDIT ME: var to edit for each module
                $idvar = 'id'; // EDIT ME: the name of the POST or GET variable that contains the id of the object (look at the URL for something like module.php?modid=3&... when you edit a field)
                $rights = 'propale'; // EDIT ME: try first to put it null, then if it doesn't work try to find the right name (search in the same file for something like $user->rights->modname where modname is the string you must put in $rights).
            }
            elseif ($parameters->context == 'productcard' or $object->table_element == 'product') {
                $currentmodule = 'product';
                $idvar = 'id';
                // We use different rights depending on the product type (product or service?)
                // we need to supply it in the $rights var because product module has not the same name in rights property
                if ($object->type == 0) {
                        $rights = 'produit';
                } elseif ($object->type == 1) {
                        $rights = 'service';
                }
            }

            include_once(DOL_DOCUMENT_ROOT.'/customfields/lib/customfields.lib.php');
            print '<br>';

            if ($printtype == 'create') {
                $action == 'edit' ?  $id = $object->id : $id = null; // If we are in a create form used to edit already instanciated fields, then we fetch the instanciated object by its id
                customfields_print_creation_form($currentmodule, $id);
            } else {
                customfields_print_main_form($currentmodule, $object, $action, $user, $idvar, $rights);
            }
        }
    }

    /** formObjectOptions is a function that is included in the datasheet of all the objects Dolibarr can handle (invoices, propales, products, services, and more soon I hope...)
     *   It is very useful if you want to include your own data in a datasheet.
     *
     */
    function formObjectOptions($parameters, $object, $action) {
        /* print_r($parameters);
            echo "action: ".$action;
            print_r($object); */

        if (!isset($object->element) or $action == 'create' or $action == 'edit') { // For the special case of edit (create form but used to edit parameters), this case is handled in the customfields lib and in the customfields_print_forms() function above (see $action == 'edit').
            $printtype = 'create';
        } else {
            $printtype = 'edit';
        }
        $this->customfields_print_forms($printtype, $parameters, $object, $action);
    }

}
