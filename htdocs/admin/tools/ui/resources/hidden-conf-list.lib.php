<?php

/**
 * @phpstan-type ConfValueType 'bool'|'int'|'float'|'string'|'array'|'json'
 *
 * @phpstan-type HiddenConf array{
 *     name: string,
 *     description: string,
 *     type?: ConfValueType,
 *     example?: string|null,
 *     added_in?: string|null,
 *     removed_in?: string|null,
 *     deprecated?: bool,
 *     deprecated_since?: string|null,
 *     module?: string,
 *     tags?: string[]
 * }
 *
 * @phpstan-type HiddenConfSection array{
 *     label: string,
 *     confs: array<string, HiddenConf>
 * }
 *
 * @phpstan-type HiddenConfList array<string, HiddenConfSection>
 *
 * @return array<string, array{
 *     label: string,
 *     confs: array<string, array{
 *         name: string,
 *         description: string,
 *         type: 'bool'|'int'|'float'|'string'|'array'|'json',
 *         example: string|null,
 *         added_in: string|null,
 *         removed_in: string|null,
 *         deprecated: bool,
 *         deprecated_since: string|null,
 *         module: string,
 *         tags: string[]
 *     }>
 * }>
 */
function getListOfHiddenConf()
{
	return [
		'GLOBAL' => [
			'label' => 'Global / System',
			'confs' => [
				'ADD_UNSPLASH_LOGIN_BACKGROUND' => [
					'name' => 'ADD_UNSPLASH_LOGIN_BACKGROUND',
					'title' => 'Use Unsplash random image as login background (external call)',
					'description' => 'The background image will be refreshed on every login page refresh. Background image is pulled from the popular open source image website Unsplash. If an already saved static background image exists, then this code will override it (but not delete the saved image). Warning: Using this may allow this external website to steel your login credentials (the value to be put is the URL of the service',
					'type' => 'string',
					'example' => 'https://source.unsplash.com/random',
					'added_in' => '10',
					'removed_in' => null,
					'deprecated' => false,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['ui', 'external'],
				],

				'MAIN_HIDE_POWERED_BY' => [
					'name' => 'MAIN_HIDE_POWERED_BY',
					'title' => 'Hide "Powered by Dolibarr" logo on public pages',
					'description' => '',
					'type' => 'bool',
					'example' => '1',
					'added_in' => null,
					'removed_in' => null,
					'deprecated' => false,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['ui'],
				],

				'MAIN_APPLICATION_TITLE' => [
					'name' => 'MAIN_APPLICATION_TITLE',
					'title' => 'Override application title (login page)',
					'description' => 'This will change the title of software (that appears on the login page by default).
									Warning: changing this may make Dolibarr version detection to fail by smartphone applications like
									<a href="https://wiki.dolibarr.org/index.php/Application_Android_-_DoliDroid" title="Application Android - DoliDroid" target="_blank">Application Android - DoliDroid</a>,
									breaking some features when using Dolibarr from such application. If the text start with a "+",
									the text will be added to the standard "Dolibarr" label instead of replacing it.',
					'type' => 'string',
					'example' => 'My ERP',
					'added_in' => null,
					'removed_in' => null,
					'deprecated' => false,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['ui'],
				],

				'MAIN_AUTOFILL_DATE' => [
					'name' => 'MAIN_AUTOFILL_DATE',
					'title' => 'Auto-fill document dates with current date (dangerous)',
					'description' => ' If this constant is defined (to something other than 0), the date of invoice, proposal, order or payment are auto-filled with the current date. It is highly recommended to NOT ENABLE this feature. This can create a lot of input errors with data not validated by users. This leads to incorrect values saved in the database causing confusion when you have to do your accountancy reports!',
					'type' => 'bool',
					'example' => '1',
					'added_in' => null,
					'removed_in' => null,
					'deprecated' => true,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['danger'],
				],

				'MAIN_AUTOFILL_DATE_PROPOSAL' => [
					'name' => 'MAIN_AUTOFILL_DATE_PROPOSAL',
					'title' => 'Auto-fill proposal date',
					'description' => 'If this constant is defined (to something other than 0), the date of proposal is auto-filled with the current date.',
					'type' => 'bool',
					'example' => '1',
					'added_in' => null,
					'removed_in' => null,
					'deprecated' => true,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['proposal'],
				],

				'MAIN_DEFAULT_LANGUAGE_FILTER' => [
					'name' => 'MAIN_DEFAULT_LANGUAGE_FILTER',
					'title' => 'Filter out en_US from language list',
					'description' => 'Useful if you do not want to show <code>en_US</code> language option in combo boxes (other languages can easily be removed in <code>langs</code> directory but not <code>en_US</code>)',
					'type' => 'bool',
					'example' => '1',
					'added_in' => '10.0',
					'removed_in' => null,
					'deprecated' => false,
					'deprecated_since' => null,
					'module' => 'core',
					'tags' => ['ui'],
				],

				//              'MAIN_LANGUAGES_ALLOWED' => [
				//                  'name' => 'MAIN_LANGUAGES_ALLOWED',
				//                  'title' => 'Restrict allowed languages list',
				//                  'description' => '',
				//                  'type' => 'string',
				//                  'example' => 'fr_FR,en_US,de_DE',
				//                  'added_in' => '11.0',
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['ui'],
				//              ],
				//
				//              'MAIN_DISABLE_FULL_SCANLIST' => [
				//                  'name' => 'MAIN_DISABLE_FULL_SCANLIST',
				//                  'title' => 'Disable full table scan for pagination performance',
				//                  'description' => '',
				//                  'type' => 'bool',
				//                  'example' => '1',
				//                  'added_in' => null,
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['performance'],
				//              ],
				//
				//              'MAIN_DISABLE_JQUERY_JNOTIFY' => [
				//                  'name' => 'MAIN_DISABLE_JQUERY_JNOTIFY',
				//                  'title' => 'Disable JNotify alerts',
				//                  'description' => '',
				//                  'type' => 'bool',
				//                  'example' => '1',
				//                  'added_in' => null,
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['ui'],
				//              ],
				//
				//              'MAIN_DISABLE_AJAX_COMBOX' => [
				//                  'name' => 'MAIN_DISABLE_AJAX_COMBOX',
				//                  'title' => 'Disable AJAX autocomplete in selects',
				//                  'description' => '',
				//                  'type' => 'bool',
				//                  'example' => '1',
				//                  'added_in' => '3.6',
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['ui'],
				//              ],
				//
				//              'MAIN_DISABLE_MULTIPLE_FILEUPLOAD' => [
				//                  'name' => 'MAIN_DISABLE_MULTIPLE_FILEUPLOAD',
				//                  'title' => 'Disable multiple file upload',
				//                  'description' => '',
				//                  'type' => 'bool',
				//                  'example' => '1',
				//                  'added_in' => null,
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['upload'],
				//              ],
				//
				//              'MAIN_DISABLE_TRUNC' => [
				//                  'name' => 'MAIN_DISABLE_TRUNC',
				//                  'title' => 'Disable truncation in select lists',
				//                  'description' => '',
				//                  'type' => 'bool',
				//                  'example' => '1',
				//                  'added_in' => '7.0',
				//                  'removed_in' => null,
				//                  'deprecated' => false,
				//                  'deprecated_since' => null,
				//                  'module' => 'core',
				//                  'tags' => ['ui'],
				//              ],
			],
		],

		//      'SECURITY' => [
		//          'label' => 'Security',
		//          'confs' => [
		//              'MAIN_SECURITY_CSRF_WITH_TOKEN' => [
		//                  'name' => 'MAIN_SECURITY_CSRF_WITH_TOKEN',
		//                  'title' => 'CSRF protection level',
		//                  'description' => '',
		//                  'type' => 'int',
		//                  'example' => '2',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'core',
		//                  'tags' => ['security'],
		//              ],
		//
		//              'MAIN_RESTRICT_IPS' => [
		//                  'name' => 'MAIN_RESTRICT_IPS',
		//                  'title' => 'Restrict access by IP range',
		//                  'description' => '',
		//                  'type' => 'string',
		//                  'example' => '192.168.0.0/24',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'core',
		//                  'tags' => ['security'],
		//              ],
		//
		//              'MAIN_ALLOW_SVG_FILES_AS_IMAGES' => [
		//                  'name' => 'MAIN_ALLOW_SVG_FILES_AS_IMAGES',
		//                  'title' => 'Allow SVG upload (security risk)',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => true,
		//                  'deprecated_since' => null,
		//                  'module' => 'core',
		//                  'tags' => ['danger'],
		//              ],
		//          ],
		//      ],
		//
		//      'INVOICE' => [
		//          'label' => 'Invoices',
		//          'confs' => [
		//              'INVOICE_CAN_BE_EDITED_EVEN_IF_PAYMENT_DONE' => [
		//                  'name' => 'INVOICE_CAN_BE_EDITED_EVEN_IF_PAYMENT_DONE',
		//                  'title' => 'Allow invoice editing after payment started',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'invoice',
		//                  'tags' => ['danger'],
		//              ],
		//
		//              'INVOICE_CAN_ALWAYS_BE_REMOVED' => [
		//                  'name' => 'INVOICE_CAN_ALWAYS_BE_REMOVED',
		//                  'title' => 'Allow invoice deletion even if not last',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'invoice',
		//                  'tags' => ['danger'],
		//              ],
		//          ],
		//      ],
		//
		//      'PRODUCT' => [
		//          'label' => 'Products',
		//          'confs' => [
		//              'PRODUCT_DISABLE_SELLBY' => [
		//                  'name' => 'PRODUCT_DISABLE_SELLBY',
		//                  'title' => 'Hide sell-by date field',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => '13',
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'product',
		//                  'tags' => ['ui'],
		//              ],
		//
		//              'PRODUCT_DISABLE_EATBY' => [
		//                  'name' => 'PRODUCT_DISABLE_EATBY',
		//                  'title' => 'Hide eat-by date field',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => '13',
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'product',
		//                  'tags' => ['ui'],
		//              ],
		//
		//              'PRODUCT_DENY_CHANGE_PRODUCT_TYPE' => [
		//                  'name' => 'PRODUCT_DENY_CHANGE_PRODUCT_TYPE',
		//                  'title' => 'Prevent changing product type',
		//                  'description' => '',
		//                  'type' => 'bool',
		//                  'example' => '1',
		//                  'added_in' => null,
		//                  'removed_in' => null,
		//                  'deprecated' => false,
		//                  'deprecated_since' => null,
		//                  'module' => 'product',
		//                  'tags' => ['security'],
		//              ],
		//          ],
		//      ],
	];
}





