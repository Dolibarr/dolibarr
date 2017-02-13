<?php
/* Copyright (C) 2010 Regis Houssin <regis.houssin@capnetworks.com>
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
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<table class="notopnoleftnoright allwidth" style="margin-bottom: 2px;">
<tr>
	<td class="nobordernopadding" width="40" align="left" valign="middle">
		<?php echo $title_picto; ?>
	</td>
	<td class="nobordernopadding" valign="middle">
    	<div class="titre"><?php echo $title_text; ?></div>
	</td>
</tr>
</table>

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" name="formulaire">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="list">
<input type="hidden" name="sortfield" value="<?php echo $sortfield; ?>">
<input type="hidden" name="sortorder" value="<?php echo $sortorder; ?>">
<input type="hidden" name="canvas" value="service">
<input type="hidden" name="type" value="1">

<table class="liste allwidth">

<!-- FIELDS TITLE -->

<tr class="liste_titre">
	<?php
 	foreach($fieldlist as $field) {
 		if ($field['enabled']) {
 			if ($field['sort'])	{ ?>
 				<td class="liste_titre" align="<?php echo $field['align']; ?>"><?php echo $field['title']; ?>
 					<a href="<?php echo $_SERVER["PHP_SELF"];?>?sortfield=<?php echo $field['name']; ?>&amp;sortorder=asc&amp;begin=&amp;tosell=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
 						<img src="<?php echo DOL_URL_ROOT; ?>/theme/<?php echo $conf->theme; ?>/img/1downarrow.png" border="0" alt="A-Z" title="A-Z">
 					</a>
  					<a href="<?php echo $_SERVER["PHP_SELF"];?>?sortfield=<?php echo $field['name']; ?>&amp;sortorder=desc&amp;begin=&amp;tosell=&amp;canvas=default&amp;fourn_id=&amp;snom=&amp;sref=">
  						<img src="<?php echo DOL_URL_ROOT; ?>/theme/<?php echo $conf->theme; ?>/img/1uparrow.png" border="0" alt="Z-A" title="Z-A">
  					</a>
  				</td>
  		<?php } else { ?>
  				<td class="liste_titre" align="<?php echo $field['align']; ?>"><?php echo $field['title']; ?></td>
	<?php } } } ?>
</tr>

 <!-- FIELDS SEARCH -->

<tr class="liste_titre">
	<?php
 	$num = count($fieldlist);
 	foreach($fieldlist as $key => $searchfield)	{
 		if ($searchfield['enabled']) {
 			if ($searchfield['search'])	{ ?>
  				<td class="liste_titre" align="<?php echo $searchfield['align']; ?>"><input class="flat" type="text" name="s<?php echo $searchfield['alias']; ?>" value=""></td>
	<?php } else if ($key == $num) { 	
        print '<td class="liste_titre" align="right">';
        $searchpitco=$form->showFilterAndCheckAddButtons(0);
        print $searchpitco;
        print '</td>';
 			} else { ?>
  			<td class="liste_titre">&nbsp;</td>
 	<?php } } } ?>
</tr>

<!-- FIELDS DATA -->

<?php
$var=True;
foreach($datas as $line) {
	$var=!$var;	?>
	<tr <?php echo $bc[$var]; ?>>
   		<?php
   		foreach($line as $key => $value) {
   			foreach($fieldlist as $field) {
   				if ($field['alias'] == $key) { ?>
   					<td align="<?php echo $field['align']; ?>"><?php echo $value; ?></td>
   		<?php } } } ?>
   	</tr>
<?php } ?>

</table>
</form>

<!-- END PHP TEMPLATE -->