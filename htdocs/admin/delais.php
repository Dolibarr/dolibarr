<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Simon Tosser            <simon@kornog-computing.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *   	\file       htdocs/admin/delais.php
 *		\brief      Page to setup late delays
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

$modules = array(
		'agenda' => array(
				array(
						'code' => 'MAIN_DELAY_ACTIONS_TODO',
						'img' => 'action'
				)
		),
		'projet' => array(
				array(
						'code' => 'MAIN_DELAY_PROJECT_TO_CLOSE',
						'img' => 'project'
				),
				array(
						'code' => 'MAIN_DELAY_TASKS_TODO',
						'img' => 'task'
				)
		),
        'propal' => array(
				array(
						'code' => 'MAIN_DELAY_PROPALS_TO_CLOSE',
						'img' => 'propal'
				),
				array(
						'code' => 'MAIN_DELAY_PROPALS_TO_BILL',
						'img' => 'propal'
				)
		),
		'commande' => array(
				array(
						'code' => 'MAIN_DELAY_ORDERS_TO_PROCESS',
						'img' => 'order'
				)
		),
		'facture' => array(
				array(
						'code' => 'MAIN_DELAY_CUSTOMER_BILLS_UNPAYED',
						'img' => 'bill'
				)
		),
		'fournisseur' => array(
				array(
						'code' => 'MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS',
						'img' => 'order'
				),
				array(
						'code' => 'MAIN_DELAY_SUPPLIER_BILLS_TO_PAY',
						'img' => 'bill'
				)
		),
		'service' => array(
				array(
						'code' => 'MAIN_DELAY_NOT_ACTIVATED_SERVICES',
						'img' => 'service'
				),
				array(
						'code' => 'MAIN_DELAY_RUNNING_SERVICES',
						'img' => 'service'
				)
		),
		'banque' => array(
				array(
						'code' => 'MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE',
						'img' => 'account'
				),
				array(
						'code' => 'MAIN_DELAY_CHEQUES_TO_DEPOSIT',
						'img' => 'account'
				)
		),
		'adherent' => array(
				array(
						'code' => 'MAIN_DELAY_MEMBERS',
						'img' => 'user'
				)
		),
		'expensereport' => array(
				array(
						'code' => 'MAIN_DELAY_EXPENSEREPORTS',
						'img' => 'trip'
				),
    		    /* TODO Enable this
		        array(
    		        'code' => 'MAIN_DELAY_EXPENSEREPORTS_TO_PAY',
    		        'img' => 'trip'
    		    )*/
		),
        'holiday' => array(
            array(
                'code' => 'MAIN_DELAY_HOLIDAYS',
                'img' => 'holiday'
            ),
        ),
);

$labelmeteo = array(0=>$langs->trans("No"), 1=>$langs->trans("Yes"), 2=>$langs->trans("OnMobileOnly"));

if (! isset($conf->global->MAIN_DELAY_PROJECT_TO_CLOSE)) {
	$conf->global->MAIN_DELAY_PROJECT_TO_CLOSE = 7;				// Must be same value than into conf.class.php
}
if (! isset($conf->global->MAIN_DELAY_TASKS_TODO)) {
	$conf->global->MAIN_DELAY_TASKS_TODO = 7;				// Must be same value than into conf.class.php
}
if (! isset($conf->global->MAIN_DELAY_MEMBERS)) {
	$conf->global->MAIN_DELAY_MEMBERS = 0;					// Must be same value than into conf.class.php
}
if (! isset($conf->global->MAIN_DELAY_ACTIONS_TODO)) {
	$conf->global->MAIN_DELAY_ACTIONS_TODO = 7;				// Must be same value than into conf.class.php
}
if (! isset($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS)) {
	$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS = 2;
}
if (! isset($conf->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS)) {
	$conf->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS = 7;
}
if (! isset($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS)) {
	$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS = 2;
}
if (! isset($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS)) {
	$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS = 2;
}
if (! isset($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS)) {
	$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS = 2;
}



/*
 * Actions
 */

