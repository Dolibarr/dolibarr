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
	 * Renders an HTML table for a given list of files.
	 *
	 * @param   string                               $title              The main H2 title for the page.
	 * @param   array<int, array<string, mixed>>     $fileList           The list of files from dol_dir_list().
	 * @param   string                               $noFileMessage      The message to display if the file list is empty.
	 * @param   callable                             $linkBuilder        A function that takes a file array and returns its download URL.
	 * @return  void
	 */
	protected function displayDocumentTable($title, $fileList, $noFileMessage, callable $linkBuilder)
	{
		global $langs;

		echo '<h2>' . htmlspecialchars($title) . '</h2>';

		if (is_array($fileList) && count($fileList) > 0) {
			echo '<table class="table" width="100%">';
			echo '<thead><tr>';
			echo '<th>' . $langs->trans('File') . '</th>';
			echo '<th style="text-align: right; white-space: nowrap;">' . $langs->trans('Size') . '</th>';
			echo '<th style="text-align: right; white-space: nowrap;">' . $langs->trans('DateM') . '</th>';
			echo '</tr></thead>';
			echo '<tbody>';

			foreach ($fileList as $file) {
				// The magic happens here: we call the provided function to build the specific URL
				$downloadLink = $linkBuilder($file);

				echo '<tr>';
				echo '<td><a href="' . $downloadLink . '" target="_blank">' . htmlspecialchars($file['name']) . '</a></td>';
				echo '<td style="text-align: right;">' . dol_print_size($file['size']) . '</td>';
				echo '<td style="text-align: right;">' . dol_print_date($file['date'], 'dayhour') . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		} else {
			echo '<p>' . htmlspecialchars($noFileMessage) . '</p>';
		}

		echo '<br>';
	}
}
