<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Simon Tosser            <simon@kornog-computing.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/admin/delais.php
 *		\brief      Page to setup late delays
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (! $user->admin) accessforbidden();

$action=GETPOST('action','alpha');

$modules=array(
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
);

if ($action == 'update')
{
	foreach($modules as $module => $delays)
	{
		if (! empty($conf->$module->enabled))
    	{
    		foreach($delays as $delay)
    		{
    			if (GETPOST($delay['code']) != '')
    			{
    				dolibarr_set_const($db, $delay['code'], GETPOST($delay['code']), 'chaine', 0, '', $conf->entity);
    			}
    		}
    	}
	}

    dolibarr_set_const($db, "MAIN_DISABLE_METEO",$_POST["MAIN_DISABLE_METEO"],'chaine',0,'',$conf->entity);
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("DelaysOfToleranceBeforeWarning"),'','title_setup');

print $langs->transnoentities("DelaysOfToleranceDesc",img_warning());
print " ".$langs->trans("OnlyActiveElementsAreShown",DOL_URL_ROOT.'/admin/modules.php')."<br>\n";
print "<br>\n";

$countrynotdefined='<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';

if ($action == 'edit')
{
    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="form_index">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

    foreach($modules as $module => $delays)
    {
    	if (! empty($conf->$module->enabled))
    	{
    		foreach($delays as $delay)
    		{
    			$var=!$var;
				$value=(! empty($conf->global->{$delay['code']})?$conf->global->{$delay['code']}:0);
    			print '<tr '.$bc[$var].'>';
    			print '<td width="20px">'.img_object('',$delay['img']).'</td>';
    			print '<td>'.$langs->trans('Delays_'.$delay['code']).'</td><td>';
    			print '<input size="5" name="'.$delay['code'].'" value="'.$value.'"> '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

    print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

	$var=false;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td>' .$form->selectyesno('MAIN_DISABLE_METEO',(empty($conf->global->MAIN_DISABLE_METEO)?0:1),1) . '</td></tr>';

	print '</table>';

	print '<br>';

    print '<br><div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
    print '<br>';

    print '</form>';
}
else
{
    /*
     * Affichage des parametres
     */

	print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';
    $var=true;

    foreach($modules as $module => $delays)
    {
    	if (! empty($conf->$module->enabled))
    	{
    		foreach($delays as $delay)
    		{
    			$var=!$var;
				$value=(! empty($conf->global->{$delay['code']})?$conf->global->{$delay['code']}:0);
    			print '<tr '.$bc[$var].'>';
    			print '<td width="20px">'.img_object('',$delay['img']).'</td>';
    			print '<td>'.$langs->trans('Delays_'.$delay['code']).'</td>';
    			print '<td>'.$value.' '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

	print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

	$var=false;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td>' . yn($conf->global->MAIN_DISABLE_METEO) . '</td></tr>';

	print '</table>';

	print '<br>';

    // Boutons d'action
    print '<div class="tabsAction">';
    print '<a class="butAction" href="delais.php?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';

}

print '<br>';


// Show logo for weather
print $langs->trans("DescWeather").'<br>';

$offset=0;
$cursor=10; // By default
//if (! empty($conf->global->MAIN_METEO_OFFSET)) $offset=$conf->global->MAIN_METEO_OFFSET;
//if (! empty($conf->global->MAIN_METEO_GAP)) $cursor=$conf->global->MAIN_METEO_GAP;
$level0=$offset;           if (! empty($conf->global->MAIN_METEO_LEVEL0)) $level0=$conf->global->MAIN_METEO_LEVEL0;
$level1=$offset+1*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL1)) $level1=$conf->global->MAIN_METEO_LEVEL1;
$level2=$offset+2*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL2)) $level2=$conf->global->MAIN_METEO_LEVEL2;
$level3=$offset+3*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL3)) $level3=$conf->global->MAIN_METEO_LEVEL3;
$text=''; $options='height="60px"';
print '<table>';
print '<tr>';
print '<td>';
print img_weather($text,'weather-clear.png',$options);
print '</td><td>= '.$level0.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_weather($text,'weather-few-clouds.png',$options);
print '</td><td>&lt;= '.$level1.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_weather($text,'weather-clouds.png',$options);
print '</td><td>&lt;= '.$level2.'</td>';
print '</tr>';

print '<tr><td>';
print img_weather($text,'weather-many-clouds.png',$options);
print '</td><td>&lt;= '.$level3.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_weather($text,'weather-storm.png',$options);
print '</td><td>&gt; '.$level3.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '</tr>';

print '</table>';


llxFooter();
$db->close();
