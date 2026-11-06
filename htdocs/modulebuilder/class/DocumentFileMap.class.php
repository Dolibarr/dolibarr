<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/class/DocumentFileMap.class.php
 * \ingroup modulebuilder
 * \brief   Document model files of a ModuleBuilder object to create or delete.
 */
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/NamingContract.class.php';
require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/DocumentGenerationMode.class.php';

/**
 * Document model files of a ModuleBuilder object, as template path => generated path.
 */
final class DocumentFileMap
{
	const TEMPLATE_FILES = array(
		'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php',
		'core/modules/mymodule/doc/pdf_standard_myobject.modules.php',
	);

	/** @var NamingContract */
	private $namingContract;

	/**
	 * @param NamingContract $namingContract Names of the module and object being generated
	 */
	public function __construct(NamingContract $namingContract)
	{
		$this->namingContract = $namingContract;
	}

	/**
	 * @return array<string,string> Template path => generated path, relative to the module directory
	 */
	public function getAll(): array
	{
		$files = array();
		foreach (self::TEMPLATE_FILES as $templateFile) {
			$files[$templateFile] = $this->namingContract->applyToFilename($templateFile);
		}

		return $files;
	}

	/**
	 * @param string $mode One of DocumentGenerationMode constants
	 * @return array<string,string> Template path => generated path
	 * @throws \InvalidArgumentException If the mode is unknown
	 */
	public function getFilesToCreate(string $mode): array
	{
		return DocumentGenerationMode::isEnabled($mode) ? $this->getAll() : array();
	}

	/**
	 * @param string $mode One of DocumentGenerationMode constants
	 * @return array<string,string> Template path => generated path
	 * @throws \InvalidArgumentException If the mode is unknown
	 */
	public function getFilesToDelete(string $mode): array
	{
		return DocumentGenerationMode::isEnabled($mode) ? array() : $this->getAll();
	}
}
