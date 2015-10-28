<?php
/* Copyright (C) 2014	  Ion Agorria		  <ion@agorria.com>
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
 *  \file	   htdocs/product/expression/editor.php
 *  \ingroup	product
 *  \brief	  Page for editing expression
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_global_variable.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

$langs->load("products");
$langs->load("accountancy"); //"Back" translation is on this file

$id = GETPOST('id', 'int');
$eid = GETPOST('eid', 'int');
$action = GETPOST('action', 'alpha');
$title = GETPOST('expression_title', 'alpha');
$expression = GETPOST('expression');
$tab = GETPOST('tab', 'alpha');
$tab = (!empty($tab)) ? $tab : 'card';
$tab = strtolower($tab);

// Security check
$result=restrictedArea($user,'produit|service&fournisseur',$id,'product&product','','','rowid');

//Initialize objects
$product = new Product($db);
$product->fetch($id, '');

$price_expression = new PriceExpression($db);
$price_globals = new PriceGlobalVariable($db);

//Fetch expression data
if (empty($eid)) //This also disables fetch when eid == 0
{
	$eid = 0;
}
else if ($action != 'delete')
{
	$price_expression->fetch($eid);
}

/*
 * Actions
 */

if ($action == 'add')
{
	if ($eid == 0)
	{
		$result = $price_expression->find_title($title);
		if ($result == 0) //No existing entry found with title, ok
		{
			//Check the expression validity by parsing it
            $priceparser = new PriceParser($db);
            $price_result = $priceparser->parseProductSupplierExpression($id, $expression, 0, 0);
            if ($price_result < 0) { //Expression is not valid
				setEventMessage($priceparser->translatedError(), 'errors');
			}
			else
			{
				$price_expression->title = $title;
				$price_expression->expression = $expression;
				$result = $price_expression->create($user);
				if ($result > 0) //created successfully, set the eid to newly created entry
				{
					$eid = $price_expression->id;
					setEventMessage($langs->trans("RecordSaved"));
				}
				else
				{
					setEventMessage("add: ".$price_expression->error, 'errors');
				}
			}
		}
		else if ($result < 0)
		{
			setEventMessage("add find: ".$price_expression->error, 'errors');
		}
		else
		{
			setEventMessage($langs->trans("ErrorRecordAlreadyExists"), 'errors');
		}
	}
}

if ($action == 'update')
{
	if ($eid != 0)
	{
		$result = $price_expression->find_title($title);
		if ($result == 0 || $result == $eid) //No existing entry found with title or existing one is the current one, ok
		{
			//Check the expression validity by parsing it
            $priceparser = new PriceParser($db);
            $price_result = $priceparser->parseProductSupplierExpression($id, $expression, 0, 0);
            if ($price_result < 0) { //Expression is not valid
				setEventMessage($priceparser->translatedError(), 'errors');
			}
			else
			{
				$price_expression->id = $eid;
				$price_expression->title = $title;
				$price_expression->expression = $expression;
				$result = $price_expression->update($user);
				if ($result < 0)
				{
					setEventMessage("update: ".$price_expression->error, 'errors');
				}
				else
				{
					setEventMessage($langs->trans("RecordSaved"));
				}
			}
		}
		else if ($result < 0)
		{
			setEventMessage("update find: ".$price_expression->error, 'errors');
		}
		else
		{
			setEventMessage($langs->trans("ErrorRecordAlreadyExists"), 'errors');
		}
	}
}

if ($action == 'delete')
{
	if ($eid != 0)
	{
		$result = $price_expression->delete($eid, $user);
		if ($result < 0)
		{
			setEventMessage("delete: ".$price_expression->error, 'errors');
		}
		$eid = 0;
	}
}

/*
 * View
 */

//Header
llxHeader("","",$langs->trans("CardProduct".$product->type));
print load_fiche_titre($langs->trans("PriceExpressionEditor"));
$form = new Form($db);

//Form/Table
print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;tab='.$tab.'&amp;eid='.$eid.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value='.($eid == 0 ? 'add' : 'update').'>';
print '<table class="border" width="100%">';

// Price expression selector
print '<tr><td class="fieldrequired">'.$langs->trans("PriceExpressionSelected").'</td><td>';
$price_expression_list = array(0 => $langs->trans("New")); //Put the new as first option
foreach ($price_expression->list_price_expression() as $entry) {
	$price_expression_list[$entry->id] = $entry->title;
}
print $form->selectarray('expression_selection', $price_expression_list, $eid);
print '</td></tr>';

// Title input
print '<tr><td class="fieldrequired">'.$langs->trans("Name").'</td><td>';
print '<input class="flat" name="expression_title" size="15" value="'.($price_expression->title?$price_expression->title:'').'">';
print '</td></tr>';

//Help text
$help_text = $langs->trans("PriceExpressionEditorHelp1");
$help_text.= '<br><br>'.$langs->trans("PriceExpressionEditorHelp2");
$help_text.= '<br><br>'.$langs->trans("PriceExpressionEditorHelp3");
$help_text.= '<br><br>'.$langs->trans("PriceExpressionEditorHelp4");
$help_text.= '<br><br>'.$langs->trans("PriceExpressionEditorHelp5");
foreach ($price_globals->listGlobalVariables() as $entry) {
    $help_text.= '<br><b>#globals_'.$entry->code.'#</b> '.$entry->description.' = '.$entry->value;
}

//Price expression editor
print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans("PriceExpressionEditor"),$help_text,1).'</td><td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor=new DolEditor('expression',isset($price_expression->expression)?$price_expression->expression:'','',300,'','',false,false,false,4,80);
$doleditor->Create();
print '</td></tr>';
print '</table>';

//Buttons
print '<div class="center">';
print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
print '<span id="back" class="butAction">'.$langs->trans("Back").'</span>';
if ($eid == 0)
{
	print '<div class="inline-block divButAction"><span id="action-delete" class="butActionRefused">'.$langs->trans('Delete').'</span></div>'."\n";
}
else
{
	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;tab='.$tab.'&amp;eid='.$eid.'&amp;action=delete">'.$langs->trans("Delete").'</a></div>';
}
print '</div>';

print '</form>';

// This code reloads the page depending of selected option, goes to page selected by tab when back is pressed
print '<script type="text/javascript">
	jQuery(document).ready(run);
	function run() {
		jQuery("#back").click(on_click);
		jQuery("#expression_selection").change(on_change);
	}
	function on_click() {
		window.location = "'.str_replace('dynamic_price/editor.php', $tab.'.php', $_SERVER["PHP_SELF"]).'?id='.$id.($tab == 'price' ? '&action=edit_price' : '').'";
	}
	function on_change() {
		window.location = "'.$_SERVER["PHP_SELF"].'?id='.$id.'&tab='.$tab.'&eid=" + $("#expression_selection").attr("value");
	}
</script>';

llxFooter();
$db->close();
