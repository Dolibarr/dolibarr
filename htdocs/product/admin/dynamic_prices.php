<?php
/* Copyright (C) 2015	  Ion Agorria		  <ion@agorria.com>
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
 */

/**
 *  \file		htdocs/product/admin/dynamic_prices.php
 *  \ingroup	product
 *  \brief		Page for configuring dynamic prices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_global_variable.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_global_variable_updater.class.php';

// Load translation files required by the page
$langs->load("products");

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$save = GETPOST('save', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$selection = GETPOST('selection', 'int');

// Security check
if (!$user->admin) accessforbidden();

//Objects
$price_globals = new PriceGlobalVariable($db);
if ($action == 'edit_variable') {
    $res = $price_globals->fetch($selection);
    if ($res < 1) {
        setEventMessages($price_globals->error, $price_globals->errors, 'errors');
    }
}
$price_updaters = new PriceGlobalVariableUpdater($db);
if ($action == 'edit_updater') {
    $res = $price_updaters->fetch($selection);
    if ($res < 1) {
        setEventMessages($price_updaters->error, $price_updaters->errors, 'errors');
    }
}


/*
 * Actions
 */

if (!empty($action) && empty($cancel)) {
    //Global variable actions
    if ($action == 'create_variable' || $action == 'edit_variable') {
        $price_globals->code = isset($_POST['code'])?GETPOST('code', 'alpha'):$price_globals->code;
        $price_globals->description = isset($_POST['description'])?GETPOST('description', 'alpha'):$price_globals->description;
        $price_globals->value = isset($_POST['value'])?GETPOST('value', 'int'):$price_globals->value;
        //Check if record already exists only when saving
        if (!empty($save)) {
            foreach ($price_globals->listGlobalVariables() as $entry) {
                if ($price_globals->id != $entry->id && dol_strtolower($price_globals->code) == dol_strtolower($entry->code)) {
                    setEventMessages($langs->trans("ErrorRecordAlreadyExists"), null, 'errors');
                    $save = null;
                }
            }
        }
    }
    if ($action == 'create_variable' && !empty($save)) {
        $res = $price_globals->create($user);
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_globals->error, $price_globals->errors, 'errors');
        }
    } elseif ($action == 'edit_variable' && !empty($save)) {
        $res = $price_globals->update($user);
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_globals->error, $price_globals->errors, 'errors');
        }
    } elseif ($action == 'delete_variable') {
        $res = $price_globals->delete($selection, $user);
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_globals->error, $price_globals->errors, 'errors');
        }
    }

    //Updaters actions
    if ($action == 'create_updater' || $action == 'edit_updater') {
        $price_updaters->type = isset($_POST['type'])?GETPOST('type', 'int'):$price_updaters->type;
        $price_updaters->description = isset($_POST['description'])?GETPOST('description', 'alpha'):$price_updaters->description;
        $price_updaters->parameters = isset($_POST['parameters'])?GETPOST('parameters'):$price_updaters->parameters;
        $price_updaters->fk_variable = isset($_POST['fk_variable'])?GETPOST('fk_variable', 'int'):$price_updaters->fk_variable;
        $price_updaters->update_interval = isset($_POST['update_interval'])?GETPOST('update_interval', 'int'):$price_updaters->update_interval;
    }
    if ($action == 'create_updater' && !empty($save)) {
        //Verify if process() works
        $res = $price_updaters->process();
        if ($res > 0) {
            $res = $price_updaters->create($user);
        }
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_updaters->error, $price_updaters->errors, 'errors');
        }
    } elseif ($action == 'edit_updater' && !empty($save)) {
        //Verify if process() works
        $res = $price_updaters->process();
        if ($res > 0) {
            $res = $price_updaters->update($user);
        }
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_updaters->error, $price_updaters->errors, 'errors');
        }
    } elseif ($action == 'delete_updater') {
        $res = $price_updaters->delete($selection, $user);
        if ($res > 0) {
            $action = '';
        } else {
            setEventMessages($price_updaters->error, $price_updaters->errors, 'errors');
        }
    }
} elseif (!empty($cancel)) {
    $action = '';
}