if ($action == 'update')
{
	foreach ($modules as $module => $delays)
	{
		if (!empty($conf->$module->enabled))
    	{
    		foreach ($delays as $delay)
    		{
    			if (GETPOST($delay['code']) != '')
    			{
    				dolibarr_set_const($db, $delay['code'], GETPOST($delay['code']), 'chaine', 0, '', $conf->entity);
    			}
    		}
    	}
	}

	dolibarr_set_const($db, "MAIN_DISABLE_METEO", $_POST["MAIN_DISABLE_METEO"], 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_USE_METEO_WITH_PERCENTAGE", GETPOST("MAIN_USE_METEO_WITH_PERCENTAGE"), 'chaine', 0, '', $conf->entity);

	// For update value with percentage
	$plus = '';
	if (!empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) $plus = '_PERCENTAGE';
	// Update values
	for ($i = 0; $i < 4; $i++) {
    	if (isset($_POST['MAIN_METEO'.$plus.'_LEVEL'.$i])) dolibarr_set_const($db, 'MAIN_METEO'.$plus.'_LEVEL'.$i, GETPOST('MAIN_METEO'.$plus.'_LEVEL'.$i, 'int'), 'chaine', 0, '', $conf->entity);
    }
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("DelaysOfToleranceBeforeWarning"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->transnoentities("DelaysOfToleranceDesc", img_warning());
print " ".$langs->trans("OnlyActiveElementsAreShown", DOL_URL_ROOT.'/admin/modules.php')."</span><br>\n";
print "<br>\n";

if ($action == 'edit')
{
    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="form_index">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td class="center" width="120px">'.$langs->trans("Value").'</td></tr>';

    foreach ($modules as $module => $delays)
    {
    	if (!empty($conf->$module->enabled))
    	{
    		foreach ($delays as $delay)
    		{
				$value = (!empty($conf->global->{$delay['code']}) ? $conf->global->{$delay['code']}:0);
    			print '<tr class="oddeven">';
    			print '<td width="20px">'.img_object('', $delay['img']).'</td>';
    			print '<td>'.$langs->trans('Delays_'.$delay['code']).'</td><td>';
    			print '<input class="right maxwidth75" type="number" name="'.$delay['code'].'" value="'.$value.'"> '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

    print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td class="center" width="120px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td class="center">';
	print $form->selectarray('MAIN_DISABLE_METEO', $labelmeteo, (empty($conf->global->MAIN_DISABLE_METEO) ? 0 : $conf->global->MAIN_DISABLE_METEO));
	print '</td></tr>';

	print '</table>';
}
else
{
    /*
     * Show parameters
     */

	print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td class="center" width="120px">'.$langs->trans("Value").'</td></tr>';

    foreach ($modules as $module => $delays)
    {
    	if (!empty($conf->$module->enabled))
    	{
    		foreach ($delays as $delay)
    		{
    			$value = (!empty($conf->global->{$delay['code']}) ? $conf->global->{$delay['code']}:0);
    			print '<tr class="oddeven">';
    			print '<td width="20px">'.img_object('', $delay['img']).'</td>';
    			print '<td>'.$langs->trans('Delays_'.$delay['code']).'</td>';
    			print '<td class="right">'.$value.' '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

	print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td class="center" width="120px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td class="center">';
	print $labelmeteo[$conf->global->MAIN_DISABLE_METEO];
	print '</td></tr>';

	print '</table>';
}

print '<br>';

// Show logo for weather
print '<span class="opacitymedium">'.$langs->trans("DescWeather").'</span> ';

if ($action == 'edit') {
	$str_mode_std = $langs->trans('MeteoStdModEnabled').' : '.$langs->trans('MeteoUseMod', $langs->transnoentitiesnoconv('MeteoPercentageMod'));
	$str_mode_percentage = $langs->trans('MeteoPercentageModEnabled').' : '.$langs->trans('MeteoUseMod', $langs->transnoentitiesnoconv('MeteoStdMod'));
	if (empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) $str_mode_enabled = $str_mode_std;
	else $str_mode_enabled = $str_mode_percentage;
	print '<a href="#" onclick="return false;" id="change_mode">'.$str_mode_enabled.'</a>';
	print '<input type="hidden" id="MAIN_USE_METEO_WITH_PERCENTAGE" name="MAIN_USE_METEO_WITH_PERCENTAGE" value="'.$conf->global->MAIN_USE_METEO_WITH_PERCENTAGE.'" />';

	print '<br><br>';
} else {
	if (empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) print $langs->trans('MeteoStdModEnabled');
	else print $langs->trans('MeteoPercentageModEnabled');
	print '<br><br>';
}

$offset = 0;
$cursor = 10; // By default
//if (! empty($conf->global->MAIN_METEO_OFFSET)) $offset=$conf->global->MAIN_METEO_OFFSET;
//if (! empty($conf->global->MAIN_METEO_GAP)) $cursor=$conf->global->MAIN_METEO_GAP;
$level0 = $offset; if (!empty($conf->global->MAIN_METEO_LEVEL0)) $level0 = $conf->global->MAIN_METEO_LEVEL0;
$level1 = $offset + 1 * $cursor; if (!empty($conf->global->MAIN_METEO_LEVEL1)) $level1 = $conf->global->MAIN_METEO_LEVEL1;
$level2 = $offset + 2 * $cursor; if (!empty($conf->global->MAIN_METEO_LEVEL2)) $level2 = $conf->global->MAIN_METEO_LEVEL2;
$level3 = $offset + 3 * $cursor; if (!empty($conf->global->MAIN_METEO_LEVEL3)) $level3 = $conf->global->MAIN_METEO_LEVEL3;
$text = ''; $options = 'class="valignmiddle" height="60px"';


if ($action == 'edit') {
	print '<div id="standard" '.(empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? '' : 'style="display:none;"').'>';

	print '<div>';
	print '<div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 0, $options);
	print '= <input type="text" size="2" name="MAIN_METEO_LEVEL0" value="'.$level0.'"/></td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 1, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_LEVEL1" value="'.$level1.'"/></td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 2, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_LEVEL2" value="'.$level2.'"/></td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 3, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_LEVEL3" value="'.$level3.'"/></td>';
	print '</div>';
	print '</div>';

	print '</div>';

	print '<div id="percentage" '.(empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? 'style="display:none;"' : '').'>';

	print '<div>';
	print '<div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 0, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_PERCENTAGE_LEVEL0" value="'.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL0.'"/>&nbsp;%</td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 1, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_PERCENTAGE_LEVEL1" value="'.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL1.'"/>&nbsp;%</td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 2, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_PERCENTAGE_LEVEL2" value="'.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL2.'"/>&nbsp;%</td>';
	print '</div><div class="inline-block" style="padding-right: 20px">';
	print img_weather($text, 3, $options);
	print '&lt;= <input type="text" size="2" name="MAIN_METEO_PERCENTAGE_LEVEL3" value="'.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL3.'"/>&nbsp;%</td>';
	print '</div>';
	print '</div>';

	print '</div>';

	?>

	<script type="text/javascript">

		$(document).ready(function() {

			$("#change_mode").click(function() {
				var use_percent = $("#MAIN_USE_METEO_WITH_PERCENTAGE");
				var str_mode_std = "<?php print $str_mode_std; ?>";
				var str_mode_percentage = "<?php print $str_mode_percentage; ?>";

				if(use_percent.val() == 1) {
					use_percent.val(0);
					$("#standard").show();
					$("#percentage").hide();
					$(this).html(str_mode_std);
				} else {
					use_percent.val(1);
					$("#standard").hide();
					$("#percentage").show();
					$(this).html(str_mode_percentage);
				}
			});

		});

	</script>

	<?php
} else {
	if (!empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE)) {
		print '<div>';
		print '<div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 0, $options);
		print '= '.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL0.'&nbsp;%</td>';
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 1, $options);
		print '&lt;= '.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL1.'&nbsp;%</td>';
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 2, $options);
		print '&lt;= '.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL2.'&nbsp;%</td>';
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 3, $options);
		print '&lt;= '.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL3.'&nbsp;%</td>';
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 4, $options);
		print '&gt; '.$conf->global->MAIN_METEO_PERCENTAGE_LEVEL3.'&nbsp;%</td>';
		print '</div>';
		print '</div>';
	} else {
		print '<div>';
		print '<div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 0, $options);
		print '= '.$level0;
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 1, $options);
		print '&lt;= '.$level1;
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 2, $options);
		print '&lt;= '.$level2;
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 3, $options);
		print '&lt;= '.$level3;
		print '</div><div class="inline-block" style="padding-right: 20px">';
		print img_weather($text, 4, $options);
		print '&gt; '.$level3;
		print '</div>';
		print '</div>';
	}
}

print '</div>';

if ($action == 'edit') {
	print '<br><div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
	print '<br></form>';
} else {
	print '<br><div class="tabsAction">';
	print '<a class="butAction" href="delais.php?action=edit">'.$langs->trans("Modify").'</a></div>';
}

// End of page
llxFooter();
$db->close();
