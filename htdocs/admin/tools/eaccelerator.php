<?php 
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id$
 * $Source$
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/admin/tools/eaccelerator.php
		\brief      Page administration de eaccelerator
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


llxHeader();


if (!function_exists('eaccelerator_info'))
{
    print 'eAccelerator is not installed.';
	llxfooter('$Date$ - $Revision$');
	exit;
}


/* {{{ process any commands */
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
	dolibarr_print_error('','An error occured getting eAccelerator information, this is caused if eAccelerator isn\'t initalised properly');
	exit;
}
/* }}} */

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

function revcompare($x, $y)
{
  global $sortby;

  if ( $x[$sortby] == $y[$sortby] )
    return 0;
  else if ( $x[$sortby] < $y[$sortby] )
    return 1;
  else
    return -1;
}
   
/* {{{ create_script_table */
function create_script_table($list)
{
	global $sortby;

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
    <table>
        <tr>
            <th><a href="<?php echo $_SERVER['PHP_SELF']?>?sort=file&order=<?php echo ($order == "asc" ? "desc" : "asc")?>">Filename</a>&nbsp;<? if($sortby == "file") echo ($order == "asc" ? "&darr;" : "&uarr;")?></th>
            <th><a href="<?php echo $_SERVER['PHP_SELF']?>?sort=mtime&order=<?php echo ($order == "asc" ? "desc" : "asc")?>">MTime</a>&nbsp;<? if($sortby == "mtime") echo ($order == "asc" ? "&darr;" : "&uarr;")?></th>
            <th><a href="<?php echo $_SERVER['PHP_SELF']?>?sort=size&order=<?php echo ($order == "asc" ? "desc" : "asc")?>">Size</a>&nbsp;<? if($sortby == "size") echo ($order == "asc" ? "&darr;" : "&uarr;")?></th>
            <th><a href="<?php echo $_SERVER['PHP_SELF']?>?sort=reloads&order=<?php echo ($order == "asc" ? "desc" : "asc")?>">Reloads</a>&nbsp;<? if($sortby == "reloads") echo ($order == "asc" ? "&darr;" : "&uarr;")?></th>
            <th><a href="<?php echo $_SERVER['PHP_SELF']?>?sort=hits&order=<?php echo ($order == "asc" ? "desc" : "asc")?>">Hits</a>&nbsp;<? if($sortby == "hits") echo ($order == "asc" ? "&darr;" : "&uarr;")?></th>
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
        <tr>
    <?php   if (function_exists('eaccelerator_dasm_file')) { ?>
            <td><a href="dasm.php?file=<?php echo $script['file']; ?>"><?php echo $script['file']; ?></a></td>
    <?php   } else { ?>
            <td><?php echo $script['file']; ?></td>
    <?php   } ?>
            <td class="vr"><?php echo date('Y-m-d H:i', $script['mtime']); ?></td>
            <td class="vr"><?php echo number_format($script['size'] / 1024, 2); ?> KB</td>
            <td class="vr"><?php echo $script['reloads']; ?> (<?php echo $script['usecount']; ?>)</td>
            <td class="vr"><?php echo $script['hits']; ?></td>
        </tr>
    <?php } ?>
    </table>
<?php 
}
/* }}} */

/* {{{ create_key_table */
function create_key_table($list) {
?>
    <table class="key">
        <tr>
            <th>Name</th>
            <th>Created</th>
            <th>Size</th>
            <th>ttl</th>
        </tr>
<?php
    foreach($list as $key) {
?>
        <tr>
            <td><?php echo $key['name']; ?></td>
            <td class="vr"><?php echo date('Y-m-d H:i', $key['created']); ?></td>
            <td class="vr"><?php echo number_format($key['size']/1024, 3); ?>KB</td>
            <td class="vr"><?php 
                if ($key['ttl'] == -1) {
                    echo 'expired';
                } elseif ($key['ttl'] == 0) {
                    echo 'none';
                } else {
                    echo date('Y-m-d H:i', $key['ttl']);
                }
            ?></td>
        </tr>
<?php
    }
?>
    </table>
<?php
}


