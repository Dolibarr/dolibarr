<?php
/* Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/class/html.formtemplate.class.php
 *       \ingroup    core
 *       \brief      File of class to manage templates
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-micro-template/src/Render.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-micro-template/src/Exception/PHPMicroTemplateException.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-micro-template/src/Exception/FileSystemException.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-micro-template/src/Exception/UndefinedSymbolException.php';
require_once DOL_DOCUMENT_ROOT . '/includes/php-micro-template/src/Exception/SyntaxErrorException.php';

use PHPMicroTemplate\Render;

/**
 *  Class to manage templates
 */
class FormTemplate extends Form
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var Render
	 */
	public $render;

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * render template
	 *
	 * @param string $tpl template file
	 * @param array<string,mixed> $params This array contains substitutions
	 *
	 * @return string
	 */
	public function renderTemplate($tpl, $params = [])
	{
		global $conf, $hookmanager;

		$this->render = new Render();

		$this->render->onResolveError(function (string $var) {
			// expression is undefined
			return $var . ' is Undefined';
		});

		$parameters = [
			'tpl' => $tpl,
			'params' => &$params,
		];
		$resHook = $hookmanager->executeHooks('renderTemplate', $parameters); // Note that $action and $object may have been modified by some hooks

		$dirtpls = array_merge((array) $conf->modules_parts['theme'], array('/theme/'.$conf->theme.'/', '/theme/common/'));
		foreach ($dirtpls as $reldir) {
			$file = dol_buildpath($reldir."tpl/".$tpl, 0);
			if (file_exists($file)) {
				$tpl = $file;
				break;
			}
		}

		return $this->render->renderTemplate(
			$tpl,
			$params
		);
	}
}