/**
 * Render hidden Dolibarr configuration list
 *
 * @return void
 */
function renderHiddenConfList(): void
{
	$data = getListOfHiddenConf();

	foreach ($data as $sectionKey => $section) {
		renderSection($sectionKey, $section);
	}
}

/**
 * Render one section
 *
 * @param string               $sectionKey the selection
 * @param array<string, mixed> $section section array
 *
 * @return void
 */
function renderSection(string $sectionKey, array $section): void
{
	print '<div class="documentation-section" data-section="'.dol_escape_htmltag($sectionKey).'">';
	print '<h2 class="documentation-title">' . htmlspecialchars($section['label']) . '</h2>';

	print '<div class="conf-list">';

	foreach ($section['confs'] as $conf) {
		renderConfItem($conf);
	}

	print '</div>';
	print '</div>';
}

/**
 * Render one configuration item
 *
 * @param array<string, mixed> $conf conf item array
 *
 * @return void
 */
function renderConfItem(array $conf): void
{
	global $langs;

	$type = $conf['type'] ?? 'string';
	if ($type === 'bool') {
		$type.= ' ( 0 | 1 )';
	}

	// TODO : compare current DOL_VERSION with 'removed_in'
	// TODO : Add used badge if conf is currently Used, and add in search form a warning if deprecated conf are used

	print '<div class="conf-item">';

	print '		<div class="conf-header">';
	print '			<span class="conf-name">' . dolPrintHTML($conf['name']) . '</span>';
	print '			<span class="conf-badges">' . renderBadges($conf) . '</span>';
	print '		</div>';

	print '		<div class="conf-body">';


	$description = trim((string) ($conf['description'] ?? ''));

	if (!empty($description)) {
		print '		<details class="conf-details">';
		print '			<summary class="conf-title">' .  dolPrintHTML($conf['title'] ?? $conf['name']) . '</summary>';
		print '			<div class="conf-desc">' . dolPrintHTML($description, 0, ['code']) . '</div>';
		print '		</details>';
	} else {
		print '		<div class="conf-title">' . ($conf['title']??'') . '</div>';
	}


	print '			<div class="conf-meta">';

	print renderMeta('Type', $type);
	print renderMeta('Example', $conf['example']);
	print renderMeta($langs->trans('AddedInVersion'), $conf['added_in']);
	print renderMeta('Removed', $conf['removed_in']);

	print '			</div>';

	print '		</div>';

	print '</div>';
}

