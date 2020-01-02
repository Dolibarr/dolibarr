<?php
/* Copyright (C) 2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       mo_production.php
 *		\ingroup    mrp
 *		\brief      Page to make production on a MO
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
dol_include_once('/mrp/class/mo.class.php');
dol_include_once('/mrp/lib/mrp_mo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mrp", "stocks", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'mocard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('mocard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'mrp', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = $user->rights->mrp->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->mrp->write; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->mrp->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->mrp->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];

$permissiontoproduce = $permissiontoadd;

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    $error = 0;

    $backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
    	//var_dump($backurlforlist);exit;
    	if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
    	else $backtopage = DOL_URL_ROOT.'/mrp/mo_production.php?id='.($id > 0 ? $id : '__ID__');
    }
    $triggermodname = 'MRP_MO_MODIFY'; // Name of trigger action code to execute when we modify record

    // Actions cancel, add, update, delete or clone
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Actions when linking object each other
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Actions to send emails
    $triggersendname = 'MO_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
    $trackid = 'mo'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

    // Action to move up and down lines of object
    //include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

    if ($action == 'set_thirdparty' && $permissiontoadd)
    {
    	$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'MO_MODIFY');
    }
    if ($action == 'classin' && $permissiontoadd)
    {
    	$object->setProject(GETPOST('projectid', 'int'));
    }
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans('Mo'), '');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';



// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	$head = moPrepareHead($object);
	dol_fiche_head($head, 'production', $langs->trans("MO"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion = array();
	    /*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
	    $formquestion = array(
	        // 'text' => $langs->trans("ConfirmClone"),
	        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
	        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
	        // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
        );
	    */
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);*/
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' : '.(is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	// Project
	if (!empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref .= '<br>'.$langs->trans('Project').' ';
	    if ($permissiontoadd)
	    {
	        if ($action != 'classify')
	            $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            if ($action == 'classify') {
            	//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
            	$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
	        }
	    } else {
	        if (!empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref .= $proj->getNomUrl();
	        } else {
	            $morehtmlref .= '';
	        }
	    }
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();


	if (! in_array($action, array('consume', 'produce', 'consumeandproduceall')))
	{
		print '<div class="tabsAction">';

		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
		if (empty($reshook)) {
			// Consume

			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consume">'.$langs->trans('Consume').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Consume').'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('Consume').'</a>';
			}

			// Produce
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=produce">'.$langs->trans('Produce').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Produce').'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('Produce').'</a>';
			}

			// ConsumeAndProduceAll
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consumeandproduceall">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
			}
		}

		print '</div>';
	}

	if (in_array($action, array('consume', 'produce', 'consumeandproduceall')))
	{
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="id" value="'.$id.'">';

		if ($action == 'consume')
		{
			print $langs->trans("FeatureNotYetAvailable");
		}
		if ($action == 'produce')
		{
			print $langs->trans("FeatureNotYetAvailable");
		}
		if ($action == 'consumeandproduceall')
		{
			$defaultstockmovementlabel = $langs->trans("ProductionForRefAndDate", $object->ref, dol_print_date(dol_now(), 'standard'));
			$defaultstockmovementcode = $object->ref.'_'.dol_print_date(dol_now(), 'dayhourlog');

			print '<div class="center">';
			print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ConfirmProductionDesc", $langs->transnoentitiesnoconv("Confirm")).'<br><br></span>';
			print $langs->trans("MovementLabel").': <input type="text" class="minwidth300" name="label" value="'.$defaultstockmovementlabel.'"> &nbsp; ';
			print $langs->trans("InventoryCode").': <input type="text" class="maxwidth150" name="inventorycode" value="'.$defaultstockmovementcode.'"> &nbsp; ';
			print '<input class="button" type="submit" value="'.$langs->trans("Confirm").'" name="confirm">';
			print ' &nbsp; ';
			print '<input class="button" type="submit" value="'.$langs->trans("Cancel").'" name="cancel">';
			print '</div>';
			print '<br>';
		}
	}


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
    	// Show object lines
    	//$result = $object->getLinesArray();
    	$object->fetchLines();

    	print '<div class="fichecenter">';
    	print '<div class="fichehalfleft">';
    	print '<div class="clearboth"></div>';

    	print load_fiche_titre($langs->trans('Consumption'), '', '');

    	print '<div class="div-table-responsive-no-min">';
    	print '<table id="tablelines" class="noborder noshadow" width="100%">';

    	print '<tr class="liste_titre">';
    	print '<td>'.$langs->trans("Product").'</td>';
    	print '<td>'.$langs->trans("Qty").'</td>';
    	print '<td>'.$langs->trans("QtyAlreadyConsumed").'</td>';
    	print '<td></td>';
    	if ($conf->productbatch->enabled) {
    		print '<td>';
    		if ($action == 'consumeandproduceall') print $langs->trans("Batch");
    		print '</td>';
    	}
    	print '</tr>';

    	if (!empty($object->lines))
    	{
    	    foreach($object->lines as $line) {
    	    	if ($line->role == 'toconsume') {
    	    		$tmpproduct = new Product($db);
    	    		$tmpproduct->fetch($line->fk_product);

    	    		print '<tr>';
    	    		print '<td>'.$tmpproduct->getNomUrl(1).'</td>';
    	    		print '<td>'.$line->qty.'</td>';
    	    		$alreadyconsumed = 0;
    	    		print '<td>'.$alreadyconsumed.'</td>';
    	    		print '<td></td>';	// Warehouse
    	    		if ($conf->productbatch->enabled) {
    	    			print '<td></td>';	// Lot
    	    		}
    	    		print '</tr>';

    	    		// Show detailed of already consumed with js code to collapse
    	    		//$arrayoflines = $line->fetchLinesLinked('consumed');

    	    		if ($action == 'consumeandproduceall') {
    	    			print '<tr>';
    	    			print '<td>'.$langs->trans("ToConsume").'</td>';
    	    			print '<td><input type="text" class="width50" name="qty-'.$line->id.'" value="'.max(0, $line->qty - $alreadyconsumed).'"></td>';
    	    			print '<td></td>';
    	    			print '<td>';
    	    			if ($tmpproduct->type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
    	    				print $formproduct->selectWarehouses('ifone', 'idwarehouse-'.$line->id, '', 1, 0, $line->fk_product, '', 1);
    	    			}
    	    			print '</td>';
    	    			if ($conf->productbatch->enabled) {
	    	    			print '<td>';
	    	    			if ($tmpproduct->status_batch) {
	    	    				print '<input type="text" class="width50" name="batch-'.$line->id.'" value="">';
	    	    			}
	    	    			print '</td>';
    	    			}
    	    			print '</tr>';
    	    		}
    	    	}
    	    }
    	}

    	/*if (!empty($object->lines))
    	{
    		$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, '/mrp/tpl');
    	}

    	// Form to add new line
    	if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
    	{
    	    if ($action != 'editline')
    	    {
    	        // Add products/services form
    	    	$object->formAddObjectLine(1, $mysoc, $soc, '/mrp/tpl');

    	        $parameters = array();
    	        $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    	    }
    	}*/

   	    print '</table>';
    	print '</div>';

		print '</div>';
    	print '<div class="fichehalfright">';
    	print '<div class="clearboth"></div>';

    	print load_fiche_titre($langs->trans('Production'), '', '');

    	print '<div class="div-table-responsive-no-min">';
    	print '<table id="tablelines" class="noborder noshadow" width="100%">';

    	print '<tr class="liste_titre">';
    	print '<td>'.$langs->trans("Product").'</td>';
    	print '<td>'.$langs->trans("Qty").'</td>';
    	print '<td>'.$langs->trans("QtyAlreadyProduced").'</td>';
    	print '<td></td>';
    	if ($conf->productbatch->enabled) {
    		print '<td>';
    		if ($action == 'consumeandproduceall') print $langs->trans("Batch");
    		print '</td>';
    	}
    	print '</tr>';

    	if (!empty($object->lines))
    	{
    		foreach($object->lines as $line) {
    			if ($line->role == 'toproduce') {
    				print '<tr>';
    				$tmpproduct = new Product($db);
    				$tmpproduct->fetch($line->fk_product);
    				print '<td>'.$tmpproduct->getNomUrl(1).'</td>';
    				print '<td>'.$line->qty.'</td>';
    				$alreadyproduced = 0;
    				print '<td>'.$alreadyproduced.'</td>';
    				print '<td></td>';	// Warehouse
    				if ($conf->productbatch->enabled) {
    					print '<td></td>';	// Lot
    				}
    				print '</tr>';

    				// Show detailed of already produced
    				//$arrayoflines = $line->fetchLinesLinked('produced');

    				if ($action == 'consumeandproduceall') {
    					print '<tr>';
    					print '<td>'.$langs->trans("ToProduce").'</td>';
    					print '<td><input type="text" class="width50" name="qty-'.$line->id.'" value="'.max(0, $line->qty - $alreadyproduced).'"></td>';
    					print '<td></td>';
    					print '<td>';
    					if ($tmpproduct->type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
    						print $formproduct->selectWarehouses('ifone', 'idwarehouse-'.$line->id, '', 1, 0, $line->fk_product, '', 1);
    					}
    					print '</td>';
    					if ($conf->productbatch->enabled) {
    						print '<td>';
    						if ($tmpproduct->status_batch) {
    							print '<input type="text" class="width50" name="batch-'.$line->id.'" value="">';
    						}
    						print '</td>';
    					}
    					print '</tr>';
    				}
    			}
    		}
    	}

    	print '</table>';
    	print '</div>';

    	print '</div>';
    	print '</div>';
	}

	if (in_array($action, array('consume', 'produce', 'consumeandproduceall')))
	{
		print "</form>\n";
	}
}

// End of page
llxFooter();
$db->close();
