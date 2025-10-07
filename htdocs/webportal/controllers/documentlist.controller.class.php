<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Schaffhauser sébastien		<sebastien@webmaster67.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


require_once __DIR__ . '/abstractdocument.controller.class.php';

/**
 * \file        htdocs/webportal/controllers/documentlist.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for thirdparty's documents list (GED).
 */

/**
 * Class for DocumentListController
 *
 * @method void display404()
 * @method bool loadTemplate(string $templateName, false|mixed $vars = false)
 */
class DocumentListController extends AbstractDocumentController
{
	/**
	 * Check access rights for this page.
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = getDolGlobalInt('WEBPORTAL_DOCUMENT_LIST_ACCESS');
		return parent::checkAccess();
	}

	/**
	 * Action method is called before HTML output.
	 *
	 * @return int Returns >0 on success, 0 if no action, <0 on error.
	 */
	public function action(): int
	{
		global $langs;
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return -1;
		}

		$langs->loadLangs(array('other', 'webportal@webportal'));
		$context->title = $langs->trans('MyDocuments');
		$context->desc = $langs->trans('ListOfMyDocuments');
		$context->menu_active[] = 'document_list';

		return 1;
	}

	/**
	 * Build and display the page.
	 *
	 * @return  void
	 */
	public function display()
	{
		global $conf, $langs;
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			$this->display404();
			return;
		}

		$this->loadTemplate('header');
		$this->loadTemplate('menu');
		$this->loadTemplate('hero-header-banner');

		print '<main class="container">';

		$thirdparty = $context->logged_thirdparty;

		if (!empty($thirdparty) && $thirdparty->id) {
			// 1. Prepare data
			require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
			$client_dir_name = dol_sanitizeFileName($thirdparty->ref);
			$dir_ged_tiers = $conf->societe->dir_output . '/' . $client_dir_name;
			$fileList = dol_dir_list($dir_ged_tiers, 'files', 0, '', '', 'date', SORT_DESC);

			// 2. Define the link builder function
			/**
			 * Get url for file (anonymous function)
			 *
			 * @param	array<string, mixed> $file  File (array) to get url for
			 * @return	string						Url for file
			 */
			$linkBuilder
				= static function (array $file) use ($client_dir_name) {
					return DOL_URL_ROOT . '/document.php?modulepart=societe&attachment=1&file=' . urlencode($client_dir_name . '/' . $file['name']);
				};

			// 3. Call the parent method to display the table
			$this->displayDocumentTable(
				$langs->trans('MyDocuments'),
				$fileList,
				$langs->trans('NoDocumentAvailable'),
				$linkBuilder
			);
		}

		print '</main>';

		$this->loadTemplate('footer');
	}
}