/*
 * View
 */

$form = new Form($db);

llxHeader("","",$langs->trans("CardProduct".$product->type));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DynamicPriceConfiguration"), $linkback, 'title_setup');

print $langs->trans("DynamicPriceDesc").'<br>';
print '<br>';

//Global variables table
if ($action != 'create_updater' && $action != 'edit_updater') {
    print $langs->trans("GlobalVariables");
    print '<table summary="listofattributes" class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Variable").'</td>';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print '<td width="80">&nbsp;</td>'; //Space for buttons
    print '</tr>';

    $arrayglobalvars=$price_globals->listGlobalVariables();
    if (! empty($arrayglobalvars))
    {
	    foreach ($arrayglobalvars as $i=>$entry) {
	        $var = !$var;
	        print '<tr class="oddeven">';
	        print '<td>'.$entry->code.'</td>';
	        print '<td>'.$entry->description.'</td>';
	        print '<td>'.price($entry->value).'</td>';
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit_variable&selection='.$entry->id.'">'.img_edit().'</a> &nbsp;';
	        print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_variable&selection='.$entry->id.'">'.img_delete().'</a></td>';
	        print '</tr>';
	    }
    }
    else
    {
    	print '<tr colspan="7"><td class="opacitymedium">';
    	print $langs->trans("None");
    	print '</td></tr>';
    }
    print '</table>';

    if (empty($action))
    {
        //Action Buttons
        print '<div class="tabsAction">';
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create_variable">'.$langs->trans("AddVariable").'</a>';
        print '</div>';
        //Separator is only need for updaters table is showed after buttons
        print '<br><br>';
    }
}

//Global variable editor
if ($action == 'create_variable' || $action == 'edit_variable') {
    //Form
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="'.$action.'">';
    print '<input type="hidden" name="selection" value="'.$selection.'">';

    //Table
    print '<br><table summary="listofattributes" class="border centpercent">';
    //Code
    print '<tr>';
    print '<td class="fieldrequired">'.$langs->trans("Variable").'</td>';
    print '<td class="valeur"><input type="text" name="code" size="20" value="'.(empty($price_globals->code)?'':$price_globals->code).'"></td>';
    print '</tr>';
    //Description
    print '<tr>';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td class="valeur"><input type="text" name="description" size="50" value="'.(empty($price_globals->description)?'':$price_globals->description).'"></td>';
    print '</tr>';
    //Value
    print '<tr>';
    print '<td class="fieldrequired">'.$langs->trans("Value").'</td>';
    print '<td class="valeur"><input type="text" name="value" size="10" value="'.(empty($price_globals->value)?'':$price_globals->value).'"></td>';
    print '</tr>';
    print '</table>';

    //Form Buttons
    print '<br><div align="center">';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp;';
    print '<input type="submit" class="button" name="cancel" id="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';
    print '</form>';
}

