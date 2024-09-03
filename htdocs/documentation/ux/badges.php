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

$res=0;
if (! $res && file_exists("../main.inc.php")) : $res=@include '../main.inc.php'; 
endif;
if (! $res && file_exists("../../main.inc.php")) : $res=@include '../../main.inc.php'; 
endif; 

// Protection if external user
if ($user->socid > 0) : accessforbidden(); 
endif;

// Includes
dol_include_once('documentation/class/documentation.class.php');

// Load documentation translations
$langs->load('documentation@documentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader('Badges');

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Elements','Badges');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">
        
        <?php $documentation->showBreadCrumb(); ?>

        <div class="doc-content-wrapper">

            <h1 class="documentation-title"><?php echo $langs->trans('DocBadgeTitle'); ?></h1>
              <p class="documentation-text"><?php echo $langs->trans('DocBadgeMainDescription'); ?></p>

              <!-- Summary -->
              <?php
                $summary = array(
                'DocBasicUsage' => '#badgesection-basicusage',
                'DocBadgeContextualVariations' => '#badgesection-contextvariations',
                'DocBadgeDefaultStatus' => '#badgesection-defaultstatus',
                'DocBadgePillBadges' => '#badgesection-pill',
                'DocBadgeDotBadges' => '#badgesection-dot',
                'DocBadgeLinks' => '#badgesection-links',
                'DocBadgeHelper' => '#badgesection-dolgetbadge'
                );
                ?>
              <ul class="documentation-summary">
                  <?php foreach ($summary as $summary_label => $summary_link): ?>
                      <li>
                          <a href="<?php echo $summary_link; ?>"><?php echo $langs->trans($summary_label); ?></a>
                      </li>
                  <?php endforeach; ?>
              </ul>

              <!-- Basic usage -->
            <div class="documentation-section" id="badgesection-basicusage">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeScaleDescription'); ?></p>
                <div class="documentation-example">
                    <h1>Example heading <span class="badge badge-secondary">New</span></h1>
                    <h2>Example heading <span class="badge badge-secondary">New</span></h2>
                    <h3>Example heading <span class="badge badge-secondary">New</span></h3>
                    <h4>Example heading <span class="badge badge-secondary">New</span></h4>
                    <h5>Example heading <span class="badge badge-secondary">New</span></h5>
                    <h6>Example heading <span class="badge badge-secondary">New</span></h6>
                </div>

                <?php 
                $lines = array(
                    '<h1>Example heading <span class="badge badge-secondary">New</span></h1>',
                    '<h2>Example heading <span class="badge badge-secondary">New</span></h2>',
                    '<h3>Example heading <span class="badge badge-secondary">New</span></h3>',
                    '<h4>Example heading <span class="badge badge-secondary">New</span></h4>',
                    '<h5>Example heading <span class="badge badge-secondary">New</span></h5>',
                    '<h6>Example heading <span class="badge badge-secondary">New</span></h6>'
                );
                echo $documentation->showCode($lines); ?>

                <p class="documentation-text"><?php echo $langs->trans('DocBadgeUseOnLinksOrButtons'); ?></p>
                <div class="documentation-example">
                    <button type="button" class="button">
                        <?php echo $langs->trans('Notifications'); ?> <span class="badge badge-primary">4</span>
                    </button>
                </div>

                <?php
                $lines = array(
                    '<button type="button" class="button">',
                    '	Notifications <span class="badge badge-primary">4</span>',
                    '</button>',
                );
                echo $documentation->showCode($lines); ?>

                <div class="warning">
                    <p class="documentation-text"><?php echo $langs->trans('DocBadgeWarningAriaHidden1'); ?></p>
                    <p class="documentation-text"><?php echo $langs->trans('DocBadgeWarningAriaHidden2'); ?></p>
                    <p class="documentation-text"><strong><?php echo $langs->trans('DocBadgeWarningAriaHidden3'); ?></strong></p>
                </div>

                <div class="documentation-example">
                    <button type="button" class="button">
                        Profile <span class="badge badge-primary" aria-label="9 unread messages" >9</span>
                        <span class="sr-only">unread messages</span>
                    </button>
                </div>

                <?php
                $lines = array(
                    '<button type="button" class="button">',
                    '	Profile <span class="badge badge-primary" aria-label="9 unread messages" >9</span>',
                    '	<span class="sr-only">unread messages</span>',
                    '</button>',
                );
                echo $documentation->showCode($lines); ?>
            </div>

            <!-- Contextual variations -->
            <div class="documentation-section" id="badgesection-contextvariations">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgeContextualVariations'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeContextualVariationsDescription'); ?></p>
                <div class="documentation-example">
                    <span class="badge badge-primary">Primary</span>
                    <span class="badge badge-secondary">Secondary</span>
                    <span class="badge badge-success">Success</span>
                    <span class="badge badge-danger">Danger</span>
                    <span class="badge badge-warning">Warning</span>
                    <span class="badge badge-info">Info</span>
                    <span class="badge badge-light">Light</span>
                    <span class="badge badge-dark">Dark</span>
                </div>
                <?php 
                $lines = array(
                    '<span class="badge badge-primary">Primary</span>',
                    '<span class="badge badge-secondary">Secondary</span>',
                    '<span class="badge badge-success">Success</span>',
                    '<span class="badge badge-danger">Danger</span>',
                    '<span class="badge badge-warning">Warning</span>',
                    '<span class="badge badge-info">Info</span>',
                    '<span class="badge badge-light">Light</span>',
                    '<span class="badge badge-dark">Dark</span>',
                );
                echo $documentation->showCode($lines); ?>
                <div class="warning">
                    <p class="documentation-text"><strong><?php echo $langs->trans('DocBadgeContextualVariationsWarning1'); ?></strong></p>
                    <p class="documentation-text"><?php echo $langs->trans('DocBadgeContextualVariationsWarning2'); ?></p>
                </div>
            </div>

            <!-- Default status -->
            <div class="documentation-section" id="badgesection-defaultstatus">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgeDefaultStatus'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeDefaultStatusDescription'); ?></p>
                <div class="documentation-example">
                    <?php for ($i = 0; $i <= 9; $i++) : ?>
                        <span class="badge badge-status<?php print $i; ?>" >status-<?php print $i; ?></span>
                    <?php endfor; ?>
                </div>
                <?php 
                $lines = array();
                for ($i = 0; $i <= 9; $i++) :
                    $lines[] = '<span class="badge badge-status'.$i.'">status-'.$i.'</span>';
                endfor;
                echo $documentation->showCode($lines); ?>
            </div>

            <!-- Pill badges -->
            <div class="documentation-section" id="badgesection-pill">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgePillBadges'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgePillBadgesDescription'); ?></p>
                <div class="documentation-example">
                    <span class="badge badge-pill badge-primary">Primary</span>
                    <span class="badge badge-pill badge-secondary">Secondary</span>
                    <span class="badge badge-pill badge-success">Success</span>
                    <span class="badge badge-pill badge-danger">Danger</span>
                    <span class="badge badge-pill badge-warning">Warning</span>
                    <span class="badge badge-pill badge-info">Info</span>
                    <span class="badge badge-pill badge-light">Light</span>
                    <span class="badge badge-pill badge-dark">Dark</span>
                    <?php for ($i = 0; $i <= 9; $i++) : ?>
                        <span class="badge badge-pill badge-status<?php print $i; ?>">status<?php print $i; ?></span>
                    <?php endfor; ?>
                </div>
                <?php 
                $lines = array(
                    '<span class="badge badge-pill badge-primary">Primary</span>',
                    '<span class="badge badge-pill badge-secondary">Secondary</span>',
                    '<span class="badge badge-pill badge-success">Success</span>',
                    '<span class="badge badge-pill badge-danger">Danger</span>',
                    '<span class="badge badge-pill badge-warning">Warning</span>',
                    '<span class="badge badge-pill badge-info">Info</span>',
                    '<span class="badge badge-pill badge-light">Light</span>',
                    '<span class="badge badge-pill badge-dark">Dark</span>',
                );
                for ($i = 0; $i <= 9; $i++) :
                    $lines[] = '<span class="badge badge-pill badge-status'.$i.'">status-'.$i.'</span>';
                endfor;
                echo $documentation->showCode($lines); ?>
            </div>

            <!-- Dot badges -->
            <div class="documentation-section" id="badgesection-dot">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgeDotBadges'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeDotBadgesDescription'); ?></p>
                <div class="documentation-example">
                    <span class="badge badge-dot badge-primary"></span>
                    <span class="badge badge-dot badge-secondary"></span>
                    <span class="badge badge-dot badge-success"></span>
                    <span class="badge badge-dot badge-danger"></span>
                    <span class="badge badge-dot badge-warning"></span>
                    <span class="badge badge-dot badge-info"></span>
                    <span class="badge badge-dot badge-light"></span>
                    <span class="badge badge-dot badge-dark"></span>
                    <?php for ($i = 0; $i <= 9; $i++) : ?>
                        <span class="badge badge-dot badge-status<?php print $i; ?>"></span>
                    <?php endfor; ?>
                </div>
                <?php 
                $lines = array(
                    '<span class="badge badge-dot badge-primary"></span>',
                    '<span class="badge badge-dot badge-secondary"></span>',
                    '<span class="badge badge-dot badge-success"></span>',
                    '<span class="badge badge-dot badge-danger"></span>',
                    '<span class="badge badge-dot badge-warning"></span>',
                    '<span class="badge badge-dot badge-info"></span>',
                    '<span class="badge badge-dot badge-light"></span>',
                    '<span class="badge badge-dot badge-dark"></span>',
                );
                for ($i = 0; $i <= 9; $i++) :
                    $lines[] = '<span class="badge badge-dot badge-status'.$i.'"></span>';
                endfor;
                echo $documentation->showCode($lines); ?>
            </div>

            <!-- Links -->
            <div class="documentation-section" id="badgesection-links">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgeLinks'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeLinksDescription'); ?></p>
                <div class="documentation-example">
                    <a href="#" class="badge badge-primary">Primary</a>
                    <a href="#" class="badge badge-secondary">Secondary</a>
                    <a href="#" class="badge badge-success">Success</a>
                    <a href="#" class="badge badge-danger">Danger</a>
                    <a href="#" class="badge badge-warning">Warning</a>
                    <a href="#" class="badge badge-info">Info</a>
                    <a href="#" class="badge badge-light">Light</a>
                    <a href="#" class="badge badge-dark">Dark</a>
                    <?php for ($i = 0; $i <= 9; $i++) : ?>
                    <a href="#" class="badge badge-status<?php print $i; ?>" >status<?php print $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php 
                $lines = array(
                    '<a href="#" class="badge badge-primary">Primary</a>',
                    '<a href="#" class="badge badge-secondary">Secondary</a>',
                    '<a href="#" class="badge badge-success">Success</a>',
                    '<a href="#" class="badge badge-danger">Danger</a>',
                    '<a href="#" class="badge badge-warning">Warning</a>',
                    '<a href="#" class="badge badge-info">Info</a>',
                    '<a href="#" class="badge badge-light">Light</a>',
                    '<a href="#" class="badge badge-dark">Dark</a>',
                );
                for ($i = 0; $i <= 9; $i++) :
                    $lines[] = '<a href="#" class="badge badge-status'.$i.'" >status'.$i.'</a>';
                endfor;
                echo $documentation->showCode($lines); ?>
            </div>

            <!-- Use badge helper function -->
            <div class="documentation-section" id="badgesection-dolgetbadge">
                <h2 class="documentation-title"><?php echo $langs->trans('DocBadgeHelper'); ?></h2>
                <p class="documentation-text"><?php echo $langs->trans('DocBadgeHelperDescription'); ?></p>
                <div class="documentation-example">
                    <?php print dolGetBadge('your label for accessibility', 'your label <u>with</u> <em>html</em>', 'primary'); ?>
                    <?php print dolGetBadge('your label for accessibility', 'your label <u>with</u> <em>html</em>', 'danger', 'pill'); ?>
                    <?php print dolGetBadge('your label for accessibility', 'your label', 'warning', 'dot'); ?>
                </div>
                <?php 
                $lines = array(
                    "/**",
                    " * Function dolGetBadge",
                    " *",
                    " * @param  string  \$label  label of badge no html : use in alt attribute for accessibility",
                    " * @param  string  \$html   optional : label of badge with html",
                    " * @param  string  \$type   type of badge : Primary Secondary Success Danger Warning Info Light Dark status0 status1 status2 status3 status4 status5 status6 status7 status8 status9",
                    " * @param  string  \$mode   Default '' , 'pill', 'dot'",
                    " * @param  string  \$url    the url for link",
                    " * ... See more: core/lib/functions.lib.php ",
                    "",
                    "<?php print dolGetBadge('your label for accessibility', 'your label <u>with</u> <em>html</em>', 'primary'); ?>",
                    "<?php print dolGetBadge('your label for accessibility', 'your label <u>with</u> <em>html</em>', 'danger', 'pill'); ?>",
                    "<?php print dolGetBadge('your label for accessibility', 'your label', 'warning', 'dot'); ?>",
                );
                echo $documentation->showCode($lines); ?>
            </div>

            <!--  -->

        </div>

    </div>

<?php
// Output close body + html
$documentation->docFooter();
?>