/**
 * Render metadata line
 *
 * @param string      $label meta label
 * @param string|null $value meta value
 *
 * @return string
 */
function renderMeta(string $label, ?string $value): string
{
	if (empty($value)) {
		return '';
	}

	return '<div class="conf-meta-line">
				<span class="meta-label">' . dolPrintHTML($label) . ':</span>
				<span class="meta-value">' . dolPrintHTML($value) . '</span>
			</div>';
}

/**
 * Render badges from tags + deprecated state
 *
 * @param array<string, mixed> $conf render tags for a conf item
 *
 * @return string
 */
function renderBadges(array $conf): string
{
	global $langs;

	$html = '';

	$tags = $conf['tags'] ?? [];

	foreach ($tags as $tag) {
		$class = getTagBadgeClass($tag);
		$html .= '<span class="badge ' . $class . '">' . dol_escape_htmltag($tag) . '</span> ';
	}

	if (!empty($conf['deprecated'])) {
		$html .= '<span class="badge badge-danger">'. $langs->trans('deprecated') . '</span>';
	}

	return $html;
}

/**
 * Map tag to bootstrap-like badge class
 *
 * @param string $tag the tag name
 *
 * @return string
 */
function getTagBadgeClass(string $tag): string
{
	return match ($tag) {
		'security', 'danger' => 'badge-danger',
		'ui' => 'badge-info',
		'performance' => 'badge-warning',
		'upload' => 'badge-secondary',
		default => 'badge-light',
	};
}

