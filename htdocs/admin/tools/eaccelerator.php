<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/admin/tools/eaccelerator.php
 *     \brief      Page administration de eaccelerator
 */

require '../../main.inc.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();


/*
 * View
 */

llxHeader();


if (!function_exists('eaccelerator_info')) {
    print 'eAccelerator is not installed.';
    llxFooter();
    exit;
}


$info = eaccelerator_info();
if (isset($_POST['caching'])) {
    if ($info['cache']) {
        eaccelerator_caching(false);
    } else {
        eaccelerator_caching(true);
    }
} elseif (isset($_POST['optimizer']) && function_exists('eaccelerator_optimizer')) {
    if ($info['optimizer']) {
        eaccelerator_optimizer(false);
    } else {
        eaccelerator_optimizer(true);
    }
} elseif (isset($_POST['clear'])) {
    eaccelerator_clear();
} elseif (isset($_POST['clean'])) {
    eaccelerator_clean();
} elseif (isset($_POST['purge'])) {
    eaccelerator_purge();
}
$info = eaccelerator_info();
if (!is_array($info)) {
    dol_print_error('', 'An error occured getting eAccelerator information, this is caused if eAccelerator isn\'t initalised properly');
    exit;
}


/**
 * Compare revisions
 *
 * @param   array   $x  Parts of version 1
 * @param   array   $y  Parts of version 2
 * @return  int         -1 if 1<2, 0 if 1=2, 1 if 1>2
 */
function compare($x, $y)
{
    global $sortby;

    if ( $x[$sortby] == $y[$sortby] ) {
        return 0;
    } elseif ($x[$sortby] < $y[$sortby]) {
        return -1;
    } else {
        return 1;
    }
}

/**
 * Compare revisions
 *
 * @param   array   $x  Parts of version 1
 * @param   array   $y  Parts of version 2
 * @return  int         1 if 1<2, 0 if 1=2, -1 if 1>2
 */
function revcompare($x, $y)
{
    global $sortby;

    if ($x[$sortby] == $y[$sortby]) {
        return 0;
    } elseif ($x[$sortby] < $y[$sortby]) {
        return 1;
    } else {
        return -1;
    }
}

/**
 * Output table
 *
 * @param   array   $list   Array of records
 * @return  void
 */
