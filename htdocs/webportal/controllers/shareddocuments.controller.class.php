<?php
/*
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (c) 2025       Schaffhauser sébastien      <sebastien@webmaster67.fr>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/abstractdocument.controller.class.php';

/**
 * \file        htdocs/webportal/controllers/shareddocuments.controller.class.php
 * \ingroup     webportal
 * \brief       This file is a controller for the globally shared documents list.
 */

/**
 * Class for SharedDocumentsController
 */
class SharedDocumentsController extends AbstractDocumentController
{
	/** @var string */
	public $sanitized_subdir = '';

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
	 * Action method is called before html output.
	 *
	 * @return int
	 */
	public function action()
	{
		global $langs;
		$context = Context::getInstance();
		if (!$context->controllerInstance->checkAccess()) {
			return -1;
		}

		$current_subdir = $_POST['subdir'] ?? $_GET['subdir'] ?? '';
		if (!empty($current_subdir)) {
			$parts = explode('/', $current_subdir);
			$safe_parts = array();
			foreach ($parts as $part) {
				if ($part !== '.' && $part !== '..') {
					$safe_parts[] = dol_sanitizeFileName($part);
				}
			}
			$this->sanitized_subdir = implode('/', $safe_parts);
		}

		$context->title = html_entity_decode($langs->trans('SharedDocuments'));
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

		// 1. Manage the current subfolder from the class property
		$sanitized_subdir = $this->sanitized_subdir;

		// 2. Prepare the paths
		$shared_dir_name = getDolGlobalString('WEBPORTAL_SHARED_DOCS_DIR', 'Documentscomptes');
		$base_dir_ged_partage = $conf->ecm->dir_output . '/' . $shared_dir_name;
		// The full path now includes the visited subfolder
		$current_dir_ged_partage = $base_dir_ged_partage . '/' . $sanitized_subdir;

		// 3. List ALL contents (files AND folders) of the current directory
		$itemList = dol_dir_list($current_dir_ged_partage, 'all', 0, '', '', 'name', SORT_ASC);
		if (is_array($itemList)) {
			foreach ($itemList as $key => $item) {
				// If the item is a file and its size is empty...
				if ($item['type'] === 'file' && empty($item['size'])) {
					$full_file_path = $current_dir_ged_partage . '/' . $item['name'];
					// ... we recalculate its size and update the table.
					// The @ avoids an error if the file is unreadable.
					$itemList[$key]['size'] = @filesize($full_file_path);
				}
			}
		}
		// 4. Build the Breadcrumb
		$baseUrl = $_SERVER['PHP_SELF'].'?controller=shareddocuments';
		$breadcrumbs = '<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="'.$baseUrl.'">'.dol_htmlentities($langs->trans("Home")).'</a></li>';
		$path_so_far = '';
		if (!empty($sanitized_subdir)) {
			foreach (explode('/', $sanitized_subdir) as $part) {
				$path_so_far .= (empty($path_so_far) ? '' : '/') . $part;
				$breadcrumbs .= '<li class="breadcrumb-item"><a href="'.$baseUrl.'&subdir='.$path_so_far.'">'.dol_htmlentities($part).'</a></li>';
			}
		}
		$breadcrumbs .= '</ol></nav>';

		// Show breadcrumbs
		print $breadcrumbs;

		// 5. Define functions to build navigation and download links
		$linkBuilder = array(
			'dir' => /**
					  * @param array<string, mixed> $dir
					  * @return string
					  */
				function (array $dir) use ($baseUrl, $sanitized_subdir) {
					$new_subdir = (!empty($sanitized_subdir) ? $sanitized_subdir . '/' : '') . $dir['name'];
					return $baseUrl . '&subdir=' . urlencode($new_subdir);
				},
			'file' => /**
					   * @param array<string, mixed> $file
					   * @return string
					   */
				function (array $file) use ($shared_dir_name, $sanitized_subdir) {
					$file_path = $shared_dir_name . '/' . (!empty($sanitized_subdir) ? $sanitized_subdir . '/' : '') . $file['name'];
					return DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . urlencode($file_path);
				}
		);
		// 6. Call the new display method
		$this->displayDocumentTable(
			html_entity_decode($langs->trans('SharedDocuments')),
			$itemList,
			$langs->trans('ThisDirectoryIsEmpty'),
			$linkBuilder
		);

		print '</main>';

		$this->loadTemplate('footer');
	}
}