/**
 * Normalize and validate hidden configuration list.
 *
 * - Ensures required fields
 * - Applies defaults
 * - Normalizes types
 *
 * @param array<string, array{label: string, confs: array<string, array<string, mixed>>}> $confList the conf list
 * @return array<string, array{label: string, confs: array<string, array<string, mixed>>}>
 */
function normalizeHiddenConfList(array $confList): array
{
	foreach ($confList as $sectionKey => &$section) {
		if (!isset($section['confs']) || !is_array($section['confs'])) {
			$section['confs'] = [];
		}

		foreach ($section['confs'] as $confKey => &$conf) {
			$conf = normalizeHiddenConf($conf, $confKey);
		}
	}

	return $confList;
}

/**
 * Normalize a single configuration entry
 *
 * @param array<string, mixed> $conf th conf array
 * @param string $fallbackName a fallback name
 * @return array<string, mixed>
 */
function normalizeHiddenConf(array $conf, string $fallbackName): array
{
	// REQUIRED FIELDS
	$name = $conf['name'] ?? $fallbackName;
	$description = $conf['description'] ?? '';

	if ($description === '') {
		throw new InvalidArgumentException("Missing description for conf: " . $name);
	}

	// TYPE NORMALIZATION
	$type = $conf['type'] ?? 'string';

	$allowedTypes = ['bool', 'int', 'float', 'string', 'array', 'json'];
	if (!in_array($type, $allowedTypes, true)) {
		$type = 'string';
	}

	// TAGS NORMALIZATION
	$tags = $conf['tags'] ?? [];
	if (!is_array($tags)) {
		$tags = [];
	}

	$tags = array_values(array_unique(array_map('strval', $tags)));

	// BOOLEAN NORMALIZATION
	$deprecated = (bool) ($conf['deprecated'] ?? false);

	return [
		'name' => (string) $name,
		'description' => (string) $description,
		'type' => $type,
		'example' => $conf['example'] ?? null,
		'added_in' => $conf['added_in'] ?? null,
		'removed_in' => $conf['removed_in'] ?? null,
		'deprecated' => $deprecated,
		'deprecated_since' => $conf['deprecated_since'] ?? null,
		'module' => $conf['module'] ?? 'core',
		'tags' => $tags,
	];
}
