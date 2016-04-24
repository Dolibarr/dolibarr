<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *	\file       htdocs/compta/facture/fiche-rec.php
 *	\ingroup    facture
 *	\brief      Page to show predefined invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load('bills');
$langs->load('compta');

// Security check
$id=(GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int'));
$ref=GETPOST('ref','alpha');
$action=GETPOST('action', 'alpha');
if ($user->societe_id) $socid=$user->societe_id;
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") $objecttype = '';
$result = restrictedArea($user, 'facture', $id, $objecttype);

if ($page == -1)
{
	$page = 0 ;
}
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page ;

if ($sortorder == "")
$sortorder="DESC";

if ($sortfield == "")
$sortfield="f.datef";

$object = new FactureRec($db);
if (($id > 0 || $ref) && $action != 'create' && $action != 'add')
{
	$ret = $object->fetch($id, $ref);
	if (!$ret)
	{
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	}
}

/*
 * Actions
 */


// Create predefined invoice
if ($action == 'add')
{
	if (! GETPOST('titre'))
	{
		setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("Title")), null, 'errors');
		$action = "create";
		$error++;
	}

	$frequency=GETPOST('frequency', 'int');
	$reyear=GETPOST('reyear');
	$remonth=GETPOST('remonth');
	$reday=GETPOST('reday');
	$rehour=GETPOST('rehour');
	$remin=GETPOST('remin');
	$nb_gen_max=GETPOST('nb_gen_max', 'int');
	if (empty($nb_gen_max)) $nb_gen_max =0;
	
	if (GETPOST('frequency'))
	{
		if (empty($reyear) || empty($remonth) || empty($reday)) 
		{
			setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("Date")), null, 'errors');
			$action = "create";
			$error++;	
		}
		if ($nb_gen_max == '')
		{
			setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("MaxPeriodNumber")), null, 'errors');
			$action = "create";
			$error++;
		}
	}

	if (! $error)
	{
		$object->titre = GETPOST('titre', 'alpha');
		$object->note_private  = GETPOST('note_private');
		$object->usenewprice = GETPOST('usenewprice');
		
		$object->frequency = $frequency;
		$object->unit_frequency = GETPOST('unit_frequency', 'alpha');
		$object->nb_gen_max = $nb_gen_max;
		$object->auto_validate = GETPOST('auto_validate', 'int');
		
		$date_next_execution = dol_mktime($rehour, $remin, 0, $remonth, $reday, $reyear);
		$object->date_when = $date_next_execution;

		// Get first contract linked to invoice used to generate template
		if ($id > 0)
		{
            $srcObject = new Facture($db);
            $srcObject->fetch(GETPOST('facid','int'));
            
            $srcObject->fetchObjectLinked();
            
            if (! empty($srcObject->linkedObjectsIds['contrat']))
            {
                $contractidid = reset($srcObject->linkedObjectsIds['contrat']);

                $object->origin = 'contrat';
                $object->origin_id = $contractidid;
                $object->linked_objects[$object->origin] = $object->origin_id;
            }
		}
		
		$db->begin();

		$oldinvoice = new Facture($db);
		$oldinvoice->fetch($id);
		
		$result = $object->create($user, $oldinvoice->id);
		if ($result > 0)
		{
			$result=$oldinvoice->delete(0, 1);
			if ($result < 0)
			{
			    $error++;
    		    setEventMessages($oldinvoice->error, $oldinvoice->errors, 'errors');
    		    $action = "create";
			}
		}
		else
		{
		    $error++;
		    setEventMessages($object->error, $object->errors, 'errors');
		    $action = "create";
		}
			
		if (! $error)
		{
			$db->commit();
			
			header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
   			exit;
		}
		else
		{
		    $db->rollback();
		    
		    $error++;
			setEventMessages($object->error, $object->errors, 'errors');
			$action = "create";
		}
	}
}

// Delete
if ($action == 'delete' && $user->rights->facture->supprimer)
{
	$object->delete();
	header("Location: " . $_SERVER['PHP_SELF'] );
	exit;
}


