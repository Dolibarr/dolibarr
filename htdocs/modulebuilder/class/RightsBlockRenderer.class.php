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
 * \file    htdocs/modulebuilder/class/RightsBlockRenderer.class.php
 * \ingroup modulebuilder
 * \brief   Renders the permission checks of a template page for a given rights configuration.
 */

require_once DOL_DOCUMENT_ROOT.'/modulebuilder/class/RightsConfig.class.php';

/**
 * Renders the permission checks of a template page for a given rights configuration.
 *
 * Works on the RIGHTS blocks of a template, before the module and object placeholders are
 * substituted: the object key stays 'myobject' unless overridden.
 */
final class RightsBlockRenderer
{
	const MARKER_BEGIN = '// BEGIN MODULEBUILDER RIGHTS';
	const MARKER_END = '// END MODULEBUILDER RIGHTS';

	/** Placeholder of the object key in the templates */
	const DEFAULT_KEY = 'myobject';

	private const BLOCK_PATTERN = '/^\h*\/\/ BEGIN MODULEBUILDER RIGHTS\h*\R(.*?)^\h*\/\/ END MODULEBUILDER RIGHTS\h*\R/ms';

	private const LEFTOVER_MARKER_PATTERN = '/\/\/ (BEGIN|END) MODULEBUILDER RIGHTS\h*\r?$/m';

	private const SWITCH_PATTERN = '/^(\h*)if \(\$enablepermissioncheck\) \{\R(.*?)^\1\} else \{\R(.*?)^\1\}\h*\R/ms';

	private const COMMENT_LINE_PATTERN = '/^\h*\/\/.*\R/m';

	private const SWITCH_INIT_PATTERN = '/^\h*\$enablepermissioncheck = getDolGlobalInt\([^;]*\);\h*\R/m';

	private const TEMPLATE_CHECK_PATTERN = "/hasRight\\('mymodule', 'myobject', '(read|write|delete)'\\)/";

	private const ANY_CHECK_PATTERN = "/hasRight\\('mymodule', '([^']*)', '([^']*)'\\)/";

	private const LOOSE_CHECK_PATTERN = '/hasRight\s*\(|->rights->/i';

	/**
	 * Render every RIGHTS block of a template.
	 *
	 * @param string            $content Template content
	 * @param RightsConfig|null $config  Rights configuration, null for an object generated before rights modes existed
	 * @return string Content without markers; a null configuration keeps the original checks
	 * @throws \RuntimeException When a block has an unexpected shape or a rendered block would check an undeclared permission
	 */
	public static function renderFile(string $content, ?RightsConfig $config): string
	{
		$rendered = preg_replace_callback(
			self::BLOCK_PATTERN,
			/**
			 * @param string[] $matches Block match
			 * @return string Rendered block
			 */
			static function (array $matches) use ($config): string {
				return $config === null ? $matches[1] : self::renderBlock($matches[1], $config);
			},
			$content
		);
		if ($rendered === null) {
			throw new \RuntimeException('Failed to scan the MODULEBUILDER RIGHTS blocks');
		}
		if (preg_match(self::LEFTOVER_MARKER_PATTERN, $rendered)) {
			throw new \RuntimeException('Unbalanced MODULEBUILDER RIGHTS marker');
		}

		return $rendered;
	}

	/**
	 * Permission condition of a generated menu entry.
	 *
	 * @param RightsConfig $config    Rights configuration
	 * @param string       $operation One of RightsConfig::OPERATIONS
	 * @return string PHP condition, double quoted strings to fit the menu declarations
	 */
	public static function renderMenuPerms(RightsConfig $config, string $operation): string
	{
		if (!$config->generatesRights()) {
			return '1';
		}

		return '$user->hasRight("mymodule", "'.self::getKey($config).'", "'.$config->getCodeFor($operation).'")';
	}

	/**
	 * @param string       $block  Block content, markers excluded
	 * @param RightsConfig $config Rights configuration
	 * @return string Rendered block
	 * @throws \RuntimeException When the block has an unexpected shape or would check an undeclared permission
	 */
	private static function renderBlock(string $block, RightsConfig $config): string
	{
		$switch = array();
		if (preg_match(self::SWITCH_PATTERN, $block, $switch, PREG_OFFSET_CAPTURE)) {
			$body = $config->generatesRights() ? $switch[2][0] : $switch[3][0];
			$head = substr($block, 0, $switch[0][1]);
			$tail = substr($block, $switch[0][1] + strlen($switch[0][0]));
			$head = (string) preg_replace(array(self::SWITCH_INIT_PATTERN, self::COMMENT_LINE_PATTERN), '', $head);
			$block = $head.preg_replace('/^\t/m', '', $body).$tail;
		} elseif (strpos($block, '$enablepermissioncheck') !== false) {
			throw new \RuntimeException('A MODULEBUILDER RIGHTS block uses $enablepermissioncheck without the expected if/else');
		} elseif (!$config->generatesRights()) {
			throw new \RuntimeException('A MODULEBUILDER RIGHTS block without an open branch cannot be generated without permissions');
		}

		if ($config->generatesRights()) {
			$key = self::getKey($config);
			$block = (string) preg_replace_callback(
				self::TEMPLATE_CHECK_PATTERN,
				/**
				 * @param string[] $matches Check match
				 * @return string Check on the mapped permission
				 */
				static function (array $matches) use ($config, $key): string {
					return "hasRight('mymodule', '".$key."', '".$config->getCodeFor($matches[1])."')";
				},
				$block
			);
		}

		self::assertOnlyDeclaredChecks($block, $config);

		return $block;
	}

	/**
	 * @param string       $block  Rendered block
	 * @param RightsConfig $config Rights configuration
	 * @return void
	 * @throws \RuntimeException When the block checks a permission the configuration does not declare
	 */
	private static function assertOnlyDeclaredChecks(string $block, RightsConfig $config): void
	{
		if (strpos($block, '$enablepermissioncheck') !== false) {
			throw new \RuntimeException('A rendered MODULEBUILDER RIGHTS block still depends on $enablepermissioncheck');
		}

		$checks = array();
		preg_match_all(self::ANY_CHECK_PATTERN, $block, $checks, PREG_SET_ORDER);
		if (preg_match_all(self::LOOSE_CHECK_PATTERN, $block) !== count($checks)) {
			throw new \RuntimeException('A rendered MODULEBUILDER RIGHTS block holds a permission check written in an unexpected form');
		}
		foreach ($checks as $check) {
			if (!$config->generatesRights() || $check[1] !== self::getKey($config) || !in_array($check[2], $config->getGeneratedCodes(), true)) {
				throw new \RuntimeException('A rendered MODULEBUILDER RIGHTS block checks the undeclared permission '.$check[1].'/'.$check[2]);
			}
		}
	}

	/**
	 * @param RightsConfig $config Rights configuration
	 * @return string Permission key as written in the template, before placeholder substitution
	 */
	private static function getKey(RightsConfig $config): string
	{
		$override = $config->getRightsKeyOverride();

		return $override !== '' ? $override : self::DEFAULT_KEY;
	}
}