function create_script_table($list)
{
    global $sortby,$langs;

    if (GETPOT('order') == "asc" || GETPOST('order') =="desc") {
        $order = GETPOST('order');
    } else {
        $order = "asc";
    }

    if (GETPOST('order')) {
        switch (GETPOST('order')) {
            case "mtime":
            case "size":
            case "reloads":
            case "hits":
                $sortby = GETPOST('sort');
                ($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
                break;
            default:
                $sortby = "file";
                ($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
        }
    }

    print '<table class="noborder">';
    print '<tr>';
    print '<th><a href="'.$_SERVER['PHP_SELF'].'?sort=file&order='.($order == "asc" ? "desc" : "asc").'">'.$langs->trans("Filename").'</a>&nbsp;';
    if($sortby == "file")
        print ($order == "asc" ? "&darr;" : "&uarr;").'</th>';
    print '<th><a href="'.$_SERVER['PHP_SELF'].'?sort=mtime&order='.($order == "asc" ? "desc" : "asc").'">'.$langs->trans("Date").'</a>&nbsp;';
    if($sortby == "mtime")
        print ($order == "asc" ? "&darr;" : "&uarr;").'</th>';
    print '<th><a href="'.$_SERVER['PHP_SELF'].'?sort=size&order='.($order == "asc" ? "desc" : "asc").'">'.$langs->trans("Size").'</a>&nbsp;';
    if($sortby == "size")
        print ($order == "asc" ? "&darr;" : "&uarr;").'</th>';
    print '<th><a href="'.$_SERVER['PHP_SELF'].'?sort=reloads&order='.($order == "asc" ? "desc" : "asc").'">'.$langs->trans("Reloads").'</a>&nbsp;';
    if($sortby == "reloads")
        print ($order == "asc" ? "&darr;" : "&uarr;").'</th>';
    print '<th><a href="'.$_SERVER['PHP_SELF'].'?sort=hits&order='.($order == "asc" ? "desc" : "asc").'">'.$langs->trans("Hits").'</a>&nbsp;';
    if($sortby == "hits")
        print ($order == "asc" ? "&darr;" : "&uarr;").'</th>';
    print '</tr>';
    switch ($sortby) {
        case "mtime":
        case "size":
        case "reloads":
        case "hits":
            ($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
            break;
        case "file":
        default:
            $sortby = "file";
            ($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
    }

    foreach($list as $script) {
        print '<tr class="oddeven">';
        print '<td>'.dol_trunc($script['file'], 80, 'left').'</td>';
        print '<td class="nowrap center">'.dol_print_date($script['mtime'], 'dayhour').'</td>';
        print '<td class="nowrap right">'.number_format($script['size'] / 1024, 2).'KB</td>';
        print '<td class="nowrap right">'.$script['reloads'].' ('.$script['usecount'].')</td>';
        print '<td class="nowrap right">'.$script['hits'].'</td>';
        print '</tr>';
    }
    print '</table>';
}

/**
 * Output table
 *
 * @param   array   $list       Array of records
 * @return  void
 */
function create_key_table($list)
{
    global $langs;

    print '<table class="noborder">';
    print '<tr class="liste_titre">';
    print '<th>Name</th>';
    print '<th>Created</th>';
    print '<th>'.$langs->trans("Size").'</th>';
    print '<th>ttl</th>';
    print '</tr>';

    foreach($list as $key) {
        print '<tr class="oddeven">';
        print '<td>'.dol_trunc($key['name'], 80, 'left').'</td>';
        print '<td class="nowrap center">'.dol_print_date($key['created'], 'dayhour').'</td>';
        print '<td class="nowrap right">'.number_format($key['size']/1024, 3).'KB</td>';
        print '<td class="nowrap right">';
        if ($key['ttl'] == -1) {
            print 'expired';
        } elseif ($key['ttl'] == 0) {
            print 'none';
        } else {
            print dol_print_date($key['ttl'], 'dayhour');
        }
        print '</td>';
        print '</tr>';
    }
    print '</table>';
}


$form=new Form($db);
print load_fiche_titre('Dolibarr eAccelerator '.$info['version'].' control panel', '', 'title_setup');

print '<br>';

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td colspan="2">Information</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Caching enabled</td>';
print '<td class="right">'.($info['cache']?'yes':'no').'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Optimizer enabled</td>';
print '<td class="right">'.$info['optimizer']?'yes':'no'.'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Memory usage</td>';
print '<td class="right">'.number_format(100 * $info['memoryAllocated']/$info['memorySize'], 2).'%('.number_format($info['memoryAllocated'] / (1024*1024), 2).'MB / '.number_format($info['memorySize']/(1024*1024), 2).'MB)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Free memory in reserved cache</td>';
print '<td class="right">'.number_format($info['memoryAvailable']/(1024*1024), 2).'MB</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Cached scripts</td>';
print '<td class="right">'.$info['cachedScripts'].'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Removed scripts</td>';
print '<td class="right">'.$info['removedScripts'].'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Cached keys</td>';
print '<td class="right">'.(isset($info['cachedKeys'])?$info['cachedKeys']:'').'</td>';
print '</tr>';
print '</table>';

$resCached = @eaccelerator_cached_scripts(); // If success return an array
$resRemoved = @eaccelerator_removed_scripts();

if (is_array($resCached) || is_array($resRemoved)) {
    print "<br>";
    print '<form name="ea_control" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder">';
    print '<tr class="liste_titre"><td colspan="2">Actions</td></tr>';

    if (is_array($resCached)) {
        print '<tr class="oddeven">';
        print "<td>Caching</td>";
        print '<td class="right"><input type="submit" class="butAction" name="caching" value="'. ($info['cache']?'disable':'enable') .'" /></td>';
        print "</tr>";

        print '<tr class="oddeven">';
        print "<td>Optimizer</td>";
        print '<td class="right"><input type="submit" class="butAction" name="optimizer" value="'. ($info['optimizer']?'disable':'enable') .'" /></td>';
        print "</tr>";
    }

    if (is_array($resRemoved)) {
        print '<tr class="oddeven">';
        print "<td>Clear cache</td>";
        print '<td class="right"><input type="submit" class="butAction" name="clear" value="clear" title="remove all unused scripts and data from shared memory and disk cache" /></td>';
        print "</tr>";

        print '<tr class="oddeven">';
        print "<td>Clean cache</td>";
        print '<td class="right"><input type="submit" class="butAction" name="clean" value="clean" title=" remove all expired scripts and data from shared memory and disk cache" /></td>';
        print "</tr>";

        print '<tr class="oddeven">';
        print "<td>Purge cache</td>";
        print '<td class="right"><input type="submit" class="butAction" name="purge" value="purge" title="remove all \'removed\' scripts from shared memory" /></td>';
        print "</tr></table></form>";
    }

    if (is_array($resCached)) {
        print "<br><br>";
        print "<b>Cached scripts</b><br>";
        create_script_table($resCached);
    }

    if (is_array($resRemoved)) {
        print "<br><br>";
        print "<b>Removed scripts</b><br>";
        create_script_table($resRemoved);
    }
} else {
    print "<br><br>";
    print "Check in your <b>php.ini</b> that <b>eaccelerator.allowed_admin_path</b> parameter is : ";
    print "<br><br>";
    print "<b>".$_SERVER["SCRIPT_FILENAME"]."</b>";
    print "<br><br>";
}

if (function_exists('eaccelerator_get')) {
    print '<br><br>';
    print '<b>Cached keys</b><br>';
    $res=eaccelerator_list_keys();
    create_key_table($res);
}

print "<br><br>";

// End of page
llxFooter();
$db->close();
