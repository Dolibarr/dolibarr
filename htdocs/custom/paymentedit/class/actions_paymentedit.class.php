<?php

/* Copyright (C) 2024-2025  Florian Hödl  <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    paymentedit/class/actions_paymentedit.class.php
 * \ingroup paymentedit
 * \brief   Hook class for PaymentEdit module - enables editing of various payments
 */

/**
 * Class ActionsPaymentEdit
 *
 * Provides hooks to add a "Modify" button to the various payment card page
 * and redirect to a custom edit form.
 */
class ActionsPaymentEdit
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * @var int Priority of hook (50 is used if value is not defined)
     */
    public $priority;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Execute action - Handles doActions hook
     *
     * This hook doesn't need to process any actions since we redirect to our own card.php
     * which handles the edit/update logic independently.
     *
     * @param array         $parameters  Hook metadata (context, etc.)
     * @param CommonObject  $object      The object being processed
     * @param string        $action      Current action (create, edit, etc.)
     * @param HookManager   $hookmanager Hook manager
     * @return int                       < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0;
        $this->resprints = '';

        // Only process on variouscard context
        if (!in_array('variouscard', explode(':', $parameters['context']))) {
            return 0;
        }

        // No action processing needed here - our card.php handles everything
        return 0;
    }

    /**
     * Hook to add custom HTML after form object options
     *
     * We use this hook to inject JavaScript that adds the "Modify" button
     * to the action bar on the various payment card page.
     *
     * @param array         $parameters  Hook metadata (context, etc.)
     * @param CommonObject  $object      The object being processed (PaymentVarious)
     * @param string        $action      Current action
     * @param HookManager   $hookmanager Hook manager
     * @return int                       < 0 on error, 0 on success, 1 to replace standard code
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $this->resprints = '';

        // Only process on variouscard context
        if (!in_array('variouscard', explode(':', $parameters['context']))) {
            return 0;
        }

        // Only when viewing (not creating)
        if (empty($object->id) || $object->id <= 0) {
            return 0;
        }

        // Check permission
        if (!$user->hasRight('banque', 'modifier')) {
            return 0;
        }

        // Load module translations
        $langs->load('paymentedit@paymentedit');

        // Check if payment is reconciled (rappro = 1 means reconciled, cannot edit)
        $isReconciled = !empty($object->rappro) && $object->rappro == 1;

        // Check if already accounted
        $alreadyAccounted = false;
        if (method_exists($object, 'getVentilExportCompta')) {
            $alreadyAccounted = $object->getVentilExportCompta();
        }

        // Build the edit URL to our custom card.php
        $editUrl = dol_buildpath('/custom/paymentedit/card.php', 1).'?id='.$object->id;

        // Determine button state
        $buttonDisabled = false;
        $disabledReason = '';

        if ($isReconciled) {
            $buttonDisabled = true;
            $disabledReason = $langs->trans('LinkedToAConciliatedTransaction');
        }

        // Generate JavaScript to inject the Modify button
        $jsCode = '
<script type="text/javascript">
jQuery(document).ready(function() {
	// Find the action bar
	var $tabsAction = jQuery(".tabsAction");
	if ($tabsAction.length === 0) {
		return;
	}

	// Create the Modify button
	var modifyBtn = \'<div class="inline-block divButAction">\';
';

        if ($buttonDisabled) {
            // Disabled button with tooltip
            $jsCode .= '
	modifyBtn += \'<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_js($disabledReason).'">'.dol_escape_js($langs->trans('Modify')).'</a>\';
';
        } else {
            // Active button
            $jsCode .= '
	modifyBtn += \'<a class="butAction" href="'.dol_escape_js($editUrl).'">'.dol_escape_js($langs->trans('Modify')).'</a>\';
';
        }

        $jsCode .= '
	modifyBtn += \'</div>\';

	// Insert before the first button (Clone button)
	var $firstButton = $tabsAction.find(".divButAction:first");
	if ($firstButton.length > 0) {
		$firstButton.before(modifyBtn);
	} else {
		// If no buttons exist, append to action bar
		$tabsAction.prepend(modifyBtn);
	}
});
</script>
';

        $this->resprints = $jsCode;

        return 0;
    }

    /**
     * Hook to add actions buttons (alternative approach)
     *
     * Note: This hook may not exist in variouscard context, but we implement it
     * in case it gets added in a future Dolibarr version.
     *
     * @param array         $parameters  Hook metadata
     * @param CommonObject  $object      The object being processed
     * @param string        $action      Current action
     * @param HookManager   $hookmanager Hook manager
     * @return int                       < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $this->resprints = '';

        // Only process on variouscard context
        if (!in_array('variouscard', explode(':', $parameters['context']))) {
            return 0;
        }

        // Only when viewing (not creating)
        if (empty($object->id) || $object->id <= 0) {
            return 0;
        }

        // Check permission
        if (!$user->hasRight('banque', 'modifier')) {
            return 0;
        }

        // Load translations
        $langs->load('paymentedit@paymentedit');

        // Check if payment is reconciled
        $isReconciled = !empty($object->rappro) && $object->rappro == 1;

        // Build the edit URL
        $editUrl = dol_buildpath('/custom/paymentedit/card.php', 1).'?id='.$object->id;

        // Output the button with proper escaping
        if ($isReconciled) {
            $this->resprints = '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans('LinkedToAConciliatedTransaction')).'">'.dol_escape_htmltag($langs->trans('Modify')).'</a></div>';
        } else {
            $this->resprints = '<div class="inline-block divButAction"><a class="butAction" href="'.dol_escape_htmltag($editUrl).'">'.dol_escape_htmltag($langs->trans('Modify')).'</a></div>';
        }

        return 0;
    }
}