// Update field
// Set condition
if ($action == 'setconditions' && $user->rights->facture->creer)
{
	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));

}
// Set mode
elseif ($action == 'setmode' && $user->rights->facture->creer)
{
	$result=$object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
}
// Set project
elseif ($action == 'classin' && $user->rights->facture->creer)
{
	$object->setProject(GETPOST('projectid', 'int'));
}
// Set bank account
elseif ($action == 'setbankaccount' && $user->rights->facture->creer)
{
    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
}
// Set frequency and unit frequency
elseif ($action == 'setfrequency' && $user->rights->facture->creer)
{
	$object->setFrequencyAndUnit(GETPOST('frequency', 'int'), GETPOST('unit_frequency', 'alpha'));
}
// Set next date of execution
elseif ($action == 'setdate_when' && $user->rights->facture->creer)
{
	$date = dol_mktime(GETPOST('date_whenhour'), GETPOST('date_whenmin'), 0, GETPOST('date_whenmonth'), GETPOST('date_whenday'), GETPOST('date_whenyear'));
	if (!empty($date)) $object->setNextDate($date);
}
// Set max period
elseif ($action == 'setnb_gen_max' && $user->rights->facture->creer)
{
	$object->setMaxPeriod(GETPOST('nb_gen_max', 'int'));
}
// Set auto validate
elseif ($action == 'setauto_validate' && $user->rights->facture->creer)
{
	$object->setAutoValidate(GETPOST('auto_validate', 'int'));
}
// Set note
$permissionnote=$user->rights->facture->creer;	// Used by the include of actions_setnotes.inc.php
include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$form = new Form($db);
$companystatic = new Societe($db);

$now = dol_now();
$tmparray=dol_getdate($now);
$today = dol_mktime(23,59,59,$tmparray['mon'],$tmparray['mday'],$tmparray['year']);   // Today is last second of current day
	  

