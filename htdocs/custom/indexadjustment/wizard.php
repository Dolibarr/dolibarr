<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       wizard.php
 * \ingroup    indexadjustment
 * \brief      AJAX Wizard for creating and executing index adjustments (contract mode)
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/lib/indexadjustment.lib.php';

// Load translations
$langs->loadLangs(array("indexadjustment@indexadjustment", "contracts", "other"));

// Security check
if (!$user->admin && !$user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	accessforbidden();
}

$canExecute = $user->admin || $user->hasRight('indexadjustment', 'indexadjustment', 'execute');

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("NewIndexAdjustment");
llxHeader('', $title, '', '', 0, 0, array('/custom/indexadjustment/js/indexadjustment_wizard.js'));

print load_fiche_titre($title, '', 'fa-percent');

// Step indicator
print '<div class="wizard-steps marginbottomonly">';
print '<span class="badge wizard-step-badge badge-primary" data-step="1"><span class="step-icon"></span>1. ' . $langs->trans("WizardStep1") . '</span> ';
print '<span class="badge wizard-step-badge badge-secondary" data-step="2"><span class="step-icon"></span>2. ' . $langs->trans("WizardStep2") . '</span> ';
print '<span class="badge wizard-step-badge badge-secondary" data-step="3"><span class="step-icon"></span>3. ' . $langs->trans("WizardStep3") . '</span>';
print '</div>';

// Error container
print '<div id="wizard-error" style="display:none;"></div>';

// ----- Step 1: Settings -----
print '<div id="wizard-step-1" class="wizard-step-content">';

print '<table class="border centpercent">';

// Label
print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td>';
print '<td><input type="text" class="flat minwidth300" id="wizard-label" value="' . dol_escape_htmltag($langs->trans("IndexAdjustment") . ' ' . date('Y')) . '"></td></tr>';

// Adjustment Percent
print '<tr><td class="fieldrequired">' . $langs->trans("AdjustmentPercent") . '</td>';
print '<td><input type="text" class="flat width100" id="wizard-percent" value="4.5"> %';
print ' <span class="opacitymedium">(' . $langs->trans("EnterAdjustmentPercent") . ')</span></td></tr>';

// Adjustment Date
print '<tr><td>' . $langs->trans("AdjustmentDate") . '</td>';
print '<td>';
// Use Dolibarr selectDate with specific IDs we can reference
print $form->selectDate(dol_now(), 'adjustment_date', 0, 0, 0, '', 1, 1);
print '</td></tr>';

// Customer
print '<tr><td>' . $langs->trans("ThirdParty") . '</td>';
print '<td>' . $form->select_company('', 'fk_soc', 'client > 0', 1, 0, 0, array(), 0, 'minwidth300');
print ' <span class="opacitymedium">(' . $langs->trans("SelectCustomerOrAll") . ')</span></td></tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<a class="butAction" id="btn-next-step1"><span class="fas fa-arrow-right"></span> ' . $langs->trans("Next") . '</a>';
print '</div>';

print '</div>'; // End Step 1

// ----- Step 2: Select Contracts -----
print '<div id="wizard-step-2" class="wizard-step-content" style="display:none;">';

print '<div class="marginbottomonly">';
print '<span class="opacitymedium" id="wizard-selection-count"></span>';
print '</div>';

print '<div id="wizard-contracts-container"></div>';

print '<br>';
print '<div class="center">';
print '<a class="button" id="btn-back-step2"><span class="fas fa-arrow-left"></span> ' . $langs->trans("Back") . '</a> ';
print '<a class="butAction" id="btn-preview"><span class="fas fa-search"></span> ' . $langs->trans("PreviewAdjustment") . '</a>';
print '</div>';

print '</div>'; // End Step 2

// ----- Step 3: Preview & Execute -----
print '<div id="wizard-step-3" class="wizard-step-content" style="display:none;">';

print '<div id="wizard-preview-container"></div>';

print '<br>';
print '<div class="center">';
print '<a class="button" id="btn-back-step3"><span class="fas fa-arrow-left"></span> ' . $langs->trans("Back") . '</a> ';
print '<a class="butAction" id="btn-execute"><span class="fas fa-check"></span> ' . $langs->trans("ExecuteIndexAdjustment") . '</a>';
print '</div>';

print '</div>'; // End Step 3

