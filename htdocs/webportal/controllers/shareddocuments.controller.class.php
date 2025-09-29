<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (c) 2025       Schaffhauser sébastien      <sebastien@webmaster67.fr>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 */

require_once __DIR__ . '/abstractdocument.controller.class.php';

/**
 * \file        htdocs/webportal/controllers/shareddocuments.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for the globally shared documents list.
 */

/**
 * Class for SharedDocumentsController
 *
 * @method void display404()
 * @method bool loadTemplate(string $templateName, false|mixed $vars = false)
 */
class SharedDocumentsController extends AbstractDocumentController
{
	/**
	 * Check access rights for this page.
	 *
	 * @return  bool
	 */
	public function checkAccess()
	{
		$this->accessRight = getDolGlobalInt('WEBPORTAL_SHARED_DOCUMENT_ACCESS');
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
		$context->title = $langs->trans('SharedDocuments');
		$context->desc = $langs->trans('ListOfSharedDocuments');
		$context->menu_active[] = 'shared_documents';

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

		// 1. Prepare data for this controller
		$shared_dir_name = getDolGlobalString('WEBPORTAL_SHARED_DOCS_DIR', 'Documentscomptes');
		$dir_ged_partage = $conf->ecm->dir_output . '/' . $shared_dir_name;
		$shared_dir_relative_path = 'ecm/' . $shared_dir_name;
		$fileList = dol_dir_list($dir_ged_partage, 'files', 0, '', '', 'date', SORT_DESC);

		// 2. Define the link builder function
		/**
		 * Get url for file (anonymous function)
		 *
		 * @param	array<string, mixed> $file  File (array) to get url for
		 * @return	string						Url for file
		 */
		$linkBuilder
			= static function (array $file) use ($shared_dir_relative_path) {
				return DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($shared_dir_relative_path . '/' . $file['name']);
			};

		// 3. Call the parent method to display the table
		$this->displayDocumentTable(
			$langs->trans('SharedDocuments'),
			$fileList,
			$langs->trans('NoSharedDocumentAvailable'),
			$linkBuilder
		);

		print '</main>';

		$this->loadTemplate('footer');
	}
}
