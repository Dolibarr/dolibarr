<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (c) 2025       Schaffhauser sébastien      <sebastien@webmaster67.fr>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 */

require_once __DIR__ . '/../class/controller.class.php';

/**
 * \file        htdocs/webportal/controllers/abstractdocument.controller.class.php
 * \ingroup     webportal
 * \brief       This file is an abstract controller with shared logic to display a list of documents.
 */

/**
 * Abstract Class for Document Controllers
 * Contains the shared logic to display a table of files.
 *
 * @property DoliDB $db          Inherited from Controller
 * @property int $accessRight    Inherited from Controller
 */
abstract class AbstractDocumentController extends Controller
{
	/**
	 * Renders an HTML file browser table for a given list of files and directories.
	 *
	 * @param   string                               $title              The main H2 title for the page.
	 * @param   array<int, array<string, mixed>>     $itemList           The list of items from dol_dir_list('all').
	 * @param   string                               $emptyMessage       The message to display if the list is empty.
	 * @param   array<string, callable>              $linkBuilder        An array of functions to build URLs ('dir' and 'file').
	 * @return  void
	 */
	protected function displayDocumentTable($title, $itemList, $emptyMessage, array $linkBuilder)
	{
		global $langs;

		echo '<h2>' . htmlspecialchars($title) . '</h2>';

		if (is_array($itemList) && count($itemList) > 0) {
			// 1. Separate folders and files
			$directories = array();
			$files = array();
			foreach ($itemList as $item) {
				if ($item['type'] === 'dir') {
					$directories[] = $item;
				} else {
					$files[] = $item;
				}
			}

			// 2. Display the table
			echo '<table class="table table-hover" width="100%">';
			echo '<thead><tr>';
			echo '<th>' . $langs->trans('Name') . '</th>';
			echo '<th style="text-align: right; white-space: nowrap;">' . $langs->trans('Size') . '</th>';
			echo '<th style="text-align: right; white-space: nowrap;">' . $langs->trans('DateM') . '</th>';
			echo '</tr></thead>';
			echo '<tbody>';

			// 3. Display all folders first
			foreach ($directories as $dir) {
				echo '<tr>';
				// The link for a directory is for navigation
				echo '<td><a href="' . $linkBuilder['dir']($dir) . '">📁&nbsp;' . htmlspecialchars($dir['name']) . '</a></td>';
				echo '<td style="text-align: right;">--</td>'; // No size for a directory
				echo '<td style="text-align: right;">' . dol_print_date($dir['date'], 'dayhour') . '</td>';
				echo '</tr>';
			}

			// 4. Then, display all files
			foreach ($files as $file) {
				echo '<tr>';
				// The link for a file is for download
				echo '<td><a href="' . $linkBuilder['file']($file) . '" target="_blank">📄&nbsp;' . htmlspecialchars($file['name']) . '</a></td>';
				echo '<td style="text-align: right;">' . dol_print_size($file['size']) . '</td>';
				echo '<td style="text-align: right;">' . dol_print_date($file['date'], 'dayhour') . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		} else {
			echo '<p>' . htmlspecialchars($emptyMessage) . '</p>';
		}

		echo '<br>';
	}
}