// Updaters table
if ($action != 'create_variable' && $action != 'edit_variable') {
    print $langs->trans("GlobalVariableUpdaters");
    print '<table summary="listofattributes" class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("VariableToUpdate").'</td>';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td>'.$langs->trans("Parameters").'</td>';
    print '<td>'.$langs->trans("UpdateInterval").'</td>';
    print '<td>'.$langs->trans("LastUpdated").'</td>';
    print '<td width="80">&nbsp;</td>'; //Space for buttons
    print '</tr>';

    $arraypriceupdaters = $price_updaters->listUpdaters();
    if (! empty($arraypriceupdaters))
    {
	    foreach ($arraypriceupdaters as $i=>$entry) {
	        $code = "";
	        if ($entry->fk_variable > 0) {
	            $res = $price_globals->fetch($entry->fk_variable);
	            if ($res > 0) {
	                $code = $price_globals->code;
	            }
	        }
	        print '<tr>';
	        print '<td>'.$code.'</td>';
	        print '<td>'.$entry->description.'</td>';
	        print '<td>'.$langs->trans("GlobalVariableUpdaterType".$entry->type).'</td>';
	        print '<td style="max-width: 250px; word-wrap: break-word; white-space: pre-wrap;">'.$entry->parameters.'</td>';
	        print '<td>'.$entry->update_interval.'</td>';
	        print '<td>'.$entry->getLastUpdated().'</td>';
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit_updater&selection='.$entry->id.'">'.img_edit().'</a> &nbsp;';
	        print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete_updater&selection='.$entry->id.'">'.img_delete().'</a></td>';
	        print '</tr>';
	    }
    }
    else
    {
    	print '<tr colspan="7"><td class="opacitymedium">';
    	print $langs->trans("None");
    	print '</td></tr>';
    }
    print '</table>';

    if (empty($action))
    {
        //Action Buttons
        print '<div class="tabsAction">';
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create_updater">'.$langs->trans("AddUpdater").'</a>';
        print '</div>';
    }
}

//Updater editor
if ($action == 'create_updater' || $action == 'edit_updater') {
    //Form
    print '<form id="updaterform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="'.$action.'">';
    print '<input type="hidden" name="selection" value="'.$selection.'">';

    //Table
    print '<br><table summary="listofattributes" class="border centpercent">';
    //Code
    print '<tr>';
    print '<td class="fieldrequired">'.$langs->trans("VariableToUpdate").'</td><td>';
    $globals_list = array();
    foreach ($price_globals->listGlobalVariables() as $entry) {
        $globals_list[$entry->id]=$entry->code;
    }
    print $form->selectarray('fk_variable', $globals_list, (empty($price_updaters->fk_variable)?0:$price_updaters->fk_variable));
    print '</td></tr>';
    //Description
    print '<tr>';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td class="valeur"><input type="text" name="description" size="50" value="'.(empty($price_updaters->description)?'':$price_updaters->description).'"></td>';
    print '</tr>';
    //Type
    print '<tr>';
    print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
    $type = empty($price_updaters->type)?0:$price_updaters->type;
    $type_list = array();
    foreach ($price_updaters->types as $val) {
        $type_list[$val] = $langs->trans("GlobalVariableUpdaterType".$val);
    }
    print $form->selectarray('type', $type_list, $type);
    // This code submits form when type is changed
    print '<script type="text/javascript">
        jQuery(document).ready(run);
        function run() {
            jQuery("#type").change(on_change);
        }
        function on_change() {
            jQuery("#updaterform").submit();
        }
    </script>';
    print '</td></tr>';
    //Parameters
    print '<tr>';
    $help = $langs->trans("GlobalVariableUpdaterHelp".$type).'<br><b>'.$langs->trans("GlobalVariableUpdaterHelpFormat".$type).'</b>';
    print '<td class="fieldrequired">'.$form->textwithpicto($langs->trans("Parameters"),$help,1).'</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('parameters',empty($price_updaters->parameters)?'':$price_updaters->parameters,'',300,'','',false,false,false,ROWS_8,'90%');
    $doleditor->Create();
    print '</td></tr>';
    print '</tr>';
    //Interval
    print '<tr>';
    print '<td class="fieldrequired">'.$langs->trans("UpdateInterval").'</td>';
    print '<td class="valeur"><input type="text" name="update_interval" size="10" value="'.(empty($price_updaters->update_interval)?'':$price_updaters->update_interval).'"></td>';
    print '</tr>';
    print '</table>';

    //Form Buttons
    print '<br><div align="center">';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp;';
    print '<input type="submit" class="button" name="cancel" id="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';
    print '</form>';
}

// End of page
llxFooter();
$db->close();