// JavaScript initialization
print '<script type="text/javascript">';
print 'jQuery(document).ready(function() {';
print '  new IndexAdjustmentWizard({';
print '    ajaxUrl: "' . dol_buildpath('/indexadjustment/ajax/wizard_ajax.php', 1) . '",';
print '    token: "' . newToken() . '",';
print '    mode: "contract",';
print '    cardUrl: "' . dol_buildpath('/indexadjustment/card.php', 1) . '",';
print '    listUrl: "' . dol_buildpath('/indexadjustment/list.php', 1) . '",';
print '    canExecute: ' . ($canExecute ? 'true' : 'false') . ',';
print '    currencySymbol: "' . dol_escape_js($langs->getCurrencySymbol($conf->currency)) . '",';
print '    langs: {';
print '      Next: "' . dol_escape_js($langs->trans("Next")) . '",';
print '      Back: "' . dol_escape_js($langs->trans("Back")) . '",';
print '      Contract: "' . dol_escape_js($langs->trans("Contract")) . '",';
print '      ThirdParty: "' . dol_escape_js($langs->trans("ThirdParty")) . '",';
print '      RefCustomer: "' . dol_escape_js($langs->trans("RefCustomer")) . '",';
print '      ActiveLines: "' . dol_escape_js($langs->trans("ActiveLines")) . '",';
print '      Product: "' . dol_escape_js($langs->trans("Product")) . '",';
print '      Lines: "' . dol_escape_js($langs->trans("Lines")) . '",';
print '      UnitPrice: "' . dol_escape_js($langs->trans("UnitPrice")) . '",';
print '      Qty: "' . dol_escape_js($langs->trans("Qty")) . '",';
print '      Summary: "' . dol_escape_js($langs->trans("Summary")) . '",';
print '      TotalContracts: "' . dol_escape_js($langs->trans("TotalContracts")) . '",';
print '      TotalLines: "' . dol_escape_js($langs->trans("TotalLines")) . '",';
print '      TotalHTBefore: "' . dol_escape_js($langs->trans("TotalHTBefore")) . '",';
print '      TotalHTAfter: "' . dol_escape_js($langs->trans("TotalHTAfter")) . '",';
print '      SubpriceBefore: "' . dol_escape_js($langs->trans("SubpriceBefore")) . '",';
print '      SubpriceAfter: "' . dol_escape_js($langs->trans("SubpriceAfter")) . '",';
print '      PriceDiff: "' . dol_escape_js($langs->trans("PriceDiff")) . '",';
print '      SelectAll: "' . dol_escape_js($langs->trans("SelectAll")) . '",';
print '      DeselectAll: "' . dol_escape_js($langs->trans("DeselectAll")) . '",';
print '      NoContractsFound: "' . dol_escape_js($langs->trans("NoContractsFound")) . '",';
print '      LoadingContracts: "' . dol_escape_js($langs->trans("LoadingContracts")) . '",';
print '      LoadingPreview: "' . dol_escape_js($langs->trans("LoadingPreview")) . '",';
print '      ExecutingAdjustment: "' . dol_escape_js($langs->trans("ExecutingAdjustment")) . '",';
print '      CreatingAdjustment: "' . dol_escape_js($langs->trans("CreatingAdjustment")) . '",';
print '      AjaxError: "' . dol_escape_js($langs->trans("AjaxError")) . '",';
print '      ValidationErrorLabel: "' . dol_escape_js($langs->trans("ValidationErrorLabel")) . '",';
print '      ValidationErrorPercent: "' . dol_escape_js($langs->trans("ValidationErrorPercent")) . '",';
print '      ValidationErrorNoSelection: "' . dol_escape_js($langs->trans("ValidationErrorNoSelection")) . '",';
print '      ExecuteSuccess: "' . dol_escape_js($langs->trans("ExecuteSuccess")) . '",';
print '      SelectedCount: "' . dol_escape_js($langs->trans("SelectedCount")) . '",';
print '      ConfirmExecuteIndexAdjustment: "' . dol_escape_js($langs->trans("ConfirmExecuteIndexAdjustment")) . '",';
print '      NotAllowed: "' . dol_escape_js($langs->trans("NotAllowed")) . '"';
print '    }';
print '  });';
print '});';
print '</script>';

llxFooter();
$db->close();
