<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

// Load Dolibarr environment
require '../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

// Includes
require_once DOL_DOCUMENT_ROOT . '/admin/tools/ui/class/documentation.class.php';

// Load documentation translations
$langs->load('uxdocumentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader('Icons');

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Components','Icons');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

		<?php $documentation->showBreadCrumb(); ?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocIconsTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('DocIconsMainDescription'); ?></p>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>


			<!-- List of usage font awesome icon -->
			<div class="documentation-section" id="img-picto-section-list">

				<?php

				$iconFileName = 'shims.json';
				$iconFilePath = DOL_DOCUMENT_ROOT . '/theme/common/fontawesome-5/metadata';

				$fontAwesomeIconRaw = file_get_contents($iconFilePath. '/' .$iconFileName);
				if ($fontAwesomeIconRaw === false) {
					dol_print_error($db, 'Error missing file  '. $iconFilePath . '/' . $iconFileName);
				}

				$fontAwesomeIcons = json_decode($fontAwesomeIconRaw);
				if ($fontAwesomeIcons === null) {
					dol_print_error($db, 'Error decoding '. $iconFilePath . '/' . $iconFileName);
				}
				?>

				<h2 class="documentation-title"><?php echo $langs->trans('DocIconsListImgPicto'); ?></h2>
				<?php /* <p class="documentation-text"><?php echo $langs->trans('DocDocIconsListDescription'); ?></p>*/ ?>
				<div class="documentation-example">
					<div class="documentation-fontawesome-icon-list">
						<?php
						foreach (getImgPictoNameList() as $iconName) {
							$labelAlt = 'Text on title tag for tooltip';
							$iconCode =  img_picto($iconName, $iconName);
							print '<div class="info-box ">
									<span class="info-box-icon bg-infobox-expensereport">
										'.$iconCode.'
									</span>
									<div class="info-box-content">
										<div class="info-box-title" >'. $iconName .'</div>
										<div class="info-box-lines">
											<div class="info-box-line spanoverflow nowrap">
												<div class="inline-block nowraponall">
													<div class="documentation-code"><pre>'.dol_htmlentities('img_picto(\''.$labelAlt.'\', \''.$iconName.'\')').'</pre></div>
												</div>
											</div>
										</div><!-- /.info-box-lines -->
									</div><!-- /.info-box-content -->
								</div>';
						}
						?>
					</div>
				</div>
			</div>
			<!--  -->


			<!-- List of usage font awesome icon -->
			<div class="documentation-section" id="icon-section-list">

				<?php

				$iconFileName = 'shims.json';
				$iconFilePath = DOL_DOCUMENT_ROOT . '/theme/common/fontawesome-5/metadata';

				$fontAwesomeIconRaw = file_get_contents($iconFilePath. '/' .$iconFileName);
				if ($fontAwesomeIconRaw === false) {
					dol_print_error($db, 'Error missing file  '. $iconFilePath . '/' . $iconFileName);
				}

				$fontAwesomeIcons = json_decode($fontAwesomeIconRaw);
				if ($fontAwesomeIcons === null) {
					dol_print_error($db, 'Error decoding '. $iconFilePath . '/' . $iconFileName);
				}
				?>

				<h2 class="documentation-title"><?php echo $langs->trans('DocIconsListFontAwesome'); ?></h2>
				<?php /* <p class="documentation-text"><?php echo $langs->trans('DocDocIconsListDescription'); ?></p>*/ ?>
				<div class="documentation-example">

					<div class="documentation-fontawesome-icon-list">

					<?php
					$alreadyDisplay = [];
					if ($fontAwesomeIcons && is_array($fontAwesomeIcons)) {
						foreach ($fontAwesomeIcons as $iconData) {
							$class= $iconData[1]??'fa';
							if (!empty($iconData[2])) {
								$class.= ' fa-'.$iconData[2];
							} else {
								$class.= ' fa-'.$iconData[0];
							}

							if (in_array($class, $alreadyDisplay)) {
								continue;
							}

							$alreadyDisplay[] = $class;
							$iconCode =  '<span class="'.$class.'" ></span>';


							print '<div class="info-box ">
										<span class="info-box-icon bg-infobox-expensereport">
											'.$iconCode.'
										</span>
										<div class="info-box-content">
											<div class="info-box-title" >'. ($iconData[2]??($iconData[0]??'')) .'</div>
											<div class="info-box-lines">
												<div class="info-box-line spanoverflow nowrap">
													<div class="inline-block nowraponall">
														<div class="documentation-code"><pre>'.dol_htmlentities($iconCode).'</pre></div>
													</div>
												</div>
											</div><!-- /.info-box-lines -->
										</div><!-- /.info-box-content -->
									</div>';
						}
					}
					?>
					</div>
				</div>
			</div>
			<!--  -->
		</div>
	</div>

<?php
// Output close body + html
$documentation->docFooter();