/*
 * Create mode
 */
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("CreateRepeatableInvoice"),'','title_accountancy.png');

	$object = new Facture($db);   // Source invoice
	$product_static = new Product($db);

	if ($object->fetch($id, $ref) > 0)
	{
		print '<form action="fiche-rec.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$object->id.'">';

		dol_fiche_head();

		$rowspan=4;
		if (! empty($conf->projet->enabled)) $rowspan++;
		if ($object->fk_account > 0) $rowspan++;

		print '<table class="border" width="100%">';

		$object->fetch_thirdparty();

		// Third party
		print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$object->thirdparty->getNomUrl(1,'customer').'</td>';
		print '<td>';
		print $langs->trans("Comment");
		print '</td></tr>';

		// Title
		print '<tr><td class="fieldrequired">'.$langs->trans("Title").'</td><td>';
		print '<input class="flat" type="text" name="titre" size="24" value="'.$_POST["titre"].'">';
		print '</td>';

		// Note
		print '<td rowspan="'.$rowspan.'" valign="top">';
		print '<textarea class="flat centpercent" name="note_private" wrap="soft" rows="'.ROWS_4.'"></textarea>';
		print '</td></tr>';

		// Author
		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		// Payment term
		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
		print "</td></tr>";

		// Payment mode
		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		print "</td></tr>";

		// Project
		if (! empty($conf->projet->enabled))
		{
			print "<tr><td>".$langs->trans("Project")."</td><td>";
			if ($object->fk_project > 0)
			{
				$project = new Project($db);
				$project->fetch($object->fk_project);
				print $project->getNomUrl(1);
			}
			print "</td></tr>";
		}

		// Bank account
		if ($object->fk_account > 0)
		{
			print "<tr><td>".$langs->trans('BankAccount')."</td><td>";
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			print "</td></tr>";
		}

		print "</table>";

		print '<br><br>';

		
		// Autogeneration
		$title = $langs->trans("Recurrence");
		print load_fiche_titre($title, '', 'calendar');
		
		print '<table class="border" width="100%">';
		
		// Frequency
		print "<tr><td>".$form->textwithpicto($langs->trans("Frequency"), $langs->transnoentitiesnoconv('toolTipFrequency'))."</td><td>";
		print "<input type='text' name='frequency' value='".GETPOST('frequency', 'int')."' size='5' />&nbsp;".$form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), (GETPOST('unit_frequency')?GETPOST('unit_frequency'):'m'));
		print "</td></tr>";
		
		// First date of execution for cron
		print "<tr><td>".$langs->trans('NextDateToExecution')."</td><td>";
		$date_next_execution = isset($date_next_execution) ? $date_next_execution : (GETPOST('remonth') ? dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear')) : -1);
		print $form->select_date($date_next_execution, '', 1, 1, '', "add", 1, 1, 1);
		print "</td></tr>";
		
		// Number max of generation
		print "<tr><td>".$langs->trans("MaxPeriodNumber")."</td><td>";
		print '<input type="text" name="nb_gen_max" value="'.GETPOST('nb_gen_max').'" size="5" />';
		print "</td></tr>";

		// Auto validate the invoice
		print "<tr><td>".$langs->trans("StatusOfGeneratedInvoices")."</td><td>";
        $select = array('0'=>$langs->trans('BillStatusDraft'),'1'=>$langs->trans('BillStatusValidated'));
        print $form->selectarray('auto_validate', $select, GETPOST('auto_validate'));
		print "</td></tr>";

		print "</table>";

		print '<br><br>';

		$title = $langs->trans("ProductsAndServices");
		if (empty($conf->service->enabled))
			$title = $langs->trans("Products");
		else if (empty($conf->product->enabled))
			$title = $langs->trans("Services");

		print load_fiche_titre($title, '', '');

		/*
		 * Invoice lines
		 */
		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td colspan="3">';

		$sql = 'SELECT l.fk_product, l.product_type, l.label as custom_label, l.description, l.qty, l.rowid, l.tva_tx,';
		$sql.= ' l.fk_remise_except,';
		$sql.= ' l.remise_percent, l.subprice, l.info_bits,';
		$sql.= ' l.total_ht, l.total_tva as total_vat, l.total_ttc,';
		$sql.= ' l.date_start,';
		$sql.= ' l.date_end,';
		$sql.= ' l.product_type,';
		$sql.= ' l.fk_unit,';
		$sql.= ' p.ref, p.fk_product_type, p.label as product_label,';
		$sql.= ' p.description as product_desc';
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as l";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
		$sql.= " WHERE l.fk_facture = ".$object->id;
		$sql.= " ORDER BY l.rowid";

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0; $total = 0;

			echo '<table class="noborder" width="100%">';
			if ($num)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="center">'.$langs->trans("VAT").'</td>';
				print '<td align="center">'.$langs->trans("Qty").'</td>';
				if ($conf->global->PRODUCT_USE_UNITS) {
					print '<td width="8%" align="left">'.$langs->trans("Unit").'</td>';
				}
				print '<td>'.$langs->trans("ReductionShort").'</td>';
				print '<td align="right">'.$langs->trans("TotalHT").'</td>';
				print '<td align="right">'.$langs->trans("TotalVAT").'</td>';
				print '<td align="right">'.$langs->trans("TotalTTC").'</td>';
				print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
				if (empty($conf->global->PRODUIT_MULTIPRICES)) print '<td align="right">'.$langs->trans("CurrentProductPrice").'</td>';
				print "</tr>\n";
			}
			$var=true;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				if ($objp->fk_product > 0)
				{
					$product = New Product($db);
					$product->fetch($objp->fk_product);
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";

				// Show product and description
				$type=(isset($objp->product_type)?$objp->product_type:$objp->fk_product_type);
				$product_static->fk_unit=$objp->fk_unit;

				if ($objp->fk_product > 0)
				{
					print '<td>';

					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->fetch($objp->fk_product);	// We need all information later
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.(! empty($objp->custom_label)?$objp->custom_label:$objp->product_label);
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
						print (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';

					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($objp->custom_label)) {

						$text.= ' <strong>'.$objp->custom_label.'</strong>';
						print $form->textwithtooltip($text,dol_htmlentitiesbr($objp->description),3,'','',$i);

					} else {

						print $text.' '.nl2br($objp->description);
					}

					// Show range
					print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));

					print "</td>\n";
				}

				// Vat rate
				print '<td align="center">'.vatrate($objp->tva_tx).'%</td>';

				// Qty
				print '<td align="center">'.$objp->qty.'</td>';

				if ($conf->global->PRODUCT_USE_UNITS) {
					print '<td align="left">'.$product_static->getLabelOfUnit().'</td>';
				}

				// Percent
				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent." %</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

				// Total HT
				print '<td align="right">'.price($objp->total_ht)."</td>\n";

				// Total VAT
				print '<td align="right">'.price($objp->total_vat)."</td>\n";

				// Total TTC
				print '<td align="right">'.price($objp->total_ttc)."</td>\n";

				// Total Unit price
				print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Current price of product
				if (empty($conf->global->PRODUIT_MULTIPRICES))
				{
					if ($objp->fk_product > 0)
					{
						$flag_price_may_change++;
						$prodprice=$product_static->price;	// price HT
						print '<td align="right">'.price($prodprice)."</td>\n";
					}
					else
					{
						print '<td>&nbsp;</td>';
					}
				}

				print "</tr>";

				$i++;
			}

			$db->free($result);

		}
		else
		{
			print $db->error();
		}
		print "</table>";

		print '</td></tr>';

		if ($flag_price_may_change)
		{
			print '<tr><td colspan="3" align="left">';
			print '<select name="usenewprice" class="flat">';
			print '<option value="0">'.$langs->trans("AlwaysUseFixedPrice").'</option>';
			print '<option value="1" disabled>'.$langs->trans("AlwaysUseNewPrice").'</option>';
			print '</select>';
			print '</td></tr>';
		}
		print "</table>\n";

        dol_fiche_end();

		print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	    print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
        print '</div>';
		print "</form>\n";
	}
	else
	{
		dol_print_error('',"Error, no invoice ".$object->id);
	}
}
else
{
	/*
	 * View mode
	 */
	if ($object->id > 0)
	{
		$object->fetch_thirdparty();

		$author = new User($db);
		$author->fetch($object->user_author);

		$head=array();
		$h=0;
		$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$object->id;
		$head[$h][1] = $langs->trans("CardBill");
		$head[$h][2] = 'card';

		dol_fiche_head($head, 'card', $langs->trans("RepeatableInvoice"),0,'bill');	// Add a div

		print '<table class="border" width="100%">';

		$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/fiche-rec.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		// Ref
		print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
		print '<td colspan="5">';
		$morehtmlref = '';
		/*
		require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
        $discount = new DiscountAbsolute($db);
		$result = $discount->fetch(0, $object->id);
		if ($result > 0) {
		    $morehtmlref = ' (' . $langs->trans("CreditNoteConvertedIntoDiscount", $discount->getNomUrl(1, 'discount')) . ')';
		}
		if ($result < 0) {
		    dol_print_error('', $discount->error);
		}*/
		print $form->showrefnav($object, 'ref', $linkback, 1, 'titre', 'titre', $morehtmlref);
		print '</td></tr>';
		
		
		print '<tr><td>'.$langs->trans("Customer").'</td>';
		print '<td colspan="3">'.$object->thirdparty->getNomUrl(1,'customer').'</td></tr>';

		print "<tr><td>".$langs->trans("Author").'</td><td colspan="3">'.$author->getFullName($langs)."</td></tr>";

		print '<tr><td>'.$langs->trans("AmountHT").'</td>';
		print '<td colspan="3">'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("AmountVAT").'</td><td colspan="3">'.price($object->total_tva,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';
		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="3">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';

		// Payment term
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editconditions' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($object->type != Facture::TYPE_CREDIT_NOTE)
		{
			if ($action == 'editconditions') {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id');
			} else {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'none');
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Payment mode
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmode&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetMode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editmode')
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT');
		}
		else
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'none', 'CRDT');
		}
		print '</td></tr>';

		
		print '<tr><td>';
		print $form->editfieldkey($langs->trans("NotePrivate"), 'note_private', $object->note_private, $object, $user->rights->facture->creer);
		print '</td><td colspan="5">';
		print $form->editfieldval($langs->trans("NotePrivate"), 'note_private', $object->note_private, $object, $user->rights->facture->creer, 'textarea:'.ROWS_4.':60');
		print '</td>';
		print '</tr>';
		
		// Project
		if (! empty($conf->projet->enabled)) {
			$langs->load('projects');
			print '<tr>';
			print '<td>';
	
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Project');
			print '</td>';
			if ($action != 'classify') {
				print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=classify&amp;facid=' . $object->id . '">';
				print img_edit($langs->trans('SetProject'), 1);
				print '</a></td>';
			}
			print '</tr></table>';
	
			print '</td><td colspan="3">';
			if ($action == 'classify') {
				$form->form_project($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1);
			} else {
				$form->form_project($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0);
			}
			print '</td>';
			print '</tr>';
		}

		// Bank Account
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('BankAccount');
		print '<td>';
		if (($action != 'editbankaccount') && $user->rights->commande->creer && ! empty($object->brouillon))
		    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editbankaccount')
		{
		    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
		}
		else
		{
		    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
		}
		print "</td>";
		print '</tr>';

		print "</table>";

		print '<br>';

		/*
		 * Recurrence
		 */
		$title = $langs->trans("Recurrence");
		print load_fiche_titre($title, '', 'calendar');
		
		print '<table class="border" width="100%">';

		// if "frequency" is empty or = 0, the reccurence is disabled
		print '<tr><td width="20%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Frequency');
		print '</td>';
		if ($action != 'editfrequency' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfrequency&amp;facid=' . $object->id . '">' . img_edit($langs->trans('Edit'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="5">';
		if ($action == 'editfrequency')
		{
			print '<form method="post" action="'.$_SERVER["PHP_SELF"] . '?facid=' . $object->id.'">';
            print '<input type="hidden" name="action" value="setfrequency">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            print "<input type='text' name='frequency' value='".$object->frequency."' size='5' />&nbsp;".$form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), ($object->unit_frequency?$object->unit_frequency:'m'));
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
		}
		else 
		{
		    if ($object->frequency > 0)
		    {
                print $langs->trans('FrequencyPer_'.$object->unit_frequency, $object->frequency);
		    }
		    else
		    {
		        print $langs->trans("NotARecurringInvoiceTemplate");
		    }
		}
		print '</td></tr>';
		
		// Date when
		print '<tr><td>';
		if ($action == 'date_when' || $object->frequency > 0)
		{
		    print $form->editfieldkey($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->rights->facture->creer, 'day');
		}
		else
		{
		    print $langs->trans("NextDateToExecution");
		}
		print '</td><td colspan="5">';
		if ($action == 'date_when' || $object->frequency > 0)
		{
		    print $form->editfieldval($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->rights->facture->creer, 'day');
		}
		print '</td>';
		print '</tr>';
				
		// Max period / Rest period
		print '<tr><td>';
		if ($action == 'nb_gen_max' || $object->frequency > 0)
		{
		    print $form->editfieldkey($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max, $object, $user->rights->facture->creer);
		}
		else
		{
		    print $langs->trans("MaxPeriodNumber");
		}
		print '</td><td colspan="5">';
		if ($action == 'nb_gen_max' || $object->frequency > 0)
		{
		      print $form->editfieldval($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max?$object->nb_gen_max:'', $object, $user->rights->facture->creer);
		}
		else
		{
		    print '';
		}
		print '</td>';
		print '</tr>';
		
		// Status of generated invoices
		print '<tr><td>';
		if ($action == 'auto_validate' || $object->frequency > 0)
		    print $form->editfieldkey($langs->trans("StatusOfGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->rights->facture->creer);
		else
		    print $langs->trans("StatusOfGeneratedInvoices");
		print '</td><td colspan="5">';
    	$select = 'select;0:'.$langs->trans('BillStatusDraft').',1:'.$langs->trans('BillStatusValidated');
		if ($action == 'auto_validate' || $object->frequency > 0)
		{
    		print $form->editfieldval($langs->trans("StatusOfGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->rights->facture->creer, $select);
		}
		print '</td>';
		print '</tr>';
		
		print '</table>';
		
    	print '<br>';
		
		if ($object->frequency > 0)
		{
    		
    		print '<table class="border" width="100%">';
    		
    		// Nb of generation already done
    		print '<tr><td width="20%">'.$langs->trans("NbOfGenerationDone").'</td>';
    		print '<td>';
    		print $object->nb_gen_done?$object->nb_gen_done:'';
    		print '</td>';
    		print '</tr>';
    		
    		// Date last
    		print '<tr><td>';
    		print $langs->trans("DateLastGeneration");
    		print '</td><td colspan="5">';
    		print dol_print_date($object->date_last_gen, 'dayhour');
    		print '</td>';
    		print '</tr>';
    		
    		print '</table>';
    		
    		print '<br>';
		}		
		
		/*
		 * Lines
		 */

		print '<table class="noborder noshadow" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td align="right">'.$langs->trans("VAT").'</td>';
		print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
		print '<td align="center">'.$langs->trans("Qty").'</td>';
		print '<td align="center">'.$langs->trans("ReductionShort").'</td>';
		if ($conf->global->PRODUCT_USE_UNITS) {
			print '<td align="left">'.$langs->trans("Unit").'</td>';
		}
		print '</tr>';

		$num = count($object->lines);
		$i = 0;
		$var=true;
		while ($i < $num)
		{
			$var=!$var;

			$product_static=new Product($db);

			// Show product and description
			$type=(isset($object->lines[$i]->product_type)?$object->lines[$i]->product_type:$object->lines[$i]->fk_product_type);
			// Try to enhance type detection using date_start and date_end for free lines when type
			// was not saved.
			if (! empty($objp->date_start)) $type=1;
			if (! empty($objp->date_end)) $type=1;

			// Show line
			print "<tr ".$bc[$var].">";
			if ($object->lines[$i]->fk_product > 0)
			{
				print '<td>';
				print '<a name="'.$object->lines[$i]->id.'"></a>'; // ancre pour retourner sur la ligne

				// Show product and description
				$product_static->type=$object->lines[$i]->fk_product_type;
				$product_static->id=$object->lines[$i]->fk_product;
				$product_static->ref=$object->lines[$i]->product_ref;
				$text=$product_static->getNomUrl(1);
				$text.= ' - '.(! empty($object->lines[$i]->label)?$object->lines[$i]->label:$object->lines[$i]->product_label);
				$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($object->lines[$i]->desc));
				print $form->textwithtooltip($text,$description,3,'','',$i);

				// Show range
				print_date_range($object->lines[$i]->date_start, $object->lines[$i]->date_end);

				// Add description in form
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					print (! empty($object->lines[$i]->desc) && $object->lines[$i]->desc!=$fac->lines[$i]->product_label)?'<br>'.dol_htmlentitiesbr($object->lines[$i]->desc):'';

				print '</td>';
			}
			else
			{
				print '<td>';

				if ($type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');

				if (! empty($object->lines[$i]->label)) {

					$text.= ' <strong>'.$object->lines[$i]->label.'</strong>';
					print $form->textwithtooltip($text,dol_htmlentitiesbr($object->lines[$i]->desc),3,'','',$i);

				} else {

					print $text.' '.nl2br($object->lines[$i]->desc);
				}

				// Show range
				print_date_range($object->lines[$i]->date_start, $object->lines[$i]->date_end);

				print '</td>';
			}
			print '<td align="right">'.vatrate($object->lines[$i]->tva_tx, 1).'</td>';
			print '<td align="right">'.price($object->lines[$i]->price).'</td>';
			print '<td align="center">'.$object->lines[$i]->qty.'</td>';
			print '<td align="center">'.$object->lines[$i]->remise_percent.' %</td>';
			if ($conf->global->PRODUCT_USE_UNITS) {
				print "<td align=\"left\">".$object->lines[$i]->getLabelOfUnit()."</td>";
			}
			print "</tr>\n";
			$i++;
		}
		print '</table>';

		dol_fiche_end();
		

		/**
		 * Barre d'actions
		 */
		print '<div class="tabsAction">';

		//if ($object->statut == Facture::STATUS_DRAFT)   // there is no draft status on templates.
		//{
		    if ($user->rights->facture->creer)
		    {
    		    if (empty($object->frequency) || $object->date_when <= $today)
    		    {
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$object->thirdparty->id.'&amp;fac_rec='.$object->id.'">'.$langs->trans("CreateBill").'</a></div>';
    		    }
    		    else
    		    {
    		        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DateIsNotEnough")).'">'.$langs->trans("CreateBill").'</a></div>';
    		    }
		    }
		    else
    	    {
    		    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("CreateBill").'</a></div>';
    		}
		//}

		//if ($object->statut == Facture::STATUS_DRAFT && $user->rights->facture->supprimer)
		if ($user->rights->facture->supprimer)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
		}

		print '</div>';
		
		

		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
		
		// Linked object block
		$somethingshown = $form->showLinkedObjectBlock($object);
		
        print '</div></div>';

	}
	else
	{
		/*
		 *  List mode
		 */
		$sql = "SELECT s.nom as name, s.rowid as socid, f.rowid as facid, f.titre, f.total, f.tva as total_vat, f.total_ttc, f.frequency,";
		$sql.= " f.date_last_gen, f.date_when";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		if ($socid)	$sql .= " AND s.rowid = ".$socid;

        $nbtotalofrecords = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
        	$result = $db->query($sql);
        	$nbtotalofrecords = $db->num_rows($result);
        }
        
        $sql.= $db->plimit($limit+1,$offset);
		
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			
			$param='&socid='.$socid;
			if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
				
            print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
            if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
        	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        	print '<input type="hidden" name="action" value="list">';
        	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
            
	        print_barre_liste($langs->trans("RepeatableInvoices"),$page,$_SERVER['PHP_SELF'],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecord,'title_accountancy.png',0,'','',$limit);

			print $langs->trans("ToCreateAPredefinedInvoice", $langs->transnoentitiesnoconv("ChangeIntoRepeatableInvoice")).'<br><br>';

			$i = 0;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Ref"));
			print_liste_field_titre($langs->trans("Company"),$_SERVER['PHP_SELF'],"s.nom","","&socid=$socid","",$sortfiled,$sortorder);
			print_liste_field_titre($langs->trans("AmountHT"),'','','','','align="right"');
			print_liste_field_titre($langs->trans("AmountVAT"),'','','','','align="right"');
			print_liste_field_titre($langs->trans("AmountTTC"),'','','','','align="right"');
			print_liste_field_titre($langs->trans("RecurringInvoiceTemplate"),'','','','','align="center"');
			print_liste_field_titre($langs->trans("DateLastGeneration"),'','','','','align="center"');
			print_liste_field_titre($langs->trans("NextDateToExecution"),'','','','','align="center"');
			print_liste_field_titre('');		// Field may contains ling text
			print "</tr>\n";

			if ($num > 0)
			{
				$var=true;
				while ($i < min($num,$limit))
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					print "<tr ".$bc[$var].">";

					print '<td><a href="'.$_SERVER['PHP_SELF'].'?id='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
					print "</a></td>\n";

					$companystatic->id=$objp->socid;
					$companystatic->name=$objp->name;
					print '<td>'.$companystatic->getNomUrl(1,'customer').'</td>';

					print '<td align="right">'.price($objp->total).'</td>'."\n";
					print '<td align="right">'.price($objp->total_vat).'</td>'."\n";
					print '<td align="right">'.price($objp->total_ttc).'</td>'."\n";
					print '<td align="center">'.yn($objp->frequency?1:0).'</td>';
					print '<td align="center">'.($objp->frequency ? dol_print_date($objp->date_last_gen,'day') : '').'</td>';
					print '<td align="center">'.($objp->frequency ? dol_print_date($objp->date_when,'day') : '').'</td>';
						
					print '<td align="center">';
					if ($user->rights->facture->creer)
					{
				        if (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today)
				        {
                            print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
                            print $langs->trans("CreateBill").'</a>';
				        }
				        else
				        {
				            print $langs->trans("DateIsNotEnough");
				        }
					}
					else
					{
					    print "&nbsp;";
					}
					print "</td>";
					print "</tr>\n";
					$i++;
				}
			}
			else print '<tr '.$bc[false].'><td colspan="9">'.$langs->trans("NoneF").'</td></tr>';

			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}

}

llxFooter();

$db->close();
