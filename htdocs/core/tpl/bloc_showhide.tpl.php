<?php
/* Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

// Protection to avoid direct call of template
if (empty($blocname)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

$hide = true; // Hide by default
if (isset($parameters['showblocbydefault'])) {
	$hide = empty($parameters['showblocbydefault']);
}
if (isset($object->extraparams[$blocname]['showhide'])) {
	$hide = empty($object->extraparams[$blocname]['showhide']);
}

?>
<!-- BEGIN PHP TEMPLATE bloc_showhide.tpl.php -->

<?php
print '<script>'."\n";
print '$(document).ready(function() {'."\n";
print '$("#hide-'.$blocname.'").click(function(){'."\n";
print '		setShowHide(0);'."\n";
print '		$("#'.$blocname.'_bloc").hide("blind", {direction: "vertical"}, 300).removeClass("nohideobject");'."\n";
print '		$(this).hide();'."\n";
print '		$("#show-'.$blocname.'").show();'."\n";
print '});'."\n";

print '$("#show-'.$blocname.'").click(function(){'."\n";
print '		setShowHide(1);'."\n";
print '		$("#'.$blocname.'_bloc").show("blind", {direction: "vertical"}, 300).addClass("nohideobject");'."\n";
print '		$(this).hide();'."\n";
print '		$("#hide-'.$blocname.'").show();'."\n";
print '});'."\n";

print 'function setShowHide(status) {'."\n";
print '		var id			= '.((int) $object->id).";\n";
print "		var element		= '".dol_escape_js($object->element)."';\n";
print "		var htmlelement	= '".dol_escape_js($blocname)."';\n";
print '		var type		= "showhide";'."\n";
print '		$.get("'.dol_buildpath('/core/ajax/extraparams.php', 1);
print '?id="+id+"&element="+element+"&htmlelement="+htmlelement+"&type="+type+"&value="+status);'."\n";
print '}'."\n";

print '});'."\n";
print '</script>'."\n";

print '<div style="float:right; position: relative; top: 3px; right:5px;" id="hide-'.$blocname.'"';
print ' class="linkobject'.($hide ? ' hideobject' : '').'">'.img_picto('', '1uparrow.png').'</div>'."\n";
print '<div style="float:right; position: relative; top: 3px; right:5px;" id="show-'.$blocname.'"';
print ' class="linkobject'.($hide ? '' : ' hideobject').'">'.img_picto('', '1downarrow.png').'</div>'."\n";
print '<div id="'.$blocname.'_title" class="liste_titre">'.$title.'</div>'."\n";
print '<div id="'.$blocname.'_bloc" class="'.($hide ? 'hideobject' : 'nohideobject').'">'."\n";

include DOL_DOCUMENT_ROOT.'/core/tpl/'.$blocname.'.tpl.php';
print '</div><br>';
?>
<!-- END PHP TEMPLATE BLOCK SHOW/HIDE -->