$html=new Form($db);
print_fiche_titre('Dolibarr eAccelerator '.$info['version'].' control panel','','setup');
?>
<br>

<!-- {{{ information -->
<table>
<tr class="liste_titre"><td colspan="2">Information</td></tr>
<tr>
    <td>Caching enabled</td> 
    <td align="right"><?php echo $info['cache'] ? 'yes':'no' ?></td>
</tr>
<tr>
    <td>Optimizer enabled</td>
    <td align="right"><?php echo $info['optimizer'] ? 'yes':'no' ?></td>
</tr>
<tr>
    <td>Memory usage</td>
    <td align="right"><?php echo number_format(100 * $info['memoryAllocated'] / $info['memorySize'], 2); ?>% 
        (<?php echo number_format($info['memoryAllocated'] / (1024*1024), 2); ?>MB/
        <?php echo number_format($info['memorySize'] / (1024*1024), 2); ?>MB)</td>
</tr>
<tr>
    <td>Free memory in reserved cache</td>
    <td align="right"><?php echo number_format($info['memoryAvailable'] / (1024*1024), 2); ?>MB</td>
</tr>
<tr>
    <td>Cached scripts</td>
    <td align="right"><?php echo $info['cachedScripts']; ?></td>
</tr>
<tr>
    <td>Removed scripts</td> 
    <td align="right"><?php echo $info['removedScripts']; ?></td>
</tr>
<tr>
    <td>Cached keys</td>
    <td align="right"><?php echo $info['cachedKeys']; ?></td>
</tr>
</table>
<!-- }}} -->

<!-- {{{ control -->
<br>
<form name="ea_control" method="post">
    <table>
		<tr class="liste_titre"><td colspan="2">Actions</td></tr>
        <tr>
            <td>Caching</td>
            <td align="right"><input type="submit" name="caching" value="<?php echo $info['cache']?'disable':'enable'; ?>" /></td>
        </tr>
        <tr>
            <td>Optimizer</td>
            <td align="right"><input type="submit" name="optimizer" value="<?php echo $info['optimizer']?'disable':'enable'; ?>" /></td>
        </tr>
        <tr>
            <td>Clear cache</td>
            <td align="right"><input type="submit" name="clear" value="clear" title="remove all unused scripts and data from shared memory and disk cache" /></td>
        </tr>
        <tr>
            <td>Clean cache</td>
            <td align="right"><input type="submit" name="clean" value="clean" title=" remove all expired scripts and data from shared memory and disk cache" /></td>
        </tr>
        <tr>
            <td>Purge cache</td>
            <td align="right"><input type="submit" name="purge" value="purge" title="remove all 'removed' scripts from shared memory" /></td>
        </tr>
    </table>
</form>
<!-- }}} -->

<br><br>
<table>
<tr class="liste_titre"><td colspan="2">Cached scripts</td></tr></table>
<?php
$res=eaccelerator_cached_scripts();			// If success return an array
if (is_array($res)) create_script_table($res);
else print "Check in your <b>php.ini</b> that <b>eaccelerator.allowed_admin_path</b> parameter is "._FILE;
?>
 
<br><br>
<table>
<tr class="liste_titre"><td colspan="2">Removed scripts</td></tr></table>
<?php
$res=eaccelerator_removed_scripts();
if (is_array($res)) create_script_table($res);
else print "Check in your <b>php.ini</b> that <b>eaccelerator.allowed_admin_path</b> parameter is "._FILE;


if (function_exists('eaccelerator_get')) {
?>
	<br><br>
	<table>
	<tr class="liste_titre"><td colspan="2">Cached keys</td></tr></table>
<?php
    $res=eaccelerator_list_keys();
	create_key_table($res);
}
?>

<br /><br />
<hr />
<table>
    <tr><td class="center">
    <strong>Eaccelerator is created by the eAccelerator team, <a href="http://eaccelerator.net">http://eaccelerator.net</a></strong><br /><br />
    </td></tr>
</table>


<?php
llxfooter('$Date$ - $Revision$');
?>
