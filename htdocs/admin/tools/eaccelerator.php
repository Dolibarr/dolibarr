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


if (!function_exists('eaccelerator_info'))
{
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
} else if (isset($_POST['optimizer']) && function_exists('eaccelerator_optimizer')) {
	if ($info['optimizer']) {
		eaccelerator_optimizer(false);
	} else {
		eaccelerator_optimizer(true);
	}
} else if (isset($_POST['clear'])) {
	eaccelerator_clear();
} else if (isset($_POST['clean'])) {
	eaccelerator_clean();
} else if (isset($_POST['purge'])) {
	eaccelerator_purge();
}
$info = eaccelerator_info();
if (!is_array($info)) {
	dol_print_error('','An error occured getting eAccelerator information, this is caused if eAccelerator isn\'t initalised properly');
	exit;
}



function compare($x, $y)
{
	global $sortby;

	if ( $x[$sortby] == $y[$sortby] )
	return 0;
	else if ( $x[$sortby] < $y[$sortby] )
	return -1;
	else
	return 1;
}

/**
 * Compare revisions
 *
 * @param 	array 	$x	Parts of version 1
 * @param 	array 	$y	Parts of version 2
 * @return	int			1 if 1<2, 0 if 1=2, -1 if 1>2
 */
function revcompare($x, $y)
{
	global $sortby, $langs;

	if ( $x[$sortby] == $y[$sortby] )
	return 0;
	else if ( $x[$sortby] < $y[$sortby] )
	return 1;
	else
	return -1;
}

/**
 * Output table
 *
 * @param 	array	$list	Array of records
 * @return 	void
 */
