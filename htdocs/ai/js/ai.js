/* Copyright (C) 2026		Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
 */

/**
 * \file    htdocs/ai/js/ai.js
 * \brief   JavaScript capabilities for the AI module.
 * \ingroup ai
 */


/**
 * Handle UI interactions for the MCP tools configuration interface.
 * configure_tools.php
 * Provides responsive controls, grouping, collapse/expand states, 
 * and dynamic class toggles for elements within configure_tools.php.
 */
jQuery(document).ready(function($) {
	/**
	 * Collapse or expand tool group rows when clicking on their group header.
	 * 
	 * Expects a '.trgroup' container with a 'data-group' attribute matching 
	 * the class name of the target rows to toggle.
	 * 
	 * @listens click
	 * @return {void}
	 */
	$(".mcp-trigger-collapse").on("click", function() {
		var $headerRow = $(this).closest(".trgroup");
		var groupId    = $headerRow.attr("data-group");

		if (groupId) {
			$("." + groupId).toggle();
			$headerRow.toggleClass("collapsed");
		}
	});
});