function create_script_table($list)
{
	global $sortby,$bc,$langs;
	$var=true;

	if (isset($_GET['order']) && ($_GET['order'] == "asc" || $_GET['order'] =="desc")) {
		$order = $_GET['order'];
	} else {
		$order = "asc";
	}

	if (isset($_GET['sort'])) {
		switch ($_GET['sort']) {
			case "mtime":
			case "size":
			case "reloads":
			case "hits":
				$sortby = $_GET['sort'];
				($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
				break;
			default:
				$sortby = "file";
				($order == "asc" ? uasort($list, 'compare') : uasort($list, 'revcompare'));
		}
	}

	?>
<table class="noborder">
	<tr>
		<th><a
			href="<?php print $_SERVER['PHP_SELF']?>?sort=file&order=<?php print ($order == "asc" ? "desc" : "asc")?>"><?php print $langs->trans("Filename"); ?></a>&nbsp;<?php if($sortby == "file") print ($order == "asc" ? "&darr;" : "&uarr;") ?></th>
		<th><a
			href="<?php print $_SERVER['PHP_SELF']?>?sort=mtime&order=<?php print ($order == "asc" ? "desc" : "asc")?>"><?php print $langs->trans("Date"); ?></a>&nbsp;<?php if($sortby == "mtime") print ($order == "asc" ? "&darr;" : "&uarr;") ?></th>
		<th><a
			href="<?php print $_SERVER['PHP_SELF']?>?sort=size&order=<?php print ($order == "asc" ? "desc" : "asc")?>"><?php print $langs->trans("Size"); ?></a>&nbsp;<?php if($sortby == "size") print ($order == "asc" ? "&darr;" : "&uarr;") ?></th>
		<th><a
			href="<?php print $_SERVER['PHP_SELF']?>?sort=reloads&order=<?php print ($order == "asc" ? "desc" : "asc")?>"><?php print $langs->trans("Reloads"); ?></a>&nbsp;<?php if($sortby == "reloads") print ($order == "asc" ? "&darr;" : "&uarr;") ?></th>
		<th><a
			href="<?php print $_SERVER['PHP_SELF']?>?sort=hits&order=<?php print ($order == "asc" ? "desc" : "asc")?>"><?php print $langs->trans("Hits"); ?></a>&nbsp;<?php if($sortby == "hits") print ($order == "asc" ? "&darr;" : "&uarr;") ?></th>
	</tr>
	<?php
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

	foreach($list as $script) { ?>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td><?php print dol_trunc($script['file'],80,'left'); ?></td>
		<td align="center" class="nowrap"><?php print dol_print_date($script['mtime'],'dayhour'); ?></td>
		<td align="right" class="nowrap"><?php print number_format($script['size'] / 1024, 2); ?>
		KB</td>
		<td align="right" class="nowrap"><?php print $script['reloads']; ?> (<?php print $script['usecount']; ?>)</td>
		<td align="right" class="nowrap"><?php print $script['hits']; ?></td>
	</tr>
	<?php } ?>
</table>
	<?php
}

/**
 * Output table
 *
 * @param	array	$list		Array of records
 * @return	void
 */
function create_key_table($list)
{
	global $bc,$langs;
	$var=true;
	?>
<table class="noborder">
	<tr class="liste_titre">
		<th>Name</th>
		<th>Created</th>
		<th><?php print $langs->trans("Size"); ?></th>
		<th>ttl</th>
	</tr>
	<?php
	foreach($list as $key) {
		?>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td><?php print dol_trunc($key['name'],80,'left'); ?></td>
		<td align="center" class="nowrap"><?php dol_print_date($key['created'],'dayhour'); ?></td>
		<td align="right" class="nowrap"><?php print number_format($key['size']/1024, 3); ?>
		KB</td>
		<td align="right" class="nowrap"><?php
		if ($key['ttl'] == -1) {
			print 'expired';
		} elseif ($key['ttl'] == 0) {
			print 'none';
		} else {
			print dol_print_date($key['ttl'],'dayhour');
		}
		?></td>
	</tr>
	<?php
	}
	?>
</table>
	<?php
}


$form=new Form($db);
print_fiche_titre('Dolibarr eAccelerator '.$info['version'].' control panel','','setup');

$var=true;

?>
<br>


<table class="noborder">
	<tr class="liste_titre">
		<td colspan="2">Information</td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Caching enabled</td>
		<td align="right"><?php print $info['cache'] ? 'yes':'no' ?></td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Optimizer enabled</td>
		<td align="right"><?php print $info['optimizer'] ? 'yes':'no' ?></td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Memory usage</td>
		<td align="right"><?php print number_format(100 * $info['memoryAllocated'] / $info['memorySize'], 2); ?>%
		(<?php print number_format($info['memoryAllocated'] / (1024*1024), 2); ?>
		MB / <?php print number_format($info['memorySize'] / (1024*1024), 2); ?>
		MB)</td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Free memory in reserved cache</td>
		<td align="right"><?php print number_format($info['memoryAvailable'] / (1024*1024), 2); ?>MB</td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Cached scripts</td>
		<td align="right"><?php print $info['cachedScripts']; ?></td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Removed scripts</td>
		<td align="right"><?php print $info['removedScripts']; ?></td>
	</tr>
	<tr <?php $var = ! $var; print $bc[$var]; ?>>
		<td>Cached keys</td>
		<td align="right"><?php print (isset($info['cachedKeys'])?$info['cachedKeys']:''); ?></td>
	</tr>
</table>
<?php

$var=true;

$resCached = @eaccelerator_cached_scripts();			// If success return an array
$resRemoved = @eaccelerator_removed_scripts();

if (is_array($resCached) || is_array($resRemoved))
{
	print "<br>";
	print '<form name="ea_control" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder">';
	print '<tr class="liste_titre"><td colspan="2">Actions</td></tr>';

	if (is_array($resCached))
	{
		$var = ! $var;
		print "<tr ".$bc[$var].">";
		print "<td>Caching</td>";
		print '<td align="right"><input type="submit" class="butAction" name="caching" value="'. ($info['cache']?'disable':'enable') .'" /></td>';
		print "</tr>";
		$var = ! $var;
		print "<tr ".$bc[$var].">";
		print "<td>Optimizer</td>";
		print '<td align="right"><input type="submit" class="butAction" name="optimizer" value="'. ($info['optimizer']?'disable':'enable') .'" /></td>';
		print "</tr>";
	}

	if (is_array($resRemoved))
	{
		$var = ! $var;
		print "<tr ".$bc[$var].">";
		print "<td>Clear cache</td>";
		print '<td align="right"><input type="submit" class="butAction" name="clear" value="clear" title="remove all unused scripts and data from shared memory and disk cache" /></td>';
		print "</tr>";
		$var = ! $var;
		print "<tr ".$bc[$var].">";
		print "<td>Clean cache</td>";
		print '<td align="right"><input type="submit" class="butAction" name="clean" value="clean" title=" remove all expired scripts and data from shared memory and disk cache" /></td>';
		print "</tr>";
		$var = ! $var;
		print "<tr ".$bc[$var].">";
		print "<td>Purge cache</td>";
		print '<td align="right"><input type="submit" class="butAction" name="purge" value="purge" title="remove all \'removed\' scripts from shared memory" /></td>';
		print "</tr></table></form>";
	}

	if (is_array($resCached))
	{
		print "<br><br>";
		print "<b>Cached scripts</b><br>";
		create_script_table($resCached);
	}

	if (is_array($resRemoved))
	{
		print "<br><br>";
		print "<b>Removed scripts</b><br>";
		create_script_table($resRemoved);
	}
}
else
{
	print "<br><br>";
	print "Check in your <b>php.ini</b> that <b>eaccelerator.allowed_admin_path</b> parameter is : ";
	print "<br><br>";
	print "<b>".$_SERVER["SCRIPT_FILENAME"]."</b>";
	print "<br><br>";
}

if (function_exists('eaccelerator_get'))
{
	print '<br><br>';
	print '<b>Cached keys</b><br>';
	$res=eaccelerator_list_keys();
	create_key_table($res);
}

print "<br><br>";


llxFooter();

$db->close();
?>
